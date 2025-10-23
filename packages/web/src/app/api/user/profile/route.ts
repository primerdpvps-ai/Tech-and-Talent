import { NextRequest, NextResponse } from 'next/server';
import { getServerSession } from 'next-auth';
import { z } from 'zod';
import { prisma } from '@tts-pms/db';
import { createApiResponse, createApiError } from '@tts-pms/infra';
import { authOptions } from '@/lib/auth';
import { verifyOtp } from '@/lib/otp';

const UpdateProfileSchema = z.object({
  fullName: z.string().min(1).optional(),
  dob: z.string().transform(str => new Date(str)).optional(),
  phone: z.string().optional(),
  city: z.string().optional(),
  province: z.string().optional(),
  country: z.string().optional(),
  address: z.string().optional(),
});

// GET /api/user/profile - Get user profile
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

// PUT /api/user/profile - Update user profile
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

    const { fullName, dob, phone, city, province, country, address } = validation.data;

    // Get current user to check core lock status
    const currentUser = await prisma.user.findUnique({
      where: { id: session.user.id },
      select: {
        coreLocked: true,
        fullName: true,
        dob: true,
      }
    });

    if (!currentUser) {
      return NextResponse.json(
        createApiError('User not found', 'USER_NOT_FOUND'),
        { status: 404 }
      );
    }

    // Check if trying to update core fields when locked
    const coreFields = { fullName, dob };
    const hasChangedCoreFields = Object.entries(coreFields).some(([key, value]) => {
      if (value === undefined) return false;
      const currentValue = currentUser[key as keyof typeof currentUser];
      return value !== currentValue;
    });

    if (currentUser.coreLocked && hasChangedCoreFields) {
      return NextResponse.json(
        createApiError(
          'Core fields (name, date of birth) are locked. Please submit an admin approval request to change these fields.',
          'CORE_FIELDS_LOCKED'
        ),
        { status: 403 }
      );
    }

    // Prepare update data (exclude undefined values)
    const updateData: any = {};
    if (phone !== undefined) updateData.phone = phone;
    if (city !== undefined) updateData.city = city;
    if (province !== undefined) updateData.province = province;
    if (country !== undefined) updateData.country = country;
    if (address !== undefined) updateData.address = address;

    // Only update core fields if not locked
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
