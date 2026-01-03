<?php
require_once '../includes/session.php';
require_once '../includes/config.php';

// Zabezpieczenie: Tylko zalogowani (lub dodaj warunek isAdmin)
checkLogin();

$logFile = '../logs/app.log';

// Obsługa czyszczenia logów
if (isset($_POST['clear'])) {
    file_put_contents($logFile, '');
    header("Location: debug_logs.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Logi Systemowe</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-gray-300 font-mono text-sm p-6">
    <div class="max-w-6xl mx-auto">
        <div class="flex justify-between items-center mb-4">
            <h1 class="text-xl font-bold text-white">Debug Logi (app.log)</h1>
            <div class="space-x-2">
                <a href="dashboard.php" class="bg-gray-700 hover:bg-gray-600 px-4 py-2 rounded">Wróć</a>
                <form method="POST" style="display:inline;">
                    <button type="submit" name="clear" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded">Wyczyść logi</button>
                </form>
            </div>
        </div>

        <div class="bg-black border border-gray-700 rounded p-4 h-[80vh] overflow-auto whitespace-pre-wrap">
<?php
if (file_exists($logFile)) {
    $content = file_get_contents($logFile);
    // Kolorowanie składni dla lepszej czytelności
    $content = htmlspecialchars($content);
    $content = str_replace('[ERROR]', '<span class="text-red-500 font-bold">[ERROR]</span>', $content);
    $content = str_replace('[DEBUG]', '<span class="text-blue-400 font-bold">[DEBUG]</span>', $content);
    $content = str_replace('[INFO]', '<span class="text-green-400 font-bold">[INFO]</span>', $content);
    $content = str_replace('Stack Trace:', '<span class="text-yellow-500">Stack Trace:</span>', $content);
    echo $content ?: '<span class="text-gray-500">Plik logów jest pusty.</span>';
} else {
    echo '<span class="text-red-500">Plik logów nie istnieje.</span>';
}
?>
        </div>
        <p class="mt-2 text-xs text-gray-500">Ścieżka: <?php echo realpath($logFile); ?></p>
    </div>
    
    <script>
        // Automatyczne przewijanie na dół
        const container = document.querySelector('.overflow-auto');
        container.scrollTop = container.scrollHeight;
    </script>
</body>
</html>