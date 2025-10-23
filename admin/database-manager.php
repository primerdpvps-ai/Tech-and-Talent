<?php
/**
 * TTS PMS Super Admin - Database Manager
 * Browse tts_* tables with pagination, search, and safe editing
 */

require_once '../config/init.php';
require_once '../includes/admin_helpers.php';

session_start();
require_admin();
require_capability('manage_database');

$db = Database::getInstance();

// Safe tables for editing
$editableTables = ['tts_settings', 'tts_module_config', 'tts_roles', 'tts_capabilities', 'tts_users'];

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid CSRF token']);
        exit;
    }
    
    try {
        switch ($_POST['action']) {
            case 'get_table_data':
                $tableName = sanitize_input($_POST['table_name']);
                $page = (int)($_POST['page'] ?? 1);
                $search = sanitize_input($_POST['search'] ?? '');
                $orderBy = sanitize_input($_POST['order_by'] ?? 'id');
                $orderDir = sanitize_input($_POST['order_dir'] ?? 'ASC');
                $perPage = 20;
                
                // Validate table name
                if (!preg_match('/^tts_[a-z_]+$/', $tableName)) {
                    throw new Exception('Invalid table name');
                }
                
                // Get table structure
                $columns = $db->fetchAll("SHOW COLUMNS FROM `$tableName`");
                
                // Build search query
                $whereClause = '';
                $params = [];
                if ($search) {
                    $searchClauses = [];
                    foreach ($columns as $col) {
                        $searchClauses[] = "`{$col['Field']}` LIKE ?";
                        $params[] = "%$search%";
                    }
                    $whereClause = 'WHERE ' . implode(' OR ', $searchClauses);
                }
                
                // Get total count
                $countResult = $db->fetchOne("SELECT COUNT(*) as total FROM `$tableName` $whereClause", $params);
                $total = $countResult['total'];
                
                // Get paginated data
                $offset = ($page - 1) * $perPage;
                $data = $db->fetchAll("
                    SELECT * FROM `$tableName` $whereClause 
                    ORDER BY `$orderBy` $orderDir 
                    LIMIT $perPage OFFSET $offset
                ", $params);
                
                $pagination = get_pagination($total, $page, $perPage);
                
                echo json_encode([
                    'success' => true,
                    'data' => $data,
                    'columns' => $columns,
                    'pagination' => $pagination,
                    'editable' => in_array($tableName, $editableTables)
                ]);
                break;
                
            case 'update_row':
                $tableName = sanitize_input($_POST['table_name']);
                $rowId = sanitize_input($_POST['row_id']);
                $updates = $_POST['updates'] ?? [];
                
                if (!in_array($tableName, $editableTables)) {
                    throw new Exception('Table not editable');
                }
                
                // Get current row for audit
                $currentRow = $db->fetchOne("SELECT * FROM `$tableName` WHERE id = ?", [$rowId]);
                if (!$currentRow) {
                    throw new Exception('Row not found');
                }
                
                // Build update query
                $setParts = [];
                $params = [];
                foreach ($updates as $field => $value) {
                    $setParts[] = "`$field` = ?";
                    $params[] = $value;
                }
                $params[] = $rowId;
                
                $db->query("UPDATE `$tableName` SET " . implode(', ', $setParts) . " WHERE id = ?", $params);
                
                // Log the change
                log_admin_action('database_edit', $tableName, $rowId, $currentRow, $updates, "Updated row in $tableName");
                
                echo json_encode(['success' => true, 'message' => 'Row updated successfully']);
                break;
                
            case 'export_csv':
                $tableName = sanitize_input($_POST['table_name']);
                $search = sanitize_input($_POST['search'] ?? '');
                
                if (!preg_match('/^tts_[a-z_]+$/', $tableName)) {
                    throw new Exception('Invalid table name');
                }
                
                // Get data for export
                $whereClause = '';
                $params = [];
                if ($search) {
                    $columns = $db->fetchAll("SHOW COLUMNS FROM `$tableName`");
                    $searchClauses = [];
                    foreach ($columns as $col) {
                        $searchClauses[] = "`{$col['Field']}` LIKE ?";
                        $params[] = "%$search%";
                    }
                    $whereClause = 'WHERE ' . implode(' OR ', $searchClauses);
                }
                
                $data = $db->fetchAll("SELECT * FROM `$tableName` $whereClause", $params);
                
                // Generate CSV
                $filename = $tableName . '_export_' . date('Y-m-d_H-i-s') . '.csv';
                
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                
                $output = fopen('php://output', 'w');
                
                if (!empty($data)) {
                    // Write headers
                    fputcsv($output, array_keys($data[0]));
                    
                    // Write data
                    foreach ($data as $row) {
                        fputcsv($output, $row);
                    }
                }
                
                fclose($output);
                exit;
                
            default:
                throw new Exception('Invalid action');
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// Get all tts_* tables
$tables = [];
$result = $db->query("SHOW TABLES LIKE 'tts_%'");
while ($row = $result->fetch(PDO::FETCH_NUM)) {
    $tableName = $row[0];
    $countResult = $db->fetchOne("SELECT COUNT(*) as count FROM `$tableName`");
    $statusResult = $db->query("SHOW TABLE STATUS LIKE '$tableName'");
    $status = $statusResult->fetch(PDO::FETCH_ASSOC);
    
    $tables[] = [
        'name' => $tableName,
        'rows' => $countResult['count'],
        'size' => $status['Data_length'] + $status['Index_length'],
        'engine' => $status['Engine'],
        'editable' => in_array($tableName, $editableTables)
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Manager - TTS PMS Super Admin</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fa; }
        .sidebar { min-height: 100vh; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .sidebar .nav-link { color: rgba(255, 255, 255, 0.8); transition: all 0.3s ease; border-radius: 8px; margin: 2px 0; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { color: white; background-color: rgba(255, 255, 255, 0.1); }
        .table-list { max-height: 400px; overflow-y: auto; }
        .table-item { cursor: pointer; transition: all 0.3s ease; }
        .table-item:hover { background-color: #f8f9fa; }
        .table-item.active { background-color: #e3f2fd; border-left: 4px solid #2196f3; }
        .data-table { font-size: 0.9rem; }
        .editable-cell { cursor: pointer; }
        .editable-cell:hover { background-color: #fff3cd; }
        .edit-input { width: 100%; border: none; background: transparent; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <div class="sidebar p-3">
                    <div class="text-center mb-4">
                        <h4><i class="fas fa-database me-2"></i>Database</h4>
                        <small>Table Manager</small>
                    </div>
                    
                    <nav class="nav flex-column">
                        <a class="nav-link" href="system-overview.php">
                            <i class="fas fa-tachometer-alt me-2"></i>System Overview
                        </a>
                        <a class="nav-link" href="page-builder.php">
                            <i class="fas fa-edit me-2"></i>Visual Page Builder
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
                        <a class="nav-link active" href="database-manager.php">
                            <i class="fas fa-database me-2"></i>Database Manager
                        </a>
                        <a class="nav-link" href="audit-log.php">
                            <i class="fas fa-history me-2"></i>Audit Log
                        </a>
                        
                        <hr class="my-3">
                        
                        <!-- Table List -->
                        <div class="mb-3">
                            <h6 class="text-white-50">Tables</h6>
                            <div class="table-list">
                                <?php foreach ($tables as $table): ?>
                                <div class="table-item p-2 rounded small" onclick="loadTable('<?php echo $table['name']; ?>')">
                                    <i class="fas fa-table me-2"></i>
                                    <?php echo $table['name']; ?>
                                    <?php if ($table['editable']): ?>
                                    <i class="fas fa-edit text-warning ms-1" title="Editable"></i>
                                    <?php endif; ?>
                                    <div class="text-white-50 small">
                                        <?php echo number_format($table['rows']); ?> rows
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <a class="nav-link" href="../" target="_blank">
                            <i class="fas fa-external-link-alt me-2"></i>View Live Site
                        </a>
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a>
                    </nav>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="p-4">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2 class="mb-1">Database Manager</h2>
                            <p class="text-muted mb-0" id="tableInfo">Select a table to view its data</p>
                        </div>
                        <div class="d-flex gap-2" id="tableActions" style="display: none;">
                            <input type="text" class="form-control" id="searchInput" placeholder="Search..." style="width: 200px;">
                            <button class="btn btn-outline-primary" onclick="searchTable()">
                                <i class="fas fa-search"></i>
                            </button>
                            <button class="btn btn-success" onclick="exportCSV()">
                                <i class="fas fa-download me-1"></i>Export CSV
                            </button>
                        </div>
                    </div>
                    
                    <!-- Data Table -->
                    <div class="card">
                        <div class="card-body">
                            <div id="tableContainer">
                                <div class="text-center text-muted py-5">
                                    <i class="fas fa-database fa-3x mb-3"></i>
                                    <h5>Select a table from the sidebar</h5>
                                    <p>Choose a table to browse its data and structure</p>
                                </div>
                            </div>
                        </div>
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
                    <h5 class="modal-title">Edit Row</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editForm">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <input type="hidden" id="editTableName" name="table_name">
                        <input type="hidden" id="editRowId" name="row_id">
                        <div id="editFields"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveEdit()">Save Changes</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        let currentTable = '';
        let currentData = [];
        let currentColumns = [];
        let isEditable = false;
        
        function loadTable(tableName) {
            currentTable = tableName;
            
            // Update UI
            document.querySelectorAll('.table-item').forEach(item => item.classList.remove('active'));
            event.target.closest('.table-item').classList.add('active');
            
            document.getElementById('tableInfo').textContent = `Browsing: ${tableName}`;
            document.getElementById('tableActions').style.display = 'flex';
            
            // Load table data
            fetchTableData(tableName);
        }
        
        function fetchTableData(tableName, page = 1, search = '', orderBy = 'id', orderDir = 'ASC') {
            const formData = new FormData();
            formData.append('action', 'get_table_data');
            formData.append('table_name', tableName);
            formData.append('page', page);
            formData.append('search', search);
            formData.append('order_by', orderBy);
            formData.append('order_dir', orderDir);
            formData.append('csrf_token', '<?php echo generate_csrf_token(); ?>');
            
            fetch('database-manager.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    currentData = data.data;
                    currentColumns = data.columns;
                    isEditable = data.editable;
                    renderTable(data.data, data.columns, data.pagination, data.editable);
                } else {
                    showAlert('Error: ' + data.error, 'danger');
                }
            })
            .catch(error => {
                showAlert('Error: ' + error.message, 'danger');
            });
        }
        
        function renderTable(data, columns, pagination, editable) {
            let html = '<div class="table-responsive">';
            html += '<table class="table table-hover data-table">';
            
            // Headers
            html += '<thead class="table-light"><tr>';
            columns.forEach(col => {
                html += `<th>${col.Field}`;
                if (col.Type) html += `<br><small class="text-muted">${col.Type}</small>`;
                html += '</th>';
            });
            if (editable) html += '<th>Actions</th>';
            html += '</tr></thead>';
            
            // Body
            html += '<tbody>';
            data.forEach(row => {
                html += '<tr>';
                columns.forEach(col => {
                    const value = row[col.Field] || '';
                    const cellClass = editable ? 'editable-cell' : '';
                    html += `<td class="${cellClass}">${escapeHtml(String(value))}</td>`;
                });
                if (editable) {
                    html += `<td><button class="btn btn-sm btn-outline-primary" onclick="editRow(${row.id})">
                        <i class="fas fa-edit"></i></button></td>`;
                }
                html += '</tr>';
            });
            html += '</tbody></table>';
            
            // Pagination
            if (pagination.total_pages > 1) {
                html += '<nav><ul class="pagination justify-content-center">';
                if (pagination.has_prev) {
                    html += `<li class="page-item"><a class="page-link" href="#" onclick="changePage(${pagination.prev_page})">Previous</a></li>`;
                }
                for (let i = Math.max(1, pagination.page - 2); i <= Math.min(pagination.total_pages, pagination.page + 2); i++) {
                    const active = i === pagination.page ? 'active' : '';
                    html += `<li class="page-item ${active}"><a class="page-link" href="#" onclick="changePage(${i})">${i}</a></li>`;
                }
                if (pagination.has_next) {
                    html += `<li class="page-item"><a class="page-link" href="#" onclick="changePage(${pagination.next_page})">Next</a></li>`;
                }
                html += '</ul></nav>';
            }
            
            html += '</div>';
            document.getElementById('tableContainer').innerHTML = html;
        }
        
        function searchTable() {
            const search = document.getElementById('searchInput').value;
            fetchTableData(currentTable, 1, search);
        }
        
        function changePage(page) {
            const search = document.getElementById('searchInput').value;
            fetchTableData(currentTable, page, search);
        }
        
        function editRow(rowId) {
            const row = currentData.find(r => r.id == rowId);
            if (!row) return;
            
            document.getElementById('editTableName').value = currentTable;
            document.getElementById('editRowId').value = rowId;
            
            let fieldsHtml = '';
            currentColumns.forEach(col => {
                if (col.Field === 'id') return; // Skip ID field
                
                const value = row[col.Field] || '';
                fieldsHtml += `
                    <div class="mb-3">
                        <label class="form-label">${col.Field}</label>
                        <input type="text" class="form-control" name="updates[${col.Field}]" value="${escapeHtml(String(value))}">
                        <small class="text-muted">${col.Type}</small>
                    </div>
                `;
            });
            
            document.getElementById('editFields').innerHTML = fieldsHtml;
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }
        
        function saveEdit() {
            const form = document.getElementById('editForm');
            const formData = new FormData(form);
            formData.append('action', 'update_row');
            
            fetch('database-manager.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();
                    fetchTableData(currentTable); // Reload data
                    showAlert(data.message, 'success');
                } else {
                    showAlert('Error: ' + data.error, 'danger');
                }
            })
            .catch(error => {
                showAlert('Error: ' + error.message, 'danger');
            });
        }
        
        function exportCSV() {
            if (!currentTable) return;
            
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            
            const fields = {
                'action': 'export_csv',
                'table_name': currentTable,
                'search': document.getElementById('searchInput').value,
                'csrf_token': '<?php echo generate_csrf_token(); ?>'
            };
            
            Object.keys(fields).forEach(key => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = fields[key];
                form.appendChild(input);
            });
            
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
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
        
        // Search on Enter key
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchTable();
            }
        });
    </script>
</body>
</html>
