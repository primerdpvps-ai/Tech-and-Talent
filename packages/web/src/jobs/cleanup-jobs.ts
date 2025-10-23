import { prisma } from '@tts-pms/db';
import { JobResult, JobContext } from '@/lib/job-runner';

/**
 * Cleanup expired OTPs and sessions
 * Runs every 4 hours to clean up expired data
 */
export async function cleanupExpiredDataJob(context: JobContext): Promise<JobResult> {
  const { jobName, startTime } = context;
  let recordsProcessed = 0;
  let totalCleaned = 0;

  try {
    const cleanupResults = {
      expiredOTPs: 0,
      expiredSessions: 0,
      expiredTokens: 0,
      oldJobLogs: 0,
      oldNotifications: 0,
      oldAgentLogs: 0
    };

    // 1. Clean up expired OTPs (older than 10 minutes)
    const tenMinutesAgo = new Date(startTime.getTime() - 10 * 60 * 1000);
    const expiredOTPs = await prisma.otp.deleteMany({
      where: {
        OR: [
          {
            expiresAt: {
              lt: startTime
            }
          },
          {
            createdAt: {
              lt: tenMinutesAgo
            }
          }
        ]
      }
    });
    cleanupResults.expiredOTPs = expiredOTPs.count;
    totalCleaned += expiredOTPs.count;

    // 2. Clean up expired sessions (older than 7 days inactive)
    const sevenDaysAgo = new Date(startTime.getTime() - 7 * 24 * 60 * 60 * 1000);
    const expiredSessions = await prisma.session.deleteMany({
      where: {
        OR: [
          {
            expires: {
              lt: startTime
            }
          },
          {
            updatedAt: {
              lt: sevenDaysAgo
            }
          }
        ]
      }
    });
    cleanupResults.expiredSessions = expiredSessions.count;
    totalCleaned += expiredSessions.count;

    // 3. Clean up expired password reset tokens (older than 1 hour)
    const oneHourAgo = new Date(startTime.getTime() - 60 * 60 * 1000);
    const expiredTokens = await prisma.passwordResetToken.deleteMany({
      where: {
        OR: [
          {
            expiresAt: {
              lt: startTime
            }
          },
          {
            createdAt: {
              lt: oneHourAgo
            }
          }
        ]
      }
    });
    cleanupResults.expiredTokens = expiredTokens.count;
    totalCleaned += expiredTokens.count;

    // 4. Clean up old job logs (keep last 30 days)
    const thirtyDaysAgo = new Date(startTime.getTime() - 30 * 24 * 60 * 60 * 1000);
    const oldJobLogs = await prisma.jobLog.deleteMany({
      where: {
        startedAt: {
          lt: thirtyDaysAgo
        },
        status: {
          in: ['COMPLETED', 'FAILED']
        }
      }
    });
    cleanupResults.oldJobLogs = oldJobLogs.count;
    totalCleaned += oldJobLogs.count;

    // 5. Clean up old read notifications (older than 30 days)
    const oldNotifications = await prisma.notification.deleteMany({
      where: {
        createdAt: {
          lt: thirtyDaysAgo
        },
        readAt: {
          not: null
        }
      }
    });
    cleanupResults.oldNotifications = oldNotifications.count;
    totalCleaned += oldNotifications.count;

    // 6. Clean up old agent request logs (keep last 7 days)
    const oldAgentLogs = await prisma.agentRequestLog.deleteMany({
      where: {
        timestamp: {
          lt: sevenDaysAgo
        }
      }
    });
    cleanupResults.oldAgentLogs = oldAgentLogs.count;
    totalCleaned += oldAgentLogs.count;

    // 7. Clean up inactive agent devices (not accessed for 90 days)
    const ninetyDaysAgo = new Date(startTime.getTime() - 90 * 24 * 60 * 60 * 1000);
    const inactiveDevices = await prisma.agentDevice.updateMany({
      where: {
        lastLoginAt: {
          lt: ninetyDaysAgo
        },
        isActive: true
      },
      data: {
        isActive: false,
        deactivatedAt: startTime
      }
    });

    recordsProcessed = totalCleaned;

    // Log cleanup summary
    console.log(`Cleanup completed: ${JSON.stringify(cleanupResults, null, 2)}`);

    return {
      success: true,
      message: `Cleaned up ${totalCleaned} expired records`,
      duration: Date.now() - startTime.getTime(),
      recordsProcessed,
      data: {
        ...cleanupResults,
        inactiveDevicesMarked: inactiveDevices.count,
        totalCleaned
      }
    };

  } catch (error) {
    console.error('Cleanup job failed:', error);
    return {
      success: false,
      message: 'Failed to clean up expired data',
      error: error instanceof Error ? error.message : 'Unknown error',
      duration: Date.now() - startTime.getTime(),
      recordsProcessed
    };
  }
}

