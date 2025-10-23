/**
 * Leaves Service
 * Handles leave request validation, notice periods, caps, penalties, and special rules
 */

export interface LeaveRequest {
  type: 'SHORT' | 'ONE_DAY' | 'LONG';
  dateFrom: Date;
  dateTo: Date;
  reason: string;
  requestedAt: Date;
  userId: string;
}

export interface LeaveValidationResult {
  isValid: boolean;
  errors: string[];
  warnings: string[];
  penalties: LeavePenalty[];
  noticeHours: number;
  leaveDays: number;
}

export interface LeavePenalty {
  type: 'short_notice' | 'excessive_leave' | 'pattern_abuse' | 'weekend_penalty';
  amount: number;
  description: string;
}

export interface LeaveConfig {
  noticeRequirements: {
    SHORT: number;      // hours
    ONE_DAY: number;    // hours  
    LONG: number;       // hours
  };
  weeklyCaps: {
    SHORT: number;      // max short leaves per week
    ONE_DAY: number;    // max one-day leaves per week
    LONG: number;       // max long leaves per week
  };
  monthlyCaps: {
    SHORT: number;      // max short leaves per month
    ONE_DAY: number;    // max one-day leaves per month
    LONG: number;       // max long leaves per month
  };
  penalties: {
    shortNotice: {
      SHORT: number;
      ONE_DAY: number;
      LONG: number;
    };
    excessiveLeave: number;  // penalty per day over monthly limit
    weekendPenalty: number;  // penalty for weekend leaves
  };
  maxConsecutiveDays: number;
  resignationNoticeDays: number;
  suspensionThresholds: {
    monthlyLeaves: number;     // auto-suspend if more than X leaves per month
    consecutiveAbsences: number; // auto-suspend if absent X consecutive days
  };
}

export interface UserLeaveHistory {
  userId: string;
  currentWeekLeaves: LeaveRequest[];
  currentMonthLeaves: LeaveRequest[];
  totalLeavesTaken: number;
  lastLeaveDate?: Date;
  suspensionCount: number;
  isCurrentlySuspended: boolean;
}

export const DEFAULT_LEAVE_CONFIG: LeaveConfig = {
  noticeRequirements: {
    SHORT: 2,      // 2 hours notice
    ONE_DAY: 24,   // 24 hours notice
    LONG: 168,     // 7 days notice (168 hours)
  },
  weeklyCaps: {
    SHORT: 2,      // max 2 short leaves per week
    ONE_DAY: 1,    // max 1 one-day leave per week
    LONG: 1,       // max 1 long leave per week
  },
  monthlyCaps: {
    SHORT: 6,      // max 6 short leaves per month
    ONE_DAY: 4,    // max 4 one-day leaves per month
    LONG: 2,       // max 2 long leaves per month
  },
  penalties: {
    shortNotice: {
      SHORT: 25,     // $25 penalty for short notice on short leave
      ONE_DAY: 50,   // $50 penalty for short notice on one-day leave
      LONG: 100,     // $100 penalty for short notice on long leave
    },
    excessiveLeave: 30,    // $30 per day over monthly limit
    weekendPenalty: 40,    // $40 penalty for weekend leaves
  },
  maxConsecutiveDays: 7,
  resignationNoticeDays: 14,
  suspensionThresholds: {
    monthlyLeaves: 10,
    consecutiveAbsences: 5,
  },
};

/**
 * Calculate the number of days between two dates (inclusive)
 */
export function calculateLeaveDays(dateFrom: Date, dateTo: Date): number {
  const timeDiff = dateTo.getTime() - dateFrom.getTime();
  return Math.ceil(timeDiff / (1000 * 60 * 60 * 24)) + 1;
}

/**
 * Calculate notice hours between request time and leave start
 */
export function calculateNoticeHours(requestedAt: Date, leaveStart: Date): number {
  const timeDiff = leaveStart.getTime() - requestedAt.getTime();
  return Math.floor(timeDiff / (1000 * 60 * 60));
}

/**
 * Check if a date falls on weekend
 */
export function isWeekend(date: Date): boolean {
  const day = date.getDay();
  return day === 0 || day === 6; // Sunday or Saturday
}

/**
 * Get week start (Monday) for a given date
 */
export function getWeekStart(date: Date): Date {
  const monday = new Date(date);
  const day = monday.getDay();
  const diff = monday.getDate() - day + (day === 0 ? -6 : 1);
  monday.setDate(diff);
  monday.setHours(0, 0, 0, 0);
  return monday;
}

/**
 * Get month start for a given date
 */
export function getMonthStart(date: Date): Date {
  return new Date(date.getFullYear(), date.getMonth(), 1);
}

/**
 * Validate leave request against notice requirements
 */
function validateNoticeRequirement(
  request: LeaveRequest,
  config: LeaveConfig
): { valid: boolean; penalty?: LeavePenalty } {
  const noticeHours = calculateNoticeHours(request.requestedAt, request.dateFrom);
  const requiredNotice = config.noticeRequirements[request.type];
  
  if (noticeHours >= requiredNotice) {
    return { valid: true };
  }
  
  return {
    valid: false,
    penalty: {
      type: 'short_notice',
      amount: config.penalties.shortNotice[request.type],
      description: `Insufficient notice: ${noticeHours}h provided, ${requiredNotice}h required`,
    },
  };
}

