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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в систему</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .login-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #eef2ff 0%, #f8fafc 100%);
        }
        .login-card {
            max-width: 420px;
            width: 100%;
        }
        .login-card h1 { text-align: center; }
        .login-card .subtitle { text-align: center; color: var(--gray-500); margin-bottom: 24px; }
        .test-credentials {
            text-align: center;
            font-size: 13px;
            color: var(--gray-500);
            margin-top: 20px;
            padding: 12px;
            background: var(--gray-50);
            border-radius: var(--radius);
        }
        .test-credentials strong { color: var(--gray-700); }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="card login-card">
            <h1>🚀 Вход</h1>
            <p class="subtitle">Войдите в свою учётную запись</p>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Email <span class="required">*</span></label>
                    <input type="email" name="email" class="form-control" placeholder="example@mail.ru" required autofocus>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Пароль <span class="required">*</span></label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
                
                <button type="submit" class="btn btn-primary w-100" style="justify-content:center; padding:12px; font-size:16px;">
                    Войти
                </button>
            </form>
            
            <div class="test-credentials">
                <strong>Тестовые данные:</strong><br>
                admin@test.ru / 123456
            </div>
            
            <p style="text-align:center; margin-top:16px;">
                <a href="register.php" style="color:var(--primary); text-decoration:none;">→ Зарегистрировать новую компанию</a>
            </p>
        </div>
    </div>
</body>
</html>