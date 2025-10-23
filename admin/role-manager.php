<?php
/**
 * TTS PMS Super Admin - Role & Permission Builder
 * Create, manage, and assign user roles with capability matrix
 */

require_once '../config/init.php';
require_once '../includes/admin_helpers.php';

session_start();
require_admin();
require_capability('manage_roles');

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
            case 'create_role':
                $roleName = sanitize_input($_POST['role_name']);
                $displayName = sanitize_input($_POST['display_name']);
                $description = sanitize_input($_POST['description']);
                $capabilities = $_POST['capabilities'] ?? [];
                
                // Check if role exists
                $existing = $db->fetchOne("SELECT id FROM tts_roles WHERE role_name = ?", [$roleName]);
                if ($existing) {
                    throw new Exception('Role already exists');
                }
                
                $roleId = $db->insert('tts_roles', [
                    'role_name' => $roleName,
                    'display_name' => $displayName,
                    'description' => $description,
                    'created_by' => $_SESSION['user_id']
                ]);
                
                // Assign capabilities
                foreach ($capabilities as $capId) {
                    $db->insert('tts_role_capability', [
                        'role_id' => $roleId,
                        'capability_id' => (int)$capId,
                        'granted_by' => $_SESSION['user_id']
                    ]);
                }
                
                log_admin_action('role_create', 'role', $roleId, null, [
                    'name' => $roleName,
                    'capabilities' => $capabilities
                ], "Created role: $displayName");
                
                echo json_encode(['success' => true, 'role_id' => $roleId]);
                break;
                
            case 'update_role':
                $roleId = (int)$_POST['role_id'];
                $displayName = sanitize_input($_POST['display_name']);
                $description = sanitize_input($_POST['description']);
                $capabilities = $_POST['capabilities'] ?? [];
                
                // Get current role for audit
                $currentRole = $db->fetchOne("SELECT * FROM tts_roles WHERE id = ?", [$roleId]);
                if (!$currentRole) {
                    throw new Exception('Role not found');
                }
                
                // Prevent editing system roles
                if ($currentRole['is_system'] && !user_can('system_admin')) {
                    throw new Exception('Cannot edit system roles');
                }
                
                $db->update('tts_roles', [
                    'display_name' => $displayName,
                    'description' => $description
                ], 'id = ?', [$roleId]);
                
                // Update capabilities
                $db->query("DELETE FROM tts_role_capability WHERE role_id = ?", [$roleId]);
                foreach ($capabilities as $capId) {
                    $db->insert('tts_role_capability', [
                        'role_id' => $roleId,
                        'capability_id' => (int)$capId,
                        'granted_by' => $_SESSION['user_id']
                    ]);
                }
                
                log_admin_action('role_update', 'role', $roleId, $currentRole, [
                    'display_name' => $displayName,
                    'capabilities' => $capabilities
                ], "Updated role: $displayName");
                
                echo json_encode(['success' => true]);
                break;
                
            case 'delete_role':
                $roleId = (int)$_POST['role_id'];
                
                $role = $db->fetchOne("SELECT * FROM tts_roles WHERE id = ?", [$roleId]);
                if (!$role) {
                    throw new Exception('Role not found');
                }
                
                if ($role['is_system']) {
                    throw new Exception('Cannot delete system roles');
                }
                
                // Check for users with this role
                $userCount = $db->fetchOne("SELECT COUNT(*) as count FROM tts_user_role WHERE role_id = ?", [$roleId]);
                if ($userCount['count'] > 0) {
                    throw new Exception('Cannot delete role with assigned users');
                }
                
                $db->query("DELETE FROM tts_roles WHERE id = ?", [$roleId]);
                
                log_admin_action('role_delete', 'role', $roleId, $role, null, "Deleted role: {$role['display_name']}");
                
                echo json_encode(['success' => true]);
                break;
                
            case 'assign_role':
                $userId = (int)$_POST['user_id'];
                $roleId = (int)$_POST['role_id'];
                
                // Check if assignment exists
                $existing = $db->fetchOne("SELECT id FROM tts_user_role WHERE user_id = ? AND role_id = ?", [$userId, $roleId]);
                if ($existing) {
                    throw new Exception('User already has this role');
                }
                
                $db->insert('tts_user_role', [
                    'user_id' => $userId,
                    'role_id' => $roleId,
                    'assigned_by' => $_SESSION['user_id']
                ]);
                
                log_admin_action('role_assign', 'user_role', "$userId:$roleId", null, [
                    'user_id' => $userId,
                    'role_id' => $roleId
                ], "Assigned role to user");
                
                echo json_encode(['success' => true]);
                break;
                
            case 'remove_role':
                $userId = (int)$_POST['user_id'];
                $roleId = (int)$_POST['role_id'];
                
                $db->query("DELETE FROM tts_user_role WHERE user_id = ? AND role_id = ?", [$userId, $roleId]);
                
                log_admin_action('role_remove', 'user_role', "$userId:$roleId", [
                    'user_id' => $userId,
                    'role_id' => $roleId
                ], null, "Removed role from user");
                
                echo json_encode(['success' => true]);
                break;
                
            default:
                throw new Exception('Invalid action');
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// Get data for display
$roles = $db->fetchAll("SELECT * FROM tts_roles ORDER BY is_system DESC, display_name ASC");
$capabilities = $db->fetchAll("SELECT * FROM tts_capabilities ORDER BY category, display_name");
$users = $db->fetchAll("SELECT id, email, first_name, last_name FROM tts_users ORDER BY first_name, last_name");

