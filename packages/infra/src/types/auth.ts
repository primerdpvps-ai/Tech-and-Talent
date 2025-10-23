import { z } from 'zod';

export enum UserRole {
  VISITOR = 'VISITOR',
  CANDIDATE = 'CANDIDATE',
  NEW_EMPLOYEE = 'NEW_EMPLOYEE',
  EMPLOYEE = 'EMPLOYEE',
  MANAGER = 'MANAGER',
  CEO = 'CEO'
}

export enum EvaluationResult {
  ELIGIBLE = 'ELIGIBLE',
  PENDING = 'PENDING',
  REJECTED = 'REJECTED'
}

export enum JobType {
  FULL_TIME = 'FULL_TIME',
  PART_TIME = 'PART_TIME'
}

export enum ApplicationStatus {
  UNDER_REVIEW = 'UNDER_REVIEW',
  APPROVED = 'APPROVED',
  REJECTED = 'REJECTED'
}

export const LoginSchema = z.object({
  email: z.string().email('Invalid email address'),
  password: z.string().min(6, 'Password must be at least 6 characters')
});

export const RegisterSchema = z.object({
  email: z.string().email('Invalid email address'),
  password: z.string().min(8, 'Password must be at least 8 characters'),
  fullName: z.string().min(1, 'Full name is required'),
  phone: z.string().optional(),
  city: z.string().optional(),
  province: z.string().optional(),
  country: z.string().optional(),
  address: z.string().optional(),
  role: z.nativeEnum(UserRole).default(UserRole.VISITOR)
});

export const EvaluationSchema = z.object({
  age: z.number().min(18, 'Must be at least 18 years old').max(100),
  deviceType: z.string().min(1, 'Device type is required'),
  ramText: z.string().min(1, 'RAM information is required'),
  processorText: z.string().min(1, 'Processor information is required'),
  stableInternet: z.boolean(),
  provider: z.string().min(1, 'Internet provider is required'),
  linkSpeed: z.string().min(1, 'Link speed is required'),
  numUsers: z.number().min(1, 'Number of users is required'),
  speedtestUrl: z.string().url('Valid speedtest URL is required'),
  profession: z.string().min(1, 'Profession is required'),
  dailyTimeOk: z.boolean(),
  timeWindows: z.array(z.string()),
  qualification: z.string().min(1, 'Qualification is required'),
  confidentialityOk: z.boolean(),
  typingOk: z.boolean()
});

export const ApplicationSchema = z.object({
  jobType: z.nativeEnum(JobType),
  files: z.record(z.string()).optional()
});

export type LoginInput = z.infer<typeof LoginSchema>;
export type RegisterInput = z.infer<typeof RegisterSchema>;
export type EvaluationInput = z.infer<typeof EvaluationSchema>;
export type ApplicationInput = z.infer<typeof ApplicationSchema>;

export interface User {
  id: string;
  email: string;
  emailVerified?: Date;
  phone?: string;
  phoneVerified?: Date;
  fullName: string;
  dob?: Date;
  city?: string;
  province?: string;
  country?: string;
  address?: string;
  coreLocked: boolean;
  role: UserRole;
  createdAt: Date;
  updatedAt: Date;
}

export interface AuthSession {
  user: User;
  accessToken: string;
  refreshToken?: string;
  expiresAt: Date;
}
