/**
 * BudgetPro Enterprise - Charts Logic
 * Obsługa wykresów Chart.js oraz aktualizacja danych na dashboardzie analitycznym.
 */

document.addEventListener('DOMContentLoaded', loadCharts);

async function loadCharts() {
    try {
        // 1. Pobieranie danych z API
        const res = await fetch('../api/get_stats.php');
        const data = await res.json();

        if (data.error) {
            console.error("API Error:", data.error);
            return;
        }

        // 2. Renderowanie Wykresów
        renderTrendChart(data.trend);     // Liniowy (Wydatki + Gradient)
        renderBarChart(data.trend);       // Słupkowy (Przychody vs Wydatki)
        renderCategoryChart(data.categories); // Kołowy (Struktura)

        // 3. Aktualizacja KPI i List
        updateComparisonCards(data.comparison);
        updateSavingsRate(data.savings_rate);
        renderTopExpenses(data.top_expenses);

        // 4. Ukrywanie Skeletonów (Animacja wejścia)
        // Czekamy chwilę, aby Chart.js zdążył się narysować, co daje płynniejszy efekt
        setTimeout(() => {
            hideSkeleton('trend-skeleton', 'trendChart');
            hideSkeleton('bar-skeleton', 'barChart'); // Jeśli masz skeleton dla bar chart
            hideSkeleton('cat-skeleton', 'catChart'); // Jeśli masz skeleton dla cat chart
        }, 300);

    } catch (e) {
        console.error("Błąd krytyczny ładowania wykresów:", e);
    }
}

// --- HELPERY ---

// Funkcja ukrywająca szary "szkielet" i pokazująca canvas
function hideSkeleton(skeletonId, canvasId) {
    const skel = document.getElementById(skeletonId);
    const canvas = document.getElementById(canvasId);
    
    if (skel) skel.classList.add('hidden');
    if (canvas) canvas.classList.remove('opacity-0');
}

// Sprawdzanie trybu ciemnego dla konfiguracji kolorów wykresów
function isDark() {
    return document.documentElement.classList.contains('dark');
}

// Kolory siatki i tekstu zależne od motywu
function getChartColors() {
    return {
        grid: isDark() ? '#334155' : '#e2e8f0', // Slate-700 vs Slate-200
        text: isDark() ? '#94a3b8' : '#64748b'  // Slate-400 vs Slate-500
    };
}

// --- RENDERY WYKRESÓW ---

// 1. Wykres Liniowy (Trend Wydatków)
function renderTrendChart(trendData) {
    const ctx = document.getElementById('trendChart');
    if (!ctx) return;

    const labels = trendData.map(item => item.month);
    const expense = trendData.map(item => item.expense);
    const colors = getChartColors();

    // Tworzenie gradientu (Czerwony zanikający w dół)
    const gradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0, 'rgba(239, 68, 68, 0.5)'); // Czerwony góra
    gradient.addColorStop(1, 'rgba(239, 68, 68, 0.0)'); // Przezroczysty dół

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Wydatki',
                data: expense,
                borderColor: '#EF4444', // Red-500
                backgroundColor: gradient,
                borderWidth: 2,
                tension: 0.4, // Krzywizna linii
                fill: true,
                pointRadius: 4,
                pointHoverRadius: 6,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#EF4444'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    backgroundColor: isDark() ? '#1e293b' : '#fff',
                    titleColor: isDark() ? '#fff' : '#1e293b',
                    bodyColor: isDark() ? '#cbd5e1' : '#475569',
                    borderColor: colors.grid,
                    borderWidth: 1
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: colors.grid },
                    ticks: { color: colors.text }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: colors.text }
                }
            }
        }
    });
}

// 2. Wykres Słupkowy (Przychody vs Wydatki)
function renderBarChart(trendData) {
    const ctx = document.getElementById('barChart');
    if (!ctx) return;

    const labels = trendData.map(item => item.month);
    const income = trendData.map(item => item.income);
    const expense = trendData.map(item => item.expense);
    const colors = getChartColors();

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Przychody',
                    data: income,
                    backgroundColor: '#10B981', // Emerald-500
                    borderRadius: 4,
                    barPercentage: 0.6,
                    categoryPercentage: 0.8
                },
                {
                    label: 'Wydatki',
                    data: expense,
                    backgroundColor: '#EF4444', // Red-500
                    borderRadius: 4,
                    barPercentage: 0.6,
                    categoryPercentage: 0.8
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: { color: colors.text }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: colors.grid },
                    ticks: { color: colors.text }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: colors.text }
                }
            }
        }
    });
}

