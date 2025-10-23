import { describe, it, expect } from 'vitest';
import {
  detectAutomaticPauses,
  calculateDailyActivity,
  calculateUserStreak,
  recomputeAllStreaks,
  qualifiesForStreakBonus,
  getStreakStatus,
  validateTimerSession,
  generateStreakReport,
  isActiveEvent,
  DEFAULT_STREAK_CONFIG,
  type ActivityEvent,
  type DailyActivity,
} from '../streaks';

describe('Streaks Service', () => {
  const mockActivityEvents: ActivityEvent[] = [
    { timestamp: new Date('2024-01-20T09:00:00'), type: 'key_press', sessionId: 'session1' },
    { timestamp: new Date('2024-01-20T09:00:30'), type: 'mouse_move', sessionId: 'session1' },
    { timestamp: new Date('2024-01-20T09:01:30'), type: 'mouse_click', sessionId: 'session1' },
    { timestamp: new Date('2024-01-20T09:05:00'), type: 'window_focus', sessionId: 'session1' },
    { timestamp: new Date('2024-01-20T09:10:00'), type: 'key_press', sessionId: 'session1' },
  ];

  const mockDailyActivity: DailyActivity = {
    userId: 'user123',
    date: new Date('2024-01-20'),
    totalSeconds: 28800, // 8 hours
    billableSeconds: 25200, // 7 hours
    pauseSeconds: 3600, // 1 hour
    activityEvents: 100,
    meetsDailyMinimum: true,
    pauseReasons: ['brief_inactivity'],
  };

  describe('isActiveEvent', () => {
    it('should identify active events correctly', () => {
      expect(isActiveEvent({ timestamp: new Date(), type: 'key_press', sessionId: 'test' })).toBe(true);
      expect(isActiveEvent({ timestamp: new Date(), type: 'mouse_click', sessionId: 'test' })).toBe(true);
      expect(isActiveEvent({ timestamp: new Date(), type: 'window_focus', sessionId: 'test' })).toBe(true);
    });
    
    it('should identify idle events correctly', () => {
      expect(isActiveEvent({ timestamp: new Date(), type: 'idle_start', sessionId: 'test' })).toBe(false);
      expect(isActiveEvent({ timestamp: new Date(), type: 'idle_end', sessionId: 'test' })).toBe(false);
    });
  });

  describe('detectAutomaticPauses', () => {
    it('should detect pauses when activity gap exceeds threshold', () => {
      const eventsWithGap: ActivityEvent[] = [
        { timestamp: new Date('2024-01-20T09:00:00'), type: 'key_press', sessionId: 'session1' },
        { timestamp: new Date('2024-01-20T09:02:00'), type: 'mouse_click', sessionId: 'session1' }, // 2 minute gap > 40s
      ];
      
      const pauses = detectAutomaticPauses(eventsWithGap);
      
      expect(pauses).toHaveLength(1);
      expect(pauses[0].durationSeconds).toBe(120); // 2 minutes
      expect(pauses[0].reason).toBe('extended_inactivity');
    });
    
    it('should not detect pauses for normal activity', () => {
      const normalEvents: ActivityEvent[] = [
        { timestamp: new Date('2024-01-20T09:00:00'), type: 'key_press', sessionId: 'session1' },
        { timestamp: new Date('2024-01-20T09:00:30'), type: 'mouse_move', sessionId: 'session1' }, // 30s gap < 40s
      ];
      
      const pauses = detectAutomaticPauses(normalEvents);
      
      expect(pauses).toHaveLength(0);
    });
    
    it('should classify pause types correctly', () => {
      const eventsWithDifferentGaps: ActivityEvent[] = [
        { timestamp: new Date('2024-01-20T09:00:00'), type: 'key_press', sessionId: 'session1' },
        { timestamp: new Date('2024-01-20T09:01:00'), type: 'mouse_click', sessionId: 'session1' }, // 1 minute = brief
        { timestamp: new Date('2024-01-20T09:07:00'), type: 'key_press', sessionId: 'session1' }, // 6 minutes = extended
      ];
      
      const pauses = detectAutomaticPauses(eventsWithDifferentGaps);
      
      expect(pauses).toHaveLength(2);
      expect(pauses[0].reason).toBe('brief_inactivity');
      expect(pauses[1].reason).toBe('extended_inactivity');
    });
    
    it('should handle empty events array', () => {
      const pauses = detectAutomaticPauses([]);
      expect(pauses).toHaveLength(0);
    });
  });

  describe('calculateDailyActivity', () => {
    it('should calculate daily metrics correctly', () => {
      const result = calculateDailyActivity(
        'user123',
        new Date('2024-01-20'),
        28800, // 8 hours total
        mockActivityEvents
      );
      
      expect(result.userId).toBe('user123');
      expect(result.totalSeconds).toBe(28800);
      expect(result.billableSeconds).toBeLessThan(28800); // After pause deduction
      expect(result.activityEvents).toBe(5); // Active events only
      expect(result.meetsDailyMinimum).toBe(true); // > 6 hours billable
    });
    
    it('should identify insufficient daily hours', () => {
      const result = calculateDailyActivity(
        'user123',
        new Date('2024-01-20'),
        18000, // 5 hours total
        mockActivityEvents
      );
      
      expect(result.meetsDailyMinimum).toBe(false); // < 6 hours
    });
    
    it('should handle no activity events', () => {
      const result = calculateDailyActivity(
        'user123',
        new Date('2024-01-20'),
        21600, // 6 hours
        []
      );
      
      expect(result.activityEvents).toBe(0);
      expect(result.lastActivityTime).toBeUndefined();
    });
  });

  describe('calculateUserStreak', () => {
    it('should calculate current streak correctly', () => {
      const dailyActivities: DailyActivity[] = [
        { ...mockDailyActivity, date: new Date('2024-01-18'), meetsDailyMinimum: true },
        { ...mockDailyActivity, date: new Date('2024-01-19'), meetsDailyMinimum: true },
        { ...mockDailyActivity, date: new Date('2024-01-20'), meetsDailyMinimum: true },
      ];
      
      const streak = calculateUserStreak('user123', dailyActivities);
      
      expect(streak.currentStreak).toBe(3);
      expect(streak.totalActiveDays).toBe(3);
      expect(streak.longestStreak).toBe(3);
    });
    
    it('should handle broken streaks', () => {
      const dailyActivities: DailyActivity[] = [
        { ...mockDailyActivity, date: new Date('2024-01-15'), meetsDailyMinimum: true },
        { ...mockDailyActivity, date: new Date('2024-01-16'), meetsDailyMinimum: true },
        // Gap on 17th (weekend or missed day)
        { ...mockDailyActivity, date: new Date('2024-01-20'), meetsDailyMinimum: true },
      ];
      
      const streak = calculateUserStreak('user123', dailyActivities);
      
      expect(streak.currentStreak).toBe(1); // Only current day
      expect(streak.longestStreak).toBe(2); // Previous streak was 2 days
    });
    
    it('should ignore days that don\'t meet minimum', () => {
      const dailyActivities: DailyActivity[] = [
        { ...mockDailyActivity, date: new Date('2024-01-18'), meetsDailyMinimum: false },
        { ...mockDailyActivity, date: new Date('2024-01-19'), meetsDailyMinimum: true },
        { ...mockDailyActivity, date: new Date('2024-01-20'), meetsDailyMinimum: true },
      ];
      
      const streak = calculateUserStreak('user123', dailyActivities);
      
      expect(streak.currentStreak).toBe(2); // Only days meeting minimum
      expect(streak.totalActiveDays).toBe(2);
    });
    
    it('should handle empty activity history', () => {
      const streak = calculateUserStreak('user123', []);
      
      expect(streak.currentStreak).toBe(0);
      expect(streak.longestStreak).toBe(0);
      expect(streak.totalActiveDays).toBe(0);
      expect(streak.streakHistory).toHaveLength(0);
    });
    
    it('should allow weekend gaps in streaks', () => {
      const dailyActivities: DailyActivity[] = [
        { ...mockDailyActivity, date: new Date('2024-01-17'), meetsDailyMinimum: true }, // Wednesday
        { ...mockDailyActivity, date: new Date('2024-01-18'), meetsDailyMinimum: true }, // Thursday
        { ...mockDailyActivity, date: new Date('2024-01-19'), meetsDailyMinimum: true }, // Friday
        // Weekend gap (Sat-Sun)
        { ...mockDailyActivity, date: new Date('2024-01-22'), meetsDailyMinimum: true }, // Monday
      ];
      
      const streak = calculateUserStreak('user123', dailyActivities);
      
      expect(streak.currentStreak).toBe(4); // Should bridge weekend gap
    });
  });

  describe('qualifiesForStreakBonus', () => {
    it('should qualify after 28+ days', () => {
      const longStreak = {
        userId: 'user123',
        currentStreak: 30,
        longestStreak: 30,
        totalActiveDays: 30,
        streakHistory: [],
      };
      
      expect(qualifiesForStreakBonus(longStreak)).toBe(true);
    });
    
    it('should not qualify before 28 days', () => {
      const shortStreak = {
        userId: 'user123',
        currentStreak: 20,
        longestStreak: 20,
        totalActiveDays: 20,
        streakHistory: [],
      };
      
      expect(qualifiesForStreakBonus(shortStreak)).toBe(false);
    });
  });

  describe('getStreakStatus', () => {
    it('should show active status for current streak', () => {
      const activeStreak = {
        userId: 'user123',
        currentStreak: 15,
        longestStreak: 15,
        totalActiveDays: 15,
        lastActiveDate: new Date(), // Today
        streakHistory: [],
      };
      
      const status = getStreakStatus(activeStreak);
      
      expect(status.status).toBe('active');
      expect(status.message).toContain('15 day streak');
      expect(status.daysUntilBonus).toBe(13); // 28 - 15
    });
    
    it('should show at-risk status for yesterday activity', () => {
      const yesterday = new Date();
      yesterday.setDate(yesterday.getDate() - 1);
      
      const atRiskStreak = {
        userId: 'user123',
        currentStreak: 10,
        longestStreak: 10,
        totalActiveDays: 10,
        lastActiveDate: yesterday,
        streakHistory: [],
      };
      
      const status = getStreakStatus(atRiskStreak);
      
      expect(status.status).toBe('at_risk');
      expect(status.message).toContain('Streak at risk');
    });
    
    it('should show broken status for old activity', () => {
      const oldDate = new Date();
      oldDate.setDate(oldDate.getDate() - 5);
      
      const brokenStreak = {
        userId: 'user123',
        currentStreak: 0,
        longestStreak: 20,
        totalActiveDays: 20,
        lastActiveDate: oldDate,
        streakHistory: [],
      };
      
      const status = getStreakStatus(brokenStreak);
      
      expect(status.status).toBe('broken');
      expect(status.message).toContain('5 days since last activity');
    });
    
    it('should not show days until bonus if already qualified', () => {
      const qualifiedStreak = {
        userId: 'user123',
        currentStreak: 30,
        longestStreak: 30,
        totalActiveDays: 30,
        lastActiveDate: new Date(),
        streakHistory: [],
      };
      
      const status = getStreakStatus(qualifiedStreak);
      
      expect(status.daysUntilBonus).toBeUndefined();
    });
  });

  describe('validateTimerSession', () => {
    it('should validate normal session', () => {
      const sessionStart = new Date('2024-01-20T09:00:00');
      const sessionEnd = new Date('2024-01-20T17:00:00'); // 8 hours
      
      const result = validateTimerSession(sessionStart, sessionEnd, mockActivityEvents);
      
      expect(result.isValid).toBe(true);
      expect(result.totalSeconds).toBe(28800); // 8 hours
      expect(result.violations).toHaveLength(0);
    });
    
    it('should detect excessive pauses', () => {
      const longPauseEvents: ActivityEvent[] = [
        { timestamp: new Date('2024-01-20T09:00:00'), type: 'key_press', sessionId: 'session1' },
        { timestamp: new Date('2024-01-20T11:00:00'), type: 'mouse_click', sessionId: 'session1' }, // 2 hour gap
      ];
      
      const sessionStart = new Date('2024-01-20T09:00:00');
      const sessionEnd = new Date('2024-01-20T17:00:00');
      
      const result = validateTimerSession(sessionStart, sessionEnd, longPauseEvents);
      
      expect(result.isValid).toBe(false);
      expect(result.violations[0]).toContain('Excessive pause detected');
    });
    
    it('should detect low activity rate', () => {
      const fewEvents: ActivityEvent[] = [
        { timestamp: new Date('2024-01-20T09:00:00'), type: 'key_press', sessionId: 'session1' },
      ];
      
      const sessionStart = new Date('2024-01-20T09:00:00');
      const sessionEnd = new Date('2024-01-20T17:00:00'); // 8 hours with only 1 event
      
      const result = validateTimerSession(sessionStart, sessionEnd, fewEvents);
      
      expect(result.isValid).toBe(false);
      expect(result.violations[0]).toContain('Low activity rate detected');
    });
  });

  describe('recomputeAllStreaks', () => {
    it('should recompute streaks for multiple users', () => {
      const userActivities = new Map([
        ['user1', [mockDailyActivity]],
        ['user2', [{ ...mockDailyActivity, userId: 'user2' }]],
      ]);
      
      const results = recomputeAllStreaks(userActivities);
      
      expect(results.size).toBe(2);
      expect(results.get('user1')).toBeDefined();
      expect(results.get('user2')).toBeDefined();
    });
  });

  describe('generateStreakReport', () => {
    it('should generate comprehensive streak analytics', () => {
      const streakData = new Map([
        ['user1', { userId: 'user1', currentStreak: 0, longestStreak: 10, totalActiveDays: 50, streakHistory: [] }],
        ['user2', { userId: 'user2', currentStreak: 15, longestStreak: 20, totalActiveDays: 60, streakHistory: [] }],
        ['user3', { userId: 'user3', currentStreak: 30, longestStreak: 30, totalActiveDays: 80, streakHistory: [] }],
      ]);
      
      const report = generateStreakReport(streakData);
      
      expect(report.totalUsers).toBe(3);
      expect(report.activeStreaks).toBe(2); // user2 and user3
      expect(report.bonusEligible).toBe(1); // only user3 (30+ days)
      expect(report.longestCurrentStreak).toBe(30);
      expect(report.streakDistribution['0']).toBe(1); // user1
      expect(report.streakDistribution['15-28']).toBe(1); // user2
      expect(report.streakDistribution['29+']).toBe(1); // user3
    });
  });

  describe('timer pause rule (≥40s inactivity)', () => {
    it('should trigger pause after 40+ seconds of inactivity', () => {
      const eventsWithPause: ActivityEvent[] = [
        { timestamp: new Date('2024-01-20T09:00:00'), type: 'key_press', sessionId: 'session1' },
        { timestamp: new Date('2024-01-20T09:00:45'), type: 'mouse_click', sessionId: 'session1' }, // 45s gap
      ];
      
      const pauses = detectAutomaticPauses(eventsWithPause, DEFAULT_STREAK_CONFIG);
      
      expect(pauses).toHaveLength(1);
      expect(pauses[0].durationSeconds).toBe(45);
    });
    
    it('should not trigger pause for activity within 40 seconds', () => {
      const eventsWithoutPause: ActivityEvent[] = [
        { timestamp: new Date('2024-01-20T09:00:00'), type: 'key_press', sessionId: 'session1' },
        { timestamp: new Date('2024-01-20T09:00:35'), type: 'mouse_click', sessionId: 'session1' }, // 35s gap
      ];
      
      const pauses = detectAutomaticPauses(eventsWithoutPause, DEFAULT_STREAK_CONFIG);
      
      expect(pauses).toHaveLength(0);
    });
  });

  describe('edge cases', () => {
    it('should handle single event', () => {
      const singleEvent: ActivityEvent[] = [
        { timestamp: new Date('2024-01-20T09:00:00'), type: 'key_press', sessionId: 'session1' },
      ];
      
      const pauses = detectAutomaticPauses(singleEvent);
      expect(pauses).toHaveLength(0);
    });
    
    it('should handle events in wrong order', () => {
      const unorderedEvents: ActivityEvent[] = [
        { timestamp: new Date('2024-01-20T09:05:00'), type: 'key_press', sessionId: 'session1' },
        { timestamp: new Date('2024-01-20T09:00:00'), type: 'mouse_click', sessionId: 'session1' },
      ];
      
      const pauses = detectAutomaticPauses(unorderedEvents);
      expect(pauses).toHaveLength(0); // Should sort and process correctly
    });
    
    it('should handle midnight boundary in streaks', () => {
      const midnightActivities: DailyActivity[] = [
        { ...mockDailyActivity, date: new Date('2024-01-31'), meetsDailyMinimum: true },
        { ...mockDailyActivity, date: new Date('2024-02-01'), meetsDailyMinimum: true },
      ];
      
      const streak = calculateUserStreak('user123', midnightActivities);
      expect(streak.currentStreak).toBe(2);
    });
  });
});
