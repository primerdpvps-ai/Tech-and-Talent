import { NextRequest, NextResponse } from 'next/server';
import { getServerSession } from 'next-auth';
import { z } from 'zod';
import { prisma } from '@tts-pms/db';
import { createApiResponse, createApiError, UserRole, PayrollStatus, PAYROLL_CONSTANTS } from '@tts-pms/infra';
import { authOptions } from '@/lib/auth';

const PayrollRunSchema = z.object({
  weekStart: z.string().transform((str: string) => new Date(str)),
  weekEnd: z.string().transform((str: string) => new Date(str)),
  userIds: z.array(z.string()).optional(), // If not provided, run for all eligible employees
  preview: z.boolean().default(true), // Preview mode by default
});

interface PayrollCalculation {
  userId: string;
  userName: string;
  hoursDecimal: number;
  baseAmount: number;
  streakBonus: number;
  deductions: {
    securityFund?: number;
    penalties?: number;
    taxes?: number;
  };
  finalAmount: number;
  eligibleForPayroll: boolean;
  reasons?: string[];
}

async function calculatePayroll(
  userId: string,
  weekStart: Date,
  weekEnd: Date
): Promise<PayrollCalculation> {
  // Get user info
  const user = await prisma.user.findUnique({
    where: { id: userId },
    select: {
      id: true,
      fullName: true,
      role: true,
      employment: true,
    }
  });

  if (!user || !user.employment) {
    return {
      userId,
      userName: 'Unknown User',
      hoursDecimal: 0,
      baseAmount: 0,
      streakBonus: 0,
      deductions: {},
      finalAmount: 0,
      eligibleForPayroll: false,
      reasons: ['No employment record found'],
    };
  }

  // Check payroll eligibility
  const isEligible = weekStart >= user.employment.firstPayrollEligibleFrom;
  if (!isEligible) {
    return {
      userId,
      userName: user.fullName,
      hoursDecimal: 0,
      baseAmount: 0,
      streakBonus: 0,
      deductions: {},
      finalAmount: 0,
      eligibleForPayroll: false,
      reasons: ['Not yet eligible for payroll'],
    };
  }

  // Get daily summaries for the week
  const dailySummaries = await prisma.dailySummary.findMany({
    where: {
      userId,
      date: {
        gte: weekStart,
        lte: weekEnd,
      }
    }
  });

  type DailySummaryRecord = (typeof dailySummaries)[number];

  // Calculate total billable hours
  const totalSeconds = dailySummaries.reduce(
    (sum: number, day: DailySummaryRecord) => sum + day.billableSeconds,
    0
  );
  const hoursDecimal = Math.round((totalSeconds / 3600) * 100) / 100;

  // Calculate base amount
  const baseAmount = hoursDecimal * PAYROLL_CONSTANTS.hourlyRate;

  // Calculate streak bonus (if worked all 7 days and met daily minimums)
  const daysWorked = dailySummaries.filter(
    (day: DailySummaryRecord) => day.meetsDailyMinimum
  ).length;
  const streakBonus = daysWorked >= 7 ? PAYROLL_CONSTANTS.streakBonus : 0;

  // Calculate deductions
  const deductions: any = {};
  let totalDeductions = 0;

  // Security fund deduction (one-time)
  if (!user.employment.securityFundDeducted) {
    deductions.securityFund = PAYROLL_CONSTANTS.securityFund;
    totalDeductions += PAYROLL_CONSTANTS.securityFund;
  }

  // Get penalties for this week
  const penalties = await prisma.penalty.findMany({
    where: {
      userId,
      payrollWeek: {
        weekStart,
        weekEnd,
      }
    }
  });

  if (penalties.length > 0) {
    type PenaltyRecord = (typeof penalties)[number];

    const penaltyAmount = penalties.reduce(
      (sum: number, penalty: PenaltyRecord) => sum + Number(penalty.amount),
      0
    );
    deductions.penalties = penaltyAmount;
    totalDeductions += penaltyAmount;
  }

  // Calculate final amount
  const finalAmount = Math.max(0, baseAmount + streakBonus - totalDeductions);

  return {
    userId,
    userName: user.fullName,
    hoursDecimal,
    baseAmount,
    streakBonus,
    deductions,
    finalAmount,
    eligibleForPayroll: true,
  };
}

