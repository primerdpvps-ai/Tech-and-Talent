/**
 * Streaks Service
 * Handles streak calculations, nightly recomputation, and timer pause rules
 */

export interface DailyActivity {
  userId: string;
  date: Date;
  totalSeconds: number;
  billableSeconds: number;
  pauseSeconds: number;
  activityEvents: number;
  meetsDailyMinimum: boolean;
  lastActivityTime?: Date;
  pauseReasons: string[];
}

export interface StreakData {
  userId: string;
  currentStreak: number;
  longestStreak: number;
  streakStartDate?: Date;
  lastActiveDate?: Date;
  totalActiveDays: number;
  streakHistory: StreakPeriod[];
}

export interface StreakPeriod {
  startDate: Date;
  endDate?: Date;
  length: number;
  reason?: string; // why streak ended
}

export interface ActivityEvent {
  timestamp: Date;
  type: 'mouse_move' | 'mouse_click' | 'key_press' | 'window_focus' | 'app_switch' | 'idle_start' | 'idle_end';
  sessionId: string;
}

export interface TimerPauseRule {
  inactivityThresholdSeconds: number;
  autoResumeAfterActivitySeconds: number;
  maxConsecutivePauseMinutes: number;
  penaltyAfterExcessivePauses: boolean;
}

export interface StreakConfig {
  minimumDailyHours: number;
  minimumDaysForBonus: number;
  activityTimeoutSeconds: number;
  pauseRule: TimerPauseRule;
  weeklyStreakRequirement: number; // days per week to maintain streak
}

export const DEFAULT_STREAK_CONFIG: StreakConfig = {
  minimumDailyHours: 6,
  minimumDaysForBonus: 28,
  activityTimeoutSeconds: 40, // Timer pause rule: if no activity ≥40s => pause
  pauseRule: {
    inactivityThresholdSeconds: 40,
    autoResumeAfterActivitySeconds: 5,
    maxConsecutivePauseMinutes: 60,
    penaltyAfterExcessivePauses: true,
  },
  weeklyStreakRequirement: 5, // must work 5 days per week minimum
};

/**
 * Check if activity indicates user is active (not idle)
 */
export function isActiveEvent(event: ActivityEvent): boolean {
  return !['idle_start', 'idle_end'].includes(event.type);
}

/**
 * Detect automatic pauses based on activity timeline
 */
export function detectAutomaticPauses(
  events: ActivityEvent[],
  config: StreakConfig = DEFAULT_STREAK_CONFIG
): Array<{ startTime: Date; endTime: Date; durationSeconds: number; reason: string }> {
  const pauses: Array<{ startTime: Date; endTime: Date; durationSeconds: number; reason: string }> = [];
  
  if (events.length < 2) return pauses;
  
  // Sort events by timestamp
  const sortedEvents = [...events].sort((a, b) => a.timestamp.getTime() - b.timestamp.getTime());
  
  let lastActivityTime = sortedEvents[0].timestamp;
  
  for (let i = 1; i < sortedEvents.length; i++) {
    const currentEvent = sortedEvents[i];
    const timeSinceLastActivity = (currentEvent.timestamp.getTime() - lastActivityTime.getTime()) / 1000;
    
    // If gap exceeds threshold, it's considered a pause
    if (timeSinceLastActivity >= config.pauseRule.inactivityThresholdSeconds) {
      pauses.push({
        startTime: lastActivityTime,
        endTime: currentEvent.timestamp,
        durationSeconds: timeSinceLastActivity,
        reason: timeSinceLastActivity >= 300 ? 'extended_inactivity' : 'brief_inactivity',
      });
    }
    
    // Update last activity time only for active events
    if (isActiveEvent(currentEvent)) {
      lastActivityTime = currentEvent.timestamp;
    }
  }
  
  return pauses;
}

/**
 * Calculate daily activity metrics
 */
export function calculateDailyActivity(
  userId: string,
  date: Date,
  timerSeconds: number,
  events: ActivityEvent[],
  config: StreakConfig = DEFAULT_STREAK_CONFIG
): DailyActivity {
  const pauses = detectAutomaticPauses(events, config);
  const totalPauseSeconds = pauses.reduce((sum, pause) => sum + pause.durationSeconds, 0);
  const billableSeconds = Math.max(0, timerSeconds - totalPauseSeconds);
  
  const billableHours = billableSeconds / 3600;
  const meetsDailyMinimum = billableHours >= config.minimumDailyHours;
  
  const activeEvents = events.filter(isActiveEvent);
  const lastActivityTime = activeEvents.length > 0 
    ? activeEvents[activeEvents.length - 1].timestamp 
    : undefined;
  
  return {
    userId,
    date,
    totalSeconds: timerSeconds,
    billableSeconds,
    pauseSeconds: totalPauseSeconds,
    activityEvents: activeEvents.length,
    meetsDailyMinimum,
    lastActivityTime,
    pauseReasons: pauses.map(p => p.reason),
  };
}

