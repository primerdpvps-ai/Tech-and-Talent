# TTS PMS - Complete Project Summary

## 🎯 Project Overview

**TTS PMS (Time Tracking System - Performance Management System)** is a comprehensive workforce management platform designed for remote teams. It provides end-to-end solutions for employee onboarding, time tracking, performance monitoring, payroll processing, and administrative oversight.

## 🏗️ Architecture

### Technology Stack
- **Frontend**: Next.js 14 (App Router), React 18, TypeScript
- **Backend**: Next.js API Routes, Node.js
- **Database**: MySQL with Prisma ORM
- **Authentication**: NextAuth.js with JWT
- **Styling**: Tailwind CSS + Headless UI
- **File Storage**: MinIO (S3-compatible) / AWS S3
- **Email**: Mailhog (dev) / SMTP providers (prod)
- **Caching**: Redis
- **Job Scheduling**: node-cron
- **Containerization**: Docker + Docker Compose

### System Components

#### 🌐 Web Application (`packages/web/`)
- **Pages**: Public landing, auth, dashboards, admin panels
- **Components**: Reusable UI components, forms, charts
- **API Routes**: RESTful endpoints for all operations
- **Services**: Business logic and external integrations
- **Jobs**: Background task processing
- **Authentication**: Role-based access control

#### 🗄️ Database (`packages/db/`)
- **Schema**: Comprehensive Prisma schema
- **Models**: Users, Employment, Applications, Payroll, etc.
- **Migrations**: Database version control
- **Seed Data**: Development sample data

#### 🔧 Infrastructure (`packages/infra/`)
- **Utilities**: Shared functions and helpers
- **Types**: TypeScript type definitions
- **Constants**: Application-wide constants

## 🚀 Key Features

### 👥 User Management
- **Multi-role System**: CEO, Admin, Manager, Employee, New Employee, Visitor
- **Registration Flow**: Email verification, document upload, approval process
- **Profile Management**: Personal info, employment details, performance metrics

### 💼 Job Application System
- **Gig Marketplace**: Public job listings with detailed descriptions
- **Application Process**: Multi-step form with document uploads
- **Review Workflow**: Manager/admin approval with notes
- **Document Management**: CNIC, utility bills, resumes, contracts

### ⏱️ Time Tracking
- **Timer Sessions**: Start/pause/stop with device tracking
- **Activity Monitoring**: Mouse, keyboard, window focus tracking
- **Screenshot Capture**: Periodic screenshots with S3 storage
- **Daily Summaries**: Automated aggregation of work hours

### 📊 Performance Management
- **Streak Tracking**: Consecutive working days
- **Compliance Monitoring**: Daily minimum hours enforcement
- **Performance Metrics**: Productivity scores and analytics
- **Bonus/Penalty System**: Automated reward/penalty application

### 💰 Payroll Processing
- **Weekly Batches**: Automated payroll composition
- **Rate Calculations**: Base, overtime, weekend multipliers
- **Bonus Integration**: Performance, attendance, referral bonuses
- **Penalty Deductions**: Late arrivals, policy violations
- **Approval Workflow**: CEO approval before payment

### 📋 Administrative Tools
- **Dashboard Analytics**: System-wide metrics and insights
- **Employee Management**: Role changes, penalties, RDP access
- **Leave Management**: Request approval with policy enforcement
- **System Settings**: Configurable rates, rules, and templates

### 🤖 Agent API System
- **Device Registration**: Secure device authentication
- **HMAC Signatures**: Request integrity verification
- **Timer Integration**: Remote time tracking capabilities
- **Activity Submission**: Bulk activity data processing
- **Operational Windows**: Time-based access control

### 🔄 Job Scheduling System
- **Hourly Jobs**: Timer session aggregation
- **Daily Jobs**: Streak computation, database maintenance
- **Weekly Jobs**: Payroll composition, upload compliance
- **Cleanup Jobs**: Data retention and system optimization
- **Health Monitoring**: System health checks and alerts

## 🐳 Docker Development Environment

### Services
- **Web Application** (Port 3000): Main Next.js application
- **MySQL Database** (Port 3306): Primary data storage
- **MinIO Storage** (Ports 9000/9001): S3-compatible file storage
- **Redis Cache** (Port 6379): Session and cache storage
- **Mailhog** (Port 8025): Email testing interface
- **Prisma Studio** (Port 5555): Database management UI

### Quick Start
```bash
# Clone and setup
git clone <repository>
cd tts_pms
cp .env.example .env

# Start all services
docker-compose up -d

# Initialize database
docker-compose run --rm db-migrate

# Access application
open http://localhost:3000
```

## 🔐 Security Features

### Authentication & Authorization
- **JWT Tokens**: Secure session management
- **Role-based Access**: Granular permission system
- **Password Hashing**: bcrypt with salt rounds
- **Email Verification**: Account activation workflow

