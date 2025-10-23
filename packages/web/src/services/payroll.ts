import { PAYROLL_CONSTANTS } from '@tts-pms/infra';

/**
 * Payroll Service
 * Handles payroll calculations, streak bonuses, deductions, and eligibility
 */

export interface TimerSummary {
  userId: string;
  weekStart: Date;
  weekEnd: Date;
  totalSeconds: number;
  billableSeconds: number;
  daysWorked: number;
  meetsDailyMinimum: boolean[];
  averageDailyHours: number;
}

export interface PayrollConfig {
  hourlyRate: number;
  securityFund: number;
  streakBonus: number;
  minimumDailyHours: number;
  minimumWeeklyHours: number;
  streakRequirementDays: number;
  firstSalaryGatingDays: number;
}

export interface PayrollCalculation {
  userId: string;
  weekStart: Date;
  weekEnd: Date;
  isEligible: boolean;
  eligibilityReasons: string[];
  
  // Time calculations
  totalHours: number;
  billableHours: number;
  
  // Financial calculations
  baseAmount: number;
  streakBonus: number;
  deductions: {
    securityFund: number;
    penalties: number;
    taxes: number;
    other: Record<string, number>;
  };
  grossAmount: number;
  netAmount: number;
  
  // Streak information
  streakInfo: {
    currentStreak: number;
    qualifiesForBonus: boolean;
    streakStartDate?: Date;
  };
  
  // Metadata
  calculatedAt: Date;
  payPeriod: string;
}

export interface UserPayrollHistory {
  userId: string;
  profileCreatedAt: Date;
  employmentStartDate: Date;
  securityFundDeducted: boolean;
  totalWeeksPaid: number;
  currentStreak: number;
  streakStartDate?: Date;
}

export const DEFAULT_PAYROLL_CONFIG: PayrollConfig = {
  hourlyRate: PAYROLL_CONSTANTS.hourlyRate,
  securityFund: PAYROLL_CONSTANTS.securityFund,
  streakBonus: PAYROLL_CONSTANTS.streakBonus,
  minimumDailyHours: PAYROLL_CONSTANTS.minimumDailyHours,
  minimumWeeklyHours: PAYROLL_CONSTANTS.minimumWeeklyHours,
  streakRequirementDays: PAYROLL_CONSTANTS.streakRequirementDays,
  firstSalaryGatingDays: PAYROLL_CONSTANTS.firstSalaryGatingDays,
};

/**
 * Get the Monday of a given week
 */
export function getWeekStartMonday(date: Date): Date {
  const monday = new Date(date);
  const day = monday.getDay();
  const diff = monday.getDate() - day + (day === 0 ? -6 : 1);
  monday.setDate(diff);
  monday.setHours(0, 0, 0, 0);
  return monday;
}

/**
 * Check if user is eligible for first salary based on profile creation date
 */
export function checkFirstSalaryEligibility(
  profileCreatedAt: Date,
  weekStart: Date,
  config: PayrollConfig = DEFAULT_PAYROLL_CONFIG
): { eligible: boolean; reason?: string } {
  const lastMonday = getWeekStartMonday(weekStart);
  const daysSinceCreation = Math.floor(
    (lastMonday.getTime() - profileCreatedAt.getTime()) / (1000 * 60 * 60 * 24)
  );
  
  if (daysSinceCreation >= config.firstSalaryGatingDays) {
    return { eligible: true };
  }
  
  return {
    eligible: false,
    reason: `Profile must be at least ${config.firstSalaryGatingDays} days old by last Monday. Days since creation: ${daysSinceCreation}`,
  };
}

/**
 * Calculate streak information for a user
 */
