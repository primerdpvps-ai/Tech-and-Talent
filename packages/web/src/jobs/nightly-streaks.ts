import { prisma } from '@tts-pms/db';
import { JobResult, JobContext } from '@/lib/job-runner';

/**
 * Nightly job to recompute streaks and daily compliance
 * Runs at 2 AM UTC to process the previous day's data
 */
export async function nightlyStreaksJob(context: JobContext): Promise<JobResult> {
  const { jobName, startTime } = context;
  let recordsProcessed = 0;
  let streaksUpdated = 0;

  try {
    // Get yesterday's date
    const yesterday = new Date(startTime);
    yesterday.setDate(yesterday.getDate() - 1);
    yesterday.setHours(0, 0, 0, 0);

    // Get all active employees
    const activeEmployees = await prisma.user.findMany({
      where: {
        role: {
          in: ['EMPLOYEE', 'NEW_EMPLOYEE', 'MANAGER']
        },
        employment: {
          isNot: null
        }
      },
      include: {
        employment: true,
        dailySummaries: {
          where: {
            date: {
              gte: new Date(yesterday.getTime() - 30 * 24 * 60 * 60 * 1000), // Last 30 days
              lte: yesterday
            }
          },
          orderBy: {
            date: 'desc'
          }
        }
      }
    });

    console.log(`Processing streaks for ${activeEmployees.length} active employees`);

    for (const employee of activeEmployees) {
      if (!employee.employment) continue;

      const { dailySummaries } = employee;
      
      // Calculate current streak (consecutive days meeting minimum)
      let currentStreak = 0;
      let longestStreak = 0;
      let tempStreak = 0;
      let totalWorkingDays = 0;
      let compliantDays = 0;

      // Sort summaries by date (newest first)
      const sortedSummaries = dailySummaries.sort((a, b) => b.date.getTime() - a.date.getTime());

      // Calculate current streak (from most recent day backwards)
      for (let i = 0; i < sortedSummaries.length; i++) {
        const summary = sortedSummaries[i];
        const summaryDate = new Date(summary.date);
        
        // Check if this is a working day (skip weekends for streak calculation)
        const dayOfWeek = summaryDate.getDay();
        const isWeekend = dayOfWeek === 0 || dayOfWeek === 6;
        
        if (!isWeekend) {
          totalWorkingDays++;
          
          if (summary.meetsDailyMinimum) {
            compliantDays++;
            if (i === 0 || currentStreak === i) {
              currentStreak++;
            }
          } else {
            // Streak broken
            if (currentStreak === i) {
              currentStreak = 0;
            }
          }
        }
      }

      // Calculate longest streak in the period
      tempStreak = 0;
      for (const summary of sortedSummaries.reverse()) { // Oldest first for longest streak
        const summaryDate = new Date(summary.date);
        const dayOfWeek = summaryDate.getDay();
        const isWeekend = dayOfWeek === 0 || dayOfWeek === 6;
        
        if (!isWeekend) {
          if (summary.meetsDailyMinimum) {
            tempStreak++;
            longestStreak = Math.max(longestStreak, tempStreak);
          } else {
            tempStreak = 0;
          }
        }
      }

      // Calculate compliance percentage
      const compliancePercentage = totalWorkingDays > 0 ? (compliantDays / totalWorkingDays) * 100 : 0;

      // Update or create employee performance record
      const performanceData = {
        currentStreak,
        longestStreak,
        totalWorkingDays,
        compliantDays,
        compliancePercentage: Math.round(compliancePercentage * 100) / 100,
        lastCalculatedAt: startTime,
        calculationPeriodStart: new Date(yesterday.getTime() - 30 * 24 * 60 * 60 * 1000),
        calculationPeriodEnd: yesterday
      };

      // Check if performance record exists
      const existingPerformance = await prisma.employeePerformance.findUnique({
        where: { userId: employee.id }
      });

      if (existingPerformance) {
        await prisma.employeePerformance.update({
          where: { userId: employee.id },
          data: performanceData
        });
      } else {
        await prisma.employeePerformance.create({
          data: {
            userId: employee.id,
            ...performanceData
          }
        });
      }

      streaksUpdated++;

      // Check for streak milestones and create notifications
      if (currentStreak > 0 && currentStreak % 7 === 0) {
        // Weekly streak milestone
        await prisma.notification.create({
          data: {
            userId: employee.id,
            type: 'STREAK_MILESTONE',
            title: `${currentStreak} Day Streak!`,
            message: `Congratulations! You've maintained a ${currentStreak}-day working streak.`,
            data: {
              streakDays: currentStreak,
              milestoneType: 'weekly'
            }
          }
        });
      }

      if (currentStreak > 0 && currentStreak % 30 === 0) {
        // Monthly streak milestone
        await prisma.notification.create({
          data: {
            userId: employee.id,
            type: 'STREAK_MILESTONE',
            title: `${currentStreak} Day Streak Achievement!`,
            message: `Amazing! You've achieved a ${currentStreak}-day perfect attendance streak.`,
            data: {
              streakDays: currentStreak,
              milestoneType: 'monthly'
            }
          }
        });
      }

      // Check for compliance issues
      if (compliancePercentage < 80 && totalWorkingDays >= 7) {
        await prisma.notification.create({
          data: {
            userId: employee.id,
            type: 'COMPLIANCE_WARNING',
            title: 'Attendance Compliance Alert',
            message: `Your attendance compliance is ${compliancePercentage.toFixed(1)}%. Please ensure you meet daily minimum requirements.`,
            data: {
              compliancePercentage,
              totalWorkingDays,
              compliantDays
            }
          }
        });
      }

      recordsProcessed++;
    }

    // Update system-wide statistics
    const systemStats = await prisma.employeePerformance.aggregate({
      _avg: {
        compliancePercentage: true,
        currentStreak: true
      },
      _max: {
        currentStreak: true,
        longestStreak: true
      },
      _count: {
        userId: true
      }
    });

    // Store system statistics
    await prisma.systemMetrics.upsert({
      where: {
        metricName: 'daily_compliance_stats'
      },
      update: {
        metricValue: systemStats._avg.compliancePercentage || 0,
        metadata: {
          averageCompliance: systemStats._avg.compliancePercentage,
          averageCurrentStreak: systemStats._avg.currentStreak,
          maxCurrentStreak: systemStats._max.currentStreak,
          maxLongestStreak: systemStats._max.longestStreak,
          totalEmployees: systemStats._count.userId,
          calculatedAt: startTime.toISOString()
        },
        updatedAt: startTime
      },
      create: {
        metricName: 'daily_compliance_stats',
        metricValue: systemStats._avg.compliancePercentage || 0,
        metadata: {
          averageCompliance: systemStats._avg.compliancePercentage,
          averageCurrentStreak: systemStats._avg.currentStreak,
          maxCurrentStreak: systemStats._max.currentStreak,
          maxLongestStreak: systemStats._max.longestStreak,
          totalEmployees: systemStats._count.userId,
          calculatedAt: startTime.toISOString()
        },
        updatedAt: startTime
      }
    });

    return {
      success: true,
      message: `Recomputed streaks and compliance for ${streaksUpdated} employees`,
      duration: Date.now() - startTime.getTime(),
      recordsProcessed,
      data: {
        employeesProcessed: streaksUpdated,
        systemStats: {
          averageCompliance: systemStats._avg.compliancePercentage,
          averageCurrentStreak: systemStats._avg.currentStreak,
          maxCurrentStreak: systemStats._max.currentStreak,
          totalEmployees: systemStats._count.userId
        }
      }
    };

  } catch (error) {
    console.error('Nightly streaks job failed:', error);
    return {
      success: false,
      message: 'Failed to recompute streaks and compliance',
      error: error instanceof Error ? error.message : 'Unknown error',
      duration: Date.now() - startTime.getTime(),
      recordsProcessed
    };
  }
}