### Agent Security
- **Device Registration**: Unique device secrets
- **HMAC Signatures**: Request tampering prevention
- **Operational Windows**: Time-based access restrictions
- **Request Logging**: Comprehensive audit trails

### Data Protection
- **Input Validation**: Zod schema validation
- **SQL Injection Prevention**: Prisma ORM protection
- **File Upload Security**: Type and size restrictions
- **Environment Secrets**: Secure configuration management

## 📈 Business Logic

### Employment Lifecycle
1. **Visitor**: Can browse jobs and apply
2. **Candidate**: Application under review
3. **New Employee**: Limited access during training
4. **Employee**: Full system access with tracking
5. **Manager**: Team oversight and approval powers
6. **Admin/CEO**: Complete system administration

### Payroll Calculation
```
Base Pay = Hours × Hourly Rate
Weekend Pay = Weekend Hours × Rate × 1.5
Overtime Pay = Overtime Hours × Rate × 1.5
Bonuses = Performance + Attendance + Referral
Penalties = Late Arrivals + Policy Violations
Net Pay = Base + Weekend + Overtime + Bonuses - Penalties
```

### Performance Metrics
- **Daily Compliance**: Minimum 6 hours per day
- **Streak Tracking**: Consecutive compliant days
- **Weekly Uploads**: Required file submissions
- **Activity Scores**: Mouse/keyboard activity analysis

## 🔧 Configuration

### Environment Variables
- **Database**: MySQL connection strings
- **Authentication**: NextAuth and JWT secrets
- **Storage**: S3/MinIO configuration
- **Email**: SMTP provider settings
- **Business Rules**: Rates, bonuses, penalties
- **Security**: API tokens and device secrets

### System Settings
- **Working Hours**: Configurable time windows
- **Rates & Bonuses**: Dynamic pricing rules
- **Penalties**: Violation consequences
- **Templates**: Email/SMS message templates
- **Legal Documents**: Terms, privacy policy

## 📊 Data Models

### Core Entities
- **User**: Authentication and profile data
- **Employment**: Job details and RDP access
- **Application**: Job application workflow
- **TimerSession**: Work time tracking
- **DailySummary**: Aggregated daily metrics
- **PayrollBatch**: Weekly payment processing
- **Gig**: Job listings and requirements

### Relationship Patterns
- **User → Employment**: One-to-one employment record
- **User → Applications**: One-to-many job applications
- **User → TimerSessions**: One-to-many work sessions
- **Manager → Employees**: One-to-many team management
- **PayrollBatch → Entries**: One-to-many employee payments

## 🚀 Deployment Options

### Development
- **Docker Compose**: Local development environment
- **Hot Reload**: Instant code changes
- **Debug Tools**: Prisma Studio, Mailhog, MinIO Console

### Production
- **Container Registry**: Docker image deployment
- **Managed Services**: AWS RDS, S3, ElastiCache
- **Load Balancing**: Multiple application instances
- **Monitoring**: Health checks and alerting

## 📚 Documentation

### User Guides
- **Employee Handbook**: System usage instructions
- **Manager Guide**: Team oversight procedures
- **Admin Manual**: System administration

### Technical Documentation
- **API Reference**: Complete endpoint documentation
- **Database Schema**: Entity relationships and constraints
- **Job System**: Background task scheduling
- **Agent Integration**: Desktop application API

### Setup Guides
- **Docker Setup**: Development environment
- **Production Deployment**: Server configuration
- **Agent Installation**: Windows desktop client

## 🔮 Future Enhancements

### Planned Features
- **Mobile Application**: iOS/Android apps
- **Advanced Analytics**: Machine learning insights
- **Integration APIs**: Third-party system connections
- **Multi-tenant Support**: Multiple organization support

### Scalability Improvements
- **Microservices**: Service decomposition
- **Event Sourcing**: Audit trail enhancement
- **Caching Strategy**: Performance optimization
- **Database Sharding**: Horizontal scaling

## 🆘 Support & Maintenance

### Monitoring
- **Health Checks**: Automated system monitoring
- **Job Scheduling**: Background task management
- **Error Tracking**: Comprehensive logging
- **Performance Metrics**: System optimization

### Backup & Recovery
- **Database Backups**: Automated daily backups
- **File Storage**: Redundant S3 storage
- **Configuration**: Environment backup
- **Disaster Recovery**: System restoration procedures

## 📞 Contact & Resources

### Development Team
- **Architecture**: System design and scalability
- **Backend**: API development and database design
- **Frontend**: User interface and experience
- **DevOps**: Infrastructure and deployment

### External Resources
- **Next.js**: https://nextjs.org/docs
- **Prisma**: https://www.prisma.io/docs
- **Docker**: https://docs.docker.com/
- **Tailwind CSS**: https://tailwindcss.com/docs

---

**TTS PMS** - Empowering remote teams with comprehensive workforce management solutions.
