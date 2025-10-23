import { NextRequest, NextResponse } from 'next/server';
import { getServerSession } from 'next-auth';
import { z } from 'zod';
import { prisma } from '@tts-pms/db';
import { createApiResponse, createApiError, LeaveType, LeaveStatus, UserRole } from '@tts-pms/infra';
import { authOptions, hasRole } from '@/lib/auth';

const CreateLeaveSchema = z.object({
  type: z.nativeEnum(LeaveType),
  dateFrom: z.string().transform((str: string) => new Date(str)),
  dateTo: z.string().transform((str: string) => new Date(str)),
  reason: z.string().min(1, 'Reason is required'),
});

// POST /api/leaves - Submit leave request (employee)
export async function POST(request: NextRequest) {
  try {
    const session = await getServerSession(authOptions);
    if (!session) {
      return NextResponse.json(
        createApiError('Unauthorized', 'UNAUTHORIZED'),
        { status: 401 }
      );
    }

    // Only employees and above can request leave
    if (!hasRole(session.user.role, UserRole.EMPLOYEE)) {
      return NextResponse.json(
        createApiError('Leave requests not available for your role', 'INVALID_ROLE'),
        { status: 403 }
      );
    }

    const body = await request.json();
    const validation = CreateLeaveSchema.safeParse(body);
    if (!validation.success) {
      return NextResponse.json(
        createApiError('Invalid leave request data', 'VALIDATION_ERROR'),
        { status: 400 }
      );
    }

    const { type, dateFrom, dateTo, reason } = validation.data;

    // Validate date range
    if (dateFrom >= dateTo) {
      return NextResponse.json(
        createApiError('End date must be after start date', 'INVALID_DATE_RANGE'),
        { status: 400 }
      );
    }

    // Calculate notice hours
    const now = new Date();
    const noticeHours = Math.floor((dateFrom.getTime() - now.getTime()) / (1000 * 60 * 60));

    // Validate notice period based on leave type
    const minNoticeHours = {
      [LeaveType.SHORT]: 2,     // 2 hours for short leave
      [LeaveType.ONE_DAY]: 24,  // 24 hours for one day
      [LeaveType.LONG]: 168,    // 7 days for long leave
    };

    if (noticeHours < minNoticeHours[type]) {
      return NextResponse.json(
        createApiError(
          `Insufficient notice. ${type} leave requires at least ${minNoticeHours[type]} hours notice`,
          'INSUFFICIENT_NOTICE'
        ),
        { status: 400 }
      );
    }

    // Check for overlapping leave requests
    const overlappingLeave = await prisma.leave.findFirst({
      where: {
        userId: session.user.id,
        status: { in: [LeaveStatus.PENDING, LeaveStatus.APPROVED] },
        OR: [
          {
            dateFrom: { lte: dateTo },
            dateTo: { gte: dateFrom },
          }
        ]
      }
    });

    if (overlappingLeave) {
      return NextResponse.json(
        createApiError('You have overlapping leave requests', 'OVERLAPPING_LEAVE'),
        { status: 409 }
      );
    }

    // Calculate penalties for short notice
    const penalties = [];
    if (type === LeaveType.LONG && noticeHours < 336) { // Less than 14 days
      penalties.push({
        type: 'short_notice',
        amount: 50,
        description: 'Short notice penalty for long leave'
      });
    }

    // Create leave request
    const leave = await prisma.leave.create({
      data: {
        userId: session.user.id,
        type,
        dateFrom,
        dateTo,
        noticeHours,
        penalties: penalties.length > 0 ? penalties : null,
      },
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

    return NextResponse.json(
      createApiResponse(leave, 'Leave request submitted successfully'),
      { status: 201 }
    );

  } catch (error) {
    console.error('Leave request error:', error);
    return NextResponse.json(
      createApiError('Internal server error', 'INTERNAL_ERROR'),
      { status: 500 }
    );
  }
}

// GET /api/leaves - Get leave requests
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
    const status = searchParams.get('status') as LeaveStatus | null;
    const userId = searchParams.get('userId');

    // Managers can see all leaves, employees see only their own
    const canViewAll = hasRole(session.user.role, UserRole.MANAGER);
    
    const where: any = {};
    if (!canViewAll) {
      where.userId = session.user.id;
    } else if (userId) {
      where.userId = userId;
    }
    if (status) {
      where.status = status;
    }

    const leaves = await prisma.leave.findMany({
      where,
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
      },
      orderBy: { requestedAt: 'desc' },
    });

    return NextResponse.json(createApiResponse(leaves));

  } catch (error) {
    console.error('Get leaves error:', error);
    return NextResponse.json(
      createApiError('Internal server error', 'INTERNAL_ERROR'),
      { status: 500 }
    );
  }
}
