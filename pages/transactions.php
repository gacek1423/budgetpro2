<?php
// budgetpro2/pages/transactions.php
// TEN KOD JEST ZGODNY Z NOWĄ BAZĄ - BEZ ZMIAN

error_reporting(E_ALL);
ini_set('display_errors', 1);

$includes_dir = __DIR__ . '/../includes';
require_once $includes_dir . '/session.php';
require_once $includes_dir . '/db.php';
require_once $includes_dir . '/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$prefs = getUserPreferences();
?>
<!DOCTYPE html>
<html lang="<?= $prefs['language'] === 'pl' ? 'pl' : 'en' ?>" class="<?= $prefs['theme'] === 'dark' ? 'dark' : '' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('transactions') ?> - <?= APP_NAME ?></title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 dark:bg-gray-900">
<?php require_once $includes_dir . '/header.php'; ?>
<?php require_once $includes_dir . '/topbar.php'; ?>
<?php require_once $includes_dir . '/sidebar.php'; ?>

<main class="md:ml-64 p-4 md:p-6">
    <div class="max-w-7xl mx-auto">
        
        <!-- Nagłówek -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-800 dark:text-white">
                <i class="fas fa-exchange-alt mr-2 text-blue-600"></i>
                <?= __('transactions') ?>
            </h1>
        </div>

        <!-- Statystyki -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <p class="text-gray-500 dark:text-gray-400 text-sm"><?= __('total_transactions') ?></p>
                <p id="summary-total" class="text-2xl font-bold text-blue-600">0</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <p class="text-gray-500 dark:text-gray-400 text-sm"><?= __('total_income') ?></p>
                <p id="summary-income" class="text-2xl font-bold text-green-600">0</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <p class="text-gray-500 dark:text-gray-400 text-sm"><?= __('total_expense') ?></p>
                <p id="summary-expense" class="text-2xl font-bold text-red-600">0</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <p class="text-gray-500 dark:text-gray-400 text-sm"><?= __('balance') ?></p>
                <p id="summary-balance" class="text-2xl font-bold text-purple-600">0</p>
            </div>
        </div>

        <!-- Kontrolki -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg mb-6">
            <div class="p-4 border-b dark:border-gray-700 flex flex-col md:flex-row justify-between gap-4">
                <div class="flex gap-3">
                    <select id="sort-select" class="border rounded-lg px-3 py-2 dark:bg-gray-700">
                        <option value="date-desc">Najnowsze</option>
                        <option value="date-asc">Najstarsze</option>
                        <option value="amount-desc">Najwyższe kwoty</option>
                        <option value="amount-asc">Najniższe kwoty</option>
                    </select>
                    <select id="limit-select" class="border rounded-lg px-3 py-2 dark:bg-gray-700">
                        <option value="25">25 na stronę</option>
                        <option value="50" selected>50 na stronę</option>
                        <option value="100">100 na stronę</option>
                    </select>
                </div>
                <div class="flex gap-3">
                    <input type="text" id="search-input" placeholder="Szukaj transakcji..." 
                           class="pl-10 pr-4 py-2 border rounded-lg dark:bg-gray-700 w-64">
                    <button onclick="exportCSV()" class="px-4 py-2 bg-green-600 text-white rounded-lg">
                        <i class="fas fa-download mr-2"></i>Export CSV
                    </button>
                </div>
            </div>

            <!-- Tabela -->
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left">Data</th>
                            <th class="px-4 py-3 text-left">Kategoria</th>
                            <th class="px-4 py-3 text-left">Opis</th>
                            <th class="px-4 py-3 text-right">Kwota</th>
                        </tr>
                    </thead>
                    <tbody id="transactions-tbody">
                        <tr>
                            <td colspan="4" class="text-center py-8">
                                <i class="fas fa-spinner animate-spin text-blue-500 text-2xl mr-3"></i> Ładowanie...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Paginacja -->
            <div class="p-4 border-t dark:border-gray-700 flex justify-between items-center">
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    <span id="pagination-info">Ładowanie...</span>
                </div>
                <div class="flex gap-2">
                    <button id="prev-btn" onclick="loadPage('prev')" 
                            class="px-3 py-2 bg-gray-200 dark:bg-gray-700 rounded-lg disabled:opacity-50">Poprzednia</button>
                    <select id="page-select" onchange="jumpToPage()" 
                            class="border rounded-lg px-3 py-2 dark:bg-gray-700"></select>
                    <button id="next-btn" onclick="loadPage('next')" 
                            class="px-3 py-2 bg-gray-200 dark:bg-gray-700 rounded-lg disabled:opacity-50">Następna</button>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
