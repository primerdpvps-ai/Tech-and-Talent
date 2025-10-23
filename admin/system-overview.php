<?php
/**
 * TTS PMS Super Admin - System Overview Dashboard
 * Master control center for the entire live website
 */

require_once '../config/init.php';

// Start session and check admin access
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// System scanner class
class SystemScanner {
    private $db;
    private $rootPath;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->rootPath = dirname(__DIR__);
    }
    
    public function scanPages() {
        $pages = [];
        $directories = [
            '/' => 'Root Pages',
            '/packages/web/' => 'Web Package Pages',
            '/packages/web/dashboard/' => 'Dashboard Pages',
            '/packages/web/auth/' => 'Authentication Pages',
            '/admin/' => 'Admin Pages'
        ];
        
        foreach ($directories as $dir => $label) {
            $fullPath = $this->rootPath . $dir;
            if (is_dir($fullPath)) {
                $files = $this->scanDirectory($fullPath, ['php', 'html']);
                foreach ($files as $file) {
                    $relativePath = str_replace($this->rootPath, '', $file);
                    $pages[] = [
                        'path' => $relativePath,
                        'name' => basename($file),
                        'category' => $label,
                        'size' => filesize($file),
                        'modified' => filemtime($file),
                        'editable' => $this->isEditable($file)
                    ];
                }
            }
        }
        
        return $pages;
    }
    
    public function scanDatabaseTables() {
        try {
            $tables = [];
            $result = $this->db->query("SHOW TABLES LIKE 'tts_%'");
            
            while ($row = $result->fetch(PDO::FETCH_NUM)) {
                $tableName = $row[0];
                $countResult = $this->db->fetchOne("SELECT COUNT(*) as count FROM `$tableName`");
                $statusResult = $this->db->query("SHOW TABLE STATUS LIKE '$tableName'");
                $status = $statusResult->fetch(PDO::FETCH_ASSOC);
                
                $tables[] = [
                    'name' => $tableName,
                    'rows' => $countResult['count'],
                    'size' => $status['Data_length'] + $status['Index_length'],
                    'engine' => $status['Engine'],
                    'created' => $status['Create_time']
                ];
            }
            
            return $tables;
        } catch (Exception $e) {
            return [];
        }
    }
    
    public function getSystemStats() {
        try {
            $stats = [
                'total_users' => $this->db->fetchOne("SELECT COUNT(*) as count FROM tts_users")['count'],
                'active_users' => $this->db->fetchOne("SELECT COUNT(*) as count FROM tts_users WHERE status = 'ACTIVE'")['count'],
                'pending_users' => $this->db->fetchOne("SELECT COUNT(*) as count FROM tts_users WHERE status = 'PENDING_VERIFICATION'")['count'],
                'total_pages' => count($this->scanPages()),
                'total_tables' => count($this->scanDatabaseTables()),
                'disk_usage' => $this->getDiskUsage(),
                'php_version' => PHP_VERSION,
                'server_info' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
            ];
            
            // Get role distribution
            $roleResult = $this->db->query("SELECT role, COUNT(*) as count FROM tts_users GROUP BY role");
            $stats['role_distribution'] = [];
            while ($row = $roleResult->fetch(PDO::FETCH_ASSOC)) {
                $stats['role_distribution'][$row['role']] = $row['count'];
            }
            
            return $stats;
        } catch (Exception $e) {
            return [];
        }
    }
    
    public function getActiveModules() {
        $modules = [
            'payroll' => $this->checkModule('tts_payslips'),
            'training' => $this->checkModule('tts_training_modules'),
            'leave_management' => $this->checkModule('tts_leave_requests'),
            'evaluations' => $this->checkModule('tts_evaluations'),
            'time_tracking' => $this->checkModule('tts_time_entries'),
            'onboarding' => $this->checkModule('tts_onboarding_tasks'),
            'gigs' => $this->checkModule('tts_gigs'),
            'page_builder' => $this->checkModule('tts_page_layouts')
        ];
        
        return $modules;
    }
    
    private function checkModule($tableName) {
        try {
            $result = $this->db->query("SHOW TABLES LIKE '$tableName'");
            return $result->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function scanDirectory($dir, $extensions = []) {
        $files = [];
        if (is_dir($dir)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $ext = strtolower($file->getExtension());
                    if (empty($extensions) || in_array($ext, $extensions)) {
                        $files[] = $file->getPathname();
                    }
                }
            }
        }
        return $files;
    }
    
    private function isEditable($file) {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        return in_array($ext, ['php', 'html', 'css', 'js']);
    }
    
    private function getDiskUsage() {
        $bytes = 0;
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->rootPath));
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $bytes += $file->getSize();
            }
        }
        return $bytes;
    }
}

