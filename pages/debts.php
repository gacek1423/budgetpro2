<?php
$pageTitle = "Redukcja Długów";
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/topbar.php';
?>

<div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
    <div>
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white flex items-center">
            <i class="fas fa-fire-extinguisher text-red-500 mr-3"></i> Pogromca Długów
        </h2>
        <p class="text-gray-500 dark:text-gray-400 text-sm">Wybierz strategię i zobacz, kiedy będziesz wolny finansowo.</p>
    </div>
    <button onclick="openDebtModal()" class="bg-red-600 hover:bg-red-700 text-white px-5 py-2 rounded-xl shadow-lg flex items-center transition">
        <i class="fas fa-plus mr-2"></i> Dodaj Dług
    </button>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    
    <div class="lg:col-span-1 space-y-6">
        <div id="debts-list" class="space-y-4">
            <div class="text-center py-10 text-gray-400">
                <i class="fas fa-circle-notch fa-spin"></i>
            </div>
        </div>
        
        <div class="bg-slate-800 text-white p-5 rounded-xl shadow-lg">
            <p class="text-xs text-slate-400 uppercase tracking-wide">Łączne Zadłużenie</p>
            <h3 class="text-3xl font-bold mt-1" id="total-debt-display">0.00 PLN</h3>
        </div>
    </div>

    <div class="lg:col-span-2 bg-white dark:bg-dark-card p-6 rounded-xl shadow-sm border dark:border-slate-700">
        
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 bg-slate-50 dark:bg-slate-800 p-4 rounded-lg">
            <div class="mb-4 sm:mb-0 w-full sm:w-auto">
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Dodatkowa spłata (miesięcznie)</label>
                <div class="relative">
                    <input type="number" id="extra-payment" value="100" class="w-full sm:w-40 border p-2 rounded dark:bg-slate-700 dark:border-slate-600 font-bold" oninput="calculatePayoff()">
                    <span class="absolute right-8 top-2 text-gray-400 text-sm">PLN</span>
                </div>
            </div>
            
            <div class="flex space-x-2 bg-white dark:bg-slate-900 p-1 rounded-lg border dark:border-slate-600">
                <button onclick="setStrategy('snowball')" id="btn-snowball" class="px-4 py-2 rounded text-sm font-medium transition bg-blue-600 text-white shadow">
                    Kula Śnieżna (Snowball)
                </button>
                <button onclick="setStrategy('avalanche')" id="btn-avalanche" class="px-4 py-2 rounded text-sm font-medium transition text-gray-500 hover:text-gray-700 dark:text-gray-400">
                    Lawina (Avalanche)
                </button>
            </div>
        </div>

        <div class="mb-6 text-center">
            <h4 class="text-lg font-medium text-gray-600 dark:text-gray-300">Będziesz wolny od długów:</h4>
            <p id="freedom-date" class="text-3xl font-bold text-green-600 dark:text-green-400 mt-1">--</p>
            <p id="interest-saved" class="text-xs text-gray-400 mt-2">Zapłacisz łącznie <span id="total-interest">0</span> PLN odsetek</p>
        </div>

        <div class="h-64 relative w-full">
            <canvas id="payoffChart"></canvas>
        </div>
        
        <div class="mt-4 text-xs text-gray-400 text-center">
            * Symulacja zakłada stałe oprocentowanie i brak nowych opłat.
        </div>
    </div>
</div>

