<?php
// admin/logs.php
require_once 'layout.php';
$db = db();

// Pobieramy 50 ostatnich operacji finansowych z CAŁEGO systemu
$stmt = $db->prepare("
    SELECT t.*, u.username, c.name as cat_name 
    FROM personal_transactions t
    JOIN users u ON t.user_id = u.id
    LEFT JOIN categories c ON t.category_id = c.id
    ORDER BY t.created_at DESC 
    LIMIT 50
");
$stmt->execute();
$logs = $stmt->fetchAll();

renderHeader('Logi Systemowe');
?>

<div class="mb-6">
    <h2 class="text-2xl font-bold text-white">Globalny Strumień Aktywności</h2>
    <p class="text-slate-400">Podgląd na żywo 50 ostatnich operacji w systemie.</p>
</div>

<div class="bg-slate-800 rounded-xl border border-slate-700 overflow-hidden shadow-lg">
    <table class="w-full text-left text-sm text-slate-300">
        <thead class="bg-slate-900 uppercase font-bold text-slate-500 text-xs">
            <tr>
                <th class="p-3">Czas</th>
                <th class="p-3">Użytkownik</th>
                <th class="p-3">Akcja</th>
                <th class="p-3 text-right">Kwota</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-700 font-mono">
            <?php foreach($logs as $log): ?>
            <tr class="hover:bg-slate-700/50">
                <td class="p-3 text-slate-500">
                    <?php echo $log['created_at']; ?>
                </td>
                <td class="p-3 font-bold text-blue-400">
                    <?php echo htmlspecialchars($log['username']); ?>
                </td>
                <td class="p-3">
                    <?php if($log['type'] === 'income'): ?>
                        <span class="text-emerald-500">Dodał przychód</span>
                    <?php else: ?>
                        <span class="text-red-400">Dodał wydatek</span>
                    <?php endif; ?>
                    <span class="text-slate-500">w kat. <?php echo htmlspecialchars($log['cat_name']); ?></span>
                    <div class="text-xs text-slate-600 italic"><?php echo htmlspecialchars($log['description']); ?></div>
                </td>
                <td class="p-3 text-right font-bold <?php echo $log['type'] === 'income' ? 'text-emerald-400' : 'text-red-400'; ?>">
                    <?php echo $log['type'] === 'income' ? '+' : '-'; ?>
                    <?php echo number_format($log['amount'], 2); ?> zł
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php renderFooter(); ?>