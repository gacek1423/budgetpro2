<?php
// pages/reports.php
require_once '../includes/db.php';
require_once '../includes/functions.php';

$pageTitle = __('reports_center');
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/topbar.php';

$user_id = getCurrentUserId();
$db = db();

$start_date = filter_var($_GET['start'] ?? date('Y-m-01'), FILTER_SANITIZE_SPECIAL_CHARS);
$end_date = filter_var($_GET['end'] ?? date('Y-m-d'), FILTER_SANITIZE_SPECIAL_CHARS);

// KPI
$sqlTotal = "SELECT SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income, SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expense FROM personal_transactions WHERE user_id = ? AND transaction_date BETWEEN ? AND ?";
$stmt = $db->prepare($sqlTotal);
$stmt->execute([$user_id, $start_date, $end_date]);
$totals = $stmt->fetch(PDO::FETCH_ASSOC);
$income = (float)($totals['total_income'] ?? 0);
$expense = (float)($totals['total_expense'] ?? 0);
$balance = $income - $expense;
$savings_rate = $income > 0 ? round(($balance / $income) * 100, 1) : 0;

// Wykresy (Trend + Kategorie)
$sqlTrend = "SELECT transaction_date, SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as daily_income, SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as daily_expense FROM personal_transactions WHERE user_id = ? AND transaction_date BETWEEN ? AND ? GROUP BY transaction_date ORDER BY transaction_date ASC";
$stmt = $db->prepare($sqlTrend);
$stmt->execute([$user_id, $start_date, $end_date]);
$trendData = $stmt->fetchAll(PDO::FETCH_ASSOC);
$labels = []; $dataIncome = []; $dataExpense = [];
foreach ($trendData as $row) {
    $labels[] = formatDate($row['transaction_date']);
    $dataIncome[] = $row['daily_income'];
    $dataExpense[] = $row['daily_expense'];
}

$sqlCat = "SELECT c.name, c.color, SUM(t.amount) as total FROM categories c JOIN personal_transactions t ON c.id = t.category_id WHERE t.user_id = ? AND t.type = 'expense' AND t.transaction_date BETWEEN ? AND ? GROUP BY c.id ORDER BY total DESC";
$stmt = $db->prepare($sqlCat);
$stmt->execute([$user_id, $start_date, $end_date]);
$catData = $stmt->fetchAll(PDO::FETCH_ASSOC);
$catLabels = []; $catAmounts = []; $catColors = [];
foreach ($catData as $row) { $catLabels[] = $row['name']; $catAmounts[] = $row['total']; $catColors[] = $row['color'] ?: '#ccc'; }
?>

<div class="space-y-6">
    <div class="flex flex-col md:flex-row justify-between items-center bg-white dark:bg-dark-card p-4 rounded-xl shadow-sm border dark:border-slate-700">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-4 md:mb-0"><i class="fas fa-chart-line text-indigo-500 mr-2"></i> <?php echo __('reports_center'); ?></h2>
        <form class="flex items-center space-x-2">
            <input type="date" name="start" value="<?php echo $start_date; ?>" class="border p-2 rounded-lg text-sm dark:bg-slate-800 dark:border-slate-600 dark:text-white">
            <span class="text-gray-400">-</span>
            <input type="date" name="end" value="<?php echo $end_date; ?>" class="border p-2 rounded-lg text-sm dark:bg-slate-800 dark:border-slate-600 dark:text-white">
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white p-2 rounded-lg transition"><i class="fas fa-filter"></i></button>
        </form>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-dark-card p-5 rounded-xl shadow-sm border-l-4 border-green-500 dark:border-slate-700">
            <p class="text-xs text-gray-500 uppercase font-bold"><?php echo __('total_income'); ?></p>
            <p class="text-2xl font-bold text-green-600 mt-1"><?php echo formatCurrency($income); ?></p>
        </div>
        <div class="bg-white dark:bg-dark-card p-5 rounded-xl shadow-sm border-l-4 border-red-500 dark:border-slate-700">
            <p class="text-xs text-gray-500 uppercase font-bold"><?php echo __('total_expense'); ?></p>
            <p class="text-2xl font-bold text-red-600 mt-1"><?php echo formatCurrency($expense); ?></p>
        </div>
        <div class="bg-white dark:bg-dark-card p-5 rounded-xl shadow-sm border-l-4 border-blue-500 dark:border-slate-700">
            <p class="text-xs text-gray-500 uppercase font-bold"><?php echo __('balance'); ?></p>
            <p class="text-2xl font-bold <?php echo $balance >= 0 ? 'text-blue-600' : 'text-red-500'; ?> mt-1"><?php echo formatCurrency($balance); ?></p>
        </div>
        <div class="bg-white dark:bg-dark-card p-5 rounded-xl shadow-sm border-l-4 border-purple-500 dark:border-slate-700">
            <p class="text-xs text-gray-500 uppercase font-bold"><?php echo __('savings_rate'); ?></p>
            <p class="text-2xl font-bold text-purple-600 mt-1"><?php echo $savings_rate; ?>%</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white dark:bg-dark-card p-6 rounded-xl shadow-sm border dark:border-slate-700">
            <h3 class="font-bold text-gray-700 dark:text-white mb-4"><?php echo __('cash_flow_chart'); ?></h3>
            <div class="h-80"><canvas id="trendChart"></canvas></div>
        </div>
        <div class="bg-white dark:bg-dark-card p-6 rounded-xl shadow-sm border dark:border-slate-700">
            <h3 class="font-bold text-gray-700 dark:text-white mb-4"><?php echo __('expenses_structure'); ?></h3>
            <div class="h-64 flex justify-center">
                <?php if(empty($catAmounts)): ?><p class="text-gray-400 self-center"><?php echo __('no_data'); ?></p><?php else: ?><canvas id="categoryChart"></canvas><?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctxTrend = document.getElementById('trendChart').getContext('2d');
    new Chart(ctxTrend, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($labels); ?>,
            datasets: [
                { label: '<?php echo __('total_income'); ?>', data: <?php echo json_encode($dataIncome); ?>, borderColor: '#10b981', tension: 0.4 },
                { label: '<?php echo __('total_expense'); ?>', data: <?php echo json_encode($dataExpense); ?>, borderColor: '#ef4444', tension: 0.4 }
            ]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });

    <?php if(!empty($catAmounts)): ?>
    new Chart(document.getElementById('categoryChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($catLabels); ?>,
            datasets: [{ data: <?php echo json_encode($catAmounts); ?>, backgroundColor: <?php echo json_encode($catColors); ?>, borderWidth: 0 }]
        },
        options: { responsive: true, maintainAspectRatio: false, legend: { display: false } }
    });
    <?php endif; ?>
</script>
<?php require_once '../includes/footer.php'; ?>