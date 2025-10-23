import { NextRequest, NextResponse } from 'next/server';
import { z } from 'zod';
import { prisma } from '@tts-pms/db';
import { createApiResponse, createApiError } from '@tts-pms/infra';
import { validateAgentRequest } from '@/lib/agent-auth';

const TimerStartSchema = z.object({
  timestamp: z.string().transform((str: string) => new Date(str)),
  timezone: z.string().optional(),
  metadata: z.record(z.any()).optional(),
});

// POST /api/agent/timer/start - Start timer session (JWT+HMAC)
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
      // Log the request even though it's outside operational window
      await prisma.agentRequestLog.create({
        data: {
          userId: userId!,
          deviceId: deviceId!,
          endpoint: '/api/agent/timer/start',
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
    const validation = TimerStartSchema.safeParse(body);
    if (!validation.success) {
      return NextResponse.json(
        createApiError('Invalid timer start data', 'VALIDATION_ERROR'),
        { status: 400 }
      );
    }

    const { timestamp, timezone, metadata } = validation.data;

    // Check for existing active session
    const activeSession = await prisma.timerSession.findFirst({
      where: {
        userId: userId!,
        deviceId,
        endedAt: null,
      }
    });

    if (activeSession) {
      return NextResponse.json(
        createApiError('Timer session already active', 'SESSION_ACTIVE'),
        { status: 409 }
      );
    }

    // Create new timer session
    const session = await prisma.timerSession.create({
      data: {
        userId: userId!,
        startedAt: timestamp,
        deviceId,
        ip: request.headers.get('x-forwarded-for') || 
            request.headers.get('x-real-ip') || 
            'unknown',
        inactivityPauses: {
          timezone,
          metadata,
          startInfo: {
            userAgent: request.headers.get('user-agent'),
            timestamp: new Date().toISOString(),
          }
        }
      }
    });

    return NextResponse.json(
      createApiResponse({
        sessionId: session.id,
        startedAt: session.startedAt,
        deviceId: session.deviceId,
        serverTime: new Date().toISOString(),
      }, 'Timer started successfully')
    );

  } catch (error) {
    console.error('Timer start error:', error);
    return NextResponse.json(
      createApiError('Internal server error', 'INTERNAL_ERROR'),
      { status: 500 }
    );
  }
}
