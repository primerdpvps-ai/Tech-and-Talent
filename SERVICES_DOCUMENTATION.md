# TTS PMS Service Layer Documentation

## Overview

The TTS PMS service layer provides pure TypeScript functions for business logic, ensuring deterministic behavior and comprehensive test coverage. All services are stateless and side-effect free, making them ideal for unit testing and reliable business rule enforcement.

## Services

### 1. Windows Service (`windows.ts`)

Handles operational time window validation for TTS operations in PKT timezone.

#### Key Functions
- `checkOperationalWindow()` - Main validation function
- `hasSpecialWindowAccess()` - Check tenure-based special access
- `calculateTenureDays()` - Calculate user tenure
- `isTimeOperational()` - Simple time validation

#### Configuration
```typescript
const DEFAULT_CONFIG = {
  standardWindow: { start: '11:00', end: '02:00' }, // 11:00 AM to 2:00 AM PKT
  specialWindow: { start: '02:00', end: '06:00' },  // 2:00 AM to 6:00 AM PKT
  timezone: 'Asia/Karachi',
  specialAccessTenureDays: 10,
};
```

#### Business Rules
- **Standard Window**: 11:00 AM to 2:00 AM PKT (next day)
- **Special Window**: 2:00 AM to 6:00 AM PKT (requires 10+ days tenure)
- **Timezone**: All calculations in Pakistan Time (UTC+5)

#### Example Usage
```typescript
import { checkOperationalWindow } from '@/services/windows';

const profileCreated = new Date('2024-01-01');
const result = checkOperationalWindow(profileCreated);

if (result.isAllowed) {
  console.log(`Access granted in ${result.currentWindow} window`);
} else {
  console.log(`Access denied: ${result.reason}`);
}
```

### 2. Eligibility Service (`eligibility.ts`)

Handles evaluation logic and decision making for candidate applications.

#### Key Functions
- `applyEvaluation()` - Main evaluation function with scoring
- `meetsMinimumRequirements()` - Basic eligibility check
- `getEvaluationSummary()` - Format results for display

#### Scoring Categories
1. **Age** (10 points) - Age validation and optimal range scoring
2. **Hardware** (25 points) - Device type, RAM, processor evaluation
3. **Internet** (25 points) - Speed, stability, shared users
4. **Availability** (15 points) - Time commitment and windows
5. **Professional** (15 points) - Background and qualifications
6. **Compliance** (10 points) - Confidentiality and typing skills

#### Decision Logic
- **ELIGIBLE**: Score ≥ 80 points
- **PENDING**: Score ≥ 60 points
- **REJECTED**: Score < 60 points or critical failures

#### Example Usage
```typescript
import { applyEvaluation } from '@/services/eligibility';

const evaluationData = {
  age: 25,
  deviceType: 'Desktop',
  ramText: '16GB DDR4',
  // ... other fields
};

const result = applyEvaluation(evaluationData);
console.log(`Status: ${result.status}, Score: ${result.score}`);
```

### 3. Payroll Service (`payroll.ts`)

Handles payroll calculations, streak bonuses, deductions, and eligibility.

#### Key Functions
- `calculatePayroll()` - Main payroll calculation
- `checkFirstSalaryEligibility()` - Validate first salary timing
- `calculateStreakInfo()` - Compute streak bonuses
- `calculateDeductions()` - Apply deductions and penalties

#### Configuration
```typescript
const DEFAULT_PAYROLL_CONFIG = {
  hourlyRate: 125,              // $125 per hour
  securityFund: 1000,           // $1000 one-time deduction
  streakBonus: 500,             // $500 bonus for 28+ day streaks
  minimumDailyHours: 6,         // 6 hours minimum per day
  minimumWeeklyHours: 30,       // 30 hours minimum per week
  streakRequirementDays: 28,    // 28 days for bonus eligibility
  firstSalaryGatingDays: 7,     // 7 days before first salary
};
```

#### Business Rules
- **First Salary Gating**: Profile must be ≥7 days old by last Monday
- **Weekly Minimum**: 30 billable hours required
- **Streak Bonus**: $500 for 28+ consecutive working days
- **Security Fund**: $1000 one-time deduction for new employees
- **Tax Calculation**: 5% basic tax on taxable amount

