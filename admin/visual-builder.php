<?php
/**
 * TTS PMS - Visual Builder Interface
 * Drag-and-drop page builder with live preview
 */

require_once '../config/init.php';
require_once '../includes/admin_helpers.php';

session_start();
require_admin();
require_capability('manage_pages');

$db = Database::getInstance();
$pageId = (int)($_GET['page_id'] ?? 0);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visual Builder - TTS PMS</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Inter', sans-serif; margin: 0; }
        .builder-container { display: flex; height: 100vh; }
        .sidebar { width: 300px; background: #f8f9fa; border-right: 1px solid #dee2e6; overflow-y: auto; }
        .canvas-area { flex: 1; display: flex; flex-direction: column; }
        .toolbar { background: white; border-bottom: 1px solid #dee2e6; padding: 1rem; }
        .canvas { flex: 1; background: #f5f5f5; position: relative; overflow: auto; }
        .preview-container { background: white; margin: 2rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); min-height: calc(100vh - 8rem); }
        .element-palette { padding: 1rem; }
        .element-item { display: block; width: 100%; padding: 0.75rem; margin-bottom: 0.5rem; background: white; border: 1px solid #dee2e6; border-radius: 6px; cursor: grab; transition: all 0.3s; }
        .element-item:hover { background: #e3f2fd; transform: translateY(-1px); }
        .drop-zone { min-height: 50px; border: 2px dashed #ccc; border-radius: 6px; display: flex; align-items: center; justify-content: center; margin: 0.5rem 0; transition: all 0.3s; }
        .drop-zone.drag-over { border-color: #007bff; background: #e3f2fd; }
        .editable { position: relative; }
        .editable:hover { outline: 2px solid #007bff; }
        .edit-controls { position: absolute; top: -30px; right: 0; background: #007bff; border-radius: 4px; padding: 0.25rem; }
        .edit-controls button { background: none; border: none; color: white; padding: 0.25rem; }
    </style>
</head>
<body>
    <div class="builder-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="p-3 border-bottom">
                <h5><i class="fas fa-puzzle-piece me-2"></i>Elements</h5>
            </div>
            
            <div class="element-palette">
                <h6>Layout</h6>
                <div class="element-item" draggable="true" data-type="section">
                    <i class="fas fa-square me-2"></i>Section
                </div>
                <div class="element-item" draggable="true" data-type="row">
                    <i class="fas fa-grip-lines me-2"></i>Row
                </div>
                <div class="element-item" draggable="true" data-type="column">
                    <i class="fas fa-columns me-2"></i>Column
                </div>
                
                <h6 class="mt-3">Content</h6>
                <div class="element-item" draggable="true" data-type="heading">
                    <i class="fas fa-heading me-2"></i>Heading
                </div>
                <div class="element-item" draggable="true" data-type="text">
                    <i class="fas fa-paragraph me-2"></i>Text
                </div>
                <div class="element-item" draggable="true" data-type="button">
                    <i class="fas fa-mouse-pointer me-2"></i>Button
                </div>
                <div class="element-item" draggable="true" data-type="image">
                    <i class="fas fa-image me-2"></i>Image
                </div>
                <div class="element-item" draggable="true" data-type="feature">
                    <i class="fas fa-star me-2"></i>Feature Card
                </div>
                <div class="element-item" draggable="true" data-type="testimonial">
                    <i class="fas fa-quote-left me-2"></i>Testimonial
                </div>
                <div class="element-item" draggable="true" data-type="contact_form">
                    <i class="fas fa-wpforms me-2"></i>Contact Form
                </div>
            </div>
        </div>
        
        <!-- Canvas Area -->
        <div class="canvas-area">
            <!-- Toolbar -->
            <div class="toolbar">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0" id="pageTitle">Visual Builder</h5>
                        <small class="text-muted" id="pageSlug"></small>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-secondary" onclick="undo()">
                            <i class="fas fa-undo"></i>
                        </button>
                        <button class="btn btn-outline-secondary" onclick="redo()">
                            <i class="fas fa-redo"></i>
                        </button>
                        <button class="btn btn-success" onclick="saveLayout()">
                            <i class="fas fa-save me-1"></i>Save
                        </button>
                        <button class="btn btn-primary" onclick="previewPage()">
                            <i class="fas fa-eye me-1"></i>Preview
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Canvas -->
            <div class="canvas">
                <div class="preview-container" id="canvas">
                    <div class="drop-zone" id="mainDropZone">
                        <span class="text-muted">Drop elements here to start building</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Element</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="editModalBody">
                    <!-- Dynamic content -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveElementEdit()">Save</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        let currentLayout = { sections: [] };
        let history = [];
        let historyIndex = -1;
        let currentEditElement = null;
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            setupDragAndDrop();
            loadLayout();
        });
        
        function setupDragAndDrop() {
            // Make elements draggable
            document.querySelectorAll('.element-item').forEach(item => {
                item.addEventListener('dragstart', function(e) {
                    e.dataTransfer.setData('text/plain', this.dataset.type);
                });
            });
            
            // Setup drop zones
            setupDropZone(document.getElementById('mainDropZone'));
        }
        
        function setupDropZone(zone) {
            zone.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.classList.add('drag-over');
            });
            
            zone.addEventListener('dragleave', function(e) {
                this.classList.remove('drag-over');
            });
            
            zone.addEventListener('drop', function(e) {
                e.preventDefault();
                this.classList.remove('drag-over');
                
                const elementType = e.dataTransfer.getData('text/plain');
                createElement(elementType, this);
            });
        }
        
        function createElement(type, dropZone) {
            const element = document.createElement('div');
            element.className = 'editable mb-3 p-3 border rounded';
            element.dataset.type = type;
            
            switch (type) {
                case 'section':
                    element.innerHTML = `
                        <div class="section-header bg-light p-2 mb-2 rounded">
                            <strong>Section</strong>
                            <div class="edit-controls">
                                <button onclick="editElement(this.closest('.editable'))"><i class="fas fa-edit"></i></button>
                                <button onclick="deleteElement(this.closest('.editable'))"><i class="fas fa-trash"></i></button>
                            </div>
                        </div>
                        <div class="drop-zone">Drop rows here</div>
                    `;
                    break;
                    
                case 'row':
                    element.innerHTML = `
                        <div class="row-header bg-info text-white p-1 mb-2 rounded">
                            <small>Row</small>
                            <div class="edit-controls">
                                <button onclick="editElement(this.closest('.editable'))"><i class="fas fa-edit"></i></button>
                                <button onclick="deleteElement(this.closest('.editable'))"><i class="fas fa-trash"></i></button>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <div class="drop-zone">Drop columns here</div>
                            </div>
                        </div>
                    `;
                    break;
                    
                case 'column':
                    element.innerHTML = `
                        <div class="col-md-6">
                            <div class="column-header bg-warning p-1 mb-2 rounded">
                                <small>Column</small>
                                <div class="edit-controls">
                                    <button onclick="editElement(this.closest('.editable'))"><i class="fas fa-edit"></i></button>
                                    <button onclick="deleteElement(this.closest('.editable'))"><i class="fas fa-trash"></i></button>
                                </div>
                            </div>
                            <div class="drop-zone">Drop content here</div>
                        </div>
                    `;
                    break;
                    
                case 'heading':
                    element.innerHTML = `
                        <h2 ondblclick="editInline(this)">Your Heading Here</h2>
                        <div class="edit-controls">
                            <button onclick="editElement(this.closest('.editable'))"><i class="fas fa-edit"></i></button>
                            <button onclick="deleteElement(this.closest('.editable'))"><i class="fas fa-trash"></i></button>
                        </div>
                    `;
                    break;
                    
                case 'text':
                    element.innerHTML = `
                        <p ondblclick="editInline(this)">Your text content goes here. Double-click to edit.</p>
                        <div class="edit-controls">
                            <button onclick="editElement(this.closest('.editable'))"><i class="fas fa-edit"></i></button>
                            <button onclick="deleteElement(this.closest('.editable'))"><i class="fas fa-trash"></i></button>
                        </div>
                    `;
                    break;
                    
                case 'button':
                    element.innerHTML = `
                        <a href="#" class="btn btn-primary" ondblclick="editInline(this)">Button Text</a>
                        <div class="edit-controls">
                            <button onclick="editElement(this.closest('.editable'))"><i class="fas fa-edit"></i></button>
                            <button onclick="deleteElement(this.closest('.editable'))"><i class="fas fa-trash"></i></button>
                        </div>
                    `;
                    break;
                    
                case 'image':
                    element.innerHTML = `
                        <img src="https://via.placeholder.com/400x200" class="img-fluid" alt="Placeholder">
                        <div class="edit-controls">
                            <button onclick="editElement(this.closest('.editable'))"><i class="fas fa-edit"></i></button>
                            <button onclick="deleteElement(this.closest('.editable'))"><i class="fas fa-trash"></i></button>
                        </div>
                    `;
                    break;
                    
                case 'feature':
                    element.innerHTML = `
                        <div class="text-center">
                            <i class="fas fa-star fa-3x text-primary mb-3"></i>
                            <h4 ondblclick="editInline(this)">Feature Title</h4>
                            <p ondblclick="editInline(this)">Feature description goes here.</p>
                        </div>
                        <div class="edit-controls">
                            <button onclick="editElement(this.closest('.editable'))"><i class="fas fa-edit"></i></button>
                            <button onclick="deleteElement(this.closest('.editable'))"><i class="fas fa-trash"></i></button>
                        </div>
                    `;
                    break;
                    
                default:
                    element.innerHTML = `
                        <div>New ${type} element</div>
                        <div class="edit-controls">
                            <button onclick="editElement(this.closest('.editable'))"><i class="fas fa-edit"></i></button>
                            <button onclick="deleteElement(this.closest('.editable'))"><i class="fas fa-trash"></i></button>
                        </div>
                    `;
            }
            
            // Replace drop zone with element
            dropZone.parentNode.replaceChild(element, dropZone);
            
            // Add new drop zone after element
            const newDropZone = document.createElement('div');
            newDropZone.className = 'drop-zone';
            newDropZone.innerHTML = '<span class="text-muted">Drop elements here</span>';
            setupDropZone(newDropZone);
            element.parentNode.insertBefore(newDropZone, element.nextSibling);
            
            saveToHistory();
        }
        
        function editInline(element) {
            const originalText = element.textContent;
            const input = document.createElement('input');
            input.type = 'text';
            input.value = originalText;
            input.className = 'form-control';
            
            input.addEventListener('blur', function() {
                element.textContent = this.value;
                element.parentNode.replaceChild(element, this);
                saveToHistory();
            });
            
            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    this.blur();
                }
            });
            
            element.parentNode.replaceChild(input, element);
            input.focus();
            input.select();
        }
        
        function editElement(element) {
            currentEditElement = element;
            const type = element.dataset.type;
            
            let modalContent = '';
            
            switch (type) {
                case 'heading':
                    modalContent = `
                        <div class="mb-3">
                            <label class="form-label">Text</label>
                            <input type="text" class="form-control" id="editText" value="${element.querySelector('h1,h2,h3,h4,h5,h6').textContent}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Size</label>
                            <select class="form-select" id="editSize">
                                <option value="h1">H1</option>
                                <option value="h2" selected>H2</option>
                                <option value="h3">H3</option>
                                <option value="h4">H4</option>
                            </select>
                        </div>
                    `;
                    break;
                    
                case 'button':
                    modalContent = `
                        <div class="mb-3">
                            <label class="form-label">Button Text</label>
                            <input type="text" class="form-control" id="editText" value="${element.querySelector('a').textContent}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Link URL</label>
                            <input type="url" class="form-control" id="editUrl" value="${element.querySelector('a').href}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Style</label>
                            <select class="form-select" id="editStyle">
                                <option value="btn-primary">Primary</option>
                                <option value="btn-secondary">Secondary</option>
                                <option value="btn-success">Success</option>
                                <option value="btn-outline-primary">Outline Primary</option>
                            </select>
                        </div>
                    `;
                    break;
                    
                default:
                    modalContent = '<p>Edit options for this element type are not yet implemented.</p>';
            }
            
            document.getElementById('editModalBody').innerHTML = modalContent;
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }
        
        function saveElementEdit() {
            if (!currentEditElement) return;
            
            const type = currentEditElement.dataset.type;
            
            switch (type) {
                case 'heading':
                    const text = document.getElementById('editText').value;
                    const size = document.getElementById('editSize').value;
                    const heading = currentEditElement.querySelector('h1,h2,h3,h4,h5,h6');
                    heading.outerHTML = `<${size} ondblclick="editInline(this)">${text}</${size}>`;
                    break;
                    
                case 'button':
                    const btnText = document.getElementById('editText').value;
                    const btnUrl = document.getElementById('editUrl').value;
                    const btnStyle = document.getElementById('editStyle').value;
                    const btn = currentEditElement.querySelector('a');
                    btn.textContent = btnText;
                    btn.href = btnUrl;
                    btn.className = `btn ${btnStyle}`;
                    break;
            }
            
            bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();
            saveToHistory();
        }
        
        function deleteElement(element) {
            if (confirm('Delete this element?')) {
                element.remove();
                saveToHistory();
            }
        }
        
        function saveToHistory() {
            const state = document.getElementById('canvas').innerHTML;
            history = history.slice(0, historyIndex + 1);
            history.push(state);
            historyIndex++;
        }
        
        function undo() {
            if (historyIndex > 0) {
                historyIndex--;
                document.getElementById('canvas').innerHTML = history[historyIndex];
                setupAllDropZones();
            }
        }
        
        function redo() {
            if (historyIndex < history.length - 1) {
                historyIndex++;
                document.getElementById('canvas').innerHTML = history[historyIndex];
                setupAllDropZones();
            }
        }
        
        function setupAllDropZones() {
            document.querySelectorAll('.drop-zone').forEach(setupDropZone);
        }
        
        function saveLayout() {
            const layoutData = extractLayoutData();
            
            fetch('api/save-layout.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `page_id=${<?php echo $pageId; ?>}&layout_json=${encodeURIComponent(JSON.stringify(layoutData))}&csrf_token=${getCsrfToken()}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Layout saved successfully!', 'success');
                } else {
                    showAlert('Error: ' + data.error, 'danger');
                }
            })
            .catch(error => {
                showAlert('Error: ' + error.message, 'danger');
            });
        }
        
        function extractLayoutData() {
            // Convert DOM to layout JSON
            return { sections: [] }; // Simplified for now
        }
        
        function loadLayout() {
            if (<?php echo $pageId; ?> > 0) {
                fetch(`api/load-layout.php?page_id=${<?php echo $pageId; ?>}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('pageTitle').textContent = data.page.title;
                        document.getElementById('pageSlug').textContent = data.page.slug;
                        // Load layout data into canvas
                    }
                });
            }
        }
        
        function previewPage() {
            window.open(`../packages/web/src/pages/preview.php?page_id=${<?php echo $pageId; ?>}`, '_blank');
        }
        
        function getCsrfToken() {
            return '<?php echo generate_csrf_token(); ?>';
        }
        
        function showAlert(message, type) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999;';
            alertDiv.innerHTML = `${message}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
            document.body.appendChild(alertDiv);
            
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.parentNode.removeChild(alertDiv);
                }
            }, 5000);
        }
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey || e.metaKey) {
                switch (e.key) {
                    case 's':
                        e.preventDefault();
                        saveLayout();
                        break;
                    case 'z':
                        e.preventDefault();
                        if (e.shiftKey) {
                            redo();
                        } else {
                            undo();
                        }
                        break;
                }
            }
        });
        
        // Initialize history
        saveToHistory();
    </script>
</body>
</html>
