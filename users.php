<?php
require_once 'config.php';
if (!hasPermission('manage_users')) {
    header('Location: index.php');
    exit;
}

$user_id = $_COOKIE['user_id'] ?? null;
if (!$user_id) {
    header('Location: login.php');
    exit;
}

// Получаем всех пользователей с ролями
$users = $db->query("SELECT u.*, GROUP_CONCAT(r.name, ', ') as roles 
    FROM users u 
    LEFT JOIN user_roles ur ON u.id = ur.user_id 
    LEFT JOIN roles r ON ur.role_id = r.id 
    GROUP BY u.id 
    ORDER BY u.last_name")->fetchAll();

// Получаем все роли для формы
$allRoles = $db->query("SELECT * FROM roles")->fetchAll();

// Удаление
if (isset($_GET['delete'])) {
    $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([(int)$_GET['delete']]);
    header('Location: users.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление пользователями</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; }
        h1 { color: #333; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #4CAF50; color: white; }
        .btn { padding: 5px 10px; text-decoration: none; border-radius: 3px; color: white; }
        .btn-edit { background-color: #2196F3; }
        .btn-delete { background-color: #f44336; }
        .btn-add { background-color: #4CAF50; padding: 10px 20px; display: inline-block; margin: 10px 0; }
        .back { margin-bottom: 20px; }
        .back a { color: #4CAF50; text-decoration: none; }
    </style>
</head>
<body>
    <div class="back">
        <a href="index.php">← На главную</a>
    </div>
    
    <h1>👥 Управление пользователями</h1>
    
    <a href="user_edit.php" class="btn btn-add">+ Добавить пользователя</a>
    
    <table>
        <tr>
            <th>ID</th>
            <th>Фамилия</th>
            <th>Имя</th>
            <th>Отчество</th>
            <th>Email</th>
            <th>Должность</th>
            <th>Роли</th>
            <th>Действия</th>
        </tr>
        <?php foreach ($users as $u): ?>
        <tr>
            <td><?= $u['id'] ?></td>
            <td><?= htmlspecialchars($u['last_name']) ?></td>
            <td><?= htmlspecialchars($u['first_name']) ?></td>
            <td><?= htmlspecialchars($u['middle_name'] ?? '—') ?></td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td><?= htmlspecialchars($u['position']) ?></td>
            <td><?= htmlspecialchars($u['roles'] ?? '—') ?></td>
            <td>
                <a href="user_edit.php?id=<?= $u['id'] ?>" class="btn btn-edit">Ред.</a>
                <a href="?delete=<?= $u['id'] ?>" class="btn btn-delete" onclick="return confirm('Удалить?')">Уд.</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>