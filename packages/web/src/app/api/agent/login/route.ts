import { NextRequest, NextResponse } from 'next/server';
import { z } from 'zod';
import bcrypt from 'bcryptjs';
import { randomBytes } from 'crypto';
import { prisma } from '@tts-pms/db';
import { createApiResponse, createApiError, UserRole } from '@tts-pms/infra';
import { generateAgentToken } from '@/lib/agent-auth';

const AgentLoginSchema = z.object({
  email: z.string().email('Valid email is required'),
  password: z.string().min(1, 'Password is required'),
  deviceId: z.string().min(1, 'Device ID is required'),
  deviceInfo: z.object({
    platform: z.string().optional(),
    version: z.string().optional(),
    userAgent: z.string().optional(),
    screenResolution: z.string().optional(),
    timezone: z.string().optional(),
  }).optional(),
});

// POST /api/agent/login - Agent device login (JWT for device)
export async function POST(request: NextRequest) {
  try {
    const body = await request.json();
    const validation = AgentLoginSchema.safeParse(body);
    if (!validation.success) {
      return NextResponse.json(
        createApiError('Invalid login data', 'VALIDATION_ERROR'),
        { status: 400 }
      );
    }

    const { email, password, deviceId, deviceInfo } = validation.data;

    // Find user
    const user = await prisma.user.findUnique({
      where: { email },
      include: {
        employment: true,
      }
    });

    if (!user) {
      return NextResponse.json(
        createApiError('Invalid credentials', 'INVALID_CREDENTIALS'),
        { status: 401 }
      );
    }

    // Verify password
    const isPasswordValid = await bcrypt.compare(password, user.passwordHash);
    if (!isPasswordValid) {
      return NextResponse.json(
        createApiError('Invalid credentials', 'INVALID_CREDENTIALS'),
        { status: 401 }
      );
    }

    // Check if user is eligible for agent access
    const eligibleRoles = [UserRole.EMPLOYEE, UserRole.NEW_EMPLOYEE, UserRole.MANAGER];
    if (!eligibleRoles.includes(user.role)) {
      return NextResponse.json(
        createApiError('Agent access not available for your role', 'INVALID_ROLE'),
        { status: 403 }
      );
    }

    // Check employment record
    if (!user.employment) {
      return NextResponse.json(
        createApiError('No employment record found', 'NO_EMPLOYMENT'),
        { status: 403 }
      );
    }

    // Register or update device
    let deviceSecret: string;
    try {
      const existingDevice = await prisma.agentDevice.findUnique({
        where: { deviceId }
      });

      if (existingDevice) {
        // Update existing device
        await prisma.agentDevice.update({
          where: { deviceId },
          data: {
            userId: user.id,
            lastLoginAt: new Date(),
            deviceInfo: deviceInfo || {},
            userAgent: request.headers.get('user-agent') || '',
            ip: request.headers.get('x-forwarded-for') || 
                request.headers.get('x-real-ip') || 
                'unknown'
          }
        });
        deviceSecret = existingDevice.deviceSecret;
      } else {
        // Create new device with generated secret
        deviceSecret = randomBytes(32).toString('hex');
        await prisma.agentDevice.create({
          data: {
            deviceId,
            userId: user.id,
            deviceSecret,
            deviceInfo: deviceInfo || {},
            userAgent: request.headers.get('user-agent') || '',
            ip: request.headers.get('x-forwarded-for') || 
                request.headers.get('x-real-ip') || 
                'unknown',
            registeredAt: new Date(),
            lastLoginAt: new Date(),
            isActive: true
          }
        });
      }
    } catch (error) {
      console.error('Failed to register/update device:', error);
      return NextResponse.json(
        createApiError('Device registration failed', 'DEVICE_ERROR'),
        { status: 500 }
      );
    }

    // Generate JWT token for device
    const token = generateAgentToken(user.id, deviceId, user.role);

    return NextResponse.json(
      createApiResponse({
        token,
        deviceSecret,
        user: {
          id: user.id,
          email: user.email,
          fullName: user.fullName,
          role: user.role,
        },
        employment: {
          startDate: user.employment.startDate,
          rdpHost: user.employment.rdpHost,
          rdpUsername: user.employment.rdpUsername,
        },
        deviceId,
        expiresIn: 24 * 60 * 60, // 24 hours
        serverTime: new Date().toISOString(),
        permissions: {
          canTrackTime: true,
          canUploadScreenshots: true,
          canSubmitActivity: true,
          canAccessRDP: !!(user.employment.rdpHost && user.employment.rdpUsername),
        }
      }, 'Agent login successful')
    );

  } catch (error) {
    console.error('Agent login error:', error);
    return NextResponse.json(
      createApiError('Internal server error', 'INTERNAL_ERROR'),
      { status: 500 }
    );
  }
}
