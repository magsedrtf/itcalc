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

    // === Проверка на существующую почту ===
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $error = "Пользователь с таким Email уже существует!";
    } 
    // Простая проверка на заполненность
    elseif (empty($email) || empty($password) || empty($last_name) || empty($first_name)) {
        $error = "Заполните все обязательные поля!";
    } 
    else {
        try {
            // Создаём пользователя
            $stmt = $db->prepare("INSERT INTO users 
                (last_name, first_name, email, password, position, party_id) 
                VALUES (?,?,?,?,?,?)");
            $stmt->execute([$last_name, $first_name, $email, $password, 'Основатель', null]);

            $user_id = $db->lastInsertId();

            // Присваиваем party_id
            $db->prepare("UPDATE users SET party_id = ? WHERE id = ?")
               ->execute([$user_id, $user_id]);

            // Роль Глобальный администратор
            $stmt = $db->prepare("INSERT INTO user_roles (user_id, role_id) 
                VALUES (?, (SELECT id FROM roles WHERE name = 'Глобальный администратор'))");
            $stmt->execute([$user_id]);

            setDefaultPermissions($user_id, 'Глобальный администратор');

            // Создаём рабочую область
            $stmt = $db->prepare("INSERT INTO workspaces (name, subdomain, admin_user_id) 
                VALUES (?,?,?)");
            $stmt->execute([$company_name, '', $user_id]);
            $newWorkspaceId = $db->lastInsertId();

            $stmt = $db->prepare("INSERT INTO workspace_users (workspace_id, user_id, role) 
                VALUES (?,?, 'Редактирование')");
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
    <title>Регистрация компании</title>
    <style>
        body { font-family: Arial; margin: 50px; background: #f5f5f5; }
        .form { max-width: 500px; margin: 0 auto; background: white; padding: 40px; border-radius: 8px; box-shadow: 0 0 15px rgba(0,0,0,0.1); }
        input { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 4px; }
        button { width: 100%; padding: 14px; background: #4CAF50; color: white; border: none; border-radius: 4px; font-size: 16px; cursor: pointer; }
        .error { color: red; background: #ffebee; padding: 10px; border-radius: 4px; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="form">
        <h1>Регистрация новой компании</h1>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <label>Название компании</label>
            <input type="text" name="company_name" placeholder="Например: IQNIX" required>
            
            <label>Ваша фамилия</label>
            <input type="text" name="last_name" required>
            
            <label>Ваше имя</label>
            <input type="text" name="first_name" required>
            
            <label>Email</label>
            <input type="email" name="email" required>
            
            <label>Пароль</label>
            <input type="password" name="password" value="123456" required>
            
            <button type="submit">Создать компанию и войти</button>
        </form>
        
        <p style="text-align:center;"><a href="login.php">← Вернуться ко входу</a></p>
    </div>
</body>
</html>