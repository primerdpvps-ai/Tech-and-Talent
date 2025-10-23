/**
 * Eligibility Service
 * Handles evaluation logic and decision making for candidate applications
 */

export interface EvaluationData {
  age: number;
  deviceType: string;
  ramText: string;
  processorText: string;
  stableInternet: boolean;
  provider: string;
  linkSpeed: string;
  numUsers: number;
  speedtestUrl: string;
  profession: string;
  dailyTimeOk: boolean;
  timeWindows: string[];
  qualification: string;
  confidentialityOk: boolean;
  typingOk: boolean;
}

export interface EvaluationResult {
  status: 'ELIGIBLE' | 'PENDING' | 'REJECTED';
  reasons: string[];
  score: number;
  breakdown: {
    age: number;
    hardware: number;
    internet: number;
    availability: number;
    professional: number;
    compliance: number;
  };
}

export interface EvaluationConfig {
  minAge: number;
  maxAge: number;
  minScore: {
    eligible: number;
    pending: number;
  };
  weights: {
    age: number;
    hardware: number;
    internet: number;
    availability: number;
    professional: number;
    compliance: number;
  };
}

export const DEFAULT_EVALUATION_CONFIG: EvaluationConfig = {
  minAge: 18,
  maxAge: 65,
  minScore: {
    eligible: 80,
    pending: 60,
  },
  weights: {
    age: 10,
    hardware: 25,
    internet: 25,
    availability: 15,
    professional: 15,
    compliance: 10,
  },
};

/**
 * Evaluate age criteria
 */
function evaluateAge(age: number, config: EvaluationConfig): { score: number; reasons: string[] } {
  const reasons: string[] = [];
  
  if (age < config.minAge) {
    reasons.push(`Must be at least ${config.minAge} years old`);
    return { score: 0, reasons };
  }
  
  if (age > config.maxAge) {
    reasons.push(`Age limit exceeded (max ${config.maxAge} years)`);
    return { score: 0, reasons };
  }
  
  // Optimal age range scoring
  if (age >= 22 && age <= 45) {
    return { score: 100, reasons };
  } else if (age >= 18 && age <= 55) {
    return { score: 80, reasons };
  } else {
    return { score: 60, reasons };
  }
}

/**
 * Evaluate hardware specifications
 */
function evaluateHardware(
  deviceType: string, 
  ramText: string, 
  processorText: string
): { score: number; reasons: string[] } {
  const reasons: string[] = [];
  let score = 0;
  
  // Device type evaluation
  const deviceLower = deviceType.toLowerCase();
  if (deviceLower.includes('desktop') || deviceLower.includes('pc')) {
    score += 40;
  } else if (deviceLower.includes('laptop')) {
    score += 35;
  } else {
    reasons.push('Desktop or laptop computer required');
    score += 10;
  }
  
  // RAM evaluation
  const ramLower = ramText.toLowerCase();
  const ramMatch = ramLower.match(/(\d+)\s*gb/);
  const ramGB = ramMatch ? parseInt(ramMatch[1]) : 0;
  
  if (ramGB >= 16) {
    score += 30;
  } else if (ramGB >= 8) {
    score += 25;
  } else if (ramGB >= 4) {
    score += 15;
    reasons.push('RAM may be insufficient for optimal performance');
  } else {
    reasons.push('Insufficient RAM (minimum 4GB required)');
    score += 5;
  }
  
  // Processor evaluation
  const processorLower = processorText.toLowerCase();
  const highEndProcessors = ['i7', 'i9', 'ryzen 7', 'ryzen 9', 'apple m1', 'apple m2'];
  const midRangeProcessors = ['i5', 'ryzen 5', 'apple a'];
  const lowEndProcessors = ['i3', 'ryzen 3', 'celeron', 'pentium'];
  
  if (highEndProcessors.some(proc => processorLower.includes(proc))) {
    score += 30;
  } else if (midRangeProcessors.some(proc => processorLower.includes(proc))) {
    score += 25;
  } else if (lowEndProcessors.some(proc => processorLower.includes(proc))) {
    score += 15;
    reasons.push('Processor may not meet performance requirements');
  } else {
    reasons.push('Processor specification unclear or insufficient');
    score += 10;
  }
  
  return { score: Math.min(100, score), reasons };
}

