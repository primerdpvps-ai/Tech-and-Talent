import { describe, it, expect } from 'vitest';
import {
  checkOperationalWindow,
  hasSpecialWindowAccess,
  calculateTenureDays,
  getCurrentPKTMinutes,
  isTimeOperational,
  DEFAULT_CONFIG,
} from '../windows';

describe('Windows Service', () => {
  const mockProfileDate = new Date('2024-01-01T00:00:00Z');
  
  describe('calculateTenureDays', () => {
    it('should calculate tenure correctly', () => {
      const profileCreated = new Date('2024-01-01');
      const currentDate = new Date('2024-01-15');
      
      const tenure = calculateTenureDays(profileCreated, currentDate);
      expect(tenure).toBe(14);
    });
    
    it('should handle same day creation', () => {
      const profileCreated = new Date('2024-01-01');
      const currentDate = new Date('2024-01-01');
      
      const tenure = calculateTenureDays(profileCreated, currentDate);
      expect(tenure).toBe(0);
    });
  });

  describe('hasSpecialWindowAccess', () => {
    it('should grant special access after 10 days', () => {
      const profileCreated = new Date('2024-01-01');
      const currentDate = new Date('2024-01-15'); // 14 days later
      
      const hasAccess = hasSpecialWindowAccess(profileCreated, DEFAULT_CONFIG, currentDate);
      expect(hasAccess).toBe(true);
    });
    
    it('should deny special access before 10 days', () => {
      const profileCreated = new Date('2024-01-01');
      const currentDate = new Date('2024-01-05'); // 4 days later
      
      const hasAccess = hasSpecialWindowAccess(profileCreated, DEFAULT_CONFIG, currentDate);
      expect(hasAccess).toBe(false);
    });
  });

  describe('checkOperationalWindow', () => {
    it('should allow access during standard window (13:00 PKT)', () => {
      const profileCreated = new Date('2024-01-01');
      // Mock PKT time 13:00 (1:00 PM)
      const mockDate = new Date('2024-01-15T08:00:00Z'); // 13:00 PKT (UTC+5)
      
      const result = checkOperationalWindow(profileCreated, DEFAULT_CONFIG, mockDate);
      expect(result.isAllowed).toBe(true);
      expect(result.currentWindow).toBe('standard');
    });
    
    it('should deny access outside operational windows for new users', () => {
      const profileCreated = new Date('2024-01-01');
      // Mock PKT time 08:00 (8:00 AM) - outside standard window
      const mockDate = new Date('2024-01-05T03:00:00Z'); // 08:00 PKT
      
      const result = checkOperationalWindow(profileCreated, DEFAULT_CONFIG, mockDate);
      expect(result.isAllowed).toBe(false);
      expect(result.currentWindow).toBe('none');
      expect(result.reason).toContain('Special access requires');
    });
    
    it('should allow special window access for tenured users', () => {
      const profileCreated = new Date('2024-01-01');
      // Mock PKT time 04:00 (4:00 AM) - special window
      const mockDate = new Date('2024-01-15T23:00:00Z'); // 04:00 PKT next day
      
      const result = checkOperationalWindow(profileCreated, DEFAULT_CONFIG, mockDate);
      expect(result.isAllowed).toBe(true);
      expect(result.currentWindow).toBe('special');
    });
    
    it('should handle overnight standard window correctly', () => {
      const profileCreated = new Date('2024-01-01');
      // Mock PKT time 01:00 (1:00 AM) - within standard window (11:00-02:00)
      const mockDate = new Date('2024-01-15T20:00:00Z'); // 01:00 PKT next day
      
      const result = checkOperationalWindow(profileCreated, DEFAULT_CONFIG, mockDate);
      expect(result.isAllowed).toBe(true);
      expect(result.currentWindow).toBe('standard');
    });
  });

  describe('isTimeOperational', () => {
    it('should return true for operational time', () => {
      const profileCreated = new Date('2024-01-01');
      const checkTime = new Date('2024-01-15T08:00:00Z'); // 13:00 PKT
      
      const isOperational = isTimeOperational(checkTime, profileCreated, DEFAULT_CONFIG);
      expect(isOperational).toBe(true);
    });
    
    it('should return false for non-operational time', () => {
      const profileCreated = new Date('2024-01-01');
      const checkTime = new Date('2024-01-05T03:00:00Z'); // 08:00 PKT, new user
      
      const isOperational = isTimeOperational(checkTime, profileCreated, DEFAULT_CONFIG);
      expect(isOperational).toBe(false);
    });
  });
});
