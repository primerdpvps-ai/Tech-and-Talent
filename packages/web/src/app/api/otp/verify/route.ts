import { NextRequest, NextResponse } from 'next/server';
import { z } from 'zod';
import { prisma } from '@tts-pms/db';
import { createApiResponse, createApiError } from '@tts-pms/infra';
import { verifyOtp } from '@/lib/otp';

const OtpVerifySchema = z.object({
  type: z.enum(['email', 'sms']),
  email: z.string().email().optional(),
  phone: z.string().optional(),
  code: z.string().min(4).max(8),
  purpose: z.enum(['registration', 'login', 'phone_verification', 'email_verification', 'password_reset']),
}).refine(
  (data) => (data.type === 'email' && data.email) || (data.type === 'sms' && data.phone),
  {
    message: "Email is required for email OTP, phone is required for SMS OTP",
  }
);

export async function POST(request: NextRequest) {
  try {
    const body = await request.json();
    
    // Validate input
    const validation = OtpVerifySchema.safeParse(body);
    if (!validation.success) {
      return NextResponse.json(
        createApiError('Invalid input data', 'VALIDATION_ERROR'),
        { status: 400 }
      );
    }

    const { type, email, phone, code, purpose } = validation.data;

    // Verify OTP
    const verifyResult = await verifyOtp(
      email || null,
      phone || null,
      code,
      type,
      purpose
    );

    if (!verifyResult.success) {
      return NextResponse.json(
        createApiError(verifyResult.error || 'OTP verification failed', 'OTP_VERIFICATION_FAILED'),
        { status: 400 }
      );
    }

    // Handle post-verification actions based on purpose
    let responseData: any = { verified: true };

    if (purpose === 'email_verification' && email) {
      // Update user's email verification status
      await prisma.user.updateMany({
        where: { email },
        data: { emailVerified: new Date() },
      });
      responseData.emailVerified = true;
    }

    if (purpose === 'phone_verification' && phone) {
      // Update user's phone verification status
      await prisma.user.updateMany({
        where: { phone },
        data: { phoneVerified: new Date() },
      });
      responseData.phoneVerified = true;
    }

    return NextResponse.json(
      createApiResponse(responseData, 'OTP verified successfully')
    );

  } catch (error) {
    console.error('OTP verification error:', error);
    return NextResponse.json(
      createApiError('Internal server error', 'INTERNAL_ERROR'),
      { status: 500 }
    );
  }
}
