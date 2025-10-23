# TTS PMS - Upload Checklist for cPanel

## ✅ Pre-Upload Preparation

### Files to Include
- [ ] `index.php` - Main landing page
- [ ] `admin/` - Complete admin panel directory
- [ ] `api/` - All API endpoints
- [ ] `config/` - Configuration files
- [ ] `database/` - Schema files
- [ ] `uploads/` - Create empty directory for file uploads
- [ ] `.htaccess` - URL rewriting rules (if needed)

### Files to Exclude
- [ ] `packages/web/node_modules/` - Next.js dependencies (not needed for PHP version)
- [ ] `.git/` - Git repository files
- [ ] `*.log` - Log files
- [ ] `composer.lock` - Composer lock file (if not using Composer)

## ✅ Database Preparation

### Schema Files Ready
- [ ] `database/schema-cpanel.sql` - Main database schema
- [ ] `database/tts_mysql_schema.sql` - Complete TTS schema
- [ ] Default admin user included in schema
- [ ] System settings configured

### Database Features Included
- [ ] User management tables
- [ ] Payroll automation tables
- [ ] OTP authentication system
- [ ] Client request handling
- [ ] Audit logging
- [ ] File upload tracking

## ✅ Configuration Files

### Database Configuration
- [ ] `config/database.php` - Database connection settings
- [ ] `config/init.php` - Application initialization
- [ ] Update database credentials for cPanel environment

### Application Settings
- [ ] Admin panel login credentials
- [ ] API endpoint configurations
- [ ] Email notification settings
- [ ] File upload paths
- [ ] Security settings

## ✅ Core Features Implemented

### Landing Page (`index.php`)
- [ ] Responsive design with Divi compatibility
- [ ] Contact form integration
- [ ] Service showcase
- [ ] Testimonials section
- [ ] Career opportunities
- [ ] Professional branding

### Admin Panel (`admin/`)
- [ ] `login.php` - Secure admin authentication
- [ ] `index.php` - Dashboard with statistics
- [ ] `applications.php` - Job application management
- [ ] `payroll-automation.php` - Automated payroll system
- [ ] `payslip-generator.php` - PDF payslip generation
- [ ] Role-based access control

### API Endpoints (`api/`)
- [ ] `submit-contact.php` - Contact form handler
- [ ] `submit-proposal.php` - Proposal request handler
- [ ] Database integration for all endpoints
- [ ] Input validation and sanitization
- [ ] Error handling and logging

### Payroll System
- [ ] Automated weekly calculations
- [ ] CEO approval workflow
- [ ] Payroll Manager processing
- [ ] Streak bonus calculations (Rs. 500 for 28+ days)
- [ ] Deduction management
- [ ] PDF payslip generation

## ✅ Security Features

### Authentication & Authorization
- [ ] Dual OTP system (Email + SMS)
- [ ] Role-based access control
- [ ] Session management
- [ ] Password hashing
- [ ] Input sanitization

### Data Protection
- [ ] SQL injection prevention
- [ ] XSS protection
- [ ] CSRF token implementation
- [ ] File upload validation
- [ ] Audit trail logging

## ✅ Testing Checklist

### Before Upload
- [ ] Test all forms on local environment
- [ ] Verify database connections
- [ ] Check file permissions
- [ ] Validate API responses
- [ ] Test admin panel functionality

### After Upload
- [ ] Landing page loads correctly
- [ ] Contact form submissions work
- [ ] Admin panel accessible
- [ ] Database connections established
- [ ] File uploads functional
- [ ] Payroll automation operational

## ✅ Post-Upload Configuration

### Immediate Tasks
- [ ] Import database schema
- [ ] Update database credentials
- [ ] Set file permissions (uploads/ = 755)
- [ ] Test admin login
- [ ] Change default passwords

### System Configuration
- [ ] Configure email settings
- [ ] Set up automated backups
- [ ] Enable error logging
- [ ] Configure SSL certificate
- [ ] Test all API endpoints

### User Management
- [ ] Create CEO user account
- [ ] Create Payroll Manager account
- [ ] Set up employee accounts
- [ ] Configure role permissions
- [ ] Test approval workflows

## ✅ Performance & Monitoring

### Optimization
- [ ] Enable PHP caching
- [ ] Optimize database queries
- [ ] Compress static assets
- [ ] Configure CDN (if available)
- [ ] Monitor resource usage

### Monitoring Setup
- [ ] Error log monitoring
- [ ] Database performance tracking
- [ ] User activity logging
- [ ] Payroll processing alerts
- [ ] System health checks

## ✅ Documentation

### User Guides
- [ ] Admin panel user manual
- [ ] Payroll processing guide
- [ ] Employee onboarding process
- [ ] API documentation
- [ ] Troubleshooting guide

### Technical Documentation
- [ ] Database schema documentation
- [ ] API endpoint specifications
- [ ] Security implementation details
- [ ] Backup and recovery procedures
- [ ] System architecture overview

---

## 📋 Final Upload Package Contents

```
tts_pms_production/
├── index.php
├── admin/
│   ├── index.php
│   ├── login.php
│   ├── logout.php
│   ├── applications.php
│   ├── payroll-automation.php
│   └── payslip-generator.php
├── api/
│   ├── submit-contact.php
│   └── submit-proposal.php
├── config/
│   ├── database.php
│   └── init.php
├── database/
│   ├── schema-cpanel.sql
│   └── tts_mysql_schema.sql
├── uploads/
│   └── payslips/
└── deployment/
    ├── cpanel-setup.md
    └── upload-checklist.md
```

**Total Estimated Upload Size**: ~2-5 MB (excluding any media files)

**Deployment Time**: 15-30 minutes for complete setup

**Go-Live Checklist**: All items above must be ✅ before making the system live for production use.
