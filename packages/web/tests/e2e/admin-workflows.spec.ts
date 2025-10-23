import { test, expect, Page } from '@playwright/test';

// Helper functions
async function loginAsAdmin(page: Page) {
  await page.goto('/auth/signin');
  await page.fill('input[name="email"]', 'ceo@tts-pms.com');
  await page.fill('input[name="password"]', 'admin123');
  await page.click('button[type="submit"]');
  await page.waitForURL('/dashboard');
}

async function loginAsManager(page: Page) {
  await page.goto('/auth/signin');
  await page.fill('input[name="email"]', 'manager@tts-pms.com');
  await page.fill('input[name="password"]', 'manager123');
  await page.click('button[type="submit"]');
  await page.waitForURL('/dashboard');
}

async function loginAsEmployee(page: Page, email = 'employee1@tts-pms.com') {
  await page.goto('/auth/signin');
  await page.fill('input[name="email"]', email);
  await page.fill('input[name="password"]', 'employee123');
  await page.click('button[type="submit"]');
  await page.waitForURL('/dashboard');
}

test.describe('Admin Workflows', () => {
  test('Complete Payroll Processing Workflow', async ({ page }) => {
    await loginAsAdmin(page);

    // Navigate to payroll management
    await page.click('text=Admin');
    await page.click('text=Payroll');
    await page.waitForURL('/admin/payroll');

    // Create new payroll batch
    await page.click('button[data-testid="create-payroll-batch"]');
    await expect(page.locator('h2')).toContainText('Create Payroll Batch');

    // Select week period
    const weekStart = new Date();
    weekStart.setDate(weekStart.getDate() - 7); // Last week
    const weekStartStr = weekStart.toISOString().split('T')[0];
    
    await page.fill('input[name="weekStart"]', weekStartStr);

    // Review eligible employees
    await page.click('button[data-testid="load-employees"]');
    await expect(page.locator('[data-testid="eligible-employees"]')).toBeVisible();

    // Verify employee calculations
    const employeeRows = page.locator('[data-testid="employee-payroll-row"]');
    const firstEmployee = employeeRows.first();
    
    await expect(firstEmployee.locator('[data-testid="hours-worked"]')).toBeVisible();
    await expect(firstEmployee.locator('[data-testid="base-amount"]')).toBeVisible();
    await expect(firstEmployee.locator('[data-testid="net-amount"]')).toBeVisible();

    // Apply manual adjustments if needed
    await firstEmployee.locator('button[data-testid="adjust-btn"]').click();
    await page.fill('input[name="bonusAdjustment"]', '100');
    await page.fill('textarea[name="adjustmentReason"]', 'Exceptional performance bonus');
    await page.click('button[data-testid="save-adjustment"]');

    // Generate batch
    await page.click('button[data-testid="generate-batch"]');
    await expect(page.locator('text=Payroll batch generated successfully')).toBeVisible();

    // Review batch summary
    await expect(page.locator('[data-testid="batch-summary"]')).toBeVisible();
    await expect(page.locator('[data-testid="total-employees"]')).toContainText(/\d+ employees/);
    await expect(page.locator('[data-testid="total-amount"]')).toContainText(/\$[\d,]+\.\d{2}/);

    // Approve batch
    await page.click('button[data-testid="approve-batch"]');
    
    // Confirm approval
    await expect(page.locator('[data-testid="approval-modal"]')).toBeVisible();
    await page.fill('textarea[name="approvalNotes"]', 'Batch reviewed and approved for payment');
    await page.click('button[data-testid="confirm-approval"]');

    // Verify batch status
    await expect(page.locator('[data-testid="batch-status"]')).toContainText('APPROVED');
    await expect(page.locator('text=Batch approved and ready for payment')).toBeVisible();
  });

  test('Employee Management and Role Changes', async ({ page }) => {
    await loginAsAdmin(page);

    // Navigate to employee management
    await page.click('text=Admin');
    await page.click('text=Employees');
    await page.waitForURL('/admin/employees');

    // Search for specific employee
    await page.fill('input[data-testid="employee-search"]', 'employee1@tts-pms.com');
    await page.keyboard.press('Enter');

    const employeeRow = page.locator('[data-testid="employee-row"]').first();
    await expect(employeeRow).toBeVisible();

    // View employee details
    await employeeRow.locator('button[data-testid="view-details"]').click();
    await expect(page.locator('h2')).toContainText('Employee Details');

    // Verify employee information
    await expect(page.locator('[data-testid="employee-email"]')).toContainText('employee1@tts-pms.com');
    await expect(page.locator('[data-testid="employee-role"]')).toContainText('EMPLOYEE');
    await expect(page.locator('[data-testid="employment-status"]')).toContainText('Active');

    // Change employee role
    await page.click('button[data-testid="change-role"]');
    await page.selectOption('select[name="newRole"]', 'MANAGER');
    await page.fill('textarea[name="roleChangeReason"]', 'Promotion due to excellent performance and leadership qualities');
    await page.click('button[data-testid="confirm-role-change"]');

    // Verify role change
    await expect(page.locator('text=Role changed successfully')).toBeVisible();
    await expect(page.locator('[data-testid="employee-role"]')).toContainText('MANAGER');

    // Apply penalty
    await page.click('button[data-testid="apply-penalty"]');
    await page.selectOption('select[name="penaltyType"]', 'LATE_ARRIVAL');
    await page.fill('input[name="penaltyAmount"]', '25');
    await page.fill('textarea[name="penaltyReason"]', 'Late arrival on 2024-01-15 - 30 minutes late');
    await page.click('button[data-testid="confirm-penalty"]');

    // Verify penalty applied
    await expect(page.locator('text=Penalty applied successfully')).toBeVisible();
    await expect(page.locator('[data-testid="penalty-history"]')).toContainText('LATE_ARRIVAL');

    // Award bonus
    await page.click('button[data-testid="award-bonus"]');
    await page.selectOption('select[name="bonusType"]', 'PERFORMANCE');
    await page.fill('input[name="bonusAmount"]', '200');
    await page.fill('textarea[name="bonusReason"]', 'Exceptional project completion and client satisfaction');
    await page.click('button[data-testid="confirm-bonus"]');

    // Verify bonus awarded
    await expect(page.locator('text=Bonus awarded successfully')).toBeVisible();
    await expect(page.locator('[data-testid="bonus-history"]')).toContainText('PERFORMANCE');
  });

  test('Leave Request Management', async ({ page }) => {
    await loginAsManager(page);

    // Navigate to leave management
    await page.click('text=Team');
    await page.click('text=Leave Requests');
    await page.waitForURL('/manager/leaves');

    // View pending leave requests
    await expect(page.locator('h1')).toContainText('Leave Requests');
    
    const pendingRequest = page.locator('[data-testid="leave-request"]').filter({ hasText: 'PENDING' }).first();
    if (await pendingRequest.isVisible()) {
      // Review leave request
      await pendingRequest.locator('button[data-testid="review-btn"]').click();
      
      await expect(page.locator('h2')).toContainText('Review Leave Request');
      await expect(page.locator('[data-testid="employee-name"]')).toBeVisible();
      await expect(page.locator('[data-testid="leave-dates"]')).toBeVisible();
      await expect(page.locator('[data-testid="leave-reason"]')).toBeVisible();

      // Check for policy violations
      const policyWarnings = page.locator('[data-testid="policy-warnings"]');
      if (await policyWarnings.isVisible()) {
        await expect(policyWarnings).toContainText('Policy Violation');
      }

      // Approve with conditions
      await page.selectOption('select[name="decision"]', 'APPROVED');
      await page.fill('textarea[name="managerNotes"]', 'Approved with reminder about advance notice policy');
      
      // Apply penalty if policy violation
      if (await page.locator('input[name="applyPenalty"]').isVisible()) {
        await page.check('input[name="applyPenalty"]');
        await page.fill('input[name="penaltyAmount"]', '15');
      }

      await page.click('button[data-testid="submit-decision"]');
      
      // Verify decision recorded
      await expect(page.locator('text=Leave request decision recorded')).toBeVisible();
    }

    // Create leave policy violation report
    await page.click('button[data-testid="generate-report"]');
    await page.selectOption('select[name="reportType"]', 'POLICY_VIOLATIONS');
    await page.fill('input[name="dateFrom"]', '2024-01-01');
    await page.fill('input[name="dateTo"]', '2024-01-31');
    await page.click('button[data-testid="generate"]');

    // Verify report generated
    await expect(page.locator('[data-testid="report-results"]')).toBeVisible();
    await expect(page.locator('[data-testid="violation-count"]')).toBeVisible();
  });

  test('System Settings Management', async ({ page }) => {
    await loginAsAdmin(page);

    // Navigate to system settings
    await page.click('text=Admin');
    await page.click('text=Settings');
    await page.waitForURL('/admin/settings');

    // Update payroll rates
    await page.click('tab[data-testid="payroll-rates"]');
    await page.fill('input[name="baseHourlyRate"]', '16');
    await page.fill('input[name="managerHourlyRate"]', '26');
    await page.fill('input[name="weekendMultiplier"]', '1.6');
    await page.fill('input[name="overtimeMultiplier"]', '1.6');

    // Update bonus amounts
    await page.fill('input[name="performanceBonus90"]', '110');
    await page.fill('input[name="performanceBonus95"]', '160');
    await page.fill('input[name="perfectAttendanceWeekly"]', '55');
    await page.fill('input[name="referralBonus"]', '550');

    // Save payroll settings
    await page.click('button[data-testid="save-payroll-settings"]');
    await expect(page.locator('text=Payroll settings updated successfully')).toBeVisible();

    // Update operational windows
    await page.click('tab[data-testid="operational-windows"]');
    await page.fill('input[name="standardWindowStart"]', '11:00');
    await page.fill('input[name="standardWindowEnd"]', '02:00');
    await page.fill('input[name="specialWindowStart"]', '02:00');
    await page.fill('input[name="specialWindowEnd"]', '06:00');
    await page.fill('input[name="specialAccessDays"]', '12');

    // Save window settings
    await page.click('button[data-testid="save-window-settings"]');
    await expect(page.locator('text=Operational window settings updated')).toBeVisible();

    // Update penalty amounts
    await page.click('tab[data-testid="penalties"]');
    await page.fill('input[name="lateArrival15"]', '6');
    await page.fill('input[name="lateArrival30"]', '12');
    await page.fill('input[name="lateArrival60"]', '30');
    await page.fill('input[name="unexcusedAbsence"]', '60');
    await page.fill('input[name="weekendLeave"]', '18');
    await page.fill('input[name="insufficientNotice"]', '30');
    await page.fill('input[name="missingWeeklyUpload"]', '35');

    // Save penalty settings
    await page.click('button[data-testid="save-penalty-settings"]');
    await expect(page.locator('text=Penalty settings updated successfully')).toBeVisible();

    // Verify settings are applied
    await page.reload();
    await expect(page.locator('input[name="baseHourlyRate"]')).toHaveValue('16');
    await expect(page.locator('input[name="lateArrival15"]')).toHaveValue('6');
  });

  test('Application Review Workflow', async ({ page }) => {
    await loginAsManager(page);

    // Navigate to applications
    await page.click('text=Admin');
    await page.click('text=Applications');
    await page.waitForURL('/admin/applications');

    // Filter pending applications
    await page.selectOption('select[data-testid="status-filter"]', 'UNDER_REVIEW');
    await page.click('button[data-testid="apply-filter"]');

    const pendingApp = page.locator('[data-testid="application-row"]').first();
    if (await pendingApp.isVisible()) {
      // Review application
      await pendingApp.locator('button[data-testid="review-btn"]').click();
      
      await expect(page.locator('h2')).toContainText('Application Review');
      
      // Review documents
      await expect(page.locator('[data-testid="resume-preview"]')).toBeVisible();
      await expect(page.locator('[data-testid="cnic-preview"]')).toBeVisible();
      await expect(page.locator('[data-testid="utility-preview"]')).toBeVisible();
      await expect(page.locator('[data-testid="selfie-preview"]')).toBeVisible();

      // Verify document authenticity
      await page.click('button[data-testid="verify-documents"]');
      await expect(page.locator('[data-testid="verification-results"]')).toBeVisible();

      // Make decision
      await page.selectOption('select[name="decision"]', 'APPROVED');
      await page.fill('textarea[name="reviewNotes"]', 'All documents verified. Candidate meets requirements. Approved for employment.');
      
      // Set employment details
      await page.fill('input[name="startDate"]', '2024-02-15');
      await page.fill('input[name="rdpHost"]', 'rdp-server-02.tts-pms.com');
      await page.fill('input[name="rdpUsername"]', 'newemployee02');
      await page.selectOption('select[name="assignedManager"]', 'manager@tts-pms.com');

      // Submit review
      await page.click('button[data-testid="submit-review"]');
      
      // Verify approval
      await expect(page.locator('text=Application approved successfully')).toBeVisible();
      
      // Verify notification sent
      await expect(page.locator('text=Approval notification sent to candidate')).toBeVisible();
    }

    // Reject an application
    const anotherApp = page.locator('[data-testid="application-row"]').nth(1);
    if (await anotherApp.isVisible()) {
      await anotherApp.locator('button[data-testid="review-btn"]').click();
      
      await page.selectOption('select[name="decision"]', 'REJECTED');
      await page.selectOption('select[name="rejectionReason"]', 'INSUFFICIENT_QUALIFICATIONS');
      await page.fill('textarea[name="reviewNotes"]', 'Experience does not meet minimum requirements for the position.');
      
      await page.click('button[data-testid="submit-review"]');
      
      // Verify rejection
      await expect(page.locator('text=Application rejected')).toBeVisible();
    }
  });

  test('Bulk Operations', async ({ page }) => {
    await loginAsAdmin(page);

    // Navigate to employees
    await page.click('text=Admin');
    await page.click('text=Employees');
    await page.waitForURL('/admin/employees');

    // Select multiple employees
    await page.check('[data-testid="employee-row"]:nth-child(1) input[type="checkbox"]');
    await page.check('[data-testid="employee-row"]:nth-child(2) input[type="checkbox"]');
    await page.check('[data-testid="employee-row"]:nth-child(3) input[type="checkbox"]');

    // Verify bulk actions enabled
    await expect(page.locator('[data-testid="bulk-actions"]')).toBeVisible();

    // Apply bulk bonus
    await page.click('button[data-testid="bulk-bonus"]');
    await page.selectOption('select[name="bonusType"]', 'HOLIDAY_BONUS');
    await page.fill('input[name="bonusAmount"]', '100');
    await page.fill('textarea[name="bonusReason"]', 'Holiday bonus for all employees');
    await page.click('button[data-testid="confirm-bulk-bonus"]');

    // Verify bulk operation
    await expect(page.locator('text=Bulk bonus applied to 3 employees')).toBeVisible();

    // Export employee data
    await page.click('button[data-testid="export-employees"]');
    await page.selectOption('select[name="exportFormat"]', 'CSV');
    await page.check('input[name="includePayrollData"]');
    await page.check('input[name="includePerformanceData"]');
    await page.click('button[data-testid="generate-export"]');

    // Verify export initiated
    await expect(page.locator('text=Export initiated')).toBeVisible();
    await expect(page.locator('text=Download will be available shortly')).toBeVisible();
  });

  test('Audit Trail and Logging', async ({ page }) => {
    await loginAsAdmin(page);

    // Navigate to audit logs
    await page.click('text=Admin');
    await page.click('text=Audit Logs');
    await page.waitForURL('/admin/audit');

    // Filter logs by action type
    await page.selectOption('select[data-testid="action-filter"]', 'PAYROLL_APPROVED');
    await page.fill('input[name="dateFrom"]', '2024-01-01');
    await page.fill('input[name="dateTo"]', '2024-01-31');
    await page.click('button[data-testid="apply-filter"]');

    // Verify audit entries
    await expect(page.locator('[data-testid="audit-entry"]')).toBeVisible();
    
    const firstEntry = page.locator('[data-testid="audit-entry"]').first();
    await expect(firstEntry.locator('[data-testid="action"]')).toBeVisible();
    await expect(firstEntry.locator('[data-testid="user"]')).toBeVisible();
    await expect(firstEntry.locator('[data-testid="timestamp"]')).toBeVisible();
    await expect(firstEntry.locator('[data-testid="ip-address"]')).toBeVisible();

    // View detailed audit entry
    await firstEntry.locator('button[data-testid="view-details"]').click();
    await expect(page.locator('[data-testid="audit-details"]')).toBeVisible();
    await expect(page.locator('[data-testid="before-state"]')).toBeVisible();
    await expect(page.locator('[data-testid="after-state"]')).toBeVisible();

    // Export audit logs
    await page.click('button[data-testid="export-audit"]');
    await page.selectOption('select[name="exportFormat"]', 'JSON');
    await page.click('button[data-testid="generate-audit-export"]');

    // Verify export
    await expect(page.locator('text=Audit export generated')).toBeVisible();
  });
});

