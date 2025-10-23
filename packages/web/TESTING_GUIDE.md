# TTS PMS Testing Guide

This document provides comprehensive information about the testing setup for the TTS PMS application, including unit tests with Vitest and end-to-end tests with Playwright.

## 🧪 Testing Overview

The TTS PMS application uses a multi-layered testing approach:

- **Unit Tests**: Vitest for testing individual service functions
- **End-to-End Tests**: Playwright for testing complete user workflows
- **API Integration Tests**: Testing REST API endpoints and authentication flows

## 📁 Test Structure

```
packages/web/
├── src/services/__tests__/          # Unit tests
│   ├── eligibility.test.ts          # Candidate evaluation logic
│   ├── payroll.test.ts              # Payroll calculations
│   ├── leaves.test.ts               # Leave request validation
│   ├── streaks.test.ts              # Activity streaks and compliance
│   └── windows.test.ts              # Operational window validation
├── tests/e2e/                      # End-to-end tests
│   ├── user-journey.spec.ts         # Complete user workflows
│   ├── admin-workflows.spec.ts      # Administrative functions
│   ├── api-integration.spec.ts      # API endpoint testing
│   ├── auth.spec.ts                 # Authentication flows
│   ├── global.setup.ts              # Test environment setup
│   └── utils/test-helpers.ts        # Shared test utilities
├── playwright.config.ts             # Playwright configuration
└── vitest.config.ts                # Vitest configuration
```

## 🔬 Unit Tests (Vitest)

### Eligibility Service Tests
Tests the candidate evaluation system that determines job eligibility based on technical requirements.

**Key Test Cases:**
- ✅ Minimum requirements validation (age, confidentiality, internet)
- ✅ Hardware scoring (RAM, processor, device type)
- ✅ Internet quality assessment (speed, users, stability)
- ✅ Professional background evaluation
- ✅ Availability and time window validation
- ✅ Overall eligibility determination (ELIGIBLE/PENDING/REJECTED)

```bash
# Run eligibility tests
npm run test:services -- eligibility.test.ts
```

### Payroll Service Tests
Tests the complex payroll calculation system including rates, bonuses, penalties, and deductions.

**Key Test Cases:**
- ✅ Basic hourly rate calculations
- ✅ Weekend and overtime multipliers
- ✅ Streak bonus eligibility (28+ days)
- ✅ Security fund deduction (first payroll)
- ✅ Tax calculations and deductions
- ✅ Penalty applications
- ✅ First salary eligibility (7+ days)
- ✅ Weekly summary aggregation

```bash
# Run payroll tests
npm run test:services -- payroll.test.ts
```

### Leave Management Tests
Tests the leave request validation system with policy enforcement and penalty calculations.

**Key Test Cases:**
- ✅ Leave type validation (SHORT/ONE_DAY/LONG)
- ✅ Notice period requirements
- ✅ Weekend penalty calculations
- ✅ Weekly and monthly limits
- ✅ Suspension eligibility checks
- ✅ Resignation notice validation
- ✅ Policy violation detection

```bash
# Run leave tests
npm run test:services -- leaves.test.ts
```

### Streaks Service Tests
Tests the activity tracking and streak calculation system for employee performance monitoring.

**Key Test Cases:**
- ✅ Activity event processing (mouse, keyboard, window focus)
- ✅ Automatic pause detection (≥40s inactivity)
- ✅ Daily activity calculations
- ✅ Streak computation (current and longest)
- ✅ Weekend gap handling in streaks
- ✅ Bonus qualification (28+ day streaks)
- ✅ Timer session validation
- ✅ Performance analytics

```bash
# Run streaks tests
npm run test:services -- streaks.test.ts
```

### Operational Windows Tests
Tests the time-based access control system for different user tenure levels.

**Key Test Cases:**
- ✅ PKT timezone calculations
- ✅ Standard window validation (11:00-02:00 PKT)
- ✅ Special window access (02:00-06:00 PKT)
- ✅ Tenure-based permissions (10+ days)
- ✅ Overnight window handling
- ✅ Access denial reasons

```bash
# Run windows tests
npm run test:services -- windows.test.ts
```

## 🎭 End-to-End Tests (Playwright)

### Complete User Journey Tests
Tests the entire user lifecycle from registration to employment.

**Test Scenarios:**

