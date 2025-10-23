<?php
/**
 * TTS PMS Database Configuration
 * Database connection and management class
 */

// Prevent direct access
if (!defined('TTS_PMS_ROOT')) {
    die('Direct access not allowed');
}

class Database {
    private static $instance = null;
    private $connection = null;
    private $host;
    private $username;
    private $password;
    private $database;
    private $port;
    private $charset;
    
    // Connection statistics
    private static $queryCount = 0;
    private static $totalExecutionTime = 0;
    
    /**
     * Private constructor for singleton pattern
     */
    private function __construct() {
        $this->loadConfiguration();
        $this->connect();
    }
    
    /**
     * Get database instance (Singleton pattern)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Load database configuration
     */
    private function loadConfiguration() {
        // cPanel Production Configuration - Updated with correct credentials
        $this->host = 'localhost';
        $this->username = 'prizmaso_admin';
        $this->password = 'mw__2m2;{%Qp-,2S';
        $this->database = 'prizmaso_tts_pms';
        $this->port = 3306;
        $this->charset = 'utf8mb4';
        
        // Log configuration for debugging
        if (APP_DEBUG) {
            log_message('info', 'Database configuration loaded', [
                'host' => $this->host,
                'database' => $this->database,
                'username' => $this->username
            ]);
        }
    }
    
