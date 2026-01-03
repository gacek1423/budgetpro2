<?php
// pages/planner.php
require_once '../includes/db.php';
require_once '../includes/functions.php';

$pageTitle = __('planner_title');
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/topbar.php';

$user_id = getCurrentUserId();
$db = db();
$currentYear = $_GET['year'] ?? date('Y');
$currentMonth = $_GET['month'] ?? date('m');

$sql = "SELECT p.*, c.name as cat_name, c.color as cat_color FROM planned_payments p LEFT JOIN categories c ON p.category_id = c.id WHERE p.user_id = ? AND MONTH(p.due_date) = ? AND YEAR(p.due_date) = ? ORDER BY p.due_date ASC";
$stmt = $db->prepare($sql);
$stmt->execute([$user_id, $currentMonth, $currentYear]);
$planned = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalToPay = 0; $totalPaid = 0;
foreach($planned as $p) {
    if($p['type'] == 'expense') {
        if($p['status'] == 'paid') $totalPaid += $p['amount']; else $totalToPay += $p['amount'];
    }
}
$catStmt = $db->prepare("SELECT id, name FROM categories WHERE user_id = ? ORDER BY name");
$catStmt->execute([$user_id]);
$categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
    <div class="flex items-center bg-white dark:bg-dark-card rounded-lg shadow p-1 border dark:border-slate-700">
        <a href="?month=<?= $currentMonth-1 <= 0 ? 12 : $currentMonth-1 ?>&year=<?= $currentMonth-1 <= 0 ? $currentYear-1 : $currentYear ?>" class="px-4 py-2 hover:bg-gray-100 dark:hover:bg-slate-700 rounded transition"><i class="fas fa-chevron-left"></i></a>
        <span class="px-6 font-bold text-lg text-gray-800 dark:text-white w-48 text-center"><?= $currentMonth . ' / ' . $currentYear; ?></span>
        <a href="?month=<?= $currentMonth+1 > 12 ? 1 : $currentMonth+1 ?>&year=<?= $currentMonth+1 > 12 ? $currentYear+1 : $currentYear ?>" class="px-4 py-2 hover:bg-gray-100 dark:hover:bg-slate-700 rounded transition"><i class="fas fa-chevron-right"></i></a>
    </div>
    <button onclick="openPlanModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg shadow flex items-center transition">
        <i class="fas fa-plus mr-2"></i> <?php echo __('new_payment'); ?>
    </button>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
    <div class="bg-white dark:bg-dark-card p-6 rounded-xl border-l-4 border-orange-400 shadow-sm flex justify-between items-center">
        <div><p class="text-xs font-bold text-gray-400 uppercase"><?php echo __('remaining_to_pay'); ?></p><h3 class="text-2xl font-bold dark:text-white"><?= formatCurrency($totalToPay) ?></h3></div>
        <div class="w-10 h-10 rounded-full bg-orange-100 flex items-center justify-center text-orange-500"><i class="fas fa-hourglass-half"></i></div>
    </div>
    <div class="bg-white dark:bg-dark-card p-6 rounded-xl border-l-4 border-green-500 shadow-sm flex justify-between items-center">
        <div><p class="text-xs font-bold text-gray-400 uppercase"><?php echo __('paid_this_month'); ?></p><h3 class="text-2xl font-bold dark:text-white"><?= formatCurrency($totalPaid) ?></h3></div>
        <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center text-green-500"><i class="fas fa-check-double"></i></div>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    <div class="xl:col-span-2 bg-white dark:bg-dark-card rounded-xl shadow p-4 dark:border dark:border-slate-700">
        <p class="text-center text-gray-400 py-10">Widok kalendarza dostępny w wersji Pro</p>
    </div>
    
    <div class="bg-white dark:bg-dark-card rounded-xl shadow p-5 dark:border dark:border-slate-700 flex flex-col h-[600px] xl:h-auto">
        <h3 class="font-bold mb-4 dark:text-white text-lg"><?php echo __('chronological_list'); ?></h3>
        <div class="flex-1 overflow-y-auto custom-scrollbar space-y-3 pr-1">
            <?php foreach($planned as $p): 
                $isPaid = $p['status'] === 'paid';
            ?>
            <div class="flex items-center p-3 rounded-lg border <?php echo $isPaid ? 'bg-gray-50 opacity-60' : 'bg-white dark:bg-dark-card'; ?>">
                <div class="flex flex-col items-center justify-center w-10 h-10 rounded bg-slate-100 dark:bg-slate-800 mr-3">
                    <span class="text-xs font-bold"><?= date('d', strtotime($p['due_date'])) ?></span>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="font-bold text-sm truncate dark:text-white"><?= htmlspecialchars($p['title']) ?></div>
                    <div class="text-xs text-gray-500"><?= htmlspecialchars($p['cat_name'] ?: '-') ?></div>
                </div>
                <div class="text-right shrink-0 ml-2">
                    <div class="font-bold text-sm"><?= formatCurrency($p['amount']) ?></div>
                    <?php if(!$isPaid): ?>
                        <button onclick="markAsPaid(<?= $p['id'] ?>)" class="text-[10px] bg-blue-600 text-white px-2 py-0.5 rounded shadow mt-1"><?php echo __('mark_as_paid'); ?></button>
                    <?php else: ?>
                        <div class="text-[10px] text-green-500 mt-1"><i class="fas fa-check"></i> <?php echo __('is_paid'); ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div id="planModal" class="fixed inset-0 bg-black/50 hidden flex items-center justify-center z-50 backdrop-blur-sm">
    <div class="bg-white dark:bg-dark-card rounded-lg p-6 w-96 shadow-2xl">
        <h3 class="text-xl font-bold mb-4 dark:text-white"><?php echo __('new_payment'); ?></h3>
        <form id="add-plan-form" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-xs font-bold uppercase mb-1"><?php echo __('type'); ?></label><select name="type" class="w-full border p-2 rounded dark:bg-slate-800"><option value="expense"><?php echo __('filter_expense'); ?></option><option value="income"><?php echo __('filter_income'); ?></option></select></div>
                <div><label class="block text-xs font-bold uppercase mb-1"><?php echo __('date'); ?></label><input type="date" name="due_date" class="w-full border p-2 rounded dark:bg-slate-800" value="<?= date('Y-m-d') ?>"></div>
            </div>
            <div><label class="block text-xs font-bold uppercase mb-1"><?php echo __('what_is_it'); ?></label><input type="text" name="title" class="w-full border p-2 rounded dark:bg-slate-800" required></div>
            <div><label class="block text-xs font-bold uppercase mb-1"><?php echo __('amount'); ?></label><input type="number" step="0.01" name="amount" class="w-full border p-2 rounded dark:bg-slate-800" required></div>
            <div><label class="block text-xs font-bold uppercase mb-1"><?php echo __('category'); ?></label><select name="category_id" class="w-full border p-2 rounded dark:bg-slate-800"><?php foreach($categories as $cat) echo "<option value='{$cat['id']}'>{$cat['name']}</option>"; ?></select></div>
            <div class="flex justify-end space-x-2 mt-6">
                <button type="button" onclick="document.getElementById('planModal').classList.add('hidden')" class="px-4 py-2 text-gray-500"><?php echo __('cancel'); ?></button>
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded"><?php echo __('save'); ?></button>
            </div>
        </form>
    </div>
</div>

<script>
    function openPlanModal() { document.getElementById('planModal').classList.remove('hidden'); document.getElementById('planModal').classList.add('flex'); }
    document.getElementById('add-plan-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const res = await fetch('../api/planner_action.php?action=add', { method: 'POST', body: new FormData(e.target) });
        if((await res.json()).success) location.reload();
    });
    async function markAsPaid(id) {
        if(confirm('OK?')) {
            await fetch(`../api/planner_action.php?action=pay&id=${id}`, { method: 'POST' });
            location.reload();
        }
    }
</script>
<?php require_once '../includes/footer.php'; ?>