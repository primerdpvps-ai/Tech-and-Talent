import { NextRequest, NextResponse } from 'next/server';
import { z } from 'zod';
import { prisma } from '@tts-pms/db';
import { createApiResponse, createApiError } from '@tts-pms/infra';
import { validateAgentRequest } from '@/lib/agent-auth';

const TimerStopSchema = z.object({
  timestamp: z.string().transform((str: string) => new Date(str)),
  summary: z.object({
    totalActiveSeconds: z.number().min(0),
    totalPauseSeconds: z.number().min(0).optional(),
    tasksSummary: z.array(z.string()).optional(),
    notes: z.string().optional(),
  }).optional(),
});

// POST /api/agent/timer/stop - Stop timer session (JWT+HMAC)
export async function POST(request: NextRequest) {
  try {
    // Validate agent request with HMAC signature
    const authResult = await validateAgentRequest(request, true);
    if (!authResult.valid) {
      return NextResponse.json(
        createApiError(authResult.error || 'Unauthorized', 'UNAUTHORIZED'),
        { status: 401 }
      );
    }

    const { userId, deviceId, operationalWindow } = authResult;

    // Check operational window and log if outside
    if (operationalWindow && !operationalWindow.allowed) {
      await prisma.agentRequestLog.create({
        data: {
          userId: userId!,
          deviceId: deviceId!,
          endpoint: '/api/agent/timer/stop',
          method: 'POST',
          ip: request.headers.get('x-forwarded-for') || 
              request.headers.get('x-real-ip') || 
              'unknown',
          userAgent: request.headers.get('user-agent') || '',
          timestamp: new Date(),
          status: 'REJECTED_OPERATIONAL_WINDOW',
          reason: operationalWindow.reason,
          requestBody: await request.text()
        }
      });

      return NextResponse.json(
        createApiError(`Request outside operational window: ${operationalWindow.reason}`, 'OPERATIONAL_WINDOW'),
        { status: 403 }
      );
    }

    const body = await request.json();
    const validation = TimerStopSchema.safeParse(body);
    if (!validation.success) {
      return NextResponse.json(
        createApiError('Invalid timer stop data', 'VALIDATION_ERROR'),
        { status: 400 }
      );
    }

    const { timestamp, summary } = validation.data;

    // Find active session
    const activeSession = await prisma.timerSession.findFirst({
      where: {
        userId: userId!,
        deviceId,
        endedAt: null,
      }
    });

    if (!activeSession) {
      return NextResponse.json(
        createApiError('No active timer session found', 'NO_ACTIVE_SESSION'),
        { status: 404 }
      );
    }

    // Calculate final active seconds
    const totalSessionSeconds = Math.floor(
      (timestamp.getTime() - activeSession.startedAt.getTime()) / 1000
    );
    
    // Use provided summary or calculate from session data
    const finalActiveSeconds = summary?.totalActiveSeconds || 
      Math.max(0, totalSessionSeconds - (summary?.totalPauseSeconds || 0));

    // Update session with final data
    const currentPauses = (activeSession.inactivityPauses as any) || {};
    const finalPauses = {
      ...currentPauses,
      sessionSummary: {
        endTime: timestamp.toISOString(),
        totalSessionSeconds,
        finalActiveSeconds,
        summary,
        serverTime: new Date().toISOString(),
      }
    };

    const completedSession = await prisma.timerSession.update({
      where: { id: activeSession.id },
      data: {
        endedAt: timestamp,
        activeSeconds: finalActiveSeconds,
        inactivityPauses: finalPauses,
      }
    });

    // Update or create daily summary
    const sessionDate = new Date(activeSession.startedAt);
    sessionDate.setHours(0, 0, 0, 0);

    const existingDailySummary = await prisma.dailySummary.findUnique({
      where: {
        userId_date: {
          userId: userId!,
          date: sessionDate,
        }
      }
    });

    const newBillableSeconds = (existingDailySummary?.billableSeconds || 0) + finalActiveSeconds;
    const meetsDailyMinimum = newBillableSeconds >= (6 * 60 * 60); // 6 hours minimum

    await prisma.dailySummary.upsert({
      where: {
        userId_date: {
          userId: userId!,
          date: sessionDate,
        }
      },
      update: {
        billableSeconds: newBillableSeconds,
        meetsDailyMinimum,
      },
      create: {
        userId: userId!,
        date: sessionDate,
        billableSeconds: finalActiveSeconds,
        meetsDailyMinimum,
        uploadsDone: false,
      }
    });

    return NextResponse.json(
      createApiResponse({
        sessionId: completedSession.id,
        startedAt: completedSession.startedAt,
        endedAt: completedSession.endedAt,
        totalSessionSeconds,
        activeSeconds: finalActiveSeconds,
        dailyTotal: newBillableSeconds,
        dailyTotalHours: Math.round((newBillableSeconds / 3600) * 100) / 100,
        meetsDailyMinimum,
        serverTime: new Date().toISOString(),
      }, 'Timer stopped successfully')
    );

  } catch (error) {
    console.error('Timer stop error:', error);
    return NextResponse.json(
      createApiError('Internal server error', 'INTERNAL_ERROR'),
      { status: 500 }
    );
  }
}
