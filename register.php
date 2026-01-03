<?php
// register.php
require_once 'includes/functions.php'; // Ładuje db, session, logger

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Logowanie próby (bez hasła!)
    logger("Próba rejestracji dla użytkownika: " . $_POST['username']);
    
    try {
        $db = db();
        
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        // --- 1. WALIDACJA SIŁY HASŁA (NOWOŚĆ) ---
        if (strlen($password) < 8) {
            $error = "Hasło jest za krótkie! Musi mieć minimum 8 znaków.";
        } 
        elseif (!preg_match("/[A-Z]/", $password)) {
            $error = "Hasło musi zawierać przynajmniej jedną DUŻĄ literę.";
        }
        elseif (!preg_match("/[a-z]/", $password)) {
            $error = "Hasło musi zawierać przynajmniej jedną małą literę.";
        }
        elseif (!preg_match("/[0-9]/", $password)) {
            $error = "Hasło musi zawierać przynajmniej jedną cyfrę.";
        }
        elseif ($password !== $confirm_password) {
            $error = "Hasła nie są identyczne!";
        } 
        else {
            // --- 2. Sprawdź czy użytkownik już istnieje ---
            $checkCtx = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $checkCtx->execute([$username, $email]);
            
            if ($checkCtx->rowCount() > 0) {
                $error = "Użytkownik o takiej nazwie lub emailu już istnieje!";
            } else {
                // --- 3. Tworzenie konta ---
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $db->prepare("INSERT INTO users (username, email, password_hash, created_at) VALUES (?, ?, ?, NOW())");
                
                if ($stmt->execute([$username, $email, $hash])) {
                    $success = "Konto utworzone pomyślnie! Możesz się zalogować.";
                    // Opcjonalnie: automatyczne logowanie po rejestracji
                } else {
                    $error = "Błąd bazy danych. Spróbuj ponownie.";
                }
            }
        }
    } catch (Exception $e) {
        logger($e->getMessage(), 'Register Error');
        $error = "Wystąpił błąd systemu.";
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Rejestracja - BudgetPro</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-lg shadow-md w-96">
        <h2 class="text-2xl font-bold mb-6 text-center text-gray-800">Rejestracja</h2>
        
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-200 text-red-700 px-4 py-3 rounded mb-4 text-sm">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-200 text-green-700 px-4 py-3 rounded mb-4 text-center">
                <?= htmlspecialchars($success) ?>
                <a href="login.php" class="font-bold underline block mt-2 text-green-800">Przejdź do logowania</a>
            </div>
        <?php else: ?>
        
        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Login</label>
                <input type="text" name="username" class="w-full border p-2 rounded focus:ring-2 focus:ring-green-500" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" class="w-full border p-2 rounded focus:ring-2 focus:ring-green-500" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Hasło</label>
                <input type="password" name="password" class="w-full border p-2 rounded focus:ring-2 focus:ring-green-500" required>
                <p class="text-xs text-gray-500 mt-1">Min. 8 znaków, duża litera i cyfra.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Powtórz hasło</label>
                <input type="password" name="confirm_password" class="w-full border p-2 rounded focus:ring-2 focus:ring-green-500" required>
            </div>

            <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 font-medium">Zarejestruj się</button>
        </form>
        
        <p class="mt-4 text-center text-sm text-gray-600">
            Masz już konto? <a href="login.php" class="text-green-600 hover:underline">Zaloguj się</a>
        </p>
        <?php endif; ?>
    </div>
</body>
</html>