/**
 * Validate leave request against weekly caps
 */
function validateWeeklyCaps(
  request: LeaveRequest,
  userHistory: UserLeaveHistory,
  config: LeaveConfig
): { valid: boolean; error?: string } {
  const weekStart = getWeekStart(request.dateFrom);
  const currentWeekLeaves = userHistory.currentWeekLeaves.filter(
    leave => getWeekStart(leave.dateFrom).getTime() === weekStart.getTime() && 
             leave.type === request.type
  );
  
  const weeklyLimit = config.weeklyCaps[request.type];
  
  if (currentWeekLeaves.length >= weeklyLimit) {
    return {
      valid: false,
      error: `Weekly limit exceeded: ${currentWeekLeaves.length}/${weeklyLimit} ${request.type} leaves this week`,
    };
  }
  
  return { valid: true };
}

/**
 * Validate leave request against monthly caps
 */
function validateMonthlyCaps(
  request: LeaveRequest,
  userHistory: UserLeaveHistory,
  config: LeaveConfig
): { valid: boolean; penalty?: LeavePenalty } {
  const monthStart = getMonthStart(request.dateFrom);
  const currentMonthLeaves = userHistory.currentMonthLeaves.filter(
    leave => getMonthStart(leave.dateFrom).getTime() === monthStart.getTime() && 
             leave.type === request.type
  );
  
  const monthlyLimit = config.monthlyCaps[request.type];
  
  if (currentMonthLeaves.length >= monthlyLimit) {
    const excessDays = calculateLeaveDays(request.dateFrom, request.dateTo);
    return {
      valid: false,
      penalty: {
        type: 'excessive_leave',
        amount: excessDays * config.penalties.excessiveLeave,
        description: `Monthly limit exceeded: ${currentMonthLeaves.length}/${monthlyLimit} ${request.type} leaves this month`,
      },
    };
  }
  
  return { valid: true };
}

/**
 * Check for weekend penalty
 */
function checkWeekendPenalty(
  request: LeaveRequest,
  config: LeaveConfig
): LeavePenalty | null {
  const leaveDays = calculateLeaveDays(request.dateFrom, request.dateTo);
  let weekendDays = 0;
  
  for (let i = 0; i < leaveDays; i++) {
    const checkDate = new Date(request.dateFrom);
    checkDate.setDate(checkDate.getDate() + i);
    if (isWeekend(checkDate)) {
      weekendDays++;
    }
  }
  
  if (weekendDays > 0) {
    return {
      type: 'weekend_penalty',
      amount: weekendDays * config.penalties.weekendPenalty,
      description: `Weekend leave penalty: ${weekendDays} weekend days`,
    };
  }
  
  return null;
}

/**
 * Check for leave pattern abuse
 */
function checkPatternAbuse(
  request: LeaveRequest,
  userHistory: UserLeaveHistory
): { warning?: string; penalty?: LeavePenalty } {
  const warnings: string[] = [];
  
  // Check for frequent short leaves
  const recentShortLeaves = userHistory.currentMonthLeaves.filter(
    leave => leave.type === 'SHORT'
  ).length;
  
  if (recentShortLeaves >= 4 && request.type === 'SHORT') {
    warnings.push('Frequent short leaves detected - consider planning ahead');
  }
  
  // Check for consecutive leave requests
  if (userHistory.lastLeaveDate) {
    const daysSinceLastLeave = Math.floor(
      (request.dateFrom.getTime() - userHistory.lastLeaveDate.getTime()) / (1000 * 60 * 60 * 24)
    );
    
    if (daysSinceLastLeave <= 3) {
      warnings.push('Consecutive leave requests may indicate pattern abuse');
    }
  }
  
  return { warning: warnings.join('; ') };
}

/**
 * Main leave validation function
 */
