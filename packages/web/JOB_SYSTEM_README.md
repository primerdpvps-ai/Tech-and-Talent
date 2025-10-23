# TTS PMS Job System Documentation

This document describes the comprehensive job scheduling system implemented for the TTS PMS application using node-cron.

## Overview

The job system provides automated background processing for critical business operations including payroll, compliance tracking, data aggregation, and system maintenance. All jobs are designed to be idempotent and include comprehensive logging.

## Job Schedule

### Hourly Jobs

#### Hourly Aggregation (`hourly-aggregation`)
- **Schedule**: Every hour at 5 minutes past (e.g., 1:05, 2:05, 3:05)
- **Purpose**: Aggregate completed TimerSession data into DailySummary records
- **Idempotency**: Only processes sessions that haven't been aggregated yet
- **Key Features**:
  - Consolidates timer sessions into daily summaries
  - Calculates billable hours and daily compliance
  - Cleans up old aggregated sessions (30+ days)
  - Updates daily minimum requirements (6 hours)

### Daily Jobs

#### Nightly Streaks Computation (`nightly-streaks`)
- **Schedule**: Daily at 2:00 AM UTC
- **Purpose**: Recompute employee streaks and compliance metrics
- **Key Features**:
  - Calculates current and longest streaks
  - Updates compliance percentages
  - Generates milestone notifications (7-day, 30-day streaks)
  - Creates compliance warnings for employees below 80%
  - Updates system-wide statistics

#### Database Maintenance (`database-maintenance`)
- **Schedule**: Daily at 4:00 AM UTC
- **Purpose**: Optimize database performance
- **Key Features**:
  - Updates table statistics (PostgreSQL ANALYZE)
  - Reindexes critical tables
  - Stores maintenance metrics
  - Monitors table activity

### Weekly Jobs

#### Weekly Upload Check (`weekly-upload-check`)
- **Schedule**: Every Sunday at 23:59 PKT (18:59 UTC)
- **Purpose**: Mark missing weekly uploads and apply penalties
- **Key Features**:
  - Checks all employees who worked during the week
  - Marks missing uploads and applies penalties
  - Notifies employees and managers
  - Generates compliance reports
  - Alerts CEO if compliance < 90%

#### Payroll Composition (`payroll-compose`)
- **Schedule**: Every Monday at 9:00 AM UTC
- **Purpose**: Compose weekly payroll and notify CEO for approval
- **Key Features**:
  - Calculates base pay, overtime, weekend premiums
  - Applies bonuses (performance, attendance)
  - Deducts penalties
  - Creates payroll batch for CEO approval
  - Notifies managers with team summaries
  - Prevents duplicate batches (idempotent)

#### Weekly Deep Cleanup (`weekly-deep-cleanup`)
- **Schedule**: Every Sunday at 3:00 AM UTC
- **Purpose**: Perform comprehensive data cleanup
- **Key Features**:
  - Removes old aggregated timer sessions (90+ days)
  - Cleans activity logs (60+ days)
  - Archives audit logs (1+ year)
  - Marks orphaned files for deletion
  - Generates system health reports
  - Database vacuum (PostgreSQL)

### Periodic Jobs

#### Cleanup Expired Data (`cleanup-expired-data`)
- **Schedule**: Every 4 hours
- **Purpose**: Clean up expired temporary data
- **Key Features**:
  - Removes expired OTPs (10+ minutes)
  - Cleans expired sessions (7+ days inactive)
  - Removes expired password reset tokens (1+ hour)
  - Archives old job logs (30+ days)
  - Cleans read notifications (30+ days)
  - Deactivates inactive agent devices (90+ days)

#### System Health Check (`system-health-check`)
- **Schedule**: Every 6 hours
- **Purpose**: Monitor system health and detect issues
- **Key Features**:
  - Detects stuck jobs (running > 2 hours)
  - Monitors failure rates
  - Checks active sessions
  - Sends alerts to administrators
  - Marks stuck jobs as failed

## Job Infrastructure

### JobRunner Class
- Manages job registration and execution
- Provides idempotency checks
- Handles graceful shutdown
- Logs all job executions to database

### Job Logging
All jobs log to the `jobLog` table with:
- Unique run ID
- Start/completion timestamps
- Duration
- Success/failure status
- Detailed results and error messages
- Metadata (PID, Node version, environment)

### Error Handling
- Comprehensive try-catch blocks
- Detailed error logging
- Graceful degradation
- Automatic retry for transient failures
- Health monitoring and alerting

## API Endpoints

### Job Management

