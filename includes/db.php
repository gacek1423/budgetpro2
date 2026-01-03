<?php
// budgetpro2/includes/db.php

require_once __DIR__ . '/../config.php';

class Database {
    private static ?PDO $instance = null;
    private static array $queryLog = [];
    private static int $queryCount = 0;
    
    private const MAX_RETRY_ATTEMPTS = 3;
    private const RETRY_DELAY_MS = 100000;

    public static function getInstance(): PDO {
        if (self::$instance === null) {
            self::$instance = self::createConnection();
        }
        return self::$instance;
    }

    private static function createConnection(): PDO {
        $lastException = null;
        $attempts = 0;
        
        while ($attempts < self::MAX_RETRY_ATTEMPTS) {
            try {
                $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_STRINGIFY_FETCHES => false,
                ];

                $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
                $pdo->exec("SET NAMES " . DB_CHARSET);
                $pdo->exec("SET time_zone = '+00:00'");
                $pdo->exec("SET sql_mode = 'STRICT_ALL_TABLES'");
                
                return $pdo;
                
            } catch (PDOException $e) {
                $lastException = $e;
                $attempts++;
                if ($attempts < self::MAX_RETRY_ATTEMPTS) {
                    usleep(self::RETRY_DELAY_MS * $attempts);
                }
            }
        }

        if (DEBUG_MODE) {
            error_log("[DB] Failed to connect: " . $lastException->getMessage());
            throw $lastException;
        } else {
            die("Błąd połączenia z bazą danych. Spróbuj ponownie później.");
        }
    }

    public function getConnection(): PDO {
        return self::getInstance();
    }

    public function select(string $sql, array $params = []): array {
        $stmt = $this->prepareAndExecute($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function selectOne(string $sql, array $params = []): ?array {
        $stmt = $this->prepareAndExecute($sql, $params);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function insert(string $sql, array $params = []): int {
        $stmt = $this->prepareAndExecute($sql, $params);
        return (int)$this->getConnection()->lastInsertId();
    }

    public function update(string $sql, array $params = []): int {
        $stmt = $this->prepareAndExecute($sql, $params);
        return $stmt->rowCount();
    }

    public function delete(string $sql, array $params = []): int {
        $stmt = $this->prepareAndExecute($sql, $params);
        return $stmt->rowCount();
    }

    public function count(string $sql, array $params = []): int {
        $result = $this->selectOne($sql, $params);
        return (int)reset($result);
    }

    public function beginTransaction(): bool {
        return $this->getConnection()->beginTransaction();
    }

    public function commit(): bool {
        return $this->getConnection()->commit();
    }

    public function rollback(): bool {
        return $this->getConnection()->rollBack();
    }

    public function transaction(callable $callback) {
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

    private function prepareAndExecute(string $sql, array $params): PDOStatement {
        $pdo = $this->getConnection();
        $stmt = $pdo->prepare($sql);
        
        foreach ($params as $key => $value) {
            $paramKey = is_numeric($key) ? $key + 1 : ':' . $key;
            
            if ($value === null) {
                $stmt->bindValue($paramKey, $value, PDO::PARAM_NULL);
            } elseif (is_int($value)) {
                $stmt->bindValue($paramKey, $value, PDO::PARAM_INT);
            } elseif (is_bool($value)) {
                $stmt->bindValue($paramKey, $value, PDO::PARAM_BOOL);
            } else {
                $stmt->bindValue($paramKey, $value, PDO::PARAM_STR);
            }
        }
        
        $stmt->execute();
        return $stmt;
    }
}

// Helper function
function db(): Database {
    static $db;
    if (!isset($db)) {
        $db = new Database();
    }
    return $db;
}