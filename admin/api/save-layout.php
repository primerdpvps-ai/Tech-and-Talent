<?php
/**
 * TTS PMS - Save Layout API
 * Saves page layout JSON and creates/updates physical file
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/init.php';
require_once '../../includes/admin_helpers.php';

session_start();
require_admin();
require_capability('manage_pages');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
}

$db = Database::getInstance();

try {
    $pageId = (int)($_POST['page_id'] ?? 0);
    $layoutJson = $_POST['layout_json'] ?? '';
    $generateFile = (bool)($_POST['generate_file'] ?? true);
    
    if (!$pageId || !$layoutJson) {
        throw new Exception('Missing required parameters');
    }
    
    // Get page info
    $page = $db->fetchOne("SELECT * FROM tts_cms_pages WHERE id = ?", [$pageId]);
    if (!$page) {
        throw new Exception('Page not found');
    }
    
    // Validate JSON
    $layoutData = json_decode($layoutJson, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON layout');
    }
    
    // Get current layout for backup
    $currentLayout = $db->fetchOne("
        SELECT * FROM tts_page_layouts 
        WHERE page_id = ? AND is_current = 1
    ", [$pageId]);
    
    // Create backup in history
    if ($currentLayout) {
        $db->insert('tts_cms_history', [
            'content_type' => 'page',
            'content_id' => $pageId,
            'version_number' => getNextVersionNumber('page', $pageId),
            'content_data' => $currentLayout['layout_json'],
            'layout_backup' => $currentLayout['layout_json'],
            'admin_id' => $_SESSION['user_id'],
            'change_description' => 'Auto-backup before layout update',
            'is_auto_backup' => true
        ]);
        
        // Mark current layout as not current
        $db->update('tts_page_layouts', ['is_current' => false], 'page_id = ?', [$pageId]);
    }
    
    // Save new layout
    $layoutId = $db->insert('tts_page_layouts', [
        'page_id' => $pageId,
        'layout_json' => $layoutJson,
        'version' => ($currentLayout['version'] ?? 0) + 1,
        'is_current' => true,
        'layout_type' => 'page',
        'admin_id' => $_SESSION['user_id']
    ]);
    
    // Generate physical file if requested
    $filePath = null;
    if ($generateFile) {
        $filePath = generatePhysicalFile($page, $layoutData);
    }
    
    // Update page metadata
    $db->update('tts_cms_pages', [
        'updated_by' => $_SESSION['user_id'],
        'updated_at' => date('Y-m-d H:i:s')
    ], 'id = ?', [$pageId]);
    
    // Log the action
    log_admin_action(
        'page_edit',
        'page',
        $pageId,
        $currentLayout ? json_decode($currentLayout['layout_json'], true) : null,
        $layoutData,
        "Updated page layout: {$page['title']}"
    );
    
    echo json_encode([
        'success' => true,
        'layout_id' => $layoutId,
        'file_path' => $filePath,
        'version' => ($currentLayout['version'] ?? 0) + 1
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}

function generatePhysicalFile($page, $layoutData) {
    // Determine file path
    $pagesDir = dirname(dirname(__DIR__)) . '/packages/web/src/pages/';
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
            
        case 'image':
            $src = $settings['src'] ?? '';
            $alt = $settings['alt'] ?? '';
            $rounded = $settings['rounded'] ?? false;
            $align = $settings['align'] ?? 'left';
            
            $imgClass = 'img-fluid';
            if ($rounded) $imgClass .= ' rounded';
            
            $containerClass = '';
            if ($align === 'center') $containerClass = 'text-center';
            if ($align === 'right') $containerClass = 'text-end';
            
            return "<div class=\"{$containerClass}\"><img src=\"{$src}\" alt=\"{$alt}\" class=\"{$imgClass}\"></div>";
            
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
            
        case 'testimonial':
            $quote = $settings['quote'] ?? 'Great service!';
            $author = $settings['author'] ?? 'Anonymous';
            $position = $settings['position'] ?? '';
            $company = $settings['company'] ?? '';
            
            return "
                <div class=\"testimonial text-center p-4\">
                    <blockquote class=\"blockquote\">
                        <p>\"" . htmlspecialchars($quote) . "\"</p>
                    </blockquote>
                    <footer class=\"blockquote-footer\">
                        <strong>" . htmlspecialchars($author) . "</strong>
                        " . ($position ? "<br><small>" . htmlspecialchars($position) . "</small>" : "") . "
                        " . ($company ? "<br><small>" . htmlspecialchars($company) . "</small>" : "") . "
                    </footer>
                </div>
            ";
            
        case 'contact_form':
            return "
                <form class=\"contact-form\">
                    <div class=\"mb-3\">
                        <label class=\"form-label\">Name</label>
                        <input type=\"text\" class=\"form-control\" required>
                    </div>
                    <div class=\"mb-3\">
                        <label class=\"form-label\">Email</label>
                        <input type=\"email\" class=\"form-control\" required>
                    </div>
                    <div class=\"mb-3\">
                        <label class=\"form-label\">Message</label>
                        <textarea class=\"form-control\" rows=\"5\" required></textarea>
                    </div>
                    <button type=\"submit\" class=\"btn btn-primary\">Send Message</button>
                </form>
            ";
            
        default:
            return "<div class=\"custom-block\">" . htmlspecialchars($content) . "</div>";
    }
}

function getNextVersionNumber($contentType, $contentId) {
    global $db;
    
    $result = $db->fetchOne(
        "SELECT MAX(version_number) as max_version FROM tts_cms_history WHERE content_type = ? AND content_id = ?",
        [$contentType, $contentId]
    );
    
    return ($result['max_version'] ?? 0) + 1;
}
?>
