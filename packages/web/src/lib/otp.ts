import { prisma } from '@tts-pms/db';
import crypto from 'crypto';

export interface OtpConfig {
  length: number;
  expiryMinutes: number;
  maxAttempts: number;
  rateLimitMinutes: number;
  maxRequestsPerPeriod: number;
}

const DEFAULT_CONFIG: OtpConfig = {
  length: 6,
  expiryMinutes: 10,
  maxAttempts: 3,
  rateLimitMinutes: 5,
  maxRequestsPerPeriod: 3,
};

export function generateOtpCode(length: number = 6): string {
  const digits = '0123456789';
  let code = '';
  for (let i = 0; i < length; i++) {
    code += digits[crypto.randomInt(0, digits.length)];
  }
  return code;
}

export async function createOtp(
  email: string | null,
  phone: string | null,
  type: 'email' | 'sms',
  purpose: string,
  config: Partial<OtpConfig> = {}
): Promise<{ success: boolean; code?: string; error?: string }> {
  const finalConfig = { ...DEFAULT_CONFIG, ...config };
  
  if (!email && !phone) {
    return { success: false, error: 'Either email or phone must be provided' };
  }

  // Check rate limiting
  const rateLimitStart = new Date(Date.now() - finalConfig.rateLimitMinutes * 60 * 1000);
  const recentOtps = await prisma.otp.count({
    where: {
      ...(email ? { email } : { phone }),
      type,
      purpose,
      createdAt: {
        gte: rateLimitStart,
      },
    },
  });

  if (recentOtps >= finalConfig.maxRequestsPerPeriod) {
    return { 
      success: false, 
      error: `Too many OTP requests. Please wait ${finalConfig.rateLimitMinutes} minutes.` 
    };
  }

  // Invalidate existing OTPs
  await prisma.otp.updateMany({
    where: {
      ...(email ? { email } : { phone }),
      type,
      purpose,
      verified: false,
    },
    data: {
      verified: true, // Mark as used to invalidate
    },
  });

  // Generate new OTP
  const code = generateOtpCode(finalConfig.length);
  const expiresAt = new Date(Date.now() + finalConfig.expiryMinutes * 60 * 1000);

  await prisma.otp.create({
    data: {
      email,
      phone,
      code,
      type,
      purpose,
      expiresAt,
    },
  });

  return { success: true, code };
}

export async function verifyOtp(
  email: string | null,
  phone: string | null,
  code: string,
  type: 'email' | 'sms',
  purpose: string,
  config: Partial<OtpConfig> = {}
): Promise<{ success: boolean; error?: string }> {
  const finalConfig = { ...DEFAULT_CONFIG, ...config };

  if (!email && !phone) {
    return { success: false, error: 'Either email or phone must be provided' };
  }

  const otp = await prisma.otp.findFirst({
    where: {
      ...(email ? { email } : { phone }),
      type,
      purpose,
      verified: false,
      expiresAt: {
        gt: new Date(),
      },
    },
    orderBy: {
      createdAt: 'desc',
    },
  });

  if (!otp) {
    return { success: false, error: 'Invalid or expired OTP' };
  }

  // Check attempts
  if (otp.attempts >= finalConfig.maxAttempts) {
    return { success: false, error: 'Maximum verification attempts exceeded' };
  }

  // Increment attempts
  await prisma.otp.update({
    where: { id: otp.id },
    data: { attempts: otp.attempts + 1 },
  });

  if (otp.code !== code) {
    return { success: false, error: 'Invalid OTP code' };
  }

  // Mark as verified
  await prisma.otp.update({
    where: { id: otp.id },
    data: { verified: true },
  });

  return { success: true };
}

export async function sendEmailOtp(email: string, code: string, purpose: string): Promise<boolean> {
  // TODO: Implement actual email sending
  // For now, just log the code (in production, use a proper email service)
  console.log(`📧 Email OTP for ${email} (${purpose}): ${code}`);
  
  // Simulate email sending delay
  await new Promise(resolve => setTimeout(resolve, 100));
  
  return true;
}

export async function sendSmsOtp(phone: string, code: string, purpose: string): Promise<boolean> {
  // TODO: Implement actual SMS sending
  // For now, just log the code (in production, use a proper SMS service)
  console.log(`📱 SMS OTP for ${phone} (${purpose}): ${code}`);
  
  // Simulate SMS sending delay
  await new Promise(resolve => setTimeout(resolve, 100));
  
  return true;
}
