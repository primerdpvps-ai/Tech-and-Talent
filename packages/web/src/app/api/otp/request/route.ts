import { NextRequest, NextResponse } from 'next/server';
import { z } from 'zod';
import { createApiResponse, createApiError } from '@tts-pms/infra';
import { createOtp, sendEmailOtp, sendSmsOtp } from '@/lib/otp';

const OtpRequestSchema = z.object({
  type: z.enum(['email', 'sms']),
  email: z.string().email().optional(),
  phone: z.string().optional(),
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
    const validation = OtpRequestSchema.safeParse(body);
    if (!validation.success) {
      return NextResponse.json(
        createApiError('Invalid input data', 'VALIDATION_ERROR'),
        { status: 400 }
      );
    }

    const { type, email, phone, purpose } = validation.data;

    // Create OTP
    const otpResult = await createOtp(
      email || null,
      phone || null,
      type,
      purpose
    );

    if (!otpResult.success) {
      return NextResponse.json(
        createApiError(otpResult.error || 'Failed to create OTP', 'OTP_CREATION_FAILED'),
        { status: 400 }
      );
    }

    // Send OTP
    let sendResult = false;
    if (type === 'email' && email && otpResult.code) {
      sendResult = await sendEmailOtp(email, otpResult.code, purpose);
    } else if (type === 'sms' && phone && otpResult.code) {
      sendResult = await sendSmsOtp(phone, otpResult.code, purpose);
    }

    if (!sendResult) {
      return NextResponse.json(
        createApiError('Failed to send OTP', 'OTP_SEND_FAILED'),
        { status: 500 }
      );
    }

    return NextResponse.json(
      createApiResponse(
        { 
          type, 
          target: email || phone,
          expiresIn: 10 * 60 // 10 minutes in seconds
        }, 
        'OTP sent successfully'
      )
    );

  } catch (error) {
    console.error('OTP request error:', error);
    return NextResponse.json(
      createApiError('Internal server error', 'INTERNAL_ERROR'),
      { status: 500 }
    );
  }
}
