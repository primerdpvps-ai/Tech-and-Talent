import { z } from 'zod';

export enum PayrollStatus {
  PENDING = 'PENDING',
  PROCESSING = 'PROCESSING',
  PAID = 'PAID',
  DELAYED = 'DELAYED'
}

export enum LeaveType {
  SHORT = 'SHORT',
  ONE_DAY = 'ONE_DAY',
  LONG = 'LONG'
}

export enum LeaveStatus {
  PENDING = 'PENDING',
  APPROVED = 'APPROVED',
  REJECTED = 'REJECTED'
}

export const TimerSessionSchema = z.object({
  deviceId: z.string().optional(),
  ip: z.string().ip().optional()
});

export const LeaveRequestSchema = z.object({
  type: z.nativeEnum(LeaveType),
  dateFrom: z.date(),
  dateTo: z.date(),
  noticeHours: z.number().min(0, 'Notice hours must be positive')
});

export const PenaltySchema = z.object({
  policyArea: z.string().min(1, 'Policy area is required'),
  amount: z.number().positive('Amount must be positive'),
  reason: z.string().min(1, 'Reason is required'),
  payrollWeekId: z.string().uuid('Invalid payroll week ID')
});

export type TimerSessionInput = z.infer<typeof TimerSessionSchema>;
export type LeaveRequestInput = z.infer<typeof LeaveRequestSchema>;
export type PenaltyInput = z.infer<typeof PenaltySchema>;

export interface Employment {
  userId: string;
  rdpHost?: string;
  rdpUsername?: string;
  startDate: Date;
  firstPayrollEligibleFrom: Date;
  securityFundDeducted: boolean;
}

export interface TimerSession {
  id: string;
  userId: string;
  startedAt: Date;
  endedAt?: Date;
  activeSeconds: number;
  deviceId?: string;
  ip?: string;
  inactivityPauses?: any;
}

export interface DailySummary {
  id: string;
  userId: string;
  date: Date;
  billableSeconds: number;
  uploadsDone: boolean;
  meetsDailyMinimum: boolean;
}

export interface PayrollWeek {
  id: string;
  userId: string;
  weekStart: Date;
  weekEnd: Date;
  hoursDecimal: number;
  baseAmount: number;
  streakBonus: number;
  deductions?: any;
  finalAmount: number;
  status: PayrollStatus;
  paidAt?: Date;
  reference?: string;
}

export interface Leave {
  id: string;
  userId: string;
  type: LeaveType;
  dateFrom: Date;
  dateTo: Date;
  noticeHours: number;
  status: LeaveStatus;
  penalties?: any;
  requestedAt: Date;
  decidedAt?: Date;
  decidedByUserId?: string;
}

export interface Penalty {
  id: string;
  userId: string;
  policyArea: string;
  amount: number;
  reason: string;
  payrollWeekId: string;
}

export interface Recording {
  id: string;
  userId: string;
  weekStart: Date;
  fileKey: string;
  uploadedAt: Date;
  validated: boolean;
}
