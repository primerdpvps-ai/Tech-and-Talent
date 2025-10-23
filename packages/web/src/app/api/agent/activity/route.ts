import { NextRequest, NextResponse } from 'next/server';
import { z } from 'zod';
import { prisma } from '@tts-pms/db';
import { createApiResponse, createApiError } from '@tts-pms/infra';
import { validateAgentRequest } from '@/lib/agent-auth';

const ActivityBatchSchema = z.object({
  activities: z.array(z.object({
    timestamp: z.string().transform((str: string) => new Date(str)),
    type: z.enum(['mouse_move', 'mouse_click', 'key_press', 'window_focus', 'app_switch', 'idle_start', 'idle_end']),
    data: z.record(z.any()).optional(),
    sessionId: z.string().optional(),
  })),
  batchId: z.string().optional(),
  deviceInfo: z.object({
    screenResolution: z.string().optional(),
    activeWindow: z.string().optional(),
    cpuUsage: z.number().optional(),
    memoryUsage: z.number().optional(),
  }).optional(),
});

// POST /api/agent/activity - Submit activity batch (JWT+HMAC)
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
          endpoint: '/api/agent/activity',
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
    const validation = ActivityBatchSchema.safeParse(body);
    if (!validation.success) {
      return NextResponse.json(
        createApiError('Invalid activity batch data', 'VALIDATION_ERROR'),
        { status: 400 }
      );
    }

    const { activities, batchId, deviceInfo } = validation.data;

    if (activities.length === 0) {
      return NextResponse.json(
        createApiError('Activity batch cannot be empty', 'EMPTY_BATCH'),
        { status: 400 }
      );
    }

    // Validate batch size (max 1000 activities per batch)
    if (activities.length > 1000) {
      return NextResponse.json(
        createApiError('Activity batch too large (max 1000 activities)', 'BATCH_TOO_LARGE'),
        { status: 400 }
      );
    }

    // Get current active session if sessionId not provided
    let sessionId = activities[0]?.sessionId;
    if (!sessionId) {
      const activeSession = await prisma.timerSession.findFirst({
        where: {
          userId: userId!,
          deviceId,
          endedAt: null,
        },
        select: { id: true }
      });

      if (!activeSession) {
        return NextResponse.json(
          createApiError('No active session found and no sessionId provided', 'NO_ACTIVE_SESSION'),
          { status: 404 }
        );
      }

      sessionId = activeSession.id;
    }

    // Process activities and calculate metrics
    const processedActivities = activities.map(activity => ({
      ...activity,
      sessionId,
      userId: userId!,
      deviceId,
    }));

    // Calculate activity metrics
    const activityMetrics = {
      totalActivities: activities.length,
      mouseEvents: activities.filter(a => a.type.startsWith('mouse_')).length,
      keyboardEvents: activities.filter(a => a.type === 'key_press').length,
      windowEvents: activities.filter(a => ['window_focus', 'app_switch'].includes(a.type)).length,
      idleEvents: activities.filter(a => ['idle_start', 'idle_end'].includes(a.type)).length,
      timeSpan: {
        start: Math.min(...activities.map(a => a.timestamp.getTime())),
        end: Math.max(...activities.map(a => a.timestamp.getTime())),
      }
    };

    // Store activity data (in a real implementation, you might use a time-series database)
    // For now, we'll store a summary in the timer session
    await prisma.timerSession.update({
      where: { id: sessionId },
      data: {
        inactivityPauses: {
          ...(await prisma.timerSession.findUnique({
            where: { id: sessionId },
            select: { inactivityPauses: true }
          }))?.inactivityPauses as any || {},
          activityBatches: {
            ...((await prisma.timerSession.findUnique({
              where: { id: sessionId },
              select: { inactivityPauses: true }
            }))?.inactivityPauses as any)?.activityBatches || {},
            [batchId || `batch_${Date.now()}`]: {
              timestamp: new Date().toISOString(),
              metrics: activityMetrics,
              deviceInfo,
              activitiesCount: activities.length,
            }
          }
        }
      }
    });

    // Calculate activity score (for productivity metrics)
    const activityScore = Math.min(100, 
      (activityMetrics.mouseEvents * 0.5) + 
      (activityMetrics.keyboardEvents * 1.0) + 
      (activityMetrics.windowEvents * 0.3)
    );

    return NextResponse.json(
      createApiResponse({
        batchId: batchId || `batch_${Date.now()}`,
        sessionId,
        processed: activities.length,
        metrics: activityMetrics,
        activityScore: Math.round(activityScore),
        serverTime: new Date().toISOString(),
        nextBatchRecommendedIn: 300, // 5 minutes
      }, 'Activity batch processed successfully')
    );

  } catch (error) {
    console.error('Activity batch error:', error);
    return NextResponse.json(
      createApiError('Internal server error', 'INTERNAL_ERROR'),
      { status: 500 }
    );
  }
}
