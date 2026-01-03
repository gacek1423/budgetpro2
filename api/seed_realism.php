<?php
// api/seed_realism.php
// "THE SIMULATOR" - Generator Hiper-Realistycznych Danych Finansowych

set_time_limit(0);
ini_set('memory_limit', '1024M');
require_once '../includes/functions.php';
$db = db();

// KONFIGURACJA
$TOTAL_USERS = 50;
$YEARS_HISTORY = 5; 
$BATCH_SIZE = 2000;

$step = $_GET['step'] ?? 'init';
$currentUserIndex = isset($_GET['user_idx']) ? (int)$_GET['user_idx'] : 1;

?>
<!DOCTYPE html>
<html>
<head>
    <title>Symulator Finansowy</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #1a1a1a; color: #e0e0e0; text-align: center; padding: 40px; }
        .box { background: #2d2d2d; padding: 30px; border-radius: 15px; max-width: 600px; margin: 0 auto; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        .progress { height: 20px; background: #444; border-radius: 10px; margin: 20px 0; overflow: hidden; }
        .fill { height: 100%; background: #3b82f6; width: 0%; transition: width 0.2s; }
        h1 { color: #60a5fa; }
        .log { font-family: monospace; color: #4ade80; font-size: 13px; text-align: left; background: #000; padding: 10px; border-radius: 5px; height: 150px; overflow-y: auto; }
    </style>
</head>
<body>
<div class="box">

<?php
if ($step === 'init') {
    // FAZA 1: STRUKTURA BAZY I CZYSZCZENIE
    
    $db->exec("SET FOREIGN_KEY_CHECKS = 0");
    $tables = ['family_links', 'planned_payments', 'debts', 'savings_goals', 'net_worth_history', 'bank_connections', 'personal_transactions', 'budgets', 'categories', 'users'];
    foreach ($tables as $t) {
        $db->exec("DELETE FROM `$t`");
        $db->exec("ALTER TABLE `$t` AUTO_INCREMENT = 1");
    }

    // Tworzenie Admina
    $pass = password_hash('1234', PASSWORD_DEFAULT);
    $db->prepare("INSERT INTO users (username, email, password, is_2fa_enabled, created_at) VALUES (?, ?, ?, 0, NOW())")
       ->execute(['Admin', 'admin@demo.com', $pass]);

    // Tworzenie reszty użytkowników
    $usersSql = [];
    for($i=2; $i<=$TOTAL_USERS; $i++) {
        $usersSql[] = "('User $i', 'user$i@demo.com', '$pass', 0, NOW())";
    }
    $db->query("INSERT INTO users (username, email, password, is_2fa_enabled, created_at) VALUES " . implode(',', $usersSql));

    // Tworzenie Kategorii dla wszystkich
    $cats = [
        ['Jedzenie', 'expense', '#10b981'], ['Mieszkanie', 'expense', '#3b82f6'], 
        ['Transport', 'expense', '#f59e0b'], ['Praca', 'income', '#059669'],
        ['Rozrywka', 'expense', '#8b5cf6'], ['Zdrowie', 'expense', '#ec4899'],
        ['Zakupy', 'expense', '#6366f1'], ['Wakacje', 'expense', '#f43f5e']
    ];
    
    $catSql = [];
    for($u=1; $u<=$TOTAL_USERS; $u++) {
        foreach($cats as $c) $catSql[] = "($u, '{$c[0]}', '{$c[1]}', '{$c[2]}')";
    }
    foreach(array_chunk($catSql, 2000) as $chunk) {
        $db->query("INSERT INTO categories (user_id, name, type, color) VALUES " . implode(',', $chunk));
    }

    // Łączenie rodzin (User 1+2, 3+4...)
    $famSql = [];
    for($u=1; $u<$TOTAL_USERS; $u+=2) {
        $p = $u+1;
        $famSql[] = "($u, 'user$p@demo.com', $p, 'accepted', NOW())";
        $famSql[] = "($p, 'user$u@demo.com', $u, 'accepted', NOW())";
    }
    $db->query("INSERT INTO family_links (inviter_id, invitee_email, member_id, status, created_at) VALUES " . implode(',', $famSql));

    $db->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "<h1>🏗️ Fundamenty gotowe</h1>";
    echo "<p>Stworzono użytkowników i kategorie. Rozpoczynam symulację życia...</p>";
    echo "<script>setTimeout(() => window.location.href = '?step=simulate&user_idx=1', 1000);</script>";
}

if ($step === 'simulate') {
    // FAZA 2: SYMULACJA DLA POJEDYNCZEGO UŻYTKOWNIKA (pętla przekierowań)
    
    if ($currentUserIndex > $TOTAL_USERS) {
        echo "<h1>✅ SYMULACJA ZAKOŃCZONA</h1>";
        echo "<p>Wygenerowano realistyczne dane.</p>";
        echo "<a href='../pages/dashboard.php' style='color:#fff'>Idź do Dashboardu</a>";
        exit;
    }

    // 1. Definiowanie Archetypu Użytkownika
    // Losujemy, kim jest ten user
    $archetype = mt_rand(1, 4); 
    
    $salaryBase = 0;
    $rentBase = 0;
    $lifestyleMultiplier = 1.0;
    
    switch($archetype) {
        case 1: // Student / Junior (Niskie dochody)
            $salaryBase = 3500; $rentBase = 1200; $lifestyleMultiplier = 0.8; $role = "Student"; break;
        case 2: // Średnia Krajowa
            $salaryBase = 5500; $rentBase = 2000; $lifestyleMultiplier = 1.0; $role = "Standard"; break;
        case 3: // Programista / Manager (Wysokie dochody)
            $salaryBase = 12000; $rentBase = 3500; $lifestyleMultiplier = 1.8; $role = "High Earner"; break;
        case 4: // Oszczędny
            $salaryBase = 6000; $rentBase = 1500; $lifestyleMultiplier = 0.6; $role = "Saver"; break;
    }

    // ID Kategorii dla tego usera (zakładamy sekwencyjne ID)
    $cBase = ($currentUserIndex - 1) * 8; 
    $catId = [
        'Jedzenie' => $cBase + 1, 'Mieszkanie' => $cBase + 2, 'Transport' => $cBase + 3,
        'Praca' => $cBase + 4, 'Rozrywka' => $cBase + 5, 'Zdrowie' => $cBase + 6,
        'Zakupy' => $cBase + 7, 'Wakacje' => $cBase + 8
    ];

    // Pętla Czasu (Miesiąc po miesiącu)
    $startDate = new DateTime("-$YEARS_HISTORY years");
    $endDate = new DateTime();
    $transactions = [];

    // Inflacja startowa (wzrośnie w czasie)
    $inflation = 0.85; 

    while ($startDate <= $endDate) {
        $ym = $startDate->format('Y-m');
        $month = (int)$startDate->format('n');
        $daysInMonth = (int)$startDate->format('t');

        // Powolny wzrost inflacji co miesiąc
        $inflation += 0.002; 
        
        // PENSJA (10-go każdego miesiąca)
        // Czasem premia (grudzień, czerwiec)
        $currentSalary = $salaryBase * $inflation;
        if ($month == 12) $currentSalary *= 1.2; // Premia świąteczna
        
        // Wariancja zarobków (+/- 200 zł)
        $finalSalary = $currentSalary + mt_rand(-200, 200);
        $date = $ym . "-10";
        if ($date <= date('Y-m-d')) {
            $transactions[] = "($currentUserIndex, {$catId['Praca']}, " . round($finalSalary, 2) . ", 'income', 'Wynagrodzenie', '$date', NOW())";
        }

        // WYDATKI STAŁE (1-go)
        // Czynsz
        $rent = $rentBase * $inflation;
        $date = $ym . "-01";
        if ($date <= date('Y-m-d')) {
            $transactions[] = "($currentUserIndex, {$catId['Mieszkanie']}, " . round($rent, 2) . ", 'expense', 'Czynsz', '$date', NOW())";
            $transactions[] = "($currentUserIndex, {$catId['Mieszkanie']}, " . round(150 * $inflation, 2) . ", 'expense', 'Prąd', '$date', NOW())";
            $transactions[] = "($currentUserIndex, {$catId['Rozrywka']}, " . round(50 * $inflation, 2) . ", 'expense', 'Internet', '$date', NOW())";
        }

        // ŻYCIE CODZIENNE (Pętla po dniach miesiąca)
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $currentDateStr = $ym . "-" . str_pad($d, 2, '0', STR_PAD_LEFT);
            if ($currentDateStr > date('Y-m-d')) break;

            $dayOfWeek = date('N', strtotime($currentDateStr)); // 1=Pon, 7=Niedz

            // Jedzenie (Zakupy w Soboty + drobne w tygodniu)
            if ($dayOfWeek == 6) { // Sobota - duże zakupy
                $amount = mt_rand(150, 300) * $inflation * $lifestyleMultiplier;
                $transactions[] = "($currentUserIndex, {$catId['Jedzenie']}, " . round($amount, 2) . ", 'expense', 'Zakupy Tygodniowe', '$currentDateStr', NOW())";
            } elseif (mt_rand(1, 100) < 30) { // 30% szans w inne dni
                $amount = mt_rand(20, 50) * $inflation;
                $transactions[] = "($currentUserIndex, {$catId['Jedzenie']}, " . round($amount, 2) . ", 'expense', 'Żabka/Piekarnia', '$currentDateStr', NOW())";
            }

            // Transport (Paliwo/Bilety w tygodniu)
            if ($dayOfWeek <= 5 && mt_rand(1, 100) < 20) {
                $amount = mt_rand(50, 200) * $inflation;
                $transactions[] = "($currentUserIndex, {$catId['Transport']}, " . round($amount, 2) . ", 'expense', 'Paliwo/Uber', '$currentDateStr', NOW())";
            }

            // Rozrywka (Piątek/Sobota wieczór)
            if (($dayOfWeek == 5 || $dayOfWeek == 6) && mt_rand(1, 100) < 50) {
                $amount = mt_rand(50, 200) * $inflation * $lifestyleMultiplier;
                $transactions[] = "($currentUserIndex, {$catId['Rozrywka']}, " . round($amount, 2) . ", 'expense', 'Kino/Bar/Restauracja', '$currentDateStr', NOW())";
            }
        }

        // WYDATKI SEZONOWE
        // Grudzień - Prezenty
        if ($month == 12) {
            $date = $ym . "-20";
            if ($date <= date('Y-m-d')) {
                $amount = mt_rand(500, 1500) * $inflation * $lifestyleMultiplier;
                $transactions[] = "($currentUserIndex, {$catId['Zakupy']}, " . round($amount, 2) . ", 'expense', 'Prezenty Świąteczne', '$date', NOW())";
            }
        }
        // Lipiec - Wakacje
        if ($month == 7) {
            $date = $ym . "-15";
            if ($date <= date('Y-m-d')) {
                $amount = mt_rand(1000, 4000) * $inflation * $lifestyleMultiplier;
                $transactions[] = "($currentUserIndex, {$catId['Wakacje']}, " . round($amount, 2) . ", 'expense', 'Wyjazd Wakacyjny', '$date', NOW())";
            }
        }

        $startDate->modify('+1 month');
    }

    // Zapis transakcji w paczkach
    $chunks = array_chunk($transactions, $BATCH_SIZE);
    $db->beginTransaction();
    foreach ($chunks as $chunk) {
        $sql = "INSERT INTO personal_transactions (user_id, category_id, amount, type, description, transaction_date, created_at) VALUES " . implode(',', $chunk);
        $db->query($sql);
    }
    
    // Dodatki dla użytkownika (Planer i Długi)
    $future = date('Y-m-d', strtotime('+5 days'));
    $db->query("INSERT INTO planned_payments (user_id, category_id, title, amount, due_date, type, status, is_recurring, recurrence_interval) VALUES 
        ($currentUserIndex, {$catId['Mieszkanie']}, 'Czynsz', " . round($rentBase*$inflation, 2) . ", '$future', 'expense', 'pending', 1, 'monthly')");
    
    if ($archetype == 1 || $archetype == 2) {
        // Biedniejsi mają długi
        $db->query("INSERT INTO debts (user_id, name, balance, interest_rate, min_payment) VALUES 
            ($currentUserIndex, 'Karta Kredytowa', " . mt_rand(2000, 8000) . ", 18.5, 300)");
    }
    
    $db->commit();

    // Postęp
    $nextUser = $currentUserIndex + 1;
    $percent = round(($currentUserIndex / $TOTAL_USERS) * 100);
    
    echo "<h1>⚙️ Przetwarzanie Usera #$currentUserIndex</h1>";
    echo "<p>Archetyp: <b>$role</b></p>";
    echo "<div class='progress'><div class='fill' style='width: $percent%'></div></div>";
    echo "<div class='log'>Wygenerowano " . count($transactions) . " transakcji.<br>Symulacja inflacji i sezonowości zakończona.</div>";
    
    echo "<script>setTimeout(() => window.location.href = '?step=simulate&user_idx=$nextUser', 50);</script>";
}
?>

</div>
</body>
</html>