export function validateLeaveRequest(
  request: LeaveRequest,
  userHistory: UserLeaveHistory,
  config: LeaveConfig = DEFAULT_LEAVE_CONFIG
): LeaveValidationResult {
  const errors: string[] = [];
  const warnings: string[] = [];
  const penalties: LeavePenalty[] = [];
  
  // Basic validation
  if (request.dateFrom >= request.dateTo) {
    errors.push('Leave end date must be after start date');
  }
  
  if (request.dateFrom <= new Date()) {
    errors.push('Leave cannot be requested for past dates');
  }
  
  const leaveDays = calculateLeaveDays(request.dateFrom, request.dateTo);
  const noticeHours = calculateNoticeHours(request.requestedAt, request.dateFrom);
  
  // Validate leave type consistency
  if (request.type === 'SHORT' && leaveDays > 0.5) {
    errors.push('Short leave cannot exceed half a day');
  } else if (request.type === 'ONE_DAY' && leaveDays !== 1) {
    errors.push('One-day leave must be exactly one day');
  } else if (request.type === 'LONG' && leaveDays < 2) {
    errors.push('Long leave must be at least 2 days');
  }
  
  // Check maximum consecutive days
  if (leaveDays > config.maxConsecutiveDays) {
    errors.push(`Leave cannot exceed ${config.maxConsecutiveDays} consecutive days`);
  }
  
  // Check if user is suspended
  if (userHistory.isCurrentlySuspended) {
    errors.push('Cannot request leave while suspended');
  }
  
  // Validate notice requirements
  const noticeValidation = validateNoticeRequirement(request, config);
  if (!noticeValidation.valid && noticeValidation.penalty) {
    penalties.push(noticeValidation.penalty);
    warnings.push(noticeValidation.penalty.description);
  }
  
  // Validate weekly caps
  const weeklyValidation = validateWeeklyCaps(request, userHistory, config);
  if (!weeklyValidation.valid) {
    errors.push(weeklyValidation.error!);
  }
  
  // Validate monthly caps
  const monthlyValidation = validateMonthlyCaps(request, userHistory, config);
  if (!monthlyValidation.valid && monthlyValidation.penalty) {
    penalties.push(monthlyValidation.penalty);
    warnings.push(monthlyValidation.penalty.description);
  }
  
  // Check weekend penalty
  const weekendPenalty = checkWeekendPenalty(request, config);
  if (weekendPenalty) {
    penalties.push(weekendPenalty);
    warnings.push(weekendPenalty.description);
  }
  
  // Check pattern abuse
  const patternCheck = checkPatternAbuse(request, userHistory);
  if (patternCheck.warning) {
    warnings.push(patternCheck.warning);
  }
  if (patternCheck.penalty) {
    penalties.push(patternCheck.penalty);
  }
  
  return {
    isValid: errors.length === 0,
    errors,
    warnings,
    penalties,
    noticeHours,
    leaveDays,
  };
}

/**
 * Check if user should be suspended based on leave history
 */
export function checkSuspensionEligibility(
  userHistory: UserLeaveHistory,
  config: LeaveConfig = DEFAULT_LEAVE_CONFIG
): { shouldSuspend: boolean; reason?: string } {
  // Check monthly leave threshold
  if (userHistory.currentMonthLeaves.length >= config.suspensionThresholds.monthlyLeaves) {
    return {
      shouldSuspend: true,
      reason: `Exceeded monthly leave limit: ${userHistory.currentMonthLeaves.length}/${config.suspensionThresholds.monthlyLeaves}`,
    };
  }
  
  // Check consecutive absences (would need additional data about actual absences)
  // This would typically be checked against attendance records
  
  return { shouldSuspend: false };
}

/**
 * Calculate resignation notice requirement
 */
export function calculateResignationNotice(
  employmentStartDate: Date,
  resignationDate: Date,
  config: LeaveConfig = DEFAULT_LEAVE_CONFIG
): { requiredNoticeDays: number; actualNoticeDays: number; isValid: boolean } {
  const actualNoticeDays = Math.floor(
    (resignationDate.getTime() - new Date().getTime()) / (1000 * 60 * 60 * 24)
  );
  
  return {
    requiredNoticeDays: config.resignationNoticeDays,
    actualNoticeDays,
    isValid: actualNoticeDays >= config.resignationNoticeDays,
  };
}

/**
 * Get leave summary for a user
 */
export function getUserLeaveSummary(
  userHistory: UserLeaveHistory,
  config: LeaveConfig = DEFAULT_LEAVE_CONFIG
): {
  weeklyUsage: Record<string, { used: number; limit: number }>;
  monthlyUsage: Record<string, { used: number; limit: number }>;
  totalPenalties: number;
  suspensionRisk: boolean;
} {
  const weeklyUsage = {
    SHORT: { used: userHistory.currentWeekLeaves.filter(l => l.type === 'SHORT').length, limit: config.weeklyCaps.SHORT },
    ONE_DAY: { used: userHistory.currentWeekLeaves.filter(l => l.type === 'ONE_DAY').length, limit: config.weeklyCaps.ONE_DAY },
    LONG: { used: userHistory.currentWeekLeaves.filter(l => l.type === 'LONG').length, limit: config.weeklyCaps.LONG },
  };
  
  const monthlyUsage = {
    SHORT: { used: userHistory.currentMonthLeaves.filter(l => l.type === 'SHORT').length, limit: config.monthlyCaps.SHORT },
    ONE_DAY: { used: userHistory.currentMonthLeaves.filter(l => l.type === 'ONE_DAY').length, limit: config.monthlyCaps.ONE_DAY },
    LONG: { used: userHistory.currentMonthLeaves.filter(l => l.type === 'LONG').length, limit: config.monthlyCaps.LONG },
  };
  
  const suspensionCheck = checkSuspensionEligibility(userHistory, config);
  
  return {
    weeklyUsage,
    monthlyUsage,
    totalPenalties: 0, // Would be calculated from actual penalty records
    suspensionRisk: suspensionCheck.shouldSuspend,
  };
}
