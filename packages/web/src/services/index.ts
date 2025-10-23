/**
 * TTS PMS Service Layer
 * Pure TypeScript functions for business logic
 */

// Windows Service
export * from './windows';

// Eligibility Service  
export * from './eligibility';

// Payroll Service
export * from './payroll';

// Leaves Service
export * from './leaves';

// Streaks Service
export * from './streaks';

// Re-export commonly used types and configs
export type {
  TimeWindow,
  OperationalConfig,
  WindowCheckResult,
} from './windows';

export type {
  EvaluationData,
  EvaluationResult,
  EvaluationConfig,
} from './eligibility';

export type {
  TimerSummary,
  PayrollConfig,
  PayrollCalculation,
  UserPayrollHistory,
} from './payroll';

export type {
  LeaveRequest,
  LeaveValidationResult,
  LeavePenalty,
  LeaveConfig,
  UserLeaveHistory,
} from './leaves';

export type {
  DailyActivity,
  StreakData,
  StreakConfig,
  ActivityEvent,
  TimerPauseRule,
} from './streaks';

// Default configurations
export {
  DEFAULT_CONFIG as DEFAULT_WINDOWS_CONFIG,
} from './windows';

export {
  DEFAULT_EVALUATION_CONFIG,
} from './eligibility';

export {
  DEFAULT_PAYROLL_CONFIG,
} from './payroll';

export {
  DEFAULT_LEAVE_CONFIG,
} from './leaves';

export {
  DEFAULT_STREAK_CONFIG,
} from './streaks';
