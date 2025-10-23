import { describe, it, expect } from 'vitest';
import {
  applyEvaluation,
  meetsMinimumRequirements,
  getEvaluationSummary,
  DEFAULT_EVALUATION_CONFIG,
  type EvaluationData,
} from '../eligibility';

describe('Eligibility Service', () => {
  const baseEvaluationData: EvaluationData = {
    age: 25,
    deviceType: 'Desktop',
    ramText: '16GB DDR4',
    processorText: 'Intel Core i7-10700K',
    stableInternet: true,
    provider: 'Comcast',
    linkSpeed: '100 Mbps',
    numUsers: 2,
    speedtestUrl: 'https://speedtest.net/result/12345',
    profession: 'Software Developer',
    dailyTimeOk: true,
    timeWindows: ['09:00-17:00', '19:00-23:00'],
    qualification: 'Bachelor in Computer Science',
    confidentialityOk: true,
    typingOk: true,
  };

  describe('meetsMinimumRequirements', () => {
    it('should pass for valid minimum requirements', () => {
      const result = meetsMinimumRequirements(baseEvaluationData);
      expect(result).toBe(true);
    });
    
    it('should fail if confidentiality not accepted', () => {
      const data = { ...baseEvaluationData, confidentialityOk: false };
      const result = meetsMinimumRequirements(data);
      expect(result).toBe(false);
    });
    
    it('should fail if under 18', () => {
      const data = { ...baseEvaluationData, age: 17 };
      const result = meetsMinimumRequirements(data);
      expect(result).toBe(false);
    });
    
    it('should fail if no stable internet', () => {
      const data = { ...baseEvaluationData, stableInternet: false };
      const result = meetsMinimumRequirements(data);
      expect(result).toBe(false);
    });
  });

  describe('applyEvaluation', () => {
    it('should return ELIGIBLE for high-quality candidate', () => {
      const result = applyEvaluation(baseEvaluationData);
      
      expect(result.status).toBe('ELIGIBLE');
      expect(result.score).toBeGreaterThanOrEqual(80);
      expect(result.reasons).toHaveLength(0);
    });
    
    it('should return REJECTED for underage candidate', () => {
      const data = { ...baseEvaluationData, age: 17 };
      const result = applyEvaluation(data);
      
      expect(result.status).toBe('REJECTED');
      expect(result.reasons).toContain('Must be at least 18 years old');
    });
    
    it('should return REJECTED for no confidentiality agreement', () => {
      const data = { ...baseEvaluationData, confidentialityOk: false };
      const result = applyEvaluation(data);
      
      expect(result.status).toBe('REJECTED');
      expect(result.reasons).toContain('Confidentiality agreement must be accepted');
    });
    
    it('should return PENDING for marginal candidate', () => {
      const data: EvaluationData = {
        ...baseEvaluationData,
        ramText: '4GB DDR3',
        processorText: 'Intel Core i3',
        linkSpeed: '25 Mbps',
        numUsers: 5,
        profession: 'Student',
        qualification: 'High School',
      };
      
      const result = applyEvaluation(data);
      
      expect(result.status).toBe('PENDING');
      expect(result.score).toBeGreaterThanOrEqual(60);
      expect(result.score).toBeLessThan(80);
    });
    
    it('should penalize insufficient RAM', () => {
      const data = { ...baseEvaluationData, ramText: '2GB DDR3' };
      const result = applyEvaluation(data);
      
      expect(result.reasons).toContain('Insufficient RAM (minimum 4GB required)');
      expect(result.breakdown.hardware).toBeLessThan(50);
    });
    
    it('should reward tech profession', () => {
      const techData = { ...baseEvaluationData, profession: 'Software Engineer' };
      const nonTechData = { ...baseEvaluationData, profession: 'Teacher' };
      
      const techResult = applyEvaluation(techData);
      const nonTechResult = applyEvaluation(nonTechData);
      
      expect(techResult.breakdown.professional).toBeGreaterThan(nonTechResult.breakdown.professional);
    });
    
    it('should handle slow internet speed', () => {
      const data = { ...baseEvaluationData, linkSpeed: '5 Mbps' };
      const result = applyEvaluation(data);
      
      expect(result.reasons).toContain('Internet speed too slow (minimum 10 Mbps required)');
    });
    
    it('should penalize too many users on connection', () => {
      const data = { ...baseEvaluationData, numUsers: 8 };
      const result = applyEvaluation(data);
      
      expect(result.reasons).toContain('Too many users sharing internet connection');
    });
    
    it('should handle missing time windows', () => {
      const data = { ...baseEvaluationData, timeWindows: [] };
      const result = applyEvaluation(data);
      
      expect(result.reasons).toContain('No available time windows specified');
    });
  });

  describe('getEvaluationSummary', () => {
    it('should format ELIGIBLE result correctly', () => {
      const result = applyEvaluation(baseEvaluationData);
      const summary = getEvaluationSummary(result);
      
      expect(summary).toContain('✅ ELIGIBLE');
      expect(summary).toContain(`Score: ${result.score}/100`);
    });
    
    it('should format REJECTED result correctly', () => {
      const data = { ...baseEvaluationData, age: 17 };
      const result = applyEvaluation(data);
      const summary = getEvaluationSummary(result);
      
      expect(summary).toContain('❌ REJECTED');
    });
    
    it('should format PENDING result correctly', () => {
      const data = { ...baseEvaluationData, ramText: '4GB', linkSpeed: '30 Mbps' };
      const result = applyEvaluation(data);
      const summary = getEvaluationSummary(result);
      
      expect(summary).toContain('⏳ PENDING');
    });
  });

  describe('score breakdown validation', () => {
    it('should have all breakdown categories', () => {
      const result = applyEvaluation(baseEvaluationData);
      
      expect(result.breakdown).toHaveProperty('age');
      expect(result.breakdown).toHaveProperty('hardware');
      expect(result.breakdown).toHaveProperty('internet');
      expect(result.breakdown).toHaveProperty('availability');
      expect(result.breakdown).toHaveProperty('professional');
      expect(result.breakdown).toHaveProperty('compliance');
    });
    
    it('should have scores between 0 and 100 for each category', () => {
      const result = applyEvaluation(baseEvaluationData);
      
      Object.values(result.breakdown).forEach(score => {
        expect(score).toBeGreaterThanOrEqual(0);
        expect(score).toBeLessThanOrEqual(100);
      });
    });
  });
});