/**
 * Deep cleanup job for old data
 * Runs weekly to perform more aggressive cleanup
 */
export async function weeklyDeepCleanupJob(context: JobContext): Promise<JobResult> {
  const { jobName, startTime } = context;
  let recordsProcessed = 0;
  let totalCleaned = 0;

  try {
    const cleanupResults = {
      oldTimerSessions: 0,
      oldActivityLogs: 0,
      oldAuditLogs: 0,
      oldSystemMetrics: 0,
      orphanedFiles: 0
    };

    // 1. Clean up old aggregated timer sessions (older than 90 days)
    const ninetyDaysAgo = new Date(startTime.getTime() - 90 * 24 * 60 * 60 * 1000);
    const oldTimerSessions = await prisma.timerSession.deleteMany({
      where: {
        endedAt: {
          lt: ninetyDaysAgo
        },
        aggregatedAt: {
          not: null
        }
      }
    });
    cleanupResults.oldTimerSessions = oldTimerSessions.count;
    totalCleaned += oldTimerSessions.count;

    // 2. Clean up old activity logs (older than 60 days)
    const sixtyDaysAgo = new Date(startTime.getTime() - 60 * 24 * 60 * 60 * 1000);
    const oldActivityLogs = await prisma.activityLog.deleteMany({
      where: {
        timestamp: {
          lt: sixtyDaysAgo
        }
      }
    });
    cleanupResults.oldActivityLogs = oldActivityLogs.count;
    totalCleaned += oldActivityLogs.count;

    // 3. Clean up old audit logs (older than 1 year)
    const oneYearAgo = new Date(startTime.getTime() - 365 * 24 * 60 * 60 * 1000);
    const oldAuditLogs = await prisma.auditLog.deleteMany({
      where: {
        timestamp: {
          lt: oneYearAgo
        }
      }
    });
    cleanupResults.oldAuditLogs = oldAuditLogs.count;
    totalCleaned += oldAuditLogs.count;

    // 4. Clean up old system metrics (keep only last 6 months)
    const sixMonthsAgo = new Date(startTime.getTime() - 180 * 24 * 60 * 60 * 1000);
    const oldSystemMetrics = await prisma.systemMetrics.deleteMany({
      where: {
        updatedAt: {
          lt: sixMonthsAgo
        },
        metricName: {
          not: {
            startsWith: 'critical_'
          }
        }
      }
    });
    cleanupResults.oldSystemMetrics = oldSystemMetrics.count;
    totalCleaned += oldSystemMetrics.count;

    // 5. Mark orphaned file uploads for cleanup
    const orphanedUploads = await prisma.fileUpload.updateMany({
      where: {
        createdAt: {
          lt: ninetyDaysAgo
        },
        status: 'PENDING',
        // Files that were never associated with any record
        weeklyUploadId: null,
        applicationId: null
      },
      data: {
        status: 'EXPIRED',
        markedForDeletion: true,
        markedForDeletionAt: startTime
      }
    });
    cleanupResults.orphanedFiles = orphanedUploads.count;

    // 6. Vacuum database statistics (PostgreSQL specific)
    if (process.env.DATABASE_URL?.includes('postgresql')) {
      try {
        await prisma.$executeRaw`VACUUM ANALYZE;`;
        console.log('Database vacuum analyze completed');
      } catch (error) {
        console.warn('Database vacuum failed:', error);
      }
    }

    recordsProcessed = totalCleaned;

    // Create system health report
    const healthMetrics = await generateSystemHealthMetrics(startTime);
    
    // Store health metrics
    await prisma.systemMetrics.upsert({
      where: {
        metricName: 'weekly_cleanup_report'
      },
      update: {
        metricValue: totalCleaned,
        metadata: {
          ...cleanupResults,
          ...healthMetrics,
          cleanupDate: startTime.toISOString()
        },
        updatedAt: startTime
      },
      create: {
        metricName: 'weekly_cleanup_report',
        metricValue: totalCleaned,
        metadata: {
          ...cleanupResults,
          ...healthMetrics,
          cleanupDate: startTime.toISOString()
        },
        updatedAt: startTime
      }
    });

    return {
      success: true,
      message: `Deep cleanup completed: ${totalCleaned} records cleaned`,
      duration: Date.now() - startTime.getTime(),
      recordsProcessed,
      data: {
        ...cleanupResults,
        healthMetrics,
        totalCleaned
      }
    };

  } catch (error) {
    console.error('Weekly deep cleanup job failed:', error);
    return {
      success: false,
      message: 'Failed to perform deep cleanup',
      error: error instanceof Error ? error.message : 'Unknown error',
      duration: Date.now() - startTime.getTime(),
      recordsProcessed
    };
  }
}