#### Initialize Jobs
```bash
POST /api/jobs/init
Authorization: Bearer ADMIN_API_TOKEN

# Response
{
  "success": true,
  "data": {
    "status": "initialized",
    "message": "Job scheduler initialized successfully",
    "timestamp": "2024-03-15T10:00:00.000Z"
  }
}
```

#### Manual Job Execution
```bash
POST /api/jobs/run
Authorization: Bearer ADMIN_API_TOKEN
Content-Type: application/json

{
  "jobName": "hourly-aggregation",
  "force": false
}
```

#### Job History
```bash
GET /api/jobs/run?jobName=hourly-aggregation&limit=50
Authorization: Bearer ADMIN_API_TOKEN
```

#### Job Status
```bash
GET /api/jobs/init
# Returns current scheduler status and recent job statistics
```

### Health Monitoring

#### Basic Health Check
```bash
GET /api/health
# Returns: 200 (healthy), 206 (degraded), 503 (unhealthy)
```

#### Detailed Health Check
```bash
POST /api/health
# Returns detailed system information including job statistics
```

## Configuration

### Environment Variables

```bash
# Job Control
NODE_ENV=production          # Auto-enables jobs in production
ENABLE_JOBS=true            # Force enable jobs in development
TZ=UTC                      # Timezone for job scheduling

# Security
ADMIN_API_TOKEN=your-secret-token  # For job management APIs

# Database
DATABASE_URL=postgresql://...      # Required for job persistence
```

### Timezone Considerations
- All jobs run in UTC by default
- Weekly upload check runs at 23:59 PKT (18:59 UTC)
- Payroll runs Monday 9:00 AM UTC
- Adjust `TZ` environment variable if needed

## Monitoring and Alerts

### Automatic Notifications
- **Streak Milestones**: 7-day and 30-day streaks
- **Compliance Warnings**: Below 80% attendance
- **Missing Uploads**: Weekly upload reminders
- **Payroll Ready**: CEO approval notifications
- **System Health**: Administrator alerts for issues

### Metrics Tracking
- Job execution statistics
- System performance metrics
- Compliance rates
- Health scores
- Database maintenance stats

## Deployment

### Development
```bash
# Enable jobs in development
export ENABLE_JOBS=true
npm run dev
```

### Production
```bash
# Jobs auto-start in production
npm run build
npm start
```

### Docker
```dockerfile
# Jobs will auto-initialize
ENV NODE_ENV=production
ENV TZ=UTC
```

### Serverless Considerations
- Jobs may not work in serverless environments (Vercel, Lambda)
- Consider external cron services (GitHub Actions, external servers)
- Use webhook triggers for serverless deployments

## Troubleshooting

### Common Issues

#### Jobs Not Running
1. Check `ENABLE_JOBS` environment variable
2. Verify database connectivity
3. Check job logs in database
4. Monitor health check endpoint

#### Stuck Jobs
- System health check automatically detects and marks stuck jobs as failed
- Jobs running > 2 hours are considered stuck
- Check system resources and database performance

#### High Failure Rates
- Monitor `/api/health` endpoint
- Check job logs for error patterns
- Verify database connectivity and performance
- Review system resource usage

### Debugging

#### View Job Logs
```sql
SELECT * FROM "jobLog" 
WHERE "jobName" = 'hourly-aggregation' 
ORDER BY "startedAt" DESC 
LIMIT 10;
```

#### Check System Health
```bash
curl -X POST http://localhost:3000/api/health
```

#### Manual Job Execution
```bash
curl -X POST http://localhost:3000/api/jobs/run \
  -H "Authorization: Bearer your-admin-token" \
  -H "Content-Type: application/json" \
  -d '{"jobName": "hourly-aggregation", "force": true}'
```

## Best Practices

### Job Design
- Always implement idempotency
- Use database transactions for consistency
- Include comprehensive error handling
- Log meaningful progress and results
- Design for graceful failure

### Performance
- Process data in batches
- Use database indexes effectively
- Monitor memory usage
- Implement timeouts for long operations

### Monitoring
- Set up alerts for job failures
- Monitor job duration trends
- Track system health metrics
- Review logs regularly

### Security
- Protect job management endpoints
- Use secure tokens for API access
- Log all administrative actions
- Monitor for unusual job patterns

## Future Enhancements

### Planned Features
- Job retry mechanisms with exponential backoff
- Job dependency management
- Dynamic job scheduling based on load
- Enhanced monitoring dashboard
- Job queue management for high-volume processing

### Scalability Considerations
- Horizontal scaling with job distribution
- Redis-based job queues
- Microservice architecture for job processing
- Load balancing for job execution
