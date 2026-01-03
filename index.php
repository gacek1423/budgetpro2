<?php
// index.php
require_once 'includes/session.php';

// Jeśli funkcja checkLogin() przekierowuje niezalogowanych, 
// to tutaj sprawdzamy ręcznie, żeby uniknąć pętli
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    header("Location: pages/dashboard.php");
} else {
    header("Location: login.php");
}
exit();
?>