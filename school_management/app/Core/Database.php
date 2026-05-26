<?php
/**
 * ============================================================
 * Database — PDO Singleton Connection
 * ============================================================
 * Thread-safe singleton providing prepared-statement-only
 * database access. Replaces the legacy global $conn (mysqli).
 */

class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;

    /**
     * Private constructor — use Database::getInstance()
     */
    private function __construct()
    {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_NAME,
            DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET,
        ];

        try {
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            if (App::env('APP_ENV') === 'development') {
                die("Database Connection Failed: " . $e->getMessage());
            }
            die("Database Connection Failed. Please contact the administrator.");
        }
    }

    /**
     * Get singleton instance
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get the raw PDO connection
     */
    public function getConnection(): PDO
    {
        return $this->pdo;
    }

    /**
     * Execute a query and return the PDOStatement
     * 
     * @param string $sql    SQL query with ? or :named placeholders
     * @param array  $params Parameters to bind
     * @return PDOStatement
     */
    public function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Fetch a single row
     */
    public function fetch(string $sql, array $params = []): ?array
    {
        $result = $this->query($sql, $params)->fetch();
        return $result ?: null;
    }

    /**
     * Fetch all rows
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * Fetch paginated rows
     * 
     * @return array ['data' => [], 'total' => int, 'pages' => int, 'current' => int]
     */
    public function paginate(string $sql, array $params = [], int $page = 1, int $perPage = 20): array
    {
        // Calculate total rows by wrapping the query
        $countSql = "SELECT COUNT(*) FROM (" . $sql . ") as total_count";
        $totalRecords = (int)$this->fetchColumn($countSql, $params);
        
        $totalPages = $totalRecords > 0 ? (int)ceil($totalRecords / $perPage) : 1;
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;

        $paginatedSql = $sql . " LIMIT " . (int)$perPage . " OFFSET " . (int)$offset;
        $data = $this->fetchAll($paginatedSql, $params);

        return [
            'data'         => $data,
            'total'        => $totalRecords,
            'pages'        => $totalPages,
            'current_page' => $page,
            'per_page'     => $perPage
        ];
    }

    /**
     * Fetch a single column value from the first row
     */
    public function fetchColumn(string $sql, array $params = [])
    {
        return $this->query($sql, $params)->fetchColumn();
    }

    /**
     * Execute an INSERT/UPDATE/DELETE and return affected row count
     */
    public function execute(string $sql, array $params = []): int
    {
        return $this->query($sql, $params)->rowCount();
    }

    /**
     * Insert a row and return the last insert ID
     */
    public function insert(string $table, array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->query($sql, array_values($data));

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Update rows and return affected count
     */
    public function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $setParts = [];
        $values = [];
        foreach ($data as $col => $val) {
            $setParts[] = "{$col} = ?";
            $values[] = $val;
        }

        $sql = "UPDATE {$table} SET " . implode(', ', $setParts) . " WHERE {$where}";
        return $this->execute($sql, array_merge($values, $whereParams));
    }

    /**
     * Delete rows and return affected count
     */
    public function delete(string $table, string $where, array $params = []): int
    {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        return $this->execute($sql, $params);
    }

    /**
     * Check if a record exists
     */
    public function exists(string $table, string $where, array $params = []): bool
    {
        $sql = "SELECT 1 FROM {$table} WHERE {$where} LIMIT 1";
        return $this->fetch($sql, $params) !== null;
    }

    /**
     * Count rows matching condition
     */
    public function count(string $table, string $where = '1=1', array $params = []): int
    {
        $sql = "SELECT COUNT(*) FROM {$table} WHERE {$where}";
        return (int) $this->fetchColumn($sql, $params);
    }

    // ─── Transaction Helpers ───

    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    public function commit(): void
    {
        $this->pdo->commit();
    }

    public function rollback(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    /**
     * Execute a callback within a transaction
     */
    public function transaction(callable $callback)
    {
        $this->beginTransaction();
        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    // Prevent cloning and unserialization
    private function __clone() {}
    public function __wakeup() { throw new \Exception("Cannot unserialize singleton"); }
}
