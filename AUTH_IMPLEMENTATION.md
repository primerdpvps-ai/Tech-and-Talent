# Authentication & Authorization Implementation

## Overview

The TTS PMS authentication system has been implemented with NextAuth.js using Credentials provider, dual-OTP verification, RBAC (Role-Based Access Control), and core field protection.

## Features Implemented

### ✅ NextAuth with Credentials Provider
- **Location**: `packages/web/src/lib/auth.ts`
- **Features**:
  - Email + password authentication
  - JWT sessions (24-hour expiry)
  - Password hashing with bcrypt (12 rounds)
  - User role and core lock status in session

### ✅ User Registration API
- **Endpoint**: `POST /api/auth/register`
- **Features**:
  - Email domain restriction (configurable via `ALLOWED_EMAIL_DOMAINS`)
  - Default: only @gmail.com accepted
  - Password hashing with bcrypt
  - New users start as `VISITOR` role

### ✅ Dual-OTP Verification System
- **Request OTP**: `POST /api/otp/request`
- **Verify OTP**: `POST /api/otp/verify`
- **Features**:
  - Email and SMS OTP support
  - Rate limiting (3 requests per 5 minutes)
  - 6-digit codes, 10-minute expiry
  - 3 verification attempts per code
  - Multiple purposes: registration, login, phone/email verification, password reset

### ✅ Core Field Protection
- **Protected Fields**: `fullName`, `dob` (date of birth)
- **Mechanism**: `coreLocked` boolean flag on users
- **Workflow**:
  - Users with `coreLocked: true` cannot directly edit core fields
  - Changes require admin approval via `AdminApprovalRequest` table
  - Approval requests tracked with current/requested data

### ✅ RBAC (Role-Based Access Control)
- **Roles Hierarchy**: VISITOR → CANDIDATE → NEW_EMPLOYEE → EMPLOYEE → MANAGER → CEO
- **Helper Functions**:
  - `hasRole(userRole, requiredRole)` - Check role hierarchy
  - `requireRole(userRole, requiredRole)` - Enforce role requirement
  - `getRoleDashboard(role)` - Get appropriate dashboard URL

### ✅ Route Protection Middleware
- **Location**: `packages/web/src/middleware.ts`
- **Features**:
  - Automatic authentication checks
  - Role-based route access control
  - Automatic dashboard redirection based on user role
  - Protected admin and system routes

## Database Schema Updates

### New Tables Added

#### OTP Table
```sql
CREATE TABLE `otps` (
  `id` VARCHAR(191) NOT NULL PRIMARY KEY,
  `email` VARCHAR(191) NULL,
  `phone` VARCHAR(191) NULL,
  `code` VARCHAR(191) NOT NULL,
  `type` VARCHAR(191) NOT NULL, -- 'email' or 'sms'
  `purpose` VARCHAR(191) NOT NULL, -- 'registration', 'login', etc.
  `expiresAt` DATETIME(3) NOT NULL,
  `verified` BOOLEAN NOT NULL DEFAULT false,
  `attempts` INTEGER NOT NULL DEFAULT 0,
  `createdAt` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3)
);
```

#### Admin Approval Requests Table
```sql
CREATE TABLE `admin_approval_requests` (
  `id` VARCHAR(191) NOT NULL PRIMARY KEY,
  `userId` VARCHAR(191) NOT NULL,
  `requestType` VARCHAR(191) NOT NULL, -- 'name_change', 'dob_change'
  `currentData` JSON NOT NULL,
  `requestedData` JSON NOT NULL,
  `reason` TEXT NULL,
  `status` VARCHAR(191) NOT NULL DEFAULT 'PENDING',
  `reviewedBy` VARCHAR(191) NULL,
  `reviewedAt` DATETIME(3) NULL,
  `createdAt` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3)
);
```

## API Endpoints

### Authentication
- `POST /api/auth/register` - User registration with domain validation
- `POST /api/auth/[...nextauth]` - NextAuth endpoints (signin, callback, etc.)

### OTP System
- `POST /api/otp/request` - Request OTP (email or SMS)
- `POST /api/otp/verify` - Verify OTP code

### User Management
- `GET /api/user/profile` - Get user profile
- `PUT /api/user/profile` - Update profile (respects core lock)

