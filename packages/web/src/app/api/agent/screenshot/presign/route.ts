import { NextRequest, NextResponse } from 'next/server';
import { z } from 'zod';
import { prisma } from '@tts-pms/db';
import { createApiResponse, createApiError } from '@tts-pms/infra';
import { validateAgentRequest } from '@/lib/agent-auth';
import { generateSecureToken } from '@tts-pms/infra';

const ScreenshotPresignSchema = z.object({
  timestamp: z.string().transform((str: string) => new Date(str)),
  sessionId: z.string().optional(),
  screenshotType: z.enum(['periodic', 'random', 'activity_triggered', 'manual']).default('periodic'),
  quality: z.enum(['low', 'medium', 'high']).default('medium'),
  metadata: z.object({
    screenResolution: z.string().optional(),
    activeWindow: z.string().optional(),
    mousePosition: z.object({
      x: z.number(),
      y: z.number(),
    }).optional(),
  }).optional(),
});

// Mock S3 presigned URL generation for screenshots
function generateScreenshotPresignedUrl(fileKey: string): {
  uploadUrl: string;
  fileUrl: string;
  fields: Record<string, string>;
} {
  const bucket = process.env.S3_BUCKET_NAME || 'tts-pms-screenshots';
  const region = process.env.S3_REGION || 'us-east-1';
  
  const uploadUrl = `https://${bucket}.s3.${region}.amazonaws.com`;
  const fileUrl = `${process.env.S3_PUBLIC_URL || uploadUrl}/${fileKey}`;
  
  // Mock presigned POST fields (in production, use AWS SDK)
  const fields = {
    key: fileKey,
    'Content-Type': 'image/jpeg',
    bucket: bucket,
    'X-Amz-Algorithm': 'AWS4-HMAC-SHA256',
    'X-Amz-Credential': `${process.env.S3_ACCESS_KEY_ID}/${new Date().toISOString().split('T')[0]}/us-east-1/s3/aws4_request`,
    'X-Amz-Date': new Date().toISOString().replace(/[:\-]|\.\d{3}/g, ''),
    'X-Amz-Expires': '300', // 5 minutes for screenshots
    'X-Amz-Security-Token': process.env.S3_SESSION_TOKEN || '',
    'X-Amz-Signature': generateSecureToken(32),
    Policy: Buffer.from(JSON.stringify({
      expiration: new Date(Date.now() + 300000).toISOString(), // 5 minutes
      conditions: [
        { bucket },
        { key: fileKey },
        { 'Content-Type': 'image/jpeg' },
        ['content-length-range', 1, 10 * 1024 * 1024], // 10MB max for screenshots
      ]
    })).toString('base64'),
  };

  return { uploadUrl, fileUrl, fields };
}

// POST /api/agent/screenshot/presign - Generate presigned URL for screenshot upload
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
          endpoint: '/api/agent/screenshot/presign',
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
    const validation = ScreenshotPresignSchema.safeParse(body);
    if (!validation.success) {
      return NextResponse.json(
        createApiError('Invalid screenshot presign data', 'VALIDATION_ERROR'),
        { status: 400 }
      );
    }

    const { timestamp, sessionId, screenshotType, quality, metadata } = validation.data;

    // Generate unique file key for screenshot
    const dateString = timestamp.toISOString().split('T')[0];
    const timeString = timestamp.toISOString().split('T')[1].replace(/[:.]/g, '-').split('-').slice(0, 3).join('-');
    const uniqueId = generateSecureToken(8);
    const fileKey = `screenshots/${userId}/${dateString}/${timeString}_${screenshotType}_${uniqueId}.jpg`;

    // Generate presigned URL
    const { uploadUrl, fileUrl, fields } = generateScreenshotPresignedUrl(fileKey);

    // Quality settings for client
    const qualitySettings = {
      low: { compression: 0.6, maxWidth: 1280, maxHeight: 720 },
      medium: { compression: 0.8, maxWidth: 1920, maxHeight: 1080 },
      high: { compression: 0.9, maxWidth: 2560, maxHeight: 1440 },
    };

    return NextResponse.json(
      createApiResponse({
        uploadUrl,
        fileUrl,
        fields,
        fileKey,
        expiresIn: 300, // 5 minutes
        qualitySettings: qualitySettings[quality],
        screenshotId: uniqueId,
        sessionId,
        metadata: {
          timestamp: timestamp.toISOString(),
          type: screenshotType,
          quality,
          deviceId,
          ...metadata,
        },
        guidelines: {
          maxFileSize: 10 * 1024 * 1024, // 10MB
          acceptedFormats: ['image/jpeg'],
          compressionRequired: true,
          blurSensitiveInfo: true,
        }
      }, 'Screenshot presigned URL generated successfully')
    );

  } catch (error) {
    console.error('Screenshot presign error:', error);
    return NextResponse.json(
      createApiError('Internal server error', 'INTERNAL_ERROR'),
      { status: 500 }
    );
  }
}
