<?php
require_once 'config.php';
require_once 'auth.php';

$user_id = $_COOKIE['user_id'] ?? null;
if (!$user_id) {
    header('Location: login.php');
    exit;
}

$workspace_id = isset($_GET['workspace_id']) ? (int)$_GET['workspace_id'] : 0;

if (!$workspace_id) {
    $stmt = $db->prepare("SELECT workspace_id FROM workspace_users WHERE user_id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $row = $stmt->fetch();
    $workspace_id = $row ? $row['workspace_id'] : 0;
}

if (!$workspace_id) {
    header('Location: workspaces.php');
    exit;
}


$stmt = $db->prepare("SELECT p.*, c.name as customer_name 
    FROM projects p 
    LEFT JOIN customers c ON p.customer_id = c.id 
    WHERE p.workspace_id = ? 
    ORDER BY p.created_at DESC");
$stmt->execute([$workspace_id]);
$projects = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Проекты</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; }
        .menu { display: flex; flex-wrap: wrap; gap: 10px; margin: 20px 0; }
        .menu a { padding: 10px 16px; background: #2196F3; color: white; text-decoration: none; border-radius: 4px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 10px; }
        th { background: #4CAF50; color: white; }
        .btn { padding: 8px 15px; color: white; text-decoration: none; border-radius: 4px; }
        .btn-add { background: #4CAF50; }
    </style>
</head>
<body>
    <a href="index.php">← На главную</a> | 
    <a href="workspaces.php">Рабочие области</a>
    <h1>Рабочая область — Проекты</h1>

        <div class="menu">
        <?php if (canManageCustomers() || isGlobalAdmin()): ?>
        <a href="customers.php?workspace_id=<?= $workspace_id ?>">Заказчики</a>
        <?php endif; ?>
        
        <?php if (canManageResources() || hasPermission('manage_employees') || isGlobalAdmin()): ?>
        <a href="employees.php?workspace_id=<?= $workspace_id ?>">Сотрудники</a>
        <a href="executors.php?workspace_id=<?= $workspace_id ?>">Исполнители</a>
        <a href="equipment.php?workspace_id=<?= $workspace_id ?>">Оборудование</a>
        <a href="subcontractors.php?workspace_id=<?= $workspace_id ?>">Субподрядчики</a>
        <?php endif; ?>
        
        <!-- Кнопка создания проекта -->
        <?php if (canManageProjects() || isGlobalAdmin()): ?>
        <a href="project_edit.php?workspace_id=<?= $workspace_id ?>" class="btn-add">+ Новый проект</a>
        <?php endif; ?>
    </div>

    <h2>Список проектов</h2>
    
    <table>
        <tr>
            <th>ID</th>
            <th>Название проекта</th>
            <th>Статус</th>
            <th>Начало</th>
            <th>Окончание</th>
            <th>Заказчик</th>
            <th>Действия</th>
        </tr>
        <?php foreach ($projects as $p): ?>
        <tr>
            <td><?= $p['id'] ?></td>
            <td><?= htmlspecialchars($p['name']) ?></td>
            <td><?= htmlspecialchars($p['status'] ?? 'Новый') ?></td>
            <td><?= $p['start_date'] ?? '—' ?></td>
            <td><?= $p['end_date'] ?? '—' ?></td>
            <td><?= htmlspecialchars($p['customer_name'] ?? '—') ?></td>
            <td>
                <a href="project_manage.php?id=<?= $p['id'] ?>" class="btn" style="background:#4CAF50">Управление</a>
                <?php if (canManageProjects()): ?>
                <a href="project_edit.php?id=<?= $p['id'] ?>" class="btn" style="background:#2196F3">Ред.</a>
                <?php endif; ?>
                <?php if (canDeleteProjects()): ?>
                <a href="project_delete.php?id=<?= $p['id'] ?>" class="btn" style="background:#f44336" onclick="return confirm('Удалить проект?')">Уд.</a>
                <?php endif; ?>
            </td>
            
        </tr>
        <?php endforeach; ?>
    </table>

    <?php if (empty($projects)): ?>
        <p>Пока нет проектов в этой рабочей области.</p>
    <?php endif; ?>
</body>
</html>