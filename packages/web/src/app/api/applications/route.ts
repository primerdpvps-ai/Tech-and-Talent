import { NextRequest, NextResponse } from 'next/server';
import { getServerSession } from 'next-auth';
import { z } from 'zod';
import { prisma } from '@tts-pms/db';
import { createApiResponse, createApiError, JobType, ApplicationStatus, UserRole, EvaluationResult } from '@tts-pms/infra';
import { authOptions, hasRole } from '@/lib/auth';

const CreateApplicationSchema = z.object({
  jobType: z.nativeEnum(JobType),
  files: z.record(z.string()).optional(),
});

// POST /api/applications - Submit job application
export async function POST(request: NextRequest) {
  try {
    const session = await getServerSession(authOptions);
    if (!session) {
      return NextResponse.json(
        createApiError('Unauthorized', 'UNAUTHORIZED'),
        { status: 401 }
      );
    }

    // Only candidates can submit applications
    if (session.user.role !== UserRole.CANDIDATE) {
      return NextResponse.json(
        createApiError('Only candidates can submit applications', 'INVALID_ROLE'),
        { status: 403 }
      );
    }

    const body = await request.json();
    const validation = CreateApplicationSchema.safeParse(body);
    if (!validation.success) {
      return NextResponse.json(
        createApiError('Invalid application data', 'VALIDATION_ERROR'),
        { status: 400 }
      );
    }

    // Check if user has eligible evaluation
    const eligibleEvaluation = await prisma.evaluation.findFirst({
      where: {
        userId: session.user.id,
        result: EvaluationResult.ELIGIBLE,
      },
      orderBy: { createdAt: 'desc' }
    });

    if (!eligibleEvaluation) {
      return NextResponse.json(
        createApiError('You must pass the evaluation before applying', 'NO_ELIGIBLE_EVALUATION'),
        { status: 403 }
      );
    }

    // Check for existing pending application
    const existingApplication = await prisma.application.findFirst({
      where: {
        userId: session.user.id,
        status: ApplicationStatus.UNDER_REVIEW,
      }
    });

    if (existingApplication) {
      return NextResponse.json(
        createApiError('You already have a pending application', 'PENDING_APPLICATION_EXISTS'),
        { status: 409 }
      );
    }

    // Create application
    const application = await prisma.application.create({
      data: {
        userId: session.user.id,
        jobType: validation.data.jobType,
        files: validation.data.files,
      },
      include: {
        user: {
          select: {
            id: true,
            email: true,
            fullName: true,
            role: true,
          }
        }
      }
    });

    return NextResponse.json(
      createApiResponse(application, 'Application submitted successfully'),
      { status: 201 }
    );

  } catch (error) {
    console.error('Application submission error:', error);
    return NextResponse.json(
      createApiError('Internal server error', 'INTERNAL_ERROR'),
      { status: 500 }
    );
  }
}

// GET /api/applications - Get applications (user's own or all for managers)
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
    const status = searchParams.get('status') as ApplicationStatus | null;
    const page = parseInt(searchParams.get('page') || '1');
    const limit = parseInt(searchParams.get('limit') || '10');
    const offset = (page - 1) * limit;

    // Managers and above can see all applications, others see only their own
    const canViewAll = hasRole(session.user.role, UserRole.MANAGER);
    
    const where: any = {};
    if (!canViewAll) {
      where.userId = session.user.id;
    }
    if (status) {
      where.status = status;
    }

    const [applications, total] = await Promise.all([
      prisma.application.findMany({
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
          decidedBy: {
            select: {
              id: true,
              fullName: true,
            }
          }
        },
        orderBy: { submittedAt: 'desc' },
        skip: offset,
        take: limit,
      }),
      prisma.application.count({ where })
    ]);

    return NextResponse.json(
      createApiResponse({
        applications,
        pagination: {
          page,
          limit,
          total,
          totalPages: Math.ceil(total / limit),
          hasNext: page * limit < total,
          hasPrev: page > 1,
        }
      })
    );

  } catch (error) {
    console.error('Get applications error:', error);
    return NextResponse.json(
      createApiError('Internal server error', 'INTERNAL_ERROR'),
      { status: 500 }
    );
  }
}
