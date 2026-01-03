<?php
// budgetpro2/includes/sidebar.php
// Ten plik jest includowany po header.php i topbar.php

$menu_items = [
    'dashboard.php' => ['icon' => 'fa-tachometer-alt', 'label' => 'Dashboard'],
    'transactions.php' => ['icon' => 'fa-exchange-alt', 'label' => 'Transakcje'],
    'budgets.php' => ['icon' => 'fa-chart-pie', 'label' => 'Budżety'],
    'categories.php' => ['icon' => 'fa-tags', 'label' => 'Kategorie'],
    'goals.php' => ['icon' => 'fa-bullseye', 'label' => 'Cele'],
    'reports.php' => ['icon' => 'fa-chart-line', 'label' => 'Raporty'],
    'settings.php' => ['icon' => 'fa-cog', 'label' => 'Ustawienia'],
];

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Sidebar -->
<aside id="sidebar" class="fixed left-0 top-0 bottom-0 z-30 w-64 bg-gray-800 text-white transform -translate-x-full md:translate-x-0 transition-transform duration-200">
    <div class="p-4 mt-14 md:mt-0">
        <!-- Logo -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-white">
                <i class="fas fa-wallet mr-2 text-blue-400"></i>
                <?= APP_NAME ?>
            </h1>
            <p class="text-gray-400 text-sm mt-1">v<?= APP_VERSION ?? '2.0' ?></p>
        </div>

        <!-- Menu -->
        <nav class="space-y-2">
            <?php foreach ($menu_items as $file => $item): ?>
                <a href="/pages/<?= $file ?>" 
                   class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-700 <?= $current_page === $file ? 'bg-blue-600' : '' ?>">
                    <i class="fas <?= $item['icon'] ?> w-5 text-center"></i>
                    <span><?= $item['label'] ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>
</aside>

<!-- Overlay dla mobile -->
<div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-20 hidden md:hidden"></div>

<script>
// Mobile menu toggle
document.getElementById('mobile-menu-btn')?.addEventListener('click', () => {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('-translate-x-full');
    document.getElementById('sidebar-overlay').classList.toggle('hidden');
});

// Overlay click
document.getElementById('sidebar-overlay')?.addEventListener('click', () => {
    document.getElementById('sidebar').classList.add('-translate-x-full');
    document.getElementById('sidebar-overlay').classList.add('hidden');
});
</script>