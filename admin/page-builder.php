<?php
/**
 * TTS PMS Super Admin - Visual Page Builder
 * Real-time page editing with live preview and file writing
 */

require_once '../config/init.php';

// Start session and check admin access
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance();

// Handle page editing requests
$editFile = $_GET['edit'] ?? '';
$pageContent = '';
$pageInfo = null;

if ($editFile) {
    $fullPath = dirname(__DIR__) . $editFile;
    if (file_exists($fullPath) && is_readable($fullPath)) {
        $pageContent = file_get_contents($fullPath);
        $pageInfo = [
            'path' => $editFile,
            'name' => basename($editFile),
            'size' => filesize($fullPath),
            'modified' => filemtime($fullPath),
            'writable' => is_writable($fullPath)
        ];
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        switch ($_POST['action']) {
            case 'save_page':
                $filePath = $_POST['file_path'] ?? '';
                $content = $_POST['content'] ?? '';
                $fullPath = dirname(__DIR__) . $filePath;
                
                // Security check
                if (strpos($filePath, '..') !== false || !file_exists($fullPath)) {
                    throw new Exception('Invalid file path');
                }
                
                // Create backup
                $backupData = [
                    'file_path' => $filePath,
                    'original_content' => file_get_contents($fullPath),
                    'new_content' => $content,
                    'admin_id' => $_SESSION['user_id'] ?? 1,
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                $db->insert('tts_page_layouts', $backupData);
                
                // Write new content
                if (file_put_contents($fullPath, $content) !== false) {
                    echo json_encode(['success' => true, 'message' => 'Page saved successfully']);
                } else {
                    throw new Exception('Failed to write file');
                }
                break;
                
            case 'get_pages':
                $pages = scanPagesForBuilder();
                echo json_encode(['success' => true, 'pages' => $pages]);
                break;
                
            case 'create_page':
                $fileName = $_POST['file_name'] ?? '';
                $template = $_POST['template'] ?? 'basic';
                $directory = $_POST['directory'] ?? '/';
                
                $newPagePath = createNewPage($fileName, $template, $directory);
                echo json_encode(['success' => true, 'path' => $newPagePath]);
                break;
                
            default:
                throw new Exception('Invalid action');
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

function scanPagesForBuilder() {
    $rootPath = dirname(__DIR__);
    $pages = [];
    
    $directories = [
        '/' => 'Root',
        '/packages/web/' => 'Web Package',
        '/packages/web/dashboard/' => 'Dashboards',
        '/packages/web/auth/' => 'Authentication',
        '/admin/' => 'Admin Panel'
    ];
    
    foreach ($directories as $dir => $label) {
        $fullPath = $rootPath . $dir;
        if (is_dir($fullPath)) {
            $files = glob($fullPath . '*.{php,html}', GLOB_BRACE);
            foreach ($files as $file) {
                $relativePath = str_replace($rootPath, '', $file);
                $pages[] = [
                    'path' => $relativePath,
                    'name' => basename($file),
                    'category' => $label,
                    'editable' => is_writable($file)
                ];
            }
        }
    }
    
    return $pages;
}

function createNewPage($fileName, $template, $directory) {
    $rootPath = dirname(__DIR__);
    $fullDir = $rootPath . $directory;
    
    if (!is_dir($fullDir)) {
        mkdir($fullDir, 0755, true);
    }
    
    $filePath = $fullDir . '/' . $fileName;
    $templateContent = getPageTemplate($template);
    
    file_put_contents($filePath, $templateContent);
    
    return str_replace($rootPath, '', $filePath);
}

function getPageTemplate($template) {
    $templates = [
        'basic' => '<?php
/**
 * TTS PMS - New Page
 * Created via Super Admin Page Builder
 */

require_once \'../config/init.php\';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Page - TTS PMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>New Page</h1>
        <p>This page was created using the TTS PMS Super Admin Page Builder.</p>
    </div>
</body>
</html>',
        
        'dashboard' => '<?php
/**
 * TTS PMS - Dashboard Page
 * Created via Super Admin Page Builder
 */

require_once \'../../../config/init.php\';

session_start();
if (!isset($_SESSION[\'logged_in\']) || $_SESSION[\'logged_in\'] !== true) {
    header(\'Location: ../auth/sign-in.php\');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - TTS PMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3 bg-primary text-white p-3">
                <h4>Dashboard</h4>
                <nav class="nav flex-column">
                    <a class="nav-link text-white" href="#"><i class="fas fa-home me-2"></i>Home</a>
                </nav>
            </div>
            <div class="col-md-9 p-4">
                <h2>Welcome to Dashboard</h2>
                <p>This dashboard was created using the Super Admin Page Builder.</p>
            </div>
        </div>
    </div>
</body>
</html>'
    ];
    
    return $templates[$template] ?? $templates['basic'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visual Page Builder - TTS PMS Super Admin</title>
    
    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- CodeMirror for code editing -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/theme/monokai.min.css">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
        }
        
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            transition: all 0.3s ease;
            border-radius: 8px;
            margin: 2px 0;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .builder-toolbar {
            background: white;
            border-bottom: 1px solid #dee2e6;
            padding: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .editor-container {
            height: calc(100vh - 200px);
            display: flex;
        }
        
        .code-editor {
            flex: 1;
            border-right: 1px solid #dee2e6;
            display: flex;
        }
        
        .live-preview {
            flex: 1;
            background: white;
        }
        
        .preview-frame {
            width: 100%;
            height: 100%;
            border: none;
        }
        
        .page-list {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .page-item {
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .page-item:hover {
            background-color: #f8f9fa;
        }
        
        .page-item.active {
            background-color: #e3f2fd;
            border-left: 4px solid #2196f3;
        }
        
        .CodeMirror {
            height: 100%;
            font-size: 14px;
        }
        
        .builder-controls {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }
        
        .element-palette {
            background: white;
            border-radius: 10px;
            padding: 1rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
        }
        
        .element-item {
            display: inline-block;
            padding: 0.5rem 1rem;
            margin: 0.25rem;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .element-item:hover {
            background: #e9ecef;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 px-0">
                <div class="sidebar p-3">
                    <div class="text-center mb-4">
                        <h5><i class="fas fa-edit me-2"></i>Page Builder</h5>
                    </div>
                    
                    <nav class="nav flex-column">
                        <a class="nav-link" href="system-overview.php">
                            <i class="fas fa-tachometer-alt me-2"></i>System Overview
                        </a>
                        <a class="nav-link active" href="page-builder.php">
                            <i class="fas fa-edit me-2"></i>Page Manager
                        </a>
                        <a class="nav-link" href="global-settings.php">
                            <i class="fas fa-cogs me-2"></i>Global Settings
                        </a>
                        <a class="nav-link" href="role-manager.php">
                            <i class="fas fa-users-cog me-2"></i>Role & Permissions
                        </a>
                        <a class="nav-link" href="module-control.php">
                            <i class="fas fa-puzzle-piece me-2"></i>Module Control
                        </a>
                        
                        <hr class="my-3">
                        
                        <!-- Page List -->
                        <div class="mb-3">
                            <h6 class="text-white-50">Pages</h6>
                            <div class="page-list" id="pageList">
                                <div class="text-center">
                                    <div class="spinner-border spinner-border-sm text-white" role="status"></div>
                                    <div class="small mt-2">Loading pages...</div>
                                </div>
                            </div>
                        </div>
                        
                        <button class="btn btn-outline-light btn-sm w-100" onclick="createNewPage()">
                            <i class="fas fa-plus me-2"></i>New Page
                        </button>
                    </nav>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 px-0">
                <!-- Toolbar -->
                <div class="builder-toolbar">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">
                                <i class="fas fa-file-code me-2"></i>
                                <span id="currentPageName">Select a page to edit</span>
                            </h5>
                            <small class="text-muted" id="currentPagePath"></small>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-secondary" onclick="togglePreview()" id="previewToggle">
                                <i class="fas fa-eye me-1"></i>Preview
                            </button>
                            <button class="btn btn-success" onclick="savePage()" id="saveBtn" disabled>
                                <i class="fas fa-save me-1"></i>Save Changes
                            </button>
                            <button class="btn btn-primary" onclick="openLivePreview()" id="livePreviewBtn" disabled>
                                <i class="fas fa-external-link-alt me-1"></i>Live Preview
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Element Palette -->
                <div class="element-palette" id="elementPalette" style="display: none;">
                    <h6 class="mb-3">Drag & Drop Elements</h6>
                    <div>
                        <div class="element-item" draggable="true" data-element="heading">
                            <i class="fas fa-heading me-2"></i>Heading
                        </div>
                        <div class="element-item" draggable="true" data-element="paragraph">
                            <i class="fas fa-paragraph me-2"></i>Paragraph
                        </div>
                        <div class="element-item" draggable="true" data-element="button">
                            <i class="fas fa-mouse-pointer me-2"></i>Button
                        </div>
                        <div class="element-item" draggable="true" data-element="image">
                            <i class="fas fa-image me-2"></i>Image
                        </div>
                        <div class="element-item" draggable="true" data-element="card">
                            <i class="fas fa-id-card me-2"></i>Card
                        </div>
                        <div class="element-item" draggable="true" data-element="form">
                            <i class="fas fa-wpforms me-2"></i>Form
                        </div>
                    </div>
                </div>
                
                <!-- Editor Container -->
                <div class="editor-container">
                    <div class="code-editor">
                        <textarea id="codeEditor" style="display: none;"><?php echo htmlspecialchars($pageContent); ?></textarea>
                    </div>
                    <div class="live-preview" id="livePreview" style="display: none;">
                        <iframe class="preview-frame" id="previewFrame" src="about:blank"></iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modals -->
    <!-- New Page Modal -->
    <div class="modal fade" id="newPageModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Page</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="newPageForm">
                        <div class="mb-3">
                            <label for="fileName" class="form-label">File Name</label>
                            <input type="text" class="form-control" id="fileName" placeholder="my-page.php" required>
                        </div>
                        <div class="mb-3">
                            <label for="directory" class="form-label">Directory</label>
                            <select class="form-select" id="directory">
                                <option value="/">Root</option>
                                <option value="/packages/web/">Web Package</option>
                                <option value="/packages/web/dashboard/">Dashboard</option>
                                <option value="/admin/">Admin</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="template" class="form-label">Template</label>
                            <select class="form-select" id="template">
                                <option value="basic">Basic Page</option>
                                <option value="dashboard">Dashboard Page</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitNewPage()">Create Page</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/xml/xml.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/javascript/javascript.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/css/css.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/htmlmixed/htmlmixed.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/php/php.min.js"></script>
    
    <script>
        let editor;
        let currentFile = '';
        let previewVisible = false;
        let hasChanges = false;
        
        // Initialize CodeMirror
        document.addEventListener('DOMContentLoaded', function() {
            editor = CodeMirror.fromTextArea(document.getElementById('codeEditor'), {
                lineNumbers: true,
                mode: 'application/x-httpd-php',
                theme: 'monokai',
                indentUnit: 4,
                lineWrapping: true,
                autoCloseTags: true,
                autoCloseBrackets: true
            });
            
            editor.on('change', function() {
                hasChanges = true;
                document.getElementById('saveBtn').disabled = false;
            });
            
            loadPages();
            
            <?php if ($editFile): ?>
            loadPage('<?php echo htmlspecialchars($editFile); ?>');
            <?php endif; ?>
        });
        
        // Load pages list
        function loadPages() {
            fetch('page-builder.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_pages'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayPages(data.pages);
                }
            })
            .catch(error => {
                console.error('Error loading pages:', error);
            });
        }
        
        // Display pages in sidebar
        function displayPages(pages) {
            const pageList = document.getElementById('pageList');
            pageList.innerHTML = '';
            
            const categories = {};
            pages.forEach(page => {
                if (!categories[page.category]) {
                    categories[page.category] = [];
                }
                categories[page.category].push(page);
            });
            
            Object.keys(categories).forEach(category => {
                const categoryDiv = document.createElement('div');
                categoryDiv.className = 'mb-2';
                categoryDiv.innerHTML = `
                    <div class="small text-white-50 mb-1">${category}</div>
                `;
                
                categories[category].forEach(page => {
                    const pageDiv = document.createElement('div');
                    pageDiv.className = 'page-item p-2 rounded small';
                    pageDiv.innerHTML = `
                        <i class="fas fa-file-code me-2"></i>
                        ${page.name}
                        ${!page.editable ? '<i class="fas fa-lock text-warning ms-1" title="Read-only"></i>' : ''}
                    `;
                    pageDiv.onclick = () => loadPage(page.path);
                    categoryDiv.appendChild(pageDiv);
                });
                
                pageList.appendChild(categoryDiv);
            });
        }
        
        // Load page content
        function loadPage(path) {
            if (hasChanges && !confirm('You have unsaved changes. Continue?')) {
                return;
            }
            
            window.location.href = `page-builder.php?edit=${encodeURIComponent(path)}`;
        }
        
        // Save page
        function savePage() {
            if (!currentFile) return;
            
            const content = editor.getValue();
            
            fetch('page-builder.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=save_page&file_path=${encodeURIComponent(currentFile)}&content=${encodeURIComponent(content)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    hasChanges = false;
                    document.getElementById('saveBtn').disabled = true;
                    showAlert('Page saved successfully!', 'success');
                } else {
                    showAlert('Error saving page: ' + data.error, 'danger');
                }
            })
            .catch(error => {
                showAlert('Error saving page: ' + error.message, 'danger');
            });
        }
        
        // Toggle preview
        function togglePreview() {
            previewVisible = !previewVisible;
            const preview = document.getElementById('livePreview');
            const toggleBtn = document.getElementById('previewToggle');
            
            if (previewVisible) {
                preview.style.display = 'block';
                toggleBtn.innerHTML = '<i class="fas fa-eye-slash me-1"></i>Hide Preview';
                updatePreview();
            } else {
                preview.style.display = 'none';
                toggleBtn.innerHTML = '<i class="fas fa-eye me-1"></i>Preview';
            }
        }
        
        // Update preview
        function updatePreview() {
            if (!previewVisible) return;
            
            const content = editor.getValue();
            const frame = document.getElementById('previewFrame');
            const doc = frame.contentDocument || frame.contentWindow.document;
            
            doc.open();
            doc.write(content);
            doc.close();
        }
        
        // Open live preview
        function openLivePreview() {
            if (currentFile) {
                const url = '../' + currentFile.substring(1); // Remove leading slash
                window.open(url, '_blank');
            }
        }
        
        // Create new page
        function createNewPage() {
            const modal = new bootstrap.Modal(document.getElementById('newPageModal'));
            modal.show();
        }
        
        // Submit new page
        function submitNewPage() {
            const fileName = document.getElementById('fileName').value;
            const directory = document.getElementById('directory').value;
            const template = document.getElementById('template').value;
            
            if (!fileName) {
                showAlert('Please enter a file name', 'warning');
                return;
            }
            
            fetch('page-builder.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=create_page&file_name=${encodeURIComponent(fileName)}&directory=${encodeURIComponent(directory)}&template=${encodeURIComponent(template)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('newPageModal')).hide();
                    loadPages();
                    loadPage(data.path);
                    showAlert('Page created successfully!', 'success');
                } else {
                    showAlert('Error creating page: ' + data.error, 'danger');
                }
            })
            .catch(error => {
                showAlert('Error creating page: ' + error.message, 'danger');
            });
        }
        
        // Show alert
        function showAlert(message, type) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(alertDiv);
            
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.parentNode.removeChild(alertDiv);
                }
            }, 5000);
        }
        
        // Initialize current file
        <?php if ($pageInfo): ?>
        currentFile = '<?php echo htmlspecialchars($pageInfo['path']); ?>';
        document.getElementById('currentPageName').textContent = '<?php echo htmlspecialchars($pageInfo['name']); ?>';
        document.getElementById('currentPagePath').textContent = '<?php echo htmlspecialchars($pageInfo['path']); ?>';
        document.getElementById('saveBtn').disabled = false;
        document.getElementById('livePreviewBtn').disabled = false;
        
        // Mark active page
        setTimeout(() => {
            const pageItems = document.querySelectorAll('.page-item');
            pageItems.forEach(item => {
                if (item.textContent.trim().includes('<?php echo htmlspecialchars($pageInfo['name']); ?>')) {
                    item.classList.add('active');
                }
            });
        }, 1000);
        <?php endif; ?>
        
        // Auto-save every 30 seconds
        setInterval(() => {
            if (hasChanges && currentFile) {
                savePage();
            }
        }, 30000);
        
        // Warn before leaving with unsaved changes
        window.addEventListener('beforeunload', function(e) {
            if (hasChanges) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    </script>
</body>
</html>