/**
 * Evaluate internet connectivity
 */
function evaluateInternet(
  stableInternet: boolean,
  provider: string,
  linkSpeed: string,
  numUsers: number,
  speedtestUrl: string
): { score: number; reasons: string[] } {
  const reasons: string[] = [];
  let score = 0;
  
  // Internet stability
  if (!stableInternet) {
    reasons.push('Stable internet connection is required');
    return { score: 0, reasons };
  }
  score += 25;
  
  // Speed evaluation
  const speedLower = linkSpeed.toLowerCase();
  const speedMatch = speedLower.match(/(\d+)\s*(mbps|mb)/);
  const speedMbps = speedMatch ? parseInt(speedMatch[1]) : 0;
  
  if (speedMbps >= 100) {
    score += 35;
  } else if (speedMbps >= 50) {
    score += 30;
  } else if (speedMbps >= 25) {
    score += 20;
    reasons.push('Internet speed may be marginal for some tasks');
  } else if (speedMbps >= 10) {
    score += 10;
    reasons.push('Internet speed insufficient for optimal performance');
  } else {
    reasons.push('Internet speed too slow (minimum 10 Mbps required)');
    score += 5;
  }
  
  // Number of users sharing connection
  if (numUsers <= 2) {
    score += 25;
  } else if (numUsers <= 4) {
    score += 20;
  } else if (numUsers <= 6) {
    score += 10;
    reasons.push('Multiple users may affect connection quality');
  } else {
    reasons.push('Too many users sharing internet connection');
    score += 5;
  }
  
  // Speedtest URL validation
  if (speedtestUrl && speedtestUrl.includes('speedtest')) {
    score += 15;
  } else {
    reasons.push('Valid speedtest result required');
    score += 5;
  }
  
  return { score: Math.min(100, score), reasons };
}

/**
 * Evaluate availability and time commitment
 */
function evaluateAvailability(
  dailyTimeOk: boolean,
  timeWindows: string[]
): { score: number; reasons: string[] } {
  const reasons: string[] = [];
  let score = 0;
  
  // Daily time commitment
  if (!dailyTimeOk) {
    reasons.push('Daily time commitment not confirmed');
    score += 20;
  } else {
    score += 50;
  }
  
  // Time windows availability
  if (timeWindows.length === 0) {
    reasons.push('No available time windows specified');
    score += 10;
  } else if (timeWindows.length >= 2) {
    score += 50; // Flexible availability
  } else {
    score += 35; // Limited but acceptable availability
  }
  
  return { score: Math.min(100, score), reasons };
}

/**
 * Evaluate professional background
 */
function evaluateProfessional(
  profession: string,
  qualification: string
): { score: number; reasons: string[] } {
  const reasons: string[] = [];
  let score = 0;
  
  // Professional background
  const professionLower = profession.toLowerCase();
  const techProfessions = [
    'developer', 'programmer', 'engineer', 'analyst', 'designer',
    'it', 'computer', 'software', 'data', 'web', 'mobile'
  ];
  const businessProfessions = [
    'manager', 'consultant', 'coordinator', 'specialist', 'administrator'
  ];
  const customerServiceProfessions = [
    'support', 'service', 'representative', 'agent', 'assistant'
  ];
  
  if (techProfessions.some(prof => professionLower.includes(prof))) {
    score += 60;
  } else if (businessProfessions.some(prof => professionLower.includes(prof))) {
    score += 50;
  } else if (customerServiceProfessions.some(prof => professionLower.includes(prof))) {
    score += 45;
  } else {
    score += 30;
    reasons.push('Professional background may not align with typical requirements');
  }
  
  // Qualification evaluation
  const qualificationLower = qualification.toLowerCase();
  const highEducation = ['master', 'mba', 'phd', 'doctorate'];
  const midEducation = ['bachelor', 'degree', 'graduate'];
  const certifications = ['certified', 'certification', 'diploma'];
  
  if (highEducation.some(edu => qualificationLower.includes(edu))) {
    score += 40;
  } else if (midEducation.some(edu => qualificationLower.includes(edu))) {
    score += 35;
  } else if (certifications.some(cert => qualificationLower.includes(cert))) {
    score += 25;
  } else {
    score += 15;
    reasons.push('Educational qualification may be insufficient');
  }
  
  return { score: Math.min(100, score), reasons };
}

