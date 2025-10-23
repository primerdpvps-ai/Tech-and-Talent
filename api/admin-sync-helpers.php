<?php
/**
 * TTS PMS Phase 5 - Sync API Helper Functions
 * Supporting functions for event handlers
 */

/**
 * Generate physical page file from layout data
 */
function generatePageFile($page, $layoutData) {
    $pagesDir = dirname(__DIR__) . '/packages/web/src/pages/';
    if (!is_dir($pagesDir)) {
        mkdir($pagesDir, 0755, true);
    }
    
    $fileName = $page['slug'] . '.php';
    $filePath = $pagesDir . $fileName;
    
    // Security check - prevent overwriting system files
    $protectedFiles = ['index.php', 'login.php', 'dashboard.php', 'admin.php'];
    if (in_array($fileName, $protectedFiles)) {
        throw new Exception('Cannot overwrite protected system file');
    }
    
    // Generate PHP content
    $phpContent = generatePageContent($page, $layoutData);
    
    // Write file
    if (file_put_contents($filePath, $phpContent) === false) {
        throw new Exception('Failed to write page file');
    }
    
    return $filePath;
}

/**
 * Generate complete page content from layout data
 */
function generatePageContent($page, $layoutData) {
    $title = htmlspecialchars($page['title']);
    $metaTitle = htmlspecialchars($page['meta_title'] ?: $page['title']);
    $metaDescription = htmlspecialchars($page['meta_description'] ?: '');
    $canonicalUrl = htmlspecialchars($page['canonical_url'] ?: '');
    
    $robotsContent = '';
    if (!$page['robots_index']) $robotsContent .= 'noindex,';
    if (!$page['robots_follow']) $robotsContent .= 'nofollow,';
    $robotsContent = rtrim($robotsContent, ',');
    
    $html = renderLayoutToHtml($layoutData);
    
    return <<<PHP
<?php
/**
 * Generated Page: {$title}
 * Created by TTS PMS Visual Builder
 * Last Updated: {date('Y-m-d H:i:s')}
 */

require_once '../../../config/init.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$metaTitle}</title>
    <meta name="description" content="{$metaDescription}">
    {($canonicalUrl ? '<link rel="canonical" href="' . $canonicalUrl . '">' : '')}
    {($robotsContent ? '<meta name="robots" content="' . $robotsContent . '">' : '')}
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Inter', sans-serif; }
        .hero-section { min-height: 60vh; display: flex; align-items: center; }
        .feature-card { transition: transform 0.3s ease; }
        .feature-card:hover { transform: translateY(-5px); }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; }
        .section-padding { padding: 4rem 0; }
    </style>
</head>
<body>
    {$html}
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
PHP;
}

/**
 * Render layout JSON to HTML
 */
function renderLayoutToHtml($layoutData) {
    $html = '';
    
    if (!isset($layoutData['sections']) || !is_array($layoutData['sections'])) {
        return '<div class="container"><p>No content available</p></div>';
    }
    
    foreach ($layoutData['sections'] as $section) {
        $html .= renderSection($section);
    }
    
    return $html;
}

/**
 * Render individual section
 */
function renderSection($section) {
    $sectionClass = 'section-padding';
    $containerClass = 'container';
    
    if (isset($section['settings'])) {
        if ($section['settings']['background'] === 'dark') {
            $sectionClass .= ' bg-dark text-white';
        } elseif ($section['settings']['background'] === 'primary') {
            $sectionClass .= ' bg-primary text-white';
        } elseif ($section['settings']['background'] === 'light') {
            $sectionClass .= ' bg-light';
        }
        
        if (isset($section['settings']['fullwidth']) && $section['settings']['fullwidth']) {
            $containerClass = 'container-fluid';
        }
    }
    
    $html = "<section class=\"{$sectionClass}\">";
    $html .= "<div class=\"{$containerClass}\">";
    
    if (isset($section['rows']) && is_array($section['rows'])) {
        foreach ($section['rows'] as $row) {
            $html .= renderRow($row);
        }
    }
    
    $html .= "</div></section>";
    
    return $html;
}

/**
 * Render row with columns
 */