test.describe('Performance and Load Testing', () => {
  test('Dashboard loads quickly with large datasets', async ({ page }) => {
    await loginAsAdmin(page);

    // Measure dashboard load time
    const startTime = Date.now();
    await page.goto('/admin/dashboard');
    await page.waitForSelector('[data-testid="dashboard-metrics"]');
    const loadTime = Date.now() - startTime;

    // Verify reasonable load time (under 3 seconds)
    expect(loadTime).toBeLessThan(3000);

    // Verify all dashboard components loaded
    await expect(page.locator('[data-testid="employee-count"]')).toBeVisible();
    await expect(page.locator('[data-testid="payroll-summary"]')).toBeVisible();
    await expect(page.locator('[data-testid="recent-activities"]')).toBeVisible();
    await expect(page.locator('[data-testid="system-health"]')).toBeVisible();
  });

  test('Large file upload handling', async ({ page }) => {
    await loginAsEmployee(page);
    await page.goto('/dashboard');

    // Navigate to file upload
    await page.click('button[data-testid="upload-files-btn"]');

    // Test large file upload (5MB)
    const largeFileBuffer = Buffer.alloc(5 * 1024 * 1024);
    await page.locator('input[type="file"]').setInputFiles({
      name: 'large-work-file.pdf',
      mimeType: 'application/pdf',
      buffer: largeFileBuffer
    });

    // Verify upload progress
    await expect(page.locator('[data-testid="upload-progress"]')).toBeVisible();
    
    // Wait for upload completion
    await expect(page.locator('text=Upload completed')).toBeVisible({ timeout: 30000 });
  });
});
