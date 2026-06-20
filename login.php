<?php
require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && $user['password'] === $password) {
        setcookie('user_id', $user['id'], time() + 86400 * 30, '/');
        header('Location: index.php');
        exit;
    } else {
        $error = 'Неверный email или пароль';
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Вход в систему</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 50px; background: #f5f5f5; }
        .login-form { max-width: 420px; margin: 0 auto; background: white; padding: 40px; border-radius: 8px; box-shadow: 0 0 15px rgba(0,0,0,0.1); }
        input { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #4CAF50; color: white; border: none; border-radius: 4px; font-size: 16px; cursor: pointer; }
        button:hover { background: #45a049; }
        .error { color: red; text-align: center; margin: 10px 0; }
        .links { text-align: center; margin-top: 20px; }
        .links a { color: #2196F3; text-decoration: none; }
    </style>
</head>
<body>
    <div class="login-form">
        <h1 style="text-align:center;">Вход в систему</h1>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <label><strong>Email</strong></label>
            <input type="text" name="email" required autofocus>
            
            <label><strong>Пароль</strong></label>
            <input type="password" name="password" required>
            
            <button type="submit">Войти</button>
        </form>
        
        <div class="links">
            <p><a href="register.php">→ Зарегистрировать новую компанию</a></p>
        </div>
        
        <p style="text-align:center; margin-top:20px; color:#666; font-size:14px;">
            Тестовые данные:<br>
            <strong>admin@test.ru</strong> / 123456
        </p>
    </div>
</body>
</html>