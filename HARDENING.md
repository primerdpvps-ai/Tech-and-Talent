# TTS PMS Phase 6 - Security Hardening & Performance Guide

## 🔐 **SECURITY HARDENING**

### **Rate Limiting Implementation**

**API Endpoint Protection:**
```php
// Add to admin-sync-v2.php
require_once '../includes/security_helpers.php';

// Rate limit admin sync API (100 requests per hour per IP)
checkIPThrottle('admin_sync_api', 100, 60);
```

**OTP Exponential Backoff:**
```php
// Add to OTP verification
$otpCheck = checkOTPRateLimit($userEmail);
if (!$otpCheck['allowed']) {
    echo json_encode(['error' => $otpCheck['message']]);
    exit;
}
```

**Usage in Controllers:**
```php
// Rate limit login attempts
$rateLimiter = new RateLimiter();
$identifier = 'login_' . $_SERVER['REMOTE_ADDR'];

if ($rateLimiter->isRateLimited($identifier, 5, 15)['limited']) {
    // Block login attempt
    return ['error' => 'Too many login attempts'];
}

// Record failed attempt
if (!$loginSuccess) {
    $rateLimiter->recordAttempt($identifier);
}
```

### **Maintenance Mode**

**Enable/Disable Maintenance:**
```php
$maintenance = MaintenanceMode::getInstance();

// Enable maintenance mode
$maintenance->enable('System upgrade in progress. ETA: 30 minutes');

// Disable maintenance mode
$maintenance->disable();

// Check if user can bypass
if ($maintenance->isEnabled() && !$maintenance->canBypass()) {
    // Show maintenance page
}
```

**Middleware Integration:**
```php
// Add to config/init.php
require_once __DIR__ . '/../includes/security_helpers.php';

// Check maintenance mode on every request
checkMaintenanceMode();
```

### **Secret Management**

**Store Encrypted Secrets:**
```php
$secretManager = new SecretManager();

// Store SMTP password securely
$secretManager->storeSecret('smtp_password', $smtpPassword, 'email');

// Retrieve secret
$smtpPassword = $secretManager->getSecret('smtp_password', 'email');

// Rotate secret (generates new random value)
$newSecret = $secretManager->rotateSecret('jwt_secret', 'auth');
```

**Webhook Signature Validation:**
```php
// Validate incoming webhook
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_SIGNATURE'] ?? '';
$secret = $secretManager->getSecret('webhook_secret');

if (!WebhookValidator::validateSignature($payload, $signature, $secret)) {
    http_response_code(401);
    exit('Invalid signature');
}
```

### **Database Security**

**Run Security Migrations:**
```sql
-- Execute phase6_hardening_migrations.sql
SOURCE /path/to/database/phase6_hardening_migrations.sql;
```

**Security Settings:**
```sql
-- Enable maintenance mode capability
UPDATE tts_settings SET setting_value = '1' 
WHERE setting_key = 'rate_limit_enabled';

-- Set webhook secret
UPDATE tts_settings SET setting_value = 'your_webhook_secret_here' 
WHERE setting_key = 'webhook_secret';
```

---

## ⚡ **PERFORMANCE OPTIMIZATION**

### **Page Caching**

**Enable Page Cache:**
```php
$pageCache = new PageCache();

// Check for cached content
$cacheKey = 'page_' . $pageId;
$cachedContent = $pageCache->get($cacheKey, 3600); // 1 hour TTL

if ($cachedContent) {
    echo $cachedContent;
    exit;
}

// Generate content
$content = generatePageContent();

// Cache the content
$pageCache->set($cacheKey, $content);
echo $content;
```

**Clear Cache on Publish:**
```php
// Add to visual builder save
clearCacheOnPublish($pageId);

// Manual cache clearing
$pageCache = new PageCache();
$cleared = $pageCache->clearAll();
echo "Cleared $cleared cache files";
```

### **Memory Caching**

**Settings Cache:**
```php
// Use cached settings
$brandingSettings = SettingsCache::getCategory('branding');
$siteName = $brandingSettings['site_name'] ?? 'TTS PMS';

// Clear settings cache after update
SettingsCache::clearCategory('branding');
```

**Memory Cache Usage:**
```php
$memCache = MemoryCache::getInstance();

// Store in cache
$memCache->set('navigation_menu', $menuData, 7200); // 2 hours

// Retrieve from cache
$menuData = $memCache->get('navigation_menu', []);

// Clear specific cache
$memCache->delete('navigation_menu');
```

### **Image Optimization**

