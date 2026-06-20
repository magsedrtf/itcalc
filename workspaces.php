<?php
require_once 'config.php';

$user_id = $_COOKIE['user_id'] ?? null;
if (!$user_id) {
    header('Location: login.php');
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
    <title>Рабочие области</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-link">← На главную</a>

        <div class="card">
            <div class="card-header">
                <div>
                    <h1>🌐 Рабочие области</h1>
                    <p class="page-subtitle">Управление проектами и командами</p>
                </div>
                <a href="workspace_edit.php" class="btn btn-success">+ Создать рабочую область</a>
            </div>

            <?php if (empty($workspaces)): ?>
                <div class="text-center" style="padding:40px 0; color:var(--gray-500);">
                    <p style="font-size:16px;">У вас пока нет доступа ни к одной рабочей области</p>
                    <a href="workspace_edit.php" class="btn btn-primary mt-2">Создать первую</a>
                </div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
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
                        </thead>
                        <tbody>
                            <?php foreach ($workspaces as $w): 
                                $stats = $db->prepare("
                                    SELECT COUNT(*) as total,
                                    SUM(CASE WHEN status = 'В процессе' THEN 1 ELSE 0 END) as in_process,
                                    SUM(CASE WHEN status = 'Завершен' THEN 1 ELSE 0 END) as completed,
                                    SUM(CASE WHEN status = 'Заброшен' THEN 1 ELSE 0 END) as abandoned
                                    FROM projects WHERE workspace_id = ?
                                ");
                                $stats->execute([$w['id']]);
                                $s = $stats->fetch();
                            ?>
                            <tr>
                                <td><?= $w['id'] ?></td>
                                <td><strong><?= htmlspecialchars($w['name']) ?></strong></td>
                                <td><?= htmlspecialchars($w['subdomain'] ?? '—') ?></td>
                                <td><strong><?= $s['total'] ?? 0 ?></strong></td>
                                <td><span class="status-badge status-process"><?= $s['in_process'] ?? 0 ?></span></td>
                                <td><span class="status-badge status-done"><?= $s['completed'] ?? 0 ?></span></td>
                                <td><span class="status-badge status-abandoned"><?= $s['abandoned'] ?? 0 ?></span></td>
                                <td>
                                    <div class="actions">
                                        <a href="workspace_edit.php?id=<?= $w['id'] ?>" class="btn btn-info btn-sm">✏️ Ред.</a>
                                        <a href="projects.php?workspace_id=<?= $w['id'] ?>" class="btn btn-primary btn-sm">📁 Проекты</a>
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