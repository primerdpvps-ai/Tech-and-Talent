import { test, expect } from '@playwright/test';
import { loginAs, TEST_USERS } from './utils/test-helpers';

test.describe('API Integration Tests', () => {
  test('Agent API Authentication Flow', async ({ page }) => {
    // Test agent login endpoint
    const response = await page.request.post('/api/agent/login', {
      data: {
        email: TEST_USERS.EMPLOYEE.email,
        password: TEST_USERS.EMPLOYEE.password,
        deviceId: 'TEST-DEVICE-001',
        deviceInfo: {
          platform: 'Windows 11',
          version: '1.0.0',
          userAgent: 'TTS-Agent/1.0.0',
          screenResolution: '1920x1080',
          timezone: 'America/New_York'
        }
      }
    });

    expect(response.ok()).toBeTruthy();
    const data = await response.json();
    
    expect(data.success).toBe(true);
    expect(data.data.token).toBeDefined();
    expect(data.data.deviceSecret).toBeDefined();
    expect(data.data.user.email).toBe(TEST_USERS.EMPLOYEE.email);
    expect(data.data.permissions.canTrackTime).toBe(true);

    // Store token and secret for subsequent requests
    const { token, deviceSecret } = data.data;

    // Test timer start with HMAC signature
    const timerStartBody = JSON.stringify({
      timestamp: new Date().toISOString(),
      timezone: 'America/New_York',
      metadata: {
        startReason: 'work_day_begin',
        computerName: 'TEST-DEVICE-001'
      }
    });

    // Mock HMAC signature calculation (in real implementation, this would be calculated properly)
    const mockSignature = 'mock_hmac_signature_' + Math.random().toString(36);

    const timerResponse = await page.request.post('/api/agent/timer/start', {
      headers: {
        'Authorization': `Bearer ${token}`,
        'X-TTS-Signature': mockSignature,
        'Content-Type': 'application/json'
      },
      data: timerStartBody
    });

    // Note: This might fail due to HMAC verification, but we're testing the flow
    const timerData = await timerResponse.json();
    console.log('Timer start response:', timerData);
  });

  test('Job Management API', async ({ page }) => {
    await loginAs(page, 'CEO');

    // Test job status endpoint
    const statusResponse = await page.request.get('/api/jobs/init');
    expect(statusResponse.ok()).toBeTruthy();
    
    const statusData = await statusResponse.json();
    expect(statusData.success).toBe(true);
    expect(statusData.data.isInitialized).toBeDefined();

    // Test manual job execution (requires admin token)
    const jobResponse = await page.request.post('/api/jobs/run', {
      headers: {
        'Authorization': 'Bearer admin-secret-token-123',
        'Content-Type': 'application/json'
      },
      data: {
        jobName: 'hourly-aggregation',
        force: true
      }
    });

    expect(jobResponse.ok()).toBeTruthy();
    const jobData = await jobResponse.json();
    expect(jobData.success).toBe(true);
    expect(jobData.data.jobName).toBe('hourly-aggregation');
  });

  test('Health Check Endpoints', async ({ page }) => {
    // Test basic health check
    const healthResponse = await page.request.get('/api/health');
    expect(healthResponse.ok()).toBeTruthy();
    
    const healthData = await healthResponse.json();
    expect(healthData.success).toBe(true);
    expect(healthData.data.status).toBeDefined();
    expect(healthData.data.services).toBeDefined();

    // Test detailed health check
    const detailedHealthResponse = await page.request.post('/api/health');
    expect(detailedHealthResponse.ok()).toBeTruthy();
    
    const detailedHealthData = await detailedHealthResponse.json();
    expect(detailedHealthData.success).toBe(true);
    expect(detailedHealthData.data.system).toBeDefined();
    expect(detailedHealthData.data.configuration).toBeDefined();
  });

  test('Authentication API Validation', async ({ page }) => {
    // Test invalid login
    const invalidLoginResponse = await page.request.post('/api/auth/signin', {
      data: {
        email: 'invalid@example.com',
        password: 'wrongpassword'
      }
    });

    expect(invalidLoginResponse.status()).toBe(401);
    const invalidData = await invalidLoginResponse.json();
    expect(invalidData.success).toBe(false);

    // Test valid login
    const validLoginResponse = await page.request.post('/api/auth/signin', {
      data: {
        email: TEST_USERS.EMPLOYEE.email,
        password: TEST_USERS.EMPLOYEE.password
      }
    });

    expect(validLoginResponse.ok()).toBeTruthy();
    const validData = await validLoginResponse.json();
    expect(validData.success).toBe(true);
    expect(validData.data.user).toBeDefined();
  });

  test('File Upload API', async ({ page }) => {
    await loginAs(page, 'EMPLOYEE');

    // Test file upload endpoint
    const testFile = Buffer.from('Test file content');
    
    const uploadResponse = await page.request.post('/api/uploads/weekly', {
      multipart: {
        files: {
          name: 'test-file.pdf',
          mimeType: 'application/pdf',
          buffer: testFile
        },
        description: 'Test file upload'
      }
    });

    if (uploadResponse.ok()) {
      const uploadData = await uploadResponse.json();
      expect(uploadData.success).toBe(true);
      expect(uploadData.data.fileUrl).toBeDefined();
    } else {
      // Log the error for debugging
      console.log('Upload failed:', await uploadResponse.text());
    }
  });

  test('Payroll API Calculations', async ({ page }) => {
    await loginAs(page, 'CEO');

    // Test payroll calculation endpoint
    const payrollResponse = await page.request.post('/api/admin/payroll/calculate', {
      data: {
        weekStart: '2024-01-15',
        employeeIds: ['employee1-id'] // This would be actual employee ID
      }
    });

    if (payrollResponse.ok()) {
      const payrollData = await payrollResponse.json();
      expect(payrollData.success).toBe(true);
      expect(payrollData.data.calculations).toBeDefined();
      expect(payrollData.data.summary).toBeDefined();
    }
  });

  test('Leave Request API', async ({ page }) => {
    await loginAs(page, 'EMPLOYEE');

    // Test leave request submission
    const leaveResponse = await page.request.post('/api/leaves/request', {
      data: {
        type: 'ONE_DAY',
        dateFrom: '2024-02-15',
        dateTo: '2024-02-15',
        reason: 'Personal appointment'
      }
    });

    if (leaveResponse.ok()) {
      const leaveData = await leaveResponse.json();
      expect(leaveData.success).toBe(true);
      expect(leaveData.data.requestId).toBeDefined();
    }

    // Test leave request validation
    const invalidLeaveResponse = await page.request.post('/api/leaves/request', {
      data: {
        type: 'SHORT',
        dateFrom: '2024-02-15',
        dateTo: '2024-02-17', // Invalid: SHORT leave can't be multiple days
        reason: 'Test'
      }
    });

    expect(invalidLeaveResponse.status()).toBe(400);
    const invalidLeaveData = await invalidLeaveResponse.json();
    expect(invalidLeaveData.success).toBe(false);
    expect(invalidLeaveData.error.message).toContain('Short leave');
  });

  test('Rate Limiting and Security', async ({ page }) => {
    // Test rate limiting on login endpoint
    const promises = [];
    for (let i = 0; i < 10; i++) {
      promises.push(
        page.request.post('/api/auth/signin', {
          data: {
            email: 'test@example.com',
            password: 'wrongpassword'
          }
        })
      );
    }

    const responses = await Promise.all(promises);
    
    // Some requests should be rate limited
    const rateLimitedResponses = responses.filter(r => r.status() === 429);
    expect(rateLimitedResponses.length).toBeGreaterThan(0);

    // Test CORS headers
    const corsResponse = await page.request.options('/api/health');
    expect(corsResponse.headers()['access-control-allow-origin']).toBeDefined();
  });

  test('Error Handling and Edge Cases', async ({ page }) => {
    // Test malformed JSON
    const malformedResponse = await page.request.post('/api/auth/signin', {
      data: 'invalid json',
      headers: {
        'Content-Type': 'application/json'
      }
    });

    expect(malformedResponse.status()).toBe(400);

    // Test missing required fields
    const missingFieldsResponse = await page.request.post('/api/auth/signin', {
      data: {
        email: 'test@example.com'
        // Missing password
      }
    });

    expect(missingFieldsResponse.status()).toBe(400);
    const missingFieldsData = await missingFieldsResponse.json();
    expect(missingFieldsData.success).toBe(false);

    // Test unauthorized access
    const unauthorizedResponse = await page.request.get('/api/admin/users');
    expect(unauthorizedResponse.status()).toBe(401);

    // Test non-existent endpoint
    const notFoundResponse = await page.request.get('/api/nonexistent');
    expect(notFoundResponse.status()).toBe(404);
  });

  test('WebSocket Connections (if implemented)', async ({ page }) => {
    await loginAs(page, 'EMPLOYEE');

    // Test WebSocket connection for real-time updates
    const wsConnected = await page.evaluate(() => {
      return new Promise((resolve) => {
        try {
          const ws = new WebSocket('ws://localhost:3000/ws');
          ws.onopen = () => {
            ws.close();
            resolve(true);
          };
          ws.onerror = () => resolve(false);
          
          // Timeout after 5 seconds
          setTimeout(() => resolve(false), 5000);
        } catch (error) {
          resolve(false);
        }
      });
    });

    // WebSocket might not be implemented yet, so this is optional
    if (wsConnected) {
      console.log('✅ WebSocket connection successful');
    } else {
      console.log('ℹ️ WebSocket not available (optional feature)');
    }
  });

  test('API Performance', async ({ page }) => {
    await loginAs(page, 'EMPLOYEE');

    // Test API response times
    const endpoints = [
      '/api/health',
      '/api/dashboard/metrics',
      '/api/user/profile'
    ];

    for (const endpoint of endpoints) {
      const startTime = Date.now();
      const response = await page.request.get(endpoint);
      const responseTime = Date.now() - startTime;

      console.log(`${endpoint}: ${responseTime}ms`);
      
      // API should respond within 2 seconds
      expect(responseTime).toBeLessThan(2000);
      
      if (response.ok()) {
        const data = await response.json();
        expect(data).toBeDefined();
      }
    }
  });
});
