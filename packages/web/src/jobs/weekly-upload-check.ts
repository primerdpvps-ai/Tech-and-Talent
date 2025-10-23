import { prisma } from '@tts-pms/db';
import { JobResult, JobContext } from '@/lib/job-runner';

/**
 * Weekly job to mark missing weekly uploads
 * Runs every Sunday at 23:59 PKT (18:59 UTC) to check for missing uploads
 */
export async function weeklyUploadCheckJob(context: JobContext): Promise<JobResult> {
  const { jobName, startTime } = context;
  let recordsProcessed = 0;
  let uploadsMarked = 0;
  let notificationsCreated = 0;

  try {
    // Calculate the week ending today (Sunday)
    const weekEndDate = new Date(startTime);
    weekEndDate.setHours(23, 59, 59, 999);
    
    // Calculate week start (Monday)
    const weekStartDate = new Date(weekEndDate);
    weekStartDate.setDate(weekStartDate.getDate() - 6);
    weekStartDate.setHours(0, 0, 0, 0);

    console.log(`Checking weekly uploads for week: ${weekStartDate.toISOString()} to ${weekEndDate.toISOString()}`);

    // Get all active employees who worked during this week
    const activeEmployees = await prisma.user.findMany({
      where: {
        role: {
          in: ['EMPLOYEE', 'NEW_EMPLOYEE', 'MANAGER']
        },
        employment: {
          isNot: null
        },
        // Only check employees who have daily summaries for this week
        dailySummaries: {
          some: {
            date: {
              gte: weekStartDate,
              lte: weekEndDate
            },
            billableSeconds: {
              gt: 0
            }
          }
        }
      },
      include: {
        employment: true,
        dailySummaries: {
          where: {
            date: {
              gte: weekStartDate,
              lte: weekEndDate
            }
          }
        },
        weeklyUploads: {
          where: {
            weekOf: weekStartDate
          }
        }
      }
    });

    console.log(`Found ${activeEmployees.length} active employees to check`);

    for (const employee of activeEmployees) {
      if (!employee.employment) continue;

      const { dailySummaries, weeklyUploads } = employee;
      
      // Calculate total billable hours for the week
      const totalBillableSeconds = dailySummaries.reduce((sum, summary) => sum + summary.billableSeconds, 0);
      const totalBillableHours = totalBillableSeconds / 3600;

      // Skip employees who didn't work enough hours (less than 6 hours total)
      if (totalBillableHours < 6) {
        continue;
      }

      recordsProcessed++;

      // Check if employee has uploaded files for this week
      const hasUploads = weeklyUploads.length > 0 && weeklyUploads.some(upload => upload.fileCount > 0);

      if (!hasUploads) {
        // Mark as missing upload
        const existingWeeklyRecord = await prisma.weeklyUpload.findUnique({
          where: {
            userId_weekOf: {
              userId: employee.id,
              weekOf: weekStartDate
            }
          }
        });

        if (existingWeeklyRecord) {
          // Update existing record
          await prisma.weeklyUpload.update({
            where: {
              userId_weekOf: {
                userId: employee.id,
                weekOf: weekStartDate
              }
            },
            data: {
              uploadsMissing: true,
              missedUploadMarkedAt: startTime,
              totalBillableHours: Math.round(totalBillableHours * 100) / 100
            }
          });
        } else {
          // Create new record
          await prisma.weeklyUpload.create({
            data: {
              userId: employee.id,
              weekOf: weekStartDate,
              fileCount: 0,
              uploadsMissing: true,
              missedUploadMarkedAt: startTime,
              totalBillableHours: Math.round(totalBillableHours * 100) / 100
            }
          });
        }

        uploadsMarked++;

        // Create notification for employee
        await prisma.notification.create({
          data: {
            userId: employee.id,
            type: 'MISSING_WEEKLY_UPLOAD',
            title: 'Weekly Upload Missing',
            message: `You have not uploaded your weekly files for the week ending ${weekEndDate.toLocaleDateString()}. Please upload your work files as soon as possible.`,
            data: {
              weekOf: weekStartDate.toISOString(),
              weekEndDate: weekEndDate.toISOString(),
              totalBillableHours: Math.round(totalBillableHours * 100) / 100,
              daysWorked: dailySummaries.filter(s => s.billableSeconds > 0).length
            },
            priority: 'HIGH'
          }
        });

        // Create notification for manager if employee has one
        if (employee.employment.managerId) {
          await prisma.notification.create({
            data: {
              userId: employee.employment.managerId,
              type: 'EMPLOYEE_MISSING_UPLOAD',
              title: 'Employee Missing Weekly Upload',
              message: `${employee.fullName} has not uploaded weekly files for the week ending ${weekEndDate.toLocaleDateString()}.`,
              data: {
                employeeId: employee.id,
                employeeName: employee.fullName,
                weekOf: weekStartDate.toISOString(),
                weekEndDate: weekEndDate.toISOString(),
                totalBillableHours: Math.round(totalBillableHours * 100) / 100
              },
              priority: 'MEDIUM'
            }
          });
        }

        notificationsCreated += employee.employment.managerId ? 2 : 1;

        // Apply penalty if configured
        const penaltyAmount = await getPenaltyAmount('MISSING_WEEKLY_UPLOAD');
        if (penaltyAmount > 0) {
          await prisma.penalty.create({
            data: {
              userId: employee.id,
              type: 'MISSING_WEEKLY_UPLOAD',
              amount: penaltyAmount,
              description: `Missing weekly upload for week ending ${weekEndDate.toLocaleDateString()}`,
              appliedAt: startTime,
              appliedBy: 'SYSTEM',
              weekOf: weekStartDate
            }
          });
        }
      } else {
        // Employee has uploads, ensure the record reflects this
        await prisma.weeklyUpload.updateMany({
          where: {
            userId: employee.id,
            weekOf: weekStartDate
          },
          data: {
            uploadsMissing: false,
            totalBillableHours: Math.round(totalBillableHours * 100) / 100
          }
        });
      }
    }

    // Generate weekly upload compliance report
    const complianceStats = await prisma.weeklyUpload.aggregate({
      where: {
        weekOf: weekStartDate
      },
      _count: {
        userId: true
      }
    });

    const missingUploadsCount = await prisma.weeklyUpload.count({
      where: {
        weekOf: weekStartDate,
        uploadsMissing: true
      }
    });

    const complianceRate = complianceStats._count.userId > 0 
      ? ((complianceStats._count.userId - missingUploadsCount) / complianceStats._count.userId) * 100 
      : 100;

    // Store compliance metrics
    await prisma.systemMetrics.upsert({
      where: {
        metricName: `weekly_upload_compliance_${weekStartDate.toISOString().split('T')[0]}`
      },
      update: {
        metricValue: complianceRate,
        metadata: {
          weekOf: weekStartDate.toISOString(),
          totalEmployees: complianceStats._count.userId,
          missingUploads: missingUploadsCount,
          complianceRate,
          checkedAt: startTime.toISOString()
        },
        updatedAt: startTime
      },
      create: {
        metricName: `weekly_upload_compliance_${weekStartDate.toISOString().split('T')[0]}`,
        metricValue: complianceRate,
        metadata: {
          weekOf: weekStartDate.toISOString(),
          totalEmployees: complianceStats._count.userId,
          missingUploads: missingUploadsCount,
          complianceRate,
          checkedAt: startTime.toISOString()
        },
        updatedAt: startTime
      }
    });

    // Notify CEO if compliance is below threshold (e.g., 90%)
    if (complianceRate < 90 && complianceStats._count.userId > 0) {
      const ceoUser = await prisma.user.findFirst({
        where: { role: 'CEO' }
      });

      if (ceoUser) {
        await prisma.notification.create({
          data: {
            userId: ceoUser.id,
            type: 'LOW_UPLOAD_COMPLIANCE',
            title: 'Weekly Upload Compliance Alert',
            message: `Weekly upload compliance is ${complianceRate.toFixed(1)}% for week ending ${weekEndDate.toLocaleDateString()}. ${missingUploadsCount} out of ${complianceStats._count.userId} employees are missing uploads.`,
            data: {
              weekOf: weekStartDate.toISOString(),
              complianceRate,
              totalEmployees: complianceStats._count.userId,
              missingUploads: missingUploadsCount
            },
            priority: 'HIGH'
          }
        });
        notificationsCreated++;
      }
    }

    return {
      success: true,
      message: `Marked ${uploadsMarked} missing weekly uploads and created ${notificationsCreated} notifications`,
      duration: Date.now() - startTime.getTime(),
      recordsProcessed,
      data: {
        weekOf: weekStartDate.toISOString(),
        employeesChecked: recordsProcessed,
        missingUploads: uploadsMarked,
        notificationsCreated,
        complianceRate: Math.round(complianceRate * 100) / 100
      }
    };

  } catch (error) {
    console.error('Weekly upload check job failed:', error);
    return {
      success: false,
      message: 'Failed to check weekly uploads',
      error: error instanceof Error ? error.message : 'Unknown error',
      duration: Date.now() - startTime.getTime(),
      recordsProcessed
    };
  }
}

/**
 * Get penalty amount for missing weekly upload from system settings
 */
async function getPenaltyAmount(penaltyType: string): Promise<number> {
  try {
    const setting = await prisma.systemSettings.findFirst({
      where: {
        key: `penalty_${penaltyType.toLowerCase()}`
      }
    });
    
    return setting ? parseFloat(setting.value) : 0;
  } catch (error) {
    console.error('Failed to get penalty amount:', error);
    return 0;
  }
}
