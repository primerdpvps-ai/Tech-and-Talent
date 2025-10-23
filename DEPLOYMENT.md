# TTS PMS Super Admin - Production Deployment Guide

## 🚀 **DEPLOYMENT OVERVIEW**

This guide covers the complete deployment of TTS PMS Super Admin integration (Phases 3-5) to your cPanel hosting environment at `https://pms.prizmasoft.com`.

**System Requirements:**
- PHP 8.4.11+ with extensions: mysqli, curl, mbstring, json, zip, openssl
- MariaDB 10.6.23+ with UTF8MB4 charset
- cPanel hosting with SSH/file manager access
- Minimum 500MB disk space for backups

---

## 📁 **FILE UPLOAD STRUCTURE**

Upload all files to your cPanel `public_html` directory following this structure:

```
public_html/
├── admin/                          # Super Admin Panel
│   ├── api/                       # Admin API endpoints
│   │   ├── admin-sync-v2.php      # Real-time sync API
│   │   ├── admin-sync-helpers.php # Sync helper functions
│   │   ├── save-layout.php        # Visual Builder save
│   │   ├── load-layout.php        # Visual Builder load
│   │   ├── rollback.php           # Version rollback
│   │   ├── save-setting.php       # Settings management
│   │   └── test-email.php         # SMTP testing
│   ├── tools/                     # Admin utilities
│   │   ├── process-sync-queue.php # Background processor
│   │   └── backup-restore.php     # Backup system
│   ├── backups/                   # Backup storage (auto-created)
│   ├── logs/                      # Log files (auto-created)
│   ├── visual-builder.php         # Drag-drop page builder
│   ├── settings-hub.php           # Global settings interface
│   ├── page-builder.php           # Page management
│   ├── database-manager.php       # Database tools
│   └── audit-log.php              # Audit viewer
├── api/                           # Public API endpoints
│   └── admin-sync.php             # Legacy sync endpoint
├── config/                        # Configuration files
│   ├── init.php                   # System initialization
│   ├── database.php               # Database connection
│   ├── app_config.php             # Application settings
│   └── email_config.php           # SMTP configuration
├── database/                      # SQL migrations
│   ├── phase3_cms_migrations.sql  # CMS tables
│   ├── phase4_settings_migrations.sql # Settings
│   ├── phase5_sync_migrations.sql # Sync system
│   ├── phase5_queue_indexes.sql   # Queue optimization
│   └── phase5_backup_indexes.sql  # Backup system
├── packages/web/src/pages/        # Generated pages (auto-created)
├── cache/                         # System cache (auto-created)
│   ├── settings/                  # Settings cache
│   └── modules/                   # Module cache
└── install_admin_integration.php  # Installation script
```

---

## 🔐 **FILE PERMISSIONS**

Set the following permissions after upload:

### **Directories (755)**
```bash
chmod 755 admin/
chmod 755 admin/api/
chmod 755 admin/tools/
chmod 755 admin/backups/
chmod 755 admin/logs/
chmod 755 config/
chmod 755 database/
chmod 755 packages/web/src/pages/
chmod 755 cache/
chmod 755 cache/settings/
chmod 755 cache/modules/
```

### **Files (644)**
```bash
chmod 644 admin/*.php
chmod 644 admin/api/*.php
chmod 644 admin/tools/*.php
chmod 644 config/*.php
chmod 644 database/*.sql
chmod 644 install_admin_integration.php
```

### **Executable Scripts (755)**
```bash
chmod 755 admin/tools/process-sync-queue.php
chmod 755 admin/tools/backup-restore.php
```

---

## 🛡️ **SECURITY HARDENING**

### **1. Directory Protection**

Create `.htaccess` files in sensitive directories:

**`admin/backups/.htaccess`:**
```apache
Order Deny,Allow
Deny from all
```

**`admin/logs/.htaccess`:**
```apache
Order Deny,Allow
Deny from all
```

**`cache/.htaccess`:**
```apache
Order Deny,Allow
Deny from all
```

**`database/.htaccess`:**
```apache
Order Deny,Allow
Deny from all
```

### **2. Force HTTPS**

Add to main `.htaccess` in `public_html`:
```apache
# Force HTTPS
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Security Headers
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"
Header always set Referrer-Policy "strict-origin-when-cross-origin"
Header always set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' cdn.jsdelivr.net cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' cdn.jsdelivr.net cdnjs.cloudflare.com fonts.googleapis.com; font-src 'self' fonts.gstatic.com cdnjs.cloudflare.com; img-src 'self' data:; connect-src 'self';"

# Disable directory browsing
Options -Indexes
```

