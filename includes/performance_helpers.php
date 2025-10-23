<?php
/**
 * TTS PMS Phase 6 - Performance Helpers
 * Caching, optimization, and performance utilities
 */

/**
 * Page cache manager
 */
class PageCache {
    private $cacheDir;
    private $enabled;
    
    public function __construct() {
        $this->cacheDir = dirname(__DIR__) . '/cache/pages/';
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
        
        $this->enabled = $this->isCacheEnabled();
    }
    
    /**
     * Get cached page content
     */
    public function get($key, $maxAge = 3600) {
        if (!$this->enabled) {
            return null;
        }
        
        $cacheFile = $this->getCacheFile($key);
        
        if (!file_exists($cacheFile)) {
            return null;
        }
        
        if ((time() - filemtime($cacheFile)) > $maxAge) {
            unlink($cacheFile);
            return null;
        }
        
        return file_get_contents($cacheFile);
    }
    
    /**
     * Store page content in cache
     */
    public function set($key, $content) {
        if (!$this->enabled) {
            return false;
        }
        
        $cacheFile = $this->getCacheFile($key);
        return file_put_contents($cacheFile, $content) !== false;
    }
    
    /**
     * Clear specific cache entry
     */
    public function clear($key) {
        $cacheFile = $this->getCacheFile($key);
        if (file_exists($cacheFile)) {
            return unlink($cacheFile);
        }
        return true;
    }
    
    /**
     * Clear all page cache
     */
    public function clearAll() {
        $files = glob($this->cacheDir . '*.cache');
        $cleared = 0;
        
        foreach ($files as $file) {
            if (unlink($file)) {
                $cleared++;
            }
        }
        
        return $cleared;
    }
    
    /**
     * Get cache file path
     */
    private function getCacheFile($key) {
        return $this->cacheDir . hash('sha256', $key) . '.cache';
    }
    
    /**
     * Check if caching is enabled
     */
    private function isCacheEnabled() {
        try {
            $db = Database::getInstance();
            $setting = $db->fetchOne(
                "SELECT setting_value FROM tts_settings WHERE setting_key = 'page_cache_enabled' AND category = 'system'"
            );
            return $setting && $setting['setting_value'] === '1';
        } catch (Exception $e) {
            return true; // Default to enabled
        }
    }
}

/**
 * Memory cache with APCu fallback
 */
class MemoryCache {
    private static $instance = null;
    private $useAPCu;
    private $fileCache;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->useAPCu = extension_loaded('apcu') && apcu_enabled();
        $this->fileCache = dirname(__DIR__) . '/cache/memory/';
        
        if (!is_dir($this->fileCache)) {
            mkdir($this->fileCache, 0755, true);
        }
    }
    
    /**
     * Get cached value
     */
    public function get($key, $default = null) {
        if ($this->useAPCu) {
            $value = apcu_fetch($key, $success);
            return $success ? $value : $default;
        }
        
        // Fallback to file cache
        $cacheFile = $this->fileCache . hash('sha256', $key) . '.cache';
        
        if (!file_exists($cacheFile)) {
            return $default;
        }
        
        $data = json_decode(file_get_contents($cacheFile), true);
        
        if (!$data || (isset($data['expires']) && $data['expires'] < time())) {
            unlink($cacheFile);
            return $default;
        }
        
        return $data['value'];
    }
    
    /**
     * Set cached value
     */
    public function set($key, $value, $ttl = 3600) {
        if ($this->useAPCu) {
            return apcu_store($key, $value, $ttl);
        }
        
        // Fallback to file cache
        $cacheFile = $this->fileCache . hash('sha256', $key) . '.cache';
        $data = [
            'value' => $value,
            'expires' => time() + $ttl,
            'created' => time()
        ];
        
        return file_put_contents($cacheFile, json_encode($data)) !== false;
    }
    
    /**
     * Delete cached value
     */
    public function delete($key) {
        if ($this->useAPCu) {
            return apcu_delete($key);
        }
        
        $cacheFile = $this->fileCache . hash('sha256', $key) . '.cache';
        if (file_exists($cacheFile)) {
            return unlink($cacheFile);
        }
        
        return true;
    }
    
    /**
     * Clear all cached values
     */
    public function clear() {
        if ($this->useAPCu) {
            return apcu_clear_cache();
        }
        
        $files = glob($this->fileCache . '*.cache');
        $cleared = 0;
        
        foreach ($files as $file) {
            if (unlink($file)) {
                $cleared++;
            }
        }
        
        return $cleared;
    }
}

