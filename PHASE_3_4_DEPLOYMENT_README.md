# TTS PMS Phase 3 & 4 Deployment Guide

## 🚀 **PHASES COMPLETED**

### ✅ **Phase 3 - Visual Builder & CMS System**
- **Visual Builder Interface** - Drag-and-drop page builder with live preview
- **Page/Post Generator** - Template wizard with SEO management  
- **API Endpoints** - Complete save/load/rollback functionality
- **Database Schema** - Full CMS tables with versioning and media management

### ✅ **Phase 4 - Global Settings Hub**
- **Branding & Appearance** - Site name, logo, social links, footer customization
- **Email & SMTP** - Complete SMTP configuration with test email functionality
- **SEO & Meta** - Global SEO settings with per-page overrides
- **Payroll & HR** - Hourly rates, bonuses, overtime, security fund configuration
- **Security & Auth** - Gmail-only toggle, OTP settings, session management

---

## 📋 **DEPLOYMENT CHECKLIST**

### **1. Database Migrations**

Execute these SQL files in order on your MariaDB 10.6.23 database:

```sql
-- Phase 3 CMS Tables
SOURCE /path/to/database/phase3_cms_migrations.sql;

-- Phase 4 Settings & Configuration
SOURCE /path/to/database/phase4_settings_migrations.sql;
```

**Key Tables Created:**
- `tts_cms_pages` - Page metadata with SEO fields
- `tts_page_layouts` - JSON layout storage with versioning
- `tts_media_files` - Image upload management
- `tts_page_templates` - Pre-built layout templates
- `tts_page_components` - Reusable component library
- Enhanced `tts_settings` - Complete configuration system

### **2. File Structure Verification**

Ensure these new files are uploaded to your cPanel:

```
admin/
├── visual-builder.php          # Drag-drop page builder
├── settings-hub.php           # Global settings interface
├── api/
│   ├── save-layout.php        # Layout save endpoint
│   ├── load-layout.php        # Layout load endpoint
│   ├── rollback.php           # Version rollback
│   ├── save-setting.php       # Settings management
│   └── test-email.php         # SMTP testing

packages/web/src/pages/        # Generated pages directory (auto-created)
```

### **3. Directory Permissions**

Set proper permissions for file generation:

```bash
chmod 755 packages/web/src/pages/
chmod 644 packages/web/src/pages/*.php
chmod 755 admin/api/
```

### **4. Configuration Updates**

The system will automatically update these config files:
- `config/email_config.php` - SMTP settings
- `config/app_config.php` - Payroll constants
- Template files - Branding updates

---

## 🔧 **TESTING PROCEDURES**

### **Phase 3 - Visual Builder Testing**

1. **Access Visual Builder:**
   ```
   https://pms.prizmasoft.com/admin/visual-builder.php?page_id=1
   ```

2. **Test Drag-and-Drop:**
   - Drag elements from sidebar to canvas
   - Test inline editing (double-click text)
   - Verify element controls (edit/delete buttons)

3. **Test Save/Load:**
   - Create a layout and save (Ctrl+S)
   - Refresh page and verify layout persists
   - Check version history in database

4. **Test Page Generation:**
   - Create new page via page-builder.php
   - Verify physical file creation in `/packages/web/src/pages/`
   - Test live preview functionality

### **Phase 4 - Global Settings Testing**

1. **Access Settings Hub:**
   ```
   https://pms.prizmasoft.com/admin/settings-hub.php
   ```

2. **Test Each Tab:**

   **Branding Tab:**
   - Update site name and tagline
   - Add logo/favicon URLs
   - Verify changes reflect on frontend

   **Email Tab:**
   - Configure SMTP settings
   - Click "Send Test Email" button
   - Check email delivery and audit log

   **SEO Tab:**
   - Set meta title/description
   - Toggle robots settings
   - Verify meta tags on generated pages

   **Payroll Tab:**
   - Update hourly rates and bonuses
   - Test calculation in payroll module
   - Verify settings persist in database

   **Auth Tab:**
   - Toggle Gmail-only requirement
   - Adjust session timeout
   - Test login behavior changes

3. **Test Settings Persistence:**
   - Save settings in each tab
   - Refresh page and verify values persist
   - Check `tts_settings` table for updates

---

## 🔍 **VERIFICATION QUERIES**

