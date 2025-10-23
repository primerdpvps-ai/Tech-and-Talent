import { describe, it, expect } from 'vitest';
import {
  validateLeaveRequest,
  calculateLeaveDays,
  calculateNoticeHours,
  isWeekend,
  checkSuspensionEligibility,
  calculateResignationNotice,
  getUserLeaveSummary,
  DEFAULT_LEAVE_CONFIG,
  type LeaveRequest,
  type UserLeaveHistory,
} from '../leaves';

describe('Leaves Service', () => {
  const mockLeaveRequest: LeaveRequest = {
    type: 'ONE_DAY',
    dateFrom: new Date('2024-01-20'), // Saturday
    dateTo: new Date('2024-01-20'),   // Same day
    reason: 'Personal appointment',
    requestedAt: new Date('2024-01-18'), // Thursday, 2 days notice
    userId: 'user123',
  };

  const mockUserHistory: UserLeaveHistory = {
    userId: 'user123',
    currentWeekLeaves: [],
    currentMonthLeaves: [],
    totalLeavesTaken: 0,
    suspensionCount: 0,
    isCurrentlySuspended: false,
  };

  describe('calculateLeaveDays', () => {
    it('should calculate single day leave correctly', () => {
      const dateFrom = new Date('2024-01-20');
      const dateTo = new Date('2024-01-20');
      
      const days = calculateLeaveDays(dateFrom, dateTo);
      expect(days).toBe(1);
    });
    
    it('should calculate multi-day leave correctly', () => {
      const dateFrom = new Date('2024-01-20');
      const dateTo = new Date('2024-01-22');
      
      const days = calculateLeaveDays(dateFrom, dateTo);
      expect(days).toBe(3);
    });
    
    it('should handle overnight leave', () => {
      const dateFrom = new Date('2024-01-20T10:00:00');
      const dateTo = new Date('2024-01-21T14:00:00');
      
      const days = calculateLeaveDays(dateFrom, dateTo);
      expect(days).toBe(2);
    });
  });

  describe('calculateNoticeHours', () => {
    it('should calculate notice hours correctly', () => {
      const requestedAt = new Date('2024-01-18T10:00:00');
      const leaveStart = new Date('2024-01-20T10:00:00');
      
      const hours = calculateNoticeHours(requestedAt, leaveStart);
      expect(hours).toBe(48); // 2 days = 48 hours
    });
    
    it('should handle same day requests', () => {
      const requestedAt = new Date('2024-01-20T08:00:00');
      const leaveStart = new Date('2024-01-20T10:00:00');
      
      const hours = calculateNoticeHours(requestedAt, leaveStart);
      expect(hours).toBe(2);
    });
  });

  describe('isWeekend', () => {
    it('should identify Saturday as weekend', () => {
      const saturday = new Date('2024-01-20'); // Saturday
      expect(isWeekend(saturday)).toBe(true);
    });
    
    it('should identify Sunday as weekend', () => {
      const sunday = new Date('2024-01-21'); // Sunday
      expect(isWeekend(sunday)).toBe(true);
    });
    
    it('should identify weekdays correctly', () => {
      const monday = new Date('2024-01-22'); // Monday
      expect(isWeekend(monday)).toBe(false);
    });
  });

  describe('validateLeaveRequest', () => {
    it('should validate proper leave request', () => {
      const validRequest = {
        ...mockLeaveRequest,
        dateFrom: new Date('2024-01-22'), // Monday, weekday
        dateTo: new Date('2024-01-22'),
        requestedAt: new Date('2024-01-20'), // 2 days notice
      };
      
      const result = validateLeaveRequest(validRequest, mockUserHistory);
      
      expect(result.isValid).toBe(true);
      expect(result.errors).toHaveLength(0);
      expect(result.leaveDays).toBe(1);
    });
    
    it('should reject leave with insufficient notice', () => {
      const shortNoticeRequest = {
        ...mockLeaveRequest,
        type: 'LONG' as const,
        dateFrom: new Date('2024-01-22'),
        dateTo: new Date('2024-01-24'),
        requestedAt: new Date('2024-01-21'), // Only 1 day notice for LONG leave
      };
      
      const result = validateLeaveRequest(shortNoticeRequest, mockUserHistory);
      
      expect(result.penalties).toHaveLength(1);
      expect(result.penalties[0].type).toBe('short_notice');
      expect(result.warnings[0]).toContain('Insufficient notice');
    });
    
    it('should reject past date leave requests', () => {
      const pastRequest = {
        ...mockLeaveRequest,
        dateFrom: new Date('2024-01-15'), // Past date
        requestedAt: new Date('2024-01-18'),
      };
      
      const result = validateLeaveRequest(pastRequest, mockUserHistory);
      
      expect(result.isValid).toBe(false);
      expect(result.errors).toContain('Leave cannot be requested for past dates');
    });
    
    it('should reject invalid date range', () => {
      const invalidRequest = {
        ...mockLeaveRequest,
        dateFrom: new Date('2024-01-22'),
        dateTo: new Date('2024-01-20'), // End before start
      };
      
      const result = validateLeaveRequest(invalidRequest, mockUserHistory);
      
      expect(result.isValid).toBe(false);
      expect(result.errors).toContain('Leave end date must be after start date');
    });
    
    it('should validate leave type consistency', () => {
      const inconsistentRequest = {
        ...mockLeaveRequest,
        type: 'SHORT' as const,
        dateFrom: new Date('2024-01-22'),
        dateTo: new Date('2024-01-24'), // 3 days for SHORT leave
      };
      
      const result = validateLeaveRequest(inconsistentRequest, mockUserHistory);
      
      expect(result.isValid).toBe(false);
      expect(result.errors).toContain('Short leave cannot exceed half a day');
    });
    
    it('should apply weekend penalty', () => {
      const weekendRequest = {
        ...mockLeaveRequest,
        dateFrom: new Date('2024-01-20'), // Saturday
        dateTo: new Date('2024-01-21'),   // Sunday
      };
      
      const result = validateLeaveRequest(weekendRequest, mockUserHistory);
      
      const weekendPenalty = result.penalties.find(p => p.type === 'weekend_penalty');
      expect(weekendPenalty).toBeDefined();
      expect(weekendPenalty!.amount).toBe(80); // 2 weekend days * $40
    });
    
    it('should enforce weekly caps', () => {
      const historyWithWeeklyLeaves: UserLeaveHistory = {
        ...mockUserHistory,
        currentWeekLeaves: [
          { ...mockLeaveRequest, type: 'ONE_DAY', dateFrom: new Date('2024-01-15') },
        ],
      };
      
      const result = validateLeaveRequest(mockLeaveRequest, historyWithWeeklyLeaves);
      
      expect(result.isValid).toBe(false);
      expect(result.errors[0]).toContain('Weekly limit exceeded');
    });
    
    it('should enforce monthly caps with penalty', () => {
      const historyWithMonthlyLeaves: UserLeaveHistory = {
        ...mockUserHistory,
        currentMonthLeaves: Array(4).fill(null).map((_, i) => ({
          ...mockLeaveRequest,
          type: 'ONE_DAY' as const,
          dateFrom: new Date(`2024-01-${i + 1}`),
        })),
      };
      
      const result = validateLeaveRequest(mockLeaveRequest, historyWithMonthlyLeaves);
      
      const excessivePenalty = result.penalties.find(p => p.type === 'excessive_leave');
      expect(excessivePenalty).toBeDefined();
    });
    
    it('should reject leave for suspended users', () => {
      const suspendedUserHistory = { ...mockUserHistory, isCurrentlySuspended: true };
      
      const result = validateLeaveRequest(mockLeaveRequest, suspendedUserHistory);
      
      expect(result.isValid).toBe(false);
      expect(result.errors).toContain('Cannot request leave while suspended');
    });
  });

  describe('checkSuspensionEligibility', () => {
    it('should not suspend user with normal leave pattern', () => {
      const result = checkSuspensionEligibility(mockUserHistory);
      
      expect(result.shouldSuspend).toBe(false);
    });
    
    it('should suspend user with excessive monthly leaves', () => {
      const excessiveHistory: UserLeaveHistory = {
        ...mockUserHistory,
        currentMonthLeaves: Array(12).fill(null).map((_, i) => ({
          ...mockLeaveRequest,
          dateFrom: new Date(`2024-01-${i + 1}`),
        })),
      };
      
      const result = checkSuspensionEligibility(excessiveHistory);
      
      expect(result.shouldSuspend).toBe(true);
      expect(result.reason).toContain('Exceeded monthly leave limit');
    });
  });

  describe('calculateResignationNotice', () => {
    it('should validate sufficient resignation notice', () => {
      const employmentStart = new Date('2024-01-01');
      const resignationDate = new Date('2024-02-15'); // 15+ days notice
      
      const result = calculateResignationNotice(employmentStart, resignationDate);
      
      expect(result.isValid).toBe(true);
      expect(result.requiredNoticeDays).toBe(14);
      expect(result.actualNoticeDays).toBeGreaterThanOrEqual(14);
    });
    
    it('should reject insufficient resignation notice', () => {
      const employmentStart = new Date('2024-01-01');
      const resignationDate = new Date('2024-02-05'); // Less than 14 days
      
      const result = calculateResignationNotice(employmentStart, resignationDate);
      
      expect(result.isValid).toBe(false);
      expect(result.actualNoticeDays).toBeLessThan(14);
    });
  });

  describe('getUserLeaveSummary', () => {
    it('should calculate usage statistics correctly', () => {
      const historyWithLeaves: UserLeaveHistory = {
        ...mockUserHistory,
        currentWeekLeaves: [
          { ...mockLeaveRequest, type: 'SHORT' },
        ],
        currentMonthLeaves: [
          { ...mockLeaveRequest, type: 'SHORT' },
          { ...mockLeaveRequest, type: 'ONE_DAY' },
        ],
      };
      
      const summary = getUserLeaveSummary(historyWithLeaves);
      
      expect(summary.weeklyUsage.SHORT.used).toBe(1);
      expect(summary.weeklyUsage.SHORT.limit).toBe(2);
      expect(summary.monthlyUsage.ONE_DAY.used).toBe(1);
      expect(summary.suspensionRisk).toBe(false);
    });
    
    it('should identify suspension risk', () => {
      const riskHistory: UserLeaveHistory = {
        ...mockUserHistory,
        currentMonthLeaves: Array(10).fill(null).map(() => ({ ...mockLeaveRequest })),
      };
      
      const summary = getUserLeaveSummary(riskHistory);
      
      expect(summary.suspensionRisk).toBe(true);
    });
  });

  describe('edge cases', () => {
    it('should handle leap year dates', () => {
      const leapYearRequest = {
        ...mockLeaveRequest,
        dateFrom: new Date('2024-02-29'), // Leap year
        dateTo: new Date('2024-02-29'),
      };
      
      const result = validateLeaveRequest(leapYearRequest, mockUserHistory);
      expect(result.leaveDays).toBe(1);
    });
    
    it('should handle year boundary leaves', () => {
      const yearBoundaryRequest = {
        ...mockLeaveRequest,
        dateFrom: new Date('2023-12-30'),
        dateTo: new Date('2024-01-02'),
        requestedAt: new Date('2023-12-20'),
      };
      
      const result = validateLeaveRequest(yearBoundaryRequest, mockUserHistory);
      expect(result.leaveDays).toBe(4);
    });
    
    it('should handle maximum consecutive days limit', () => {
      const longLeaveRequest = {
        ...mockLeaveRequest,
        type: 'LONG' as const,
        dateFrom: new Date('2024-01-22'),
        dateTo: new Date('2024-01-30'), // 9 days (exceeds 7-day limit)
        requestedAt: new Date('2024-01-01'), // Plenty of notice
      };
      
      const result = validateLeaveRequest(longLeaveRequest, mockUserHistory);
      
      expect(result.isValid).toBe(false);
      expect(result.errors).toContain('Leave cannot exceed 7 consecutive days');
    });
  });
});