/**
 * Settings cache manager
 */
class SettingsCache {
    private static $cache = [];
    private static $memoryCache = null;
    
    /**
     * Get cached settings by category
     */
    public static function getCategory($category) {
        if (isset(self::$cache[$category])) {
            return self::$cache[$category];
        }
        
        if (self::$memoryCache === null) {
            self::$memoryCache = MemoryCache::getInstance();
        }
        
        $cacheKey = "settings_category_$category";
        $cached = self::$memoryCache->get($cacheKey);
        
        if ($cached !== null) {
            self::$cache[$category] = $cached;
            return $cached;
        }
        
        // Load from database
        try {
            $db = Database::getInstance();
            $settings = $db->fetchAll(
                "SELECT setting_key, setting_value FROM tts_settings WHERE category = ?",
                [$category]
            );
            
            $result = [];
            foreach ($settings as $setting) {
                $result[$setting['setting_key']] = $setting['setting_value'];
            }
            
            self::$cache[$category] = $result;
            self::$memoryCache->set($cacheKey, $result, 1800); // 30 minutes
            
            return $result;
            
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Clear settings cache
     */
    public static function clearCategory($category) {
        unset(self::$cache[$category]);
        
        if (self::$memoryCache === null) {
            self::$memoryCache = MemoryCache::getInstance();
        }
        
        self::$memoryCache->delete("settings_category_$category");
    }
    
    /**
     * Clear all settings cache
     */
    public static function clearAll() {
        self::$cache = [];
        
        if (self::$memoryCache === null) {
            self::$memoryCache = MemoryCache::getInstance();
        }
        
        // Clear all settings cache keys
        $categories = ['branding', 'email', 'seo', 'payroll', 'auth', 'system'];
        foreach ($categories as $category) {
            self::$memoryCache->delete("settings_category_$category");
        }
    }
}

/**
 * Image optimization pipeline
 */
class ImageOptimizer {
    private $uploadDir;
    private $maxWidth = 1920;
    private $maxHeight = 1080;
    private $quality = 85;
    
    public function __construct($uploadDir = null) {
        $this->uploadDir = $uploadDir ?: dirname(__DIR__) . '/media/';
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }
    
    /**
     * Optimize uploaded image
     */
    public function optimize($filePath, $options = []) {
        if (!file_exists($filePath)) {
            return false;
        }
        
        $imageInfo = getimagesize($filePath);
        if (!$imageInfo) {
            return false;
        }
        
        $width = $imageInfo[0];
        $height = $imageInfo[1];
        $type = $imageInfo[2];
        
        // Skip if image is already small enough
        if ($width <= $this->maxWidth && $height <= $this->maxHeight) {
            return true;
        }
        
        // Calculate new dimensions
        $ratio = min($this->maxWidth / $width, $this->maxHeight / $height);
        $newWidth = intval($width * $ratio);
        $newHeight = intval($height * $ratio);
        
        // Create image resource
        $source = $this->createImageResource($filePath, $type);
        if (!$source) {
            return false;
        }
        
        // Create resized image
        $resized = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preserve transparency for PNG and GIF
        if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_GIF) {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
            imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);
        }
        
        // Resize image
        imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        
        // Save optimized image
        $result = $this->saveOptimizedImage($resized, $filePath, $type);
        
        // Clean up
        imagedestroy($source);
        imagedestroy($resized);
        
        return $result;
    }
    
