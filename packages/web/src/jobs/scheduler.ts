import { jobRunner } from '@/lib/job-runner';
import { hourlyAggregationJob } from './hourly-aggregation';
import { nightlyStreaksJob } from './nightly-streaks';
import { weeklyUploadCheckJob } from './weekly-upload-check';
import { payrollComposeJob } from './payroll-compose';
import { cleanupExpiredDataJob, weeklyDeepCleanupJob } from './cleanup-jobs';

/**
 * Initialize and start all scheduled jobs
 */
export function initializeJobScheduler() {
  console.log('Initializing job scheduler...');

  // 1. Hourly aggregation job - runs every hour at minute 5
  jobRunner.registerJob(
    'hourly-aggregation',
    '5 * * * *', // Every hour at 5 minutes past
    hourlyAggregationJob,
    {
      timezone: 'UTC',
      description: 'Aggregate TimerSession data into DailySummary',
      runOnInit: false
    }
  );

  // 2. Nightly streaks computation - runs at 2:00 AM UTC daily
  jobRunner.registerJob(
    'nightly-streaks',
    '0 2 * * *', // Daily at 2:00 AM UTC
    nightlyStreaksJob,
    {
      timezone: 'UTC',
      description: 'Recompute streaks and daily compliance',
      runOnInit: false
    }
  );

  // 3. Weekly upload check - runs every Sunday at 23:59 PKT (18:59 UTC)
  jobRunner.registerJob(
    'weekly-upload-check',
    '59 18 * * 0', // Every Sunday at 18:59 UTC (23:59 PKT)
    weeklyUploadCheckJob,
    {
      timezone: 'UTC',
      description: 'Mark missing weekly uploads',
      runOnInit: false
    }
  );

  // 4. Payroll composition - runs every Monday at 9:00 AM UTC
  jobRunner.registerJob(
    'payroll-compose',
    '0 9 * * 1', // Every Monday at 9:00 AM UTC
    payrollComposeJob,
    {
      timezone: 'UTC',
      description: 'Compose weekly payroll and notify CEO',
      runOnInit: false
    }
  );

  // 5. Cleanup expired data - runs every 4 hours
  jobRunner.registerJob(
    'cleanup-expired-data',
    '0 */4 * * *', // Every 4 hours
    cleanupExpiredDataJob,
    {
      timezone: 'UTC',
      description: 'Clean up expired OTPs, sessions, and tokens',
      runOnInit: false
    }
  );

  // 6. Weekly deep cleanup - runs every Sunday at 3:00 AM UTC
  jobRunner.registerJob(
    'weekly-deep-cleanup',
    '0 3 * * 0', // Every Sunday at 3:00 AM UTC
    weeklyDeepCleanupJob,
    {
      timezone: 'UTC',
      description: 'Deep cleanup of old data and system maintenance',
      runOnInit: false
    }
  );

  // 7. System health check - runs every 6 hours
  jobRunner.registerJob(
    'system-health-check',
    '0 */6 * * *', // Every 6 hours
    systemHealthCheckJob,
    {
      timezone: 'UTC',
      description: 'Monitor system health and send alerts',
      runOnInit: false
    }
  );

  // 8. Database maintenance - runs daily at 4:00 AM UTC
  jobRunner.registerJob(
    'database-maintenance',
    '0 4 * * *', // Daily at 4:00 AM UTC
    databaseMaintenanceJob,
    {
      timezone: 'UTC',
      description: 'Database optimization and maintenance',
      runOnInit: false
    }
  );

  // Start the job runner
  jobRunner.start();

  console.log('Job scheduler initialized and started');
}

/**
 * System health check job
 */
