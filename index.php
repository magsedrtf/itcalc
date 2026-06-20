<?php
require_once 'config.php';
require_once 'auth.php';

$user_id = $_COOKIE['user_id'] ?? null;
if (!$user_id) {
    header('Location: login.php');
    exit;
}

$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: logout.php');
    exit;
}

$stmt = $db->prepare("
    SELECT w.* FROM workspaces w
    JOIN workspace_users wu ON w.id = wu.workspace_id
    WHERE wu.user_id = ?
    ORDER BY w.name
");
$stmt->execute([$user_id]);
$workspaces = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель управления</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="nav-bar">
            <span class="nav-brand">📊 ITCalc</span>
            <span class="nav-divider">|</span>
            <a href="team.php">👥 Команда</a>
            <a href="workspaces.php">🌐 Рабочие области</a>
            <?php if (isGlobalAdmin()): ?>
                <a href="settings.php">⚙️ Настройки</a>
            <?php endif; ?>
            <span class="nav-divider">|</span>
            <a href="logout.php" style="color:var(--danger);">🚪 Выйти</a>
            <span class="nav-right">
                <span style="color:var(--gray-500); font-size:14px;">
                    👤 <strong><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></strong>
                    (<?= htmlspecialchars($user['position'] ?? 'Пользователь') ?>)
                </span>
            </span>
        </div>

        <div class="card">
            <div class="card-header">
                <div>
                    <h1>Панель управления</h1>
                    <p class="page-subtitle">Выберите рабочую область для работы</p>
                </div>
            </div>
        </div>

        <?php if (empty($workspaces)): ?>
            <div class="card text-center" style="padding:40px;">
                <p style="font-size:18px; color:var(--gray-500); margin-bottom:16px;">
                    🏗️ У вас пока нет рабочих областей
                </p>
                <a href="workspace_edit.php" class="btn btn-primary">Создать первую рабочую область</a>
            </div>
        <?php else: ?>
            <div class="menu-grid">
                <?php foreach ($workspaces as $w): ?>
                    <a href="projects.php?workspace_id=<?= $w['id'] ?>" class="menu-item">
                        <span class="menu-icon">📁</span>
                        <span class="menu-title"><?= htmlspecialchars($w['name']) ?></span>
                        <?php if (!empty($w['subdomain'])): ?>
                            <span class="menu-desc"><?= htmlspecialchars($w['subdomain']) ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div style="margin-top:16px; text-align:center;">
            <a href="workspaces.php" class="btn btn-outline">🌐 Управление всеми рабочими областями</a>
        </div>
    </div>
</body>
</html>