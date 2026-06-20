<?php
require_once 'config.php';
require_once 'auth.php';

if (!hasPermission('manage_users')) {
    header('Location: index.php');
    exit;
}

$user_id = $_COOKIE['user_id'] ?? null;
if (!$user_id) {
    header('Location: login.php');
    exit;
}

$users = $db->query("SELECT u.*, GROUP_CONCAT(r.name, ', ') as roles 
    FROM users u 
    LEFT JOIN user_roles ur ON u.id = ur.user_id 
    LEFT JOIN roles r ON ur.role_id = r.id 
    GROUP BY u.id 
    ORDER BY u.last_name")->fetchAll();

$allRoles = $db->query("SELECT * FROM roles")->fetchAll();

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление пользователями</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-link">← На главную</a>

        <div class="card">
            <div class="card-header">
                <div>
                    <h1>👥 Управление пользователями</h1>
                    <p class="page-subtitle">Все пользователи системы</p>
                </div>
                <a href="user_edit.php" class="btn btn-success">+ Добавить пользователя</a>
            </div>

            <div class="table-wrapper">
                <table class="table">
                    <thead>
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
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?= $u['id'] ?></td>
                            <td><?= htmlspecialchars($u['last_name']) ?></td>
                            <td><?= htmlspecialchars($u['first_name']) ?></td>
                            <td><?= htmlspecialchars($u['middle_name'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td><?= htmlspecialchars($u['position']) ?></td>
                            <td>
                                <?php foreach (explode(',', $u['roles'] ?? '') as $role): ?>
                                    <?php if (trim($role)): ?>
                                        <span class="role-badge"><?= htmlspecialchars(trim($role)) ?></span>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </td>
                            <td>
                                <div class="actions">
                                    <a href="user_edit.php?id=<?= $u['id'] ?>" class="btn btn-info btn-sm">✏️ Ред.</a>
                                    <a href="?delete=<?= $u['id'] ?>" class="btn btn-danger btn-sm" 
                                       onclick="return confirm('Удалить?')">🗑️ Уд.</a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>