async function systemHealthCheckJob(context: any) {
  const { startTime } = context;
  
  try {
    const { prisma } = await import('@tts-pms/db');
    
    // Check database connectivity
    await prisma.$queryRaw`SELECT 1`;
    
    // Check for stuck jobs (running for more than 2 hours)
    const stuckJobs = await prisma.jobLog.findMany({
      where: {
        status: 'RUNNING',
        startedAt: {
          lt: new Date(startTime.getTime() - 2 * 60 * 60 * 1000)
        }
      }
    });

    // Check for high failure rate (more than 10 failures in last hour)
    const recentFailures = await prisma.jobLog.count({
      where: {
        status: 'FAILED',
        startedAt: {
          gte: new Date(startTime.getTime() - 60 * 60 * 1000)
        }
      }
    });

    // Check active user sessions
    const activeSessions = await prisma.timerSession.count({
      where: {
        endedAt: null,
        startedAt: {
          gte: new Date(startTime.getTime() - 24 * 60 * 60 * 1000)
        }
      }
    });

    const healthIssues = [];
    
    if (stuckJobs.length > 0) {
      healthIssues.push(`${stuckJobs.length} stuck jobs detected`);
      
      // Mark stuck jobs as failed
      await prisma.jobLog.updateMany({
        where: {
          id: {
            in: stuckJobs.map(job => job.runId)
          }
        },
        data: {
          status: 'FAILED',
          completedAt: startTime,
          result: {
            success: false,
            error: 'Job marked as stuck by health check',
            stuckDuration: 2 * 60 * 60 * 1000
          }
        }
      });
    }

    if (recentFailures > 10) {
      healthIssues.push(`High failure rate: ${recentFailures} failures in last hour`);
    }

    // Send alerts if issues detected
    if (healthIssues.length > 0) {
      const adminUsers = await prisma.user.findMany({
        where: { role: 'ADMIN' }
      });

      for (const admin of adminUsers) {
        await prisma.notification.create({
          data: {
            userId: admin.id,
            type: 'SYSTEM_HEALTH_ALERT',
            title: 'System Health Issues Detected',
            message: `Health check found ${healthIssues.length} issues: ${healthIssues.join(', ')}`,
            data: {
              issues: healthIssues,
              stuckJobs: stuckJobs.length,
              recentFailures,
              activeSessions,
              checkTime: startTime.toISOString()
            },
            priority: 'HIGH'
          }
        });
      }
    }

    return {
      success: true,
      message: healthIssues.length === 0 ? 'System health check passed' : `Found ${healthIssues.length} issues`,
      duration: Date.now() - startTime.getTime(),
      recordsProcessed: stuckJobs.length + (healthIssues.length > 0 ? adminUsers?.length || 0 : 0),
      data: {
        stuckJobs: stuckJobs.length,
        recentFailures,
        activeSessions,
        healthIssues
      }
    };

  } catch (error) {
    return {
      success: false,
      message: 'System health check failed',
      error: error instanceof Error ? error.message : 'Unknown error',
      duration: Date.now() - startTime.getTime(),
      recordsProcessed: 0
    };
  }
}

/**
 * Database maintenance job
 */
async function databaseMaintenanceJob(context: any) {
  const { startTime } = context;
  
  try {
    const { prisma } = await import('@tts-pms/db');
    
    let maintenanceTasks = 0;

    // 1. Update database statistics (PostgreSQL)
    if (process.env.DATABASE_URL?.includes('postgresql')) {
      try {
        await prisma.$executeRaw`ANALYZE;`;
        maintenanceTasks++;
      } catch (error) {
        console.warn('Database ANALYZE failed:', error);
      }
    }

    // 2. Optimize frequently accessed tables
    try {
      // Reindex critical tables if needed
      if (process.env.DATABASE_URL?.includes('postgresql')) {
        await prisma.$executeRaw`REINDEX INDEX CONCURRENTLY IF EXISTS idx_timer_session_user_device;`;
        await prisma.$executeRaw`REINDEX INDEX CONCURRENTLY IF EXISTS idx_daily_summary_user_date;`;
        maintenanceTasks++;
      }
    } catch (error) {
      console.warn('Database reindex failed:', error);
    }

    // 3. Update table statistics for query optimization
    const tableStats = await prisma.$queryRaw`
      SELECT 
        schemaname,
        tablename,
        n_tup_ins + n_tup_upd + n_tup_del as total_changes,
        n_live_tup as live_tuples,
        n_dead_tup as dead_tuples
      FROM pg_stat_user_tables 
      WHERE schemaname = 'public'
      ORDER BY total_changes DESC
      LIMIT 10;
    ` as any[];

    // 4. Store maintenance metrics
    await prisma.systemMetrics.upsert({
      where: {
        metricName: 'database_maintenance_stats'
      },
      update: {
        metricValue: maintenanceTasks,
        metadata: {
          maintenanceTasks,
          tableStats: tableStats.slice(0, 5), // Top 5 most active tables
          maintenanceDate: startTime.toISOString()
        },
        updatedAt: startTime
      },
      create: {
        metricName: 'database_maintenance_stats',
        metricValue: maintenanceTasks,
        metadata: {
          maintenanceTasks,
          tableStats: tableStats.slice(0, 5),
          maintenanceDate: startTime.toISOString()
        },
        updatedAt: startTime
      }
    });

    return {
      success: true,
      message: `Database maintenance completed: ${maintenanceTasks} tasks`,
      duration: Date.now() - startTime.getTime(),
      recordsProcessed: maintenanceTasks,
      data: {
        maintenanceTasks,
        topTables: tableStats.slice(0, 3)
      }
    };

  } catch (error) {
    return {
      success: false,
      message: 'Database maintenance failed',
      error: error instanceof Error ? error.message : 'Unknown error',
      duration: Date.now() - startTime.getTime(),
      recordsProcessed: 0
    };
  }
}

/**
 * Graceful shutdown handler
 */
export async function shutdownJobScheduler() {
  console.log('Shutting down job scheduler...');
  await jobRunner.shutdown();
  console.log('Job scheduler shutdown complete');
}

// Handle process termination
process.on('SIGINT', shutdownJobScheduler);
process.on('SIGTERM', shutdownJobScheduler);