function renderRow($row) {
    $html = '<div class="row">';
    
    if (isset($row['columns']) && is_array($row['columns'])) {
        foreach ($row['columns'] as $column) {
            $html .= renderColumn($column);
        }
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Render column with blocks
 */
function renderColumn($column) {
    $width = $column['width'] ?? '12';
    $html = "<div class=\"col-md-{$width}\">";
    
    if (isset($column['blocks']) && is_array($column['blocks'])) {
        foreach ($column['blocks'] as $block) {
            $html .= renderBlock($block);
        }
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Render individual content block
 */
function renderBlock($block) {
    $type = $block['type'] ?? 'text';
    $content = $block['content'] ?? '';
    $settings = $block['settings'] ?? [];
    
    switch ($type) {
        case 'heading':
            $size = $settings['size'] ?? 'h2';
            $align = $settings['align'] ?? 'left';
            return "<{$size} class=\"text-{$align}\">" . htmlspecialchars($content) . "</{$size}>";
            
        case 'text':
            $align = $settings['align'] ?? 'left';
            $size = $settings['size'] ?? '';
            $class = "text-{$align}";
            if ($size === 'lg') $class .= ' fs-5';
            if ($size === 'sm') $class .= ' small';
            return "<p class=\"{$class}\">" . nl2br(htmlspecialchars($content)) . "</p>";
            
        case 'button':
            $style = $settings['style'] ?? 'primary';
            $size = $settings['size'] ?? '';
            $link = $settings['link'] ?? '#';
            $target = $settings['target'] ?? '_self';
            $align = $settings['align'] ?? 'left';
            
            $btnClass = "btn btn-{$style}";
            if ($size) $btnClass .= " btn-{$size}";
            
            $containerClass = '';
            if ($align === 'center') $containerClass = 'text-center';
            if ($align === 'right') $containerClass = 'text-end';
            
            return "<div class=\"{$containerClass}\"><a href=\"{$link}\" target=\"{$target}\" class=\"{$btnClass}\">" . htmlspecialchars($content) . "</a></div>";
            
        case 'feature':
            $title = $content['title'] ?? 'Feature Title';
            $description = $content['description'] ?? 'Feature description';
            $icon = $content['icon'] ?? 'fas fa-star';
            
            return "
                <div class=\"feature-card text-center p-4\">
                    <div class=\"mb-3\"><i class=\"{$icon} fa-3x text-primary\"></i></div>
                    <h4>" . htmlspecialchars($title) . "</h4>
                    <p class=\"text-muted\">" . htmlspecialchars($description) . "</p>
                </div>
            ";
            
        default:
            return "<div class=\"custom-block\">" . htmlspecialchars($content) . "</div>";
    }
}

/**
 * Apply settings to configuration files
 */
function applySettingsToFiles($category, $settings) {
    switch ($category) {
        case 'email':
            updateEmailConfigFile($settings);
            break;
        case 'branding':
            updateBrandingFiles($settings);
            break;
        case 'seo':
            updateSEOInTemplates($settings);
            break;
    }
}

/**
 * Update email configuration file
 */
function updateEmailConfigFile($settings) {
    $configPath = dirname(__DIR__) . '/config/email_config.php';
    if (!file_exists($configPath)) return;
    
    $content = file_get_contents($configPath);
    
    $replacements = [
        'smtp_host' => 'SMTP_HOST',
        'smtp_port' => 'SMTP_PORT',
        'smtp_username' => 'SMTP_USERNAME',
        'smtp_password' => 'SMTP_PASSWORD',
        'smtp_encryption' => 'SMTP_ENCRYPTION',
        'from_email' => 'FROM_EMAIL',
        'from_name' => 'FROM_NAME'
    ];
    
    foreach ($replacements as $key => $constant) {
        if (isset($settings[$key])) {
            $pattern = "/define\('$constant', '[^']*'\);/";
            $replacement = "define('$constant', '{$settings[$key]}');";
            $content = preg_replace($pattern, $replacement, $content);
        }
    }
    
    file_put_contents($configPath, $content);
}

/**
 * Update branding in template files
 */
function updateBrandingFiles($settings) {
    $files = [
        dirname(__DIR__) . '/index.php',
        dirname(__DIR__) . '/packages/web/auth/sign-in.php'
    ];
    
    foreach ($files as $file) {
        if (!file_exists($file)) continue;
        
        $content = file_get_contents($file);
        
        if (isset($settings['site_name'])) {
            $content = preg_replace(
                '/<title>[^<]*<\/title>/',
                "<title>{$settings['site_name']}</title>",
                $content
            );
        }
        
        if (isset($settings['meta_description'])) {
            $pattern = '/<meta name="description" content="[^"]*">/';
            $replacement = '<meta name="description" content="' . htmlspecialchars($settings['meta_description']) . '">';
            
            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, $replacement, $content);
            } else {
                $content = str_replace('</head>', "    $replacement\n</head>", $content);
            }
        }
        
        file_put_contents($file, $content);
    }
}

/**
 * Update payroll configuration file
 */
function updatePayrollConfigFile($settings) {
    $configPath = dirname(__DIR__) . '/config/app_config.php';
    if (!file_exists($configPath)) return;
    
    $content = file_get_contents($configPath);
    
    foreach ($settings as $key => $value) {
        $constantName = 'PAYROLL_' . strtoupper($key);
        $pattern = "/define\('$constantName', '[^']*'\);/";
        $replacement = "define('$constantName', '$value');";
        
        if (preg_match($pattern, $content)) {
            $content = preg_replace($pattern, $replacement, $content);
        } else {
            $content .= "\ndefine('$constantName', '$value');";
        }
    }
    
    file_put_contents($configPath, $content);
}

/**
 * Update SEO in existing pages
 */
function updateSEOInExistingPages($seoSettings) {
    $pagesDir = dirname(__DIR__) . '/packages/web/src/pages/';
    $updatedFiles = [];
    
    if (!is_dir($pagesDir)) return $updatedFiles;
    
    $files = glob($pagesDir . '*.php');
    
    foreach ($files as $file) {
        if (!is_writable($file)) continue;
        
        $content = file_get_contents($file);
        $updated = false;
        
        if (isset($seoSettings['meta_description'])) {
            $pattern = '/<meta name="description" content="[^"]*">/';
            $replacement = '<meta name="description" content="' . htmlspecialchars($seoSettings['meta_description']) . '">';
            
            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, $replacement, $content);
                $updated = true;
            }
        }
        
        if (isset($seoSettings['meta_title'])) {
            $pattern = '/<title>[^<]*<\/title>/';
            $replacement = "<title>{$seoSettings['meta_title']}</title>";
            
            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, $replacement, $content);
                $updated = true;
            }
        }
        
        if ($updated) {
            file_put_contents($file, $content);
            $updatedFiles[] = basename($file);
        }
    }
    
    return $updatedFiles;
}

