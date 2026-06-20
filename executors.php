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
    header('Location: workspaces.php');
    exit;
}
if (!hasPermission('manage_employees') && !isGlobalAdmin()) {
    header('Location: projects.php?workspace_id=' . $workspace_id);
    exit;
}

$stmt = $db->prepare("SELECT * FROM executors WHERE workspace_id = ? ORDER BY last_name");
$stmt->execute([$workspace_id]);
$executors = $stmt->fetchAll();

if (isset($_GET['delete'])) {
    $stmt = $db->prepare("DELETE FROM executors WHERE id = ? AND workspace_id = ?");
    $stmt->execute([(int)$_GET['delete'], $workspace_id]);
    header("Location: executors.php?workspace_id=$workspace_id");
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Реестр Исполнителей</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <a href="projects.php?workspace_id=<?= $workspace_id ?>" class="back-link">← К проектам</a>

        <div class="card">
            <div class="card-header">
                <div>
                    <h1>👤 Реестр Исполнителей</h1>
                    <p class="page-subtitle">Физические лица — исполнители</p>
                </div>
                <?php if (hasPermission('manage_employees')): ?>
                    <a href="executor_edit.php?workspace_id=<?= $workspace_id ?>" class="btn btn-success">+ Добавить исполнителя</a>
                <?php endif; ?>
            </div>

            <?php if (empty($executors)): ?>
                <div class="text-center" style="padding:30px 0; color:var(--gray-500);">
                    <p>Пока нет исполнителей</p>
                </div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>ФИО</th>
                                <th>Тип оформления</th>
                                <th>Ставка налога</th>
                                <th>Ед.изм</th>
                                <th>Стоимость за ед.</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($executors as $e): ?>
                            <tr>
                                <td><?= $e['id'] ?></td>
                                <td><strong><?= htmlspecialchars($e['last_name'] . ' ' . $e['first_name']) ?></strong></td>
                                <td><?= htmlspecialchars($e['contract_type']) ?></td>
                                <td><?= $e['tax_rate'] ?>%</td>
                                <td><?= htmlspecialchars($e['unit_type']) ?></td>
                                <td><?= number_format($e['unit_cost'], 2) ?></td>
                                <td>
                                    <div class="actions">
                                        <a href="executor_edit.php?id=<?= $e['id'] ?>&workspace_id=<?= $workspace_id ?>" class="btn btn-info btn-sm">✏️ Ред.</a>
                                        <a href="?delete=<?= $e['id'] ?>&workspace_id=<?= $workspace_id ?>" class="btn btn-danger btn-sm" 
                                           onclick="return confirm('Удалить?')">🗑️ Уд.</a>
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