<?php
// budgetpro2/includes/RateLimiter.php

class RateLimiter {
    private int $maxAttempts;
    private int $timeWindow; // w sekundach
    private string $endpoint;

    public function __construct(string $endpoint, int $maxAttempts = 5, int $timeWindow = 900) {
        $this->endpoint = $endpoint;
        $this->maxAttempts = $maxAttempts;
        $this->timeWindow = $timeWindow;
    }

    public function check(string $ip): bool {
        $db = db();
        
        // Czyścimy stare próby
        $this->cleanup($db);
        
        $stmt = $db->prepare("
            SELECT attempts, last_attempt 
            FROM rate_limit 
            WHERE ip_address = ? AND endpoint = ?
        ");
        $stmt->execute([$ip, $this->endpoint]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$record) {
            return true; // Pierwsza próba
        }
        
        // Sprawdź czy okno czasowe minęło
        $lastAttempt = strtotime($record['last_attempt']);
        if (time() - $lastAttempt > $this->timeWindow) {
            $this->reset($ip);
            return true;
        }
        
        // Sprawdź limit prób
        if ($record['attempts'] >= $this->maxAttempts) {
            return false;
        }
        
        return true;
    }

    public function hit(string $ip): void {
        $db = db();
        
        $stmt = $db->prepare("
            INSERT INTO rate_limit (ip_address, endpoint, attempts) 
            VALUES (?, ?, 1)
            ON DUPLICATE KEY UPDATE 
                attempts = attempts + 1,
                last_attempt = NOW()
        ");
        $stmt->execute([$ip, $this->endpoint]);
    }

    public function reset(string $ip): void {
        $db = db();
        $db->prepare("DELETE FROM rate_limit WHERE ip_address = ? AND endpoint = ?")
           ->execute([$ip, $this->endpoint]);
    }

    private function cleanup($db): void {
        $db->prepare("DELETE FROM rate_limit WHERE last_attempt < NOW() - INTERVAL 1 DAY")
           ->execute();
    }

    public function getRemainingTime(string $ip): int {
        $db = db();
        $stmt = $db->prepare("
            SELECT last_attempt 
            FROM rate_limit 
            WHERE ip_address = ? AND endpoint = ?
        ");
        $stmt->execute([$ip, $this->endpoint]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$record) return 0;
        
        $lastAttempt = strtotime($record['last_attempt']);
        $elapsed = time() - $lastAttempt;
        return max(0, $this->timeWindow - $elapsed);
    }
}