import { NextRequest, NextResponse } from 'next/server';
import { getServerSession } from 'next-auth';
import { z } from 'zod';
import { prisma } from '@tts-pms/db';
import { createApiResponse, createApiError, UserRole } from '@tts-pms/infra';
import { authOptions, hasRole } from '@/lib/auth';
import { generateSecureToken } from '@tts-pms/infra';

const PresignRequestSchema = z.object({
  weekStart: z.string().transform((str: string) => new Date(str)),
  fileName: z.string().min(1, 'File name is required'),
  fileSize: z.number().positive('File size must be positive'),
  contentType: z.string().min(1, 'Content type is required'),
});

// Mock S3 presigned URL generation (replace with actual S3 implementation)
function generatePresignedUrl(fileKey: string, contentType: string): {
  uploadUrl: string;
  fileUrl: string;
  fields: Record<string, string>;
} {
  const bucket = process.env.S3_BUCKET_NAME || 'tts-pms-recordings';
  const region = process.env.S3_REGION || 'us-east-1';
  
  // In production, use AWS SDK to generate actual presigned URLs
  const uploadUrl = `https://${bucket}.s3.${region}.amazonaws.com`;
  const fileUrl = `${process.env.S3_PUBLIC_URL || uploadUrl}/${fileKey}`;
  
  // Mock presigned POST fields (in production, use AWS SDK)
  const fields = {
    key: fileKey,
    'Content-Type': contentType,
    bucket: bucket,
    'X-Amz-Algorithm': 'AWS4-HMAC-SHA256',
    'X-Amz-Credential': `${process.env.S3_ACCESS_KEY_ID}/${new Date().toISOString().split('T')[0]}/us-east-1/s3/aws4_request`,
    'X-Amz-Date': new Date().toISOString().replace(/[:\-]|\.\d{3}/g, ''),
    'X-Amz-Expires': '3600',
    'X-Amz-Security-Token': process.env.S3_SESSION_TOKEN || '',
    'X-Amz-Signature': generateSecureToken(32), // Mock signature
    Policy: Buffer.from(JSON.stringify({
      expiration: new Date(Date.now() + 3600000).toISOString(),
      conditions: [
        { bucket },
        { key: fileKey },
        { 'Content-Type': contentType },
        ['content-length-range', 1, 100 * 1024 * 1024], // 100MB max
      ]
    })).toString('base64'),
  };

  return { uploadUrl, fileUrl, fields };
}

// POST /api/recordings/presign - Generate presigned URL for recording upload
export async function POST(request: NextRequest) {
  try {
    const session = await getServerSession(authOptions);
    if (!session) {
      return NextResponse.json(
        createApiError('Unauthorized', 'UNAUTHORIZED'),
        { status: 401 }
      );
    }

    // Only employees and above can upload recordings
    if (!hasRole(session.user.role, UserRole.EMPLOYEE)) {
      return NextResponse.json(
        createApiError('Recording uploads not available for your role', 'INVALID_ROLE'),
        { status: 403 }
      );
    }

    const body = await request.json();
    const validation = PresignRequestSchema.safeParse(body);
    if (!validation.success) {
      return NextResponse.json(
        createApiError('Invalid presign request data', 'VALIDATION_ERROR'),
        { status: 400 }
      );
    }

    const { weekStart, fileName, fileSize, contentType } = validation.data;

    // Validate file type (only allow video/audio files)
    const allowedTypes = [
      'video/mp4', 'video/avi', 'video/mov', 'video/wmv',
      'audio/mp3', 'audio/wav', 'audio/m4a', 'audio/ogg'
    ];
    
    if (!allowedTypes.includes(contentType)) {
      return NextResponse.json(
        createApiError('Invalid file type. Only video and audio files are allowed', 'INVALID_FILE_TYPE'),
        { status: 400 }
      );
    }

    // Validate file size (max 100MB)
    const maxSize = 100 * 1024 * 1024; // 100MB
    if (fileSize > maxSize) {
      return NextResponse.json(
        createApiError('File size exceeds 100MB limit', 'FILE_TOO_LARGE'),
        { status: 400 }
      );
    }

    // Check if recording already exists for this week
    const existingRecording = await prisma.recording.findFirst({
      where: {
        userId: session.user.id,
        weekStart,
      }
    });

    if (existingRecording) {
      return NextResponse.json(
        createApiError('Recording already exists for this week', 'RECORDING_EXISTS'),
        { status: 409 }
      );
    }

    // Generate unique file key
    const weekString = weekStart.toISOString().split('T')[0];
    const fileExtension = fileName.split('.').pop();
    const uniqueId = generateSecureToken(16);
    const fileKey = `recordings/${session.user.id}/${weekString}/${uniqueId}.${fileExtension}`;

    // Generate presigned URL
    const { uploadUrl, fileUrl, fields } = generatePresignedUrl(fileKey, contentType);

    // Create recording record
    const recording = await prisma.recording.create({
      data: {
        userId: session.user.id,
        weekStart,
        fileKey,
      }
    });

    return NextResponse.json(
      createApiResponse({
        recordingId: recording.id,
        uploadUrl,
        fileUrl,
        fields,
        fileKey,
        expiresIn: 3600, // 1 hour
        maxFileSize: maxSize,
        allowedTypes,
      }, 'Presigned URL generated successfully')
    );

  } catch (error) {
    console.error('Recording presign error:', error);
    return NextResponse.json(
      createApiError('Internal server error', 'INTERNAL_ERROR'),
      { status: 500 }
    );
  }
}
