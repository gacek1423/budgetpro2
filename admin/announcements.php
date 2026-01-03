<?php
// admin/announcements.php
require_once 'layout.php';

// Plik do przechowywania ogłoszenia (prosta baza plikowa wystarczy)
$announceFile = '../announcement.json';

if (isset($_POST['save_announcement'])) {
    $data = [
        'text' => trim($_POST['text']),
        'type' => $_POST['type'], // info, warning, danger
        'active' => isset($_POST['active'])
    ];
    file_put_contents($announceFile, json_encode($data));
    $msg = "Zapisano ogłoszenie.";
}

// Odczyt
$current = file_exists($announceFile) ? json_decode(file_get_contents($announceFile), true) : ['text'=>'', 'type'=>'info', 'active'=>false];

renderHeader('Ogłoszenia Globalne');
?>

<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-white">Komunikaty dla Użytkowników</h2>
        <p class="text-slate-400">Wiadomość wpisana tutaj pojawi się na górze każdej podstrony.</p>
    </div>

    <?php if(isset($msg)): ?>
        <div class="bg-emerald-500/20 text-emerald-400 p-3 rounded mb-4"><?php echo $msg; ?></div>
    <?php endif; ?>

    <div class="bg-slate-800 p-6 rounded-xl border border-slate-700">
        <form method="POST">
            <div class="mb-4">
                <label class="block text-slate-400 text-sm font-bold mb-2">Treść komunikatu</label>
                <textarea name="text" rows="3" class="w-full bg-slate-900 border border-slate-700 rounded-lg p-3 text-white focus:border-blue-500 outline-none"><?php echo htmlspecialchars($current['text']); ?></textarea>
            </div>

            <div class="mb-4">
                <label class="block text-slate-400 text-sm font-bold mb-2">Typ komunikatu</label>
                <div class="flex gap-4">
                    <label class="flex items-center cursor-pointer">
                        <input type="radio" name="type" value="info" <?php echo $current['type']=='info'?'checked':''; ?> class="mr-2">
                        <span class="text-blue-400 font-bold">Info (Niebieski)</span>
                    </label>
                    <label class="flex items-center cursor-pointer">
                        <input type="radio" name="type" value="warning" <?php echo $current['type']=='warning'?'checked':''; ?> class="mr-2">
                        <span class="text-yellow-500 font-bold">Ostrzeżenie (Żółty)</span>
                    </label>
                    <label class="flex items-center cursor-pointer">
                        <input type="radio" name="type" value="danger" <?php echo $current['type']=='danger'?'checked':''; ?> class="mr-2">
                        <span class="text-red-500 font-bold">Awaria (Czerwony)</span>
                    </label>
                </div>
            </div>

            <div class="mb-6">
                <label class="flex items-center cursor-pointer bg-slate-900 p-3 rounded-lg border border-slate-700">
                    <input type="checkbox" name="active" <?php echo $current['active']?'checked':''; ?> class="w-5 h-5 rounded text-blue-600">
                    <span class="ml-3 text-white font-bold">Pokaż komunikat użytkownikom</span>
                </label>
            </div>

            <button type="submit" name="save_announcement" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg transition">
                Zapisz Zmiany
            </button>
        </form>
    </div>

    <div class="mt-8">
        <h3 class="text-slate-500 text-sm font-bold uppercase mb-2">Podgląd na żywo:</h3>
        <?php 
            $colors = ['info'=>'bg-blue-600', 'warning'=>'bg-yellow-600', 'danger'=>'bg-red-600'];
            $bg = $colors[$current['type']];
        ?>
        <div class="<?php echo $bg; ?> text-white p-4 rounded-lg shadow-lg text-center font-medium">
            <?php echo htmlspecialchars($current['text']) ?: 'Tu pojawi się tekst...'; ?>
        </div>
    </div>
</div>

<?php renderFooter(); ?>