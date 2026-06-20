<?php
require_once 'config.php';

$user_id = $_COOKIE['user_id'] ?? null;
if (!$user_id) {
    header('Location: login.php');
    exit;
}

$editId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$allRoles = $db->query("SELECT * FROM roles")->fetchAll();

// Загружаем данные пользователя, если редактируем
$user = null;
$userRoles = [];
if ($editId) {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$editId]);
    $user = $stmt->fetch();
    
    $stmt = $db->prepare("SELECT role_id FROM user_roles WHERE user_id = ?");
    $stmt->execute([$editId]);
    $userRoles = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Сохранение
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lastName = $_POST['last_name'];
    $firstName = $_POST['first_name'];
    $middleName = $_POST['middle_name'] ?: null;
    $email = $_POST['email'];
    $position = $_POST['position'];
    $roles = $_POST['roles'] ?? [];
    
    if ($editId) {
        $stmt = $db->prepare("UPDATE users SET last_name=?, first_name=?, middle_name=?, email=?, position=? WHERE id=?");
        $stmt->execute([$lastName, $firstName, $middleName, $email, $position, $editId]);
        
        // Обновляем роли
        $db->prepare("DELETE FROM user_roles WHERE user_id=?")->execute([$editId]);
    } else {
        $stmt = $db->prepare("INSERT INTO users (last_name, first_name, middle_name, email, position) VALUES (?,?,?,?,?)");
        $stmt->execute([$lastName, $firstName, $middleName, $email, $position]);
        $editId = $db->lastInsertId();
    }
    
    // Добавляем роли
    $insertRole = $db->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?,?)");
    foreach ($roles as $roleId) {
        $insertRole->execute([$editId, $roleId]);
    }
    
    header('Location: users.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?= $editId ? 'Редактирование' : 'Создание' ?> пользователя</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; }
        h1 { color: #333; }
        form { max-width: 500px; }
        label { display: block; margin: 10px 0 5px; font-weight: bold; }
        input[type="text"], input[type="email"] { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .roles { margin: 10px 0; }
        .roles label { display: inline-block; margin-right: 15px; font-weight: normal; }
        button { background: #4CAF50; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; margin-top: 15px; }
        button:hover { background: #45a049; }
        .back { margin-bottom: 20px; }
        .back a { color: #4CAF50; text-decoration: none; }
    </style>
</head>
<body>
    <div class="back">
        <a href="users.php">← К списку пользователей</a>
    </div>
    
    <h1><?= $editId ? '✏️ Редактирование' : '➕ Создание' ?> пользователя</h1>
    
    <form method="POST">
        <label>Фамилия *</label>
        <input type="text" name="last_name" value="<?= htmlspecialchars($user['last_name'] ?? '') ?>" required>
        
        <label>Имя *</label>
        <input type="text" name="first_name" value="<?= htmlspecialchars($user['first_name'] ?? '') ?>" required>
        
        <label>Отчество</label>
        <input type="text" name="middle_name" value="<?= htmlspecialchars($user['middle_name'] ?? '') ?>">
        
        <label>Email *</label>
        <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
        
        <label>Должность *</label>
        <input type="text" name="position" value="<?= htmlspecialchars($user['position'] ?? '') ?>" required>
        
        <label>Роли</label>
        <div class="roles">
            <?php foreach ($allRoles as $role): ?>
                <label>
                    <input type="checkbox" name="roles[]" value="<?= $role['id'] ?>"
                        <?= in_array($role['id'], $userRoles) ? 'checked' : '' ?>>
                    <?= htmlspecialchars($role['name']) ?>
                </label><br>
            <?php endforeach; ?>
        </div>
        
        <button type="submit">💾 Сохранить</button>
    </form>
</body>
</html>