import { test, expect, Page } from '@playwright/test';

// Test data
const testUser = {
  email: 'testuser@example.com',
  password: 'TestPassword123!',
  fullName: 'Test User',
  phone: '+1234567890',
};

// Helper functions
async function fillSignupForm(page: Page, userData = testUser) {
  await page.fill('input[name="email"]', userData.email);
  await page.fill('input[name="password"]', userData.password);
  await page.fill('input[name="confirmPassword"]', userData.password);
  await page.fill('input[name="fullName"]', userData.fullName);
  await page.fill('input[name="phone"]', userData.phone);
}

async function mockOTPVerification(page: Page, otpCode = '123456') {
  // Mock OTP input - assuming 6 digit OTP with individual inputs
  for (let i = 0; i < 6; i++) {
    await page.fill(`input[data-testid="otp-input-${i}"]`, otpCode[i]);
  }
}

async function waitForNavigation(page: Page, expectedUrl: string, timeout = 10000) {
  await page.waitForURL(expectedUrl, { timeout });
}

test.describe('Complete User Journey', () => {
  test.beforeEach(async ({ page }) => {
    // Start from the landing page
    await page.goto('/');
  });

  test('Sign-up + Dual OTP Happy Path', async ({ page }) => {
    // Step 1: Navigate to signup
    await page.click('text=Sign Up');
    await expect(page).toHaveURL('/auth/signup');

    // Step 2: Fill signup form
    await fillSignupForm(page);
    await page.check('input[name="agreeToTerms"]');
    await page.click('button[type="submit"]');

    // Step 3: Email OTP verification
    await expect(page.locator('h2')).toContainText('Verify Your Email');
    await expect(page.locator('text=We sent a verification code to testuser@example.com')).toBeVisible();
    
    // Mock email OTP verification
    await mockOTPVerification(page, '123456');
    await page.click('button[type="submit"]');

    // Step 4: SMS OTP verification
    await expect(page.locator('h2')).toContainText('Verify Your Phone');
    await expect(page.locator('text=We sent a verification code to +1234567890')).toBeVisible();
    
    // Mock SMS OTP verification
    await mockOTPVerification(page, '654321');
    await page.click('button[type="submit"]');

    // Step 5: Verify successful registration
    await waitForNavigation(page, '/evaluation');
    await expect(page.locator('h1')).toContainText('Technical Evaluation');
    
    // Verify user is logged in
    await expect(page.locator('[data-testid="user-menu"]')).toBeVisible();
  });

  test('Evaluation => Candidate Flow', async ({ page }) => {
    // Assume user is already registered and logged in
    await page.goto('/auth/signin');
    await page.fill('input[name="email"]', 'candidate@tts-pms.com');
    await page.fill('input[name="password"]', 'candidate123');
    await page.click('button[type="submit"]');

    // Navigate to evaluation
    await waitForNavigation(page, '/evaluation');
    
    // Fill evaluation form
    await page.fill('input[name="age"]', '25');
    await page.selectOption('select[name="deviceType"]', 'Desktop');
    await page.fill('input[name="ramText"]', '16GB DDR4');
    await page.fill('input[name="processorText"]', 'Intel Core i7-10700K');
    await page.check('input[name="stableInternet"]');
    await page.fill('input[name="provider"]', 'Comcast');
    await page.fill('input[name="linkSpeed"]', '100 Mbps');
    await page.fill('input[name="numUsers"]', '2');
    await page.fill('input[name="speedtestUrl"]', 'https://speedtest.net/result/12345');
    await page.fill('input[name="profession"]', 'Software Developer');
    await page.check('input[name="dailyTimeOk"]');
    await page.fill('input[name="timeWindows"]', '09:00-17:00, 19:00-23:00');
    await page.fill('input[name="qualification"]', 'Bachelor in Computer Science');
    await page.check('input[name="confidentialityOk"]');
    await page.check('input[name="typingOk"]');

    // Submit evaluation
    await page.click('button[type="submit"]');

    // Verify evaluation results
    await expect(page.locator('text=✅ ELIGIBLE')).toBeVisible();
    await expect(page.locator('text=You can now apply for available positions')).toBeVisible();

    // Verify role change to CANDIDATE
    await page.reload();
    await expect(page.locator('[data-testid="user-role"]')).toContainText('Candidate');
  });

  test('Application Wizard (uploads/selfie) => UNDER_REVIEW', async ({ page }) => {
    // Login as candidate
    await page.goto('/auth/signin');
    await page.fill('input[name="email"]', 'candidate@tts-pms.com');
    await page.fill('input[name="password"]', 'candidate123');
    await page.click('button[type="submit"]');

    // Navigate to gigs page
    await page.click('text=Browse Jobs');
    await waitForNavigation(page, '/gigs');

    // Select a gig to apply for
    await page.click('[data-testid="gig-card"]:first-child .btn-primary');
    await waitForNavigation(page, /\/gigs\/.*\/apply/);

    // Step 1: Personal Information
    await expect(page.locator('h2')).toContainText('Personal Information');
    await page.fill('input[name="address"]', '123 Main St, Anytown, USA');
    await page.fill('input[name="phone"]', '+1-555-0123');
    await page.fill('input[name="emergencyContact"]', 'Jane Doe - +1-555-0124');
    await page.fill('textarea[name="experience"]', '3 years of relevant experience in the field');
    await page.click('button[type="submit"]');

    // Step 2: Document Uploads
    await expect(page.locator('h2')).toContainText('Document Upload');
    
    // Upload resume
    const resumeFile = await page.locator('input[name="resume"]');
    await resumeFile.setInputFiles({
      name: 'resume.pdf',
      mimeType: 'application/pdf',
      buffer: Buffer.from('Mock PDF content for resume')
    });

    // Upload CNIC
    const cnicFile = await page.locator('input[name="cnic"]');
    await cnicFile.setInputFiles({
      name: 'cnic.jpg',
      mimeType: 'image/jpeg',
      buffer: Buffer.from('Mock JPEG content for CNIC')
    });

    // Upload utility bill
    const utilityFile = await page.locator('input[name="utility"]');
    await utilityFile.setInputFiles({
      name: 'utility.pdf',
      mimeType: 'application/pdf',
      buffer: Buffer.from('Mock PDF content for utility bill')
    });

    await page.click('button[type="submit"]');

    // Step 3: Selfie Capture
    await expect(page.locator('h2')).toContainText('Identity Verification');
    
    // Mock camera access and selfie capture
    await page.evaluate(() => {
      // Mock getUserMedia
      Object.defineProperty(navigator, 'mediaDevices', {
        writable: true,
        value: {
          getUserMedia: () => Promise.resolve({
            getTracks: () => [{ stop: () => {} }]
          })
        }
      });
    });

    await page.click('button[data-testid="start-camera"]');
    await page.waitForSelector('[data-testid="camera-preview"]');
    await page.click('button[data-testid="capture-selfie"]');
    await expect(page.locator('[data-testid="selfie-preview"]')).toBeVisible();
    await page.click('button[data-testid="confirm-selfie"]');

    // Step 4: Review and Submit
    await expect(page.locator('h2')).toContainText('Review Application');
    await expect(page.locator('text=Personal Information')).toBeVisible();
    await expect(page.locator('text=Documents Uploaded')).toBeVisible();
    await expect(page.locator('text=Identity Verified')).toBeVisible();

    await page.click('button[data-testid="submit-application"]');

    // Verify application submitted
    await expect(page.locator('text=Application Submitted Successfully')).toBeVisible();
    await expect(page.locator('text=Your application is now under review')).toBeVisible();
    
    // Verify status change
    await page.goto('/dashboard');
    await expect(page.locator('[data-testid="application-status"]')).toContainText('Under Review');
  });

  test('Admin Approve => New Employee', async ({ page }) => {
    // Login as admin
    await page.goto('/auth/signin');
    await page.fill('input[name="email"]', 'manager@tts-pms.com');
    await page.fill('input[name="password"]', 'manager123');
    await page.click('button[type="submit"]');

    // Navigate to admin applications
    await page.click('text=Admin');
    await page.click('text=Applications');
    await waitForNavigation(page, '/admin/applications');

    // Find pending application
    const applicationRow = page.locator('[data-testid="application-row"]').first();
    await expect(applicationRow.locator('[data-testid="status"]')).toContainText('UNDER_REVIEW');

    // Click review button
    await applicationRow.locator('button[data-testid="review-btn"]').click();

    // Review application details
    await expect(page.locator('h2')).toContainText('Application Review');
    await expect(page.locator('[data-testid="applicant-info"]')).toBeVisible();
    await expect(page.locator('[data-testid="uploaded-documents"]')).toBeVisible();

    // Approve application
    await page.selectOption('select[name="decision"]', 'APPROVED');
    await page.fill('textarea[name="reviewNotes"]', 'Excellent qualifications and documentation. Approved for employment.');
    
    // Set employment details
    await page.fill('input[name="rdpHost"]', 'rdp-server-01.tts-pms.com');
    await page.fill('input[name="rdpUsername"]', 'newemployee01');
    await page.fill('input[name="startDate"]', '2024-02-01');

    await page.click('button[data-testid="submit-review"]');

    // Verify approval
    await expect(page.locator('text=Application approved successfully')).toBeVisible();
    
    // Verify status change in applications list
    await page.goto('/admin/applications');
    await expect(applicationRow.locator('[data-testid="status"]')).toContainText('APPROVED');

    // Verify user role change
    await page.click('text=Users');
    const userRow = page.locator('[data-testid="user-row"]').filter({ hasText: testUser.email });
    await expect(userRow.locator('[data-testid="role"]')).toContainText('NEW_EMPLOYEE');
  });

  test('Training Lock => Employee after 1st Payroll', async ({ page }) => {
    // Login as new employee
    await page.goto('/auth/signin');
    await page.fill('input[name="email"]', 'newemployee@tts-pms.com');
    await page.fill('input[name="password"]', 'newemployee123');
    await page.click('button[type="submit"]');

    // Verify training lock is active
    await waitForNavigation(page, '/dashboard');
    await expect(page.locator('[data-testid="training-banner"]')).toBeVisible();
    await expect(page.locator('text=Training Period Active')).toBeVisible();
    await expect(page.locator('text=Limited access until first payroll')).toBeVisible();

    // Verify restricted navigation
    await expect(page.locator('nav a[href="/payroll"]')).toBeDisabled();
    await expect(page.locator('nav a[href="/reports"]')).toBeDisabled();

    // Simulate first payroll processing (admin action)
    await page.goto('/auth/signin');
    await page.fill('input[name="email"]', 'ceo@tts-pms.com');
    await page.fill('input[name="password"]', 'admin123');
    await page.click('button[type="submit"]');

    // Navigate to payroll
    await page.click('text=Admin');
    await page.click('text=Payroll');
    await waitForNavigation(page, '/admin/payroll');

    // Process first payroll batch
    await page.click('button[data-testid="create-payroll-batch"]');
    
    // Select the new employee
    const employeeRow = page.locator('[data-testid="employee-row"]').filter({ hasText: 'newemployee@tts-pms.com' });
    await employeeRow.locator('input[type="checkbox"]').check();
    
    await page.click('button[data-testid="generate-batch"]');
    await page.click('button[data-testid="approve-batch"]');

    // Verify payroll processed
    await expect(page.locator('text=Payroll batch processed successfully')).toBeVisible();

    // Login back as new employee to verify training lock removed
    await page.goto('/auth/signin');
    await page.fill('input[name="email"]', 'newemployee@tts-pms.com');
    await page.fill('input[name="password"]', 'newemployee123');
    await page.click('button[type="submit"]');

    // Verify training lock is removed
    await waitForNavigation(page, '/dashboard');
    await expect(page.locator('[data-testid="training-banner"]')).not.toBeVisible();
    
    // Verify role upgrade to EMPLOYEE
    await expect(page.locator('[data-testid="user-role"]')).toContainText('Employee');
    
    // Verify full access restored
    await expect(page.locator('nav a[href="/payroll"]')).toBeEnabled();
    await expect(page.locator('nav a[href="/reports"]')).toBeEnabled();
  });

  test('Employee Dashboard Upload => Payroll Hold Cleared', async ({ page }) => {
    // Login as employee
    await page.goto('/auth/signin');
    await page.fill('input[name="email"]', 'employee1@tts-pms.com');
    await page.fill('input[name="password"]', 'employee123');
    await page.click('button[type="submit"]');

    // Navigate to dashboard
    await waitForNavigation(page, '/dashboard');

    // Check if payroll hold is active
    const payrollHoldBanner = page.locator('[data-testid="payroll-hold-banner"]');
    if (await payrollHoldBanner.isVisible()) {
      await expect(payrollHoldBanner.locator('text=Weekly Upload Required')).toBeVisible();
      await expect(payrollHoldBanner.locator('text=Upload your work files to release payroll hold')).toBeVisible();
    }

    // Navigate to weekly upload section
    await page.click('button[data-testid="upload-files-btn"]');
    await expect(page.locator('h2')).toContainText('Weekly File Upload');

    // Upload work files
    const fileInput = page.locator('input[type="file"][multiple]');
    await fileInput.setInputFiles([
      {
        name: 'work-file-1.docx',
        mimeType: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        buffer: Buffer.from('Mock DOCX content for work file 1')
      },
      {
        name: 'work-file-2.xlsx',
        mimeType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        buffer: Buffer.from('Mock XLSX content for work file 2')
      },
      {
        name: 'work-file-3.pdf',
        mimeType: 'application/pdf',
        buffer: Buffer.from('Mock PDF content for work file 3')
      }
    ]);

    // Add upload description
    await page.fill('textarea[name="description"]', 'Weekly work files including project documentation, data analysis, and reports.');

    // Submit upload
    await page.click('button[data-testid="submit-upload"]');

    // Verify upload success
    await expect(page.locator('text=Files uploaded successfully')).toBeVisible();
    await expect(page.locator('[data-testid="upload-status"]')).toContainText('Completed');

    // Verify payroll hold is cleared
    await page.goto('/dashboard');
    await expect(page.locator('[data-testid="payroll-hold-banner"]')).not.toBeVisible();
    
    // Verify upload confirmation
    await expect(page.locator('[data-testid="upload-confirmation"]')).toBeVisible();
    await expect(page.locator('text=Weekly upload completed')).toBeVisible();
    await expect(page.locator('text=3 files uploaded')).toBeVisible();

    // Verify in uploads history
    await page.click('text=Upload History');
    const latestUpload = page.locator('[data-testid="upload-entry"]').first();
    await expect(latestUpload.locator('[data-testid="file-count"]')).toContainText('3 files');
    await expect(latestUpload.locator('[data-testid="upload-date"]')).toBeVisible();
    await expect(latestUpload.locator('[data-testid="status"]')).toContainText('Approved');
  });

  test('Error Handling and Edge Cases', async ({ page }) => {
    // Test invalid email format
    await page.goto('/auth/signup');
    await page.fill('input[name="email"]', 'invalid-email');
    await page.fill('input[name="password"]', 'password123');
    await page.click('button[type="submit"]');
    await expect(page.locator('text=Invalid email address')).toBeVisible();

    // Test password mismatch
    await page.fill('input[name="email"]', 'test@example.com');
    await page.fill('input[name="password"]', 'password123');
    await page.fill('input[name="confirmPassword"]', 'different123');
    await page.click('button[type="submit"]');
    await expect(page.locator('text=Passwords do not match')).toBeVisible();

    // Test file upload size limit
    await page.goto('/auth/signin');
    await page.fill('input[name="email"]', 'employee1@tts-pms.com');
    await page.fill('input[name="password"]', 'employee123');
    await page.click('button[type="submit"]');
    
    await page.goto('/dashboard');
    await page.click('button[data-testid="upload-files-btn"]');
    
    // Try to upload oversized file
    const largeFileBuffer = Buffer.alloc(11 * 1024 * 1024); // 11MB file
    await page.locator('input[type="file"]').setInputFiles({
      name: 'large-file.pdf',
      mimeType: 'application/pdf',
      buffer: largeFileBuffer
    });
    
    await expect(page.locator('text=File size exceeds maximum limit')).toBeVisible();
  });
});

