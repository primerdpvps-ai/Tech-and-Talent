import { test, expect } from '@playwright/test';

test.describe('Authentication', () => {
  test('should redirect to signin when not authenticated', async ({ page }) => {
    await page.goto('/');
    await expect(page).toHaveURL('/auth/signin');
  });

  test('should show signin form', async ({ page }) => {
    await page.goto('/auth/signin');
    
    await expect(page.locator('h2')).toContainText('Sign in to TTS PMS');
    await expect(page.locator('input[type="email"]')).toBeVisible();
    await expect(page.locator('input[type="password"]')).toBeVisible();
    await expect(page.locator('button[type="submit"]')).toBeVisible();
  });

  test('should show validation errors for invalid input', async ({ page }) => {
    await page.goto('/auth/signin');
    
    // Try to submit empty form
    await page.click('button[type="submit"]');
    
    // Should show validation errors
    await expect(page.locator('text=Invalid email address')).toBeVisible();
    await expect(page.locator('text=Password must be at least 6 characters')).toBeVisible();
  });
});
