import { prisma } from '@tts-pms/db';
import { JobResult, JobContext } from '@/lib/job-runner';

/**
 * Weekly payroll composition job
 * Runs every Monday to compose payroll for the previous week and notify CEO
 */
export async function payrollComposeJob(context: JobContext): Promise<JobResult> {
  const { jobName, startTime } = context;
  let recordsProcessed = 0;
  let payrollCreated = false;

  try {
    // Calculate the previous week (Monday to Sunday)
    const currentMonday = new Date(startTime);
    const dayOfWeek = currentMonday.getDay();
    const daysToSubtract = dayOfWeek === 0 ? 6 : dayOfWeek - 1; // If Sunday, go back 6 days to Monday
    currentMonday.setDate(currentMonday.getDate() - daysToSubtract);
    currentMonday.setHours(0, 0, 0, 0);

    // Previous week Monday
    const previousWeekMonday = new Date(currentMonday);
    previousWeekMonday.setDate(previousWeekMonday.getDate() - 7);

    // Previous week Sunday
    const previousWeekSunday = new Date(previousWeekMonday);
    previousWeekSunday.setDate(previousWeekSunday.getDate() + 6);
    previousWeekSunday.setHours(23, 59, 59, 999);

    console.log(`Composing payroll for week: ${previousWeekMonday.toISOString()} to ${previousWeekSunday.toISOString()}`);

    // Check if payroll batch already exists for this week
    const existingBatch = await prisma.payrollBatch.findFirst({
      where: {
        weekOf: previousWeekMonday,
        status: {
          in: ['DRAFT', 'PENDING_APPROVAL', 'APPROVED', 'PAID']
        }
      }
    });

    if (existingBatch) {
      console.log(`Payroll batch already exists for week ${previousWeekMonday.toISOString()}: ${existingBatch.id}`);
      return {
        success: true,
        message: `Payroll batch already exists for week ${previousWeekMonday.toLocaleDateString()}`,
        duration: Date.now() - startTime.getTime(),
        recordsProcessed: 0,
        data: {
          existingBatchId: existingBatch.id,
          batchStatus: existingBatch.status
        }
      };
    }

    // Get all employees who worked during the previous week
    const employeesWithWork = await prisma.user.findMany({
      where: {
        role: {
          in: ['EMPLOYEE', 'NEW_EMPLOYEE', 'MANAGER']
        },
        employment: {
          isNot: null
        },
        dailySummaries: {
          some: {
            date: {
              gte: previousWeekMonday,
              lte: previousWeekSunday
            },
            billableSeconds: {
              gt: 0
            }
          }
        }
      },
      include: {
        employment: true,
        dailySummaries: {
          where: {
            date: {
              gte: previousWeekMonday,
              lte: previousWeekSunday
            }
          }
        },
        penalties: {
          where: {
            appliedAt: {
              gte: previousWeekMonday,
              lte: previousWeekSunday
            }
          }
        },
        bonuses: {
          where: {
            awardedAt: {
              gte: previousWeekMonday,
              lte: previousWeekSunday
            }
          }
        }
      }
    });

    console.log(`Found ${employeesWithWork.length} employees with work for payroll`);

    if (employeesWithWork.length === 0) {
      return {
        success: true,
        message: `No employees worked during week ${previousWeekMonday.toLocaleDateString()}`,
        duration: Date.now() - startTime.getTime(),
        recordsProcessed: 0
      };
    }

    // Get system rates and settings
    const systemSettings = await getSystemSettings();
    const payrollEntries: any[] = [];
    let totalGrossAmount = 0;
    let totalNetAmount = 0;

    for (const employee of employeesWithWork) {
      if (!employee.employment) continue;

      const { dailySummaries, penalties, bonuses } = employee;
      
      // Calculate total billable hours
      const totalBillableSeconds = dailySummaries.reduce((sum, summary) => sum + summary.billableSeconds, 0);
      const totalBillableHours = totalBillableSeconds / 3600;

      // Skip if less than minimum hours (e.g., 1 hour)
      if (totalBillableHours < 1) continue;

      // Determine hourly rate based on role
      const hourlyRate = getHourlyRate(employee.role, systemSettings);

      // Calculate base pay
      const basePay = totalBillableHours * hourlyRate;

      // Calculate weekend/overtime multipliers
      let weekendHours = 0;
      let overtimeHours = 0;

      for (const summary of dailySummaries) {
        const summaryDate = new Date(summary.date);
        const dayOfWeek = summaryDate.getDay();
        const isWeekend = dayOfWeek === 0 || dayOfWeek === 6;
        const dailyHours = summary.billableSeconds / 3600;

        if (isWeekend) {
          weekendHours += dailyHours;
        }

        // Overtime after 8 hours per day
        if (dailyHours > 8) {
          overtimeHours += dailyHours - 8;
        }
      }

      const weekendPay = weekendHours * hourlyRate * (systemSettings.weekendMultiplier - 1);
      const overtimePay = overtimeHours * hourlyRate * (systemSettings.overtimeMultiplier - 1);

      // Calculate bonuses
      const totalBonuses = bonuses.reduce((sum, bonus) => sum + bonus.amount, 0);

      // Add performance bonuses
      let performanceBonus = 0;
      const weeklyPerformance = calculateWeeklyPerformance(dailySummaries);
      if (weeklyPerformance >= 95) {
        performanceBonus = systemSettings.performanceBonus95;
      } else if (weeklyPerformance >= 90) {
        performanceBonus = systemSettings.performanceBonus90;
      }

      // Perfect attendance bonus (worked all 5 weekdays)
      const weekdaysWorked = dailySummaries.filter(s => {
        const date = new Date(s.date);
        const day = date.getDay();
        return day >= 1 && day <= 5 && s.billableSeconds > 0;
      }).length;

      const perfectAttendanceBonus = weekdaysWorked >= 5 ? systemSettings.perfectAttendanceWeekly : 0;

      // Calculate penalties
      const totalPenalties = penalties.reduce((sum, penalty) => sum + penalty.amount, 0);

      // Calculate gross and net pay
      const grossPay = basePay + weekendPay + overtimePay + totalBonuses + performanceBonus + perfectAttendanceBonus;
      const netPay = grossPay - totalPenalties;

      totalGrossAmount += grossPay;
      totalNetAmount += netPay;

      payrollEntries.push({
        userId: employee.id,
        employeeName: employee.fullName,
        role: employee.role,
        hoursWorked: Math.round(totalBillableHours * 100) / 100,
        hourlyRate,
        basePay: Math.round(basePay * 100) / 100,
        weekendHours: Math.round(weekendHours * 100) / 100,
        weekendPay: Math.round(weekendPay * 100) / 100,
        overtimeHours: Math.round(overtimeHours * 100) / 100,
        overtimePay: Math.round(overtimePay * 100) / 100,
        bonuses: Math.round((totalBonuses + performanceBonus + perfectAttendanceBonus) * 100) / 100,
        penalties: Math.round(totalPenalties * 100) / 100,
        grossPay: Math.round(grossPay * 100) / 100,
        netPay: Math.round(netPay * 100) / 100,
        weeklyPerformance: Math.round(weeklyPerformance * 100) / 100
      });

      recordsProcessed++;
    }

    // Create payroll batch
    const payrollBatch = await prisma.payrollBatch.create({
      data: {
        weekOf: previousWeekMonday,
        status: 'PENDING_APPROVAL',
        employeeCount: payrollEntries.length,
        totalGrossAmount: Math.round(totalGrossAmount * 100) / 100,
        totalNetAmount: Math.round(totalNetAmount * 100) / 100,
        createdAt: startTime,
        payrollEntries: {
          create: payrollEntries.map(entry => ({
            userId: entry.userId,
            hoursWorked: entry.hoursWorked,
            hourlyRate: entry.hourlyRate,
            basePay: entry.basePay,
            weekendPay: entry.weekendPay,
            overtimePay: entry.overtimePay,
            bonuses: entry.bonuses,
            penalties: entry.penalties,
            grossPay: entry.grossPay,
            netPay: entry.netPay,
            metadata: {
              weekendHours: entry.weekendHours,
              overtimeHours: entry.overtimeHours,
              weeklyPerformance: entry.weeklyPerformance,
              role: entry.role
            }
          }))
        }
      }
    });

    payrollCreated = true;

    // Notify CEO for approval
    const ceoUser = await prisma.user.findFirst({
      where: { role: 'CEO' }
    });

    if (ceoUser) {
      await prisma.notification.create({
        data: {
          userId: ceoUser.id,
          type: 'PAYROLL_APPROVAL_REQUIRED',
          title: 'Payroll Batch Ready for Approval',
          message: `Payroll batch for week ending ${previousWeekSunday.toLocaleDateString()} is ready for approval. ${payrollEntries.length} employees, total amount: $${totalNetAmount.toFixed(2)}`,
          data: {
            payrollBatchId: payrollBatch.id,
            weekOf: previousWeekMonday.toISOString(),
            employeeCount: payrollEntries.length,
            totalGrossAmount: totalGrossAmount,
            totalNetAmount: totalNetAmount,
            approvalUrl: `/admin/payroll/${payrollBatch.id}`
          },
          priority: 'HIGH'
        }
      });
    }

    // Notify managers about their team's payroll
    const managers = await prisma.user.findMany({
      where: {
        role: 'MANAGER',
        managedEmployees: {
          some: {
            id: {
              in: payrollEntries.map(e => e.userId)
            }
          }
        }
      },
      include: {
        managedEmployees: {
          where: {
            id: {
              in: payrollEntries.map(e => e.userId)
            }
          }
        }
      }
    });

    for (const manager of managers) {
      const teamEntries = payrollEntries.filter(e => 
        manager.managedEmployees.some(emp => emp.id === e.userId)
      );
      
      const teamTotal = teamEntries.reduce((sum, entry) => sum + entry.netPay, 0);

      await prisma.notification.create({
        data: {
          userId: manager.id,
          type: 'TEAM_PAYROLL_SUMMARY',
          title: 'Team Payroll Summary',
          message: `Payroll processed for ${teamEntries.length} team members for week ending ${previousWeekSunday.toLocaleDateString()}. Team total: $${teamTotal.toFixed(2)}`,
          data: {
            payrollBatchId: payrollBatch.id,
            weekOf: previousWeekMonday.toISOString(),
            teamMemberCount: teamEntries.length,
            teamTotal: teamTotal
          },
          priority: 'MEDIUM'
        }
      });
    }

    return {
      success: true,
      message: `Created payroll batch ${payrollBatch.id} for ${payrollEntries.length} employees, total: $${totalNetAmount.toFixed(2)}`,
      duration: Date.now() - startTime.getTime(),
      recordsProcessed,
      data: {
        payrollBatchId: payrollBatch.id,
        weekOf: previousWeekMonday.toISOString(),
        employeeCount: payrollEntries.length,
        totalGrossAmount: Math.round(totalGrossAmount * 100) / 100,
        totalNetAmount: Math.round(totalNetAmount * 100) / 100,
        notificationsSent: 1 + managers.length
      }
    };

  } catch (error) {
    console.error('Payroll compose job failed:', error);
    return {
      success: false,
      message: 'Failed to compose payroll batch',
      error: error instanceof Error ? error.message : 'Unknown error',
      duration: Date.now() - startTime.getTime(),
      recordsProcessed
    };
  }
}

