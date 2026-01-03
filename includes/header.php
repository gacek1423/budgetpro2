<?php
// includes/header.php

// 1. Inicjalizacja Sesji
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Dołączanie plików bazowych (używamy __DIR__ dla pewności ścieżek)
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

// 3. Bezpieczeństwo - Sprawdzenie logowania
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// 4. Pobranie danych zalogowanego użytkownika
$db = db();
$stmt = $db->prepare("SELECT username, email, role, created_at FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

// Jeśli użytkownik nie istnieje (np. usunięty), wyloguj
if (!$currentUser) {
    session_destroy();
    header("Location: ../login.php");
    exit;
}

// 5. Konfiguracja strony
$pageTitle = isset($pageTitle) ? $pageTitle : 'BudgetPro';
?>
<!DOCTYPE html>
<html lang="pl" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - BudgetPro</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
			<?php 
		// Pobierz kolor użytkownika (jeśli nie ma funkcji getUserPreferences w tym scope, dodaj require functions.php wyżej)
		$userPrefs = getUserPreferences(); 
		$themeColor = $userPrefs['theme_color'] ?? 'blue';

		// Mapa kolorów hex dla Tailwinda (uproszczona)
		$colorsMap = [
			'blue' => '#3b82f6',   // blue-500
			'green' => '#10b981',  // emerald-500
			'purple' => '#9333ea', // purple-600
			'orange' => '#f97316'  // orange-500
		];
		$primaryHex = $colorsMap[$themeColor];
		?>

		<script>
			tailwind.config = {
				darkMode: 'class',
				theme: {
					extend: {
						colors: {
							dark: { bg: '#0f172a', card: '#1e293b', text: '#e2e8f0' },
							// Dynamiczny kolor brandowy
							brand: { 
								DEFAULT: '<?php echo $primaryHex; ?>',
								green: '#10b981', // Stary kolor (możesz zostawić)
								blue: '#3b82f6'
							}
						}
					}
				}
			}
		</script>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #0f172a; color: #e2e8f0; }
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #1e293b; }
        ::-webkit-scrollbar-thumb { background: #475569; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #64748b; }
        .nav-item.active { 
            background: linear-gradient(90deg, #10b981, #059669); 
            color: white; 
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3); 
        }
    </style>
</head>
<body class="flex h-screen overflow-hidden bg-dark-bg text-dark-text">

    <?php if (isset($_SESSION['original_admin_id'])): ?>
    <div class="fixed bottom-0 left-0 w-full bg-red-600 text-white px-6 py-3 shadow-[0_-5px_20px_rgba(220,38,38,0.5)] z-[100] flex justify-between items-center animate-pulse">
        <div class="flex items-center gap-4">
            <div class="bg-white text-red-600 rounded-full w-10 h-10 flex items-center justify-center">
                <i class="fas fa-user-secret text-xl"></i>
            </div>
            <div>
                <p class="font-bold uppercase text-xs tracking-wider opacity-80">Tryb Szpiega (Shadow Mode)</p>
                <p class="text-sm">Zalogowany jako: <span class="font-bold bg-black/20 px-2 rounded"><?php echo htmlspecialchars($currentUser['username']); ?></span></p>
            </div>
        </div>
        <a href="../admin/stop_impersonate.php" class="bg-white text-red-700 hover:bg-gray-100 px-6 py-2 rounded-full font-bold text-sm transition shadow-lg transform hover:scale-105 border-2 border-transparent hover:border-red-200">
            <i class="fas fa-sign-out-alt mr-2"></i> Zakończ Podgląd
        </a>
    </div>
    <script>document.body.classList.add('pb-16');</script>
    <?php endif; ?>

    <?php
    $announceFile = __DIR__ . '/../announcement.json';
    if (file_exists($announceFile)) {
        $ann = json_decode(file_get_contents($announceFile), true);
        if ($ann && $ann['active'] && !empty($ann['text'])) {
            $annId = md5($ann['text'] . $ann['type']);
            $styles = [
                'info' => ['icon' => 'fa-info-circle', 'color' => 'text-blue-500', 'btn' => 'bg-blue-600 hover:bg-blue-700'],
                'warning' => ['icon' => 'fa-exclamation-triangle', 'color' => 'text-yellow-500', 'btn' => 'bg-yellow-600 hover:bg-yellow-700'],
                'danger' => ['icon' => 'fa-radiation', 'color' => 'text-red-500', 'btn' => 'bg-red-600 hover:bg-red-700']
            ];
            $s = $styles[$ann['type']] ?? $styles['info'];
            ?>
            <div id="global-announcement" style="display: none;" class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/80 backdrop-blur-sm p-4 transition-opacity duration-300">
                <div class="bg-white dark:bg-slate-900 border border-slate-700 rounded-2xl shadow-2xl max-w-lg w-full text-center p-8 relative transform scale-100 transition-transform duration-300">
                    <div class="mb-6 mx-auto w-20 h-20 flex items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800 <?php echo $s['color']; ?>">
                        <i class="fas <?php echo $s['icon']; ?> text-5xl animate-pulse"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-2 uppercase tracking-wider">Komunikat Systemowy</h2>
                    <div class="text-gray-600 dark:text-gray-300 text-lg mb-8 font-medium leading-relaxed">
                        <?php echo htmlspecialchars($ann['text']); ?>
                    </div>
                    <button onclick="closeAnnouncement('<?php echo $annId; ?>')" class="<?php echo $s['btn']; ?> text-white px-8 py-3 rounded-full font-bold shadow-lg transition transform hover:scale-105 w-full md:w-auto focus:outline-none">
                        <i class="fas fa-check mr-2"></i> Zamknij
                    </button>
                </div>
            </div>
            <script>
                (function() {
                    const annId = "<?php echo $annId; ?>";
                    const storageKey = 'budgetpro_seen_' + annId;
                    if (!localStorage.getItem(storageKey)) {
                        document.getElementById('global-announcement').style.display = 'flex';
                    }
                })();
                function closeAnnouncement(id) {
                    const modal = document.getElementById('global-announcement');
                    localStorage.setItem('budgetpro_seen_' + id, 'true');
                    modal.classList.add('opacity-0');
                    setTimeout(() => { modal.remove(); }, 300);
                }
            </script>
            <?php
        }
    }
    ?>