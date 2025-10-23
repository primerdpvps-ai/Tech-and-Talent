<?php
/**
 * TTS PMS Super Admin - Audit Log Viewer
 * View system changes with filters and rollback functionality
 */

require_once '../config/init.php';
require_once '../includes/admin_helpers.php';

session_start();
require_admin();
require_capability('view_audit_log');

$db = Database::getInstance();

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
            case 'get_logs':
                $page = (int)($_POST['page'] ?? 1);
                $perPage = 25;
                $filters = $_POST['filters'] ?? [];
                
                // Build WHERE clause
                $whereClauses = [];
                $params = [];
                
                if (!empty($filters['actor'])) {
                    $whereClauses[] = "admin_id = ?";
                    $params[] = (int)$filters['actor'];
                }
                
                if (!empty($filters['action'])) {
                    $whereClauses[] = "action_type = ?";
                    $params[] = $filters['action'];
                }
                
                if (!empty($filters['object_type'])) {
                    $whereClauses[] = "object_type = ?";
                    $params[] = $filters['object_type'];
                }
                
                if (!empty($filters['date_from'])) {
                    $whereClauses[] = "created_at >= ?";
                    $params[] = $filters['date_from'] . ' 00:00:00';
                }
                
                if (!empty($filters['date_to'])) {
                    $whereClauses[] = "created_at <= ?";
                    $params[] = $filters['date_to'] . ' 23:59:59';
                }
                
                $whereClause = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';
                
                // Get total count
                $countResult = $db->fetchOne("SELECT COUNT(*) as total FROM tts_admin_edits $whereClause", $params);
                $total = $countResult['total'];
                
                // Get paginated logs
                $offset = ($page - 1) * $perPage;
                $logs = $db->fetchAll("
                    SELECT ae.*, u.first_name, u.last_name, u.email
                    FROM tts_admin_edits ae
                    LEFT JOIN tts_users u ON ae.admin_id = u.id
                    $whereClause
                    ORDER BY ae.created_at DESC
                    LIMIT $perPage OFFSET $offset
                ", $params);
                
                $pagination = get_pagination($total, $page, $perPage);
                
                echo json_encode([
                    'success' => true,
                    'logs' => $logs,
                    'pagination' => $pagination
                ]);
                break;
                
            case 'rollback_change':
                $logId = (int)$_POST['log_id'];
                
                // Get the log entry
                $log = $db->fetchOne("SELECT * FROM tts_admin_edits WHERE id = ?", [$logId]);
                if (!$log) {
                    throw new Exception('Log entry not found');
                }
                
                // Check if rollback is possible
                if (!$log['before_json'] || empty($log['object_type']) || empty($log['object_id'])) {
                    throw new Exception('This change cannot be rolled back');
                }
                
                $beforeData = json_decode($log['before_json'], true);
                $objectType = $log['object_type'];
                $objectId = $log['object_id'];
                
                // Perform rollback based on object type
                switch ($objectType) {
                    case 'tts_settings':
                        foreach ($beforeData as $key => $value) {
                            $db->query(
                                "UPDATE tts_settings SET setting_value = ? WHERE setting_key = ?",
                                [$value, $key]
                            );
                        }
                        break;
                        
                    case 'module':
                        $db->query(
                            "UPDATE tts_module_config SET is_enabled = ?, config_data = ? WHERE module_name = ?",
                            [$beforeData['enabled'], json_encode($beforeData['config']), $objectId]
                        );
                        break;
                        
                    case 'role':
                        if (isset($beforeData['display_name'])) {
                            $db->update('tts_roles', [
                                'display_name' => $beforeData['display_name'],
                                'description' => $beforeData['description'] ?? ''
                            ], 'id = ?', [$objectId]);
                        }
                        break;
                        
                    case 'page':
                        // Restore page content
                        $pagePath = dirname(__DIR__) . $log['target_path'];
                        if (file_exists($pagePath) && isset($beforeData['content'])) {
                            file_put_contents($pagePath, $beforeData['content']);
                        }
                        break;
                        
                    default:
                        throw new Exception('Rollback not supported for this object type');
                }
                
                // Log the rollback action
                log_admin_action(
                    'revert_change',
                    $objectType,
                    $objectId,
                    json_decode($log['after_json'], true),
                    $beforeData,
                    "Reverted change from log ID: $logId"
                );
                
                echo json_encode(['success' => true, 'message' => 'Change rolled back successfully']);
                break;
                
            case 'export_logs':
                $format = $_POST['format'] ?? 'csv';
                $filters = $_POST['filters'] ?? [];
                
                // Build query with same filters as get_logs
                $whereClauses = [];
                $params = [];
                
                if (!empty($filters['actor'])) {
                    $whereClauses[] = "admin_id = ?";
                    $params[] = (int)$filters['actor'];
                }
                
                if (!empty($filters['action'])) {
                    $whereClauses[] = "action_type = ?";
                    $params[] = $filters['action'];
                }
                
                if (!empty($filters['object_type'])) {
                    $whereClauses[] = "object_type = ?";
                    $params[] = $filters['object_type'];
                }
                
                if (!empty($filters['date_from'])) {
                    $whereClauses[] = "created_at >= ?";
                    $params[] = $filters['date_from'] . ' 00:00:00';
                }
                
                if (!empty($filters['date_to'])) {
                    $whereClauses[] = "created_at <= ?";
                    $params[] = $filters['date_to'] . ' 23:59:59';
                }
                
                $whereClause = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';
                
                $logs = $db->fetchAll("
                    SELECT ae.*, u.first_name, u.last_name, u.email
                    FROM tts_admin_edits ae
                    LEFT JOIN tts_users u ON ae.admin_id = u.id
                    $whereClause
                    ORDER BY ae.created_at DESC
                ", $params);
                
                if ($format === 'json') {
                    header('Content-Type: application/json');
                    header('Content-Disposition: attachment; filename="audit_log_' . date('Y-m-d_H-i-s') . '.json"');
                    echo json_encode($logs, JSON_PRETTY_PRINT);
                } else {
                    header('Content-Type: text/csv');
                    header('Content-Disposition: attachment; filename="audit_log_' . date('Y-m-d_H-i-s') . '.csv"');
                    
                    $output = fopen('php://output', 'w');
                    
                    if (!empty($logs)) {
                        // CSV headers
                        fputcsv($output, [
                            'ID', 'Actor', 'Email', 'Action', 'Object Type', 'Object ID', 
                            'Description', 'IP Address', 'Created At'
                        ]);
                        
                        foreach ($logs as $log) {
                            fputcsv($output, [
                                $log['id'],
                                ($log['first_name'] ?? '') . ' ' . ($log['last_name'] ?? ''),
                                $log['email'] ?? '',
                                $log['action_type'],
                                $log['object_type'] ?? '',
                                $log['object_id'] ?? '',
                                $log['changes'] ?? '',
                                $log['ip_address'] ?? '',
                                $log['created_at']
                            ]);
                        }
                    }
                    
                    fclose($output);
                }
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

// Get filter options
$actors = $db->fetchAll("
    SELECT DISTINCT u.id, u.first_name, u.last_name, u.email
    FROM tts_admin_edits ae
    JOIN tts_users u ON ae.admin_id = u.id
    ORDER BY u.first_name, u.last_name
");

$actionTypes = $db->fetchAll("
    SELECT DISTINCT action_type
    FROM tts_admin_edits
    WHERE action_type IS NOT NULL
    ORDER BY action_type
");

$objectTypes = $db->fetchAll("
    SELECT DISTINCT object_type
    FROM tts_admin_edits
    WHERE object_type IS NOT NULL
    ORDER BY object_type
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Log Viewer - TTS PMS Super Admin</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fa; }
        .sidebar { min-height: 100vh; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .sidebar .nav-link { color: rgba(255, 255, 255, 0.8); transition: all 0.3s ease; border-radius: 8px; margin: 2px 0; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { color: white; background-color: rgba(255, 255, 255, 0.1); }
        .log-table { font-size: 0.9rem; }
        .diff-container { background: #f8f9fa; border-radius: 8px; padding: 1rem; margin: 0.5rem 0; }
        .diff-before { background: #fff3cd; border-left: 4px solid #ffc107; }
        .diff-after { background: #d1ecf1; border-left: 4px solid #17a2b8; }
        .json-viewer { font-family: 'Courier New', monospace; font-size: 0.8rem; white-space: pre-wrap; }
        .filter-card { border-radius: 15px; border: none; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <div class="sidebar p-3">
                    <div class="text-center mb-4">
                        <h4><i class="fas fa-history me-2"></i>Audit Log</h4>
                        <small>System Changes</small>
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
                        <a class="nav-link" href="database-manager.php">
                            <i class="fas fa-database me-2"></i>Database Manager
                        </a>
                        <a class="nav-link active" href="audit-log.php">
                            <i class="fas fa-history me-2"></i>Audit Log
                        </a>
                        
                        <hr class="my-3">
                        
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
                            <h2 class="mb-1">Audit Log Viewer</h2>
                            <p class="text-muted mb-0">Track all system changes and administrative actions</p>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-success" onclick="exportLogs('csv')">
                                <i class="fas fa-file-csv me-1"></i>Export CSV
                            </button>
                            <button class="btn btn-info" onclick="exportLogs('json')">
                                <i class="fas fa-file-code me-1"></i>Export JSON
                            </button>
                        </div>
                    </div>
                    
                    <!-- Filters -->
                    <div class="card filter-card mb-4">
                        <div class="card-body">
                            <h6 class="card-title">Filters</h6>
                            <div class="row">
                                <div class="col-md-3">
                                    <label class="form-label">Actor</label>
                                    <select class="form-select" id="filterActor">
                                        <option value="">All Users</option>
                                        <?php foreach ($actors as $actor): ?>
                                        <option value="<?php echo $actor['id']; ?>">
                                            <?php echo htmlspecialchars($actor['first_name'] . ' ' . $actor['last_name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Action</label>
                                    <select class="form-select" id="filterAction">
                                        <option value="">All Actions</option>
                                        <?php foreach ($actionTypes as $action): ?>
                                        <option value="<?php echo htmlspecialchars($action['action_type']); ?>">
                                            <?php echo htmlspecialchars($action['action_type']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Object Type</label>
                                    <select class="form-select" id="filterObjectType">
                                        <option value="">All Types</option>
                                        <?php foreach ($objectTypes as $type): ?>
                                        <option value="<?php echo htmlspecialchars($type['object_type']); ?>">
                                            <?php echo htmlspecialchars($type['object_type']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">From Date</label>
                                    <input type="date" class="form-control" id="filterDateFrom">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">To Date</label>
                                    <input type="date" class="form-control" id="filterDateTo">
                                </div>
                            </div>
                            <div class="mt-3">
                                <button class="btn btn-primary" onclick="applyFilters()">
                                    <i class="fas fa-filter me-1"></i>Apply Filters
                                </button>
                                <button class="btn btn-outline-secondary" onclick="clearFilters()">
                                    <i class="fas fa-times me-1"></i>Clear
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Log Table -->
                    <div class="card">
                        <div class="card-body">
                            <div id="logContainer">
                                <div class="text-center text-muted py-5">
                                    <i class="fas fa-spinner fa-spin fa-2x mb-3"></i>
                                    <p>Loading audit logs...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Diff Modal -->
    <div class="modal fade" id="diffModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Change Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="diffContent"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-warning" id="rollbackBtn" onclick="rollbackChange()">
                        <i class="fas fa-undo me-1"></i>Rollback Change
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        let currentLogId = null;
        let currentPage = 1;
        
        // Load logs on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadLogs();
        });
        
        function loadLogs(page = 1) {
            currentPage = page;
            
            const filters = {
                actor: document.getElementById('filterActor').value,
                action: document.getElementById('filterAction').value,
                object_type: document.getElementById('filterObjectType').value,
                date_from: document.getElementById('filterDateFrom').value,
                date_to: document.getElementById('filterDateTo').value
            };
            
            const formData = new FormData();
            formData.append('action', 'get_logs');
            formData.append('page', page);
            formData.append('filters', JSON.stringify(filters));
            formData.append('csrf_token', '<?php echo generate_csrf_token(); ?>');
            
            fetch('audit-log.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderLogs(data.logs, data.pagination);
                } else {
                    showAlert('Error: ' + data.error, 'danger');
                }
            })
            .catch(error => {
                showAlert('Error: ' + error.message, 'danger');
            });
        }
        
        function renderLogs(logs, pagination) {
            let html = '<div class="table-responsive">';
            html += '<table class="table table-hover log-table">';
            html += '<thead class="table-light"><tr>';
            html += '<th>ID</th><th>Actor</th><th>Action</th><th>Object</th><th>Description</th><th>Date</th><th>Actions</th>';
            html += '</tr></thead><tbody>';
            
            logs.forEach(log => {
                const actorName = (log.first_name || '') + ' ' + (log.last_name || '');
                const hasChanges = log.before_json && log.after_json;
                const canRollback = hasChanges && ['settings_update', 'module_toggle', 'role_update', 'page_edit'].includes(log.action_type);
                
                html += '<tr>';
                html += `<td>#${log.id}</td>`;
                html += `<td><small>${escapeHtml(actorName)}<br><span class="text-muted">${escapeHtml(log.email || '')}</span></small></td>`;
                html += `<td><span class="badge bg-primary">${escapeHtml(log.action_type)}</span></td>`;
                html += `<td><small>${escapeHtml(log.object_type || '')}<br><span class="text-muted">${escapeHtml(log.object_id || '')}</span></small></td>`;
                html += `<td><small>${escapeHtml(log.changes || '')}</small></td>`;
                html += `<td><small>${new Date(log.created_at).toLocaleString()}</small></td>`;
                html += '<td>';
                if (hasChanges) {
                    html += `<button class="btn btn-sm btn-outline-info me-1" onclick="showDiff(${log.id})">
                        <i class="fas fa-eye"></i></button>`;
                }
                if (canRollback) {
                    html += `<button class="btn btn-sm btn-outline-warning" onclick="showDiff(${log.id}, true)">
                        <i class="fas fa-undo"></i></button>`;
                }
                html += '</td></tr>';
            });
            
            html += '</tbody></table>';
            
            // Pagination
            if (pagination.total_pages > 1) {
                html += '<nav><ul class="pagination justify-content-center">';
                if (pagination.has_prev) {
                    html += `<li class="page-item"><a class="page-link" href="#" onclick="loadLogs(${pagination.prev_page})">Previous</a></li>`;
                }
                for (let i = Math.max(1, pagination.page - 2); i <= Math.min(pagination.total_pages, pagination.page + 2); i++) {
                    const active = i === pagination.page ? 'active' : '';
                    html += `<li class="page-item ${active}"><a class="page-link" href="#" onclick="loadLogs(${i})">${i}</a></li>`;
                }
                if (pagination.has_next) {
                    html += `<li class="page-item"><a class="page-link" href="#" onclick="loadLogs(${pagination.next_page})">Next</a></li>`;
                }
                html += '</ul></nav>';
            }
            
            html += '</div>';
            document.getElementById('logContainer').innerHTML = html;
        }
        
        function showDiff(logId, showRollback = false) {
            currentLogId = logId;
            
            // Find log data
            const formData = new FormData();
            formData.append('action', 'get_logs');
            formData.append('page', 1);
            formData.append('filters', JSON.stringify({}));
            formData.append('csrf_token', '<?php echo generate_csrf_token(); ?>');
            
            fetch('audit-log.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const log = data.logs.find(l => l.id == logId);
                if (!log) return;
                
                let diffHtml = '';
                
                if (log.before_json && log.after_json) {
                    const before = JSON.parse(log.before_json);
                    const after = JSON.parse(log.after_json);
                    
                    diffHtml += '<div class="row">';
                    diffHtml += '<div class="col-md-6">';
                    diffHtml += '<h6><i class="fas fa-arrow-left text-warning me-2"></i>Before</h6>';
                    diffHtml += '<div class="diff-container diff-before">';
                    diffHtml += `<div class="json-viewer">${JSON.stringify(before, null, 2)}</div>`;
                    diffHtml += '</div></div>';
                    
                    diffHtml += '<div class="col-md-6">';
                    diffHtml += '<h6><i class="fas fa-arrow-right text-info me-2"></i>After</h6>';
                    diffHtml += '<div class="diff-container diff-after">';
                    diffHtml += `<div class="json-viewer">${JSON.stringify(after, null, 2)}</div>`;
                    diffHtml += '</div></div>';
                    diffHtml += '</div>';
                } else {
                    diffHtml += '<div class="alert alert-info">No detailed change data available for this log entry.</div>';
                }
                
                document.getElementById('diffContent').innerHTML = diffHtml;
                document.getElementById('rollbackBtn').style.display = showRollback ? 'inline-block' : 'none';
                
                new bootstrap.Modal(document.getElementById('diffModal')).show();
            });
        }
        
        function rollbackChange() {
            if (!currentLogId) return;
            
            if (!confirm('Are you sure you want to rollback this change? This action cannot be undone.')) return;
            
            const formData = new FormData();
            formData.append('action', 'rollback_change');
            formData.append('log_id', currentLogId);
            formData.append('csrf_token', '<?php echo generate_csrf_token(); ?>');
            
            fetch('audit-log.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('diffModal')).hide();
                    loadLogs(currentPage); // Reload current page
                    showAlert(data.message, 'success');
                } else {
                    showAlert('Error: ' + data.error, 'danger');
                }
            })
            .catch(error => {
                showAlert('Error: ' + error.message, 'danger');
            });
        }
        
        function applyFilters() {
            loadLogs(1);
        }
        
        function clearFilters() {
            document.getElementById('filterActor').value = '';
            document.getElementById('filterAction').value = '';
            document.getElementById('filterObjectType').value = '';
            document.getElementById('filterDateFrom').value = '';
            document.getElementById('filterDateTo').value = '';
            loadLogs(1);
        }
        
        function exportLogs(format) {
            const filters = {
                actor: document.getElementById('filterActor').value,
                action: document.getElementById('filterAction').value,
                object_type: document.getElementById('filterObjectType').value,
                date_from: document.getElementById('filterDateFrom').value,
                date_to: document.getElementById('filterDateTo').value
            };
            
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            
            const fields = {
                'action': 'export_logs',
                'format': format,
                'filters': JSON.stringify(filters),
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
    </script>
</body>
</html>
