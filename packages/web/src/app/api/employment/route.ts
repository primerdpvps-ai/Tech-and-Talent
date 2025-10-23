import { NextRequest, NextResponse } from 'next/server';
import { getServerSession } from 'next-auth';
import { prisma } from '@tts-pms/db';
import { createApiResponse, createApiError, UserRole } from '@tts-pms/infra';
import { authOptions, hasRole } from '@/lib/auth';

// GET /api/employment - Get employment information (employee-facing)
export async function GET(request: NextRequest) {
  try {
    const session = await getServerSession(authOptions);
    if (!session) {
      return NextResponse.json(
        createApiError('Unauthorized', 'UNAUTHORIZED'),
        { status: 401 }
      );
    }

    // Only employees and above can access employment data
    if (!hasRole(session.user.role, UserRole.NEW_EMPLOYEE)) {
      return NextResponse.json(
        createApiError('Employment data not available for your role', 'INVALID_ROLE'),
        { status: 403 }
      );
    }

    const employment = await prisma.employment.findUnique({
      where: { userId: session.user.id },
      include: {
        user: {
          select: {
            id: true,
            email: true,
            fullName: true,
            role: true,
            createdAt: true,
          }
        }
      }
    });

    if (!employment) {
      return NextResponse.json(
        createApiError('Employment record not found', 'EMPLOYMENT_NOT_FOUND'),
        { status: 404 }
      );
    }

    // Calculate employment duration
    const now = new Date();
    const startDate = new Date(employment.startDate);
    const daysEmployed = Math.floor((now.getTime() - startDate.getTime()) / (1000 * 60 * 60 * 24));
    const monthsEmployed = Math.floor(daysEmployed / 30);

    // Check payroll eligibility
    const isPayrollEligible = now >= employment.firstPayrollEligibleFrom;
    const daysUntilPayrollEligible = isPayrollEligible 
      ? 0 
      : Math.ceil((employment.firstPayrollEligibleFrom.getTime() - now.getTime()) / (1000 * 60 * 60 * 24));

    // Get recent payroll data
    const recentPayrolls = await prisma.payrollWeek.findMany({
      where: { userId: session.user.id },
      orderBy: { weekStart: 'desc' },
      take: 5,
      select: {
        id: true,
        weekStart: true,
        weekEnd: true,
        hoursDecimal: true,
        finalAmount: true,
        status: true,
        paidAt: true,
      }
    });

    // Get current week's summary
    const currentWeekStart = new Date();
    currentWeekStart.setDate(currentWeekStart.getDate() - currentWeekStart.getDay());
    currentWeekStart.setHours(0, 0, 0, 0);

    const currentWeekSummary = await prisma.dailySummary.findMany({
      where: {
        userId: session.user.id,
        date: {
          gte: currentWeekStart,
        }
      },
      select: {
        date: true,
        billableSeconds: true,
        meetsDailyMinimum: true,
      }
    });

    const totalCurrentWeekSeconds = currentWeekSummary.reduce(
      (sum, day) => sum + day.billableSeconds, 0
    );
    const currentWeekHours = Math.round((totalCurrentWeekSeconds / 3600) * 100) / 100;

    return NextResponse.json(
      createApiResponse({
        employment: {
          ...employment,
          user: employment.user,
        },
        stats: {
          daysEmployed,
          monthsEmployed,
          isPayrollEligible,
          daysUntilPayrollEligible,
          currentWeekHours,
          securityFundStatus: employment.securityFundDeducted ? 'Deducted' : 'Pending',
        },
        recentPayrolls,
        currentWeekSummary: currentWeekSummary.map(day => ({
          date: day.date,
          hours: Math.round((day.billableSeconds / 3600) * 100) / 100,
          meetsDailyMinimum: day.meetsDailyMinimum,
        })),
        rdpAccess: {
          host: employment.rdpHost,
          username: employment.rdpUsername,
          available: !!(employment.rdpHost && employment.rdpUsername),
        }
      })
    );

  } catch (error) {
    console.error('Get employment error:', error);
    return NextResponse.json(
      createApiError('Internal server error', 'INTERNAL_ERROR'),
      { status: 500 }
    );
  }
}
