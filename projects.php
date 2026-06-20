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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Проекты</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="nav-bar">
            <a href="index.php">← На главную</a>
            <span class="nav-divider">|</span>
            <a href="workspaces.php">🌐 Рабочие области</a>
        </div>

        <div class="card">
            <div class="card-header">
                <div>
                    <h1>📁 Проекты</h1>
                    <p class="page-subtitle">Рабочая область — управление проектами</p>
                </div>
            </div>
            
            <div style="display:flex; flex-wrap:wrap; gap:8px; margin-bottom:20px;">
                <?php if (canManageCustomers() || isGlobalAdmin()): ?>
                    <a href="customers.php?workspace_id=<?= $workspace_id ?>" class="btn btn-info">🏢 Заказчики</a>
                <?php endif; ?>
                
                <?php if (canManageResources() || hasPermission('manage_employees') || isGlobalAdmin()): ?>
                    <a href="employees.php?workspace_id=<?= $workspace_id ?>" class="btn btn-info">👨‍💼 Сотрудники</a>
                    <a href="executors.php?workspace_id=<?= $workspace_id ?>" class="btn btn-info">👤 Исполнители</a>
                    <a href="equipment.php?workspace_id=<?= $workspace_id ?>" class="btn btn-info">🖥️ Оборудование</a>
                    <a href="subcontractors.php?workspace_id=<?= $workspace_id ?>" class="btn btn-info">🤝 Субподрядчики</a>
                <?php endif; ?>
                
                <?php if (canManageProjects() || isGlobalAdmin()): ?>
                    <a href="project_edit.php?workspace_id=<?= $workspace_id ?>" class="btn btn-success" style="margin-left:auto;">+ Новый проект</a>
                <?php endif; ?>
            </div>

            <h2>Список проектов</h2>
            
            <?php if (empty($projects)): ?>
                <div class="text-center" style="padding:40px 0; color:var(--gray-500);">
                    <p style="font-size:16px;">Пока нет проектов в этой рабочей области</p>
                    <?php if (canManageProjects() || isGlobalAdmin()): ?>
                        <a href="project_edit.php?workspace_id=<?= $workspace_id ?>" class="btn btn-primary mt-2">Создать первый проект</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Название</th>
                                <th>Статус</th>
                                <th>Начало</th>
                                <th>Окончание</th>
                                <th>Заказчик</th>
                                <th style="min-width:200px;">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($projects as $p): ?>
                            <tr>
                                <td><?= $p['id'] ?></td>
                                <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
                                <td>
                                    <?php
                                    $statusClass = 'status-new';
                                    if (($p['status'] ?? 'Новый') == 'В процессе') $statusClass = 'status-process';
                                    elseif (($p['status'] ?? '') == 'Завершен') $statusClass = 'status-done';
                                    elseif (($p['status'] ?? '') == 'Заброшен') $statusClass = 'status-abandoned';
                                    ?>
                                    <span class="status-badge <?= $statusClass ?>">
                                        <?= htmlspecialchars($p['status'] ?? 'Новый') ?>
                                    </span>
                                </td>
                                <td><?= $p['start_date'] ?? '—' ?></td>
                                <td><?= $p['end_date'] ?? '—' ?></td>
                                <td><?= htmlspecialchars($p['customer_name'] ?? '—') ?></td>
                                <td>
                                    <div class="actions">
                                        <a href="project_manage.php?id=<?= $p['id'] ?>" class="btn btn-success btn-sm">📊 Управление</a>
                                        <?php if (canManageProjects()): ?>
                                            <a href="project_edit.php?id=<?= $p['id'] ?>" class="btn btn-info btn-sm">✏️ Ред.</a>
                                        <?php endif; ?>
                                        <?php if (canDeleteProjects()): ?>
                                            <a href="project_delete.php?id=<?= $p['id'] ?>" class="btn btn-danger btn-sm" 
                                               onclick="return confirm('Удалить проект?')">🗑️ Уд.</a>
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