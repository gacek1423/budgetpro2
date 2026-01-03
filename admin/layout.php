<?php
// admin/layout.php
session_start();
require_once '../includes/db.php';

// 1. BEZPIECZEŃSTWO: Sprawdź czy user jest zalogowany
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// 2. BEZPIECZEŃSTWO: Sprawdź czy user ma rolę ADMIN
// Pobieramy rolę z bazy przy każdym odświeżeniu (dla bezpieczeństwa)
$db = db();
$stmt = $db->prepare("SELECT username, email, role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$currentUser = $stmt->fetch();

if (!$currentUser || $currentUser['role'] !== 'admin') {
    // Intruz! Wyrzucamy do dashboardu zwykłego usera
    header("Location: ../pages/dashboard.php");
    exit;
}

// Funkcja renderująca początek strony (Head + Sidebar)
function renderHeader($title) {
    global $currentUser;
?>
<!DOCTYPE html>
<html lang="pl" class="dark">
<head>
    <meta charset="UTF-8">
    <title><?php echo $title; ?> | Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = { darkMode: 'class', theme: { extend: { colors: { dark: { bg: '#0f172a', card: '#1e293b' } } } } }
    </script>
    <style>
        body { background-color: #0f172a; color: #e2e8f0; font-family: 'Segoe UI', sans-serif; }
        .nav-item.active { background: #3b82f6; color: white; }
    </style>
</head>
<body class="flex min-h-screen">

    <aside class="w-64 bg-slate-900 border-r border-slate-700 flex flex-col fixed h-full z-10">
        <div class="p-6 border-b border-slate-700">
            <h1 class="text-xl font-bold text-white"><i class="fas fa-shield-halved text-blue-500 mr-2"></i>AdminPro</h1>
            <p class="text-xs text-slate-500 mt-1">Zarządzanie BudgetPro</p>
        </div>

        <nav class="flex-1 p-4 space-y-2">
            <a href="index.php" class="nav-item flex items-center p-3 rounded-lg text-slate-400 hover:bg-slate-800 hover:text-white transition <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-line w-6 text-center"></i> <span class="ml-3">Dashboard</span>
            </a>
            <a href="users.php" class="nav-item flex items-center p-3 rounded-lg text-slate-400 hover:bg-slate-800 hover:text-white transition <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
                <i class="fas fa-users w-6 text-center"></i> <span class="ml-3">Użytkownicy</span>
            </a>
            <a href="logs.php" class="nav-item flex items-center p-3 rounded-lg text-slate-400 hover:bg-slate-800 hover:text-white transition <?php echo basename($_SERVER['PHP_SELF']) == 'logs.php' ? 'active' : ''; ?>">
                <i class="fas fa-list-ul w-6 text-center"></i> <span class="ml-3">Logi Systemowe</span>
            </a>
			<a href="system.php" class="nav-item flex items-center p-3 rounded-lg text-slate-400 hover:bg-slate-800 hover:text-white transition <?php echo basename($_SERVER['PHP_SELF']) == 'system.php' ? 'active' : ''; ?>">
				<i class="fas fa-server w-6 text-center"></i> <span class="ml-3">System i Baza</span>
			</a>
			<a href="announcements.php" class="nav-item flex items-center p-3 rounded-lg text-slate-400 hover:bg-slate-800 hover:text-white transition <?php echo basename($_SERVER['PHP_SELF']) == 'announcements.php' ? 'active' : ''; ?>">
				<i class="fas fa-bullhorn w-6 text-center"></i> <span class="ml-3">Ogłoszenia</span>
			</a>
            <hr class="border-slate-700 my-4">
            <a href="../pages/dashboard.php" class="flex items-center p-3 rounded-lg text-emerald-500 hover:bg-emerald-500/10 transition">
                <i class="fas fa-reply w-6 text-center"></i> <span class="ml-3">Wróć do Aplikacji</span>
            </a>
            <a href="../logout.php" class="flex items-center p-3 rounded-lg text-red-400 hover:bg-red-500/10 transition">
                <i class="fas fa-sign-out-alt w-6 text-center"></i> <span class="ml-3">Wyloguj</span>
            </a>
        </nav>

        <div class="p-4 border-t border-slate-700 text-xs text-slate-500 text-center">
            Zalogowany jako: <span class="text-white font-bold"><?php echo htmlspecialchars($currentUser['username']); ?></span>
        </div>
    </aside>

    <main class="flex-1 ml-64 p-8">
<?php
}

// Funkcja zamykająca stronę
function renderFooter() {
?>
    </main>
</body>
</html>
<?php
}
?>