export function calculateStreakInfo(
  currentWeekStart: Date,
  payrollHistory: UserPayrollHistory,
  weeklyTimerSummaries: TimerSummary[],
  config: PayrollConfig = DEFAULT_PAYROLL_CONFIG
): {
  currentStreak: number;
  qualifiesForBonus: boolean;
  streakStartDate?: Date;
  streakWeeks: Date[];
} {
  // Sort summaries by week start date
  const sortedSummaries = weeklyTimerSummaries
    .filter(summary => summary.weekStart <= currentWeekStart)
    .sort((a, b) => b.weekStart.getTime() - a.weekStart.getTime());
  
  let currentStreak = 0;
  let streakStartDate: Date | undefined;
  const streakWeeks: Date[] = [];
  
  // Check consecutive weeks backwards from current week
  for (const summary of sortedSummaries) {
    const meetsRequirements = summary.billableSeconds >= (config.minimumWeeklyHours * 3600) &&
                             summary.daysWorked >= 5;
    
    if (meetsRequirements) {
      currentStreak++;
      streakWeeks.unshift(summary.weekStart);
      
      if (!streakStartDate) {
        streakStartDate = summary.weekStart;
      }
    } else {
      break; // Streak broken
    }
  }
  
  // Check if qualifies for streak bonus (28+ days = 4+ weeks)
  const qualifiesForBonus = currentStreak >= Math.ceil(config.streakRequirementDays / 7);
  
  return {
    currentStreak,
    qualifiesForBonus,
    streakStartDate,
    streakWeeks,
  };
}

/**
 * Calculate deductions for a payroll period
 */
export function calculateDeductions(
  baseAmount: number,
  userHistory: UserPayrollHistory,
  penalties: Record<string, number> = {},
  config: PayrollConfig = DEFAULT_PAYROLL_CONFIG
): {
  securityFund: number;
  penalties: number;
  taxes: number;
  other: Record<string, number>;
  total: number;
} {
  const deductions = {
    securityFund: 0,
    penalties: 0,
    taxes: 0,
    other: {} as Record<string, number>,
  };
  
  // Security fund deduction (one-time)
  if (!userHistory.securityFundDeducted) {
    deductions.securityFund = config.securityFund;
  }
  
  // Penalties
  deductions.penalties = Object.values(penalties).reduce((sum, amount) => sum + amount, 0);
  
  // Basic tax calculation (simplified - in reality this would be more complex)
  const taxableAmount = baseAmount - deductions.securityFund;
  if (taxableAmount > 0) {
    // Simplified tax rate
    deductions.taxes = Math.round(taxableAmount * 0.05); // 5% basic tax
  }
  
  const total = deductions.securityFund + deductions.penalties + deductions.taxes +
                Object.values(deductions.other).reduce((sum, amount) => sum + amount, 0);
  
  return { ...deductions, total };
}

/**
 * Main payroll calculation function
 */
export function calculatePayroll(
  timerSummary: TimerSummary,
  userHistory: UserPayrollHistory,
  penalties: Record<string, number> = {},
  config: PayrollConfig = DEFAULT_PAYROLL_CONFIG,
  allWeeklySummaries: TimerSummary[] = []
): PayrollCalculation {
  const eligibilityReasons: string[] = [];
  
  // Check first salary eligibility
  const firstSalaryCheck = checkFirstSalaryEligibility(
    userHistory.profileCreatedAt,
    timerSummary.weekStart,
    config
  );
  
  if (!firstSalaryCheck.eligible) {
    eligibilityReasons.push(firstSalaryCheck.reason!);
  }
  
  // Check minimum hours requirement
  const billableHours = timerSummary.billableSeconds / 3600;
  if (billableHours < config.minimumWeeklyHours) {
    eligibilityReasons.push(
      `Insufficient hours: ${billableHours.toFixed(1)}h (minimum: ${config.minimumWeeklyHours}h)`
    );
  }
  
  const isEligible = eligibilityReasons.length === 0;
  
  // Calculate base amount
  const totalHours = timerSummary.totalSeconds / 3600;
  const baseAmount = Math.round(billableHours * config.hourlyRate);
  
  // Calculate streak information
  const streakInfo = calculateStreakInfo(
    timerSummary.weekStart,
    userHistory,
    allWeeklySummaries,
    config
  );
  
  // Calculate streak bonus
  const streakBonus = streakInfo.qualifiesForBonus ? config.streakBonus : 0;
  
  // Calculate deductions
  const deductions = calculateDeductions(baseAmount, userHistory, penalties, config);
  
  // Calculate final amounts
  const grossAmount = baseAmount + streakBonus;
  const netAmount = Math.max(0, grossAmount - deductions.total);
  
  // Generate pay period string
  const payPeriod = `${timerSummary.weekStart.toISOString().split('T')[0]} to ${timerSummary.weekEnd.toISOString().split('T')[0]}`;
  
  return {
    userId: timerSummary.userId,
    weekStart: timerSummary.weekStart,
    weekEnd: timerSummary.weekEnd,
    isEligible,
    eligibilityReasons,
    
    totalHours: Math.round(totalHours * 100) / 100,
    billableHours: Math.round(billableHours * 100) / 100,
    
    baseAmount,
    streakBonus,
    deductions: {
      securityFund: deductions.securityFund,
      penalties: deductions.penalties,
      taxes: deductions.taxes,
      other: deductions.other,
    },
    grossAmount,
    netAmount,
    
    streakInfo,
    
    calculatedAt: new Date(),
    payPeriod,
  };
}

