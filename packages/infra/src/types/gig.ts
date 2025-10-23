import { z } from 'zod';

export enum ClientRequestStatus {
  NEW = 'NEW',
  IN_REVIEW = 'IN_REVIEW',
  CLOSED = 'CLOSED'
}

export const GigSchema = z.object({
  slug: z.string().min(1, 'Slug is required').regex(/^[a-z0-9-]+$/, 'Slug must contain only lowercase letters, numbers, and hyphens'),
  title: z.string().min(1, 'Title is required'),
  description: z.string().min(1, 'Description is required'),
  price: z.number().positive('Price must be positive'),
  badges: z.array(z.string()).optional(),
  active: z.boolean().default(true)
});

export const ClientRequestSchema = z.object({
  businessName: z.string().min(1, 'Business name is required'),
  contactEmail: z.string().email('Valid email is required'),
  contactPhone: z.string().optional(),
  brief: z.string().min(10, 'Brief must be at least 10 characters'),
  attachments: z.record(z.string()).optional()
});

export type GigInput = z.infer<typeof GigSchema>;
export type ClientRequestInput = z.infer<typeof ClientRequestSchema>;

export interface Gig {
  id: string;
  slug: string;
  title: string;
  description: string;
  price: number;
  badges?: string[];
  active: boolean;
  createdAt: Date;
}

export interface ClientRequest {
  id: string;
  businessName: string;
  contactEmail: string;
  contactPhone?: string;
  brief: string;
  attachments?: any;
  status: ClientRequestStatus;
  createdAt: Date;
}
