<?php
// budgetpro2/includes/topbar.php
// Ten plik jest includowany ZAWSZE po header.php

$user_data = getUserData($user_id);
?>
<!-- Top Navigation -->
<nav class="fixed top-0 left-0 right-0 bg-white dark:bg-gray-800 shadow z-40 md:ml-64">
    <div class="px-4 py-3 flex justify-between items-center">
        
        <!-- Mobile menu button -->
        <button id="mobile-menu-btn" class="md:hidden text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
            <i class="fas fa-bars text-xl"></i>
        </button>
        
        <!-- Page title (mobile) -->
        <div class="md:hidden font-semibold">
            <?= APP_NAME ?>
        </div>
        
        <!-- User menu -->
        <div class="flex items-center gap-3">
            <!-- Notifications -->
            <button onclick="showToast('Powiadomienia w budowie', 'info')" 
                    class="relative p-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                <i class="fas fa-bell text-lg"></i>
                <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
            </button>
            
            <!-- User profile -->
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center text-white font-semibold">
                    <?= substr($_SESSION['username'] ?? 'U', 0, 1) ?>
                </div>
                <div class="hidden md:block">
                    <p class="font-medium"><?= htmlspecialchars($_SESSION['username'] ?? 'Użytkownik') ?></p>
                    <p class="text-xs text-gray-500">
                        <?= htmlspecialchars($user_data['account_type'] ?? 'personal') ?>
                    </p>
                </div>
                <form method="POST" action="/logout.php" class="ml-2">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <button type="submit" class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300" title="Wyloguj">
                        <i class="fas fa-sign-out-alt"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
</nav>