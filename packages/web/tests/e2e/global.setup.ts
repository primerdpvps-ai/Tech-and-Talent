import { test as setup, expect, Page } from '@playwright/test';

/**
 * Global setup for e2e tests
 * This runs once before all tests to prepare the test environment
 */
setup('prepare test environment', async ({ page }: { page: Page }) => {
  console.log('🔧 Setting up test environment...');

  // Wait for the application to be ready
  await page.goto('/');
  
  // Check if the application is running
  await expect(page).toHaveURL(/\/(auth\/signin|dashboard)/);
  
  // Verify database is seeded with test data
  await page.goto('/auth/signin');
  
  // Test login with seeded CEO account
  await page.fill('input[name="email"]', 'ceo@tts-pms.com');
  await page.fill('input[name="password"]', 'admin123');
  await page.click('button[type="submit"]');
  
  // Verify successful login
  await expect(page).toHaveURL('/dashboard');
  await expect(page.locator('[data-testid="user-menu"]')).toBeVisible();
  
  // Verify admin access
  await page.click('text=Admin');
  await expect(page.locator('text=System Overview')).toBeVisible();
  
  // Sign out to clean up
  await page.click('[data-testid="user-menu"]');
  await page.click('text=Sign Out');
  await expect(page).toHaveURL('/auth/signin');
  
  console.log('✅ Test environment ready');
});

/**
 * Setup test data if needed
 */
setup('verify test data', async ({ page }: { page: Page }) => {
  console.log('🗄️ Verifying test data...');
  
  // Login as admin to check system state
  await page.goto('/auth/signin');
  await page.fill('input[name="email"]', 'ceo@tts-pms.com');
  await page.fill('input[name="password"]', 'admin123');
  await page.click('button[type="submit"]');
  
  // Check if we have the required test users
  await page.goto('/admin/employees');
  
  const requiredUsers = [
    'manager@tts-pms.com',
    'employee1@tts-pms.com',
    'newemployee@tts-pms.com',
    'candidate@tts-pms.com'
  ];
  
  for (const email of requiredUsers) {
    await page.fill('input[data-testid="employee-search"]', email);
    await page.keyboard.press('Enter');
    await expect(page.locator(`text=${email}`)).toBeVisible();
    console.log(`✅ Found test user: ${email}`);
  }
  
  // Check if we have test gigs
  await page.goto('/gigs');
  await expect(page.locator('[data-testid="gig-card"]')).toHaveCount(6); // Should have 6 sample gigs
  console.log('✅ Found 6 test gigs');
  
  // Check system settings
  await page.goto('/admin/settings');
  await expect(page.locator('input[name="baseHourlyRate"]')).toHaveValue(/\d+/);
  console.log('✅ System settings configured');
  
  // Sign out
  await page.click('[data-testid="user-menu"]');
  await page.click('text=Sign Out');
  
  console.log('✅ Test data verification complete');
});

/**
 * Clean up any test artifacts from previous runs
 */
setup('cleanup previous test data', async ({ page }: { page: Page }) => {
  console.log('🧹 Cleaning up previous test data...');
  
  // Login as admin
  await page.goto('/auth/signin');
  await page.fill('input[name="email"]', 'ceo@tts-pms.com');
  await page.fill('input[name="password"]', 'admin123');
  await page.click('button[type="submit"]');
  
  // Clean up any test users that might have been created
  const testEmails = [
    'testuser@example.com',
    'e2etest@example.com',
    'playwright@test.com'
  ];
  
  await page.goto('/admin/employees');
  
  for (const email of testEmails) {
    await page.fill('input[data-testid="employee-search"]', email);
    await page.keyboard.press('Enter');
    
    // If user exists, delete them
    const userRow = page.locator(`[data-testid="employee-row"]:has-text("${email}")`);
    if (await userRow.isVisible()) {
      await userRow.locator('button[data-testid="delete-user"]').click();
      await page.click('button[data-testid="confirm-delete"]');
      console.log(`🗑️ Cleaned up test user: ${email}`);
    }
  }
  
  // Clean up any pending applications from previous test runs
  await page.goto('/admin/applications');
  const testApplications = page.locator('[data-testid="application-row"]:has-text("testuser@example.com")');
  const count = await testApplications.count();
  
  for (let i = 0; i < count; i++) {
    const app = testApplications.nth(i);
    if (await app.isVisible()) {
      await app.locator('button[data-testid="delete-application"]').click();
      await page.click('button[data-testid="confirm-delete"]');
    }
  }
  
  if (count > 0) {
    console.log(`🗑️ Cleaned up ${count} test applications`);
  }
  
  // Sign out
  await page.click('[data-testid="user-menu"]');
  await page.click('text=Sign Out');
  
  console.log('✅ Cleanup complete');
});
