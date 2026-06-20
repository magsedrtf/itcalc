<?php
require_once 'config.php';

$user_id = $_COOKIE['user_id'] ?? null;
if (!$user_id) {
    header('Location: login.php');
    exit;
}

// Получаем рабочие области, к которым у пользователя есть доступ
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
    <title>Рабочие области</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background: #4CAF50; color: white; }
        .btn { padding: 8px 15px; color: white; text-decoration: none; border-radius: 4px; }
        .btn-add { background: #4CAF50; }
        .btn-edit { background: #2196F3; }
        .status-new { color: #2196F3; }
        .status-process { color: #FF9800; }
        .status-done { color: #4CAF50; }
        .status-abandoned { color: #f44336; }
    </style>
</head>
<body>
    <a href="index.php">← На главную</a>
    <h1>🌐 Рабочие области</h1>
    
    <a href="workspace_edit.php" class="btn btn-add">+ Создать рабочую область</a>
    
    <table>
        <tr>
            <th>ID</th>
            <th>Название</th>
            <th>Поддомен</th>
            <th>Проектов</th>
            <th>В процессе</th>
            <th>Завершено</th>
            <th>Заброшено</th>
            <th>Действия</th>
        </tr>
        <?php foreach ($workspaces as $w): 
            // Статистика проектов
            $stats = $db->prepare("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'В процессе' THEN 1 ELSE 0 END) as in_process,
                    SUM(CASE WHEN status = 'Завершен' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = 'Заброшен' THEN 1 ELSE 0 END) as abandoned
                FROM projects 
                WHERE workspace_id = ?
            ");
            $stats->execute([$w['id']]);
            $s = $stats->fetch();
        ?>
        <tr>
            <td><?= $w['id'] ?></td>
            <td><?= htmlspecialchars($w['name']) ?></td>
            <td><?= htmlspecialchars($w['subdomain'] ?? '—') ?></td>
            <td><strong><?= $s['total'] ?? 0 ?></strong></td>
            <td class="status-process"><?= $s['in_process'] ?? 0 ?></td>
            <td class="status-done"><?= $s['completed'] ?? 0 ?></td>
            <td class="status-abandoned"><?= $s['abandoned'] ?? 0 ?></td>
            <td>
                <a href="workspace_edit.php?id=<?= $w['id'] ?>" class="btn btn-edit">Редактировать</a>
                <a href="projects.php?workspace_id=<?= $w['id'] ?>" class="btn" style="background:#2196F3">Проекты</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>

    <?php if (empty($workspaces)): ?>
        <p>У вас пока нет доступа ни к одной рабочей области.</p>
    <?php endif; ?>
</body>
</html>