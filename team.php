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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Моя команда</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .credential-hidden { color: var(--gray-400); font-style: italic; }
    </style>
</head>
<body>
    <div class="container">
        <div class="nav-bar">
            <a href="index.php" class="back-link" style="margin-bottom:0;">← На главную</a>
        </div>

        <div class="card">
            <div class="card-header">
                <div>
                    <h1>👥 Моя команда</h1>
                    <p class="page-subtitle">Управление участниками команды</p>
                </div>
                <?php if ($canManageUsers): ?>
                    <a href="add_user.php" class="btn btn-success">+ Добавить в команду</a>
                <?php endif; ?>
            </div>

            <?php if (empty($team)): ?>
                <div class="text-center" style="padding:40px 0;">
                    <p style="color:var(--gray-500); font-size:16px;">В вашей команде пока никого нет</p>
                    <?php if ($canManageUsers): ?>
                        <a href="add_user.php" class="btn btn-primary mt-2">Добавить первого участника</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ФИО</th>
                                <th>Должность</th>
                                <th>Роли</th>
                                <th>Логин</th>
                                <th>Пароль</th>
                                <th style="min-width:180px;">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($team as $member): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($member['first_name'] . ' ' . $member['last_name']) ?></strong>
                                    <?php if ($member['id'] == $user_id): ?>
                                        <span class="role-badge" style="background:var(--success-light); color:var(--success-dark);">Вы</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($member['position']) ?></td>
                                <td>
                                    <?php foreach (explode(',', $member['roles'] ?? '') as $role): ?>
                                        <?php if (trim($role)): ?>
                                            <span class="role-badge"><?= htmlspecialchars(trim($role)) ?></span>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </td>
                                <td>
                                    <?php if ($member['id'] == $user_id || $canViewCredentials): ?>
                                        <?= htmlspecialchars($member['email']) ?>
                                    <?php else: ?>
                                        <span class="credential-hidden">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($member['id'] == $user_id): ?>
                                        <span style="color:var(--gray-400);">—</span>
                                    <?php elseif ($canViewCredentials): ?>
                                        <code style="background:var(--gray-100); padding:2px 8px; border-radius:4px;">
                                            <?= htmlspecialchars($member['password'] ?? '—') ?>
                                        </code>
                                    <?php else: ?>
                                        <span class="credential-hidden">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="actions">
                                        <?php if ($canManageUsers): ?>
                                            <a href="edit_user.php?id=<?= $member['id'] ?>" class="btn btn-info btn-sm">✏️ Ред.</a>
                                        <?php endif; ?>
                                        <?php if ($canManageUsers && $member['id'] != $user_id): ?>
                                            <a href="?delete=<?= $member['id'] ?>" class="btn btn-danger btn-sm" 
                                               onclick="return confirm('Удалить пользователя?')">🗑️ Уд.</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>