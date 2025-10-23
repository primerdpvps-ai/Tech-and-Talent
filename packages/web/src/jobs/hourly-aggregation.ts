import { prisma } from '@tts-pms/db';
import { JobResult, JobContext } from '@/lib/job-runner';

/**
 * Hourly job to aggregate TimerSession data into DailySummary
 * Runs every hour to process completed timer sessions
 */
export async function hourlyAggregationJob(context: JobContext): Promise<JobResult> {
  const { jobName, startTime } = context;
  let recordsProcessed = 0;
  let summariesUpdated = 0;

  try {
    // Get the last hour window
    const oneHourAgo = new Date(startTime.getTime() - 60 * 60 * 1000);
    const twoHoursAgo = new Date(startTime.getTime() - 2 * 60 * 60 * 1000);

    // Find completed timer sessions from the last hour that haven't been aggregated
    const completedSessions = await prisma.timerSession.findMany({
      where: {
        endedAt: {
          gte: twoHoursAgo,
          lt: oneHourAgo
        },
        // Only process sessions that haven't been aggregated yet
        aggregatedAt: null
      },
      include: {
        user: {
          select: {
            id: true,
            role: true,
            employment: {
              select: {
                startDate: true
              }
            }
          }
        }
      },
      orderBy: {
        endedAt: 'asc'
      }
    });

    console.log(`Found ${completedSessions.length} completed sessions to aggregate`);

    // Group sessions by user and date
    const sessionsByUserDate = new Map<string, {
      userId: string;
      date: Date;
      sessions: typeof completedSessions;
      totalActiveSeconds: number;
    }>();

    for (const session of completedSessions) {
      if (!session.endedAt || !session.user) continue;

      const sessionDate = new Date(session.startedAt);
      sessionDate.setHours(0, 0, 0, 0);
      
      const key = `${session.userId}_${sessionDate.toISOString()}`;
      
      if (!sessionsByUserDate.has(key)) {
        sessionsByUserDate.set(key, {
          userId: session.userId,
          date: sessionDate,
          sessions: [],
          totalActiveSeconds: 0
        });
      }

      const group = sessionsByUserDate.get(key)!;
      group.sessions.push(session);
      group.totalActiveSeconds += session.activeSeconds || 0;
    }

    // Process each user-date group
    for (const [key, group] of sessionsByUserDate) {
      const { userId, date, sessions, totalActiveSeconds } = group;

      // Check if user meets daily minimum (6 hours = 21600 seconds)
      const meetsDailyMinimum = totalActiveSeconds >= 21600;

      // Get existing daily summary or create new one
      const existingSummary = await prisma.dailySummary.findUnique({
        where: {
          userId_date: {
            userId,
            date
          }
        }
      });

      if (existingSummary) {
        // Update existing summary
        await prisma.dailySummary.update({
          where: {
            userId_date: {
              userId,
              date
            }
          },
          data: {
            billableSeconds: existingSummary.billableSeconds + totalActiveSeconds,
            meetsDailyMinimum: existingSummary.billableSeconds + totalActiveSeconds >= 21600,
            lastUpdatedAt: startTime
          }
        });
      } else {
        // Create new daily summary
        await prisma.dailySummary.create({
          data: {
            userId,
            date,
            billableSeconds: totalActiveSeconds,
            meetsDailyMinimum,
            uploadsDone: false,
            lastUpdatedAt: startTime
          }
        });
      }

      summariesUpdated++;

      // Mark sessions as aggregated
      const sessionIds = sessions.map(s => s.id);
      await prisma.timerSession.updateMany({
        where: {
          id: {
            in: sessionIds
          }
        },
        data: {
          aggregatedAt: startTime
        }
      });

      recordsProcessed += sessions.length;
    }

    // Clean up old aggregated sessions (older than 30 days)
    const thirtyDaysAgo = new Date(startTime.getTime() - 30 * 24 * 60 * 60 * 1000);
    const cleanupResult = await prisma.timerSession.deleteMany({
      where: {
        endedAt: {
          lt: thirtyDaysAgo
        },
        aggregatedAt: {
          not: null
        }
      }
    });

    console.log(`Cleaned up ${cleanupResult.count} old aggregated sessions`);

    return {
      success: true,
      message: `Aggregated ${recordsProcessed} timer sessions into ${summariesUpdated} daily summaries`,
      duration: Date.now() - startTime.getTime(),
      recordsProcessed,
      data: {
        sessionsProcessed: recordsProcessed,
        summariesUpdated,
        sessionsCleanedUp: cleanupResult.count
      }
    };

  } catch (error) {
    console.error('Hourly aggregation job failed:', error);
    return {
      success: false,
      message: 'Failed to aggregate timer sessions',
      error: error instanceof Error ? error.message : 'Unknown error',
      duration: Date.now() - startTime.getTime(),
      recordsProcessed
    };
  }
}
