# TTS PMS Phase 5 - Sync API Testing Guide

## 🧪 **Event Handler Testing**

### **Prerequisites**
- Admin session active in browser
- CSRF token available from admin panel
- Database with Phase 5 migrations applied

### **1. Page Update Handler**

**Test Page Creation/Update:**
```bash
curl -X POST https://pms.prizmasoft.com/api/admin-sync-v2.php \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "action=page_update" \
  -d "csrf_token=YOUR_CSRF_TOKEN" \
  -d "data[page_id]=1" \
  -d "data[layout_json]={\"sections\":[{\"type\":\"header\",\"rows\":[{\"columns\":[{\"width\":\"12\",\"blocks\":[{\"type\":\"heading\",\"content\":\"Test Page\",\"settings\":{\"size\":\"h1\",\"align\":\"center\"}}]}]}]}]}" \
  -d "data[generate_file]=true" \
  --cookie-jar cookies.txt
```

**Expected Response:**
```json
{
  "success": true,
  "sync_id": "sync_abc123_1698067200",
  "action": "page_update",
  "message": "Page 'Test Page' updated successfully",
  "timestamp": "2024-10-23 11:00:00"
}
```

**Verification:**
- Check `tts_page_layouts` for new entry with `is_current = 1`
- Verify physical file created in `/packages/web/src/pages/`
- Confirm backup entry in `tts_cms_history`

### **2. Settings Update Handler**

**Test Branding Settings:**
```bash
curl -X POST https://pms.prizmasoft.com/api/admin-sync-v2.php \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "action=settings_update" \
  -d "csrf_token=YOUR_CSRF_TOKEN" \
  -d "data[category]=branding" \
  -d "data[settings][site_name]=TTS Updated" \
  -d "data[settings][tagline]=New Tagline" \
  --cookie-jar cookies.txt
```

**Test Email Settings:**
```bash
curl -X POST https://pms.prizmasoft.com/api/admin-sync-v2.php \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "action=settings_update" \
  -d "csrf_token=YOUR_CSRF_TOKEN" \
  -d "data[category]=email" \
  -d "data[settings][smtp_host]=smtp.gmail.com" \
  -d "data[settings][smtp_port]=465" \
  --cookie-jar cookies.txt
```

**Expected Response:**
```json
{
  "success": true,
  "sync_id": "sync_def456_1698067260",
  "action": "settings_update",
  "message": "Branding settings updated successfully",
  "timestamp": "2024-10-23 11:01:00"
}
```

**Verification:**
- Check `tts_settings` table for updated values
- Verify config files updated (email_config.php, app_config.php)
- Confirm audit entry in `tts_admin_edits`

### **3. Module Toggle Handler**

**Test Module Disable:**
```bash
curl -X POST https://pms.prizmasoft.com/api/admin-sync-v2.php \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "action=module_toggle" \
  -d "csrf_token=YOUR_CSRF_TOKEN" \
  -d "data[module_name]=gigs" \
  -d "data[is_enabled]=false" \
  --cookie-jar cookies.txt
```

**Test Module Enable:**
```bash
curl -X POST https://pms.prizmasoft.com/api/admin-sync-v2.php \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "action=module_toggle" \
  -d "csrf_token=YOUR_CSRF_TOKEN" \
  -d "data[module_name]=payroll" \
  -d "data[is_enabled]=true" \
  --cookie-jar cookies.txt
```

**Expected Response:**
```json
{
  "success": true,
  "sync_id": "sync_ghi789_1698067320",
  "action": "module_toggle",
  "message": "Module 'gigs' disabled successfully",
  "timestamp": "2024-10-23 11:02:00"
}
```

**Verification:**
- Check `tts_module_config` for updated `is_enabled` status
- Verify navigation cache rebuilt
- Test frontend - disabled module should not appear in menus

### **4. Payroll Update Handler**

**Test Payroll Settings:**
```bash
curl -X POST https://pms.prizmasoft.com/api/admin-sync-v2.php \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "action=payroll_update" \
  -d "csrf_token=YOUR_CSRF_TOKEN" \
  -d "data[settings][base_hourly_rate]=150" \
  -d "data[settings][streak_bonus]=600" \
  -d "data[recalculate_existing]=true" \
  --cookie-jar cookies.txt
```

**Expected Response:**
```json
{
  "success": true,
  "sync_id": "sync_jkl012_1698067380",
  "action": "payroll_update",
  "message": "Payroll settings updated successfully",
  "timestamp": "2024-10-23 11:03:00"
}
```

**Verification:**
- Check `tts_settings` for updated payroll values
- Verify `app_config.php` constants updated
- Confirm recalculation job queued in `tts_admin_sync`

### **5. SEO Update Handler**

**Test SEO Settings:**
```bash
curl -X POST https://pms.prizmasoft.com/api/admin-sync-v2.php \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "action=seo_update" \
  -d "csrf_token=YOUR_CSRF_TOKEN" \
  -d "data[settings][meta_title]=TTS - Professional Services" \
  -d "data[settings][meta_description]=Updated description for SEO" \
  -d "data[update_existing_pages]=true" \
  --cookie-jar cookies.txt
```

**Expected Response:**
```json
{
  "success": true,
  "sync_id": "sync_mno345_1698067440",
  "action": "seo_update",
  "message": "SEO settings updated successfully",
  "timestamp": "2024-10-23 11:04:00"
}
```

**Verification:**
- Check `tts_settings` for updated SEO values
- Verify meta tags updated in existing pages
- Confirm template files updated

## 🔍 **Queue Status Testing**