<div id="debtModal" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-50 backdrop-blur-sm p-4">
    <div class="bg-white dark:bg-dark-card rounded-2xl p-6 w-full max-w-md shadow-2xl border dark:border-slate-700">
        <h3 class="text-xl font-bold mb-4 dark:text-white">Nowe Zobowiązanie</h3>
        <form id="debt-form" class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Nazwa</label>
                <input type="text" name="name" class="w-full border p-2 rounded dark:bg-slate-800 dark:border-slate-600" placeholder="np. Karta VISA" required>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Saldo (Do spłaty)</label>
                    <input type="number" step="0.01" name="balance" class="w-full border p-2 rounded dark:bg-slate-800 dark:border-slate-600" required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Oprocentowanie (%)</label>
                    <input type="number" step="0.01" name="interest_rate" class="w-full border p-2 rounded dark:bg-slate-800 dark:border-slate-600" placeholder="np. 18.5" required>
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Minimalna Rata</label>
                <input type="number" step="0.01" name="min_payment" class="w-full border p-2 rounded dark:bg-slate-800 dark:border-slate-600" required>
            </div>
            <div class="flex justify-end space-x-2 mt-6">
                <button type="button" onclick="document.getElementById('debtModal').classList.add('hidden')" class="px-4 py-2 text-gray-500">Anuluj</button>
                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded shadow">Dodaj</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    let debts = [];
    let currentStrategy = 'snowball';
    let chartInstance = null;

    document.addEventListener('DOMContentLoaded', loadDebts);

    async function loadDebts() {
        const res = await fetch('../api/debts_action.php');
        const data = await res.json();
        debts = data.data || [];
        renderList();
        calculatePayoff();
    }

    function renderList() {
        const container = document.getElementById('debts-list');
        const totalDisplay = document.getElementById('total-debt-display');
        container.innerHTML = '';
        
        let total = 0;

        if(debts.length === 0) {
            container.innerHTML = '<div class="text-center text-gray-400 py-4">Brak długów! Ciesz się wolnością.</div>';
            totalDisplay.innerText = "0.00 PLN";
            return;
        }

        debts.forEach(d => {
            total += parseFloat(d.balance);
            const div = document.createElement('div');
            div.className = "bg-white dark:bg-dark-card p-4 rounded-lg shadow-sm border border-gray-100 dark:border-slate-700 flex justify-between items-center group";
            div.innerHTML = `
                <div>
                    <div class="font-bold text-gray-800 dark:text-white">${d.name}</div>
                    <div class="text-xs text-gray-500">RRSO: ${d.interest_rate}% | Min: ${d.min_payment} zł</div>
                </div>
                <div class="text-right">
                    <div class="font-bold text-red-600 dark:text-red-400 text-lg">${formatCurrency(d.balance)}</div>
                    <button onclick="deleteDebt(${d.id})" class="text-xs text-gray-300 hover:text-red-500 opacity-0 group-hover:opacity-100 transition"><i class="fas fa-trash"></i> Usuń</button>
                </div>
            `;
            container.appendChild(div);
        });

        totalDisplay.innerText = formatCurrency(total);
    }

    // --- LOGIKA KALKULATORA ---
    function setStrategy(s) {
        currentStrategy = s;
        // Update UI buttons
        const btnS = document.getElementById('btn-snowball');
        const btnA = document.getElementById('btn-avalanche');
        const activeClass = "bg-blue-600 text-white shadow";
        const inactiveClass = "text-gray-500 hover:text-gray-700 dark:text-gray-400";
        
        if(s === 'snowball') {
            btnS.className = "px-4 py-2 rounded text-sm font-medium transition " + activeClass;
            btnA.className = "px-4 py-2 rounded text-sm font-medium transition " + inactiveClass;
        } else {
            btnA.className = "px-4 py-2 rounded text-sm font-medium transition " + activeClass;
            btnS.className = "px-4 py-2 rounded text-sm font-medium transition " + inactiveClass;
        }
        calculatePayoff();
    }

    function calculatePayoff() {
        if(debts.length === 0) return;

        let extraMoney = parseFloat(document.getElementById('extra-payment').value) || 0;
        
        // Klonujemy tablicę, żeby nie psuć oryginału symulacją
        // Dodajemy pole 'simBalance'
        let simDebts = debts.map(d => ({...d, simBalance: parseFloat(d.balance)}));
        
        // Sortowanie wg strategii
        if(currentStrategy === 'snowball') {
            // Najmniejsze saldo najpierw
            simDebts.sort((a,b) => a.simBalance - b.simBalance);
        } else {
            // Największy procent najpierw
            simDebts.sort((a,b) => b.interest_rate - a.interest_rate);
        }

        let months = 0;
        let totalInterest = 0;
        let history = []; // Dane do wykresu
        let totalBalance = simDebts.reduce((sum, d) => sum + d.simBalance, 0);

        // Maksymalnie 30 lat (360 miesięcy) symulacji, żeby nie zawiesić przeglądarki
        while(totalBalance > 1 && months < 360) {
            months++;
            let availableExtra = extraMoney;
            let monthInterest = 0;

            // 1. Nalicz odsetki i spłać minimum
            simDebts.forEach(d => {
                if(d.simBalance > 0) {
                    let interest = (d.simBalance * (parseFloat(d.interest_rate)/100)) / 12;
                    monthInterest += interest;
                    d.simBalance += interest;
                    
                    let pay = Math.min(d.simBalance, parseFloat(d.min_payment));
                    d.simBalance -= pay;
                }
            });

            // 2. Nadpłacaj wg strategii
            for(let d of simDebts) {
                if(d.simBalance > 0 && availableExtra > 0) {
                    let pay = Math.min(d.simBalance, availableExtra);
                    d.simBalance -= pay;
                    availableExtra -= pay;
                }
            }

            totalInterest += monthInterest;
            totalBalance = simDebts.reduce((sum, d) => sum + d.simBalance, 0);
            history.push(totalBalance);
        }

        // Wyniki
        document.getElementById('total-interest').innerText = formatCurrency(totalInterest);
        
        const date = new Date();
        date.setMonth(date.getMonth() + months);
        document.getElementById('freedom-date').innerText = date.toLocaleDateString('pl-PL', {month: 'long', year: 'numeric'});

        renderChart(history);
    }

    function renderChart(data) {
        const ctx = document.getElementById('payoffChart');
        if(chartInstance) chartInstance.destroy();

        const labels = data.map((_, i) => `Msc ${i+1}`);

        chartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Pozostałe Zadłużenie',
                    data: data,
                    borderColor: '#10B981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: {display: false} },
                scales: { 
                    x: { display: false },
                    y: { beginAtZero: true }
                }
            }
        });
    }

    // Modal i API
    function openDebtModal() { document.getElementById('debtModal').classList.remove('hidden'); document.getElementById('debtModal').classList.add('flex'); }
    
    document.getElementById('debt-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        await fetch('../api/debts_action.php?action=add', { method: 'POST', body: JSON.stringify(Object.fromEntries(formData)) });
        location.reload();
    });

    async function deleteDebt(id) {
        if(confirm('Usunąć ten dług?')) {
            await fetch(`../api/debts_action.php?action=delete&id=${id}`);
            loadDebts();
        }
    }
</script>

<?php require_once '../includes/footer.php'; ?>