#### Example Usage
```typescript
import { calculatePayroll } from '@/services/payroll';

const timerSummary = {
  userId: 'user123',
  weekStart: new Date('2024-01-15'),
  weekEnd: new Date('2024-01-21'),
  billableSeconds: 129600, // 36 hours
  // ... other fields
};

const result = calculatePayroll(timerSummary, userHistory);
console.log(`Net Amount: $${result.netAmount}`);
```

### 4. Leaves Service (`leaves.ts`)

Handles leave request validation, notice periods, caps, and penalties.

#### Key Functions
- `validateLeaveRequest()` - Comprehensive leave validation
- `calculateLeaveDays()` - Calculate leave duration
- `checkSuspensionEligibility()` - Auto-suspension logic
- `calculateResignationNotice()` - Resignation validation

#### Leave Types & Notice Requirements
- **SHORT**: ≤0.5 days, 2 hours notice
- **ONE_DAY**: Exactly 1 day, 24 hours notice  
- **LONG**: ≥2 days, 168 hours (7 days) notice

#### Caps & Limits
```typescript
weeklyCaps: { SHORT: 2, ONE_DAY: 1, LONG: 1 }
monthlyCaps: { SHORT: 6, ONE_DAY: 4, LONG: 2 }
maxConsecutiveDays: 7
```

#### Penalties
- **Short Notice**: $25-$100 based on leave type
- **Weekend Leave**: $40 per weekend day
- **Excessive Leave**: $30 per day over monthly limit

#### Example Usage
```typescript
import { validateLeaveRequest } from '@/services/leaves';

const leaveRequest = {
  type: 'ONE_DAY',
  dateFrom: new Date('2024-01-22'),
  dateTo: new Date('2024-01-22'),
  requestedAt: new Date('2024-01-20'),
  // ... other fields
};

const result = validateLeaveRequest(leaveRequest, userHistory);
if (result.isValid) {
  console.log('Leave approved');
} else {
  console.log('Errors:', result.errors);
}
```

### 5. Streaks Service (`streaks.ts`)

Handles streak calculations, nightly recomputation, and timer pause rules.

#### Key Functions
- `calculateUserStreak()` - Compute user streak data
- `detectAutomaticPauses()` - Timer pause detection
- `recomputeAllStreaks()` - Nightly batch processing
- `validateTimerSession()` - Session validation

#### Timer Pause Rule
**Critical Rule**: If no activity ≥40 seconds → automatic pause

#### Streak Calculation
- **Current Streak**: Consecutive working days from today backwards
- **Bonus Eligibility**: 28+ consecutive days
- **Weekend Gaps**: Allowed in streak calculation
- **Daily Minimum**: 6 billable hours required

#### Configuration
```typescript
const DEFAULT_STREAK_CONFIG = {
  minimumDailyHours: 6,
  minimumDaysForBonus: 28,
  activityTimeoutSeconds: 40,    // Timer pause threshold
  weeklyStreakRequirement: 5,    // 5 days per week minimum
};
```

#### Example Usage
```typescript
import { calculateUserStreak, detectAutomaticPauses } from '@/services/streaks';

// Calculate streak
const streak = calculateUserStreak('user123', dailyActivities);
console.log(`Current streak: ${streak.currentStreak} days`);

// Detect pauses
const pauses = detectAutomaticPauses(activityEvents);
console.log(`Detected ${pauses.length} automatic pauses`);
```

## Testing

### Running Tests
```bash
# Run all service tests
npm run test src/services

# Run specific service tests
npm run test src/services/__tests__/windows.test.ts

# Run with coverage
npm run test:coverage
```

### Test Coverage
All services have comprehensive unit tests covering:
- ✅ **Happy path scenarios**
- ✅ **Edge cases and boundary conditions**
- ✅ **Error conditions and validation**
- ✅ **Configuration variations**
- ✅ **Date/time boundary handling**

### Test Structure
```
src/services/__tests__/
├── windows.test.ts      # 15 test cases
├── eligibility.test.ts  # 20 test cases  
├── payroll.test.ts      # 25 test cases
├── leaves.test.ts       # 30 test cases
└── streaks.test.ts      # 35 test cases
```

