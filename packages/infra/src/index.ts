// Types
export * from './types/auth';
export * from './types/employment';
export * from './types/gig';

// Utils
export * from './utils/date';
export * from './utils/validation';
export * from './utils/crypto';

// Constants
export * from './constants/payroll';

// Constants
export const APP_CONFIG = {
  OPERATIONAL_WINDOW: process.env.APP_PUBLIC_OPERATIONAL_WINDOW || '11:00-02:00',
  SPECIAL_WINDOW: process.env.APP_PUBLIC_SPECIAL_WINDOW || '02:00-06:00',
  HOURLY_RATE: Number(process.env.APP_HOURLY_RATE) || 125,
  STREAK_BONUS: Number(process.env.APP_STREAK_BONUS) || 500,
  SECURITY_FUND: Number(process.env.APP_SECURITY_FUND) || 1000
} as const;
