import { Page, expect } from '@playwright/test';

// Test data constants
export const TEST_USERS = {
  CEO: {
    email: 'ceo@tts-pms.com',
    password: 'admin123',
    role: 'CEO'
  },
  MANAGER: {
    email: 'manager@tts-pms.com',
    password: 'manager123',
    role: 'MANAGER'
  },
  EMPLOYEE: {
    email: 'employee1@tts-pms.com',
    password: 'employee123',
    role: 'EMPLOYEE'
  },
  NEW_EMPLOYEE: {
    email: 'newemployee@tts-pms.com',
    password: 'newemployee123',
    role: 'NEW_EMPLOYEE'
  },
  CANDIDATE: {
    email: 'candidate@tts-pms.com',
    password: 'candidate123',
    role: 'VISITOR'
  }
};

export const TEST_SIGNUP_DATA = {
  email: 'testuser@example.com',
  password: 'TestPassword123!',
  confirmPassword: 'TestPassword123!',
  fullName: 'Test User',
  phone: '+1234567890'
};

// Authentication helpers
export async function loginAs(page: Page, userType: keyof typeof TEST_USERS) {
  const user = TEST_USERS[userType];
  
  await page.goto('/auth/signin');
  await page.fill('input[name="email"]', user.email);
  await page.fill('input[name="password"]', user.password);
  await page.click('button[type="submit"]');
  
  // Wait for successful login
  await page.waitForURL('/dashboard');
  await expect(page.locator('[data-testid="user-menu"]')).toBeVisible();
  
  return user;
}

export async function logout(page: Page) {
  await page.click('[data-testid="user-menu"]');
  await page.click('text=Sign Out');
  await page.waitForURL('/auth/signin');
}

// Form helpers
export async function fillSignupForm(page: Page, userData = TEST_SIGNUP_DATA) {
  await page.fill('input[name="email"]', userData.email);
  await page.fill('input[name="password"]', userData.password);
  await page.fill('input[name="confirmPassword"]', userData.confirmPassword);
  await page.fill('input[name="fullName"]', userData.fullName);
  await page.fill('input[name="phone"]', userData.phone);
}

export async function fillEvaluationForm(page: Page, overrides: Partial<any> = {}) {
  const defaultData = {
    age: '25',
    deviceType: 'Desktop',
    ramText: '16GB DDR4',
    processorText: 'Intel Core i7-10700K',
    stableInternet: true,
    provider: 'Comcast',
    linkSpeed: '100 Mbps',
    numUsers: '2',
    speedtestUrl: 'https://speedtest.net/result/12345',
    profession: 'Software Developer',
    dailyTimeOk: true,
    timeWindows: '09:00-17:00, 19:00-23:00',
    qualification: 'Bachelor in Computer Science',
    confidentialityOk: true,
    typingOk: true,
    ...overrides
  };

  await page.fill('input[name="age"]', defaultData.age);
  await page.selectOption('select[name="deviceType"]', defaultData.deviceType);
  await page.fill('input[name="ramText"]', defaultData.ramText);
  await page.fill('input[name="processorText"]', defaultData.processorText);
  
  if (defaultData.stableInternet) {
    await page.check('input[name="stableInternet"]');
  }
  
  await page.fill('input[name="provider"]', defaultData.provider);
  await page.fill('input[name="linkSpeed"]', defaultData.linkSpeed);
  await page.fill('input[name="numUsers"]', defaultData.numUsers);
  await page.fill('input[name="speedtestUrl"]', defaultData.speedtestUrl);
  await page.fill('input[name="profession"]', defaultData.profession);
  
  if (defaultData.dailyTimeOk) {
    await page.check('input[name="dailyTimeOk"]');
  }
  
  await page.fill('input[name="timeWindows"]', defaultData.timeWindows);
  await page.fill('input[name="qualification"]', defaultData.qualification);
  
  if (defaultData.confidentialityOk) {
    await page.check('input[name="confidentialityOk"]');
  }
  
  if (defaultData.typingOk) {
    await page.check('input[name="typingOk"]');
  }
}

export async function fillApplicationForm(page: Page, overrides: Partial<any> = {}) {
  const defaultData = {
    address: '123 Main St, Anytown, USA',
    phone: '+1-555-0123',
    emergencyContact: 'Jane Doe - +1-555-0124',
    experience: '3 years of relevant experience in the field',
    ...overrides
  };

  await page.fill('input[name="address"]', defaultData.address);
  await page.fill('input[name="phone"]', defaultData.phone);
  await page.fill('input[name="emergencyContact"]', defaultData.emergencyContact);
  await page.fill('textarea[name="experience"]', defaultData.experience);
}

// OTP helpers
export async function mockOTPVerification(page: Page, otpCode = '123456') {
  // Handle both single input and multi-input OTP forms
  const singleInput = page.locator('input[name="otp"]');
  const multiInputs = page.locator('[data-testid^="otp-input-"]');
  
  if (await singleInput.isVisible()) {
    await singleInput.fill(otpCode);
  } else if (await multiInputs.first().isVisible()) {
    for (let i = 0; i < 6; i++) {
      await page.fill(`input[data-testid="otp-input-${i}"]`, otpCode[i]);
    }
  }
}

// File upload helpers
export async function uploadTestFiles(page: Page, fileTypes: string[] = ['pdf', 'jpg', 'docx']) {
  const files = fileTypes.map(type => ({
    name: `test-file.${type}`,
    mimeType: getMimeType(type),
    buffer: Buffer.from(`Mock ${type.toUpperCase()} content`)
  }));

  await page.locator('input[type="file"]').setInputFiles(files);
}

