<?php
// api/seed_titan.php
// GENERATOR "TITAN" - 10 MILIONÓW REKORDÓW
// Zoptymalizowany pod kątem szybkości zapisu (Bulk Inserts + Transactions)

set_time_limit(0);
ini_set('memory_limit', '2048M'); // 2GB RAM dla pewności
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../includes/functions.php';
$db = db();

// KONFIGURACJA SKALI
$TOTAL_TRANSACTIONS = 10000000; // 10 MILIONÓW
$TOTAL_USERS = 500;             // 500 UŻYTKOWNIKÓW
$BATCH_SIZE_SQL = 5000;         // Ile rekordów w jednym INSERT INTO (dla SQL)
$BATCH_PER_REQUEST = 50000;     // Ile rekordów na jedno odświeżenie strony (dla PHP)

// Pobieranie stanu
$step = isset($_GET['step']) ? $_GET['step'] : 'init';
$processed = isset($_GET['processed']) ? (int)$_GET['processed'] : 0;

?>
<!DOCTYPE html>
<html>
<head>
    <title>Generator TITAN - 10M</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #0f172a; color: #e2e8f0; text-align: center; padding: 50px; }
        .container { max-width: 700px; margin: 0 auto; background: #1e293b; padding: 40px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); border: 1px solid #334155; }
        h1 { color: #38bdf8; margin-top: 0; font-size: 28px; }
        .progress-bg { background: #334155; height: 20px; border-radius: 10px; margin: 30px 0; overflow: hidden; }
        .progress-fill { height: 100%; background: linear-gradient(90deg, #3b82f6, #06b6d4); width: 0%; transition: width 0.5s ease-out; }
        .stat { font-size: 40px; font-weight: bold; color: #f8fafc; margin: 10px 0; }
        .sub-stat { color: #94a3b8; font-size: 14px; }
        .log { text-align: left; background: #0f172a; padding: 15px; border-radius: 8px; font-family: monospace; color: #10b981; font-size: 12px; height: 100px; overflow-y: auto; margin-top: 20px; }
    </style>
</head>
<body>

<div class="container">
    <?php
    if ($step === 'init') {
        // ==========================================================
        // FAZA 1: PRZYGOTOWANIE STRUKTURY I DANYCH PODSTAWOWYCH
        // ==========================================================
        
        $db->exec("SET FOREIGN_KEY_CHECKS = 0");
        $db->exec("SET UNIQUE_CHECKS = 0"); // Przyspiesza inserty
        $db->exec("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO'");

        // 1. Reset Tabel
        $tables = ['family_links', 'debts', 'savings_goals', 'net_worth_history', 'planned_payments', 'bank_connections', 'personal_transactions', 'budgets', 'categories', 'users'];
        foreach ($tables as $t) {
            $db->exec("DROP TABLE IF EXISTS `$t`");
        }

        // 2. Odtwarzanie Struktury (Wersja skrócona dla oszczędności miejsca, ale pełna funkcjonalnie)
        $queries = [
            "CREATE TABLE `users` ( `id` int(11) NOT NULL AUTO_INCREMENT, `username` varchar(50) NOT NULL, `email` varchar(100) NOT NULL, `password` varchar(255) NOT NULL, `two_factor_secret` varchar(255) DEFAULT NULL, `is_2fa_enabled` tinyint(1) DEFAULT 0, `created_at` timestamp NOT NULL DEFAULT current_timestamp(), PRIMARY KEY (`id`), UNIQUE KEY `email` (`email`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE `categories` ( `id` int(11) NOT NULL AUTO_INCREMENT, `user_id` int(11) NOT NULL, `name` varchar(50) NOT NULL, `type` enum('income','expense') NOT NULL, `color` varchar(7) DEFAULT '#cccccc', `icon` varchar(50) DEFAULT 'fa-tag', PRIMARY KEY (`id`), KEY `user_id` (`user_id`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE `personal_transactions` ( `id` int(11) NOT NULL AUTO_INCREMENT, `user_id` int(11) NOT NULL, `category_id` int(11) DEFAULT NULL, `amount` decimal(10,2) NOT NULL, `type` enum('income','expense') NOT NULL, `description` varchar(255) DEFAULT NULL, `transaction_date` date NOT NULL, `currency` varchar(3) DEFAULT 'PLN', `exchange_rate` decimal(10,4) DEFAULT 1.0000, `original_amount` decimal(10,2) DEFAULT NULL, `receipt_path` varchar(255) DEFAULT NULL, `created_at` timestamp NOT NULL DEFAULT current_timestamp(), PRIMARY KEY (`id`), KEY `user_id` (`user_id`), KEY `category_id` (`category_id`), KEY `transaction_date` (`transaction_date`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE `budgets` ( `id` int(11) NOT NULL AUTO_INCREMENT, `user_id` int(11) NOT NULL, `category_id` int(11) NOT NULL, `amount_limit` decimal(10,2) NOT NULL, PRIMARY KEY (`id`), KEY `user_id` (`user_id`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE `savings_goals` ( `id` int(11) NOT NULL AUTO_INCREMENT, `user_id` int(11) NOT NULL, `name` varchar(100) NOT NULL, `target_amount` decimal(15,2) NOT NULL, `current_amount` decimal(15,2) DEFAULT 0.00, `deadline` date DEFAULT NULL, `icon` varchar(50) DEFAULT 'fa-star', `color` varchar(20) DEFAULT '#3b82f6', `status` enum('active','completed') DEFAULT 'active', `created_at` timestamp NOT NULL DEFAULT current_timestamp(), PRIMARY KEY (`id`), KEY `user_id` (`user_id`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE `debts` ( `id` int(11) NOT NULL AUTO_INCREMENT, `user_id` int(11) NOT NULL, `name` varchar(100) NOT NULL, `balance` decimal(15,2) NOT NULL, `interest_rate` decimal(5,2) NOT NULL DEFAULT 0.00, `min_payment` decimal(15,2) NOT NULL, `created_at` timestamp NOT NULL DEFAULT current_timestamp(), PRIMARY KEY (`id`), KEY `user_id` (`user_id`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE `family_links` ( `id` int(11) NOT NULL AUTO_INCREMENT, `inviter_id` int(11) NOT NULL, `invitee_email` varchar(100) NOT NULL, `member_id` int(11) DEFAULT NULL, `status` enum('pending','accepted') DEFAULT 'pending', `created_at` timestamp NOT NULL DEFAULT current_timestamp(), PRIMARY KEY (`id`), KEY `inviter_id` (`inviter_id`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE `net_worth_history` ( `id` int(11) NOT NULL AUTO_INCREMENT, `user_id` int(11) NOT NULL, `record_date` date NOT NULL, `assets` decimal(15,2) NOT NULL DEFAULT 0.00, `liabilities` decimal(15,2) NOT NULL DEFAULT 0.00, PRIMARY KEY (`id`), KEY `user_id` (`user_id`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE `planned_payments` ( `id` int(11) NOT NULL AUTO_INCREMENT, `user_id` int(11) NOT NULL, `category_id` int(11) DEFAULT NULL, `title` varchar(255) NOT NULL, `amount` decimal(10,2) NOT NULL, `due_date` date NOT NULL, `type` enum('income','expense') NOT NULL DEFAULT 'expense', `status` enum('pending','paid','overdue') DEFAULT 'pending', `is_recurring` tinyint(1) DEFAULT 0, `recurrence_interval` enum('monthly','weekly','yearly') DEFAULT NULL, `created_at` timestamp NOT NULL DEFAULT current_timestamp(), PRIMARY KEY (`id`), KEY `user_id` (`user_id`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE `bank_connections` ( `id` int(11) NOT NULL AUTO_INCREMENT, `user_id` int(11) NOT NULL, `institution_id` varchar(50) NOT NULL, `requisition_id` varchar(100) NOT NULL, `account_id` varchar(100) DEFAULT NULL, `status` enum('pending','active','expired') DEFAULT 'pending', `created_at` timestamp NOT NULL DEFAULT current_timestamp(), PRIMARY KEY (`id`), KEY `user_id` (`user_id`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        ];

        foreach($queries as $q) $db->exec($q);

        // 3. Generowanie Użytkowników i Danych Pomocniczych
        echo "<h1>🏗️ Budowanie fundamentów...</h1>";
        
        $passHash = password_hash('1234', PASSWORD_DEFAULT);
        
        // Admin
        $db->prepare("INSERT INTO users (username, email, password, is_2fa_enabled) VALUES (?, ?, ?, 0)")
           ->execute(['Admin', 'admin@demo.com', $passHash]);
        
        // Pozostali userzy (Batch)
        $userValues = [];
        for ($i = 2; $i <= $TOTAL_USERS; $i++) {
            $userValues[] = "('User $i', 'user$i@demo.com', '$passHash', 0, NOW())";
        }
        // Wstawiamy userów w paczkach po 1000
        foreach(array_chunk($userValues, 1000) as $chunk) {
            $db->query("INSERT INTO users (username, email, password, is_2fa_enabled, created_at) VALUES " . implode(',', $chunk));
        }

        // 4. Generowanie: Kategorie, Budżety, Cele, Długi, Historia dla WSZYSTKICH 500
        $catsData = [];
        $budgetData = [];
        $goalsData = [];
        $debtsData = [];
        $netWorthData = [];
        
        $catsTemplate = [
            ['Jedzenie','expense','#ef4444'], ['Dom','expense','#3b82f6'], 
            ['Auto','expense','#f59e0b'], ['Praca','income','#10b981'],
            ['Rozrywka','expense','#8b5cf6'], ['Zdrowie','expense','#ec4899']
        ];

        for ($u = 1; $u <= $TOTAL_USERS; $u++) {
            // Kategorie (ID kategorii dla usera u to: (u-1)*6 + 1 ... +6)
            foreach($catsTemplate as $c) {
                $catsData[] = "($u, '{$c[0]}', '{$c[1]}', '{$c[2]}')";
            }
            
            // Budżety (losowe 3 na usera)
            $budgetData[] = "($u, " . (($u-1)*6 + 1) . ", " . rand(1000, 3000) . ")";
            $budgetData[] = "($u, " . (($u-1)*6 + 2) . ", " . rand(500, 1500) . ")";
            
            // Cele
            $goalsData[] = "($u, 'Wakacje', " . rand(5000, 20000) . ", " . rand(1000, 4000) . ", '" . date('Y-m-d', strtotime('+6 months')) . "', 'fa-plane', '#3b82f6', 'active', NOW())";
            
            // Długi
            $debtsData[] = "($u, 'Karta Kredytowa', " . rand(1000, 5000) . ", 18.5, 200, NOW())";
            
            // Net Worth History (3 miesiące wstecz)
            $netWorthData[] = "($u, '" . date('Y-m-d', strtotime('-1 month')) . "', " . rand(10000, 50000) . ", " . rand(5000, 20000) . ")";
            $netWorthData[] = "($u, '" . date('Y-m-d') . "', " . rand(12000, 55000) . ", " . rand(4000, 18000) . ")";
        }

        // Wykonanie insertów pomocniczych
        foreach(array_chunk($catsData, 2000) as $chunk) $db->query("INSERT INTO categories (user_id, name, type, color) VALUES " . implode(',', $chunk));
        foreach(array_chunk($budgetData, 2000) as $chunk) $db->query("INSERT INTO budgets (user_id, category_id, amount_limit) VALUES " . implode(',', $chunk));
        foreach(array_chunk($goalsData, 2000) as $chunk) $db->query("INSERT INTO savings_goals (user_id, name, target_amount, current_amount, deadline, icon, color, status, created_at) VALUES " . implode(',', $chunk));
        foreach(array_chunk($debtsData, 2000) as $chunk) $db->query("INSERT INTO debts (user_id, name, balance, interest_rate, min_payment, created_at) VALUES " . implode(',', $chunk));
        foreach(array_chunk($netWorthData, 2000) as $chunk) $db->query("INSERT INTO net_worth_history (user_id, record_date, assets, liabilities) VALUES " . implode(',', $chunk));

        echo "<script>window.location.href = '?step=process&processed=0';</script>";
        exit;
    }

    if ($step === 'process') {
        // ==========================================================
        // FAZA 2: GENEROWANIE TRANSAKCJI (THE BIG ONE)
        // ==========================================================
        
        if ($processed >= $TOTAL_TRANSACTIONS) {
            // Włączamy klucze z powrotem
            $db->exec("SET FOREIGN_KEY_CHECKS = 1");
            $db->exec("SET UNIQUE_CHECKS = 1");
            
            echo "<h1>🏆 UKOŃCZONO!</h1>";
            echo "<div class='stat'>10 000 000</div>";
            echo "<p>Baza danych jest pełna. Twój system to teraz potwór.</p>";
            echo "<a href='../pages/dashboard.php' style='display:inline-block; padding:15px 30px; background:#3b82f6; color:white; text-decoration:none; border-radius:10px; font-weight:bold; margin-top:20px;'>Otwórz Dashboard</a>";
            exit;
        }

        // ROZPOCZNIJ TRANSAKCJĘ (Klucz do szybkości)
        $db->beginTransaction();

        $batchValues = [];
        $descList = ['Biedronka', 'Lidl', 'Orlen', 'Uber', 'Netflix', 'Spotify', 'Czynsz', 'Restauracja', 'Apteka', 'Zabka', 'Amazon', 'Allegro', 'Kino', 'Silownia', 'Fryzjer'];
        
        // Generujemy paczkę
        for ($i = 0; $i < $BATCH_PER_REQUEST; $i++) {
            $uid = mt_rand(1, $TOTAL_USERS);
            // Wybieramy losową kategorię użytkownika (uproszczona matematyka: ID kategorii są sekwencyjne)
            // User 1 ma kategorie 1-6, User 2 ma 7-12 itd.
            $catOffset = mt_rand(1, 6);
            $catId = ($uid - 1) * 6 + $catOffset;
            
            $daysBack = mt_rand(0, 1000); // ok. 3 lata wstecz
            $date = date('Y-m-d', strtotime("-$daysBack days"));
            
            $amount = mt_rand(100, 100000) / 100;
            $type = ($catOffset == 4) ? 'income' : 'expense'; // 4. kategoria to 'Praca' (income)
            $desc = $descList[array_rand($descList)];

            $batchValues[] = "($uid, $catId, $amount, '$type', '$desc', '$date', NOW())";

            // Zrzucamy do bazy co BATCH_SIZE_SQL rekordów
            if (count($batchValues) >= $BATCH_SIZE_SQL) {
                $sql = "INSERT INTO personal_transactions (user_id, category_id, amount, type, description, transaction_date, created_at) VALUES " . implode(',', $batchValues);
                $db->query($sql);
                $batchValues = []; // Reset bufora
            }
        }

        // Zrzut resztek
        if (!empty($batchValues)) {
            $sql = "INSERT INTO personal_transactions (user_id, category_id, amount, type, description, transaction_date, created_at) VALUES " . implode(',', $batchValues);
            $db->query($sql);
        }

        $db->commit();

        $newProcessed = $processed + $BATCH_PER_REQUEST;
        $percent = round(($newProcessed / $TOTAL_TRANSACTIONS) * 100, 2);

        echo "<h1>☢️ GENEROWANIE DANYCH</h1>";
        echo "<div class='stat'>" . number_format($newProcessed, 0, ',', ' ') . "</div>";
        echo "<div class='sub-stat'>z " . number_format($TOTAL_TRANSACTIONS, 0, ',', ' ') . " rekordów</div>";
        
        echo "<div class='progress-bg'><div class='progress-fill' style='width: $percent%'></div></div>";
        
        echo "<div class='log'>";
        echo "> Przetworzono paczkę " . number_format($BATCH_PER_REQUEST) . " rekordów.<br>";
        echo "> Commit transakcji zakończony.<br>";
        echo "> Status pamięci: " . round(memory_get_usage() / 1024 / 1024, 2) . " MB<br>";
        echo "> Pozostało: " . number_format($TOTAL_TRANSACTIONS - $newProcessed) . "<br>";
        echo "</div>";

        echo "<script>
            setTimeout(function() {
                window.location.href = '?step=process&processed=$newProcessed';
            }, 100); 
        </script>";
    }
    ?>
</div>

</body>
</html>