let currentPage = 1;
let totalPages = 1;
let currentSort = 'date-desc';
let currentLimit = 50;
let searchTimeout = null;

async function loadTransactions() {
    const tbody = document.getElementById('transactions-tbody');
    tbody.innerHTML = '<tr><td colspan="4" class="text-center py-8"><i class="fas fa-spinner animate-spin text-blue-500 text-2xl mr-3"></i> Ładowanie...</td></tr>';
    
    const params = new URLSearchParams({
        page: currentPage,
        limit: currentLimit,
        sort: currentSort.split('-')[0],
        order: currentSort.split('-')[1],
        search: document.getElementById('search-input')?.value.trim() || ''
    });

    try {
        const response = await fetch(`/api/get_transactions.php?${params}`);
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        
        const data = await response.json();
        if (data.error) throw new Error(data.message);

        tbody.innerHTML = data.data.map(tx => `
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                <td class="px-4 py-3">${new Date(tx.transaction_date).toLocaleDateString('pl-PL')}</td>
                <td class="px-4 py-3">
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium" 
                          style="background-color: ${tx.category_color}20; color: ${tx.category_color}">
                        ${tx.category_name || 'Inne'}
                    </span>
                </td>
                <td class="px-4 py-3">${tx.description || '-'}</td>
                <td class="px-4 py-3 text-right font-semibold ${tx.type === 'income' ? 'text-green-600' : 'text-red-600'}">
                    ${tx.type === 'income' ? '+' : '-'} ${parseFloat(tx.amount).toLocaleString('pl-PL', {minimumFractionDigits: 2})} zł
                </td>
            </tr>
        `).join('');

        document.getElementById('summary-total').textContent = data.summary.total_transactions;
        document.getElementById('summary-income').textContent = data.summary.total_income.toLocaleString('pl-PL', {minimumFractionDigits: 2}) + ' zł';
        document.getElementById('summary-expense').textContent = data.summary.total_expense.toLocaleString('pl-PL', {minimumFractionDigits: 2}) + ' zł';
        document.getElementById('summary-balance').textContent = (data.summary.total_income - data.summary.total_expense).toLocaleString('pl-PL', {minimumFractionDigits: 2}) + ' zł';

        totalPages = data.pagination.total_pages;
        document.getElementById('pagination-info').textContent = `Strona ${data.pagination.current_page} z ${totalPages} (${data.pagination.total} transakcji)`;
        
        document.getElementById('prev-btn').disabled = !data.pagination.has_prev;
        document.getElementById('next-btn').disabled = !data.pagination.has_next;

    } catch (error) {
        tbody.innerHTML = `<tr><td colspan="4" class="text-center py-8 text-red-600"><i class="fas fa-exclamation-circle mr-2"></i> Błąd: ${error.message}</td></tr>`;
    }
}

function loadPage(direction) {
    if (direction === 'next' && currentPage < totalPages) currentPage++;
    if (direction === 'prev' && currentPage > 1) currentPage--;
    loadTransactions();
}

function jumpToPage() {
    currentPage = parseInt(document.getElementById('page-select').value);
    loadTransactions();
}

function exportCSV() {
    window.location.href = '/api/export_csv.php';
}

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('sort-select').addEventListener('change', (e) => {
        currentSort = e.target.value;
        currentPage = 1;
        loadTransactions();
    });
    
    document.getElementById('limit-select').addEventListener('change', (e) => {
        currentLimit = parseInt(e.target.value);
        currentPage = 1;
        loadTransactions();
    });
    
    document.getElementById('search-input').addEventListener('input', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            currentPage = 1;
            loadTransactions();
        }, 500);
    });
    
    loadTransactions();
});
</script>

<?php require_once $includes_dir . '/footer.php'; ?>