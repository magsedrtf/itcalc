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
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Панель управления</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; background: #f9f9f9; }
        .header { display: flex; justify-content: space-between; align-items: center; background: white; padding: 15px 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .menu { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 15px; margin-top: 30px; }
        .menu a { 
            padding: 20px; 
            background: #4CAF50; 
            color: white; 
            text-decoration: none; 
            border-radius: 8px; 
            text-align: center;
            font-size: 17px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .menu a:hover { background: #45a049; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Панель управления</h1>
        <p> 
            Вы: <b><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></b> 
            (<?= htmlspecialchars($user['position'] ?? 'Пользователь') ?>)
            |
            <a href="team.php">Команда</a> |
            <a href="workspaces.php">Рабочие области</a> 
            <?php if (isGlobalAdmin()): ?>
                | <a href="settings.php">Настройки компании</a>
            <?php endif; ?>
            | <a href="logout.php">Выйти</a>
        </p>
    </div>

    <h2>Ваши рабочие области</h2>
    
    <?php 
    $stmt = $db->prepare("
        SELECT w.* FROM workspaces w
        JOIN workspace_users wu ON w.id = wu.workspace_id
        WHERE wu.user_id = ?
        ORDER BY w.name
    ");
    $stmt->execute([$user_id]);
    $workspaces = $stmt->fetchAll();
    ?>

    <?php if (empty($workspaces)): ?>
        <p>У вас пока нет рабочих областей. <a href="workspace_edit.php">Создайте первую</a></p>
    <?php else: ?>
        <div class="menu">
            <?php foreach ($workspaces as $w): ?>
                <a href="projects.php?workspace_id=<?= $w['id'] ?>">
                     <?= htmlspecialchars($w['name']) ?><br>
                    <small><?= htmlspecialchars($w['subdomain'] ?? '') ?></small>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <br><br>
    <a href="workspaces.php" style="color:#4CAF50; font-size:16px;">→ Управление всеми рабочими областями</a>
</body>
</html>