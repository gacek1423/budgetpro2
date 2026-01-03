<?php
// admin/stop_impersonate.php
session_start();
require_once '../includes/db.php';

// 1. Sprawdź, czy w ogóle jesteśmy w trybie "Shadow Mode"
if (!isset($_SESSION['original_admin_id'])) {
    // Jeśli nie, to po prostu przekieruj gdziekolwiek
    header("Location: ../pages/dashboard.php");
    exit;
}

// 2. Pobierz ID prawdziwego admina
$adminId = $_SESSION['original_admin_id'];

// 3. Pobierz dane admina z bazy (żeby odświeżyć username itp.)
$db = db();
$stmt = $db->prepare("SELECT id, username, role FROM users WHERE id = ?");
$stmt->execute([$adminId]);
$admin = $stmt->fetch();

if ($admin && $admin['role'] === 'admin') {
    // 4. PRZYWRÓĆ SESJĘ ADMINA
    $_SESSION['user_id'] = $admin['id'];
    $_SESSION['username'] = $admin['username'];
    $_SESSION['logged_in'] = true; // Dla pewności
    
    // 5. Usuń ślad po podglądzie
    unset($_SESSION['original_admin_id']);

    // 6. Wróć do listy użytkowników
    header("Location: users.php");
    exit;
} else {
    // Błąd krytyczny (np. konto admina zostało w międzyczasie usunięte)
    die("Błąd przywracania sesji. Wyloguj się ręcznie.");
}
?>