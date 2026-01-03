<?php
// budgetpro2/includes/session.php

// Brak białych znaków przed <?php!

// Start sesji jeśli nie uruchomiona
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Sprawdź timeout (1 godzina)
if (isset($_SESSION['last_activity'])) {
    $timeout_duration = 3600; // 1 godzina
    if (time() - $_SESSION['last_activity'] > $timeout_duration) {
        session_unset();
        session_destroy();
        session_start();
        $_SESSION['timeout_msg'] = "Sesja wygasła. Zaloguj się ponownie.";
        header("Location: /login.php");
        exit();
    }
}
$_SESSION['last_activity'] = time();

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Funkcje pomocnicze
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
}

function getCurrentUserId(): int {
    return $_SESSION['user_id'] ?? 0;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
}