import { NextRequest, NextResponse } from 'next/server';
import { z } from 'zod';
import { prisma } from '@tts-pms/db';
import { createApiResponse, createApiError } from '@tts-pms/infra';
import { validateAgentRequest } from '@/lib/agent-auth';

const TimerPauseSchema = z.object({
  timestamp: z.string().transform((str: string) => new Date(str)),
  reason: z.enum(['break', 'meeting', 'technical_issue', 'personal', 'other']).optional(),
  duration: z.number().positive().optional(), // Expected pause duration in seconds
});

// POST /api/agent/timer/pause - Pause timer session (JWT+HMAC)
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
          endpoint: '/api/agent/timer/pause',
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
    const validation = TimerPauseSchema.safeParse(body);
    if (!validation.success) {
      return NextResponse.json(
        createApiError('Invalid timer pause data', 'VALIDATION_ERROR'),
        { status: 400 }
      );
    }

    const { timestamp, reason, duration } = validation.data;

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

    // Calculate active time up to pause
    const activeSeconds = Math.floor(
      (timestamp.getTime() - activeSession.startedAt.getTime()) / 1000
    );

    // Update session with pause information
    const currentPauses = (activeSession.inactivityPauses as any) || {};
    const pauseId = `pause_${Date.now()}`;
    
    const updatedPauses = {
      ...currentPauses,
      pauses: {
        ...(currentPauses.pauses || {}),
        [pauseId]: {
          startTime: timestamp.toISOString(),
          reason,
          expectedDuration: duration,
          serverTime: new Date().toISOString(),
        }
      }
    };

    const updatedSession = await prisma.timerSession.update({
      where: { id: activeSession.id },
      data: {
        activeSeconds,
        inactivityPauses: updatedPauses,
      }
    });

    return NextResponse.json(
      createApiResponse({
        sessionId: updatedSession.id,
        pauseId,
        pausedAt: timestamp,
        activeSecondsBeforePause: activeSeconds,
        serverTime: new Date().toISOString(),
      }, 'Timer paused successfully')
    );

  } catch (error) {
    console.error('Timer pause error:', error);
    return NextResponse.json(
      createApiError('Internal server error', 'INTERNAL_ERROR'),
      { status: 500 }
    );
  }
}