/**
 * Calculate streak for a user based on daily activities
 */
export function calculateUserStreak(
  userId: string,
  dailyActivities: DailyActivity[],
  config: StreakConfig = DEFAULT_STREAK_CONFIG
): StreakData {
  // Sort activities by date (newest first)
  const sortedActivities = [...dailyActivities]
    .filter(activity => activity.meetsDailyMinimum)
    .sort((a, b) => b.date.getTime() - a.date.getTime());
  
  if (sortedActivities.length === 0) {
    return {
      userId,
      currentStreak: 0,
      longestStreak: 0,
      totalActiveDays: 0,
      streakHistory: [],
    };
  }
  
  // Calculate current streak (consecutive days from today backwards)
  let currentStreak = 0;
  let streakStartDate: Date | undefined;
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  
  // Check if user worked today or yesterday (allow 1-day gap)
  const latestActivity = sortedActivities[0];
  const daysSinceLatest = Math.floor((today.getTime() - latestActivity.date.getTime()) / (1000 * 60 * 60 * 24));
  
  if (daysSinceLatest <= 1) {
    // Count consecutive working days
    let expectedDate = new Date(latestActivity.date);
    
    for (const activity of sortedActivities) {
      const activityDate = new Date(activity.date);
      activityDate.setHours(0, 0, 0, 0);
      
      // Check if this activity is on the expected date (allowing weekends)
      const daysDiff = Math.floor((expectedDate.getTime() - activityDate.getTime()) / (1000 * 60 * 60 * 24));
      
      if (daysDiff === 0) {
        currentStreak++;
        streakStartDate = activityDate;
        
        // Move to previous day (skip weekends for streak calculation)
        expectedDate.setDate(expectedDate.getDate() - 1);
        while (expectedDate.getDay() === 0 || expectedDate.getDay() === 6) {
          expectedDate.setDate(expectedDate.getDate() - 1);
        }
      } else if (daysDiff <= 3) {
        // Allow small gaps (weekends, holidays)
        expectedDate = new Date(activityDate);
        expectedDate.setDate(expectedDate.getDate() - 1);
      } else {
        // Streak broken
        break;
      }
    }
  }
  
  // Calculate longest streak and streak history
  const streakHistory: StreakPeriod[] = [];
  let longestStreak = currentStreak;
  
  // Group consecutive days into streak periods
  let tempStreak = 0;
  let tempStartDate: Date | undefined;
  
  for (let i = sortedActivities.length - 1; i >= 0; i--) {
    const activity = sortedActivities[i];
    
    if (tempStreak === 0) {
      tempStreak = 1;
      tempStartDate = activity.date;
    } else {
      const prevActivity = sortedActivities[i + 1];
      const daysDiff = Math.floor((activity.date.getTime() - prevActivity.date.getTime()) / (1000 * 60 * 60 * 24));
      
      if (daysDiff <= 3) { // Allow weekend gaps
        tempStreak++;
      } else {
        // End current streak period
        if (tempStartDate) {
          streakHistory.push({
            startDate: tempStartDate,
            endDate: prevActivity.date,
            length: tempStreak,
          });
          
          longestStreak = Math.max(longestStreak, tempStreak);
        }
        
        tempStreak = 1;
        tempStartDate = activity.date;
      }
    }
  }
  
  // Add final streak period
  if (tempStreak > 0 && tempStartDate) {
    streakHistory.push({
      startDate: tempStartDate,
      endDate: sortedActivities[0].date,
      length: tempStreak,
    });
    
    longestStreak = Math.max(longestStreak, tempStreak);
  }
  
  return {
    userId,
    currentStreak,
    longestStreak,
    streakStartDate,
    lastActiveDate: sortedActivities[0]?.date,
    totalActiveDays: sortedActivities.length,
    streakHistory,
  };
}

/**
 * Nightly recomputation task for all users
 */
export function recomputeAllStreaks(
  userActivities: Map<string, DailyActivity[]>,
  config: StreakConfig = DEFAULT_STREAK_CONFIG
): Map<string, StreakData> {
  const streakResults = new Map<string, StreakData>();
  
  for (const [userId, activities] of userActivities) {
    const streakData = calculateUserStreak(userId, activities, config);
    streakResults.set(userId, streakData);
  }
  
  return streakResults;
}

