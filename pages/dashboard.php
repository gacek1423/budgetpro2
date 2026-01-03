<?php
// pages/dashboard.php

// 1. NAJWAŻNIEJSZE: Załaduj funkcje PRZED użyciem ich do tłumaczenia tytułu
require_once '../includes/db.php';
require_once '../includes/functions.php';

// 2. Teraz funkcja __() jest już znana i zadziała
$pageTitle = __('menu_dashboard');

// 3. Ładowanie reszty modułów
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/topbar.php';

// 4. LOGIKA BIZNESOWA
$user_id = getCurrentUserId();
$db = db();

// A. Pobieranie sum
$sqlIncome = "SELECT COALESCE(SUM(amount), 0) FROM personal_transactions WHERE user_id = ? AND type = 'income'";
$sqlExpense = "SELECT COALESCE(SUM(amount), 0) FROM personal_transactions WHERE user_id = ? AND type = 'expense'";

$income = $db->prepare($sqlIncome);
$income->execute([$user_id]);
$incomeAmount = (float)$income->fetchColumn();

$expense = $db->prepare($sqlExpense);
$expense->execute([$user_id]);
$expenseAmount = (float)$expense->fetchColumn();

$balance = $incomeAmount - $expenseAmount;

// B. Ostatnie 5 transakcji
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
?>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    
    <div class="bg-dark-card p-6 rounded-xl border border-slate-700 shadow-lg relative overflow-hidden group">
        <div class="absolute right-0 top-0 p-4 opacity-10 group-hover:opacity-20 transition">
            <i class="fas fa-wallet text-6xl text-emerald-500"></i>
        </div>
        <h3 class="text-slate-400 text-xs font-bold uppercase tracking-wider mb-1"><?php echo __('total_income'); ?></h3>
        <p class="text-2xl font-bold text-emerald-400">
            <?php echo formatCurrency($incomeAmount); ?>
        </p>
    </div>

    <div class="bg-dark-card p-6 rounded-xl border border-slate-700 shadow-lg relative overflow-hidden group">
        <div class="absolute right-0 top-0 p-4 opacity-10 group-hover:opacity-20 transition">
            <i class="fas fa-shopping-cart text-6xl text-red-500"></i>
        </div>
        <h3 class="text-slate-400 text-xs font-bold uppercase tracking-wider mb-1"><?php echo __('total_expense'); ?></h3>
        <p class="text-2xl font-bold text-red-400">
            <?php echo formatCurrency($expenseAmount); ?>
        </p>
    </div>

    <div class="bg-dark-card p-6 rounded-xl border border-slate-700 shadow-lg relative overflow-hidden group">
        <div class="absolute right-0 top-0 p-4 opacity-10 group-hover:opacity-20 transition">
            <i class="fas fa-chart-line text-6xl text-blue-500"></i>
        </div>
        <h3 class="text-slate-400 text-xs font-bold uppercase tracking-wider mb-1"><?php echo __('balance'); ?></h3>
        <p class="text-2xl font-bold <?php echo $balance >= 0 ? 'text-blue-400' : 'text-red-400'; ?>">
            <?php echo formatCurrency($balance); ?>
        </p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    
    <div class="lg:col-span-2 bg-dark-card rounded-xl border border-slate-700 shadow-lg overflow-hidden">
        <div class="p-5 border-b border-slate-700 flex justify-between items-center">
            <h3 class="font-bold text-white"><?php echo __('recent_transactions'); ?></h3>
            <a href="transactions.php" class="text-xs text-brand-blue hover:text-white transition"><?php echo __('view_all'); ?></a>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="bg-slate-800/50 text-xs uppercase font-bold text-slate-500">
                    <tr>
                        <th class="p-4"><?php echo __('date'); ?></th>
                        <th class="p-4"><?php echo __('category'); ?></th>
                        <th class="p-4"><?php echo __('description'); ?></th>
                        <th class="p-4 text-right"><?php echo __('amount'); ?></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-700">
                    <?php if (count($recentTransactions) > 0): ?>
                        <?php foreach($recentTransactions as $t): ?>
                        <tr class="hover:bg-slate-700/30 transition">
                            <td class="p-4 text-slate-400 font-mono text-xs">
                                <?php echo formatDate($t['transaction_date']); ?>
                            </td>
                            <td class="p-4">
                                <span class="px-2 py-1 rounded text-[10px] font-bold uppercase tracking-wide border border-opacity-20"
                                      style="color: <?php echo $t['category_color']; ?>; border-color: <?php echo $t['category_color']; ?>; background-color: <?php echo $t['category_color']; ?>15;">
                                    <?php echo htmlspecialchars($t['category_name'] ?? 'Inne'); ?>
                                </span>
                            </td>
                            <td class="p-4 font-medium text-white truncate max-w-[150px]">
                                <?php echo htmlspecialchars($t['description']); ?>
                            </td>
                            <td class="p-4 text-right font-bold <?php echo $t['type'] === 'income' ? 'text-emerald-400' : 'text-red-400'; ?>">
                                <?php echo $t['type'] === 'income' ? '+' : '-'; ?>
                                <?php echo formatCurrency($t['amount']); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="p-8 text-center text-slate-500">
                                <i class="fas fa-inbox text-3xl mb-3 opacity-50"></i><br>
                                <?php echo __('no_data'); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="space-y-6">
        <div class="bg-gradient-to-br from-brand-blue to-blue-700 p-6 rounded-xl shadow-lg text-white">
            <h3 class="font-bold text-lg mb-2"><?php echo __('quick_action'); ?></h3>
            <p class="text-blue-100 text-sm mb-4"><?php echo __('quick_action_desc'); ?></p>
            <a href="transactions.php?action=new" class="block w-full bg-white text-blue-600 text-center py-2 rounded-lg font-bold hover:bg-blue-50 transition shadow-md">
                <i class="fas fa-plus mr-2"></i> <?php echo __('add_transaction'); ?>
            </a>
        </div>

        <div class="bg-dark-card p-5 rounded-xl border border-slate-700 shadow-lg">
            <h3 class="font-bold text-white mb-4 text-sm uppercase text-slate-400"><?php echo __('your_goals'); ?></h3>
            <div class="text-center py-4 text-slate-500 text-sm">
                <a href="goals.php" class="hover:text-brand-green transition"><?php echo __('go_to_goals'); ?> &rarr;</a>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>