/**
 * Evaluate compliance requirements
 */
function evaluateCompliance(
  confidentialityOk: boolean,
  typingOk: boolean
): { score: number; reasons: string[] } {
  const reasons: string[] = [];
  let score = 0;
  
  // Confidentiality agreement (mandatory)
  if (!confidentialityOk) {
    reasons.push('Confidentiality agreement must be accepted');
    return { score: 0, reasons };
  }
  score += 60;
  
  // Typing skills
  if (!typingOk) {
    reasons.push('Typing skills requirement not met');
    score += 20;
  } else {
    score += 40;
  }
  
  return { score: Math.min(100, score), reasons };
}

/**
 * Main evaluation function
 */
export function applyEvaluation(
  data: EvaluationData,
  config: EvaluationConfig = DEFAULT_EVALUATION_CONFIG
): EvaluationResult {
  const allReasons: string[] = [];
  
  // Evaluate each category
  const ageEval = evaluateAge(data.age, config);
  const hardwareEval = evaluateHardware(data.deviceType, data.ramText, data.processorText);
  const internetEval = evaluateInternet(
    data.stableInternet, data.provider, data.linkSpeed, 
    data.numUsers, data.speedtestUrl
  );
  const availabilityEval = evaluateAvailability(data.dailyTimeOk, data.timeWindows);
  const professionalEval = evaluateProfessional(data.profession, data.qualification);
  const complianceEval = evaluateCompliance(data.confidentialityOk, data.typingOk);
  
  // Collect all reasons
  allReasons.push(...ageEval.reasons);
  allReasons.push(...hardwareEval.reasons);
  allReasons.push(...internetEval.reasons);
  allReasons.push(...availabilityEval.reasons);
  allReasons.push(...professionalEval.reasons);
  allReasons.push(...complianceEval.reasons);
  
  // Calculate weighted score
  const breakdown = {
    age: ageEval.score,
    hardware: hardwareEval.score,
    internet: internetEval.score,
    availability: availabilityEval.score,
    professional: professionalEval.score,
    compliance: complianceEval.score,
  };
  
  const totalScore = Math.round(
    (breakdown.age * config.weights.age +
     breakdown.hardware * config.weights.hardware +
     breakdown.internet * config.weights.internet +
     breakdown.availability * config.weights.availability +
     breakdown.professional * config.weights.professional +
     breakdown.compliance * config.weights.compliance) / 100
  );
  
  // Determine status
  let status: 'ELIGIBLE' | 'PENDING' | 'REJECTED';
  
  // Automatic rejection for critical failures
  if (!data.confidentialityOk || data.age < config.minAge || data.age > config.maxAge) {
    status = 'REJECTED';
  } else if (totalScore >= config.minScore.eligible) {
    status = 'ELIGIBLE';
  } else if (totalScore >= config.minScore.pending) {
    status = 'PENDING';
  } else {
    status = 'REJECTED';
  }
  
  return {
    status,
    reasons: allReasons,
    score: totalScore,
    breakdown,
  };
}

/**
 * Get evaluation summary for display
 */
export function getEvaluationSummary(result: EvaluationResult): string {
  const statusEmoji = {
    ELIGIBLE: '✅',
    PENDING: '⏳',
    REJECTED: '❌',
  };
  
  return `${statusEmoji[result.status]} ${result.status} (Score: ${result.score}/100)`;
}

/**
 * Check if evaluation meets minimum requirements
 */
export function meetsMinimumRequirements(data: EvaluationData): boolean {
  return data.confidentialityOk && 
         data.age >= 18 && 
         data.stableInternet &&
         data.dailyTimeOk;
}