**Optimize Uploaded Images:**
```php
$optimizer = new ImageOptimizer();

// Optimize image on upload
if ($optimizer->optimize($uploadedFilePath)) {
    echo "Image optimized successfully";
}

// Generate lazy loading image
echo $optimizer->generateLazyImage(
    '/media/image.jpg', 
    'Alt text', 
    'img-fluid', 
    800, 
    600
);
```

**Lazy Loading JavaScript:**
```html
<script>
document.addEventListener('DOMContentLoaded', function() {
    const lazyImages = document.querySelectorAll('.lazy-image');
    
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.remove('lazy-image');
                observer.unobserve(img);
            }
        });
    });
    
    lazyImages.forEach(img => imageObserver.observe(img));
});
</script>
```

### **Database Optimization**

**Query Performance Analysis:**
```php
$optimizer = new QueryOptimizer();

// Analyze slow queries
$analysis = $optimizer->analyzeSlowQueries();

// Get index suggestions
$suggestions = $optimizer->suggestIndexes();

foreach ($suggestions as $table => $indexes) {
    foreach ($indexes as $index) {
        echo "Suggested: " . $index['sql'] . "\n";
    }
}
```

**Enable Slow Query Log:**
```sql
-- Enable slow query logging
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 1.0;
SET GLOBAL log_queries_not_using_indexes = 'ON';
```

**Composite Index Examples:**
```sql
-- High-impact indexes for common queries
ALTER TABLE tts_admin_edits 
ADD INDEX idx_admin_action_date (admin_id, action_type, created_at);

ALTER TABLE tts_admin_sync 
ADD INDEX idx_status_priority_created (status, priority, created_at);

ALTER TABLE tts_cms_pages 
ADD INDEX idx_status_updated (status, updated_at);
```

---

## 🧪 **OBSERVABILITY & MONITORING**

### **Health Dashboard**

**Access Health Dashboard:**
```
https://pms.prizmasoft.com/admin/health-dashboard.php
```

**Health Metrics API:**
```php
// Get real-time health metrics
$healthDashboard = new HealthDashboard();
$metrics = $healthDashboard->getHealthMetrics();

// Returns:
// - database: latency, connections, size
// - queue: depth, failure rate, processing stats
// - backup: freshness, size, status
// - errors: error rate, patterns
// - performance: response times, cache stats
// - security: failed logins, rate limits
```

### **Error Aggregation**

**Monitor Error Patterns:**
```php
$errorAggregator = new ErrorAggregator();

// Check for error spikes
$alerts = $errorAggregator->checkErrorSpikes();

// Get error patterns
$patterns = $errorAggregator->getErrorPatterns();
```

**Automated Error Alerts:**
```sql
-- Configure alert thresholds
UPDATE tts_settings SET setting_value = '10' 
WHERE setting_key = 'error_spike_threshold';

UPDATE tts_settings SET setting_value = 'admin@yourdomain.com' 
WHERE setting_key = 'admin_email';
```

### **Performance Monitoring**

**Track Operation Performance:**
```php
// Start performance timer
startPerformanceTimer('page_generation');

// ... perform operation ...

// End timer and log if slow
$duration = endPerformanceTimer('page_generation');
echo "Operation took: {$duration}s";
```

**Collect System Metrics:**
```php
$metricsCollector = new MetricsCollector();
$metrics = $metricsCollector->collectMetrics();

// Metrics include:
// - Database query times and connection stats
// - Application memory usage and cache stats
// - System disk space and PHP version
```

---

## 🔧 **CONFIGURATION**

### **Security Settings**

**Rate Limiting Configuration:**
```sql
UPDATE tts_settings SET setting_value = '1' WHERE setting_key = 'rate_limit_enabled';
UPDATE tts_settings SET setting_value = '100' WHERE setting_key = 'api_rate_limit_per_hour';
UPDATE tts_settings SET setting_value = '5' WHERE setting_key = 'login_rate_limit';
```

**Maintenance Mode Settings:**
```sql
UPDATE tts_settings SET setting_value = '0' WHERE setting_key = 'maintenance_mode';
UPDATE tts_settings SET setting_value = 'Site maintenance in progress' WHERE setting_key = 'maintenance_message';
```

### **Performance Settings**

**Cache Configuration:**
```sql
UPDATE tts_settings SET setting_value = '1' WHERE setting_key = 'page_cache_enabled';
UPDATE tts_settings SET setting_value = '3600' WHERE setting_key = 'page_cache_ttl';
UPDATE tts_settings SET setting_value = '1800' WHERE setting_key = 'settings_cache_ttl';
```