// 3. Wykres Kołowy (Kategorie)
function renderCategoryChart(catData) {
    const ctx = document.getElementById('catChart');
    if (!ctx) return;

    // Obsługa braku danych
    if (catData.length === 0) {
        ctx.style.display = 'none';
        const noDataMsg = document.getElementById('no-cat-data');
        if(noDataMsg) noDataMsg.classList.remove('hidden');
        return;
    }

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: catData.map(c => c.name),
            datasets: [{
                data: catData.map(c => c.total),
                backgroundColor: catData.map(c => c.color),
                borderWidth: 2,
                borderColor: isDark() ? '#1e293b' : '#fff',
                hoverOffset: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '75%', // Grubość pierścienia
            plugins: {
                legend: { display: false } // Legenda ukryta dla czystości
            }
        }
    });
}

// --- AKTUALIZACJA KPI I HTML ---

// Aktualizacja karty porównania (Trend)
function updateComparisonCards(comp) {
    const iconEl = document.getElementById('comparison-icon');
    const textEl = document.getElementById('comparison-text');
    if (!iconEl || !textEl) return;

    // Reset ikon
    iconEl.className = "text-3xl mb-3"; 

    if (comp.percent > 0) {
        // Więcej wydatków (Źle)
        iconEl.classList.add('fas', 'fa-arrow-trend-up', 'text-red-500');
        textEl.innerHTML = `<span class="text-red-500 font-bold">+${comp.percent}%</span> r/r`;
    } else if (comp.percent < 0) {
        // Mniej wydatków (Dobrze)
        iconEl.classList.add('fas', 'fa-arrow-trend-down', 'text-green-500');
        textEl.innerHTML = `<span class="text-green-500 font-bold">${comp.percent}%</span> r/r`;
    } else {
        // Bez zmian
        iconEl.classList.add('fas', 'fa-minus', 'text-gray-400');
        textEl.innerHTML = `<span class="text-gray-500 font-bold">0%</span> zmian`;
    }
}

// Aktualizacja Wskaźnika Oszczędności
function updateSavingsRate(rate) {
    const el = document.getElementById('savings-rate-val');
    const bar = document.getElementById('savings-rate-bar');
    if (!el || !bar) return;

    el.innerText = rate + '%';
    
    // Zabezpieczenie szerokości paska (0-100%)
    const width = Math.max(0, Math.min(100, rate));
    bar.style.width = width + '%';

    // Koloryzacja w zależności od wyniku
    if (rate <= 0) {
        el.className = "text-4xl font-bold text-red-500";
        bar.className = "bg-red-500 h-2 rounded-full transition-all duration-1000";
    } else if (rate < 20) {
        el.className = "text-4xl font-bold text-yellow-500";
        bar.className = "bg-yellow-500 h-2 rounded-full transition-all duration-1000";
    } else {
        el.className = "text-4xl font-bold text-green-500";
        bar.className = "bg-green-500 h-2 rounded-full transition-all duration-1000";
    }
}

// Generowanie listy Top 5 Wydatków
function renderTopExpenses(list) {
    const container = document.getElementById('top-expenses-list');
    if (!container) return;
    
    container.innerHTML = '';

    if (list.length === 0) {
        container.innerHTML = `
            <div class="flex flex-col items-center justify-center py-10 text-gray-400">
                <i class="fas fa-receipt text-2xl mb-2"></i>
                <p class="text-xs">Brak wydatków w tym miesiącu</p>
            </div>`;
        return;
    }

    list.forEach(item => {
        const div = document.createElement('div');
        div.className = "flex items-center justify-between p-3 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-lg transition border-b border-gray-100 dark:border-slate-800 last:border-0";
        
        const amount = parseFloat(item.amount).toFixed(2);
        const color = item.cat_color || '#94a3b8';
        const icon = item.cat_icon || 'fa-tag';
        const name = item.description || item.cat_name;
        const date = item.transaction_date;

        div.innerHTML = `
            <div class="flex items-center space-x-3 overflow-hidden">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-white shrink-0 shadow-sm" style="background-color: ${color}">
                    <i class="fas ${icon} text-xs"></i>
                </div>
                <div class="min-w-0">
                    <div class="font-bold text-sm text-gray-800 dark:text-gray-200 truncate pr-2">${name}</div>
                    <div class="text-[10px] text-gray-500 dark:text-gray-400">${date}</div>
                </div>
            </div>
            <div class="font-bold text-red-600 dark:text-red-400 text-sm whitespace-nowrap">
                -${amount} <span class="text-[10px] font-normal text-gray-400">PLN</span>
            </div>
        `;
        container.appendChild(div);
    });
}