#### 1. Sign-up + Dual OTP Happy Path
- ✅ User registration form completion
- ✅ Email OTP verification
- ✅ SMS OTP verification
- ✅ Successful account creation
- ✅ Automatic redirect to evaluation

#### 2. Evaluation => Candidate Flow
- ✅ Technical evaluation form completion
- ✅ Hardware and internet assessment
- ✅ Professional background validation
- ✅ Eligibility determination
- ✅ Role change to CANDIDATE

#### 3. Application Wizard => UNDER_REVIEW
- ✅ Job selection and application start
- ✅ Personal information collection
- ✅ Document uploads (resume, CNIC, utility bill)
- ✅ Selfie capture with camera mock
- ✅ Application review and submission
- ✅ Status change to UNDER_REVIEW

#### 4. Admin Approve => New Employee
- ✅ Manager/admin login
- ✅ Application review interface
- ✅ Document verification
- ✅ Employment details setup (RDP, start date)
- ✅ Approval decision recording
- ✅ Role change to NEW_EMPLOYEE

#### 5. Training Lock => Employee after 1st Payroll
- ✅ New employee restricted access
- ✅ Training period banner display
- ✅ Limited navigation enforcement
- ✅ First payroll processing
- ✅ Training lock removal
- ✅ Full access restoration

#### 6. Employee Dashboard Upload => Payroll Hold Cleared
- ✅ Weekly upload requirement notification
- ✅ File upload interface
- ✅ Multiple file selection and upload
- ✅ Upload description and metadata
- ✅ Payroll hold clearance
- ✅ Upload history tracking

### Administrative Workflow Tests
Tests comprehensive admin and manager functions.

**Test Scenarios:**
- ✅ Complete payroll processing workflow
- ✅ Employee management and role changes
- ✅ Leave request review and approval
- ✅ System settings management
- ✅ Application review workflow
- ✅ Bulk operations (bonuses, penalties)
- ✅ Audit trail and logging
- ✅ Performance monitoring

### API Integration Tests
Tests REST API endpoints and authentication flows.

**Test Scenarios:**
- ✅ Agent API authentication with HMAC
- ✅ Job management API endpoints
- ✅ Health check endpoints
- ✅ File upload API validation
- ✅ Payroll calculation API
- ✅ Leave request API
- ✅ Rate limiting and security
- ✅ Error handling and edge cases

## 🚀 Running Tests

### Unit Tests
```bash
# Run all unit tests
npm run test

# Run specific service tests
npm run test:services -- eligibility
npm run test:services -- payroll
npm run test:services -- leaves
npm run test:services -- streaks
npm run test:services -- windows

# Run tests with coverage
npm run test:coverage

# Watch mode for development
npm run test -- --watch
```

### End-to-End Tests
```bash
# Run all e2e tests
npm run test:e2e

# Run specific test file
npm run test:e2e -- user-journey.spec.ts

# Run tests in headed mode (see browser)
npm run test:e2e -- --headed

# Run tests on specific browser
npm run test:e2e -- --project=chromium
npm run test:e2e -- --project=firefox
npm run test:e2e -- --project=webkit

# Run mobile tests
npm run test:e2e -- --project="Mobile Chrome"
npm run test:e2e -- --project="Mobile Safari"

# Debug tests
npm run test:e2e -- --debug
```

### Test Reports
```bash
# View Playwright HTML report
npx playwright show-report

# View test coverage report
npm run test:coverage
open coverage/index.html
```

## 🛠️ Test Configuration

### Vitest Configuration
Located in `vitest.config.ts`:
- TypeScript support
- JSX/TSX handling
- Path aliases
- Coverage reporting
- Test environment setup

### Playwright Configuration
Located in `playwright.config.ts`:
- Multi-browser testing (Chrome, Firefox, Safari)
- Mobile and tablet testing
- Screenshot and video capture
- Trace recording
- Parallel execution
- Test timeouts and retries

## 🎯 Test Data Management

### Seeded Test Data
The application includes comprehensive seed data for testing:

**Users:**
- CEO: `ceo@tts-pms.com` / `admin123`
- Manager: `manager@tts-pms.com` / `manager123`
- Employee: `employee1@tts-pms.com` / `employee123`
- New Employee: `newemployee@tts-pms.com` / `newemployee123`
- Candidate: `candidate@tts-pms.com` / `candidate123`