### Admin Operations
- `GET /api/admin/approval-requests` - List approval requests
- `POST /api/admin/approval-requests` - Create approval request

## Role-Based Dashboards

### Dashboard Routes
- **VISITOR**: `/dashboard/visitor` - Onboarding and evaluation
- **CANDIDATE**: `/dashboard/candidate` - Application process
- **NEW_EMPLOYEE**: `/dashboard/employee` - Training and basic features
- **EMPLOYEE**: `/dashboard/employee` - Full employee features
- **MANAGER**: `/dashboard/manager` - Team management
- **CEO**: `/dashboard/ceo` - Executive overview

### Route Protection
```typescript
// Example usage in API routes
const authResult = await requireRole(request, UserRole.MANAGER);
if (!authResult.success) {
  return authResult.response; // 401 or 403 error
}

// Example middleware usage
export const middleware = createRoleMiddleware({
  requiredRole: UserRole.EMPLOYEE,
  redirectOnFail: true
});
```

## Configuration

### Environment Variables
```bash
# Required
NEXTAUTH_SECRET="your-secret-key"
NEXTAUTH_URL="http://localhost:3000"
DATABASE_URL="mysql://user:pass@localhost:3306/tts_pms"

# Optional
ALLOWED_EMAIL_DOMAINS="gmail.com,company.com" # Comma-separated
```

### OTP Configuration
```typescript
const otpConfig = {
  length: 6,           // Code length
  expiryMinutes: 10,   // Expiry time
  maxAttempts: 3,      // Verification attempts
  rateLimitMinutes: 5, // Rate limit window
  maxRequestsPerPeriod: 3 // Max requests per window
};
```

## Security Features

### Password Security
- bcrypt hashing with 12 rounds
- Minimum 6 characters (configurable via Zod schema)

### OTP Security
- Short-lived codes (10 minutes)
- Rate limiting per email/phone
- Attempt limiting (3 tries per code)
- Automatic invalidation of old codes

### Session Security
- JWT with 24-hour expiry
- Secure session management via NextAuth
- Role-based access control

### Core Field Protection
- Immutable core fields when `coreLocked: true`
- Admin approval workflow for changes
- Audit trail of change requests

## Usage Examples

### Protecting API Routes
```typescript
// In API route
export async function GET(request: NextRequest) {
  const authResult = await requireRole(request, UserRole.MANAGER);
  if (!authResult.success) return authResult.response;
  
  const { session } = authResult;
  // Continue with authenticated logic
}
```

### Client-Side Role Checks
```typescript
// In React components
const canManageTeam = useRoleGuard(UserRole.MANAGER, session?.user.role);

// Higher-order component
const ProtectedComponent = withRoleGuard(MyComponent, UserRole.EMPLOYEE);
```

### OTP Workflow
```typescript
// Request OTP
const response = await fetch('/api/otp/request', {
  method: 'POST',
  body: JSON.stringify({
    type: 'email',
    email: 'user@gmail.com',
    purpose: 'email_verification'
  })
});

// Verify OTP
const verifyResponse = await fetch('/api/otp/verify', {
  method: 'POST',
  body: JSON.stringify({
    type: 'email',
    email: 'user@gmail.com',
    code: '123456',
    purpose: 'email_verification'
  })
});
```

## Testing

### Default Test Accounts
After running `npm run prisma:seed`:

- **CEO**: `ceo@tts-pms.com` / `ceo123`
- **Manager**: `manager@tts-pms.com` / `manager123`
- **Employee**: `employee@tts-pms.com` / `employee123`
- **New Employee**: `newemployee@tts-pms.com` / `newemployee123`
- **Candidate**: `candidate@tts-pms.com` / `candidate123`

### Testing OTP System
OTP codes are logged to console in development:
```
📧 Email OTP for user@gmail.com (registration): 123456
📱 SMS OTP for +1234567890 (phone_verification): 789012
```

## Next Steps

1. **Install dependencies** and run `npm run prisma:generate`
2. **Run migrations** to create new tables
3. **Configure email/SMS providers** for production OTP delivery
4. **Customize role permissions** based on business requirements
5. **Add additional dashboard features** for each role

The authentication system is now fully implemented with enterprise-grade security features, role-based access control, and comprehensive user management capabilities.
