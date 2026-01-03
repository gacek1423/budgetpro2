<?php
// admin/users.php
require_once 'layout.php';
$db = db();

// --- 1. OBSŁUGA USUWANIA UŻYTKOWNIKA ---
if (isset($_POST['delete_user_id'])) {
    $uid = (int)$_POST['delete_user_id'];
    
    // Zabezpieczenie: Nie pozwól usunąć samego siebie (Admina)
    if ($uid !== $_SESSION['user_id']) {
        // Usuwanie użytkownika (dzięki ON DELETE CASCADE w bazie, usunie też jego transakcje)
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$uid]);
        $msg = "Użytkownik został pomyślnie usunięty.";
        $msgType = "success";
    } else {
        $msg = "Nie możesz usunąć swojego konta administratora!";
        $msgType = "error";
    }
}

// --- 2. PAGINACJA I POBIERANIE DANYCH ---
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Pobieranie listy użytkowników + liczba ich transakcji (subquery)
// Używamy LEFT JOIN lub podzapytania. Podzapytanie jest czytelniejsze tutaj.
$stmt = $db->prepare("
    SELECT id, username, email, role, created_at, 
    (SELECT COUNT(*) FROM personal_transactions WHERE user_id = users.id) as tx_count
    FROM users 
    ORDER BY id DESC 
    LIMIT $perPage OFFSET $offset
");
$stmt->execute();
$users = $stmt->fetchAll();

// Pobranie całkowitej liczby userów do paginacji
$totalUsers = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalPages = ceil($totalUsers / $perPage);

renderHeader('Zarządzanie Użytkownikami');
?>

<div class="flex flex-col md:flex-row justify-between items-center mb-6">
    <div>
        <h2 class="text-2xl font-bold text-white">Użytkownicy Systemu</h2>
        <p class="text-slate-400 text-sm">Zarządzanie kontami i dostępem (Razem: <?php echo number_format($totalUsers); ?>)</p>
    </div>
    
    <div class="mt-4 md:mt-0">
        <input type="text" placeholder="Szukaj po email..." class="bg-slate-800 border border-slate-700 text-slate-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:border-blue-500">
    </div>
</div>

<?php if(isset($msg)): ?>
    <div class="<?php echo $msgType === 'success' ? 'bg-emerald-500/20 text-emerald-400 border-emerald-500/50' : 'bg-red-500/20 text-red-400 border-red-500/50'; ?> p-4 rounded-lg mb-6 border flex items-center">
        <i class="fas <?php echo $msgType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-3"></i> 
        <?php echo $msg; ?>
    </div>
<?php endif; ?>

<div class="bg-slate-800 rounded-xl border border-slate-700 overflow-hidden shadow-lg">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-slate-300">
            <thead class="bg-slate-900 text-xs uppercase font-bold text-slate-500">
                <tr>
                    <th class="p-4 w-16">ID</th>
                    <th class="p-4">Użytkownik</th>
                    <th class="p-4">Rola</th>
                    <th class="p-4">Statystyki</th>
                    <th class="p-4">Data Rejestracji</th>
                    <th class="p-4 text-right">Akcje</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-700">
                <?php foreach($users as $u): ?>
                <tr class="hover:bg-slate-700/50 transition duration-150">
                    
                    <td class="p-4 text-slate-500 font-mono text-xs">#<?php echo $u['id']; ?></td>
                    
                    <td class="p-4">
                        <div class="flex items-center">
                            <div class="w-8 h-8 rounded-full bg-slate-700 flex items-center justify-center text-slate-300 mr-3 font-bold uppercase">
                                <?php echo substr($u['username'], 0, 1); ?>
                            </div>
                            <div>
                                <div class="font-bold text-white"><?php echo htmlspecialchars($u['username']); ?></div>
                                <div class="text-xs text-slate-500"><?php echo htmlspecialchars($u['email']); ?></div>
                            </div>
                        </div>
                    </td>

                    <td class="p-4">
                        <?php if($u['role'] === 'admin'): ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium bg-purple-500/20 text-purple-400 border border-purple-500/30">
                                <i class="fas fa-shield-halved mr-1"></i> ADMIN
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium bg-slate-700 text-slate-400 border border-slate-600">
                                USER
                            </span>
                        <?php endif; ?>
                    </td>

                    <td class="p-4">
                        <span class="text-xs bg-blue-500/10 text-blue-400 px-2 py-1 rounded border border-blue-500/20">
                            <i class="fas fa-database mr-1"></i> <?php echo number_format($u['tx_count']); ?> wpisów
                        </span>
                    </td>

                    <td class="p-4 text-sm text-slate-400">
                        <?php echo date('d.m.Y H:i', strtotime($u['created_at'])); ?>
                    </td>

                    <td class="p-4 text-right">
                        <div class="flex justify-end items-center gap-2">
                            <?php if($u['id'] !== $_SESSION['user_id']): ?>
                                
                                <a href="impersonate.php?id=<?php echo $u['id']; ?>" 
                                   class="text-blue-400 hover:text-white hover:bg-blue-600 p-2 rounded transition" 
                                   title="Zaloguj jako ten użytkownik">
                                    <i class="fas fa-user-secret"></i>
                                </a>

                                <form method="POST" onsubmit="return confirm('Czy na pewno chcesz usunąć użytkownika <?php echo htmlspecialchars($u['username']); ?>? Tej operacji nie można cofnąć.');" style="margin:0;">
                                    <input type="hidden" name="delete_user_id" value="<?php echo $u['id']; ?>">
                                    <button type="submit" 
                                            class="text-red-400 hover:text-white hover:bg-red-600 p-2 rounded transition" 
                                            title="Usuń konto">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>

                            <?php else: ?>
                                <span class="text-slate-600 text-xs italic px-2">To Twoje konto</span>
                            <?php endif; ?>
                        </div>
                    </td>

                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <?php if($totalPages > 1): ?>
    <div class="p-4 bg-slate-900 border-t border-slate-700 flex justify-center items-center gap-2">
        <?php if($page > 1): ?>
            <a href="?page=<?php echo $page-1; ?>" class="px-3 py-1 bg-slate-700 text-slate-300 rounded hover:bg-blue-600 hover:text-white transition text-sm">
                <i class="fas fa-chevron-left mr-1"></i> Poprzednia
            </a>
        <?php endif; ?>

        <span class="text-xs text-slate-500 uppercase font-bold mx-2">
            Strona <?php echo $page; ?> z <?php echo $totalPages; ?>
        </span>

        <?php if($page < $totalPages): ?>
            <a href="?page=<?php echo $page+1; ?>" class="px-3 py-1 bg-slate-700 text-slate-300 rounded hover:bg-blue-600 hover:text-white transition text-sm">
                Następna <i class="fas fa-chevron-right ml-1"></i>
            </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

</div>

<?php renderFooter(); ?>