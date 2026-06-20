<?php
require_once 'config.php';
require_once 'auth.php';

$user_id = $_COOKIE['user_id'] ?? null;
if (!$user_id) {
    header('Location: login.php');
    exit;
}


$canManageUsers     = hasPermission('manage_users');
$canViewCredentials = hasPermission('view_user_credentials');


$stmt = $db->prepare("SELECT party_id FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$current_party_id = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT u.*, GROUP_CONCAT(r.name) as roles 
    FROM users u 
    LEFT JOIN user_roles ur ON u.id = ur.user_id 
    LEFT JOIN roles r ON ur.role_id = r.id 
    WHERE u.party_id = ? 
    GROUP BY u.id 
    ORDER BY u.last_name");
$stmt->execute([$current_party_id]);
$team = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Моя команда</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #4CAF50; color: white; }
        .btn { padding: 6px 12px; color: white; text-decoration: none; border-radius: 4px; }
        .btn-delete { background: #f44336; }
        .hidden { color: #999; font-style: italic; }
    </style>
</head>
<body>
    <a href="index.php">← На главную</a>
    <h1>Моя команда</h1>
    
    <?php if ($canManageUsers): ?>
        <a href="add_user.php" class="btn" style="background:#4CAF50">+ Добавить человека в команду</a>
    <?php endif; ?>
    
    <table>
        <tr>
            <th>ФИО</th>
            <th>Должность</th>
            <th>Роли</th>
            <th>Логин (Email)</th>
            <th>Пароль</th>
            <th>Действия</th>
        </tr>
        <?php foreach ($team as $member): ?>
        <tr>
            <td><?= htmlspecialchars($member['first_name'] . ' ' . $member['last_name']) ?></td>
            <td><?= htmlspecialchars($member['position']) ?></td>
            <td><?= htmlspecialchars($member['roles'] ?? '—') ?></td>
            
            <td>
                <?php if ($member['id'] == $user_id || $canViewCredentials): ?>
                    <?= htmlspecialchars($member['email']) ?>
                <?php else: ?>
                    <span class="hidden">—</span>
                <?php endif; ?>
            </td>
            
            <td>
                <?php if ($member['id'] == $user_id): ?>
                    <strong>—</strong> <small>(ваш аккаунт)</small>
                <?php elseif ($canViewCredentials): ?>
                    <strong><?= htmlspecialchars($member['password'] ?? '—') ?></strong>
                <?php else: ?>
                    <span class="hidden">—</span>
                <?php endif; ?>
            </td>
            
            <td>
                <?php if ($canManageUsers): ?>
                    <a href="edit_user.php?id=<?= $member['id'] ?>" class="btn" style="background:#2196F3">Редактировать</a>
                <?php endif; ?>

                <?php if ($canManageUsers && $member['id'] != $user_id): ?>
                    <a href="?delete=<?= $member['id'] ?>" class="btn btn-delete" onclick="return confirm('Удалить пользователя?')">Удалить</a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>

    <?php if (empty($team)): ?>
        <p>В вашей команде пока никого нет.</p>
    <?php endif; ?>
</body>
</html>