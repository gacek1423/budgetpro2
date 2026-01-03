<?php
// api/install.php
// INSTALATOR STRUKTURY BAZY DANYCH

require_once '../includes/db.php';

try {
    $db = db();
    
    echo "<h1>🛠️ Instalacja BudgetPro...</h1>";

    // Wyłącz sprawdzanie kluczy na czas tworzenia
    $db->exec("SET FOREIGN_KEY_CHECKS = 0");

    // 1. USUWANIE STARYCH TABEL (Jeśli jakieś są)
    $tables = ['family_links', 'debts', 'savings_goals', 'net_worth_history', 'planned_payments', 'bank_connections', 'personal_transactions', 'budgets', 'categories', 'users'];
    foreach ($tables as $t) {
        $db->exec("DROP TABLE IF EXISTS `$t`");
    }
    echo "<p>🗑️ Wyczyszczono stare tabele.</p>";

    // 2. TWORZENIE TABEL (SQL)
    $queries = [
        "CREATE TABLE `users` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `username` varchar(50) NOT NULL,
          `email` varchar(100) NOT NULL,
          `password` varchar(255) NOT NULL,
          `two_factor_secret` varchar(255) DEFAULT NULL,
          `is_2fa_enabled` tinyint(1) DEFAULT 0,
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          PRIMARY KEY (`id`),
          UNIQUE KEY `email` (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        "CREATE TABLE `categories` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `user_id` int(11) NOT NULL,
          `name` varchar(50) NOT NULL,
          `type` enum('income','expense') NOT NULL,
          `color` varchar(7) DEFAULT '#cccccc',
          `icon` varchar(50) DEFAULT 'fa-tag',
          PRIMARY KEY (`id`),
          KEY `user_id` (`user_id`),
          CONSTRAINT `fk_cat_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        "CREATE TABLE `personal_transactions` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `user_id` int(11) NOT NULL,
          `category_id` int(11) DEFAULT NULL,
          `amount` decimal(10,2) NOT NULL,
          `type` enum('income','expense') NOT NULL,
          `description` varchar(255) DEFAULT NULL,
          `transaction_date` date NOT NULL,
          `currency` varchar(3) DEFAULT 'PLN',
          `exchange_rate` decimal(10,4) DEFAULT 1.0000,
          `original_amount` decimal(10,2) DEFAULT NULL,
          `receipt_path` varchar(255) DEFAULT NULL,
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          PRIMARY KEY (`id`),
          KEY `user_id` (`user_id`),
          KEY `category_id` (`category_id`),
          CONSTRAINT `fk_trans_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
          CONSTRAINT `fk_trans_cat` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        "CREATE TABLE `budgets` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `user_id` int(11) NOT NULL,
          `category_id` int(11) NOT NULL,
          `amount_limit` decimal(10,2) NOT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `user_cat_unique` (`user_id`, `category_id`),
          CONSTRAINT `fk_budget_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
          CONSTRAINT `fk_budget_cat` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        "CREATE TABLE `savings_goals` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `user_id` int(11) NOT NULL,
          `name` varchar(100) NOT NULL,
          `target_amount` decimal(15,2) NOT NULL,
          `current_amount` decimal(15,2) DEFAULT 0.00,
          `deadline` date DEFAULT NULL,
          `icon` varchar(50) DEFAULT 'fa-star',
          `color` varchar(20) DEFAULT '#3b82f6',
          `status` enum('active','completed') DEFAULT 'active',
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          PRIMARY KEY (`id`),
          KEY `user_id` (`user_id`),
          CONSTRAINT `fk_goals_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        "CREATE TABLE `debts` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `user_id` int(11) NOT NULL,
          `name` varchar(100) NOT NULL,
          `balance` decimal(15,2) NOT NULL,
          `interest_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
          `min_payment` decimal(15,2) NOT NULL,
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          PRIMARY KEY (`id`),
          KEY `user_id` (`user_id`),
          CONSTRAINT `fk_debts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        "CREATE TABLE `family_links` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `inviter_id` int(11) NOT NULL,
          `invitee_email` varchar(100) NOT NULL,
          `member_id` int(11) DEFAULT NULL,
          `status` enum('pending','accepted') DEFAULT 'pending',
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          PRIMARY KEY (`id`),
          KEY `inviter_id` (`inviter_id`),
          CONSTRAINT `fk_fam_inviter` FOREIGN KEY (`inviter_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        "CREATE TABLE `net_worth_history` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `user_id` int(11) NOT NULL,
          `record_date` date NOT NULL,
          `assets` decimal(15,2) NOT NULL DEFAULT 0.00,
          `liabilities` decimal(15,2) NOT NULL DEFAULT 0.00,
          PRIMARY KEY (`id`),
          KEY `user_id` (`user_id`),
          CONSTRAINT `fk_nw_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        "CREATE TABLE `planned_payments` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `user_id` int(11) NOT NULL,
          `category_id` int(11) DEFAULT NULL,
          `title` varchar(255) NOT NULL,
          `amount` decimal(10,2) NOT NULL,
          `due_date` date NOT NULL,
          `type` enum('income','expense') NOT NULL DEFAULT 'expense',
          `status` enum('pending','paid','overdue') DEFAULT 'pending',
          `is_recurring` tinyint(1) DEFAULT 0,
          `recurrence_interval` enum('monthly','weekly','yearly') DEFAULT NULL,
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          PRIMARY KEY (`id`),
          KEY `user_id` (`user_id`),
          CONSTRAINT `fk_plan_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        
        "CREATE TABLE `bank_connections` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `user_id` int(11) NOT NULL,
          `institution_id` varchar(50) NOT NULL,
          `requisition_id` varchar(100) NOT NULL,
          `account_id` varchar(100) DEFAULT NULL,
          `status` enum('pending','active','expired') DEFAULT 'pending',
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          PRIMARY KEY (`id`),
          KEY `user_id` (`user_id`),
          CONSTRAINT `fk_bank_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
    ];

    foreach ($queries as $sql) {
        $db->exec($sql);
    }
    
    $db->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "<h2 style='color:green'>✅ SUKCES! Struktura bazy została utworzona.</h2>";
    echo "<p>Możesz teraz przejść do Panelu Generatora, aby wypełnić ją danymi.</p>";
    echo "<a href='seed_control_panel.php' style='background: #3b82f6; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Przejdź do Generatora >></a>";

} catch (Exception $e) {
    echo "<h2 style='color:red'>BŁĄD: " . $e->getMessage() . "</h2>";
}
?>