/**
 * BudgetPro Enterprise - Main JS
 * Obsługa interfejsu, powiadomień, trybu ciemnego i logiki pomocniczej.
 */

document.addEventListener('DOMContentLoaded', () => {
    initDarkMode();
    initMobileMenu();
    attachMathParser();
});

// ==========================================
// 1. POWIADOMIENIA (Toastify Wrapper)
// ==========================================
function showToast(message, type = 'success') {
    // Kolory: Zielony (sukces), Czerwony (błąd), Niebieski (info)
    let bg;
    if (type === 'success') bg = "linear-gradient(to right, #059669, #10b981)";
    else if (type === 'error') bg = "linear-gradient(to right, #dc2626, #ef4444)";
    else bg = "linear-gradient(to right, #2563eb, #3b82f6)";

    if (typeof Toastify === 'function') {
        Toastify({
            text: message,
            duration: 3000,
            gravity: "top",
            position: "right",
            className: "font-sans font-medium text-sm shadow-lg rounded-lg",
            style: {
                background: bg,
                boxShadow: "0 4px 6px -1px rgba(0, 0, 0, 0.1)"
            },
            stopOnFocus: true,
            close: true
        }).showToast();
    } else {
        console.log(`[${type.toUpperCase()}] ${message}`);
        // Fallback jeśli biblioteka nie załadowana
        if(type === 'error') alert(message);
    }
}

// ==========================================
// 2. LOADER PRZYCISKÓW (UX)
// ==========================================
function setButtonLoading(btn, isLoading, originalText = '') {
    if (!btn) return;
    
    if (isLoading) {
        if (!btn.dataset.originalText) btn.dataset.originalText = btn.innerHTML;
        btn.disabled = true;
        // SVG Spinner
        btn.innerHTML = `
            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg> 
            Przetwarzanie...`;
        btn.classList.add('opacity-75', 'cursor-not-allowed');
    } else {
        btn.disabled = false;
        btn.innerHTML = originalText || btn.dataset.originalText;
        btn.classList.remove('opacity-75', 'cursor-not-allowed');
    }
}

// ==========================================
// 3. OBSŁUGA TRYBU CIEMNEGO (Dark Mode)
// ==========================================
function initDarkMode() {
    const toggleBtn = document.getElementById('theme-toggle');
    const dot = document.getElementById('theme-dot');
    const html = document.documentElement;

    const updateUI = (isDark) => {
        if (isDark) {
            html.classList.add('dark');
            if(dot) {
                dot.style.transform = 'translateX(1.25rem)'; // Przesunięcie kropki w prawo
                dot.classList.add('bg-gray-800');
                dot.classList.remove('bg-white');
            }
        } else {
            html.classList.remove('dark');
            if(dot) {
                dot.style.transform = 'translateX(0.25rem)'; // Przesunięcie kropki w lewo
                dot.classList.remove('bg-gray-800');
                dot.classList.add('bg-white');
            }
        }
    };

    // Inicjalizacja stanu przy załadowaniu
    updateUI(html.classList.contains('dark'));

    if (toggleBtn) {
        toggleBtn.addEventListener('click', (e) => {
            // Zapobiegamy propagacji jeśli kliknięto wewnątrz
            e.stopPropagation();
            
            const isDark = html.classList.contains('dark');
            if (isDark) {
                localStorage.setItem('theme', 'light');
                updateUI(false);
            } else {
                localStorage.setItem('theme', 'dark');
                updateUI(true);
            }
        });
    }
}

// ==========================================
// 4. MENU MOBILNE (Hamburger)
// ==========================================
function initMobileMenu() {
    const btn = document.getElementById('mobile-menu-btn');
    const closeBtn = document.getElementById('close-sidebar-btn');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');

    if (!btn || !sidebar || !overlay) return;

    function toggleMenu() {
        // Tailwind klasa -translate-x-full ukrywa sidebar poza ekranem
        sidebar.classList.toggle('-translate-x-full');
        
        // Pokazujemy/ukrywamy overlay
        if (overlay.classList.contains('hidden')) {
            overlay.classList.remove('hidden');
            // Małe opóźnienie dla animacji opacity (opcjonalne)
            setTimeout(() => overlay.classList.remove('opacity-0'), 10);
        } else {
            overlay.classList.add('opacity-0');
            setTimeout(() => overlay.classList.add('hidden'), 300);
        }
    }

    // Event listenery
    btn.addEventListener('click', (e) => {
        e.stopPropagation();
        toggleMenu();
    });

    if(closeBtn) {
        closeBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            toggleMenu();
        });
    }

    // Zamknij po kliknięciu w tło
    overlay.addEventListener('click', toggleMenu);
}

// ==========================================
// 5. INTELIGENTNY KALKULATOR W INPUTACH
// ==========================================
function attachMathParser() {
    // Szukamy inputów z kwotą (po nazwie lub ID)
    const inputs = document.querySelectorAll('input[name="amount"], input[id^="inp-amount"]');
    
    inputs.forEach(input => {
        input.addEventListener('change', (e) => {
            const val = e.target.value;
            // Sprawdź czy wpisano operatory matematyczne (+, -, *, /)
            if (/[\+\-\*\/]/.test(val)) {
                try {
                    // Sanityzacja: zostawiamy tylko cyfry i operatory
                    const clean = val.replace(/[^0-9\.\+\-\*\/]/g, '');
                    if(!clean) return;
                    
                    // Obliczenie (Function jest bezpieczniejsze niż eval, ale nadal wymaga ostrożności)
                    const result = new Function('return ' + clean)();
                    
                    if(isFinite(result)) {
                        e.target.value = result.toFixed(2);
                        showToast(`Obliczono: ${clean} = ${result.toFixed(2)}`, 'info');
                        
                        // Wywołaj event input, aby zaktualizować inne skrypty (np. przelicznik walut)
                        e.target.dispatchEvent(new Event('input'));
                    }
                } catch (err) {
                    console.error("Błąd kalkulatora:", err);
                }
            }
        });
    });
}

// ==========================================
// 6. HELPER FORMATOWANIA WALUTY
// ==========================================
function formatCurrency(amount, currency = 'PLN') {
    return new Intl.NumberFormat('pl-PL', { 
        style: 'currency', 
        currency: currency 
    }).format(amount);
}