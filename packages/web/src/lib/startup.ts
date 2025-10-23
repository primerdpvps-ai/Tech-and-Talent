import { initializeJobScheduler } from '@/jobs/scheduler';

/**
 * Application startup initialization
 * This should be called when the Next.js application starts
 */
export async function initializeApplication() {
  console.log('🚀 Initializing TTS PMS application...');

  try {
    // Only initialize jobs in production or when explicitly enabled
    const shouldInitializeJobs = 
      process.env.NODE_ENV === 'production' || 
      process.env.ENABLE_JOBS === 'true' ||
      process.env.VERCEL_ENV === 'production';

    if (shouldInitializeJobs) {
      console.log('📅 Starting job scheduler...');
      initializeJobScheduler();
      console.log('✅ Job scheduler initialized');
    } else {
      console.log('⏸️  Job scheduler disabled (development mode)');
      console.log('   Set ENABLE_JOBS=true to enable jobs in development');
    }

    // Log application info
    console.log(`🌍 Environment: ${process.env.NODE_ENV || 'development'}`);
    console.log(`🔧 Jobs enabled: ${shouldInitializeJobs ? 'Yes' : 'No'}`);
    console.log(`📍 Timezone: ${process.env.TZ || 'UTC'}`);
    
    // Log database connection status
    try {
      const { prisma } = await import('@tts-pms/db');
      await prisma.$queryRaw`SELECT 1`;
      console.log('✅ Database connection established');
    } catch (error) {
      console.error('❌ Database connection failed:', error);
    }

    console.log('🎉 Application initialization complete');

  } catch (error) {
    console.error('❌ Application initialization failed:', error);
    throw error;
  }
}

/**
 * Health check function
 */
export async function healthCheck() {
  const health = {
    status: 'healthy',
    timestamp: new Date().toISOString(),
    services: {
      database: 'unknown',
      jobs: 'unknown'
    },
    environment: process.env.NODE_ENV || 'development'
  };

  try {
    // Check database
    const { prisma } = await import('@tts-pms/db');
    await prisma.$queryRaw`SELECT 1`;
    health.services.database = 'healthy';
  } catch (error) {
    health.services.database = 'unhealthy';
    health.status = 'degraded';
  }

  try {
    // Check jobs (if enabled)
    const shouldHaveJobs = 
      process.env.NODE_ENV === 'production' || 
      process.env.ENABLE_JOBS === 'true';

    if (shouldHaveJobs) {
      const { jobRunner } = await import('@/lib/job-runner');
      const recentJobs = await jobRunner.getJobHistory(undefined, 5);
      
      // Check if any jobs have run recently (within last 2 hours)
      const twoHoursAgo = new Date(Date.now() - 2 * 60 * 60 * 1000);
      const recentJobRuns = recentJobs.filter(job => 
        new Date(job.startedAt) > twoHoursAgo
      );

      health.services.jobs = recentJobRuns.length > 0 ? 'healthy' : 'stale';
      
      if (health.services.jobs === 'stale') {
        health.status = 'degraded';
      }
    } else {
      health.services.jobs = 'disabled';
    }
  } catch (error) {
    health.services.jobs = 'unhealthy';
    health.status = 'degraded';
  }

  return health;
}
