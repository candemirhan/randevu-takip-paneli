<?php
session_start();
require 'db.php';

if (isset($_POST['login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    if ($username && $password) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            if ($user['role'] === 'admin' && $password === '123456') {
                // Admin fixed password
                $_SESSION['user'] = $user;
                if ($remember) {
                    setcookie('user', $username, time() + (86400 * 30), "/");
                }
                header('Location: dashboard.php');
                exit;
            } else if (password_verify($password, $user['password'])) {
                $_SESSION['user'] = $user;
                if ($remember) {
                    setcookie('user', $username, time() + (86400 * 30), "/");
                }
                header('Location: dashboard.php');
                exit;
            } else {
                $error = "Geçersiz kullanıcı adı veya şifre.";
            }
        } else {
            $error = "Geçersiz kullanıcı adı veya şifre.";
        }
    } else {
        $error = "Lütfen kullanıcı adı ve şifre giriniz.";
    }
} else {
    // Check cookie for remember me
    if (isset($_COOKIE['user'])) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$_COOKIE['user']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $_SESSION['user'] = $user;
            header('Location: dashboard.php');
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Giriş - Randevu Takip Paneli</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.2/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-black text-white flex items-center justify-center min-h-screen">
    <form method="POST" class="bg-gray-900 p-8 rounded-lg shadow-lg w-full max-w-sm">
        <h1 class="text-2xl font-bold mb-6 text-red-600 text-center">Randevu Takip Paneli</h1>
        <?php if (isset($error)): ?>
            <div class="bg-red-700 text-white p-2 mb-4 rounded"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <label for="username" class="block mb-2">Kullanıcı Adı</label>
        <input type="text" id="username" name="username" required class="w-full p-2 mb-4 rounded bg-gray-800 border border-gray-700 focus:outline-none focus:border-red-600" />
        <label for="password" class="block mb-2">Şifre</label>
        <input type="password" id="password" name="password" required class="w-full p-2 mb-4 rounded bg-gray-800 border border-gray-700 focus:outline-none focus:border-red-600" />
        <div class="flex items-center mb-4">
            <input type="checkbox" id="remember" name="remember" class="mr-2" />
            <label for="remember">Beni Hatırla</label>
        </div>
        <button type="submit" name="login" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-2 rounded transition">Giriş Yap</button>
    </form>
</body>
</html>
