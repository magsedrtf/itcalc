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
    <title>Реестр Исполнителей</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #4CAF50; color: white; }
        .btn { padding: 6px 12px; color: white; text-decoration: none; border-radius: 4px; }
        .btn-add { background: #4CAF50; }
    </style>
</head>
<body>
    <a href="projects.php?workspace_id=<?= $workspace_id ?>">← К проектам</a>
    <h1>Реестр Исполнителей (ФЛ)</h1>
    
    <?php if (hasPermission('manage_employees')): ?>
    <a href="executor_edit.php?workspace_id=<?= $workspace_id ?>" class="btn btn-add">+ Добавить исполнителя</a>
    <?php endif; ?>
    
    <table>
        <tr>
            <th>ID</th>
            <th>ФИО</th>
            <th>Тип оформления</th>
            <th>Ставка налога</th>
            <th>Ед.изм</th>
            <th>Стоимость за ед.</th>
            <th>Действия</th>
        </tr>
        <?php foreach ($executors as $e): ?>
        <tr>
            <td><?= $e['id'] ?></td>
            <td><?= htmlspecialchars($e['last_name'] . ' ' . $e['first_name']) ?></td>
            <td><?= htmlspecialchars($e['contract_type']) ?></td>
            <td><?= $e['tax_rate'] ?>%</td>
            <td><?= htmlspecialchars($e['unit_type']) ?></td>
            <td><?= number_format($e['unit_cost'], 2) ?></td>
            <td>
                <a href="executor_edit.php?id=<?= $e['id'] ?>&workspace_id=<?= $workspace_id ?>" class="btn" style="background:#2196F3">Ред.</a>
                <a href="?delete=<?= $e['id'] ?>&workspace_id=<?= $workspace_id ?>" class="btn" style="background:#f44336" onclick="return confirm('Удалить?')">Уд.</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>