/**
 * Get system settings for payroll calculation
 */
async function getSystemSettings() {
  const settings = await prisma.systemSettings.findMany({
    where: {
      key: {
        in: [
          'base_hourly_rate',
          'manager_hourly_rate',
          'weekend_multiplier',
          'overtime_multiplier',
          'performance_bonus_90',
          'performance_bonus_95',
          'perfect_attendance_weekly'
        ]
      }
    }
  });

  const settingsMap = settings.reduce((acc, setting) => {
    acc[setting.key] = parseFloat(setting.value);
    return acc;
  }, {} as Record<string, number>);

  return {
    baseHourlyRate: settingsMap.base_hourly_rate || 15,
    managerHourlyRate: settingsMap.manager_hourly_rate || 25,
    weekendMultiplier: settingsMap.weekend_multiplier || 1.5,
    overtimeMultiplier: settingsMap.overtime_multiplier || 1.5,
    performanceBonus90: settingsMap.performance_bonus_90 || 100,
    performanceBonus95: settingsMap.performance_bonus_95 || 150,
    perfectAttendanceWeekly: settingsMap.perfect_attendance_weekly || 50
  };
}

/**
 * Get hourly rate based on employee role
 */
function getHourlyRate(role: string, settings: any): number {
  switch (role) {
    case 'MANAGER':
      return settings.managerHourlyRate;
    case 'EMPLOYEE':
    case 'NEW_EMPLOYEE':
    default:
      return settings.baseHourlyRate;
  }
}

/**
 * Calculate weekly performance percentage
 */
function calculateWeeklyPerformance(dailySummaries: any[]): number {
  if (dailySummaries.length === 0) return 0;

  const totalPossibleHours = dailySummaries.length * 8; // 8 hours per day
  const totalActualHours = dailySummaries.reduce((sum, summary) => sum + (summary.billableSeconds / 3600), 0);

  return Math.min(100, (totalActualHours / totalPossibleHours) * 100);
}
