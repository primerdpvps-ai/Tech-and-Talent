<?php
/**
 * TTS PMS - Divi Builder Integration
 * Visual page builder for admin customization
 */

// Load configuration
require_once '../config/init.php';

// Start session
session_start();

// Check if user is logged in as admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

try {
    $db = Database::getInstance();
} catch (Exception $e) {
    error_log("Divi builder error: " . $e->getMessage());
    $db = null;
}

$message = '';
$messageType = '';
$currentPage = $_GET['page'] ?? 'dashboard';
$currentRole = $_GET['role'] ?? 'visitor';

// Available pages for customization
$availablePages = [
    'dashboard' => [
        'visitor' => 'Visitor Dashboard',
        'candidate' => 'Candidate Dashboard', 
        'new_employee' => 'New Employee Dashboard',
        'employee' => 'Employee Dashboard',
        'manager' => 'Manager Dashboard',
        'ceo' => 'CEO Dashboard'
    ],
    'forms' => [
        'evaluation' => 'Visitor Evaluation Form',
        'job_application' => 'Job Application Form',
        'leave_request' => 'Leave Request Form'
    ],
    'pages' => [
        'homepage' => 'Main Homepage',
        'services' => 'Services Page',
        'about' => 'About Us Page',
        'contact' => 'Contact Page'
    ],
    'reports' => [
        'payslip' => 'Payslip Template',
        'performance' => 'Performance Report',
        'timesheet' => 'Timesheet Report'
    ]
];