## Integration Points

### API Integration
Services are used by API routes for business logic:

```typescript
// In API route
import { applyEvaluation } from '@/services/eligibility';

export async function POST(request: NextRequest) {
  const data = await request.json();
  const result = applyEvaluation(data);
  
  // Save to database
  await prisma.evaluation.create({
    data: { ...data, result: result.status }
  });
}
```

### Database Integration
Services work with Prisma models but remain database-agnostic:

```typescript
// Fetch data, apply business logic, save results
const timerData = await prisma.timerSession.findMany({...});
const payrollResult = calculatePayroll(timerData, userHistory);
await prisma.payrollWeek.create({ data: payrollResult });
```

### Scheduled Jobs
Services support batch processing for nightly tasks:

```typescript
// Nightly streak recomputation
const allUserActivities = await fetchAllUserActivities();
const streakResults = recomputeAllStreaks(allUserActivities);
await updateStreakDatabase(streakResults);
```

## Configuration Management

### Environment-Based Config
```typescript
// Override defaults with environment variables
const payrollConfig = {
  ...DEFAULT_PAYROLL_CONFIG,
  hourlyRate: Number(process.env.HOURLY_RATE) || 125,
  streakBonus: Number(process.env.STREAK_BONUS) || 500,
};
```

### Runtime Configuration
```typescript
// Dynamic configuration per calculation
const customConfig = { minimumDailyHours: 8 }; // Stricter requirement
const result = calculatePayroll(data, history, penalties, customConfig);
```

## Error Handling

### Validation Errors
Services return structured error information:

```typescript
const result = validateLeaveRequest(request, history);
if (!result.isValid) {
  return {
    errors: result.errors,        // Blocking errors
    warnings: result.warnings,    // Non-blocking issues
    penalties: result.penalties,  // Financial impacts
  };
}
```

### Graceful Degradation
Services handle edge cases gracefully:

```typescript
// Handle missing data
const streak = calculateUserStreak(userId, activities || []);
// Returns valid streak object with zero values

// Handle invalid dates
const days = calculateLeaveDays(invalidStart, invalidEnd);
// Returns 0 or throws descriptive error
```

## Performance Considerations

### Pure Functions
- No side effects or external dependencies
- Deterministic output for same input
- Easy to cache and memoize

### Batch Processing
- `recomputeAllStreaks()` processes multiple users efficiently
- `calculateBatchPayroll()` handles bulk payroll calculations

### Memory Efficiency
- Streaming processing for large datasets
- Minimal object creation in loops
- Efficient date calculations

## Best Practices

### Function Design
1. **Pure Functions**: No side effects, deterministic
2. **Single Responsibility**: Each function has one clear purpose
3. **Immutable Inputs**: Never modify input parameters
4. **Explicit Returns**: Always return structured results

### Error Handling
1. **Validation First**: Check inputs before processing
2. **Structured Errors**: Return error objects, don't throw
3. **Graceful Degradation**: Handle missing/invalid data
4. **Clear Messages**: Provide actionable error descriptions

### Testing Strategy
1. **Comprehensive Coverage**: Test all code paths
2. **Edge Cases**: Boundary conditions and limits
3. **Mock-Free**: Pure functions don't need mocking
4. **Fast Execution**: Unit tests run in milliseconds

### Documentation
1. **JSDoc Comments**: Document all public functions
2. **Type Definitions**: Comprehensive TypeScript types
3. **Usage Examples**: Show real-world usage
4. **Configuration**: Document all config options

## Future Enhancements

### Planned Features
- **Configurable Penalties**: Runtime penalty configuration
- **Advanced Streak Rules**: Team-based streaks, seasonal bonuses
- **ML Integration**: Predictive eligibility scoring
- **Audit Trails**: Detailed calculation logging

### Performance Optimizations
- **Caching Layer**: Redis caching for expensive calculations
- **Parallel Processing**: Worker threads for batch operations
- **Database Optimization**: Efficient query patterns

### Monitoring & Analytics
- **Performance Metrics**: Function execution times
- **Business Metrics**: Streak distributions, payroll summaries
- **Error Tracking**: Validation failure patterns
