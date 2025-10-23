/**
 * Operational Windows Service
 * Handles time window validation for TTS operations in PKT timezone
 */

export interface TimeWindow {
  start: string; // HH:MM format
  end: string;   // HH:MM format
}

export interface OperationalConfig {
  standardWindow: TimeWindow;
  specialWindow: TimeWindow;
  timezone: string;
  specialAccessTenureDays: number;
}

export interface WindowCheckResult {
  isAllowed: boolean;
  currentWindow: 'standard' | 'special' | 'none';
  timeUntilNext?: number; // minutes until next allowed window
  reason?: string;
}

export const DEFAULT_CONFIG: OperationalConfig = {
  standardWindow: { start: '11:00', end: '02:00' }, // 11:00 AM to 2:00 AM next day
  specialWindow: { start: '02:00', end: '06:00' },  // 2:00 AM to 6:00 AM
  timezone: 'Asia/Karachi', // PKT
  specialAccessTenureDays: 10,
};

/**
 * Parse time string to minutes since midnight
 */
function parseTimeToMinutes(timeStr: string): number {
  const [hours, minutes] = timeStr.split(':').map(Number);
  return hours * 60 + minutes;
}

/**
 * Convert minutes since midnight to time string
 */
function minutesToTimeString(minutes: number): string {
  const hours = Math.floor(minutes / 60) % 24;
  const mins = minutes % 60;
  return `${hours.toString().padStart(2, '0')}:${mins.toString().padStart(2, '0')}`;
}

/**
 * Get current time in PKT timezone as minutes since midnight
 */
export function getCurrentPKTMinutes(date?: Date): number {
  const now = date || new Date();
  
  // Convert to PKT (UTC+5)
  const pktTime = new Date(now.toLocaleString('en-US', { timeZone: 'Asia/Karachi' }));
  
  return pktTime.getHours() * 60 + pktTime.getMinutes();
}

/**
 * Check if current time falls within a time window (handles overnight windows)
 */
function isTimeInWindow(currentMinutes: number, window: TimeWindow): boolean {
  const startMinutes = parseTimeToMinutes(window.start);
  const endMinutes = parseTimeToMinutes(window.end);
  
  // Handle overnight window (e.g., 23:00 to 06:00)
  if (startMinutes > endMinutes) {
    return currentMinutes >= startMinutes || currentMinutes <= endMinutes;
  }
  
  // Normal window (e.g., 09:00 to 17:00)
  return currentMinutes >= startMinutes && currentMinutes <= endMinutes;
}

/**
 * Calculate minutes until next allowed window
 */
function calculateTimeUntilNext(
  currentMinutes: number, 
  config: OperationalConfig,
  hasSpecialAccess: boolean
): number {
  const standardStart = parseTimeToMinutes(config.standardWindow.start);
  const standardEnd = parseTimeToMinutes(config.standardWindow.end);
  const specialStart = parseTimeToMinutes(config.specialWindow.start);
  const specialEnd = parseTimeToMinutes(config.specialWindow.end);
  
  const minutesInDay = 24 * 60;
  
  // Find next window start time
  const possibleStarts = [standardStart];
  if (hasSpecialAccess) {
    possibleStarts.push(specialStart);
  }
  
  // Sort possible start times
  possibleStarts.sort((a, b) => a - b);
  
  // Find next start time after current time
  for (const startTime of possibleStarts) {
    if (startTime > currentMinutes) {
      return startTime - currentMinutes;
    }
  }
  
  // If no window today, get first window tomorrow
  const nextStart = Math.min(...possibleStarts);
  return (minutesInDay - currentMinutes) + nextStart;
}

/**
 * Calculate user tenure in days from profile creation date
 */
export function calculateTenureDays(profileCreatedAt: Date, currentDate?: Date): number {
  const now = currentDate || new Date();
  const diffTime = now.getTime() - profileCreatedAt.getTime();
  return Math.floor(diffTime / (1000 * 60 * 60 * 24));
}

/**
 * Check if user has special window access based on tenure
 */
export function hasSpecialWindowAccess(
  profileCreatedAt: Date, 
  config: OperationalConfig = DEFAULT_CONFIG,
  currentDate?: Date
): boolean {
  const tenureDays = calculateTenureDays(profileCreatedAt, currentDate);
  return tenureDays >= config.specialAccessTenureDays;
}

/**
 * Main function to check operational window access
 */
export function checkOperationalWindow(
  profileCreatedAt: Date,
  config: OperationalConfig = DEFAULT_CONFIG,
  currentDate?: Date
): WindowCheckResult {
  const currentMinutes = getCurrentPKTMinutes(currentDate);
  const hasSpecialAccess = hasSpecialWindowAccess(profileCreatedAt, config, currentDate);
  
  // Check standard window
  const inStandardWindow = isTimeInWindow(currentMinutes, config.standardWindow);
  
  // Check special window (if user has access)
  const inSpecialWindow = hasSpecialAccess && 
    isTimeInWindow(currentMinutes, config.specialWindow);
  
  if (inStandardWindow) {
    return {
      isAllowed: true,
      currentWindow: 'standard',
    };
  }
  
  if (inSpecialWindow) {
    return {
      isAllowed: true,
      currentWindow: 'special',
    };
  }
  
  // Not in any allowed window
  const timeUntilNext = calculateTimeUntilNext(currentMinutes, config, hasSpecialAccess);
  const reason = hasSpecialAccess 
    ? `Outside operational windows. Standard: ${config.standardWindow.start}-${config.standardWindow.end}, Special: ${config.specialWindow.start}-${config.specialWindow.end} PKT`
    : `Outside standard operational window: ${config.standardWindow.start}-${config.standardWindow.end} PKT. Special access requires ${config.specialAccessTenureDays}+ days tenure.`;
  
  return {
    isAllowed: false,
    currentWindow: 'none',
    timeUntilNext,
    reason,
  };
}

/**
 * Get user's available windows based on tenure
 */
export function getUserAvailableWindows(
  profileCreatedAt: Date,
  config: OperationalConfig = DEFAULT_CONFIG,
  currentDate?: Date
): TimeWindow[] {
  const windows = [config.standardWindow];
  
  if (hasSpecialWindowAccess(profileCreatedAt, config, currentDate)) {
    windows.push(config.specialWindow);
  }
  
  return windows;
}

/**
 * Format window check result for display
 */
export function formatWindowStatus(result: WindowCheckResult): string {
  if (result.isAllowed) {
    return `✅ Access granted (${result.currentWindow} window)`;
  }
  
  const timeStr = result.timeUntilNext 
    ? ` Next window in ${Math.floor(result.timeUntilNext / 60)}h ${result.timeUntilNext % 60}m.`
    : '';
  
  return `❌ ${result.reason}${timeStr}`;
}

/**
 * Check if a specific time falls within operational windows
 */
export function isTimeOperational(
  checkTime: Date,
  profileCreatedAt: Date,
  config: OperationalConfig = DEFAULT_CONFIG
): boolean {
  const result = checkOperationalWindow(profileCreatedAt, config, checkTime);
  return result.isAllowed;
}