// Handle save operations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_layout'])) {
    $pageType = $_POST['page_type'] ?? '';
    $pageRole = $_POST['page_role'] ?? '';
    $layoutData = $_POST['layout_data'] ?? '';
    $customCSS = $_POST['custom_css'] ?? '';
    $customJS = $_POST['custom_js'] ?? '';
    
    try {
        if ($db) {
            // Save layout configuration to database
            $layoutConfig = [
                'page_type' => $pageType,
                'page_role' => $pageRole,
                'layout_data' => $layoutData,
                'custom_css' => $customCSS,
                'custom_js' => $customJS,
                'created_by' => $_SESSION['admin_id'] ?? 1,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // Check if layout exists
            $existingLayout = $db->fetchOne(
                'SELECT id FROM tts_page_layouts WHERE page_type = ? AND page_role = ?',
                [$pageType, $pageRole]
            );
            
            if ($existingLayout) {
                $db->update(
                    'tts_page_layouts',
                    $layoutConfig,
                    'page_type = ? AND page_role = ?',
                    [$pageType, $pageRole]
                );
            } else {
                $db->insert('tts_page_layouts', $layoutConfig);
            }
            
            $message = 'Layout saved successfully!';
            $messageType = 'success';
        } else {
            // Fallback: Save to file system
            $layoutDir = '../layouts/';
            if (!is_dir($layoutDir)) {
                mkdir($layoutDir, 0755, true);
            }
            
            $layoutFile = $layoutDir . $pageType . '_' . $pageRole . '.json';
            $layoutData = json_encode([
                'layout_data' => $layoutData,
                'custom_css' => $customCSS,
                'custom_js' => $customJS,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            file_put_contents($layoutFile, $layoutData);
            $message = 'Layout saved to file system successfully!';
            $messageType = 'success';
        }
    } catch (Exception $e) {
        $message = 'Error saving layout: ' . $e->getMessage();
        $messageType = 'danger';
        error_log('Divi builder save error: ' . $e->getMessage());
    }
}

// Load existing layout
$existingLayout = null;
if ($db) {
    try {
        $existingLayout = $db->fetchOne(
            'SELECT * FROM tts_page_layouts WHERE page_type = ? AND page_role = ?',
            [$currentPage, $currentRole]
        );
    } catch (Exception $e) {
        // Fallback to file system
        $layoutFile = '../layouts/' . $currentPage . '_' . $currentRole . '.json';
        if (file_exists($layoutFile)) {
            $existingLayout = json_decode(file_get_contents($layoutFile), true);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-mdb-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Divi Builder - TTS PMS</title>
    
    <!-- Bootstrap & MDB CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- GrapesJS CSS -->
    <link rel="stylesheet" href="https://unpkg.com/grapesjs/dist/css/grapes.min.css">
    <link rel="stylesheet" href="https://unpkg.com/grapesjs-preset-webpage/dist/grapesjs-preset-webpage.min.css">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
        }
        
        .builder-header {
            background: linear-gradient(135deg, #6f42c1, #8e44ad);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .builder-toolbar {
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            padding: 0.75rem 0;
        }
        
        .page-selector {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            margin-right: 1rem;
        }
        
        .builder-container {
            height: calc(100vh - 140px);
            display: flex;
        }
        
        .builder-sidebar {
            width: 300px;
            background: #f8f9fa;
            border-right: 1px solid #dee2e6;
            overflow-y: auto;
        }
        
        .builder-main {
            flex: 1;
            position: relative;
        }
        
        .component-category {
            padding: 1rem;
            border-bottom: 1px solid #dee2e6;
        }
        
        .component-item {
            display: flex;
            align-items: center;
            padding: 0.75rem;
            margin: 0.25rem 0;
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .component-item:hover {
            border-color: #6f42c1;
            box-shadow: 0 2px 8px rgba(111, 66, 193, 0.2);
        }
        
        .component-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #6f42c1, #8e44ad);
            color: white;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 0.75rem;
        }
        
        .save-panel {
            position: fixed;
            top: 0;
            right: -400px;
            width: 400px;
            height: 100vh;
            background: white;
            box-shadow: -2px 0 10px rgba(0, 0, 0, 0.1);
            transition: right 0.3s ease;
            z-index: 1050;
            overflow-y: auto;
        }
        
        .save-panel.active {
            right: 0;
        }
        
        .save-panel-header {
            background: #6f42c1;
            color: white;
            padding: 1rem;
        }
        
        .save-panel-body {
            padding: 1rem;
        }
        
        #gjs {
            height: 100%;
            overflow: hidden;
        }
        
        .gjs-cv-canvas {
            background: #f8f9fa;
        }
        
        .preview-mode {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: white;
            z-index: 1060;
            display: none;
        }
        
        .preview-header {
            background: #343a40;
            color: white;
            padding: 1rem;
            display: flex;
            justify-content: between;
            align-items: center;
        }
        
        .preview-content {
            height: calc(100vh - 60px);
            overflow-y: auto;
            padding: 2rem;
        }
    </style>
</head>
<body>
    <!-- Builder Header -->
    <div class="builder-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h4 class="mb-0">
                        <i class="fas fa-paint-brush me-2"></i>Divi Builder
                        <small class="opacity-75 ms-2">Visual Page Customization</small>
                    </h4>
                </div>
                <div class="col-md-6 text-end">
                    <button class="btn btn-outline-light me-2" onclick="togglePreview()">
                        <i class="fas fa-eye me-1"></i>Preview
                    </button>
                    <button class="btn btn-light me-2" onclick="toggleSavePanel()">
                        <i class="fas fa-save me-1"></i>Save Layout
                    </button>
                    <a href="dashboard.php" class="btn btn-outline-light">
                        <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Builder Toolbar -->
    <div class="builder-toolbar">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="d-flex align-items-center">
                        <select class="page-selector" id="pageTypeSelector" onchange="changePage()">
                            <optgroup label="Dashboards">
                                <?php foreach ($availablePages['dashboard'] as $role => $name): ?>
                                <option value="dashboard|<?php echo $role; ?>" <?php echo ($currentPage === 'dashboard' && $currentRole === $role) ? 'selected' : ''; ?>>
                                    <?php echo $name; ?>
                                </option>
                                <?php endforeach; ?>
                            </optgroup>
                            <optgroup label="Forms">
                                <?php foreach ($availablePages['forms'] as $form => $name): ?>
                                <option value="forms|<?php echo $form; ?>" <?php echo ($currentPage === 'forms' && $currentRole === $form) ? 'selected' : ''; ?>>
                                    <?php echo $name; ?>
                                </option>
                                <?php endforeach; ?>
                            </optgroup>
                            <optgroup label="Pages">
                                <?php foreach ($availablePages['pages'] as $page => $name): ?>
                                <option value="pages|<?php echo $page; ?>" <?php echo ($currentPage === 'pages' && $currentRole === $page) ? 'selected' : ''; ?>>
                                    <?php echo $name; ?>
                                </option>
                                <?php endforeach; ?>
                            </optgroup>
                            <optgroup label="Reports">
                                <?php foreach ($availablePages['reports'] as $report => $name): ?>
                                <option value="reports|<?php echo $report; ?>" <?php echo ($currentPage === 'reports' && $currentRole === $report) ? 'selected' : ''; ?>>
                                    <?php echo $name; ?>
                                </option>
                                <?php endforeach; ?>
                            </optgroup>
                        </select>
                        
                        <div class="btn-group me-2">
                            <button class="btn btn-sm btn-outline-primary" onclick="setDevice('desktop')">
                                <i class="fas fa-desktop"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-primary" onclick="setDevice('tablet')">
                                <i class="fas fa-tablet-alt"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-primary" onclick="setDevice('mobile')">
                                <i class="fas fa-mobile-alt"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <div class="btn-group">
                        <button class="btn btn-sm btn-success" onclick="saveLayout()">
                            <i class="fas fa-save me-1"></i>Save
                        </button>
                        <button class="btn btn-sm btn-warning" onclick="loadTemplate()">
                            <i class="fas fa-download me-1"></i>Load Template
                        </button>
                        <button class="btn btn-sm btn-info" onclick="exportLayout()">
                            <i class="fas fa-file-export me-1"></i>Export
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show m-3">
        <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
        <?php echo htmlspecialchars($message); ?>
        <button type="button" class="btn-close" data-mdb-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Builder Container -->
    <div class="builder-container">
        <!-- GrapesJS Editor -->
        <div id="gjs" class="builder-main"></div>
    </div>

    <!-- Save Panel -->
    <div class="save-panel" id="savePanel">
        <div class="save-panel-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Save Layout</h5>
                <button class="btn btn-sm btn-outline-light" onclick="toggleSavePanel()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        <div class="save-panel-body">
            <form method="POST" id="saveForm">
                <input type="hidden" name="page_type" id="savePageType" value="<?php echo $currentPage; ?>">
                <input type="hidden" name="page_role" id="savePageRole" value="<?php echo $currentRole; ?>">
                <input type="hidden" name="layout_data" id="saveLayoutData">
                
                <div class="mb-3">
                    <label class="form-label">Page Information</label>
                    <div class="card">
                        <div class="card-body">
                            <p class="mb-1"><strong>Type:</strong> <span id="displayPageType"><?php echo ucfirst($currentPage); ?></span></p>
                            <p class="mb-0"><strong>Role/Section:</strong> <span id="displayPageRole"><?php echo ucfirst(str_replace('_', ' ', $currentRole)); ?></span></p>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="custom_css" class="form-label">Custom CSS</label>
                    <textarea class="form-control" id="custom_css" name="custom_css" rows="6" placeholder="/* Add your custom CSS here */"><?php echo htmlspecialchars($existingLayout['custom_css'] ?? ''); ?></textarea>
                </div>
                
                <div class="mb-3">
                    <label for="custom_js" class="form-label">Custom JavaScript</label>
                    <textarea class="form-control" id="custom_js" name="custom_js" rows="6" placeholder="// Add your custom JavaScript here"><?php echo htmlspecialchars($existingLayout['custom_js'] ?? ''); ?></textarea>
                </div>
                
                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="apply_to_all">
                        <label class="form-check-label" for="apply_to_all">
                            Apply to all pages of this type
                        </label>
                    </div>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" name="save_layout" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Save Layout
                    </button>
                    <button type="button" class="btn btn-outline-secondary" onclick="resetLayout()">
                        <i class="fas fa-undo me-2"></i>Reset to Default
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Preview Mode -->
    <div class="preview-mode" id="previewMode">
        <div class="preview-header">
            <h5 class="mb-0">Preview Mode</h5>
            <button class="btn btn-sm btn-outline-light" onclick="togglePreview()">
                <i class="fas fa-times me-1"></i>Close Preview
            </button>
        </div>
        <div class="preview-content" id="previewContent">
            <!-- Preview content will be loaded here -->
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.js"></script>
    
    <!-- GrapesJS Scripts -->
    <script src="https://unpkg.com/grapesjs"></script>
    <script src="https://unpkg.com/grapesjs-preset-webpage"></script>
    <script src="https://unpkg.com/grapesjs-blocks-basic"></script>
    <script src="https://unpkg.com/grapesjs-plugin-forms"></script>
    <script src="https://unpkg.com/grapesjs-component-countdown"></script>
    <script src="https://unpkg.com/grapesjs-plugin-export"></script>
    <script src="https://unpkg.com/grapesjs-tabs"></script>
    <script src="https://unpkg.com/grapesjs-custom-code"></script>
    
    <script>
        let editor;
        
        // Initialize GrapesJS
        document.addEventListener('DOMContentLoaded', function() {
            editor = grapesjs.init({
                container: '#gjs',
                height: '100%',
                width: 'auto',
                storageManager: false,
                plugins: [
                    'gjs-blocks-basic',
                    'gjs-plugin-forms',
                    'gjs-component-countdown',
                    'gjs-plugin-export',
                    'gjs-tabs',
                    'gjs-custom-code',
                    'gjs-preset-webpage'
                ],
                pluginsOpts: {
                    'gjs-blocks-basic': { flexGrid: true },
                    'gjs-preset-webpage': {
                        modalImportTitle: 'Import Template',
                        modalImportLabel: '<div style="margin-bottom: 10px; font-size: 13px;">Paste here your HTML/CSS and click Import</div>',
                        modalImportContent: function(editor) {
                            return editor.getHtml() + '<style>' + editor.getCss() + '</style>'
                        }
                    }
                },
                canvas: {
                    styles: [
                        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css',
                        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'
                    ],
                    scripts: [
                        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js'
                    ]
                }
            });

            // Add custom TTS components
            addTTSComponents();
            
            // Load existing layout if available
            <?php if ($existingLayout && isset($existingLayout['layout_data'])): ?>
            editor.setComponents('<?php echo addslashes($existingLayout['layout_data']); ?>');
            <?php endif; ?>
            
            // Add custom CSS if available
            <?php if ($existingLayout && isset($existingLayout['custom_css'])): ?>
            editor.setStyle('<?php echo addslashes($existingLayout['custom_css']); ?>');
            <?php endif; ?>
        });
        
        // Add TTS-specific components
        function addTTSComponents() {
            const blockManager = editor.BlockManager;
            
            // Dashboard Stats Card
            blockManager.add('tts-stats-card', {
                label: 'Stats Card',
                category: 'TTS Components',
                content: `
                    <div class="card stats-card text-center">
                        <div class="card-body">
                            <i class="fas fa-users fa-2x text-primary mb-2"></i>
                            <h4 class="mb-1">150</h4>
                            <p class="text-muted mb-0">Total Users</p>
                        </div>
                    </div>
                `,
                attributes: { class: 'fa fa-chart-bar' }
            });
            
            // User Profile Card
            blockManager.add('tts-profile-card', {
                label: 'Profile Card',
                category: 'TTS Components',
                content: `
                    <div class="card">
                        <div class="card-body text-center">
                            <img src="https://via.placeholder.com/80" class="rounded-circle mb-3" alt="Profile">
                            <h5 class="mb-1">John Doe</h5>
                            <p class="text-muted">Software Developer</p>
                            <button class="btn btn-primary btn-sm">View Profile</button>
                        </div>
                    </div>
                `,
                attributes: { class: 'fa fa-user' }
            });
            
            // Task List Component
            blockManager.add('tts-task-list', {
                label: 'Task List',
                category: 'TTS Components',
                content: `
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Today's Tasks</h5>
                        </div>
                        <div class="card-body">
                            <div class="task-item d-flex justify-content-between align-items-center mb-2">
                                <span>Complete project documentation</span>
                                <span class="badge bg-warning">In Progress</span>
                            </div>
                            <div class="task-item d-flex justify-content-between align-items-center mb-2">
                                <span>Review code submissions</span>
                                <span class="badge bg-success">Completed</span>
                            </div>
                        </div>
                    </div>
                `,
                attributes: { class: 'fa fa-tasks' }
            });
        }
        
        // Change page function
        function changePage() {
            const selector = document.getElementById('pageTypeSelector');
            const [pageType, pageRole] = selector.value.split('|');
            window.location.href = `?page=${pageType}&role=${pageRole}`;
        }
        
        // Device preview functions
        function setDevice(device) {
            const canvas = editor.Canvas;
            if (device === 'desktop') {
                canvas.setDevice('Desktop');
            } else if (device === 'tablet') {
                canvas.setDevice('Tablet');
            } else if (device === 'mobile') {
                canvas.setDevice('Mobile portrait');
            }
        }
        
        // Save panel functions
        function toggleSavePanel() {
            const panel = document.getElementById('savePanel');
            panel.classList.toggle('active');
        }
        
        // Save layout function
        function saveLayout() {
            const layoutData = editor.getHtml();
            const cssData = editor.getCss();
            
            document.getElementById('saveLayoutData').value = layoutData;
            document.getElementById('custom_css').value += '\n' + cssData;
            
            toggleSavePanel();
        }
        
        // Preview functions
        function togglePreview() {
            const previewMode = document.getElementById('previewMode');
            const previewContent = document.getElementById('previewContent');
            
            if (previewMode.style.display === 'none' || !previewMode.style.display) {
                const html = editor.getHtml();
                const css = editor.getCss();
                
                previewContent.innerHTML = `
                    <style>${css}</style>
                    ${html}
                `;
                previewMode.style.display = 'block';
            } else {
                previewMode.style.display = 'none';
            }
        }
        
        // Template functions
        function loadTemplate() {
            // Implementation for loading predefined templates
            alert('Template loading functionality will be implemented based on your specific needs.');
        }
        
        function exportLayout() {
            const html = editor.getHtml();
            const css = editor.getCss();
            const fullHtml = `
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TTS PMS Layout Export</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>${css}</style>
</head>
<body>
    ${html}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>`;
            
            const blob = new Blob([fullHtml], { type: 'text/html' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'tts-layout-export.html';
            a.click();
            URL.revokeObjectURL(url);
        }
        
        function resetLayout() {
            if (confirm('Are you sure you want to reset the layout to default? This action cannot be undone.')) {
                editor.setComponents('');
                editor.setStyle('');
            }
        }
    </script>
</body>
</html>
