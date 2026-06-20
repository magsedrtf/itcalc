<?php
require_once 'config.php';
require_once 'auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $last_name = trim($_POST['last_name']);
    $first_name = trim($_POST['first_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $company_name = trim($_POST['company_name'] ?: $first_name . "'s Company");

    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $error = "Пользователь с таким Email уже существует!";
    } elseif (empty($email) || empty($password) || empty($last_name) || empty($first_name)) {
        $error = "Заполните все обязательные поля!";
    } else {
        try {
            $stmt = $db->prepare("INSERT INTO users (last_name, first_name, email, password, position, party_id) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$last_name, $first_name, $email, $password, 'Основатель', null]);
            $user_id = $db->lastInsertId();

            $db->prepare("UPDATE users SET party_id = ? WHERE id = ?")->execute([$user_id, $user_id]);

            $stmt = $db->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, (SELECT id FROM roles WHERE name = 'Глобальный администратор'))");
            $stmt->execute([$user_id]);

            setDefaultPermissions($user_id, 'Глобальный администратор');

            $stmt = $db->prepare("INSERT INTO workspaces (name, subdomain, admin_user_id) VALUES (?,?,?)");
            $stmt->execute([$company_name, '', $user_id]);
            $newWorkspaceId = $db->lastInsertId();

            $stmt = $db->prepare("INSERT INTO workspace_users (workspace_id, user_id, role) VALUES (?,?, 'Редактирование')");
            $stmt->execute([$newWorkspaceId, $user_id]);

            setcookie('user_id', $user_id, time() + 86400*30, '/');
            header('Location: index.php');
            exit;
        } catch (Exception $e) {
            $error = "Ошибка при регистрации: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация компании</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .register-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #eef2ff 0%, #f8fafc 100%);
        }
        .register-card { max-width: 500px; width: 100%; }
    </style>
</head>
<body>
    <div class="register-wrapper">
        <div class="card register-card">
            <h1>🏢 Регистрация</h1>
            <p class="page-subtitle" style="text-align:center;">Создайте новую компанию</p>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Название компании <span class="required">*</span></label>
                    <input type="text" name="company_name" class="form-control" placeholder="Например: IQNIX" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Фамилия <span class="required">*</span></label>
                        <input type="text" name="last_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Имя <span class="required">*</span></label>
                        <input type="text" name="first_name" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email <span class="required">*</span></label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Пароль <span class="required">*</span></label>
                    <input type="password" name="password" class="form-control" value="123456" required>
                </div>
                
                <button type="submit" class="btn btn-success w-100" style="justify-content:center; padding:12px; font-size:16px;">
                    🚀 Создать компанию и войти
                </button>
            </form>
            
            <p style="text-align:center; margin-top:16px;">
                <a href="login.php" style="color:var(--primary); text-decoration:none;">← Вернуться ко входу</a>
            </p>
        </div>
    </div>
</body>
</html>