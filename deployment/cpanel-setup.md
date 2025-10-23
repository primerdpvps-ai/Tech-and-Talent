# TTS PMS - cPanel Deployment Guide

## Pre-Deployment Checklist

### 1. Files to Upload
- **Root PHP Files**: Upload all files from `c:\xampp\htdocs\tts_pms\` to your cPanel public_html directory
- **Database Schema**: Import `database/tts_mysql_schema.sql` via phpMyAdmin
- **Configuration**: Update database credentials in `config/database.php`

### 2. Required Directory Structure
```
public_html/
├── index.php (Main landing page)
├── admin/ (Admin panel with all management pages)
├── api/ (API endpoints including Stripe integration)
├── auth/ (Authentication system)
├── config/ (Configuration files)
├── dashboard/ (Role-based dashboards)
│   ├── visitor/ (Visitor dashboard)
│   ├── candidate/ (Candidate dashboard)
│   ├── employee/ (Employee dashboard)
│   ├── manager/ (Manager dashboard)
│   └── ceo/ (CEO dashboard)
├── database/ (Schema files)
├── legal/ (Terms and Privacy pages)
├── packages/web/auth/ (Enhanced sign-up system)
├── uploads/ (File uploads - set 755 permissions)
└── vendor/ (Composer dependencies if any)
```

### 3. Database Setup
1. Create MySQL database in cPanel
2. Import `database/tts_mysql_schema.sql`
3. Update database credentials in `config/database.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'your_database_name');
   define('DB_USER', 'your_db_username');
   define('DB_PASS', 'your_db_password');
   ```

### 4. File Permissions
Set the following permissions:
- `uploads/` directory: 755
- `config/` directory: 644
- All PHP files: 644

### 5. Required PHP Extensions
Ensure these PHP extensions are enabled:
- mysqli or PDO
- json
- curl
- openssl
- mbstring

## Deployment Steps

### Step 1: Upload Files
1. Compress the TTS PMS folder (excluding node_modules)
2. Upload via cPanel File Manager or FTP
3. Extract in public_html directory

### Step 2: Database Configuration
1. Access phpMyAdmin from cPanel
2. Create new database
3. Import `database/tts_mysql_schema.sql`
4. Note database credentials

### Step 3: Update Configuration
Edit `config/database.php` with your cPanel database details:
```php
<?php
// Database Configuration for cPanel
define('DB_HOST', 'localhost'); // Usually localhost
define('DB_NAME', 'cpanel_username_dbname'); // Your database name
define('DB_USER', 'cpanel_username_dbuser'); // Your database user
define('DB_PASS', 'your_database_password'); // Your database password
define('DB_CHARSET', 'utf8mb4');

// Application Settings
define('APP_URL', 'https://yourdomain.com');
define('ADMIN_EMAIL', 'admin@yourdomain.com');
define('APP_DEBUG', false); // Set to false for production
?>
```

### Step 4: Test Installation
1. Visit your domain to see the landing page
2. Access `/admin/` for the admin panel (login: admin@tts-pms.com / admin123)
3. Test user dashboards at `/dashboard/`
4. Test API endpoints at `/api/`
5. Verify Stripe payment integration

### Step 5: Security Configuration
1. Change default admin password
2. Update Stripe API keys in `/admin/gigs.php`
3. Set proper file permissions
4. Enable SSL certificate

## Post-Deployment Configuration

### Admin Panel Access
- URL: `https://yourdomain.com/admin/`
- Default Login: `admin@tts-pms.com`
- Default Password: `admin123` (CHANGE IMMEDIATELY)

### User Dashboard Access
- **Main Login**: `https://yourdomain.com/auth/sign-in.php`
- **Demo Credentials**:
  - Visitor: `visitor@demo.com` / `demo123`
  - Candidate: `candidate@demo.com` / `demo123`
  - Employee: `employee@demo.com` / `demo123`
  - Manager: `manager@demo.com` / `demo123`
  - CEO: `ceo@demo.com` / `demo123`

### API Endpoints
- Contact Form: `/api/submit-contact.php`
- Proposal Form: `/api/submit-proposal.php`
- Stripe Payment Intent: `/api/create-payment-intent.php`
- Payroll API: `/api/payroll/`

### Automated Features
The system includes:
- **Role-based Dashboards**: Visitor, Candidate, Employee, Manager, CEO
- **Payment Processing**: Stripe integration for gig payments
- **Timesheet Management**: Employee time tracking with approval workflow
- **Leave Management**: Leave requests with manager approval
- **Client & Project Management**: Full CRM functionality
- **Legal Compliance**: Terms & Privacy with read tracking
- **Enhanced Security**: Strong password requirements, OTP verification
- **Weekly payroll automation**
- **CEO approval workflow**
- **Payslip generation**
- **Email notifications** (configure SMTP)

### Stripe Payment Configuration
1. Create a Stripe account at https://stripe.com
2. Get your API keys from Stripe Dashboard
3. Update the publishable key in `/admin/gigs.php` line 259:
   ```javascript
   const stripe = Stripe('pk_test_YOUR_PUBLISHABLE_KEY_HERE');
   ```
4. Update the secret key in `/api/create-payment-intent.php` (when implementing real Stripe)
5. Test payments with Stripe test cards

### Email Configuration
Update email settings in admin panel or `config/email.php`:
```php
// SMTP Configuration
define('SMTP_HOST', 'your-smtp-server.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@domain.com');
define('SMTP_PASSWORD', 'your-email-password');
define('SMTP_ENCRYPTION', 'tls');
```

## Troubleshooting

### Common Issues
1. **Database Connection Error**: Check credentials in `config/database.php`
2. **File Upload Issues**: Verify `uploads/` directory permissions (755)
3. **Admin Panel 404**: Ensure `.htaccess` is uploaded if using URL rewriting
4. **API Errors**: Check PHP error logs in cPanel

### Support Files
- Error logs: Check cPanel Error Logs
- Debug mode: Set `APP_DEBUG = true` temporarily
- Database logs: Enable MySQL query logging

## Security Recommendations
1. Change all default passwords
2. Enable two-factor authentication
3. Regular database backups
4. Keep PHP version updated
5. Monitor access logs
6. Use strong SSL certificates

## Backup Strategy
1. **Database**: Weekly automated backups via cPanel
2. **Files**: Monthly full site backups
3. **Critical Data**: Daily payroll and user data exports

## Performance Optimization
1. Enable PHP OPcache
2. Use CDN for static assets
3. Optimize database queries
4. Enable GZIP compression
5. Minify CSS/JS files

---

**Note**: This deployment guide assumes a standard cPanel hosting environment. Adjust paths and configurations based on your specific hosting provider's setup.