    /**
     * Generate lazy loading HTML
     */
    public function generateLazyImage($src, $alt = '', $class = '', $width = null, $height = null) {
        $placeholder = 'data:image/svg+xml;base64,' . base64_encode(
            '<svg width="' . ($width ?: 300) . '" height="' . ($height ?: 200) . '" xmlns="http://www.w3.org/2000/svg">
                <rect width="100%" height="100%" fill="#f0f0f0"/>
                <text x="50%" y="50%" text-anchor="middle" dy=".3em" fill="#999">Loading...</text>
            </svg>'
        );
        
        $attributes = [
            'src' => $placeholder,
            'data-src' => $src,
            'alt' => $alt,
            'loading' => 'lazy',
            'class' => trim($class . ' lazy-image')
        ];
        
        if ($width) $attributes['width'] = $width;
        if ($height) $attributes['height'] = $height;
        
        $html = '<img';
        foreach ($attributes as $key => $value) {
            $html .= ' ' . $key . '="' . htmlspecialchars($value) . '"';
        }
        $html .= '>';
        
        return $html;
    }
    
    /**
     * Create image resource from file
     */
    private function createImageResource($filePath, $type) {
        switch ($type) {
            case IMAGETYPE_JPEG:
                return imagecreatefromjpeg($filePath);
            case IMAGETYPE_PNG:
                return imagecreatefrompng($filePath);
            case IMAGETYPE_GIF:
                return imagecreatefromgif($filePath);
            case IMAGETYPE_WEBP:
                return imagecreatefromwebp($filePath);
            default:
                return false;
        }
    }
    
    /**
     * Save optimized image
     */
    private function saveOptimizedImage($image, $filePath, $type) {
        switch ($type) {
            case IMAGETYPE_JPEG:
                return imagejpeg($image, $filePath, $this->quality);
            case IMAGETYPE_PNG:
                return imagepng($image, $filePath, 9);
            case IMAGETYPE_GIF:
                return imagegif($image, $filePath);
            case IMAGETYPE_WEBP:
                return imagewebp($image, $filePath, $this->quality);
            default:
                return false;
        }
    }
}

/**
 * Database query optimizer
 */
