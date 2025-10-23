import { NextRequest, NextResponse } from 'next/server';
import { jobRunner } from '@/lib/job-runner';
import { hourlyAggregationJob } from '@/jobs/hourly-aggregation';
import { nightlyStreaksJob } from '@/jobs/nightly-streaks';
import { weeklyUploadCheckJob } from '@/jobs/weekly-upload-check';
import { payrollComposeJob } from '@/jobs/payroll-compose';
import { cleanupExpiredDataJob, weeklyDeepCleanupJob } from '@/jobs/cleanup-jobs';
import { createApiResponse, createApiError } from '@tts-pms/infra';
import { z } from 'zod';

const RunJobSchema = z.object({
  jobName: z.enum([
    'hourly-aggregation',
    'nightly-streaks',
    'weekly-upload-check',
    'payroll-compose',
    'cleanup-expired-data',
    'weekly-deep-cleanup'
  ]),
  force: z.boolean().default(false)
});

/**
 * Manually run a specific job (for testing and admin purposes)
 */
export async function POST(request: NextRequest) {
  try {
    // Verify admin access
    const authHeader = request.headers.get('authorization');
    const expectedToken = process.env.ADMIN_API_TOKEN || 'admin-secret-token';
    
    if (!authHeader || authHeader !== `Bearer ${expectedToken}`) {
      return NextResponse.json(
        createApiError('Unauthorized access to job execution', 'UNAUTHORIZED'),
        { status: 401 }
      );
    }

    const body = await request.json();
    const validation = RunJobSchema.safeParse(body);
    
    if (!validation.success) {
      return NextResponse.json(
        createApiError('Invalid job execution request', 'VALIDATION_ERROR'),
        { status: 400 }
      );
    }

    const { jobName, force } = validation.data;

    // Check if job is already running (unless forced)
    if (!force) {
      const recentJobs = await jobRunner.getJobHistory(jobName, 1);
      const lastJob = recentJobs[0];
      
      if (lastJob && lastJob.status === 'RUNNING') {
        return NextResponse.json(
          createApiError(`Job ${jobName} is already running`, 'JOB_RUNNING'),
          { status: 409 }
        );
      }

      // Check if job ran recently (within last 10 minutes)
      if (lastJob && lastJob.startedAt) {
        const tenMinutesAgo = new Date(Date.now() - 10 * 60 * 1000);
        if (new Date(lastJob.startedAt) > tenMinutesAgo) {
          return NextResponse.json(
            createApiError(`Job ${jobName} ran recently. Use force=true to override`, 'JOB_RECENT'),
            { status: 409 }
          );
        }
      }
    }

    // Get the job function
    const jobFunctions = {
      'hourly-aggregation': hourlyAggregationJob,
      'nightly-streaks': nightlyStreaksJob,
      'weekly-upload-check': weeklyUploadCheckJob,
      'payroll-compose': payrollComposeJob,
      'cleanup-expired-data': cleanupExpiredDataJob,
      'weekly-deep-cleanup': weeklyDeepCleanupJob
    };

    const jobFunction = jobFunctions[jobName];
    if (!jobFunction) {
      return NextResponse.json(
        createApiError(`Unknown job: ${jobName}`, 'UNKNOWN_JOB'),
        { status: 400 }
      );
    }

    // Run the job manually
    const startTime = Date.now();
    jobRunner.runJobManually(jobName, jobFunction);

    return NextResponse.json(
      createApiResponse({
        jobName,
        status: 'started',
        startedAt: new Date().toISOString(),
        message: `Job ${jobName} started manually`,
        forced: force
      }, `Manual execution of ${jobName} started`)
    );

  } catch (error) {
    console.error('Failed to run job manually:', error);
    return NextResponse.json(
      createApiError('Failed to execute job', 'EXECUTION_ERROR'),
      { status: 500 }
    );
  }
}

/**
 * Get job execution history
 */
export async function GET(request: NextRequest) {
  try {
    const url = new URL(request.url);
    const jobName = url.searchParams.get('jobName');
    const limit = parseInt(url.searchParams.get('limit') || '50');

    const jobs = await jobRunner.getJobHistory(jobName || undefined, limit);

    // Calculate job statistics
    const stats = {
      total: jobs.length,
      completed: jobs.filter(j => j.status === 'COMPLETED').length,
      failed: jobs.filter(j => j.status === 'FAILED').length,
      running: jobs.filter(j => j.status === 'RUNNING').length
    };

    // Calculate average duration for completed jobs
    const completedJobs = jobs.filter(j => j.status === 'COMPLETED' && j.duration);
    const avgDuration = completedJobs.length > 0 
      ? completedJobs.reduce((sum, job) => sum + (job.duration || 0), 0) / completedJobs.length
      : 0;

    // Get job frequency (jobs per day)
    const jobsLast7Days = jobs.filter(j => {
      const sevenDaysAgo = new Date(Date.now() - 7 * 24 * 60 * 60 * 1000);
      return new Date(j.startedAt) > sevenDaysAgo;
    });

    return NextResponse.json(
      createApiResponse({
        jobs: jobs.map(job => ({
          runId: job.runId,
          jobName: job.jobName,
          status: job.status,
          startedAt: job.startedAt,
          completedAt: job.completedAt,
          duration: job.duration,
          result: job.result,
          description: job.description
        })),
        stats: {
          ...stats,
          avgDuration: Math.round(avgDuration),
          jobsLast7Days: jobsLast7Days.length,
          successRate: stats.total > 0 ? Math.round((stats.completed / stats.total) * 100) : 0
        },
        filter: {
          jobName: jobName || 'all',
          limit
        }
      }, 'Job history retrieved')
    );

  } catch (error) {
    console.error('Failed to get job history:', error);
    return NextResponse.json(
      createApiError('Failed to get job history', 'HISTORY_ERROR'),
      { status: 500 }
    );
  }
}