// Group capabilities by category
$capsByCategory = [];
foreach ($capabilities as $cap) {
    $capsByCategory[$cap['category']][] = $cap;
}

// Get role capabilities
$roleCaps = [];
foreach ($roles as $role) {
    $caps = $db->fetchAll("
        SELECT c.id 
        FROM tts_role_capability rc 
        JOIN tts_capabilities c ON rc.capability_id = c.id 
        WHERE rc.role_id = ?
    ", [$role['id']]);
    $roleCaps[$role['id']] = array_column($caps, 'id');
}

// Get user roles
$userRoles = [];
$userRoleData = $db->fetchAll("
    SELECT ur.user_id, ur.role_id, r.display_name 
    FROM tts_user_role ur 
    JOIN tts_roles r ON ur.role_id = r.id
    WHERE ur.expires_at IS NULL OR ur.expires_at > NOW()
");
foreach ($userRoleData as $ur) {
    $userRoles[$ur['user_id']][] = $ur;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Role & Permission Manager - TTS PMS Super Admin</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fa; }
        .sidebar { min-height: 100vh; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .sidebar .nav-link { color: rgba(255, 255, 255, 0.8); transition: all 0.3s ease; border-radius: 8px; margin: 2px 0; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { color: white; background-color: rgba(255, 255, 255, 0.1); }
        .capability-matrix { max-height: 400px; overflow-y: auto; }
        .capability-category { background: #f8f9fa; padding: 0.5rem; font-weight: 600; }
        .role-card { border-radius: 15px; border: none; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); margin-bottom: 1rem; }
        .system-role { border-left: 4px solid #28a745; }
        .custom-role { border-left: 4px solid #007bff; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <div class="sidebar p-3">
                    <div class="text-center mb-4">
                        <h4><i class="fas fa-users-cog me-2"></i>Roles</h4>
                        <small>Permission Management</small>
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
                        <a class="nav-link active" href="role-manager.php">
                            <i class="fas fa-users-cog me-2"></i>Role & Permissions
                        </a>
                        <a class="nav-link" href="module-control.php">
                            <i class="fas fa-puzzle-piece me-2"></i>Module Control
                        </a>
                        <a class="nav-link" href="database-manager.php">
                            <i class="fas fa-database me-2"></i>Database Manager
                        </a>
                        <a class="nav-link" href="audit-log.php">
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
                            <h2 class="mb-1">Role & Permission Manager</h2>
                            <p class="text-muted mb-0">Manage user roles and system capabilities</p>
                        </div>
                        <button class="btn btn-primary" onclick="showCreateRoleModal()">
                            <i class="fas fa-plus me-2"></i>Create Role
                        </button>
                    </div>
                    
                    <!-- Tabs -->
                    <ul class="nav nav-tabs mb-4" id="roleManagerTabs">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#roles-tab">
                                <i class="fas fa-user-tag me-2"></i>Roles
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#users-tab">
                                <i class="fas fa-users me-2"></i>User Assignments
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#capabilities-tab">
                                <i class="fas fa-key me-2"></i>Capabilities
                            </a>
                        </li>
                    </ul>
                    
                    <div class="tab-content">
                        <!-- Roles Tab -->
                        <div class="tab-pane fade show active" id="roles-tab">
                            <div class="row">
                                <?php foreach ($roles as $role): ?>
                                <div class="col-lg-6 mb-3">
                                    <div class="card role-card <?php echo $role['is_system'] ? 'system-role' : 'custom-role'; ?>">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h5 class="card-title">
                                                        <?php echo htmlspecialchars($role['display_name']); ?>
                                                        <?php if ($role['is_system']): ?>
                                                        <span class="badge bg-success ms-2">System</span>
                                                        <?php endif; ?>
                                                    </h5>
                                                    <p class="card-text text-muted"><?php echo htmlspecialchars($role['description']); ?></p>
                                                </div>
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li><a class="dropdown-item" href="#" onclick="editRole(<?php echo $role['id']; ?>)">
                                                            <i class="fas fa-edit me-2"></i>Edit
                                                        </a></li>
                                                        <?php if (!$role['is_system']): ?>
                                                        <li><a class="dropdown-item text-danger" href="#" onclick="deleteRole(<?php echo $role['id']; ?>)">
                                                            <i class="fas fa-trash me-2"></i>Delete
                                                        </a></li>
                                                        <?php endif; ?>
                                                    </ul>
                                                </div>
                                            </div>
                                            
                                            <div class="mt-3">
                                                <small class="text-muted">Capabilities:</small>
                                                <div class="mt-1">
                                                    <?php 
                                                    $roleCapabilities = $roleCaps[$role['id']] ?? [];
                                                    $capCount = count($roleCapabilities);
                                                    ?>
                                                    <span class="badge bg-primary"><?php echo $capCount; ?> capabilities</span>
                                                    <button class="btn btn-sm btn-outline-primary ms-2" onclick="viewRoleCapabilities(<?php echo $role['id']; ?>)">
                                                        View Details
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- Users Tab -->
                        <div class="tab-pane fade" id="users-tab">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>User</th>
                                            <th>Email</th>
                                            <th>Assigned Roles</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong>
                                            </td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td>
                                                <?php if (isset($userRoles[$user['id']])): ?>
                                                    <?php foreach ($userRoles[$user['id']] as $ur): ?>
                                                    <span class="badge bg-secondary me-1"><?php echo htmlspecialchars($ur['display_name']); ?></span>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">No roles assigned</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" onclick="manageUserRoles(<?php echo $user['id']; ?>)">
                                                    <i class="fas fa-user-cog me-1"></i>Manage Roles
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Capabilities Tab -->
                        <div class="tab-pane fade" id="capabilities-tab">
                            <?php foreach ($capsByCategory as $category => $caps): ?>
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h5 class="mb-0"><?php echo ucfirst($category); ?> Capabilities</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <?php foreach ($caps as $cap): ?>
                                        <div class="col-md-6 mb-2">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-key text-primary me-2"></i>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($cap['display_name']); ?></strong>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($cap['description']); ?></small>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Create/Edit Role Modal -->
    <div class="modal fade" id="roleModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="roleModalTitle">Create Role</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="roleForm">
                        <input type="hidden" id="roleId" name="role_id">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="roleName" name="role_name" required>
                                    <label>Role Name (slug)</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="displayName" name="display_name" required>
                                    <label>Display Name</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-floating mb-3">
                            <textarea class="form-control" id="description" name="description" style="height: 80px"></textarea>
                            <label>Description</label>
                        </div>
                        
                        <h6>Capabilities</h6>
                        <div class="capability-matrix border rounded">
                            <?php foreach ($capsByCategory as $category => $caps): ?>
                            <div class="capability-category">
                                <i class="fas fa-folder me-2"></i><?php echo ucfirst($category); ?>
                            </div>
                            <?php foreach ($caps as $cap): ?>
                            <div class="form-check p-3 border-bottom">
                                <input class="form-check-input" type="checkbox" name="capabilities[]" 
                                       value="<?php echo $cap['id']; ?>" id="cap_<?php echo $cap['id']; ?>">
                                <label class="form-check-label" for="cap_<?php echo $cap['id']; ?>">
                                    <strong><?php echo htmlspecialchars($cap['display_name']); ?></strong>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($cap['description']); ?></small>
                                </label>
                            </div>
                            <?php endforeach; ?>
                            <?php endforeach; ?>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveRole()">Save Role</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        const roleCaps = <?php echo json_encode($roleCaps); ?>;
        const roles = <?php echo json_encode($roles); ?>;
        
        function showCreateRoleModal() {
            document.getElementById('roleModalTitle').textContent = 'Create Role';
            document.getElementById('roleForm').reset();
            document.getElementById('roleId').value = '';
            document.getElementById('roleName').disabled = false;
            new bootstrap.Modal(document.getElementById('roleModal')).show();
        }
        
        function editRole(roleId) {
            const role = roles.find(r => r.id == roleId);
            if (!role) return;
            
            document.getElementById('roleModalTitle').textContent = 'Edit Role';
            document.getElementById('roleId').value = roleId;
            document.getElementById('roleName').value = role.role_name;
            document.getElementById('roleName').disabled = role.is_system == '1';
            document.getElementById('displayName').value = role.display_name;
            document.getElementById('description').value = role.description || '';
            
            // Check capabilities
            document.querySelectorAll('input[name="capabilities[]"]').forEach(cb => {
                cb.checked = roleCaps[roleId] && roleCaps[roleId].includes(parseInt(cb.value));
            });
            
            new bootstrap.Modal(document.getElementById('roleModal')).show();
        }
        
        function saveRole() {
            const form = document.getElementById('roleForm');
            const formData = new FormData(form);
            const roleId = document.getElementById('roleId').value;
            
            formData.append('action', roleId ? 'update_role' : 'create_role');
            
            fetch('role-manager.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                alert('Error: ' + error.message);
            });
        }
        
        function deleteRole(roleId) {
            if (!confirm('Are you sure you want to delete this role?')) return;
            
            const formData = new FormData();
            formData.append('action', 'delete_role');
            formData.append('role_id', roleId);
            formData.append('csrf_token', '<?php echo generate_csrf_token(); ?>');
            
            fetch('role-manager.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + data.error);
                }
            });
        }
        
        function manageUserRoles(userId) {
            // Implementation for user role management modal
            alert('User role management - to be implemented');
        }
        
        function viewRoleCapabilities(roleId) {
            // Implementation for viewing role capabilities
            alert('Role capabilities viewer - to be implemented');
        }
    </script>
</body>
</html>