### **Get Queue Overview:**
```bash
curl -X GET https://pms.prizmasoft.com/api/admin-sync-v2.php \
  --cookie-jar cookies.txt
```

**Expected Response:**
```json
{
  "success": true,
  "queue_stats": {
    "pending": 2,
    "processing": 0,
    "completed": 15,
    "failed": 1,
    "total": 18,
    "recent_operations": [...]
  },
  "timestamp": "2024-10-23 11:05:00"
}
```

### **Get Specific Operation Status:**
```bash
curl -X GET "https://pms.prizmasoft.com/api/admin-sync-v2.php?sync_id=sync_abc123_1698067200" \
  --cookie-jar cookies.txt
```

**Expected Response:**
```json
{
  "success": true,
  "operation": {
    "id": 1,
    "sync_id": "sync_abc123_1698067200",
    "action_type": "page_update",
    "status": "completed",
    "created_at": "2024-10-23 11:00:00",
    "completed_at": "2024-10-23 11:00:05"
  },
  "timestamp": "2024-10-23 11:05:00"
}
```

## 🚨 **Error Testing**

### **Test Invalid CSRF Token:**
```bash
curl -X POST https://pms.prizmasoft.com/api/admin-sync-v2.php \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "action=page_update" \
  -d "csrf_token=invalid_token" \
  -d "data[page_id]=1" \
  --cookie-jar cookies.txt
```

**Expected Response:**
```json
{
  "error": "Invalid CSRF token",
  "timestamp": "2024-10-23 11:06:00",
  "request_id": "req_abc123_def456"
}
```

### **Test Missing Required Data:**
```bash
curl -X POST https://pms.prizmasoft.com/api/admin-sync-v2.php \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "action=page_update" \
  -d "csrf_token=YOUR_CSRF_TOKEN" \
  --cookie-jar cookies.txt
```

**Expected Response:**
```json
{
  "error": "Page ID and layout JSON required",
  "timestamp": "2024-10-23 11:07:00",
  "request_id": "req_def456_ghi789"
}
```

### **Test Unauthorized Access:**
```bash
curl -X POST https://pms.prizmasoft.com/api/admin-sync-v2.php \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "action=page_update" \
  -d "csrf_token=YOUR_CSRF_TOKEN" \
  -d "data[page_id]=1"
  # No cookies - no session
```

**Expected Response:**
```json
{
  "error": "Unauthorized - Admin session required"
}
```

## 📊 **Database Verification Queries**

### **Check Sync Queue:**
```sql
SELECT sync_id, action_type, status, created_at, completed_at 
FROM tts_admin_sync 
ORDER BY created_at DESC 
LIMIT 10;
```

### **Check Audit Trail:**
```sql
SELECT action_type, object_type, object_id, changes, created_at 
FROM tts_admin_edits 
WHERE action_type = 'sync_event' 
ORDER BY created_at DESC 
LIMIT 10;
```

### **Check Settings Updates:**
```sql
SELECT setting_key, setting_value, category, updated_at 
FROM tts_settings 
WHERE updated_at > DATE_SUB(NOW(), INTERVAL 1 HOUR);
```

### **Check Page Layouts:**
```sql
SELECT pl.id, pl.page_id, pl.version, pl.is_current, cp.title, cp.slug
FROM tts_page_layouts pl
JOIN tts_cms_pages cp ON pl.page_id = cp.id
WHERE pl.updated_at > DATE_SUB(NOW(), INTERVAL 1 HOUR);
```

## 🎯 **Success Criteria**

✅ **All Handlers Working:**
- [ ] Page updates create files and database entries
- [ ] Settings propagate to config files immediately
- [ ] Module toggles affect navigation and functionality
- [ ] Payroll changes update rates and queue recalculation
- [ ] SEO updates modify meta tags across pages

✅ **Security Validated:**
- [ ] CSRF tokens required and validated
- [ ] Admin session required for all operations
- [ ] Capability checking enforced per action
- [ ] SQL injection protection via prepared statements

✅ **Audit Trail Complete:**
- [ ] All sync operations logged to `tts_admin_edits`
- [ ] Before/after JSON captured for settings
- [ ] Error conditions logged with details
- [ ] Queue operations tracked with status

✅ **Error Handling Robust:**
- [ ] Invalid data rejected with clear messages
- [ ] Database transaction rollback on failure
- [ ] File system errors handled gracefully
- [ ] Dependency conflicts prevented

## 🔧 **Postman Collection**

Import this collection for easier testing:

```json
{
  "info": {
    "name": "TTS PMS Sync API",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "item": [
    {
      "name": "Page Update",
      "request": {
        "method": "POST",
        "header": [],
        "body": {
          "mode": "urlencoded",
          "urlencoded": [
            {"key": "action", "value": "page_update"},
            {"key": "csrf_token", "value": "{{csrf_token}}"},
            {"key": "data[page_id]", "value": "1"},
            {"key": "data[layout_json]", "value": "{\"sections\":[]}"},
            {"key": "data[generate_file]", "value": "true"}
          ]
        },
        "url": {
          "raw": "{{base_url}}/api/admin-sync-v2.php",
          "host": ["{{base_url}}"],
          "path": ["api", "admin-sync-v2.php"]
        }
      }
    }
  ],
  "variable": [
    {"key": "base_url", "value": "https://pms.prizmasoft.com"},
    {"key": "csrf_token", "value": "YOUR_CSRF_TOKEN_HERE"}
  ]
}
```

Ready for **Part 3 - Sync Queue Processor** implementation!
