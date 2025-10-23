import { NextRequest, NextResponse } from 'next/server';
import { getServerSession } from 'next-auth';
import { z } from 'zod';
import { prisma } from '@tts-pms/db';
import { createApiResponse, createApiError } from '@tts-pms/infra';
import { authOptions } from '@/lib/auth';
import { verifyOtp } from '@/lib/otp';

const UpdateProfileSchema = z.object({
  fullName: z.string().min(1).optional(),
  dob: z.string().transform((str: string) => new Date(str)).optional(),
  phone: z.string().optional(),
  email: z.string().email().optional(),
  city: z.string().optional(),
  province: z.string().optional(),
  country: z.string().optional(),
  address: z.string().optional(),
  // OTP verification for email/phone changes
  oldEmailOtp: z.string().optional(),
  newEmailOtp: z.string().optional(),
  oldPhoneOtp: z.string().optional(),
  newPhoneOtp: z.string().optional(),
});

// GET /api/profile - Get user profile
export async function GET(request: NextRequest) {
  try {
    const session = await getServerSession(authOptions);
    if (!session) {
      return NextResponse.json(
        createApiError('Unauthorized', 'UNAUTHORIZED'),
        { status: 401 }
      );
    }

    const user = await prisma.user.findUnique({
      where: { id: session.user.id },
      select: {
        id: true,
        email: true,
        emailVerified: true,
        phone: true,
        phoneVerified: true,
        fullName: true,
        dob: true,
        city: true,
        province: true,
        country: true,
        address: true,
        coreLocked: true,
        role: true,
        createdAt: true,
        updatedAt: true,
      }
    });

    if (!user) {
      return NextResponse.json(
        createApiError('User not found', 'USER_NOT_FOUND'),
        { status: 404 }
      );
    }

    return NextResponse.json(createApiResponse(user));

  } catch (error) {
    console.error('Get profile error:', error);
    return NextResponse.json(
      createApiError('Internal server error', 'INTERNAL_ERROR'),
      { status: 500 }
    );
  }
}

// PUT /api/profile - Update user profile with dual OTP confirmation
export async function PUT(request: NextRequest) {
  try {
    const session = await getServerSession(authOptions);
    if (!session) {
      return NextResponse.json(
        createApiError('Unauthorized', 'UNAUTHORIZED'),
        { status: 401 }
      );
    }

    const body = await request.json();
    const validation = UpdateProfileSchema.safeParse(body);
    if (!validation.success) {
      return NextResponse.json(
        createApiError('Invalid input data', 'VALIDATION_ERROR'),
        { status: 400 }
      );
    }

    const { 
      fullName, dob, phone, email, city, province, country, address,
      oldEmailOtp, newEmailOtp, oldPhoneOtp, newPhoneOtp
    } = validation.data;

    // Get current user
    const currentUser = await prisma.user.findUnique({
      where: { id: session.user.id },
      select: {
        coreLocked: true,
        fullName: true,
        dob: true,
        email: true,
        phone: true,
      }
    });

    if (!currentUser) {
      return NextResponse.json(
        createApiError('User not found', 'USER_NOT_FOUND'),
        { status: 404 }
      );
    }

    // Check core field restrictions
    const coreFields = { fullName, dob };
    const hasChangedCoreFields = Object.entries(coreFields).some(([key, value]) => {
      if (value === undefined) return false;
      const currentValue = currentUser[key as keyof typeof currentUser];
      return value !== currentValue;
    });

    if (currentUser.coreLocked && hasChangedCoreFields) {
      return NextResponse.json(
        createApiError(
          'Core fields are locked. Submit an admin approval request.',
          'CORE_FIELDS_LOCKED'
        ),
        { status: 403 }
      );
    }

    // Handle email change with dual OTP
    if (email && email !== currentUser.email) {
      if (!oldEmailOtp || !newEmailOtp) {
        return NextResponse.json(
          createApiError('Email change requires OTP verification for both old and new email', 'OTP_REQUIRED'),
          { status: 400 }
        );
      }

      // Verify old email OTP
      const oldEmailVerify = await verifyOtp(
        currentUser.email, null, oldEmailOtp, 'email', 'email_change_old'
      );
      if (!oldEmailVerify.success) {
        return NextResponse.json(
          createApiError('Invalid OTP for current email', 'INVALID_OLD_EMAIL_OTP'),
          { status: 400 }
        );
      }

      // Verify new email OTP
      const newEmailVerify = await verifyOtp(
        email, null, newEmailOtp, 'email', 'email_change_new'
      );
      if (!newEmailVerify.success) {
        return NextResponse.json(
          createApiError('Invalid OTP for new email', 'INVALID_NEW_EMAIL_OTP'),
          { status: 400 }
        );
      }

      // Check if new email is already taken
      const existingUser = await prisma.user.findUnique({
        where: { email },
        select: { id: true }
      });
      if (existingUser && existingUser.id !== session.user.id) {
        return NextResponse.json(
          createApiError('Email already in use', 'EMAIL_TAKEN'),
          { status: 409 }
        );
      }
    }

    // Handle phone change with dual OTP
    if (phone && phone !== currentUser.phone) {
      if (!oldPhoneOtp || !newPhoneOtp) {
        return NextResponse.json(
          createApiError('Phone change requires OTP verification for both old and new phone', 'OTP_REQUIRED'),
          { status: 400 }
        );
      }

      // Verify old phone OTP (if user has a phone)
      if (currentUser.phone) {
        const oldPhoneVerify = await verifyOtp(
          null, currentUser.phone, oldPhoneOtp, 'sms', 'phone_change_old'
        );
        if (!oldPhoneVerify.success) {
          return NextResponse.json(
            createApiError('Invalid OTP for current phone', 'INVALID_OLD_PHONE_OTP'),
            { status: 400 }
          );
        }
      }

      // Verify new phone OTP
      const newPhoneVerify = await verifyOtp(
        null, phone, newPhoneOtp, 'sms', 'phone_change_new'
      );
      if (!newPhoneVerify.success) {
        return NextResponse.json(
          createApiError('Invalid OTP for new phone', 'INVALID_NEW_PHONE_OTP'),
          { status: 400 }
        );
      }
    }

    // Prepare update data
    const updateData: any = {};
    if (city !== undefined) updateData.city = city;
    if (province !== undefined) updateData.province = province;
    if (country !== undefined) updateData.country = country;
    if (address !== undefined) updateData.address = address;

    // Update email if verified
    if (email && email !== currentUser.email) {
      updateData.email = email;
      updateData.emailVerified = new Date();
    }

    // Update phone if verified
    if (phone && phone !== currentUser.phone) {
      updateData.phone = phone;
      updateData.phoneVerified = new Date();
    }

    // Update core fields if not locked
    if (!currentUser.coreLocked) {
      if (fullName !== undefined) updateData.fullName = fullName;
      if (dob !== undefined) updateData.dob = dob;
    }

    // Update user profile
    const updatedUser = await prisma.user.update({
      where: { id: session.user.id },
      data: updateData,
      select: {
        id: true,
        email: true,
        emailVerified: true,
        phone: true,
        phoneVerified: true,
        fullName: true,
        dob: true,
        city: true,
        province: true,
        country: true,
        address: true,
        coreLocked: true,
        role: true,
        updatedAt: true,
      }
    });

    return NextResponse.json(
      createApiResponse(updatedUser, 'Profile updated successfully')
    );

  } catch (error) {
    console.error('Update profile error:', error);
    return NextResponse.json(
      createApiError('Internal server error', 'INTERNAL_ERROR'),
      { status: 500 }
    );
  }
}