test.describe('Mobile Responsiveness', () => {
  test.use({ viewport: { width: 375, height: 667 } }); // iPhone SE size

  test('Mobile signup flow works correctly', async ({ page }) => {
    await page.goto('/auth/signup');
    
    // Verify mobile layout
    await expect(page.locator('[data-testid="mobile-signup-form"]')).toBeVisible();
    
    // Fill form on mobile
    await fillSignupForm(page);
    await page.check('input[name="agreeToTerms"]');
    await page.click('button[type="submit"]');
    
    // Verify OTP modal is mobile-friendly
    await expect(page.locator('[data-testid="mobile-otp-modal"]')).toBeVisible();
  });
});

test.describe('Accessibility', () => {
  test('Signup form is accessible', async ({ page }) => {
    await page.goto('/auth/signup');
    
    // Check for proper labels
    await expect(page.locator('label[for="email"]')).toBeVisible();
    await expect(page.locator('label[for="password"]')).toBeVisible();
    
    // Check for ARIA attributes
    await expect(page.locator('input[name="email"]')).toHaveAttribute('aria-required', 'true');
    await expect(page.locator('input[name="password"]')).toHaveAttribute('aria-required', 'true');
    
    // Check keyboard navigation
    await page.keyboard.press('Tab');
    await expect(page.locator('input[name="email"]')).toBeFocused();
  });
});
