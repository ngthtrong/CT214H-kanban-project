<?php

declare(strict_types=1);

namespace App\Database;

use PDO;
use PDOException;

/**
 * Database Connection Class
 * Team Kanban - CT214H Final Project
 */
class Connection
{
    private static ?PDO $instance = null;
    
    private string $host;
    private string $port;
    private string $database;
    private string $username;
    private string $password;
    private string $charset;
    
    public function __construct(
        string $host = 'localhost',
        string $port = '3306',
        string $database = 'kanban_db',
        string $username = 'root',
        string $password = '',
        string $charset = 'utf8mb4'
    ) {
        $this->host = $host;
        $this->port = $port;
        $this->database = $database;
        $this->username = $username;
        $this->password = $password;
        $this->charset = $charset;
    }
    
    /**
     * Create connection from environment variables
     */
    public static function fromEnvironment(): self
    {
        return new self(
            getenv('DB_HOST') ?: 'localhost',
            getenv('DB_PORT') ?: '3306',
            getenv('DB_DATABASE') ?: 'kanban_db',
            getenv('DB_USERNAME') ?: 'root',
            getenv('DB_PASSWORD') ?: '',
            getenv('DB_CHARSET') ?: 'utf8mb4'
        );
    }
    
    /**
     * Get PDO instance (singleton)
     */
    public function getPdo(): PDO
    {
        if (self::$instance === null) {
            self::$instance = $this->createConnection();
        }
        
        return self::$instance;
    }
    
    /**
     * Create new PDO connection
     */
    private function createConnection(): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $this->host,
            $this->port,
            $this->database,
            $this->charset
        );
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset} COLLATE {$this->charset}_unicode_ci"
        ];
        
        return new PDO($dsn, $this->username, $this->password, $options);
    }
    
    /**
     * Execute query and return all rows
     */
    public function query(string $sql, array $params = []): array
    {
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Execute query and return first row
     */
    public function queryOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    /**
     * Execute query and return affected rows
     */
    public function execute(string $sql, array $params = []): int
    {
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }
    
    /**
     * Insert record and return last insert ID
     */
    public function insert(string $table, array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute(array_values($data));
        
        return (int)$this->getPdo()->lastInsertId();
    }
    
    /**
     * Update records
     */
    public function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $setParts = array_map(fn($col) => "{$col} = ?", array_keys($data));
        $setClause = implode(', ', $setParts);
        
        $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";
        
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute(array_merge(array_values($data), $whereParams));
        
        return $stmt->rowCount();
    }
    
    /**
     * Delete records
     */
    public function delete(string $table, string $where, array $params = []): int
    {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->rowCount();
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction(): void
    {
        $this->getPdo()->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit(): void
    {
        $this->getPdo()->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollback(): void
    {
        if ($this->getPdo()->inTransaction()) {
            $this->getPdo()->rollBack();
        }
    }
    
    /**
     * Reset singleton instance (for testing)
     */
    public static function resetInstance(): void
    {
        self::$instance = null;
    }
    
    /**
     * Set PDO instance (for testing)
     */
    public static function setInstance(PDO $pdo): void
    {
        self::$instance = $pdo;
    }
}