    /**
     * Establish database connection
     */
    private function connect() {
        try {
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->database};charset={$this->charset}";
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset} COLLATE {$this->charset}_unicode_ci"
            ];
            
            $this->connection = new PDO($dsn, $this->username, $this->password, $options);
            
            // Set timezone
            $this->connection->exec("SET time_zone = '" . date('P') . "'");
            
            if (APP_DEBUG) {
                log_message('info', 'Database connected successfully', [
                    'host' => $this->host,
                    'database' => $this->database,
                    'charset' => $this->charset
                ]);
            }
            
        } catch (PDOException $e) {
            $error = 'Database connection failed: ' . $e->getMessage();
            log_message('error', $error);
            
            if (APP_DEBUG) {
                die($error);
            } else {
                die('Database connection failed. Please try again later.');
            }
        }
    }
    
    /**
     * Get PDO connection
     */
    public function getConnection() {
        if ($this->connection === null) {
            $this->connect();
        }
        return $this->connection;
    }
    
    /**
     * Execute a prepared statement
     */
    public function query($sql, $params = []) {
        $startTime = microtime(true);
        
        try {
            $stmt = $this->connection->prepare($sql);
            $result = $stmt->execute($params);
            
            self::$queryCount++;
            self::$totalExecutionTime += (microtime(true) - $startTime);
            
            if (APP_DEBUG) {
                log_message('debug', 'SQL Query executed', [
                    'sql' => $sql,
                    'params' => $params,
                    'execution_time' => round(microtime(true) - $startTime, 4)
                ]);
            }
            
            return $stmt;
            
        } catch (PDOException $e) {
            log_message('error', 'SQL Query failed: ' . $e->getMessage(), [
                'sql' => $sql,
                'params' => $params
            ]);
            throw $e;
        }
    }
    
    /**
     * Fetch single row
     */
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    /**
     * Fetch all rows
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Insert record and return last insert ID
     */
    public function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->query($sql, $data);
        
        return $this->connection->lastInsertId();
    }
    
    /**
     * Update records
     */
    public function update($table, $data, $where, $whereParams = []) {
        $setClause = [];
        foreach (array_keys($data) as $column) {
            $setClause[] = "{$column} = :{$column}";
        }
        $setClause = implode(', ', $setClause);
        
        $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";
        $params = array_merge($data, $whereParams);
        
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    /**
     * Delete records
     */
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    /**
     * Check if record exists
     */
    public function exists($table, $where, $params = []) {
        $sql = "SELECT 1 FROM {$table} WHERE {$where} LIMIT 1";
        $stmt = $this->query($sql, $params);
        return $stmt->fetch() !== false;
    }
    
    /**
     * Count records
     */
    public function count($table, $where = '1', $params = []) {
        $sql = "SELECT COUNT(*) as count FROM {$table} WHERE {$where}";
        $result = $this->fetchOne($sql, $params);
        return (int) $result['count'];
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit() {
        return $this->connection->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->connection->rollback();
    }
    
    /**
     * Execute transaction with callback
     */
    public function transaction($callback) {
        $this->beginTransaction();
        
        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }
    
    /**
     * Get table schema
     */
    public function getTableSchema($table) {
        $sql = "DESCRIBE {$table}";
        return $this->fetchAll($sql);
    }
    
    /**
     * Check if table exists
     */
    public function tableExists($table) {
        $sql = "SHOW TABLES LIKE :table";
        $stmt = $this->query($sql, ['table' => $table]);
        return $stmt->fetch() !== false;
    }
    
    /**
     * Get database size
     */
    public function getDatabaseSize() {
        $sql = "SELECT 
                    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
                FROM information_schema.tables 
                WHERE table_schema = :database";
        
        $result = $this->fetchOne($sql, ['database' => $this->database]);
        return $result['size_mb'] ?? 0;
    }
    
    /**
     * Get connection statistics
     */
    public static function getStats() {
        return [
            'query_count' => self::$queryCount,
            'total_execution_time' => round(self::$totalExecutionTime, 4),
            'average_execution_time' => self::$queryCount > 0 ? 
                round(self::$totalExecutionTime / self::$queryCount, 4) : 0
        ];
    }
    
    /**
     * Test database connection
     */
    public function testConnection() {
        try {
            $this->query("SELECT 1");
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Get server info
     */
    public function getServerInfo() {
        return [
            'version' => $this->connection->getAttribute(PDO::ATTR_SERVER_VERSION),
            'connection_status' => $this->connection->getAttribute(PDO::ATTR_CONNECTION_STATUS),
            'driver_name' => $this->connection->getAttribute(PDO::ATTR_DRIVER_NAME),
            'server_info' => $this->connection->getAttribute(PDO::ATTR_SERVER_INFO)
        ];
    }
    
    /**
     * Backup database
     */
    public function backup($filename = null) {
        if (!$filename) {
            $filename = LOGS_PATH . '/backup_' . date('Y-m-d_H-i-s') . '.sql';
        }
        
        $tables = $this->fetchAll("SHOW TABLES");
        $backup = '';
        
        foreach ($tables as $table) {
            $tableName = array_values($table)[0];
            
            // Get table structure
            $createTable = $this->fetchOne("SHOW CREATE TABLE {$tableName}");
            $backup .= "\n\n-- Table structure for {$tableName}\n";
            $backup .= "DROP TABLE IF EXISTS `{$tableName}`;\n";
            $backup .= $createTable['Create Table'] . ";\n\n";
            
            // Get table data
            $rows = $this->fetchAll("SELECT * FROM {$tableName}");
            if (!empty($rows)) {
                $backup .= "-- Data for table {$tableName}\n";
                foreach ($rows as $row) {
                    $values = array_map(function($value) {
                        return $value === null ? 'NULL' : "'" . addslashes($value) . "'";
                    }, array_values($row));
                    
                    $backup .= "INSERT INTO `{$tableName}` VALUES (" . implode(', ', $values) . ");\n";
                }
                $backup .= "\n";
            }
        }
        
        file_put_contents($filename, $backup);
        return $filename;
    }
    
    /**
     * Prevent cloning
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
    
    /**
     * Close connection on destruct
     */
    public function __destruct() {
        $this->connection = null;
    }
}

// Global database functions for convenience
function db() {
    return Database::getInstance();
}

function db_query($sql, $params = []) {
    return Database::getInstance()->query($sql, $params);
}

function db_fetch_one($sql, $params = []) {
    return Database::getInstance()->fetchOne($sql, $params);
}

function db_fetch_all($sql, $params = []) {
    return Database::getInstance()->fetchAll($sql, $params);
}

function db_insert($table, $data) {
    return Database::getInstance()->insert($table, $data);
}

function db_update($table, $data, $where, $whereParams = []) {
    return Database::getInstance()->update($table, $data, $where, $whereParams);
}

function db_delete($table, $where, $params = []) {
    return Database::getInstance()->delete($table, $where, $params);
}

function db_exists($table, $where, $params = []) {
    return Database::getInstance()->exists($table, $where, $params);
}

function db_count($table, $where = '1', $params = []) {
    return Database::getInstance()->count($table, $where, $params);
}

// Database health check function
function db_health_check() {
    $db = Database::getInstance();
    
    $health = [
        'status' => 'ok',
        'connection' => $db->testConnection(),
        'server_info' => $db->getServerInfo(),
        'database_size' => $db->getDatabaseSize(),
        'stats' => Database::getStats(),
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    if (!$health['connection']) {
        $health['status'] = 'error';
    }
    
    return $health;
}

// Initialize database connection
try {
    Database::getInstance();
} catch (Exception $e) {
    log_message('critical', 'Failed to initialize database: ' . $e->getMessage());
    if (APP_DEBUG) {
        die('Database initialization failed: ' . $e->getMessage());
    }
}

?>
