import { NextRequest, NextResponse } from 'next/server';
import { getServerSession } from 'next-auth';
import { z } from 'zod';
import { prisma } from '@tts-pms/db';
import { createApiResponse, createApiError, UserRole } from '@tts-pms/infra';
import { authOptions, hasRole } from '@/lib/auth';

const CreateApprovalRequestSchema = z.object({
  requestType: z.enum(['name_change', 'dob_change']),
  requestedData: z.record(z.any()),
  reason: z.string().optional(),
});

const UpdateApprovalRequestSchema = z.object({
  status: z.enum(['APPROVED', 'REJECTED']),
  reviewNotes: z.string().optional(),
});

// GET /api/admin/approval-requests - List approval requests
export async function GET(request: NextRequest) {
  try {
    const session = await getServerSession(authOptions);
    if (!session) {
      return NextResponse.json(
        createApiError('Unauthorized', 'UNAUTHORIZED'),
        { status: 401 }
      );
    }

    const { searchParams } = new URL(request.url);
    const status = searchParams.get('status');
    const userId = searchParams.get('userId');

    // Regular users can only see their own requests
    // Managers and above can see all requests
    const canViewAll = hasRole(session.user.role, UserRole.MANAGER);
    
    const where: any = {};
    if (status) where.status = status;
    if (!canViewAll) {
      where.userId = session.user.id;
    } else if (userId) {
      where.userId = userId;
    }

    const requests = await prisma.adminApprovalRequest.findMany({
      where,
      include: {
        user: {
          select: {
            id: true,
            email: true,
            fullName: true,
            role: true,
          }
        },
        reviewer: {
          select: {
            id: true,
            email: true,
            fullName: true,
          }
        }
      },
      orderBy: { createdAt: 'desc' },
    });

    return NextResponse.json(createApiResponse(requests));

  } catch (error) {
    console.error('Get approval requests error:', error);
    return NextResponse.json(
      createApiError('Internal server error', 'INTERNAL_ERROR'),
      { status: 500 }
    );
  }
}

// POST /api/admin/approval-requests - Create approval request
export async function POST(request: NextRequest) {
  try {
    const session = await getServerSession(authOptions);
    if (!session) {
      return NextResponse.json(
        createApiError('Unauthorized', 'UNAUTHORIZED'),
        { status: 401 }
      );
    }

    const body = await request.json();
    const validation = CreateApprovalRequestSchema.safeParse(body);
    if (!validation.success) {
      return NextResponse.json(
        createApiError('Invalid input data', 'VALIDATION_ERROR'),
        { status: 400 }
      );
    }

    const { requestType, requestedData, reason } = validation.data;

    // Get current user data for comparison
    const currentUser = await prisma.user.findUnique({
      where: { id: session.user.id },
      select: {
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

    // Check if user has core lock (cannot request changes to core fields)
    if (session.user.coreLocked) {
      return NextResponse.json(
        createApiError('Core fields are locked and cannot be changed', 'CORE_LOCKED'),
        { status: 403 }
      );
    }

    // Check for existing pending request of the same type
    const existingRequest = await prisma.adminApprovalRequest.findFirst({
      where: {
        userId: session.user.id,
        requestType,
        status: 'PENDING',
      }
    });

    if (existingRequest) {
      return NextResponse.json(
        createApiError('You already have a pending request of this type', 'PENDING_REQUEST_EXISTS'),
        { status: 409 }
      );
    }

    // Create approval request
    const approvalRequest = await prisma.adminApprovalRequest.create({
      data: {
        userId: session.user.id,
        requestType,
        currentData: {
          fullName: currentUser.fullName,
          dob: currentUser.dob,
        },
        requestedData,
        reason,
      },
      include: {
        user: {
          select: {
            id: true,
            email: true,
            fullName: true,
          }
        }
      }
    });

    return NextResponse.json(
      createApiResponse(approvalRequest, 'Approval request created successfully'),
      { status: 201 }
    );

  } catch (error) {
    console.error('Create approval request error:', error);
    return NextResponse.json(
      createApiError('Internal server error', 'INTERNAL_ERROR'),
      { status: 500 }
    );
  }
}