**Image Optimization:**
```sql
UPDATE tts_settings SET setting_value = '1920' WHERE setting_key = 'max_image_width';
UPDATE tts_settings SET setting_value = '1080' WHERE setting_key = 'max_image_height';
UPDATE tts_settings SET setting_value = '85' WHERE setting_key = 'image_quality';
```

### **Monitoring Configuration**

**Health Check Settings:**
```sql
UPDATE tts_settings SET setting_value = '300' WHERE setting_key = 'health_check_interval';
UPDATE tts_settings SET setting_value = '10' WHERE setting_key = 'error_spike_threshold';
UPDATE tts_settings SET setting_value = '1.0' WHERE setting_key = 'slow_query_threshold';
```

---

## 📊 **MONITORING QUERIES**

### **Security Monitoring**

```sql
-- Failed login attempts by IP
SELECT ip_address, COUNT(*) as attempts, MAX(created_at) as last_attempt
FROM tts_admin_edits 
WHERE action_type = 'login_failed' 
AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
GROUP BY ip_address 
HAVING attempts > 5
ORDER BY attempts DESC;

-- Rate limit triggers
SELECT * FROM v_security_events 
WHERE action_type = 'rate_limit_triggered'
ORDER BY event_count DESC;
```

### **Performance Monitoring**

```sql
-- Slow operations
SELECT * FROM v_slow_operations 
WHERE avg_duration > 2.0
ORDER BY avg_duration DESC;

-- Database performance
SELECT 
    VARIABLE_NAME,
    VARIABLE_VALUE
FROM information_schema.GLOBAL_STATUS 
WHERE VARIABLE_NAME IN (
    'Slow_queries', 'Connections', 'Threads_connected',
    'Queries', 'Bytes_received', 'Bytes_sent'
);
```

### **System Health**

```sql
-- Overall system health
SELECT check_type, check_name, status, message, checked_at
FROM tts_system_health 
ORDER BY checked_at DESC;

-- Error aggregation
SELECT error_type, occurrence_count, last_occurred
FROM tts_error_aggregation 
WHERE last_occurred > DATE_SUB(NOW(), INTERVAL 24 HOUR)
ORDER BY occurrence_count DESC;
```

---

## 🚨 **ALERT CONFIGURATION**

### **Email Alerts**

**Configure Admin Email:**
```sql
UPDATE tts_settings SET setting_value = 'admin@yourdomain.com' 
WHERE setting_key = 'admin_email';
```

**Alert Thresholds:**
```sql
-- Error spike threshold (errors per hour)
UPDATE tts_settings SET setting_value = '10' 
WHERE setting_key = 'error_spike_threshold';

-- Failure rate threshold (percentage)
UPDATE tts_settings SET setting_value = '5' 
WHERE setting_key = 'failure_rate_threshold';

-- Queue depth threshold
UPDATE tts_settings SET setting_value = '50' 
WHERE setting_key = 'queue_depth_threshold';
```

### **Automated Monitoring**

**Cron Job for Health Checks:**
```bash
# Run health checks every 5 minutes
*/5 * * * * /usr/local/bin/php /home/prizmaso/public_html/admin/tools/health-check.php >> /home/prizmaso/logs/health.log 2>&1
```

**Event Scheduler Tasks:**
```sql
-- Automated error aggregation (runs every hour)
SELECT * FROM information_schema.EVENTS 
WHERE EVENT_NAME = 'ev_performance_maintenance';
```

---

## ✅ **VERIFICATION CHECKLIST**

### **Security Hardening Complete:**
- [ ] Rate limiting enabled for admin endpoints
- [ ] OTP exponential backoff implemented
- [ ] Maintenance mode functional
- [ ] Secrets encrypted and rotatable
- [ ] Webhook signature validation active
- [ ] IP throttling configured

### **Performance Optimization Complete:**
- [ ] Page caching enabled with clear hooks
- [ ] Memory caching (APCu/file fallback) active
- [ ] Image optimization pipeline working
- [ ] Lazy loading implemented
- [ ] Database indexes optimized
- [ ] Slow query monitoring enabled

### **Observability Active:**
- [ ] Health dashboard accessible
- [ ] Error aggregation and alerting functional
- [ ] Performance metrics collection active
- [ ] Automated monitoring cron jobs running
- [ ] Alert email configuration tested

**Phase 6 hardening provides enterprise-grade security, performance, and monitoring for production deployment.**