/**
 * Update SEO in template files
 */
function updateSEOInTemplates($seoSettings) {
    $templateDirs = [
        dirname(__DIR__) . '/packages/web/',
        dirname(__DIR__) . '/admin/'
    ];
    
    foreach ($templateDirs as $dir) {
        if (!is_dir($dir)) continue;
        
        $files = glob($dir . '*.php');
        
        foreach ($files as $file) {
            if (!is_writable($file)) continue;
            
            $content = file_get_contents($file);
            
            if (isset($seoSettings['meta_description'])) {
                $pattern = '/<meta name="description" content="[^"]*">/';
                $replacement = '<meta name="description" content="' . htmlspecialchars($seoSettings['meta_description']) . '">';
                
                if (preg_match($pattern, $content)) {
                    $content = preg_replace($pattern, $replacement, $content);
                    file_put_contents($file, $content);
                }
            }
        }
    }
}

/**
 * Check module dependencies
 */
function checkModuleDependencies($moduleName) {
    $dependencies = [
        'payroll' => ['time_tracking', 'user_management'],
        'time_tracking' => ['user_management'],
        'project_management' => ['user_management', 'time_tracking'],
        'reporting' => ['payroll', 'time_tracking']
    ];
    
    return $dependencies[$moduleName] ?? [];
}

/**
 * Rebuild navigation cache
 */
function rebuildNavigationCache() {
    global $db;
    
    $enabledModules = $db->fetchAll(
        "SELECT module_name FROM tts_module_config WHERE is_enabled = 1"
    );
    
    $cacheDir = dirname(__DIR__) . '/cache/';
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }
    
    $cacheFile = $cacheDir . 'navigation.cache';
    file_put_contents($cacheFile, json_encode($enabledModules));
}

/**
 * Clear module-specific cache
 */
function clearModuleCache($moduleName) {
    $cacheDir = dirname(__DIR__) . '/cache/modules/';
    if (is_dir($cacheDir)) {
        $cacheFile = $cacheDir . $moduleName . '.cache';
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }
    }
}

/**
 * Clear settings cache
 */
function clearSettingsCache($category) {
    $cacheDir = dirname(__DIR__) . '/cache/settings/';
    if (is_dir($cacheDir)) {
        $cacheFile = $cacheDir . $category . '.cache';
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }
    }
}

/**
 * Queue payroll recalculation
 */
function queuePayrollRecalculation($syncId) {
    global $db;
    
    $db->insert('tts_admin_sync', [
        'sync_id' => 'payroll_recalc_' . $syncId,
        'action_type' => 'payroll_recalculation',
        'data_payload' => json_encode(['trigger_sync_id' => $syncId]),
        'priority' => 2,
        'status' => 'pending',
        'admin_id' => $_SESSION['user_id'] ?? 1,
        'created_at' => date('Y-m-d H:i:s')
    ]);
}

/**
 * Get next version number for content
 */
function getNextVersionNumber($contentType, $contentId) {
    global $db;
    
    $result = $db->fetchOne(
        "SELECT MAX(version_number) as max_version FROM tts_cms_history WHERE content_type = ? AND content_id = ?",
        [$contentType, $contentId]
    );
    
    return ($result['max_version'] ?? 0) + 1;
}

/**
 * Get setting description
 */
function getSettingDescription($key) {
    $descriptions = [
        'site_name' => 'Website name displayed in title and headers',
        'tagline' => 'Site tagline or slogan',
        'base_hourly_rate' => 'Base hourly rate in PKR',
        'streak_bonus' => 'Bonus for consecutive work streaks',
        'meta_title' => 'Default page title for SEO',
        'meta_description' => 'Default meta description',
        'smtp_host' => 'SMTP server hostname',
        'smtp_port' => 'SMTP server port number'
    ];
    
    return $descriptions[$key] ?? '';
}
?>