$scanner = new SystemScanner();
$pages = $scanner->scanPages();
$tables = $scanner->scanDatabaseTables();
$stats = $scanner->getSystemStats();
$modules = $scanner->getActiveModules();

// Format file sizes
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    for ($i = 0; $bytes > 1024; $i++) {
        $bytes /= 1024;
    }
    return round($bytes, $precision) . ' ' . $units[$i];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Overview - TTS PMS Super Admin</title>
    
    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
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
        
        .stat-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }
        
        .table-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .module-status {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }
        
        .module-active {
            background-color: #28a745;
        }
        
        .module-inactive {
            background-color: #dc3545;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <div class="sidebar p-3">
                    <div class="text-center mb-4">
                        <h4><i class="fas fa-shield-alt me-2"></i>Super Admin</h4>
                        <small>Master Control Center</small>
                    </div>
                    
                    <nav class="nav flex-column">
                        <a class="nav-link active" href="system-overview.php">
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
                            <h2 class="mb-1">System Overview</h2>
                            <p class="text-muted mb-0">Complete control center for pms.prizmasoft.com</p>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-primary" onclick="refreshData()">
                                <i class="fas fa-sync-alt me-1"></i>Refresh
                            </button>
                            <button class="btn btn-primary" onclick="openQuickActions()">
                                <i class="fas fa-bolt me-1"></i>Quick Actions
                            </button>
                        </div>
                    </div>
                    
                    <!-- System Stats -->
                    <div class="row g-4 mb-4">
                        <div class="col-xl-3 col-md-6">
                            <div class="card stat-card">
                                <div class="card-body d-flex align-items-center">
                                    <div class="stat-icon bg-primary me-3">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-1"><?php echo number_format($stats['total_users']); ?></h3>
                                        <p class="text-muted mb-0">Total Users</p>
                                        <small class="text-success"><?php echo $stats['active_users']; ?> active</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-md-6">
                            <div class="card stat-card">
                                <div class="card-body d-flex align-items-center">
                                    <div class="stat-icon bg-success me-3">
                                        <i class="fas fa-file-code"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-1"><?php echo number_format($stats['total_pages']); ?></h3>
                                        <p class="text-muted mb-0">Total Pages</p>
                                        <small class="text-info">Editable via builder</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-md-6">
                            <div class="card stat-card">
                                <div class="card-body d-flex align-items-center">
                                    <div class="stat-icon bg-warning me-3">
                                        <i class="fas fa-database"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-1"><?php echo number_format($stats['total_tables']); ?></h3>
                                        <p class="text-muted mb-0">Database Tables</p>
                                        <small class="text-primary">MariaDB 10.6.23</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-md-6">
                            <div class="card stat-card">
                                <div class="card-body d-flex align-items-center">
                                    <div class="stat-icon bg-info me-3">
                                        <i class="fas fa-hdd"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-1"><?php echo formatBytes($stats['disk_usage']); ?></h3>
                                        <p class="text-muted mb-0">Disk Usage</p>
                                        <small class="text-muted">PHP <?php echo $stats['php_version']; ?></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Charts Row -->
                    <div class="row g-4 mb-4">
                        <div class="col-lg-8">
                            <div class="card table-card">
                                <div class="card-header bg-transparent">
                                    <h5 class="mb-0">User Role Distribution</h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="roleChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-4">
                            <div class="card table-card">
                                <div class="card-header bg-transparent">
                                    <h5 class="mb-0">Active Modules</h5>
                                </div>
                                <div class="card-body">
                                    <?php foreach ($modules as $module => $active): ?>
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <span class="module-status <?php echo $active ? 'module-active' : 'module-inactive'; ?>"></span>
                                            <?php echo ucwords(str_replace('_', ' ', $module)); ?>
                                        </div>
                                        <span class="badge bg-<?php echo $active ? 'success' : 'danger'; ?>">
                                            <?php echo $active ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pages & Database Tables -->
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <div class="card table-card">
                                <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">System Pages</h5>
                                    <button class="btn btn-sm btn-primary" onclick="openPageBuilder()">
                                        <i class="fas fa-edit me-1"></i>Edit Pages
                                    </button>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Page</th>
                                                    <th>Category</th>
                                                    <th>Size</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach (array_slice($pages, 0, 10) as $page): ?>
                                                <tr>
                                                    <td>
                                                        <i class="fas fa-file-code me-2 text-primary"></i>
                                                        <?php echo htmlspecialchars($page['name']); ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-secondary"><?php echo $page['category']; ?></span>
                                                    </td>
                                                    <td><?php echo formatBytes($page['size']); ?></td>
                                                    <td>
                                                        <?php if ($page['editable']): ?>
                                                        <button class="btn btn-sm btn-outline-primary" onclick="editPage('<?php echo htmlspecialchars($page['path']); ?>')">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-6">
                            <div class="card table-card">
                                <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Database Tables</h5>
                                    <button class="btn btn-sm btn-warning" onclick="openDatabaseManager()">
                                        <i class="fas fa-database me-1"></i>Manage DB
                                    </button>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Table</th>
                                                    <th>Rows</th>
                                                    <th>Size</th>
                                                    <th>Engine</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach (array_slice($tables, 0, 10) as $table): ?>
                                                <tr>
                                                    <td>
                                                        <i class="fas fa-table me-2 text-warning"></i>
                                                        <?php echo htmlspecialchars($table['name']); ?>
                                                    </td>
                                                    <td><?php echo number_format($table['rows']); ?></td>
                                                    <td><?php echo formatBytes($table['size']); ?></td>
                                                    <td>
                                                        <span class="badge bg-info"><?php echo $table['engine']; ?></span>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Role Distribution Chart
        const roleCtx = document.getElementById('roleChart').getContext('2d');
        const roleData = <?php echo json_encode($stats['role_distribution']); ?>;
        
        new Chart(roleCtx, {
            type: 'doughnut',
            data: {
                labels: Object.keys(roleData).map(role => role.charAt(0).toUpperCase() + role.slice(1)),
                datasets: [{
                    data: Object.values(roleData),
                    backgroundColor: [
                        '#667eea',
                        '#764ba2',
                        '#f093fb',
                        '#f5576c',
                        '#4facfe',
                        '#00f2fe',
                        '#43e97b'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
        
        // Functions
        function refreshData() {
            location.reload();
        }
        
        function openQuickActions() {
            // Open quick actions modal
            alert('Quick Actions: Page Builder, Settings, User Management');
        }
        
        function openPageBuilder() {
            window.location.href = 'page-builder.php';
        }
        
        function openDatabaseManager() {
            window.location.href = 'database-manager.php';
        }
        
        function editPage(path) {
            window.location.href = `page-builder.php?edit=${encodeURIComponent(path)}`;
        }
        
        // Auto-refresh every 5 minutes
        setInterval(refreshData, 300000);
    </script>
</body>
</html>
