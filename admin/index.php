<?php
// admin/index.php
require_once 'layout.php';
$db = db();

// --- POBIERANIE STATYSTYK ---

// 1. Liczba użytkowników
$countUsers = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();

// 2. Liczba transakcji (Formatowana, bo może być 10mln)
$countTrans = $db->query("SELECT COUNT(*) FROM personal_transactions")->fetchColumn();

// 3. Rozmiar Bazy Danych (MB)
$sqlSize = "SELECT table_schema 'db', SUM(data_length + index_length) / 1024 / 1024 'size' 
            FROM information_schema.tables WHERE table_schema = 'budgetpro' GROUP BY table_schema";
$dbSize = $db->query($sqlSize)->fetchColumn(1);

// 4. Nowi użytkownicy (Ostatnie 7 dni)
$newUsers = $db->query("SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();

renderHeader('Dashboard');
?>

<div class="mb-8">
    <h2 class="text-3xl font-bold text-white mb-2">Witaj, Administratorze 👋</h2>
    <p class="text-slate-400">Oto co dzieje się dzisiaj w systemie BudgetPro.</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="bg-slate-800 p-6 rounded-xl border border-slate-700 shadow-lg">
        <div class="text-slate-400 text-sm font-bold uppercase mb-1">Użytkownicy</div>
        <div class="text-3xl font-bold text-white"><?php echo number_format($countUsers); ?></div>
        <div class="text-emerald-400 text-xs mt-2"><i class="fas fa-arrow-up"></i> +<?php echo $newUsers; ?> w tym tyg.</div>
    </div>
    
    <div class="bg-slate-800 p-6 rounded-xl border border-slate-700 shadow-lg">
        <div class="text-slate-400 text-sm font-bold uppercase mb-1">Transakcje</div>
        <div class="text-3xl font-bold text-blue-400"><?php echo number_format($countTrans); ?></div>
        <div class="text-slate-500 text-xs mt-2">Rekordów w tabeli</div>
    </div>

    <div class="bg-slate-800 p-6 rounded-xl border border-slate-700 shadow-lg">
        <div class="text-slate-400 text-sm font-bold uppercase mb-1">Rozmiar Bazy</div>
        <div class="text-3xl font-bold text-purple-400"><?php echo number_format($dbSize, 2); ?> MB</div>
        <div class="text-slate-500 text-xs mt-2">Zajętość dysku</div>
    </div>

    <div class="bg-slate-800 p-6 rounded-xl border border-slate-700 shadow-lg">
        <div class="text-slate-400 text-sm font-bold uppercase mb-1">Status Systemu</div>
        <div class="text-3xl font-bold text-emerald-500">Online</div>
        <div class="text-slate-500 text-xs mt-2">PHP <?php echo phpversion(); ?> / Laragon</div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-slate-800 p-6 rounded-xl border border-slate-700">
        <h3 class="text-xl font-bold text-white mb-4">Wzrost Użytkowników</h3>
        <div class="h-64 bg-slate-900/50 rounded-lg flex items-center justify-center border border-dashed border-slate-700">
            <p class="text-slate-500"><i class="fas fa-chart-area mr-2"></i>Tu będzie Chart.js</p>
        </div>
    </div>

    <div class="bg-slate-800 p-6 rounded-xl border border-slate-700">
        <h3 class="text-xl font-bold text-white mb-4">Narzędzia Administracyjne</h3>
        <div class="space-y-3">
            <a href="../api/seed_control_panel.php" target="_blank" class="block p-4 bg-slate-700 hover:bg-slate-600 rounded-lg transition flex items-center justify-between group">
                <span class="text-white font-bold"><i class="fas fa-database text-yellow-500 mr-2"></i> Generator Danych (Seed)</span>
                <i class="fas fa-external-link-alt text-slate-500 group-hover:text-white"></i>
            </a>
            <div class="block p-4 bg-slate-700 rounded-lg opacity-50 cursor-not-allowed flex items-center justify-between">
                <span class="text-white font-bold"><i class="fas fa-broom text-red-500 mr-2"></i> Wyczyść Cache (Wkrótce)</span>
                <i class="fas fa-lock text-slate-500"></i>
            </div>
            <div class="block p-4 bg-slate-700 rounded-lg opacity-50 cursor-not-allowed flex items-center justify-between">
                <span class="text-white font-bold"><i class="fas fa-server text-blue-500 mr-2"></i> Optymalizacja Tabel (Wkrótce)</span>
                <i class="fas fa-lock text-slate-500"></i>
            </div>
        </div>
    </div>
</div>

<?php renderFooter(); ?>