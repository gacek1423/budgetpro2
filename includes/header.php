<?php
// budgetpro2/includes/header.php
// ŻADNYCH białych znaków przed <?php!

require_once __DIR__ . '/session.php';

// Sprawdź czy user zalogowany (poza stronami publicznymi)
$public_pages = ['login.php', 'register.php', 'api/login_action.php'];
$current_page = basename($_SERVER['PHP_SELF']);

if (!isLoggedIn() && !in_array($current_page, $public_pages)) {
    header('Location: /login.php');
    exit;
}

$user_id = getCurrentUserId();
$prefs = getUserPreferences();
?><!DOCTYPE html>
<html lang="<?= $prefs['language'] === 'pl' ? 'pl' : 'en' ?>" 
      class="<?= $prefs['theme'] === 'dark' ? 'dark' : '' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= APP_NAME ?? 'BudgetPro' ?> - Dashboard</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Toastify -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        .dark ::-webkit-scrollbar { width: 8px; height: 8px; }
        .dark ::-webkit-scrollbar-track { background: #1f2937; }
        .dark ::-webkit-scrollbar-thumb { background: #4b5563; border-radius: 4px; }
        .dark { color-scheme: dark; }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100">