<?php
$pageTitle = "Ustawienia";
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/topbar.php';

$user_id = getCurrentUserId();
$db = db();

// Pobierz aktualne dane usera
$stmt = $db->prepare("SELECT username, email, is_2fa_enabled, currency_format, date_format, language, theme_color, start_page FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<div class="max-w-5xl mx-auto">
    
    <div class="mb-8">
        <h2 class="text-3xl font-bold text-gray-800 dark:text-white">Ustawienia Konta</h2>
        <p class="text-gray-500 dark:text-gray-400">Zarządzaj swoim profilem, bezpieczeństwem i preferencjami.</p>
    </div>

    <div class="flex flex-col lg:flex-row gap-8">
        
        <div class="w-full lg:w-64 flex-shrink-0">
            <nav class="flex flex-row lg:flex-col gap-2 overflow-x-auto lg:overflow-visible pb-4 lg:pb-0">
                <button onclick="switchTab('profile')" id="tab-btn-profile" class="tab-btn active flex items-center px-4 py-3 rounded-lg text-sm font-bold text-left transition w-full whitespace-nowrap">
                    <i class="fas fa-user-circle w-6 text-lg mr-2"></i> Profil
                </button>
                <button onclick="switchTab('security')" id="tab-btn-security" class="tab-btn flex items-center px-4 py-3 rounded-lg text-sm font-bold text-left transition w-full whitespace-nowrap">
                    <i class="fas fa-shield-halved w-6 text-lg mr-2"></i> Bezpieczeństwo
                </button>
                <button onclick="switchTab('preferences')" id="tab-btn-preferences" class="tab-btn flex items-center px-4 py-3 rounded-lg text-sm font-bold text-left transition w-full whitespace-nowrap">
                    <i class="fas fa-sliders-h w-6 text-lg mr-2"></i> Preferencje
                </button>
                <button onclick="switchTab('danger')" id="tab-btn-danger" class="tab-btn flex items-center px-4 py-3 rounded-lg text-sm font-bold text-left transition w-full text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 whitespace-nowrap">
                    <i class="fas fa-exclamation-triangle w-6 text-lg mr-2"></i> Strefa Ryzyka
                </button>
            </nav>
        </div>

        <div class="flex-1">
            
            <div id="tab-profile" class="tab-content block space-y-6">
                
                <div class="bg-white dark:bg-dark-card p-6 rounded-xl shadow-sm border dark:border-slate-700 flex items-center gap-6">
                    <div class="w-20 h-20 rounded-full bg-gradient-to-tr from-brand-green to-blue-500 flex items-center justify-center text-3xl text-white font-bold shadow-lg">
                        <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold dark:text-white">Twoje zdjęcie</h3>
                        <p class="text-sm text-gray-500 mb-3">Awatar jest generowany automatycznie z inicjałów.</p>
                        <button class="text-xs bg-slate-100 dark:bg-slate-700 px-3 py-1 rounded border dark:border-slate-600 opacity-50 cursor-not-allowed">Zmień (Wkrótce)</button>
                    </div>
                </div>

                <div class="bg-white dark:bg-dark-card p-6 rounded-xl shadow-sm border dark:border-slate-700">
                    <h3 class="text-lg font-bold dark:text-white mb-4">Dane Osobowe</h3>
                    <form id="profile-form" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Nazwa Użytkownika</label>
                                <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" class="w-full border p-3 rounded-lg dark:bg-slate-800 dark:border-slate-600 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Adres Email</label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="w-full border p-3 rounded-lg dark:bg-slate-800 dark:border-slate-600 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none">
                            </div>
                        </div>
                        <div class="flex justify-end pt-2">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-bold transition shadow">
                                Zapisz Zmiany
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div id="tab-security" class="tab-content hidden space-y-6">
                
                <div class="bg-white dark:bg-dark-card p-6 rounded-xl shadow-sm border dark:border-slate-700">
                    <h3 class="text-lg font-bold dark:text-white mb-4">Zmiana Hasła</h3>
                    <form id="password-form" class="space-y-4 max-w-md">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Obecne hasło</label>
                            <input type="password" name="current_password" class="w-full border p-3 rounded-lg dark:bg-slate-800 dark:border-slate-600 focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Nowe hasło</label>
                            <input type="password" name="new_password" class="w-full border p-3 rounded-lg dark:bg-slate-800 dark:border-slate-600 focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Powtórz nowe hasło</label>
                            <input type="password" name="confirm_password" class="w-full border p-3 rounded-lg dark:bg-slate-800 dark:border-slate-600 focus:ring-2 focus:ring-blue-500">
                        </div>
                        <button type="submit" class="bg-slate-800 hover:bg-black dark:bg-slate-700 dark:hover:bg-slate-600 text-white px-6 py-2 rounded-lg font-bold transition">
                            Zmień Hasło
                        </button>
                    </form>
                </div>

                <div class="bg-white dark:bg-dark-card p-6 rounded-xl shadow-sm border dark:border-slate-700">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="text-lg font-bold dark:text-white flex items-center">
                                Weryfikacja Dwuetapowa (2FA)
                                <?php if($user['is_2fa_enabled']): ?>
                                    <span class="ml-3 bg-green-100 text-green-700 text-xs px-2 py-1 rounded-full">AKTYWNA</span>
                                <?php else: ?>
                                    <span class="ml-3 bg-red-100 text-red-700 text-xs px-2 py-1 rounded-full">NIEAKTYWNA</span>
                                <?php endif; ?>
                            </h3>
                            <p class="text-sm text-gray-500 mt-1">Zabezpiecz konto kodem z aplikacji Google Authenticator.</p>
                        </div>
                        <?php if(!$user['is_2fa_enabled']): ?>
                            <button onclick="start2FA()" class="text-blue-600 hover:text-blue-700 font-bold text-sm bg-blue-50 px-3 py-2 rounded-lg">Konfiguruj</button>
                        <?php else: ?>
                            <button onclick="disable2FA()" class="text-red-600 hover:text-red-700 font-bold text-sm bg-red-50 px-3 py-2 rounded-lg">Wyłącz</button>
                        <?php endif; ?>
                    </div>

                    <div id="setup-2fa-box" class="hidden mt-6 border-t dark:border-slate-600 pt-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div class="text-center">
                                <p class="text-xs font-bold mb-2 uppercase text-gray-500">1. Zeskanuj Kod</p>
                                <img id="qr-img" src="" class="mx-auto w-32 h-32 border p-2 rounded">
                                <p id="manual-secret" class="mt-2 font-mono text-xs bg-gray-100 dark:bg-slate-800 p-1 rounded"></p>
                            </div>
                            <div>
                                <p class="text-xs font-bold mb-2 uppercase text-gray-500">2. Potwierdź Kodem</p>
                                <input type="text" id="verify-code" class="w-full text-center text-2xl tracking-widest border p-3 rounded-lg dark:bg-slate-800 mb-3" placeholder="000 000" maxlength="6">
                                <button onclick="confirm2FA()" class="w-full bg-green-600 text-white font-bold py-2 rounded-lg hover:bg-green-700 transition">Aktywuj</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="tab-preferences" class="tab-content hidden space-y-6">
    <div class="bg-white dark:bg-dark-card p-6 rounded-xl shadow-sm border dark:border-slate-700">
        <h3 class="text-lg font-bold dark:text-white mb-6">Personalizacja Systemu</h3>
        
        <form id="preferences-form" class="space-y-6">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 border-b dark:border-slate-700 pb-6">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Tryb Nocny</label>
                    <button type="button" onclick="toggleDarkMode()" class="flex items-center w-full p-3 border rounded-lg dark:bg-slate-800 dark:border-slate-600 hover:bg-slate-50 transition">
                        <div class="w-10 h-6 bg-slate-300 dark:bg-blue-600 rounded-full relative transition-colors duration-300 mr-3">
                            <div class="w-4 h-4 bg-white rounded-full absolute top-1 left-1 dark:left-5 transition-all duration-300 shadow-sm"></div>
                        </div>
                        <span class="text-sm font-bold dark:text-white">Przełącz motyw</span>
                    </button>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Kolor Wiodący</label>
                    <div class="flex gap-3">
                        <?php 
                        $colors = ['blue' => 'bg-blue-600', 'green' => 'bg-emerald-500', 'purple' => 'bg-purple-600', 'orange' => 'bg-orange-500'];
                        foreach($colors as $name => $bgClass): 
                            $activeRing = ($user['theme_color'] === $name) ? 'ring-2 ring-offset-2 ring-gray-400 dark:ring-white' : '';
                        ?>
                        <label class="cursor-pointer">
                            <input type="radio" name="theme_color" value="<?php echo $name; ?>" class="hidden" <?php echo ($user['theme_color'] === $name) ? 'checked' : ''; ?>>
                            <div class="w-10 h-10 rounded-full <?php echo $bgClass; ?> <?php echo $activeRing; ?> hover:opacity-80 transition shadow-sm flex items-center justify-center text-white">
                                <?php if($user['theme_color'] === $name): ?><i class="fas fa-check"></i><?php endif; ?>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 border-b dark:border-slate-700 pb-6">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Format Waluty</label>
                    <select name="currency_format" class="w-full border p-3 rounded-lg dark:bg-slate-800 dark:border-slate-600 dark:text-white focus:ring-2 focus:ring-blue-500">
                        <option value="pl" <?php echo $user['currency_format'] == 'pl' ? 'selected' : ''; ?>>1 234,56 zł (Polski)</option>
                        <option value="en" <?php echo $user['currency_format'] == 'en' ? 'selected' : ''; ?>>1,234.56 PLN (Międzynarodowy)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Format Daty</label>
                    <select name="date_format" class="w-full border p-3 rounded-lg dark:bg-slate-800 dark:border-slate-600 dark:text-white focus:ring-2 focus:ring-blue-500">
                        <option value="Y-m-d" <?php echo $user['date_format'] == 'Y-m-d' ? 'selected' : ''; ?>>2023-12-31 (ISO)</option>
                        <option value="d.m.Y" <?php echo $user['date_format'] == 'd.m.Y' ? 'selected' : ''; ?>>31.12.2023 (Polski)</option>
                        <option value="d/m/Y" <?php echo $user['date_format'] == 'd/m/Y' ? 'selected' : ''; ?>>31/12/2023 (UK)</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Język Interfejsu</label>
                    <select name="language" class="w-full border p-3 rounded-lg dark:bg-slate-800 dark:border-slate-600 dark:text-white focus:ring-2 focus:ring-blue-500">
                        <option value="pl" <?php echo $user['language'] == 'pl' ? 'selected' : ''; ?>>🇵🇱 Polski</option>
                        <option value="en" <?php echo $user['language'] == 'en' ? 'selected' : ''; ?>>🇬🇧 English</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Strona Startowa</label>
                    <select name="start_page" class="w-full border p-3 rounded-lg dark:bg-slate-800 dark:border-slate-600 dark:text-white focus:ring-2 focus:ring-blue-500">
                        <option value="dashboard.php" <?php echo $user['start_page'] == 'dashboard.php' ? 'selected' : ''; ?>>Pulpit (Dashboard)</option>
                        <option value="transactions.php" <?php echo $user['start_page'] == 'transactions.php' ? 'selected' : ''; ?>>Transakcje</option>
                        <option value="reports.php" <?php echo $user['start_page'] == 'reports.php' ? 'selected' : ''; ?>>Raporty</option>
                        <option value="planner.php" <?php echo $user['start_page'] == 'planner.php' ? 'selected' : ''; ?>>Planer</option>
                    </select>
                </div>
            </div>

            <div class="flex justify-end pt-4">
                <button type="submit" class="bg-slate-800 hover:bg-black dark:bg-blue-600 dark:hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-bold transition shadow">
                    Zapisz Preferencje
                </button>
            </div>
        </form>
    </div>
</div>

            <div id="tab-danger" class="tab-content hidden space-y-6">
                <div class="bg-red-50 dark:bg-red-900/10 border border-red-200 dark:border-red-800 rounded-xl p-6">
                    <h3 class="text-lg font-bold text-red-600 mb-2">Strefa Niebezpieczna</h3>
                    <p class="text-sm text-red-800 dark:text-red-300 mb-6">Operacje tutaj są nieodwracalne. Uważaj co klikasz.</p>

                    <div class="space-y-4">
                        <div class="flex items-center justify-between bg-white dark:bg-slate-800 p-4 rounded-lg border border-red-100 dark:border-slate-600">
                            <div>
                                <div class="font-bold dark:text-white">Resetuj Dane</div>
                                <div class="text-xs text-gray-500">Usuwa wszystkie transakcje, cele i budżety. Konto zostaje.</div>
                            </div>
                            <button onclick="resetData()" class="bg-white border border-red-300 text-red-600 hover:bg-red-50 px-4 py-2 rounded-lg text-sm font-bold transition">
                                Resetuj
                            </button>
                        </div>

                        <div class="flex items-center justify-between bg-white dark:bg-slate-800 p-4 rounded-lg border border-red-100 dark:border-slate-600">
                            <div>
                                <div class="font-bold dark:text-white">Usuń Konto</div>
                                <div class="text-xs text-gray-500">Usuwa wszystko trwale. Nie ma powrotu.</div>
                            </div>
                            <button onclick="deleteAccount()" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-bold transition">
                                Usuń Trwale
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
    /* Style dla aktywnych/nieaktywnych tabów */
    .tab-btn.active {
        background-color: #3b82f6;
        color: white;
    }
    .dark .tab-btn.active {
        background-color: #2563eb;
    }
    .tab-btn:not(.active) {
        color: #64748b;
    }
    .tab-btn:not(.active):hover {
        background-color: #f1f5f9;
        color: #334155;
    }
    .dark .tab-btn:not(.active) {
        color: #94a3b8;
    }
    .dark .tab-btn:not(.active):hover {
        background-color: #1e293b;
        color: #e2e8f0;
    }
</style>

<script>
    // --- OBSŁUGA ZAKŁADEK (TABS) ---
    function switchTab(tabId) {
        // Ukryj wszystkie treści
        document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
        document.getElementById('tab-' + tabId).classList.remove('hidden');

        // Zresetuj style przycisków
        document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
        document.getElementById('tab-btn-' + tabId).classList.add('active');
    }

    // --- 1. PROFIL ---
    document.getElementById('profile-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        
        try {
            const res = await fetch('../api/settings_action.php?action=update_profile', {
                method: 'POST', body: formData
            });
            const data = await res.json();
            if(data.success) {
                showToast('Profil zaktualizowany!', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(data.error || 'Błąd aktualizacji', 'error');
            }
        } catch(e) { showToast('Błąd połączenia', 'error'); }
    });

    // --- 2. HASŁO ---
    document.getElementById('password-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        
        if(formData.get('new_password') !== formData.get('confirm_password')) {
            return showToast('Nowe hasła nie są identyczne', 'error');
        }

        try {
            const res = await fetch('../api/settings_action.php?action=change_password', {
                method: 'POST', body: formData
            });
            const data = await res.json();
            if(data.success) {
                showToast('Hasło zmienione pomyślnie!', 'success');
                e.target.reset();
            } else {
                showToast(data.error || 'Błąd zmiany hasła', 'error');
            }
        } catch(e) { showToast('Błąd połączenia', 'error'); }
    });

    // --- 3. 2FA (LOGIKA ZACHOWANA Z POPRZEDNIEGO PLIKU) ---
    let tempSecret = '';
    async function start2FA() {
        try {
            const res = await fetch('../api/2fa_setup.php?action=generate');
            const data = await res.json();
            if(data.success) {
                tempSecret = data.secret;
                document.getElementById('qr-img').src = data.qr_url;
                document.getElementById('manual-secret').innerText = data.secret;
                document.getElementById('setup-2fa-box').classList.remove('hidden');
            } else { showToast(data.error, 'error'); }
        } catch(e) { showToast('Błąd', 'error'); }
    }

    async function confirm2FA() {
        const code = document.getElementById('verify-code').value.replace(/\s/g, '');
        try {
            const res = await fetch('../api/2fa_setup.php?action=enable', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ secret: tempSecret, code: code })
            });
            const data = await res.json();
            if(data.success) { showToast('2FA Włączone!', 'success'); setTimeout(() => location.reload(), 1000); }
            else { showToast('Błędny kod', 'error'); }
        } catch(e) { showToast('Błąd', 'error'); }
    }

    async function disable2FA() {
        if(confirm('Wyłączyć 2FA? Konto będzie mniej bezpieczne.')) {
            await fetch('../api/2fa_setup.php?action=disable');
            location.reload();
        }
    }

    // --- 4. STREFA RYZYKA ---
    async function resetData() {
        const code = prompt("Aby potwierdzić, wpisz słowo: RESET");
        if(code === 'RESET') {
            const res = await fetch('../api/settings_action.php?action=reset_data', {method: 'POST'});
            const data = await res.json();
            if(data.success) {
                showToast('Konto wyczyszczone.', 'success');
                setTimeout(() => location.href='dashboard.php', 1000);
            }
        }
    }

    async function deleteAccount() {
        const code = prompt("Aby USUNĄĆ KONTO TRWALE, wpisz słowo: USUŃ");
        if(code === 'USUŃ') {
            const res = await fetch('../api/settings_action.php?action=delete_account', {method: 'POST'});
            const data = await res.json();
            if(data.success) {
                alert('Konto usunięte. Do widzenia.');
                location.href = '../login.php';
            }
        }
    }

    // --- 5. PREFERENCJE ---
    function toggleDarkMode() {
        document.documentElement.classList.toggle('dark');
        // Tutaj można dodać zapisywanie do LocalStorage lub bazy
        localStorage.setItem('theme', document.documentElement.classList.contains('dark') ? 'dark' : 'light');
    }
		// --- 6. ZAPIS PREFERENCJI ---
	document.getElementById('preferences-form').addEventListener('submit', async (e) => {
		e.preventDefault();
		const formData = new FormData(e.target);
		
		try {
			const res = await fetch('../api/settings_action.php?action=update_preferences', {
				method: 'POST', body: formData
			});
			const data = await res.json();
			
			if(data.success) {
				showToast('Preferencje zapisane!', 'success');
				// Opcjonalnie przeładowanie, aby zastosować zmiany (np. język/kolor)
				setTimeout(() => location.reload(), 1000);
			} else {
				showToast(data.error || 'Błąd zapisu', 'error');
			}
		} catch(e) { showToast('Błąd połączenia', 'error'); }
	});
</script>

<?php require_once '../includes/footer.php'; ?>