/**
 * Check if user qualifies for streak bonus
 */
export function qualifiesForStreakBonus(
  streakData: StreakData,
  config: StreakConfig = DEFAULT_STREAK_CONFIG
): boolean {
  return streakData.currentStreak >= config.minimumDaysForBonus;
}

/**
 * Get streak status for display
 */
export function getStreakStatus(streakData: StreakData): {
  status: 'active' | 'at_risk' | 'broken';
  message: string;
  daysUntilBonus?: number;
} {
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  
  if (!streakData.lastActiveDate) {
    return {
      status: 'broken',
      message: 'No recent activity',
    };
  }
  
  const daysSinceActive = Math.floor(
    (today.getTime() - streakData.lastActiveDate.getTime()) / (1000 * 60 * 60 * 24)
  );
  
  if (daysSinceActive > 2) {
    return {
      status: 'broken',
      message: `Streak broken - ${daysSinceActive} days since last activity`,
    };
  }
  
  if (daysSinceActive === 1) {
    return {
      status: 'at_risk',
      message: 'Streak at risk - work today to maintain',
    };
  }
  
  const daysUntilBonus = Math.max(0, DEFAULT_STREAK_CONFIG.minimumDaysForBonus - streakData.currentStreak);
  
  return {
    status: 'active',
    message: `${streakData.currentStreak} day streak`,
    daysUntilBonus: daysUntilBonus > 0 ? daysUntilBonus : undefined,
  };
}

/**
 * Validate timer session for pause detection
 */
export function validateTimerSession(
  sessionStart: Date,
  sessionEnd: Date,
  events: ActivityEvent[],
  config: StreakConfig = DEFAULT_STREAK_CONFIG
): {
  isValid: boolean;
  totalSeconds: number;
  activeSeconds: number;
  pauseSeconds: number;
  violations: string[];
} {
  const violations: string[] = [];
  const totalSeconds = Math.floor((sessionEnd.getTime() - sessionStart.getTime()) / 1000);
  
  const pauses = detectAutomaticPauses(events, config);
  const pauseSeconds = pauses.reduce((sum, pause) => sum + pause.durationSeconds, 0);
  const activeSeconds = totalSeconds - pauseSeconds;
  
  // Check for excessive pauses
  const longPauses = pauses.filter(p => p.durationSeconds > config.pauseRule.maxConsecutivePauseMinutes * 60);
  if (longPauses.length > 0) {
    violations.push(`Excessive pause detected: ${longPauses.length} pauses over ${config.pauseRule.maxConsecutivePauseMinutes} minutes`);
  }
  
  // Check activity level
  const activityRate = events.filter(isActiveEvent).length / (totalSeconds / 60); // events per minute
  if (activityRate < 0.5) { // Less than 0.5 events per minute
    violations.push('Low activity rate detected');
  }
  
  return {
    isValid: violations.length === 0,
    totalSeconds,
    activeSeconds,
    pauseSeconds,
    violations,
  };
}

/**
 * Generate streak report for analytics
 */
export function generateStreakReport(
  streakData: Map<string, StreakData>
): {
  totalUsers: number;
  activeStreaks: number;
  bonusEligible: number;
  averageStreak: number;
  longestCurrentStreak: number;
  streakDistribution: Record<string, number>;
} {
  const users = Array.from(streakData.values());
  const activeStreaks = users.filter(u => u.currentStreak > 0).length;
  const bonusEligible = users.filter(u => qualifiesForStreakBonus(u)).length;
  const averageStreak = users.reduce((sum, u) => sum + u.currentStreak, 0) / users.length;
  const longestCurrentStreak = Math.max(...users.map(u => u.currentStreak), 0);
  
  // Streak distribution
  const distribution: Record<string, number> = {
    '0': 0,
    '1-7': 0,
    '8-14': 0,
    '15-28': 0,
    '29+': 0,
  };
  
  users.forEach(user => {
    const streak = user.currentStreak;
    if (streak === 0) distribution['0']++;
    else if (streak <= 7) distribution['1-7']++;
    else if (streak <= 14) distribution['8-14']++;
    else if (streak <= 28) distribution['15-28']++;
    else distribution['29+']++;
  });
  
  return {
    totalUsers: users.length,
    activeStreaks,
    bonusEligible,
    averageStreak: Math.round(averageStreak * 100) / 100,
    longestCurrentStreak,
    streakDistribution: distribution,
  };
}