export async function uploadSpecificFiles(page: Page, selector: string, files: Array<{name: string, type: string, size?: number}>) {
  const fileInputs = files.map(file => ({
    name: file.name,
    mimeType: getMimeType(file.type),
    buffer: Buffer.alloc(file.size || 1024, `Mock content for ${file.name}`)
  }));

  await page.locator(selector).setInputFiles(fileInputs);
}

function getMimeType(extension: string): string {
  const mimeTypes: Record<string, string> = {
    'pdf': 'application/pdf',
    'jpg': 'image/jpeg',
    'jpeg': 'image/jpeg',
    'png': 'image/png',
    'docx': 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'xlsx': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'txt': 'text/plain'
  };
  
  return mimeTypes[extension.toLowerCase()] || 'application/octet-stream';
}

// Navigation helpers
export async function navigateToAdminSection(page: Page, section: string) {
  await page.click('text=Admin');
  await page.click(`text=${section}`);
  await page.waitForURL(`/admin/${section.toLowerCase()}`);
}

export async function waitForToast(page: Page, message: string, type: 'success' | 'error' | 'info' = 'success') {
  await expect(page.locator(`[data-testid="toast-${type}"]:has-text("${message}")`)).toBeVisible();
}

// Mock camera for selfie capture
export async function mockCameraAccess(page: Page) {
  await page.evaluate(() => {
    // Mock getUserMedia
    Object.defineProperty(navigator, 'mediaDevices', {
      writable: true,
      value: {
        getUserMedia: () => Promise.resolve({
          getTracks: () => [{ stop: () => {} }],
          getVideoTracks: () => [{ stop: () => {} }]
        })
      }
    });
  });
}

// Wait helpers
export async function waitForLoadingToComplete(page: Page) {
  await page.waitForSelector('[data-testid="loading-spinner"]', { state: 'hidden' });
}

export async function waitForTableToLoad(page: Page, tableSelector = '[data-testid="data-table"]') {
  await page.waitForSelector(tableSelector);
  await page.waitForSelector(`${tableSelector} [data-testid="table-row"]`);
}

// Assertion helpers
export async function expectUserRole(page: Page, expectedRole: string) {
  await expect(page.locator('[data-testid="user-role"]')).toContainText(expectedRole);
}

export async function expectNotificationCount(page: Page, count: number) {
  if (count === 0) {
    await expect(page.locator('[data-testid="notification-badge"]')).not.toBeVisible();
  } else {
    await expect(page.locator('[data-testid="notification-badge"]')).toContainText(count.toString());
  }
}

export async function expectDashboardMetric(page: Page, metric: string, value: string | RegExp) {
  await expect(page.locator(`[data-testid="metric-${metric}"]`)).toContainText(value);
}

// Date helpers
export function getDateString(daysOffset = 0): string {
  const date = new Date();
  date.setDate(date.getDate() + daysOffset);
  return date.toISOString().split('T')[0];
}

export function getTimeString(hoursOffset = 0): string {
  const date = new Date();
  date.setHours(date.getHours() + hoursOffset);
  return date.toTimeString().slice(0, 5);
}

// Test data generators
export function generateRandomEmail(): string {
  const timestamp = Date.now();
  const random = Math.random().toString(36).substring(7);
  return `test-${timestamp}-${random}@example.com`;
}

export function generateTestUser(overrides: Partial<any> = {}) {
  return {
    email: generateRandomEmail(),
    password: 'TestPassword123!',
    fullName: 'Test User ' + Math.random().toString(36).substring(7),
    phone: '+1555' + Math.floor(Math.random() * 1000000).toString().padStart(7, '0'),
    ...overrides
  };
}

// Error handling helpers
export async function expectValidationError(page: Page, field: string, message: string) {
  await expect(page.locator(`[data-testid="error-${field}"]`)).toContainText(message);
}

export async function expectFormError(page: Page, message: string) {
  await expect(page.locator('[data-testid="form-error"]')).toContainText(message);
}

// Performance helpers
export async function measurePageLoadTime(page: Page, url: string): Promise<number> {
  const startTime = Date.now();
  await page.goto(url);
  await page.waitForLoadState('networkidle');
  return Date.now() - startTime;
}

// Mobile helpers
export async function expectMobileLayout(page: Page) {
  await expect(page.locator('[data-testid="mobile-nav"]')).toBeVisible();
  await expect(page.locator('[data-testid="desktop-nav"]')).not.toBeVisible();
}

export async function expectDesktopLayout(page: Page) {
  await expect(page.locator('[data-testid="desktop-nav"]')).toBeVisible();
  await expect(page.locator('[data-testid="mobile-nav"]')).not.toBeVisible();
}

// Accessibility helpers
export async function expectProperLabels(page: Page, formSelector = 'form') {
  const inputs = page.locator(`${formSelector} input[required]`);
  const count = await inputs.count();
  
  for (let i = 0; i < count; i++) {
    const input = inputs.nth(i);
    const id = await input.getAttribute('id');
    if (id) {
      await expect(page.locator(`label[for="${id}"]`)).toBeVisible();
    }
  }
}

export async function expectAriaAttributes(page: Page, selector: string) {
  const element = page.locator(selector);
  await expect(element).toHaveAttribute('aria-required', 'true');
}

// Database state helpers (for testing purposes)
export async function resetTestUserState(page: Page, email: string) {
  // This would typically call an API endpoint to reset user state
  // For now, we'll just ensure the user is logged out
  await page.goto('/auth/signin');
  // Additional cleanup logic would go here
}