/**
 * Generate system health metrics
 */
async function generateSystemHealthMetrics(timestamp: Date) {
  try {
    const [
      totalUsers,
      activeUsers,
      totalSessions,
      activeSessions,
      pendingJobs,
      failedJobs,
      diskUsage
    ] = await Promise.all([
      prisma.user.count(),
      prisma.user.count({
        where: {
          lastLoginAt: {
            gte: new Date(timestamp.getTime() - 30 * 24 * 60 * 60 * 1000)
          }
        }
      }),
      prisma.timerSession.count(),
      prisma.timerSession.count({
        where: {
          endedAt: null
        }
      }),
      prisma.jobLog.count({
        where: {
          status: 'RUNNING'
        }
      }),
      prisma.jobLog.count({
        where: {
          status: 'FAILED',
          startedAt: {
            gte: new Date(timestamp.getTime() - 24 * 60 * 60 * 1000)
          }
        }
      }),
      calculateDiskUsage()
    ]);

    return {
      totalUsers,
      activeUsers,
      totalSessions,
      activeSessions,
      pendingJobs,
      failedJobs,
      diskUsage,
      healthScore: calculateHealthScore({
        totalUsers,
        activeUsers,
        pendingJobs,
        failedJobs
      })
    };
  } catch (error) {
    console.error('Failed to generate health metrics:', error);
    return {
      error: 'Failed to generate health metrics'
    };
  }
}

/**
 * Calculate estimated disk usage
 */
async function calculateDiskUsage(): Promise<number> {
  try {
    // This is a simplified calculation
    // In production, you might want to use actual database size queries
    const [
      sessionCount,
      logCount,
      uploadCount
    ] = await Promise.all([
      prisma.timerSession.count(),
      prisma.jobLog.count(),
      prisma.fileUpload.count()
    ]);

    // Rough estimation: sessions ~1KB, logs ~2KB, uploads ~5MB average
    const estimatedBytes = (sessionCount * 1024) + (logCount * 2048) + (uploadCount * 5 * 1024 * 1024);
    return Math.round(estimatedBytes / (1024 * 1024)); // Return in MB
  } catch (error) {
    console.error('Failed to calculate disk usage:', error);
    return 0;
  }
}

/**
 * Calculate system health score (0-100)
 */
function calculateHealthScore(metrics: {
  totalUsers: number;
  activeUsers: number;
  pendingJobs: number;
  failedJobs: number;
}): number {
  let score = 100;

  // Deduct points for failed jobs
  score -= Math.min(metrics.failedJobs * 5, 30);

  // Deduct points for too many pending jobs
  if (metrics.pendingJobs > 10) {
    score -= Math.min((metrics.pendingJobs - 10) * 2, 20);
  }

  // Deduct points for low user activity
  const activityRate = metrics.totalUsers > 0 ? (metrics.activeUsers / metrics.totalUsers) : 1;
  if (activityRate < 0.5) {
    score -= (0.5 - activityRate) * 100;
  }

  return Math.max(0, Math.round(score));
}
