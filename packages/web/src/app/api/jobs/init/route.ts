import { NextRequest, NextResponse } from 'next/server';
import { initializeJobScheduler } from '@/jobs/scheduler';
import { jobRunner } from '@/lib/job-runner';
import { createApiResponse, createApiError } from '@tts-pms/infra';

// Global flag to prevent multiple initializations
let isJobSchedulerInitialized = false;

/**
 * Initialize the job scheduler
 * This endpoint should be called once when the application starts
 */
export async function POST(request: NextRequest) {
  try {
    // Check if already initialized
    if (isJobSchedulerInitialized) {
      return NextResponse.json(
        createApiResponse({
          status: 'already_initialized',
          message: 'Job scheduler is already running'
        }, 'Job scheduler already initialized')
      );
    }

    // Verify admin access (in production, this should be protected)
    const authHeader = request.headers.get('authorization');
    const expectedToken = process.env.ADMIN_API_TOKEN || 'admin-secret-token';
    
    if (!authHeader || authHeader !== `Bearer ${expectedToken}`) {
      return NextResponse.json(
        createApiError('Unauthorized access to job initialization', 'UNAUTHORIZED'),
        { status: 401 }
      );
    }

    // Initialize the job scheduler
    initializeJobScheduler();
    isJobSchedulerInitialized = true;

    return NextResponse.json(
      createApiResponse({
        status: 'initialized',
        message: 'Job scheduler initialized successfully',
        timestamp: new Date().toISOString()
      }, 'Job scheduler initialized')
    );

  } catch (error) {
    console.error('Failed to initialize job scheduler:', error);
    return NextResponse.json(
      createApiError('Failed to initialize job scheduler', 'INITIALIZATION_ERROR'),
      { status: 500 }
    );
  }
}

/**
 * Get job scheduler status
 */
export async function GET(request: NextRequest) {
  try {
    // Get recent job history
    const recentJobs = await jobRunner.getJobHistory(undefined, 20);
    
    // Group jobs by status
    const jobStats = recentJobs.reduce((acc, job) => {
      acc[job.status] = (acc[job.status] || 0) + 1;
      return acc;
    }, {} as Record<string, number>);

    // Get running jobs
    const runningJobs = recentJobs.filter(job => job.status === 'RUNNING');

    return NextResponse.json(
      createApiResponse({
        isInitialized: isJobSchedulerInitialized,
        stats: jobStats,
        runningJobs: runningJobs.length,
        recentJobs: recentJobs.slice(0, 10),
        lastJobRun: recentJobs[0]?.startedAt || null,
        systemTime: new Date().toISOString()
      }, 'Job scheduler status retrieved')
    );

  } catch (error) {
    console.error('Failed to get job scheduler status:', error);
    return NextResponse.json(
      createApiError('Failed to get job scheduler status', 'STATUS_ERROR'),
      { status: 500 }
    );
  }
}