### **3. PHP Security**

Ensure these settings in `php.ini` or `.htaccess`:
```apache
# Disable dangerous functions
php_value disable_functions "exec,passthru,shell_exec,system,proc_open,popen"

# Session security
php_value session.cookie_httponly 1
php_value session.cookie_secure 1
php_value session.cookie_samesite "Strict"
php_value session.use_strict_mode 1

# File upload limits
php_value upload_max_filesize "10M"
php_value post_max_size "10M"
php_value max_execution_time "300"
```

---

## ⚙️ **INSTALLATION PROCESS**

### **Step 1: Upload Files**
Upload all files to your cPanel file manager or via FTP following the structure above.

### **Step 2: Set Permissions**
Apply the file permissions listed in the permissions section.

### **Step 3: Database Configuration**
Ensure your `config/database.php` has correct cPanel database credentials:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'prizmaso_tts_pms');
define('DB_USER', 'prizmaso_admin');
define('DB_PASS', 'your_database_password');
define('DB_CHARSET', 'utf8mb4');
```

### **Step 4: Run Installer**
Visit: `https://pms.prizmasoft.com/install_admin_integration.php`

The installer will:
- ✅ Check PHP requirements and extensions
- ✅ Verify/create required directories
- ✅ Test database connection
- ✅ Run all SQL migrations idempotently
- ✅ Seed default roles, capabilities, and settings
- ✅ Perform system health checks
- ✅ Generate installation report

### **Step 5: Remove Installer**
**IMPORTANT:** Delete `install_admin_integration.php` after successful installation for security.

---

## 🕐 **CRON JOB SETUP**

Configure these cron jobs in your cPanel:

### **Sync Queue Processor (Every 10 minutes)**
```bash
*/10 * * * * /usr/local/bin/php /home/prizmaso/public_html/admin/tools/process-sync-queue.php >> /home/prizmaso/logs/sync.log 2>&1
```

### **Daily Backup (2 AM)**
```bash
0 2 * * * /usr/local/bin/php /home/prizmaso/public_html/admin/tools/backup-restore.php --auto >> /home/prizmaso/logs/backup.log 2>&1
```

### **Weekly Log Cleanup (Sunday 3 AM)**
```bash
0 3 * * 0 find /home/prizmaso/logs -name "*.log" -mtime +30 -delete
```

### **Monthly Backup Cleanup**
```bash
0 4 1 * * /usr/local/bin/php -r "include '/home/prizmaso/public_html/config/init.php'; \$db = Database::getInstance(); \$db->query('CALL CleanupExpiredBackups()');"
```

---

## 🧪 **SMOKE TESTING CHECKLIST**

After deployment, verify all systems are working:

### **✅ Visual Builder Test**
1. Visit: `https://pms.prizmasoft.com/admin/visual-builder.php`
2. Create a test page with sections and content blocks
3. Save the page (Ctrl+S)
4. Verify physical file created in `/packages/web/src/pages/`
5. Test public access to generated page

### **✅ Settings Management Test**
1. Visit: `https://pms.prizmasoft.com/admin/settings-hub.php`
2. Update branding settings (site name, tagline)
3. Configure SMTP settings
4. Send test email to verify SMTP works
5. Verify settings persist after page refresh

### **✅ Module Toggle Test**
1. Go to Module Control Panel
2. Disable "Gigs" module
3. Verify menu item disappears from navigation
4. Test that gigs routes return 404
5. Re-enable module and verify restoration

### **✅ Payroll Update Test**
1. Update hourly rate in Settings Hub
2. Run payroll calculation
3. Verify new rates applied to calculations
4. Check audit log for rate change entry

### **✅ Sync API Test**
1. Make changes through admin panel
2. Check `tts_admin_sync` table for queued operations
3. Run sync processor manually: `php admin/tools/process-sync-queue.php`
4. Verify operations marked as completed
5. Check `tts_admin_edits` for sync process entries

### **✅ Backup/Restore Test**
1. Create manual backup via `admin/tools/backup-restore.php`
2. Verify ZIP file created in `admin/backups/`
3. Test backup download
4. Make small database change
5. Restore from backup and verify change reverted

### **✅ Audit & Rollback Test**
1. Make page changes in Visual Builder
2. Check audit log shows all changes
3. Use rollback feature to revert to previous version
4. Verify page content restored correctly
5. Check CMS history for version tracking

---

## 🔧 **TROUBLESHOOTING**

### **Common Issues**

**Installation Fails:**
- Check PHP version and extensions
- Verify database credentials
- Ensure proper file permissions
- Check error logs in cPanel

