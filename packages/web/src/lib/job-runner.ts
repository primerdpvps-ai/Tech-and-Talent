import cron from 'node-cron';
import { prisma } from '@tts-pms/db';

export interface JobResult {
  success: boolean;
  message: string;
  data?: any;
  error?: string;
  duration: number;
  recordsProcessed?: number;
}

export interface JobContext {
  jobName: string;
  startTime: Date;
  runId: string;
}

export class JobRunner {
  private jobs: Map<string, cron.ScheduledTask> = new Map();
  private isShuttingDown = false;

  constructor() {
    // Handle graceful shutdown
    process.on('SIGINT', () => this.shutdown());
    process.on('SIGTERM', () => this.shutdown());
  }

  /**
   * Register a scheduled job
   */
  registerJob(
    name: string,
    schedule: string,
    jobFunction: (context: JobContext) => Promise<JobResult>,
    options: {
      timezone?: string;
      runOnInit?: boolean;
      description?: string;
    } = {}
  ) {
    if (this.jobs.has(name)) {
      console.warn(`Job ${name} is already registered. Skipping.`);
      return;
    }

    const task = cron.schedule(
      schedule,
      async () => {
        if (this.isShuttingDown) return;
        await this.executeJob(name, jobFunction, options.description);
      },
      {
        scheduled: false,
        timezone: options.timezone || 'UTC'
      }
    );

    this.jobs.set(name, task);
    console.log(`Registered job: ${name} with schedule: ${schedule}`);

    // Run immediately if requested
    if (options.runOnInit) {
      setImmediate(() => this.executeJob(name, jobFunction, options.description));
    }
  }

  /**
   * Start all registered jobs
   */
  start() {
    console.log('Starting job runner...');
    this.jobs.forEach((task, name) => {
      task.start();
      console.log(`Started job: ${name}`);
    });
    console.log(`Job runner started with ${this.jobs.size} jobs`);
  }

  /**
   * Stop all jobs gracefully
   */
  async shutdown() {
    if (this.isShuttingDown) return;
    
    console.log('Shutting down job runner...');
    this.isShuttingDown = true;

    this.jobs.forEach((task, name) => {
      task.stop();
      console.log(`Stopped job: ${name}`);
    });

    console.log('Job runner shutdown complete');
  }

  /**
   * Execute a job with proper logging and error handling
   */
  private async executeJob(
    jobName: string,
    jobFunction: (context: JobContext) => Promise<JobResult>,
    description?: string
  ) {
    const runId = `${jobName}_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
    const startTime = new Date();
    const context: JobContext = { jobName, startTime, runId };

    console.log(`[${runId}] Starting job: ${jobName}`);

    try {
      // Check if job is already running (idempotency check)
      const existingRun = await prisma.jobLog.findFirst({
        where: {
          jobName,
          status: 'RUNNING',
          startedAt: {
            gte: new Date(Date.now() - 60 * 60 * 1000) // Within last hour
          }
        }
      });

      if (existingRun) {
        console.log(`[${runId}] Job ${jobName} is already running. Skipping.`);
        return;
      }

      // Create job log entry
      await prisma.jobLog.create({
        data: {
          runId,
          jobName,
          description: description || jobName,
          status: 'RUNNING',
          startedAt: startTime,
          metadata: {
            pid: process.pid,
            nodeVersion: process.version,
            environment: process.env.NODE_ENV || 'development'
          }
        }
      });

      // Execute the job
      const result = await jobFunction(context);
      const endTime = new Date();
      const duration = endTime.getTime() - startTime.getTime();

      // Update job log with results
      await prisma.jobLog.update({
        where: { runId },
        data: {
          status: result.success ? 'COMPLETED' : 'FAILED',
          completedAt: endTime,
          duration,
          result: {
            success: result.success,
            message: result.message,
            data: result.data,
            error: result.error,
            recordsProcessed: result.recordsProcessed
          }
        }
      });

      if (result.success) {
        console.log(`[${runId}] Job ${jobName} completed successfully in ${duration}ms: ${result.message}`);
        if (result.recordsProcessed) {
          console.log(`[${runId}] Processed ${result.recordsProcessed} records`);
        }
      } else {
        console.error(`[${runId}] Job ${jobName} failed after ${duration}ms: ${result.error || result.message}`);
      }

    } catch (error) {
      const endTime = new Date();
      const duration = endTime.getTime() - startTime.getTime();
      const errorMessage = error instanceof Error ? error.message : 'Unknown error';

      console.error(`[${runId}] Job ${jobName} crashed after ${duration}ms:`, error);

      // Update job log with error
      try {
        await prisma.jobLog.update({
          where: { runId },
          data: {
            status: 'FAILED',
            completedAt: endTime,
            duration,
            result: {
              success: false,
              error: errorMessage,
              stack: error instanceof Error ? error.stack : undefined
            }
          }
        });
      } catch (logError) {
        console.error(`[${runId}] Failed to update job log:`, logError);
      }
    }
  }

  /**
   * Get job execution history
   */
  async getJobHistory(jobName?: string, limit = 50) {
    return await prisma.jobLog.findMany({
      where: jobName ? { jobName } : undefined,
      orderBy: { startedAt: 'desc' },
      take: limit
    });
  }

  /**
   * Run a job manually (for testing)
   */
  async runJobManually(
    jobName: string,
    jobFunction: (context: JobContext) => Promise<JobResult>
  ) {
    console.log(`Manually executing job: ${jobName}`);
    await this.executeJob(jobName, jobFunction, `Manual execution of ${jobName}`);
  }
}

// Singleton instance
export const jobRunner = new JobRunner();