### **Check CMS Tables:**
```sql
-- Verify page templates loaded
SELECT COUNT(*) as template_count FROM tts_page_templates WHERE is_system = 1;
-- Should return 4

-- Check component library
SELECT COUNT(*) as component_count FROM tts_page_components WHERE is_system = 1;
-- Should return 6

-- Verify settings categories
SELECT DISTINCT category FROM tts_settings ORDER BY category;
-- Should return: auth, branding, email, modules, payroll, seo, system
```

### **Check Module Status:**
```sql
-- Verify new modules enabled
SELECT module_name, is_enabled FROM tts_module_config 
WHERE module_name IN ('visual_builder', 'global_settings', 'audit_system');
-- All should be enabled (1)
```

---

## 🚨 **TROUBLESHOOTING**

### **Common Issues:**

1. **"Permission Denied" on File Generation:**
   ```bash
   chmod 755 packages/web/src/pages/
   chown www-data:www-data packages/web/src/pages/
   ```

2. **SMTP Test Email Fails:**
   - Verify Gmail App Password is correct
   - Check firewall allows port 465/587
   - Enable "Less secure app access" if needed

3. **Visual Builder Not Loading:**
   - Check browser console for JavaScript errors
   - Verify all CSS/JS CDN links are accessible
   - Clear browser cache

4. **Settings Not Saving:**
   - Check CSRF token generation
   - Verify admin session is active
   - Check database connection

### **Debug Mode:**
Enable debug logging in settings:
```sql
UPDATE tts_settings SET setting_value = '1' 
WHERE setting_key = 'debug_mode' AND category = 'system';
```

---

## 📊 **PERFORMANCE NOTES**

### **Optimization Recommendations:**

1. **Database Indexing:**
   - All CMS tables have proper indexes
   - Settings queries are optimized for category lookups

2. **File Generation:**
   - Generated pages are static PHP files
   - No database queries needed for page rendering
   - Automatic backup system prevents data loss

3. **Caching:**
   - Settings are cached in session
   - Layout JSON is stored efficiently
   - Media files support CDN integration

### **Backup Strategy:**
- Auto-backup before each layout change
- Settings changes logged to audit trail
- Version history limited to 10 versions per page
- Manual backup recommended before major updates

---

## 🎯 **SUCCESS METRICS**

After deployment, verify these capabilities:

✅ **Visual Builder:**
- [ ] Drag-and-drop interface functional
- [ ] Live preview updates in real-time
- [ ] Page generation creates valid PHP files
- [ ] Version rollback works correctly
- [ ] Keyboard shortcuts (Ctrl+S, Ctrl+Z) work

✅ **Global Settings:**
- [ ] All 5 tabs load and save correctly
- [ ] SMTP test email sends successfully
- [ ] Settings apply to live site immediately
- [ ] Audit trail records all changes
- [ ] Configuration files update automatically

✅ **Integration:**
- [ ] Admin helpers load correctly
- [ ] CSRF protection active on all endpoints
- [ ] Capability checking enforced
- [ ] Database migrations completed successfully
- [ ] No PHP errors in logs

---

## 📞 **SUPPORT**

For deployment issues:
1. Check PHP error logs in cPanel
2. Verify database connection in `config/database.php`
3. Test admin login functionality
4. Confirm all file permissions are correct

**Database Credentials:**
- Host: localhost
- Database: prizmaso_tts_pms
- User: prizmaso_admin
- Ensure UTF8MB4 charset is set

---

## 🔄 **NEXT STEPS**

Phase 3 & 4 are now complete. The system includes:

- **Complete Visual Builder** with drag-drop interface
- **Comprehensive Settings Hub** with 5 configuration categories
- **Full API Ecosystem** for layout management
- **Advanced Audit System** with rollback capabilities
- **Professional UI/UX** with consistent design

The TTS PMS Super Admin system is now fully functional for content management and system configuration.

---

## 🔄 **PHASE 5 - SYNC QUEUE PROCESSOR**

### **Queue Processor Setup**

**File Location:** `/admin/tools/process-sync-queue.php`

**Features:**
- Processes pending sync operations in background
- Automatic retry logic (up to 3 attempts)
- Health monitoring with failure rate alerts
- CLI and web compatible
- Transaction safety with rollback on failure

### **Cron Configuration**

Add these entries to your cPanel cron jobs:

```bash
# Process sync queue every 10 minutes
*/10 * * * * /usr/local/bin/php /home/prizmaso/public_html/admin/tools/process-sync-queue.php >> /home/prizmaso/logs/sync.log 2>&1

# Daily backup at 2 AM
0 2 * * * /usr/local/bin/php /home/prizmaso/public_html/admin/tools/backup-restore.php --auto >> /home/prizmaso/logs/backup.log 2>&1

# Weekly cleanup of old logs and expired jobs
0 3 * * 0 find /home/prizmaso/logs -name "*.log" -mtime +30 -delete
```

