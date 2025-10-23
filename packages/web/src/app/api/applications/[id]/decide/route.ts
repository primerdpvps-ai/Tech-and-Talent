import { NextRequest, NextResponse } from 'next/server';
import { getServerSession } from 'next-auth';
import { z } from 'zod';
import { prisma } from '@tts-pms/db';
import { createApiResponse, createApiError, ApplicationStatus, UserRole } from '@tts-pms/infra';
import { authOptions, hasRole } from '@/lib/auth';

const DecisionSchema = z.object({
  decision: z.enum(['approve', 'reject']),
  reasons: z.array(z.string()).optional(),
  notes: z.string().optional(),
});

// POST /api/applications/[id]/decide - Approve or reject application (MANAGER/CEO only)
export async function POST(
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

    // Only managers and CEOs can make decisions
    if (!hasRole(session.user.role, UserRole.MANAGER)) {
      return NextResponse.json(
        createApiError('Insufficient permissions', 'INSUFFICIENT_PERMISSIONS'),
        { status: 403 }
      );
    }

    const body = await request.json();
    const validation = DecisionSchema.safeParse(body);
    if (!validation.success) {
      return NextResponse.json(
        createApiError('Invalid decision data', 'VALIDATION_ERROR'),
        { status: 400 }
      );
    }

    const { decision, reasons, notes } = validation.data;

    // Find the application
    const application = await prisma.application.findUnique({
      where: { id: params.id },
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

    if (!application) {
      return NextResponse.json(
        createApiError('Application not found', 'APPLICATION_NOT_FOUND'),
        { status: 404 }
      );
    }

    // Check if application is still pending
    if (application.status !== ApplicationStatus.UNDER_REVIEW) {
      return NextResponse.json(
        createApiError('Application has already been decided', 'ALREADY_DECIDED'),
        { status: 409 }
      );
    }

    const newStatus = decision === 'approve' ? ApplicationStatus.APPROVED : ApplicationStatus.REJECTED;

    // Update application with decision
    const updatedApplication = await prisma.application.update({
      where: { id: params.id },
      data: {
        status: newStatus,
        decidedAt: new Date(),
        decidedByUserId: session.user.id,
        reasons: reasons || null,
      },
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
      }
    });

    // If approved, update user role and create employment record
    if (decision === 'approve') {
      await prisma.user.update({
        where: { id: application.userId },
        data: { role: UserRole.NEW_EMPLOYEE }
      });

      // Create employment record
      const startDate = new Date();
      const firstPayrollDate = new Date(startDate);
      firstPayrollDate.setDate(startDate.getDate() + 7); // First payroll eligible after 1 week

      await prisma.employment.create({
        data: {
          userId: application.userId,
          startDate,
          firstPayrollEligibleFrom: firstPayrollDate,
          securityFundDeducted: false,
        }
      });
    }

    return NextResponse.json(
      createApiResponse(
        updatedApplication,
        `Application ${decision === 'approve' ? 'approved' : 'rejected'} successfully`
      )
    );

  } catch (error) {
    console.error('Application decision error:', error);
    return NextResponse.json(
      createApiError('Internal server error', 'INTERNAL_ERROR'),
      { status: 500 }
    );
  }
}