/**
 * Calculate payroll for multiple users
 */
export function calculateBatchPayroll(
  timerSummaries: TimerSummary[],
  userHistories: Map<string, UserPayrollHistory>,
  penalties: Map<string, Record<string, number>> = new Map(),
  config: PayrollConfig = DEFAULT_PAYROLL_CONFIG
): PayrollCalculation[] {
  const results: PayrollCalculation[] = [];
  
  // Group summaries by user for streak calculation
  const summariesByUser = new Map<string, TimerSummary[]>();
  timerSummaries.forEach(summary => {
    if (!summariesByUser.has(summary.userId)) {
      summariesByUser.set(summary.userId, []);
    }
    summariesByUser.get(summary.userId)!.push(summary);
  });
  
  // Calculate payroll for each user's current week
  timerSummaries.forEach(summary => {
    const userHistory = userHistories.get(summary.userId);
    const userPenalties = penalties.get(summary.userId) || {};
    const allUserSummaries = summariesByUser.get(summary.userId) || [];
    
    if (userHistory) {
      const calculation = calculatePayroll(
        summary,
        userHistory,
        userPenalties,
        config,
        allUserSummaries
      );
      results.push(calculation);
    }
  });
  
  return results;
}

/**
 * Get payroll summary statistics
 */
export function getPayrollSummary(calculations: PayrollCalculation[]): {
  totalEmployees: number;
  eligibleEmployees: number;
  totalGrossAmount: number;
  totalNetAmount: number;
  totalDeductions: number;
  averageHours: number;
  streakBonusRecipients: number;
} {
  const eligible = calculations.filter(calc => calc.isEligible);
  
  return {
    totalEmployees: calculations.length,
    eligibleEmployees: eligible.length,
    totalGrossAmount: eligible.reduce((sum, calc) => sum + calc.grossAmount, 0),
    totalNetAmount: eligible.reduce((sum, calc) => sum + calc.netAmount, 0),
    totalDeductions: eligible.reduce((sum, calc) => 
      sum + calc.deductions.securityFund + calc.deductions.penalties + calc.deductions.taxes, 0
    ),
    averageHours: eligible.length > 0 
      ? eligible.reduce((sum, calc) => sum + calc.billableHours, 0) / eligible.length 
      : 0,
    streakBonusRecipients: eligible.filter(calc => calc.streakBonus > 0).length,
  };
}

/**
 * Format payroll calculation for display
 */
export function formatPayrollCalculation(calc: PayrollCalculation): string {
  const status = calc.isEligible ? '✅ Eligible' : '❌ Not Eligible';
  const amount = calc.isEligible ? `$${calc.netAmount}` : 'N/A';
  
  return `${status} - ${calc.billableHours}h - ${amount}`;
}