### **Manual Testing**

**CLI Execution:**
```bash
cd /home/prizmaso/public_html/admin/tools/
php process-sync-queue.php
```

**Expected Output:**
```
[2024-10-23 11:15:00] === TTS Sync Queue Processor Started ===
[2024-10-23 11:15:00] Batch size: 10, Max retries: 3
[2024-10-23 11:15:01] Found 3 pending jobs
[2024-10-23 11:15:01] Processing job sync_abc123_1698067200 (page_update)
[2024-10-23 11:15:02] ✓ Job sync_abc123_1698067200 completed successfully
[2024-10-23 11:15:02] Processing job sync_def456_1698067260 (settings_update)
[2024-10-23 11:15:03] ✓ Job sync_def456_1698067260 completed successfully
[2024-10-23 11:15:03] Processing job sync_ghi789_1698067320 (module_toggle)
[2024-10-23 11:15:04] ✓ Job sync_ghi789_1698067320 completed successfully
[2024-10-23 11:15:04] === Processing Complete ===
[2024-10-23 11:15:04] Processed: 3, Success: 3, Failed: 0, Skipped: 0
[2024-10-23 11:15:04] Duration: 4.23s
```

**Web Execution (with token):**
```
https://pms.prizmasoft.com/admin/tools/process-sync-queue.php?token=DAILY_TOKEN_HASH
```

### **Health Monitoring**

**Database Queries:**

```sql
-- Check queue status
SELECT * FROM v_sync_queue_stats;

-- Check system health
SELECT * FROM v_system_health_summary;

-- Recent processing activity
SELECT action_type, object_type, changes, created_at 
FROM tts_admin_edits 
WHERE action_type = 'sync_process' 
ORDER BY created_at DESC 
LIMIT 10;
```

### **Log Files**

**Sync Queue Log:** `/admin/logs/sync-queue.log`
**Cron Output:** `/home/prizmaso/logs/sync.log`
**Backup Log:** `/home/prizmaso/logs/backup.log`

### **Failure Alerts**

When failure rate exceeds 10%, automatic email alerts are sent to the admin email configured in settings. Alert includes:
- Failure rate percentage
- Number of failed jobs
- Timestamp of detection
- Link to admin panel for investigation

### **Security Features**

- **CLI Mode:** Direct execution, no authentication required
- **Web Mode:** Requires admin session + daily rotating token
- **Transaction Safety:** All operations wrapped in database transactions
- **Audit Trail:** Every processing attempt logged to `tts_admin_edits`
- **Concurrency Protection:** Job locking prevents duplicate processing

### **Performance Optimization**

- **Batch Processing:** Configurable batch size (default: 10 jobs)
- **Priority Queue:** High priority jobs processed first
- **Efficient Indexing:** Optimized database indexes for queue queries
- **Memory Management:** Processes jobs individually to prevent memory issues
- **Cleanup Automation:** Automatic cleanup of stuck and expired jobs

The sync queue processor ensures reliable background processing of all admin changes with comprehensive monitoring and error handling.

---

## 💾 **BACKUP/RESTORE SYSTEM**

### **Backup Utility Setup**

**File Location:** `/admin/tools/backup-restore.php`

**Features:**
- Interactive web UI and CLI automation modes
- Selective table backup with presets (Config, CMS, Payroll, Full)
- Media file manifest with checksums
- Secret masking for security (SMTP passwords, API keys)
- ZIP compression with metadata
- Automatic retention management

### **Backup Presets**

**Config Only:**
- `tts_settings`, `tts_module_config`, `tts_roles`, `tts_capabilities`

**CMS Only:**
- All `tts_cms_*` and `tts_page_*` tables

**Payroll Only:**
- All `tts_payroll*` and `tts_timesheet*` tables

**Full Backup:**
- All `tts_*` tables + media manifest

### **CLI Usage**

**Automated Backup (Cron):**
```bash
cd /home/prizmaso/public_html/admin/tools/
php backup-restore.php --auto
```

**Interactive CLI:**
```bash
php backup-restore.php
# Follow prompts to create backup or list existing backups
```

