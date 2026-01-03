<?php
// pages/transactions.php
require_once '../includes/db.php';
require_once '../includes/functions.php';

$pageTitle = __('transactions_history');
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/topbar.php';

$user_id = getCurrentUserId();
$db = db();

// --- KONFIGURACJA ---
$type_filter = $_GET['type'] ?? 'all';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 20; 
$offset = ($page - 1) * $limit;

$whereClause = "WHERE t.user_id = ?";
$params = [$user_id];

if ($type_filter === 'income') $whereClause .= " AND t.type = 'income'";
elseif ($type_filter === 'expense') $whereClause .= " AND t.type = 'expense'";

$sql = "SELECT t.*, c.name as cat_name, c.color as cat_color, c.icon as cat_icon 
        FROM personal_transactions t 
        LEFT JOIN categories c ON t.category_id = c.id 
        $whereClause 
        ORDER BY t.transaction_date DESC, t.id DESC LIMIT $limit OFFSET $offset";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$countStmt = $db->prepare("SELECT COUNT(*) FROM personal_transactions t $whereClause");
$countStmt->execute($params);
$total_rows = $countStmt->fetchColumn();
$total_pages = ceil($total_rows / $limit);
?>

<div class="bg-white dark:bg-dark-card shadow-sm rounded-xl overflow-hidden border dark:border-dark-border">
    <div class="p-4 border-b dark:border-dark-border flex flex-col md:flex-row justify-between items-center gap-4">
        <h2 class="text-xl font-bold text-gray-800 dark:text-white">
            <?php echo __('all_transactions'); ?> <span class="text-sm font-normal text-gray-500 dark:text-dark-muted">(<?php echo $total_rows; ?>)</span>
        </h2>
        
        <div class="flex bg-slate-100 dark:bg-slate-700 p-1 rounded-lg">
            <a href="?type=all" class="px-4 py-2 rounded text-sm transition font-medium <?php echo $type_filter == 'all' ? 'bg-white dark:bg-slate-600 shadow text-slate-800 dark:text-white' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700'; ?>">
                <?php echo __('filter_all'); ?>
            </a>
            <a href="?type=income" class="px-4 py-2 rounded text-sm transition font-medium <?php echo $type_filter == 'income' ? 'bg-white dark:bg-slate-600 shadow text-green-600 dark:text-green-400' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700'; ?>">
                <?php echo __('filter_income'); ?>
            </a>
            <a href="?type=expense" class="px-4 py-2 rounded text-sm transition font-medium <?php echo $type_filter == 'expense' ? 'bg-white dark:bg-slate-600 shadow text-red-600 dark:text-red-400' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700'; ?>">
                <?php echo __('filter_expense'); ?>
            </a>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 dark:bg-slate-800/50 text-slate-500 dark:text-dark-muted uppercase text-xs">
                <tr>
                    <th class="px-6 py-4"><?php echo __('date'); ?></th>
                    <th class="px-6 py-4"><?php echo __('category'); ?></th>
                    <th class="px-6 py-4"><?php echo __('description'); ?></th>
                    <th class="px-6 py-4 text-right"><?php echo __('amount'); ?></th>
                    <th class="px-6 py-4 text-center"><?php echo __('actions'); ?></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-dark-border">
                <?php if (empty($transactions)): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-10 text-center text-gray-400 dark:text-slate-500">
                            <i class="fas fa-inbox text-4xl mb-3"></i><br><?php echo __('no_data'); ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($transactions as $t): 
                        $catColor = $t['cat_color'] ?? '#94a3b8';
                        $catName = $t['cat_name'] ?? __('category'); // fallback
                    ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition group">
                        <td class="px-6 py-4 text-slate-500 dark:text-slate-400 whitespace-nowrap"><?php echo formatDate($t['transaction_date']); ?></td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium text-white shadow-sm whitespace-nowrap" style="background-color: <?php echo $catColor; ?>;">
                                <i class="fas <?php echo $t['cat_icon'] ?? 'fa-circle'; ?> mr-1.5"></i> <?php echo htmlspecialchars($catName); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-slate-700 dark:text-slate-300">
                            <div class="flex items-center">
                                <span class="truncate max-w-[200px]" title="<?php echo htmlspecialchars($t['description']); ?>"><?php echo htmlspecialchars($t['description'] ?: '-'); ?></span>
                                <?php if(!empty($t['receipt_path'])): ?>
                                    <button onclick="showReceipt('<?php echo $t['receipt_path']; ?>')" class="ml-2 text-blue-500 hover:text-blue-600 bg-blue-50 dark:bg-blue-900/30 p-1.5 rounded-full transition" title="<?php echo __('receipt_view'); ?>"><i class="fas fa-paperclip text-xs"></i></button>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="font-bold text-base <?php echo $t['type'] === 'income' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'; ?>">
                                <?php echo ($t['type'] === 'income' ? '+' : '-') . ' ' . formatCurrency($t['amount']); ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <button onclick="deleteTrans(<?php echo $t['id']; ?>)" class="text-slate-300 hover:text-red-500 transition p-2" title="<?php echo __('delete'); ?>">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="receiptModal" class="fixed inset-0 bg-black/90 hidden items-center justify-center z-[60] p-4 cursor-pointer backdrop-blur-sm" onclick="closeReceiptModal()">
    <div class="relative max-w-5xl max-h-full" onclick="event.stopPropagation()">
        <img id="receipt-img" src="" class="max-h-[85vh] max-w-full rounded shadow-2xl object-contain bg-white">
        <button onclick="closeReceiptModal()" class="absolute -top-12 right-0 text-white/70 hover:text-white text-4xl transition">&times;</button>
        <a id="receipt-download" href="" download class="absolute bottom-4 right-4 bg-white/90 hover:bg-white text-gray-800 px-4 py-2 rounded shadow text-sm font-bold flex items-center">
            <i class="fas fa-download mr-2"></i> <?php echo __('download'); ?>
        </a>
    </div>
</div>

<script>
    async function deleteTrans(id) {
        if(confirm('<?php echo __('delete_confirm'); ?>')) {
            try {
                const res = await fetch(`../api/delete_transaction.php?id=${id}&type=personal`, { method: 'DELETE' });
                if((await res.json()).success) { showToast('OK', 'success'); setTimeout(() => location.reload(), 500); }
            } catch(e) { showToast('Error', 'error'); }
        }
    }
    function showReceipt(path) {
        document.getElementById('receipt-img').src = '../' + path;
        document.getElementById('receipt-download').href = '../' + path;
        document.getElementById('receiptModal').classList.remove('hidden');
        document.getElementById('receiptModal').classList.add('flex');
    }
    function closeReceiptModal() {
        document.getElementById('receiptModal').classList.add('hidden');
        document.getElementById('receiptModal').classList.remove('flex');
    }
</script>
<?php require_once '../includes/footer.php'; ?>