<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Logowanie - BudgetPro</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .fade-in { animation: fadeIn 0.3s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="bg-slate-100 flex items-center justify-center h-screen">

    <div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-md border border-slate-200">
        
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-12 h-12 bg-gradient-to-tr from-green-400 to-blue-500 rounded-lg shadow-lg mb-4">
                <i class="fas fa-wallet text-white text-xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-800">Witaj ponownie!</h1>
            <p class="text-gray-500 text-sm mt-1">Zaloguj się do BudgetPro</p>
        </div>

        <form id="login-form" class="space-y-5">
            
            <div id="error-msg" class="hidden bg-red-50 text-red-600 p-3 rounded text-sm text-center border border-red-100"></div>

            <div id="step-1">
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Email</label>
                        <input type="email" name="email" class="w-full border border-gray-300 p-3 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" placeholder="jan@kowalski.pl" required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Hasło</label>
                        <input type="password" name="password" class="w-full border border-gray-300 p-3 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" placeholder="••••••••" required>
                    </div>
                </div>
            </div>

            <div id="step-2" class="hidden fade-in">
                <div class="bg-blue-50 border border-blue-100 p-4 rounded-lg text-center mb-4">
                    <p class="text-blue-800 font-bold mb-1"><i class="fas fa-shield-halved"></i> Weryfikacja 2FA</p>
                    <p class="text-xs text-blue-600">Podaj kod z aplikacji Google Authenticator</p>
                </div>
                
                <div>
                    <input type="text" id="2fa-code" class="w-full border border-gray-300 p-3 rounded-lg text-center text-2xl tracking-[0.5em] font-mono focus:ring-2 focus:ring-blue-500 outline-none transition" placeholder="000000" maxlength="6">
                </div>
            </div>

            <button type="submit" id="login-btn" class="w-full bg-slate-900 hover:bg-black text-white p-3 rounded-lg font-bold transition duration-200 shadow-lg flex justify-center items-center">
                Zaloguj się
            </button>
        </form>

        <div class="mt-6 text-center text-sm text-gray-500">
            Nie masz konta? <a href="register.php" class="text-blue-600 hover:underline font-bold">Zarejestruj się</a>
        </div>
    </div>

<script>
    const form = document.getElementById('login-form');
    const step1 = document.getElementById('step-1');
    const step2 = document.getElementById('step-2');
    const errorMsg = document.getElementById('error-msg');
    const btn = document.getElementById('login-btn');
    const codeInput = document.getElementById('2fa-code');
    
    let is2FaMode = false;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        // Reset UI
        errorMsg.classList.add('hidden');
        btn.disabled = true;
        const originalBtnText = btn.innerText;
        btn.innerHTML = '<i class="fas fa-circle-notch fa-spin mr-2"></i> Przetwarzanie...';

        // Przygotowanie danych
        const formData = new FormData(form);
        const payload = Object.fromEntries(formData);
        
        // Jeśli jesteśmy w trybie 2FA, dodajemy kod
        if(is2FaMode) {
            payload.code = codeInput.value.replace(/\s/g, '');
        }

        try {
            const res = await fetch('api/login_action.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(payload)
            });
            const data = await res.json();

            if (data.status === 'success') {
                // Sukces - przekierowanie
                btn.innerHTML = '<i class="fas fa-check mr-2"></i> Sukces!';
                btn.classList.replace('bg-slate-900', 'bg-green-600');
                setTimeout(() => window.location.href = 'pages/dashboard.php', 500);
            
            } else if (data.status === '2fa_required') {
                // Wymagane 2FA - pokaż drugi krok
                step1.classList.add('hidden'); // Ukryj email/hasło
                step2.classList.remove('hidden'); // Pokaż pole na kod
                is2FaMode = true;
                
                btn.innerText = "Zweryfikuj kod";
                btn.disabled = false;
                codeInput.focus();
            
            } else {
                // Błąd
                throw new Error(data.message || 'Błąd logowania');
            }

        } catch (err) {
            errorMsg.innerText = err.message;
            errorMsg.classList.remove('hidden');
            btn.disabled = false;
            btn.innerText = originalBtnText;
            
            // Jeśli błąd w trybie 2FA, wyczyść input
            if(is2FaMode) codeInput.value = '';
        }
    });
</script>

</body>
</html>