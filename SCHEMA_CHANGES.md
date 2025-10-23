# Database Schema Changes

## Overview

The Prisma schema has been completely updated to match the new TTS PMS requirements with the following entities:

## Updated Models

### User
- **Fields**: id, email, emailVerified, phone, phoneVerified, passwordHash, fullName, dob, city, province, country, address, coreLocked, role, createdAt, updatedAt
- **Role Enum**: VISITOR, CANDIDATE, NEW_EMPLOYEE, EMPLOYEE, MANAGER, CEO
- **Indexes**: email, role

### Evaluation
- **Fields**: id, userId, age, deviceType, ramText, processorText, stableInternet, provider, linkSpeed, numUsers, speedtestUrl, profession, dailyTimeOk, timeWindows, qualification, confidentialityOk, typingOk, result, reasons, attempts, createdAt
- **Result Enum**: ELIGIBLE, PENDING, REJECTED
- **Relations**: User (many-to-one)
- **Indexes**: userId, result

### Application
- **Fields**: id, userId, jobType, status, reasons, submittedAt, decidedAt, decidedByUserId, files
- **JobType Enum**: FULL_TIME, PART_TIME
- **Status Enum**: UNDER_REVIEW, APPROVED, REJECTED
- **Relations**: User (many-to-one), DecidedBy User (many-to-one, optional)
- **Indexes**: userId, status

### Employment
- **Fields**: userId (PK), rdpHost, rdpUsername, startDate, firstPayrollEligibleFrom, securityFundDeducted
- **Relations**: User (one-to-one)

### TimerSession
- **Fields**: id, userId, startedAt, endedAt, activeSeconds, deviceId, ip, inactivityPauses
- **Relations**: User (many-to-one)
- **Indexes**: userId, startedAt

### DailySummary
- **Fields**: id, userId, date, billableSeconds, uploadsDone, meetsDailyMinimum
- **Relations**: User (many-to-one)
- **Unique**: userId + date
- **Indexes**: userId, date

### PayrollWeek
- **Fields**: id, userId, weekStart, weekEnd, hoursDecimal, baseAmount, streakBonus, deductions, finalAmount, status, paidAt, reference
- **Status Enum**: PENDING, PROCESSING, PAID, DELAYED
- **Relations**: User (many-to-one), Penalties (one-to-many)
- **Unique**: userId + weekStart
- **Indexes**: userId, status

### Leave
- **Fields**: id, userId, type, dateFrom, dateTo, noticeHours, status, penalties, requestedAt, decidedAt, decidedByUserId
- **Type Enum**: SHORT, ONE_DAY, LONG
- **Status Enum**: PENDING, APPROVED, REJECTED
- **Relations**: User (many-to-one), DecidedBy User (many-to-one, optional)
- **Indexes**: userId, status

### Penalty
- **Fields**: id, userId, policyArea, amount, reason, payrollWeekId
- **Relations**: User (many-to-one), PayrollWeek (many-to-one)
- **Indexes**: userId, payrollWeekId

### Recording
- **Fields**: id, userId, weekStart, fileKey, uploadedAt, validated
- **Relations**: User (many-to-one)
- **Indexes**: userId, weekStart

### Gig
- **Fields**: id, slug, title, description, price, badges, active, createdAt
- **Unique**: slug
- **Indexes**: active, slug

### ClientRequest
- **Fields**: id, businessName, contactEmail, contactPhone, brief, attachments, status, createdAt
- **Status Enum**: NEW, IN_REVIEW, CLOSED
- **Indexes**: status, contactEmail

## Migration Instructions

1. **Generate Prisma Client:**
   ```bash
   npm run prisma:generate
   ```

2. **Run Migration:**
   ```bash
   npm run prisma:migrate
   ```
   
   Or manually execute the SQL in: `packages/db/prisma/migrations/20241017_initial_schema/migration.sql`

3. **Seed Database:**
   ```bash
   npm run prisma:seed
   ```

## Key Features

- **MySQL-compatible types** with proper DECIMAL, DATE, and JSON fields
- **Comprehensive indexing** for performance optimization
- **Foreign key relationships** with CASCADE delete where appropriate
- **Enum types** for consistent data validation
- **JSON fields** for flexible data storage (timeWindows, reasons, deductions, etc.)
- **Unique constraints** to prevent data duplication

## Sample Data

The seed script creates:
- 5 users with different roles (CEO, Manager, Employee, New Employee, Candidate)
- Employment records for employees
- Sample evaluation and application data
- 3 sample gigs
- 2 sample client requests

## Updated Types

The `@tts-pms/infra` package has been updated with:
- New UserRole enum matching the schema
- Validation schemas for evaluations and applications
- TypeScript interfaces for all entities
- Form validation schemas using Zod
