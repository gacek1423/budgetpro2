<?php
// admin/system.php
require_once 'layout.php';
$db = db();

$msg = '';

// --- AKCJA 1: OPTYMALIZACJA BAZY ---
if (isset($_POST['optimize_db'])) {
    // Lista tabel do naprawy
    $tables = ['personal_transactions', 'users', 'logs', 'categories']; // Dodaj swoje
    foreach ($tables as $t) {
        // OPTIMIZE TABLE to komenda MySQL porządkująca pliki fizyczne
        $db->query("OPTIMIZE TABLE `$t`");
    }
    $msg = "Baza danych została zoptymalizowana i zdefragmentowana.";
}

// --- AKCJA 2: TRYB KONSERWACJI ---
// Używamy prostego pliku tekstowego jako flagi
$maintenanceFile = '../maintenance.lock';
if (isset($_POST['toggle_maintenance'])) {
    if (file_exists($maintenanceFile)) {
        unlink($maintenanceFile); // Wyłącz
        $msg = "Tryb konserwacji WYŁĄCZONY. Użytkownicy mogą się logować.";
    } else {
        file_put_contents($maintenanceFile, "Maintenance Mode ON"); // Włącz
        $msg = "Tryb konserwacji WŁĄCZONY. Tylko admin może korzystać z systemu.";
    }
}

$isMaintenance = file_exists($maintenanceFile);

renderHeader('Zarządzanie Systemem');
?>

<div class="mb-8">
    <h2 class="text-2xl font-bold text-white mb-2">Stan Techniczny Systemu</h2>
    <p class="text-slate-400">Narzędzia do konserwacji i optymalizacji wydajności.</p>
</div>

<?php if($msg): ?>
    <div class="bg-blue-500/20 text-blue-400 p-4 rounded-lg mb-6 border border-blue-500/50 flex items-center">
        <i class="fas fa-info-circle mr-3 text-xl"></i> <?php echo $msg; ?>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">

    <div class="bg-slate-800 p-6 rounded-xl border border-slate-700">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-white"><i class="fas fa-tools text-yellow-500 mr-2"></i> Tryb Konserwacji</h3>
            <?php if($isMaintenance): ?>
                <span class="bg-red-500 text-white px-3 py-1 rounded-full text-xs font-bold animate-pulse">AKTYWNY</span>
            <?php else: ?>
                <span class="bg-emerald-500/20 text-emerald-500 px-3 py-1 rounded-full text-xs font-bold">NIEAKTYWNY</span>
            <?php endif; ?>
        </div>
        <p class="text-slate-400 text-sm mb-6">
            Gdy włączony, zwykli użytkownicy zobaczą ekran "Przerwa Techniczna" i nie będą mogli się zalogować. Admin nadal ma dostęp.
        </p>
        <form method="POST">
            <button type="submit" name="toggle_maintenance" class="w-full py-3 rounded-lg font-bold transition <?php echo $isMaintenance ? 'bg-emerald-600 hover:bg-emerald-700 text-white' : 'bg-red-600 hover:bg-red-700 text-white'; ?>">
                <?php echo $isMaintenance ? 'Wyłącz Tryb Konserwacji' : 'Włącz Tryb Konserwacji'; ?>
            </button>
        </form>
    </div>

    <div class="bg-slate-800 p-6 rounded-xl border border-slate-700">
        <h3 class="text-xl font-bold text-white mb-4"><i class="fas fa-database text-blue-500 mr-2"></i> Higiena Bazy Danych</h3>
        <p class="text-slate-400 text-sm mb-4">
            Uruchamia komendę <code>OPTIMIZE TABLE</code> na głównych tabelach. Zalecane raz w miesiącu przy dużej liczbie transakcji.
        </p>
        <div class="bg-slate-900 p-3 rounded mb-6 text-xs font-mono text-slate-500">
            > OPTIMIZE TABLE personal_transactions...<br>
            > OPTIMIZE TABLE users...<br>
            > Rebuilding indexes...
        </div>
        <form method="POST">
            <button type="submit" name="optimize_db" class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-bold transition flex items-center justify-center">
                <i class="fas fa-broom mr-2"></i> Optymalizuj Bazę
            </button>
        </form>
    </div>

</div>

<?php renderFooter(); ?>