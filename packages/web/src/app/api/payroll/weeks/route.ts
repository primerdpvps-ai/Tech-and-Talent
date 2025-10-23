import { NextRequest, NextResponse } from 'next/server';
import { getServerSession } from 'next-auth';
import { prisma } from '@tts-pms/db';
import { createApiResponse, createApiError, UserRole, PayrollStatus } from '@tts-pms/infra';
import { authOptions, hasRole } from '@/lib/auth';

// GET /api/payroll/weeks - Get payroll weeks (mine or all for managers)
export async function GET(request: NextRequest) {
  try {
    const session = await getServerSession(authOptions);
    if (!session) {
      return NextResponse.json(
        createApiError('Unauthorized', 'UNAUTHORIZED'),
        { status: 401 }
      );
    }

    // Only employees and above can access payroll data
    if (!hasRole(session.user.role, UserRole.NEW_EMPLOYEE)) {
      return NextResponse.json(
        createApiError('Payroll data not available for your role', 'INVALID_ROLE'),
        { status: 403 }
      );
    }

    const { searchParams } = new URL(request.url);
    const status = searchParams.get('status') as PayrollStatus | null;
    const userId = searchParams.get('userId');
    const year = searchParams.get('year');
    const month = searchParams.get('month');
    const page = parseInt(searchParams.get('page') || '1');
    const limit = parseInt(searchParams.get('limit') || '10');
    const offset = (page - 1) * limit;

    // Managers and above can see all payrolls, employees see only their own
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

    // Filter by year/month if provided
    if (year) {
      const yearNum = parseInt(year);
      const startOfYear = new Date(yearNum, 0, 1);
      const endOfYear = new Date(yearNum + 1, 0, 1);
      
      where.weekStart = {
        gte: startOfYear,
        lt: endOfYear,
      };

      if (month) {
        const monthNum = parseInt(month) - 1; // JavaScript months are 0-indexed
        const startOfMonth = new Date(yearNum, monthNum, 1);
        const endOfMonth = new Date(yearNum, monthNum + 1, 1);
        
        where.weekStart = {
          gte: startOfMonth,
          lt: endOfMonth,
        };
      }
    }

    const [payrollWeeks, total] = await Promise.all([
      prisma.payrollWeek.findMany({
        where,
        include: {
          user: {
            select: {
              id: true,
              fullName: true,
              email: true,
              role: true,
            }
          },
          penalties: {
            select: {
              id: true,
              policyArea: true,
              amount: true,
              reason: true,
            }
          }
        },
        orderBy: { weekStart: 'desc' },
        skip: offset,
        take: limit,
      }),
      prisma.payrollWeek.count({ where })
    ]);

    // Calculate summary statistics
    const summary = {
      totalAmount: payrollWeeks.reduce((sum, week) => sum + Number(week.finalAmount), 0),
      totalHours: payrollWeeks.reduce((sum, week) => sum + Number(week.hoursDecimal), 0),
      averageWeeklyAmount: payrollWeeks.length > 0 
        ? payrollWeeks.reduce((sum, week) => sum + Number(week.finalAmount), 0) / payrollWeeks.length 
        : 0,
      statusCounts: {
        pending: payrollWeeks.filter(w => w.status === PayrollStatus.PENDING).length,
        processing: payrollWeeks.filter(w => w.status === PayrollStatus.PROCESSING).length,
        paid: payrollWeeks.filter(w => w.status === PayrollStatus.PAID).length,
        delayed: payrollWeeks.filter(w => w.status === PayrollStatus.DELAYED).length,
      }
    };

    return NextResponse.json(
      createApiResponse({
        payrollWeeks: payrollWeeks.map(week => ({
          ...week,
          deductions: week.deductions || {},
          penalties: week.penalties,
        })),
        pagination: {
          page,
          limit,
          total,
          totalPages: Math.ceil(total / limit),
          hasNext: page * limit < total,
          hasPrev: page > 1,
        },
        summary,
      })
    );

  } catch (error) {
    console.error('Get payroll weeks error:', error);
    return NextResponse.json(
      createApiError('Internal server error', 'INTERNAL_ERROR'),
      { status: 500 }
    );
  }
}
