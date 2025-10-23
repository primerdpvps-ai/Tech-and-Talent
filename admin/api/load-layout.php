<?php
/**
 * TTS PMS - Load Layout API
 * Loads page layout JSON for editing
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/init.php';
require_once '../../includes/admin_helpers.php';

session_start();
require_admin();
require_capability('manage_pages');

$db = Database::getInstance();

try {
    $pageId = (int)($_GET['page_id'] ?? $_POST['page_id'] ?? 0);
    $version = (int)($_GET['version'] ?? $_POST['version'] ?? 0);
    
    if (!$pageId) {
        throw new Exception('Page ID required');
    }
    
    // Get page info
    $page = $db->fetchOne("SELECT * FROM tts_cms_pages WHERE id = ?", [$pageId]);
    if (!$page) {
        throw new Exception('Page not found');
    }
    
    // Get layout data
    if ($version > 0) {
        // Load specific version from history
        $layout = $db->fetchOne("
            SELECT layout_backup as layout_json, version_number as version, created_at
            FROM tts_cms_history 
            WHERE content_type = 'page' AND content_id = ? AND version_number = ?
        ", [$pageId, $version]);
        
        if (!$layout) {
            throw new Exception('Version not found');
        }
        
        $layoutJson = $layout['layout_json'];
        $versionInfo = [
            'version' => $layout['version'],
            'created_at' => $layout['created_at'],
            'is_current' => false
        ];
    } else {
        // Load current version
        $layout = $db->fetchOne("
            SELECT layout_json, version, admin_id, created_at
            FROM tts_page_layouts 
            WHERE page_id = ? AND is_current = 1
        ", [$pageId]);
        
        if (!$layout) {
            // No layout exists, create default
            $layoutJson = json_encode([
                'sections' => [
                    [
                        'type' => 'header',
                        'settings' => ['background' => 'light'],
                        'rows' => [
                            [
                                'columns' => [
                                    [
                                        'width' => '12',
                                        'blocks' => [
                                            [
                                                'type' => 'heading',
                                                'content' => $page['title'],
                                                'settings' => ['size' => 'h1', 'align' => 'center']
                                            ],
                                            [
                                                'type' => 'text',
                                                'content' => 'Welcome to your new page. Start editing to add content.',
                                                'settings' => ['align' => 'center', 'size' => 'lg']
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]);
            
            $versionInfo = [
                'version' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'is_current' => true,
                'is_default' => true
            ];
        } else {
            $layoutJson = $layout['layout_json'];
            $versionInfo = [
                'version' => $layout['version'],
                'created_at' => $layout['created_at'],
                'is_current' => true
            ];
        }
    }
    
    // Get version history
    $versions = $db->fetchAll("
        SELECT version_number as version, created_at, change_description, admin_id,
               u.first_name, u.last_name
        FROM tts_cms_history h
        LEFT JOIN tts_users u ON h.admin_id = u.id
        WHERE h.content_type = 'page' AND h.content_id = ?
        ORDER BY h.version_number DESC
        LIMIT 10
    ", [$pageId]);
    
    // Get available templates
    $templates = $db->fetchAll("
        SELECT id, name, description, category, preview_image
        FROM tts_page_templates
        WHERE is_active = 1
        ORDER BY category, name
    ");
    
    // Get available components
    $components = $db->fetchAll("
        SELECT id, name, component_type, category, preview_html
        FROM tts_page_components
        ORDER BY category, name
    ");
    
    echo json_encode([
        'success' => true,
        'page' => $page,
        'layout_json' => $layoutJson,
        'layout_data' => json_decode($layoutJson, true),
        'version_info' => $versionInfo,
        'versions' => $versions,
        'templates' => $templates,
        'components' => $components
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
