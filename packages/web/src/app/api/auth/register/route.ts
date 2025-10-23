import { NextRequest, NextResponse } from 'next/server';
import bcrypt from 'bcryptjs';
import { prisma } from '@tts-pms/db';
import { RegisterSchema, UserRole, createApiResponse, createApiError } from '@tts-pms/infra';

const ALLOWED_EMAIL_DOMAINS = process.env.ALLOWED_EMAIL_DOMAINS?.split(',') || ['gmail.com'];

export async function POST(request: NextRequest) {
  try {
    const body = await request.json();
    
    // Validate input
    const validation = RegisterSchema.safeParse(body);
    if (!validation.success) {
      return NextResponse.json(
        createApiError('Invalid input data', 'VALIDATION_ERROR'),
        { status: 400 }
      );
    }

    const { email, password, fullName, phone, city, province, country, address } = validation.data;

    // Check email domain restriction
    const emailDomain = email.split('@')[1];
    if (!ALLOWED_EMAIL_DOMAINS.includes(emailDomain)) {
      return NextResponse.json(
        createApiError(`Only emails from ${ALLOWED_EMAIL_DOMAINS.join(', ')} are allowed`, 'DOMAIN_NOT_ALLOWED'),
        { status: 400 }
      );
    }

    // Check if user already exists
    const existingUser = await prisma.user.findUnique({
      where: { email }
    });

    if (existingUser) {
      return NextResponse.json(
        createApiError('User with this email already exists', 'USER_EXISTS'),
        { status: 409 }
      );
    }

    // Hash password
    const passwordHash = await bcrypt.hash(password, 12);

    // Create user
    const user = await prisma.user.create({
      data: {
        email,
        passwordHash,
        fullName,
        phone,
        city,
        province,
        country,
        address,
        role: UserRole.VISITOR,
        coreLocked: false,
      },
      select: {
        id: true,
        email: true,
        fullName: true,
        role: true,
        createdAt: true,
      }
    });

    return NextResponse.json(
      createApiResponse(user, 'User created successfully'),
      { status: 201 }
    );

  } catch (error) {
    console.error('Registration error:', error);
    return NextResponse.json(
      createApiError('Internal server error', 'INTERNAL_ERROR'),
      { status: 500 }
    );
  }
}
