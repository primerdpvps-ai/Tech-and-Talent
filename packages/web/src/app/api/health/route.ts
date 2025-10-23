import { NextRequest, NextResponse } from 'next/server';
import { healthCheck } from '@/lib/startup';
import { createApiResponse } from '@tts-pms/infra';

/**
 * Health check endpoint
 * Returns the current health status of the application
 */
export async function GET(request: NextRequest) {
  try {
    const health = await healthCheck();
    
    // Return appropriate HTTP status based on health
    const status = health.status === 'healthy' ? 200 : 
                  health.status === 'degraded' ? 206 : 503;

    return NextResponse.json(
      createApiResponse(health, `System status: ${health.status}`),
      { status }
    );

  } catch (error) {
    console.error('Health check failed:', error);
    
    return NextResponse.json(
      createApiResponse({
        status: 'unhealthy',
        timestamp: new Date().toISOString(),
        error: error instanceof Error ? error.message : 'Unknown error',
        services: {
          database: 'unknown',
          jobs: 'unknown'
        }
      }, 'Health check failed'),
      { status: 503 }
    );
  }
}

/**
 * Detailed health check with more information
 */
export async function POST(request: NextRequest) {
  try {
    const health = await healthCheck();
    
    // Add more detailed information
    const detailedHealth = {
      ...health,
      system: {
        nodeVersion: process.version,
        platform: process.platform,
        uptime: process.uptime(),
        memoryUsage: process.memoryUsage(),
        pid: process.pid
      },
      configuration: {
        nodeEnv: process.env.NODE_ENV,
        jobsEnabled: process.env.ENABLE_JOBS === 'true' || process.env.NODE_ENV === 'production',
        timezone: process.env.TZ || 'UTC',
        databaseUrl: process.env.DATABASE_URL ? 'configured' : 'missing'
      }
    };

    // Get recent job statistics if jobs are enabled
    if (detailedHealth.configuration.jobsEnabled) {
      try {
        const { jobRunner } = await import('@/lib/job-runner');
        const recentJobs = await jobRunner.getJobHistory(undefined, 20);
        
        const jobStats = {
          total: recentJobs.length,
          completed: recentJobs.filter(j => j.status === 'COMPLETED').length,
          failed: recentJobs.filter(j => j.status === 'FAILED').length,
          running: recentJobs.filter(j => j.status === 'RUNNING').length,
          lastRun: recentJobs[0]?.startedAt || null
        };

        detailedHealth.jobs = jobStats;
      } catch (error) {
        detailedHealth.jobs = { error: 'Failed to get job statistics' };
      }
    }

    const status = health.status === 'healthy' ? 200 : 
                  health.status === 'degraded' ? 206 : 503;

    return NextResponse.json(
      createApiResponse(detailedHealth, `Detailed system status: ${health.status}`),
      { status }
    );

  } catch (error) {
    console.error('Detailed health check failed:', error);
    
    return NextResponse.json(
      createApiResponse({
        status: 'unhealthy',
        timestamp: new Date().toISOString(),
        error: error instanceof Error ? error.message : 'Unknown error'
      }, 'Detailed health check failed'),
      { status: 503 }
    );
  }
}