class QueryOptimizer {
    private $db;
    private $slowQueryThreshold = 1.0; // 1 second
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Analyze slow queries
     */
    public function analyzeSlowQueries() {
        try {
            // Enable slow query log temporarily
            $this->db->query("SET SESSION long_query_time = ?", [$this->slowQueryThreshold]);
            
            // Get slow query log status
            $status = $this->db->fetchOne("SHOW VARIABLES LIKE 'slow_query_log'");
            
            if ($status && $status['Value'] === 'ON') {
                // Get recent slow queries (if available)
                $slowQueries = $this->getSlowQueriesFromLog();
                return $this->generateOptimizationSuggestions($slowQueries);
            }
            
            return ['status' => 'slow_query_log_disabled'];
            
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Suggest indexes for tables with high scan counts
     */
    public function suggestIndexes() {
        try {
            $suggestions = [];
            
            // Get table scan statistics
            $tables = $this->db->fetchAll("
                SELECT TABLE_NAME, TABLE_ROWS 
                FROM information_schema.TABLES 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME LIKE 'tts_%'
                AND TABLE_ROWS > 1000
            ");
            
            foreach ($tables as $table) {
                $tableName = $table['TABLE_NAME'];
                
                // Check existing indexes
                $indexes = $this->db->fetchAll("SHOW INDEX FROM `$tableName`");
                $existingIndexes = array_column($indexes, 'Column_name');
                
                // Suggest common indexes
                $indexSuggestions = $this->getIndexSuggestions($tableName, $existingIndexes);
                
                if (!empty($indexSuggestions)) {
                    $suggestions[$tableName] = $indexSuggestions;
                }
            }
            
            return $suggestions;
            
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Get index suggestions for specific table
     */
    private function getIndexSuggestions($tableName, $existingIndexes) {
        $suggestions = [];
        
        // Common index patterns
        $commonIndexes = [
            'tts_admin_edits' => [
                ['admin_id', 'created_at'],
                ['action_type', 'created_at'],
                ['object_type', 'object_id']
            ],
            'tts_admin_sync' => [
                ['status', 'priority', 'created_at'],
                ['admin_id', 'status']
            ],
            'tts_cms_pages' => [
                ['status', 'updated_at'],
                ['slug', 'status']
            ],
            'tts_page_layouts' => [
                ['page_id', 'is_current'],
                ['admin_id', 'updated_at']
            ],
            'tts_settings' => [
                ['category', 'setting_key'],
                ['updated_at']
            ]
        ];
        
        if (isset($commonIndexes[$tableName])) {
            foreach ($commonIndexes[$tableName] as $indexColumns) {
                $indexName = 'idx_' . implode('_', $indexColumns);
                
                // Check if similar index exists
                $hasIndex = false;
                foreach ($indexColumns as $column) {
                    if (in_array($column, $existingIndexes)) {
                        $hasIndex = true;
                        break;
                    }
                }
                
                if (!$hasIndex) {
                    $suggestions[] = [
                        'index_name' => $indexName,
                        'columns' => $indexColumns,
                        'sql' => "ALTER TABLE `$tableName` ADD INDEX `$indexName` (`" . implode('`, `', $indexColumns) . "`)"
                    ];
                }
            }
        }
        
        return $suggestions;
    }
    
    /**
     * Get slow queries from log (simplified)
     */
    private function getSlowQueriesFromLog() {
        // This would parse the slow query log file
        // For now, return empty array as log parsing is complex
        return [];
    }
    
    /**
     * Generate optimization suggestions
     */
    private function generateOptimizationSuggestions($slowQueries) {
        $suggestions = [];
        
        foreach ($slowQueries as $query) {
            // Analyze query and suggest optimizations
            $suggestions[] = [
                'query' => $query,
                'suggestions' => $this->analyzeQuery($query)
            ];
        }
        
        return $suggestions;
    }
    
    /**
     * Analyze individual query
     */
    private function analyzeQuery($query) {
        $suggestions = [];
        
        // Basic query analysis
        if (stripos($query, 'SELECT *') !== false) {
            $suggestions[] = 'Avoid SELECT * - specify only needed columns';
        }
        
        if (stripos($query, 'ORDER BY') !== false && stripos($query, 'LIMIT') === false) {
            $suggestions[] = 'Consider adding LIMIT when using ORDER BY';
        }
        
        if (stripos($query, 'WHERE') === false && stripos($query, 'SELECT') !== false) {
            $suggestions[] = 'Consider adding WHERE clause to limit results';
        }
        
        return $suggestions;
    }
}

/**
 * Cache clear hooks for builder publish
 */
function clearCacheOnPublish($pageId) {
    $pageCache = new PageCache();
    $memoryCache = MemoryCache::getInstance();
    
    // Clear page-specific cache
    $pageCache->clear("page_$pageId");
    
    // Clear navigation cache
    $memoryCache->delete('navigation_cache');
    
    // Clear settings cache that might affect page rendering
    SettingsCache::clearCategory('branding');
    SettingsCache::clearCategory('seo');
    
    // Log cache clear
    try {
        $db = Database::getInstance();
        $db->insert('tts_admin_edits', [
            'admin_id' => $_SESSION['user_id'] ?? 1,
            'action_type' => 'cache_clear',
            'object_type' => 'page',
            'object_id' => $pageId,
            'changes' => json_encode(['reason' => 'page_publish']),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'system',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Cache Manager'
        ]);
    } catch (Exception $e) {
        error_log("Failed to log cache clear: " . $e->getMessage());
    }
}

/**
 * Preload critical resources
 */
function preloadCriticalResources() {
    $resources = [
        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
        'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap'
    ];
    
    foreach ($resources as $resource) {
        echo '<link rel="preload" href="' . $resource . '" as="style" onload="this.onload=null;this.rel=\'stylesheet\'">' . "\n";
    }
}
?>