**Sync Queue Not Processing:**
- Verify cron job is configured correctly
- Check `/home/prizmaso/logs/sync.log` for errors
- Test manual execution: `php admin/tools/process-sync-queue.php`
- Verify database connection

**Backup System Issues:**
- Check disk space availability
- Verify ZIP extension is installed
- Ensure backup directory is writable
- Check `/home/prizmaso/logs/backup.log`

**Visual Builder Not Loading:**
- Clear browser cache
- Check JavaScript console for errors
- Verify CDN resources are accessible
- Test with different browser

**SMTP Not Working:**
- Verify Gmail App Password is correct
- Check firewall allows ports 465/587
- Test with different SMTP provider
- Review email logs in admin panel

### **Debug Mode**

Enable debug logging:
```sql
UPDATE tts_settings SET setting_value = '1' 
WHERE setting_key = 'debug_mode' AND category = 'system';
```

### **Health Monitoring**

Check system health:
```sql
-- Overall system health
SELECT * FROM tts_system_health ORDER BY checked_at DESC;

-- Recent sync activity
SELECT * FROM v_sync_queue_stats;

-- Backup status
SELECT * FROM v_backup_statistics;

-- Recent admin actions
SELECT action_type, COUNT(*) as count, MAX(created_at) as last_action
FROM tts_admin_edits 
WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
GROUP BY action_type;
```

---

## 📊 **MONITORING & MAINTENANCE**

### **Daily Checks**
- Review sync queue processing logs
- Check backup completion status
- Monitor disk space usage
- Verify system health status

### **Weekly Tasks**
- Review audit logs for unusual activity
- Test backup restoration process
- Update system settings if needed
- Clean up old log files

### **Monthly Tasks**
- Review and optimize database performance
- Update PHP/MariaDB if needed
- Security audit of admin access
- Backup retention policy review

---

## 🎯 **SUCCESS METRICS**

Your deployment is successful when:

- ✅ All smoke tests pass without errors
- ✅ Cron jobs execute successfully every cycle
- ✅ Backup system creates daily archives
- ✅ Sync queue processes operations within 10 minutes
- ✅ Visual Builder generates valid pages
- ✅ Settings changes apply immediately
- ✅ Audit trail captures all admin actions
- ✅ System health shows all green status
- ✅ No PHP errors in logs
- ✅ All security headers present

---

## 📞 **SUPPORT**

For deployment issues:

1. **Check Logs:** Review cPanel error logs and application logs
2. **Database:** Verify connection and charset settings
3. **Permissions:** Ensure all directories are writable
4. **PHP:** Confirm all required extensions are loaded
5. **Cron:** Test manual execution of scheduled scripts

**Database Connection Test:**
```php
<?php
require_once 'config/init.php';
try {
    $db = Database::getInstance();
    $result = $db->fetchOne("SELECT 'Connection successful' as status");
    echo $result['status'];
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
```

---

## 🔄 **ENVIRONMENT CONFIGURATION**

### **Optional .env Configuration**

Create `.env` file in root (optional - settings stored in database):

```env
# Database Configuration
DB_HOST=localhost
DB_NAME=prizmaso_tts_pms
DB_USER=prizmaso_admin
DB_PASS=your_secure_password

# SMTP Configuration (stored in tts_settings)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=465
SMTP_USER=tts.workhub@gmail.com
SMTP_PASS=***CONFIGURED_IN_ADMIN***

# Security Keys (auto-generated)
JWT_SECRET=***AUTO_GENERATED***
CSRF_SECRET=***AUTO_GENERATED***

# System Configuration
ENVIRONMENT=production
DEBUG_MODE=false
BACKUP_RETENTION_DAYS=30
QUEUE_BATCH_SIZE=10
```

**Note:** Most settings are managed through the admin interface and stored in `tts_settings` table.

---

## ✅ **DEPLOYMENT COMPLETE**

Your TTS PMS Super Admin system is now fully deployed with:

- **Visual Builder** - Drag-and-drop page creation
- **Global Settings Hub** - Centralized configuration
- **Real-time Sync API** - Background processing
- **Backup/Restore System** - Data protection
- **Comprehensive Audit Trail** - Change tracking
- **Health Monitoring** - System status
- **Security Hardening** - Production-ready protection

**Next Steps:**
1. Configure your branding and SMTP settings
2. Create your first pages with Visual Builder
3. Set up regular backup schedule
4. Train your team on the admin interface
5. Monitor system health and performance

**Welcome to your new Super Admin system!** 🚀
