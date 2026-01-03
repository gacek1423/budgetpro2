<?php
/**
 * BudgetPro Enterprise - Klasa Połączenia z Bazą Danych
 * 
 * Singleton Pattern - zarządza połączeniem PDO z MySQL
 * Wsparcie dla table prefix, logowania zapytań i obsługi błędów
 * @version 2.0 - FIXED
 */

require_once 'config.php';

class Database {
    /**
     * @var PDO|null Instancja połączenia PDO
     */
    private static $instance = null;
    
    /**
     * @var int Licznik zapytań (tylko w trybie debug)
     */
    private static $queryCount = 0;
    
    /**
     * @var array Historia zapytań
     */
    private static $queryLog = [];
    
    /**
     * @var int Maksymalna liczba prób połączenia
     */
    private const MAX_RETRY_ATTEMPTS = 3;
    
    /**
     * @var int Opóźnienie między próbami (mikrosekundy)
     */
    private const RETRY_DELAY_MS = 500000;
    
    /**
     * Prywatny konstruktor - Singleton Pattern
     */
    private function __construct() {}
    
    /**
     * Prywatny klon - Singleton Pattern
     */
    private function __clone() {}
    
    /**
     * Prywatny wakeup - Singleton Pattern
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
    
    /**
     * Pobiera instancję bazy danych (Singleton)
     * @return PDO
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = self::createInstance();
        }
        return self::$instance;
    }
    
    /**
     * Tworzy nową instancję PDO
     * @return PDO
     */
    private static function createInstance() {
        $attempts = 0;
        $lastException = null;
        
        while ($attempts < self::MAX_RETRY_ATTEMPTS) {
            try {
                $options = self::getPDOOptions();
                
                // Wyłącz persistent connections jeśli debug jest włączony
                if (isDebugMode()) {
                    unset($options[PDO::ATTR_PERSISTENT]);
                }
                
                $pdo = new PDO(
                    self::buildDSN(),
                    DB_USERNAME,
                    DB_PASSWORD,
                    $options
                );
                
                // Ustaw statement class TYLKO dla nie-persistent
                if (isDebugMode() && !isset($options[PDO::ATTR_PERSISTENT])) {
                    $pdo->setAttribute(PDO::ATTR_STATEMENT_CLASS, ['LoggingStatement', [$pdo]]);
                }
                
                // Ustawienia MySQL
                $pdo->exec("SET NAMES " . DB_CHARSET);
                $pdo->exec("SET time_zone = '+00:00'");
                $pdo->exec("SET sql_mode = 'STRICT_ALL_TABLES'");
                
                // Test połączenia
                $pdo->query("SELECT 1");
                return $pdo;
                
            } catch (PDOException $e) {
                $lastException = $e;
                $attempts++;
                if ($attempts < self::MAX_RETRY_ATTEMPTS) {
                    usleep(self::RETRY_DELAY_MS * $attempts);
                }
            }
        }
        
        // Logowanie błędu
        if (isDebugMode()) {
            error_log("[DB] Failed to connect: " . $lastException->getMessage());
            throw $lastException;
        } else {
            die("Wystąpił problem z połączeniem z bazą danych.");
        }
    }
    
    /**
     * Buduje DSN
     */
    private static function buildDSN() {
        return "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    }
    
    /**
     * Pobiera opcje PDO
     */
    private static function getPDOOptions() {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
        ];
        
        // Persistent tylko dla PHP 8+ bez debug
        if (PHP_VERSION_ID >= 80000 && !isDebugMode()) {
            $options[PDO::ATTR_PERSISTENT] = true;
        }
        
        return $options;
    }
    
    /**
     * Pobiera instancję PDO
     */
    public function getConnection() {
        return self::getInstance();
    }
    
    /**
     * Wykonuje SELECT (wszystkie wiersze)
     */
    public function select($sql, $params = []) {
        $this->logQuery($sql, $params);
        $stmt = $this->prepareAndExecute($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Wykonuje SELECT (pierwszy wiersz)
     */
    public function selectOne($sql, $params = []) {
        $this->logQuery($sql, $params);
        $stmt = $this->prepareAndExecute($sql, $params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Wykonuje INSERT
     */
    public function insert($sql, $params = []) {
        $this->logQuery($sql, $params);
        $stmt = $this->prepareAndExecute($sql, $params);
        return $this->getConnection()->lastInsertId();
    }
    
    /**
     * Wykonuje UPDATE
     */
    public function update($sql, $params = []) {
        $this->logQuery($sql, $params);
        $stmt = $this->prepareAndExecute($sql, $params);
        return $stmt->rowCount();
    }
    
    /**
     * Wykonuje DELETE
     */
    public function delete($sql, $params = []) {
        $this->logQuery($sql, $params);
        $stmt = $this->prepareAndExecute($sql, $params);
        return $stmt->rowCount();
    }
    
    /**
     * Wykonuje COUNT
     */
    public function count($sql, $params = []) {
        $result = $this->selectOne($sql, $params);
        return (int)reset($result);
    }
    
    /**
     * Rozpoczyna transakcję
     */
    public function beginTransaction() {
        return $this->getConnection()->beginTransaction();
    }
    
    /**
     * Zatwierdza transakcję
     */
    public function commit() {
        return $this->getConnection()->commit();
    }
    
    /**
     * Cofa transakcję
     */
    public function rollback() {
        return $this->getConnection()->rollBack();
    }
    
    /**
     * Wykonuje transakcję z callbackiem
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
     * Przygotowuje i wykonuje zapytanie
     */
    private function prepareAndExecute($sql, $params = []) {
        $pdo = $this->getConnection();
        $stmt = $pdo->prepare($sql);
        
        foreach ($params as $key => $value) {
            $paramKey = is_numeric($key) ? $key + 1 : ':' . $key;
            
            if (is_null($value)) {
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
    
    /**
     * Loguje zapytanie (tryb debug)
     */
    private function logQuery($sql, $params = []) {
        if (!isDebugMode()) return;
        
        self::$queryCount++;
        self::$queryLog[] = [
            'query' => $sql,
            'params' => $params,
            'time' => microtime(true),
            'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []
        ];
    }
    
    /**
     * Pobiera statystyki zapytań
     */
    public static function getQueryStats() {
        return [
            'count' => self::$queryCount,
            'queries' => self::$queryLog,
            'total_time' => array_sum(array_column(self::$queryLog, 'time'))
        ];
    }
    
    /**
     * Pinguje bazę danych
     */
    public function ping() {
        try {
            $this->getConnection()->query("SELECT 1");
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Zamyka połączenie
     */
    public function close() {
        self::$instance = null;
    }
}

// Klasa pomocnicza do logowania (tylko debug)
if (isDebugMode()) {
    class LoggingStatement extends PDOStatement {
        protected function __construct() {}
    }
}

// Funkcja pomocnicza
function db() {
    return Database::getInstance();
}