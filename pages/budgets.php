<?php
// pages/budgets.php
require_once '../includes/db.php';
require_once '../includes/functions.php';

$pageTitle = __('budgets_title');
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/topbar.php';

$user_id = getCurrentUserId();
$db = db();

// Pobieranie danych: Kategorie z ustawionym limitem > 0 + suma wydatków z tego miesiąca
$start_date = date('Y-m-01');
$end_date = date('Y-m-t');

$sql = "
    SELECT 
        c.id, c.name, c.color, c.icon, c.budget_limit,
        COALESCE(SUM(t.amount), 0) as spent
    FROM categories c
    LEFT JOIN personal_transactions t 
        ON c.id = t.category_id 
        AND t.type = 'expense' 
        AND t.transaction_date BETWEEN ? AND ?
    WHERE c.user_id = ? AND c.budget_limit > 0
    GROUP BY c.id
    ORDER BY c.budget_limit DESC
";

$stmt = $db->prepare($sql);
$stmt->execute([$start_date, $end_date, $user_id]);
$budgets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pobierz wszystkie kategorie (wydatki) do modala (żeby móc ustawić budżet dla nowej)
$catStmt = $db->prepare("SELECT * FROM categories WHERE user_id = ? AND type = 'expense' ORDER BY name");
$catStmt->execute([$user_id]);
$allCategories = $catStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
    <div>
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white"><?php echo __('budgets_title'); ?></h2>
        <p class="text-gray-500 text-sm dark:text-gray-400"><?php echo __('budgets_desc'); ?></p>
    </div>
    <button onclick="openBudgetModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl shadow-lg flex items-center transition transform hover:scale-105">
        <i class="fas fa-plus mr-2"></i> <?php echo __('create_budget'); ?>
    </button>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <?php if (empty($budgets)): ?>
        <div class="col-span-full py-20 text-center text-gray-400 border-2 border-dashed border-gray-200 dark:border-slate-700 rounded-3xl">
            <i class="fas fa-chart-pie text-6xl mb-4 opacity-30"></i>
            <p class="text-lg"><?php echo __('no_data'); ?></p>
            <p class="text-sm">Ustaw limit dla kategorii, aby kontrolować wydatki.</p>
        </div>
    <?php else: ?>
        <?php foreach ($budgets as $b): 
            $percent = $b['budget_limit'] > 0 ? round(($b['spent'] / $b['budget_limit']) * 100) : 0;
            $remaining = $b['budget_limit'] - $b['spent'];
            $isOver = $remaining < 0;
            
            // Kolory paska
            $barColor = 'bg-green-500';
            if($percent >= 80) $barColor = 'bg-yellow-500';
            if($percent >= 100) $barColor = 'bg-red-500';
        ?>
        <div class="bg-white dark:bg-dark-card rounded-xl shadow-sm border dark:border-slate-700 p-6 relative group hover:shadow-md transition">
            
            <div class="flex justify-between items-start mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white text-xl shadow-sm" style="background-color: <?php echo $b['color']; ?>">
                        <i class="fas <?php echo $b['icon']; ?>"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-lg dark:text-white"><?php echo htmlspecialchars($b['name']); ?></h3>
                        <div class="text-xs font-bold uppercase <?php echo $isOver ? 'text-red-500' : 'text-green-500'; ?>">
                            <?php echo $isOver ? __('over_budget') : __('on_track'); ?>
                        </div>
                    </div>
                </div>
                <button onclick='editBudget(<?php echo json_encode($b); ?>)' class="text-gray-300 hover:text-blue-500 p-2 opacity-0 group-hover:opacity-100 transition">
                    <i class="fas fa-pen"></i>
                </button>
            </div>

            <div class="flex justify-between text-sm mb-1 dark:text-gray-300">
                <span><?php echo __('spent'); ?>: <b><?php echo formatCurrency($b['spent']); ?></b></span>
                <span><?php echo __('budget_percent'); ?>: <b><?php echo $percent; ?>%</b></span>
            </div>

            <div class="w-full bg-gray-200 dark:bg-slate-700 rounded-full h-4 overflow-hidden mb-2">
                <div class="<?php echo $barColor; ?> h-4 rounded-full transition-all duration-1000" style="width: <?php echo min(100, $percent); ?>%"></div>
            </div>

            <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400">
                <span>Limit: <?php echo formatCurrency($b['budget_limit']); ?></span>
                <span class="<?php echo $isOver ? 'text-red-500 font-bold' : ''; ?>">
                    <?php echo __('remaining'); ?>: <?php echo formatCurrency($remaining); ?>
                </span>
            </div>

        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div id="budgetModal" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-50 backdrop-blur-sm p-4">
    <div class="bg-white dark:bg-dark-card rounded-2xl p-6 w-full max-w-md shadow-2xl border dark:border-slate-700 transform transition-all scale-100">
        <h3 class="text-xl font-bold mb-4 dark:text-white"><?php echo __('create_budget'); ?></h3>
        
        <form id="budget-form" class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1"><?php echo __('category'); ?></label>
                <select name="category_id" id="cat-select" class="w-full border p-3 rounded-lg dark:bg-slate-800 dark:border-slate-600 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none">
                    <?php foreach ($allCategories as $c): ?>
                        <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1"><?php echo __('budget_amount'); ?></label>
                <div class="relative">
                    <input type="number" step="0.01" name="amount" id="budget-amount" class="w-full border p-3 rounded-lg dark:bg-slate-800 dark:border-slate-600 dark:text-white font-bold focus:ring-2 focus:ring-blue-500 outline-none" placeholder="0.00" required>
                    <span class="absolute right-4 top-3 text-gray-400">PLN</span>
                </div>
                <p class="text-xs text-gray-400 mt-1">Wpisz 0, aby usunąć budżet.</p>
            </div>

            <div class="flex justify-end gap-2 mt-6">
                <button type="button" onclick="closeBudgetModal()" class="px-4 py-2 text-gray-500 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg transition"><?php echo __('cancel'); ?></button>
                <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-bold shadow transition"><?php echo __('save'); ?></button>
            </div>
        </form>
    </div>
</div>

<script>
    const modal = document.getElementById('budgetModal');
    const catSelect = document.getElementById('cat-select');
    const amountInput = document.getElementById('budget-amount');

    function openBudgetModal() {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        catSelect.value = catSelect.options[0].value; // Reset select
        amountInput.value = '';
    }

    function closeBudgetModal() {
        modal.classList.add('hidden');
        modal.classList.remove