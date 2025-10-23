import { describe, it, expect } from 'vitest';
import {
  calculatePayroll,
  checkFirstSalaryEligibility,
  calculateStreakInfo,
  getWeekStartMonday,
  calculateDeductions,
  getPayrollSummary,
  DEFAULT_PAYROLL_CONFIG,
  type TimerSummary,
  type UserPayrollHistory,
} from '../payroll';

describe('Payroll Service', () => {
  const mockTimerSummary: TimerSummary = {
    userId: 'user123',
    weekStart: new Date('2024-01-15'), // Monday
    weekEnd: new Date('2024-01-21'),   // Sunday
    totalSeconds: 144000, // 40 hours
    billableSeconds: 129600, // 36 hours
    daysWorked: 5,
    meetsDailyMinimum: [true, true, true, true, true, false, false],
    averageDailyHours: 7.2,
  };

  const mockUserHistory: UserPayrollHistory = {
    userId: 'user123',
    profileCreatedAt: new Date('2024-01-01'),
    employmentStartDate: new Date('2024-01-08'),
    securityFundDeducted: false,
    totalWeeksPaid: 0,
    currentStreak: 0,
  };

  describe('getWeekStartMonday', () => {
    it('should return Monday for any day of the week', () => {
      const wednesday = new Date('2024-01-17'); // Wednesday
      const monday = getWeekStartMonday(wednesday);
      
      expect(monday.getDay()).toBe(1); // Monday
      expect(monday.toDateString()).toBe('Mon Jan 15 2024');
    });
    
    it('should return same date if already Monday', () => {
      const monday = new Date('2024-01-15'); // Monday
      const result = getWeekStartMonday(monday);
      
      expect(result.toDateString()).toBe(monday.toDateString());
    });
    
    it('should handle Sunday correctly', () => {
      const sunday = new Date('2024-01-21'); // Sunday
      const monday = getWeekStartMonday(sunday);
      
      expect(monday.getDay()).toBe(1); // Monday
      expect(monday.toDateString()).toBe('Mon Jan 15 2024');
    });
  });

  describe('checkFirstSalaryEligibility', () => {
    it('should be eligible after 7 days', () => {
      const profileCreated = new Date('2024-01-01');
      const weekStart = new Date('2024-01-15'); // 14 days later
      
      const result = checkFirstSalaryEligibility(profileCreated, weekStart);
      expect(result.eligible).toBe(true);
    });
    
    it('should not be eligible before 7 days', () => {
      const profileCreated = new Date('2024-01-10');
      const weekStart = new Date('2024-01-15'); // 5 days later
      
      const result = checkFirstSalaryEligibility(profileCreated, weekStart);
      expect(result.eligible).toBe(false);
      expect(result.reason).toContain('Profile must be at least 7 days old');
    });
    
    it('should calculate from last Monday correctly', () => {
      const profileCreated = new Date('2024-01-01');
      const weekStart = new Date('2024-01-17'); // Wednesday, but should check from Monday (Jan 15)
      
      const result = checkFirstSalaryEligibility(profileCreated, weekStart);
      expect(result.eligible).toBe(true);
    });
  });

  describe('calculateDeductions', () => {
    it('should deduct security fund for new employee', () => {
      const baseAmount = 5000;
      const userHistory = { ...mockUserHistory, securityFundDeducted: false };
      
      const deductions = calculateDeductions(baseAmount, userHistory);
      
      expect(deductions.securityFund).toBe(1000);
      expect(deductions.taxes).toBeGreaterThan(0);
      expect(deductions.total).toBe(deductions.securityFund + deductions.penalties + deductions.taxes);
    });
    
    it('should not deduct security fund if already deducted', () => {
      const baseAmount = 5000;
      const userHistory = { ...mockUserHistory, securityFundDeducted: true };
      
      const deductions = calculateDeductions(baseAmount, userHistory);
      
      expect(deductions.securityFund).toBe(0);
    });
    
    it('should include penalties', () => {
      const baseAmount = 5000;
      const penalties = { 'late_penalty': 50, 'absence_penalty': 25 };
      
      const deductions = calculateDeductions(baseAmount, mockUserHistory, penalties);
      
      expect(deductions.penalties).toBe(75);
    });
  });

  describe('calculateStreakInfo', () => {
    it('should calculate streak correctly for consecutive weeks', () => {
      const currentWeek = new Date('2024-01-15');
      const weeklySummaries: TimerSummary[] = [
        { ...mockTimerSummary, weekStart: new Date('2024-01-08'), billableSeconds: 108000 }, // 30 hours
        { ...mockTimerSummary, weekStart: new Date('2024-01-15'), billableSeconds: 129600 }, // 36 hours
      ];
      
      const streakInfo = calculateStreakInfo(currentWeek, mockUserHistory, weeklySummaries);
      
      expect(streakInfo.currentStreak).toBe(2);
      expect(streakInfo.qualifiesForBonus).toBe(false); // Need 4+ weeks
    });
    
    it('should qualify for bonus after 4 weeks', () => {
      const currentWeek = new Date('2024-02-05');
      const weeklySummaries: TimerSummary[] = [
        { ...mockTimerSummary, weekStart: new Date('2024-01-15'), billableSeconds: 108000, daysWorked: 5 },
        { ...mockTimerSummary, weekStart: new Date('2024-01-22'), billableSeconds: 108000, daysWorked: 5 },
        { ...mockTimerSummary, weekStart: new Date('2024-01-29'), billableSeconds: 108000, daysWorked: 5 },
        { ...mockTimerSummary, weekStart: new Date('2024-02-05'), billableSeconds: 108000, daysWorked: 5 },
      ];
      
      const streakInfo = calculateStreakInfo(currentWeek, mockUserHistory, weeklySummaries);
      
      expect(streakInfo.currentStreak).toBe(4);
      expect(streakInfo.qualifiesForBonus).toBe(true);
    });
  });

  describe('calculatePayroll', () => {
    it('should calculate basic payroll correctly', () => {
      const result = calculatePayroll(mockTimerSummary, mockUserHistory);
      
      expect(result.isEligible).toBe(true);
      expect(result.billableHours).toBe(36);
      expect(result.baseAmount).toBe(36 * 125); // 36 hours * $125/hour
      expect(result.streakBonus).toBe(0); // No streak yet
      expect(result.netAmount).toBeLessThan(result.grossAmount); // After deductions
    });
    
    it('should be ineligible if insufficient hours', () => {
      const lowHoursSummary = { ...mockTimerSummary, billableSeconds: 72000 }; // 20 hours
      
      const result = calculatePayroll(lowHoursSummary, mockUserHistory);
      
      expect(result.isEligible).toBe(false);
      expect(result.eligibilityReasons).toContain('Insufficient hours: 20.0h (minimum: 30h)');
    });
    
    it('should be ineligible if profile too new', () => {
      const newUserHistory = { ...mockUserHistory, profileCreatedAt: new Date('2024-01-14') };
      
      const result = calculatePayroll(mockTimerSummary, newUserHistory);
      
      expect(result.isEligible).toBe(false);
      expect(result.eligibilityReasons[0]).toContain('Profile must be at least 7 days old');
    });
    
    it('should apply streak bonus when qualified', () => {
      const streakSummaries: TimerSummary[] = [
        { ...mockTimerSummary, weekStart: new Date('2024-01-01'), billableSeconds: 108000, daysWorked: 5 },
        { ...mockTimerSummary, weekStart: new Date('2024-01-08'), billableSeconds: 108000, daysWorked: 5 },
        { ...mockTimerSummary, weekStart: new Date('2024-01-15'), billableSeconds: 108000, daysWorked: 5 },
        { ...mockTimerSummary, weekStart: new Date('2024-01-22'), billableSeconds: 108000, daysWorked: 5 },
      ];
      
      const result = calculatePayroll(mockTimerSummary, mockUserHistory, {}, DEFAULT_PAYROLL_CONFIG, streakSummaries);
      
      expect(result.streakBonus).toBe(500);
      expect(result.streakInfo.qualifiesForBonus).toBe(true);
    });
    
    it('should handle penalties correctly', () => {
      const penalties = { 'tardiness': 100 };
      
      const result = calculatePayroll(mockTimerSummary, mockUserHistory, penalties);
      
      expect(result.deductions.penalties).toBe(100);
      expect(result.netAmount).toBe(result.grossAmount - result.deductions.securityFund - result.deductions.penalties - result.deductions.taxes);
    });
  });

  describe('getPayrollSummary', () => {
    it('should calculate summary statistics correctly', () => {
      const calculations = [
        calculatePayroll(mockTimerSummary, mockUserHistory),
        calculatePayroll({ ...mockTimerSummary, userId: 'user456', billableSeconds: 108000 }, { ...mockUserHistory, userId: 'user456' }),
      ];
      
      const summary = getPayrollSummary(calculations);
      
      expect(summary.totalEmployees).toBe(2);
      expect(summary.eligibleEmployees).toBe(2);
      expect(summary.totalGrossAmount).toBeGreaterThan(0);
      expect(summary.averageHours).toBeGreaterThan(0);
    });
    
    it('should handle ineligible employees', () => {
      const lowHoursSummary = { ...mockTimerSummary, billableSeconds: 36000 }; // 10 hours
      const calculations = [
        calculatePayroll(mockTimerSummary, mockUserHistory), // Eligible
        calculatePayroll(lowHoursSummary, mockUserHistory), // Ineligible
      ];
      
      const summary = getPayrollSummary(calculations);
      
      expect(summary.totalEmployees).toBe(2);
      expect(summary.eligibleEmployees).toBe(1);
    });
  });

  describe('edge cases', () => {
    it('should handle zero hours gracefully', () => {
      const zeroHoursSummary = { ...mockTimerSummary, totalSeconds: 0, billableSeconds: 0 };
      
      const result = calculatePayroll(zeroHoursSummary, mockUserHistory);
      
      expect(result.baseAmount).toBe(0);
      expect(result.isEligible).toBe(false);
    });
    
    it('should ensure net amount is never negative', () => {
      const highPenalties = { 'major_violation': 10000 };
      
      const result = calculatePayroll(mockTimerSummary, mockUserHistory, highPenalties);
      
      expect(result.netAmount).toBeGreaterThanOrEqual(0);
    });
    
    it('should handle weekend work periods', () => {
      const weekendSummary = {
        ...mockTimerSummary,
        weekStart: new Date('2024-01-13'), // Saturday
        weekEnd: new Date('2024-01-19'),   // Friday
      };
      
      const result = calculatePayroll(weekendSummary, mockUserHistory);
      
      expect(result.isEligible).toBe(true); // Should still be eligible
    });
  });
});