**Sample Data:**
- 6 job gigs with different categories
- System settings and configurations
- Daily summaries and timer sessions
- Bonus and penalty records
- Application examples

### Test Utilities
Located in `tests/e2e/utils/test-helpers.ts`:
- Authentication helpers
- Form filling utilities
- File upload mocks
- OTP verification mocks
- Camera access mocks
- Navigation helpers
- Assertion helpers
- Data generators

## 🔍 Debugging Tests

### Unit Test Debugging
```bash
# Run tests with debug output
npm run test -- --reporter=verbose

# Run single test file
npm run test -- eligibility.test.ts

# Debug specific test
npm run test -- --t "should calculate payroll correctly"
```

### E2E Test Debugging
```bash
# Run in headed mode
npm run test:e2e -- --headed

# Debug mode with DevTools
npm run test:e2e -- --debug

# Slow motion execution
npm run test:e2e -- --headed --slowMo=1000

# Trace viewer
npx playwright show-trace trace.zip
```

### Common Issues and Solutions

#### Database Connection Issues
```bash
# Ensure Docker services are running
docker-compose up -d mysql redis

# Run database migrations
npm run db:migrate

# Seed test data
npm run db:seed
```

#### File Upload Tests Failing
- Ensure MinIO service is running
- Check file size limits
- Verify MIME type handling

#### Authentication Tests Failing
- Verify test user credentials
- Check JWT token expiration
- Ensure session cleanup between tests

## 📊 Test Coverage Goals

### Unit Tests
- **Services**: 90%+ coverage for all business logic
- **Critical Paths**: 100% coverage for payroll, eligibility, and leave calculations
- **Edge Cases**: Comprehensive error handling and validation

### E2E Tests
- **User Journeys**: Complete workflows from registration to employment
- **Admin Functions**: All administrative operations
- **API Endpoints**: Critical API functionality
- **Cross-browser**: Chrome, Firefox, Safari compatibility
- **Mobile**: Responsive design validation

## 🚦 Continuous Integration

### GitHub Actions Integration
```yaml
# .github/workflows/test.yml
name: Tests
on: [push, pull_request]
jobs:
  unit-tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: actions/setup-node@v3
      - run: npm ci
      - run: npm run test:coverage
      
  e2e-tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: actions/setup-node@v3
      - run: npm ci
      - run: npx playwright install
      - run: docker-compose up -d
      - run: npm run test:e2e
```

### Pre-commit Hooks
```json
{
  "husky": {
    "hooks": {
      "pre-commit": "npm run test:services && npm run lint"
    }
  }
}
```

## 📝 Writing New Tests

### Unit Test Template
```typescript
import { describe, it, expect } from 'vitest';
import { yourFunction } from '../your-service';

describe('Your Service', () => {
  describe('yourFunction', () => {
    it('should handle normal case', () => {
      const result = yourFunction(validInput);
      expect(result).toBe(expectedOutput);
    });
    
    it('should handle edge case', () => {
      const result = yourFunction(edgeInput);
      expect(result).toMatchObject(expectedShape);
    });
    
    it('should throw error for invalid input', () => {
      expect(() => yourFunction(invalidInput)).toThrow('Expected error');
    });
  });
});
```

### E2E Test Template
```typescript
import { test, expect } from '@playwright/test';
import { loginAs } from './utils/test-helpers';

test.describe('Feature Name', () => {
  test('should complete workflow', async ({ page }) => {
    await loginAs(page, 'EMPLOYEE');
    
    // Navigate to feature
    await page.goto('/feature');
    
    // Interact with UI
    await page.fill('input[name="field"]', 'value');
    await page.click('button[type="submit"]');
    
    // Assert results
    await expect(page.locator('text=Success')).toBeVisible();
  });
});
```

## 🏆 Best Practices

### Unit Tests
- Test business logic, not implementation details
- Use descriptive test names
- Test edge cases and error conditions
- Mock external dependencies
- Keep tests fast and isolated

### E2E Tests
- Test user workflows, not individual components
- Use data-testid attributes for reliable selectors
- Clean up test data between tests
- Handle async operations properly
- Test critical paths thoroughly

### General
- Write tests before fixing bugs
- Maintain test data consistency
- Use meaningful assertions
- Document complex test scenarios
- Regular test maintenance and updates

---

**Happy Testing! 🎉**

For questions or issues with the testing setup, please refer to the troubleshooting section in the main documentation or contact the development team.