**Expected Output:**
```
[2024-10-23 11:30:00] === TTS Backup Utility - CLI Mode ===
[2024-10-23 11:30:01] Creating full backup with media manifest
[2024-10-23 11:30:05] ✓ Schema exported: 25 tables
[2024-10-23 11:30:08] ✓ Data exported: 15,432 records
[2024-10-23 11:30:10] ✓ Media manifest created: 127 files
[2024-10-23 11:30:12] ✓ Automated backup completed: tts-backup-backup_20241023_113000_abc123.zip
[2024-10-23 11:30:12] Tables: 25, Size: 2,847,392 bytes
```

### **Web UI Access**

**URL:** `https://pms.prizmasoft.com/admin/tools/backup-restore.php`

**Features:**
- Create backup with custom table selection
- Download existing backups
- Restore from uploaded ZIP files
- Delete old backups
- View backup statistics and health

### **Backup File Structure**

Each backup ZIP contains:
```
tts-backup-YYYYMMDD-HHMM.zip
├── schema.sql          # Table structure
├── data.sql           # Table data (secrets masked)
├── meta.json          # Backup metadata
└── media-manifest.json # Media files list with checksums
```

**Metadata Example:**
```json
{
  "backup_id": "backup_20241023_113000_abc123",
  "created_at": "2024-10-23 11:30:00",
  "app_version": "1.0.0",
  "preset": "full",
  "tables": ["tts_users", "tts_settings", ...],
  "record_count": 15432,
  "include_media": true,
  "media_files": 127
}
```

### **Security Features**

**Secret Masking:**
- SMTP passwords → `***MASKED***`
- API keys and JWT secrets → `***MASKED***`
- User password hashes → `***MASKED***`
- Session tokens → `***MASKED***`

**Access Control:**
- Web UI requires admin session + `manage_backups` capability
- CLI mode requires server access
- CSRF protection on all web operations
- File path validation prevents directory traversal

### **Retention Management**

**Automatic Cleanup:**
- Retains last 7 backups by default
- Configurable via `backup_retention_days` setting
- Expired backups marked in database before file deletion
- Cleanup procedure runs with each automated backup

**Manual Management:**
```sql
-- View backup statistics
SELECT * FROM v_backup_statistics;

-- List recent backups
SELECT backup_name, file_size, created_at, status 
FROM tts_backups 
ORDER BY created_at DESC 
LIMIT 10;

-- Clean up expired backups manually
CALL CleanupExpiredBackups();
```

### **Restore Process**

**Web UI Restore:**
1. Upload backup ZIP or select from existing backups
2. Review backup contents and metadata
3. Choose restore options:
   - Include/exclude media files
   - Restore/skip masked secrets
   - Selective table restore
4. Confirm with CSRF token
5. Transaction-wrapped import with rollback on error

**CLI Restore:**
```bash
php backup-restore.php
# Choose option 3 for restore
# Select backup file
# Confirm restore options
```

### **Health Monitoring**

**System Health Updates:**
- Backup success/failure status
- File size and duration metrics
- Error details for failed backups
- Last successful backup timestamp

**Database Queries:**
```sql
-- Check backup system health
SELECT * FROM tts_system_health WHERE check_type = 'backup';

-- Recent backup activity
SELECT action_type, changes, created_at 
FROM tts_admin_edits 
WHERE action_type LIKE 'backup_%' 
ORDER BY created_at DESC 
LIMIT 10;
```

### **Error Handling**

**Common Issues:**
- **Insufficient disk space:** Check available space before backup
- **Permission errors:** Ensure backup directory is writable
- **Large backup size:** Configure `backup_max_size_mb` setting
- **ZIP creation failure:** Verify PHP ZIP extension is installed

**Troubleshooting:**
```bash
# Check backup directory permissions
ls -la /home/prizmaso/public_html/admin/backups/

# Test ZIP functionality
php -m | grep zip

# Check backup logs
tail -f /home/prizmaso/logs/backup.log
```

### **Integration with Cron**

**Recommended Schedule:**
```bash
# Daily backup at 2 AM
0 2 * * * /usr/local/bin/php /home/prizmaso/public_html/admin/tools/backup-restore.php --auto >> /home/prizmaso/logs/backup.log 2>&1

# Weekly cleanup of old backups
0 3 * * 0 /usr/local/bin/php -r "include '/home/prizmaso/public_html/config/init.php'; \$db = Database::getInstance(); \$db->query('CALL CleanupExpiredBackups()');"
```

The backup/restore system provides comprehensive data protection with automated scheduling, security features, and health monitoring.