// POST /api/payroll/run - Run payroll calculation (CEO only)
export async function POST(request: NextRequest) {
  try {
    const session = await getServerSession(authOptions);
    if (!session) {
      return NextResponse.json(
        createApiError('Unauthorized', 'UNAUTHORIZED'),
        { status: 401 }
      );
    }

    // Only CEO can run payroll
    if (session.user.role !== UserRole.CEO) {
      return NextResponse.json(
        createApiError('Only CEO can run payroll', 'INSUFFICIENT_PERMISSIONS'),
        { status: 403 }
      );
    }

    const body = await request.json();
    const validation = PayrollRunSchema.safeParse(body);
    if (!validation.success) {
      return NextResponse.json(
        createApiError('Invalid payroll data', 'VALIDATION_ERROR'),
        { status: 400 }
      );
    }

    const { weekStart, weekEnd, userIds, preview } = validation.data;

    // Validate week range
    const weekDiff = Math.ceil((weekEnd.getTime() - weekStart.getTime()) / (1000 * 60 * 60 * 24));
    if (weekDiff !== 6) {
      return NextResponse.json(
        createApiError('Week range must be exactly 7 days', 'INVALID_WEEK_RANGE'),
        { status: 400 }
      );
    }

    // Get eligible employees
    let eligibleUsers;
    if (userIds && userIds.length > 0) {
      eligibleUsers = await prisma.user.findMany({
        where: {
          id: { in: userIds },
          role: { in: [UserRole.NEW_EMPLOYEE, UserRole.EMPLOYEE, UserRole.MANAGER] },
          employment: { isNot: null },
        },
        select: { id: true }
      });
    } else {
      eligibleUsers = await prisma.user.findMany({
        where: {
          role: { in: [UserRole.NEW_EMPLOYEE, UserRole.EMPLOYEE, UserRole.MANAGER] },
          employment: { isNot: null },
        },
        select: { id: true }
      });
    }

    // Calculate payroll for each user
    const calculations: PayrollCalculation[] = [];
    for (const user of eligibleUsers) {
      const calculation = await calculatePayroll(user.id, weekStart, weekEnd);
      calculations.push(calculation);
    }

    // If not preview mode, create payroll records
    if (!preview) {
      const payrollRecords = [];
      
      for (const calc of calculations) {
        if (calc.eligibleForPayroll) {
          // Check if payroll already exists for this week
          const existingPayroll = await prisma.payrollWeek.findUnique({
            where: {
              userId_weekStart: {
                userId: calc.userId,
                weekStart,
              }
            }
          });

          if (!existingPayroll) {
            const payroll = await prisma.payrollWeek.create({
              data: {
                userId: calc.userId,
                weekStart,
                weekEnd,
                hoursDecimal: calc.hoursDecimal,
                baseAmount: calc.baseAmount,
                streakBonus: calc.streakBonus,
                deductions: calc.deductions,
                finalAmount: calc.finalAmount,
                status: PayrollStatus.PENDING,
              }
            });

            // Update security fund deduction status
            if (calc.deductions.securityFund) {
              await prisma.employment.update({
                where: { userId: calc.userId },
                data: { securityFundDeducted: true }
              });
            }

            payrollRecords.push(payroll);
          }
        }
      }

      return NextResponse.json(
        createApiResponse({
          calculations,
          payrollRecords,
          summary: {
            totalEmployees: calculations.length,
            eligibleEmployees: calculations.filter(c => c.eligibleForPayroll).length,
            totalAmount: calculations.reduce((sum, c) => sum + c.finalAmount, 0),
            totalHours: calculations.reduce((sum, c) => sum + c.hoursDecimal, 0),
          }
        }, 'Payroll run completed successfully')
      );
    }

    // Preview mode - return calculations only
    return NextResponse.json(
      createApiResponse({
        preview: true,
        calculations,
        summary: {
          totalEmployees: calculations.length,
          eligibleEmployees: calculations.filter(c => c.eligibleForPayroll).length,
          totalAmount: calculations.reduce((sum, c) => sum + c.finalAmount, 0),
          totalHours: calculations.reduce((sum, c) => sum + c.hoursDecimal, 0),
        }
      }, 'Payroll preview generated successfully')
    );

  } catch (error) {
    console.error('Payroll run error:', error);
    return NextResponse.json(
      createApiError('Internal server error', 'INTERNAL_ERROR'),
      { status: 500 }
    );
  }
}
