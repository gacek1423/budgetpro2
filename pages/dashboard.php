<?php
// budgetpro2/pages/dashboard.php

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

$includes_dir = __DIR__ . '/../includes';
$required_files = [
    'session.php' => $includes_dir . '/session.php',
    'db.php' => $includes_dir . '/db.php',
    'functions.php' => $includes_dir . '/functions.php'
];

foreach ($required_files as $name => $path) {
    if (!file_exists($path)) {
        die("❌ CRITICAL ERROR: Missing file $name at: $path");
    }
    require_once $path;
}

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// Pobierz dane
try {
    $prefs = getUserPreferences();
    $db = db()->getConnection();
    
    // Statystyki osobiste
    $sqlIncome = "SELECT COALESCE(SUM(amount), 0) FROM personal_transactions WHERE user_id = ? AND type = 'income'";
    $income = $db->prepare($sqlIncome);
    $income->execute([$user_id]);
    $incomeAmount = (float)$income->fetchColumn();
    
    $sqlExpense = "SELECT COALESCE(SUM(amount), 0) FROM personal_transactions WHERE user_id = ? AND type = 'expense'";
    $expense = $db->prepare($sqlExpense);
    $expense->execute([$user_id]);
    $expenseAmount = (float)$expense->fetchColumn();
    
    $balance = $incomeAmount - $expenseAmount;
    
    // Ostatnie transakcje
    $sqlRecent = "
        SELECT t.*, c.name as category_name, c.color as category_color 
        FROM personal_transactions t 
        LEFT JOIN categories c ON t.category_id = c.id 
        WHERE t.user_id = ? 
        ORDER BY t.transaction_date DESC, t.id DESC 
        LIMIT 5
    ";
    $stmt = $db->prepare($sqlRecent);
    $stmt->execute([$user_id]);
    $recentTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Budżety
    $stmt = $db->prepare("SELECT COUNT(*) as budget_count FROM budgets WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $budgetData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Nowe statystyki
    $stats = [
        'personal' => [
            'income' => $incomeAmount,
            'expense' => $expenseAmount,
            'balance' => $balance,
            'budgets' => $budgetData['budget_count'],
            'goals' => db()->count("SELECT COUNT(*) FROM financial_goals WHERE user_id = ? AND is_active = 1", [$user_id]),
        ],
        'business' => getBusinessStats($user_id),
        'family' => [
            'members' => db()->count("SELECT COUNT(*) FROM family_links WHERE primary_user_id = ?", [$user_id]),
        ]
    ];
    
    // Aktywne cele
    $goals = getActiveGoals($user_id);
    
} catch (Exception $e) {
    $incomeAmount = 0;
    $expenseAmount = 0;
    $balance = 0;
    $recentTransactions = [];
    $budgetData = ['budget_count' => 0];
    $stats = ['personal' => [], 'business' => [], 'family' => []];
    $goals = [];
    
    if (DEBUG_MODE) {
        error_log("Dashboard DB Error: " . $e->getMessage());
    }
}

require_once $includes_dir . '/header.php';
require_once $includes_dir . '/topbar.php';
require_once $includes_dir . '/sidebar.php';
?>
<main class="md:ml-64 p-4 md:p-6">
    <div class="max-w-7xl mx-auto">
        
        <!-- Nagłówek -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-800 dark:text-white">
                <i class="fas fa-tachometer-alt mr-2 text-blue-600"></i>
                Dashboard
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">
                Witaj z powrotem, <?= e($_SESSION['username'] ?? 'Użytkownik') ?>! Zarządzaj swoimi finansami.
            </p>
        </div>

        <!-- NOWE: Widget Celów Finansowych -->
        <?php if (!empty($goals)): ?>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg mb-6">
            <div class="p-6 border-b dark:border-gray-700">
                <h2 class="text-xl font-semibold">Aktywne Cele Finansowe</h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php foreach ($goals as $goal): ?>
                    <div class="border-l-4 border-blue-500 pl-4 bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                        <h3 class="font-semibold mb-2"><?= e($goal['name']) ?></h3>
                        <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-3">
                            <div class="bg-blue-600 h-3 rounded-full transition-all duration-300" 
                                 style="width: <?= $goal['progress'] ?>%"></div>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                            <?= formatCurrency($goal['current_amount']) ?> / <?= formatCurrency($goal['target_amount']) ?> 
                            (<?= $goal['progress'] ?>%)
                        </p>
                        <p class="text-xs text-gray-500">
                            Cel: <?= date('d.m.Y', strtotime($goal['target_date'])) ?>
                        </p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- NOWE: Statystyki Biznesowe (jeśli konto business) -->
        <?php if ($stats['business']['projects'] > 0): ?>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg mb-6">
            <div class="p-6 border-b dark:border-gray-700">
                <h2 class="text-xl font-semibold">Statystyki Biznesowe</h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="text-center">
                        <p class="text-3xl font-bold text-blue-600"><?= $stats['business']['projects'] ?></p>
                        <p class="text-gray-500 text-sm">Projektów</p>
                    </div>
                    <div class="text-center">
                        <p class="text-3xl font-bold text-green-600"><?= $stats['business']['invoices'] ?></p>
                        <p class="text-gray-500 text-sm">Faktur</p>
                    </div>
                    <div class="text-center">
                        <p class="text-3xl font-bold text-purple-600"><?= $stats['business']['clients'] ?></p>
                        <p class="text-gray-500 text-sm">Klientów</p>
                    </div>
                    <div class="text-center">
                        <p class="text-3xl font-bold text-orange-600"><?= $stats['business']['tasks'] ?></p>
                        <p class="text-gray-500 text-sm">Zadań</p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Statystyki Główne -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border-l-4 border-blue-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 dark:text-gray-400 text-sm">Przychody (mies.)</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= formatCurrency($incomeAmount) ?></p>
                    </div>
                    <i class="fas fa-arrow-up text-blue-500 text-2xl"></i>
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border-l-4 border-red-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 dark:text-gray-400 text-sm">Wydatki (mies.)</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= formatCurrency($expenseAmount) ?></p>
                    </div>
                    <i class="fas fa-arrow-down text-red-500 text-2xl"></i>
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border-l-4 border-green-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 dark:text-gray-400 text-sm">Bilans</p>
                        <p class="text-2xl font-bold <?= $balance >= 0 ? 'text-green-600' : 'text-red-600' ?>">
                            <?= formatCurrency($balance) ?>
                        </p>
                    </div>
                    <i class="fas fa-balance-scale text-2xl <?= $balance >= 0 ? 'text-green-500' : 'text-red-500' ?>"></i>
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border-l-4 border-purple-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 dark:text-gray-400 text-sm">Aktywne budżety</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= $budgetData['budget_count'] ?></p>
                    </div>
                    <i class="fas fa-piggy-bank text-purple-500 text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Ostatnie transakcje -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg mb-6">
            <div class="p-6 border-b dark:border-gray-700 flex justify-between items-center">
                <h2 class="text-xl font-semibold">Ostatnie transakcje</h2>
                <a href="/pages/transactions.php" class="text-blue-600 hover:underline">Zobacz wszystkie →</a>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left">Data</th>
                            <th class="px-4 py-3 text-left">Kategoria</th>
                            <th class="px-4 py-3 text-left">Opis</th>
                            <th class="px-4 py-3 text-right">Kwota</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <?php if (empty($recentTransactions)): ?>
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                    <i class="fas fa-inbox text-4xl mb-2 block"></i>
                                    <p>Brak transakcji w tym miesiącu</p>
                                    <a href="pages/add_transaction.php" class="text-blue-600 hover:underline inline-block mt-2">
                                        <i class="fas fa-plus mr-1"></i>Dodaj pierwszą transakcję
                                    </a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentTransactions as $tx): ?>
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-4 py-3"><?= formatDate($tx['transaction_date']) ?></td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium" 
                                              style="background-color: <?= $tx['category_color'] ?>20; color: <?= $tx['category_color'] ?>">
                                            <?= e($tx['category_name'] ?? 'Inne') ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3"><?= e($tx['description'] ?? '-') ?></td>
                                    <td class="px-4 py-3 text-right font-semibold <?= $tx['type'] === 'income' ? 'text-green-600' : 'text-red-600' ?>">
                                        <?= $tx['type'] === 'income' ? '+' : '-' ?> <?= formatCurrency($tx['amount']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Szybkie akcje -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <a href="/pages/add_transaction.php" 
               class="bg-blue-600 hover:bg-blue-700 text-white p-6 rounded-xl shadow-lg flex items-center justify-between transition-colors">
                <div>
                    <h3 class="font-semibold">Dodaj transakcję</h3>
                    <p class="text-blue-100 text-sm">Szybkie dodanie przychodu lub wydatku</p>
                </div>
                <i class="fas fa-plus-circle text-3xl text-blue-300"></i>
            </a>
            
            <a href="/pages/budgets.php" 
               class="bg-green-600 hover:bg-green-700 text-white p-6 rounded-xl shadow-lg flex items-center justify-between transition-colors">
                <div>
                    <h3 class="font-semibold">Zarządzaj budżetami</h3>
                    <p class="text-green-100 text-sm">Ustaw limity wydatków</p>
                </div>
                <i class="fas fa-chart-pie text-3xl text-green-300"></i>
            </a>
            
            <a href="/pages/reports.php" 
               class="bg-purple-600 hover:bg-purple-700 text-white p-6 rounded-xl shadow-lg flex items-center justify-between transition-colors">
                <div>
                    <h3 class="font-semibold">Raporty</h3>
                    <p class="text-purple-100 text-sm">Analiza finansów i trendy</p>
                </div>
                <i class="fas fa-chart-line text-3xl text-purple-300"></i>
            </a>
        </div>

    </div>
</main>

<?php require_once $includes_dir . '/footer.php'; ?>