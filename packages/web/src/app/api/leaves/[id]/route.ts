import { NextRequest, NextResponse } from 'next/server';
import { getServerSession } from 'next-auth';
import { z } from 'zod';
import { prisma } from '@tts-pms/db';
import { createApiResponse, createApiError, LeaveStatus, UserRole } from '@tts-pms/infra';
import { authOptions, hasRole } from '@/lib/auth';

const LeaveDecisionSchema = z.object({
  decision: z.enum(['approve', 'reject']),
  notes: z.string().optional(),
});

// PATCH /api/leaves/[id] - Approve or reject leave request (manager)
export async function PATCH(
  request: NextRequest,
  { params }: { params: { id: string } }
) {
  try {
    const session = await getServerSession(authOptions);
    if (!session) {
      return NextResponse.json(
        createApiError('Unauthorized', 'UNAUTHORIZED'),
        { status: 401 }
      );
    }

    // Only managers and above can approve/reject leaves
    if (!hasRole(session.user.role, UserRole.MANAGER)) {
      return NextResponse.json(
        createApiError('Insufficient permissions', 'INSUFFICIENT_PERMISSIONS'),
        { status: 403 }
      );
    }

    const body = await request.json();
    const validation = LeaveDecisionSchema.safeParse(body);
    if (!validation.success) {
      return NextResponse.json(
        createApiError('Invalid decision data', 'VALIDATION_ERROR'),
        { status: 400 }
      );
    }

    const { decision, notes } = validation.data;

    // Find the leave request
    const leave = await prisma.leave.findUnique({
      where: { id: params.id },
      include: {
        user: {
          select: {
            id: true,
            fullName: true,
            email: true,
          }
        }
      }
    });

    if (!leave) {
      return NextResponse.json(
        createApiError('Leave request not found', 'LEAVE_NOT_FOUND'),
        { status: 404 }
      );
    }

    // Check if leave is still pending
    if (leave.status !== LeaveStatus.PENDING) {
      return NextResponse.json(
        createApiError('Leave request has already been decided', 'ALREADY_DECIDED'),
        { status: 409 }
      );
    }

    const newStatus = decision === 'approve' ? LeaveStatus.APPROVED : LeaveStatus.REJECTED;

    // Update leave with decision
    const updatedLeave = await prisma.leave.update({
      where: { id: params.id },
      data: {
        status: newStatus,
        decidedAt: new Date(),
        decidedByUserId: session.user.id,
      },
      include: {
        user: {
          select: {
            id: true,
            fullName: true,
            email: true,
          }
        },
        decidedBy: {
          select: {
            id: true,
            fullName: true,
          }
        }
      }
    });

    return NextResponse.json(
      createApiResponse(
        updatedLeave,
        `Leave request ${decision === 'approve' ? 'approved' : 'rejected'} successfully`
      )
    );

  } catch (error) {
    console.error('Leave decision error:', error);
    return NextResponse.json(
      createApiError('Internal server error', 'INTERNAL_ERROR'),
      { status: 500 }
    );
  }
}
