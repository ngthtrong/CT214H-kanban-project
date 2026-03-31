<?php
/**
 * Database Connection
 * Team Kanban - CT214H Final Project
 */

// Load config if not loaded
if (!defined('DB_HOST')) {
    require_once __DIR__ . '/config.php';
}

/**
 * Get PDO database connection
 * 
 * @return PDO
 * @throws PDOException
 */
function getDbConnection(): PDO
{
    static $pdo = null;
    
    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_PORT,
            DB_DATABASE,
            DB_CHARSET
        );
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];
        
        try {
            $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, $options);
        } catch (PDOException $e) {
            if (APP_DEBUG) {
                throw $e;
            }
            error_log('Database connection failed: ' . $e->getMessage());
            die('Không thể kết nối đến cơ sở dữ liệu. Vui lòng thử lại sau.');
        }
    }
    
    return $pdo;
}

/**
 * Execute a query and return all rows
 */
function dbQuery(string $sql, array $params = []): array
{
    $stmt = getDbConnection()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Execute a query and return first row
 */
function dbQueryOne(string $sql, array $params = []): ?array
{
    $stmt = getDbConnection()->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetch();
    return $result ?: null;
}

/**
 * Execute a query and return affected rows count
 */
function dbExecute(string $sql, array $params = []): int
{
    $stmt = getDbConnection()->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
}

/**
 * Insert a record and return last insert ID
 */
function dbInsert(string $table, array $data): int
{
    $columns = implode(', ', array_keys($data));
    $placeholders = implode(', ', array_fill(0, count($data), '?'));
    
    $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
    
    $stmt = getDbConnection()->prepare($sql);
    $stmt->execute(array_values($data));
    
    return (int)getDbConnection()->lastInsertId();
}

/**
 * Update records
 */
function dbUpdate(string $table, array $data, string $where, array $whereParams = []): int
{
    $setParts = [];
    foreach (array_keys($data) as $column) {
        $setParts[] = "{$column} = ?";
    }
    $setClause = implode(', ', $setParts);
    
    $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";
    
    $stmt = getDbConnection()->prepare($sql);
    $stmt->execute(array_merge(array_values($data), $whereParams));
    
    return $stmt->rowCount();
}

/**
 * Delete records
 */
function dbDelete(string $table, string $where, array $params = []): int
{
    $sql = "DELETE FROM {$table} WHERE {$where}";
    
    $stmt = getDbConnection()->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->rowCount();
}

/**
 * Begin transaction
 */
function dbBeginTransaction(): void
{
    getDbConnection()->beginTransaction();
}

/**
 * Commit transaction
 */
function dbCommit(): void
{
    getDbConnection()->commit();
}

/**
 * Rollback transaction
 */
function dbRollback(): void
{
    if (getDbConnection()->inTransaction()) {
        getDbConnection()->rollBack();
    }
}
