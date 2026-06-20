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

$stmt = $db->prepare("SELECT * FROM customers WHERE workspace_id = ? ORDER BY name, director_name");
$stmt->execute([$workspace_id]);
$customers = $stmt->fetchAll();

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Проверяем, есть ли проекты с этим заказчиком
    $check = $db->prepare("SELECT COUNT(*) FROM projects WHERE customer_id = ?");
    $check->execute([$id]);
    if ($check->fetchColumn() > 0) {
        die("Нельзя удалить заказчика, так как он используется в проектах!");
    }
    
    $stmt = $db->prepare("DELETE FROM customers WHERE id = ? AND workspace_id = ?");
    $stmt->execute([$id, $workspace_id]);
    header("Location: customers.php?workspace_id=$workspace_id");
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Реестр Заказчиков</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 10px; }
        th { background: #4CAF50; color: white; }
        .btn { padding: 6px 12px; color: white; text-decoration: none; border-radius: 4px; }
        .btn-add { background: #4CAF50; }
    </style>
</head>
<body>
    <a href="projects.php?workspace_id=<?= $workspace_id ?>">← К проектам</a>
    <h1>Реестр Заказчиков</h1>
    
    <?php if (hasPermission('manage_customers')): ?>
    <a href="customer_edit.php?workspace_id=<?= $workspace_id ?>" class="btn btn-add">+ Добавить заказчика</a>
    <?php endif; ?>
    
    <table>
        <tr>
            <th>ID</th>
            <th>Тип</th>
            <th>Название / ФИО</th>
            <th>ИНН</th>
            <th>Email</th>
            <th>Телефон</th>
            <th>Действия</th>
        </tr>
        <?php foreach ($customers as $c): ?>
        <tr>
            <td><?= $c['id'] ?></td>
            <td><?= htmlspecialchars($c['type']) ?></td>
            <td><?= htmlspecialchars($c['name'] ?: $c['director_name']) ?></td>
            <td><?= htmlspecialchars($c['inn'] ?? '—') ?></td>
            <td><?= htmlspecialchars($c['email'] ?? '—') ?></td>
            <td><?= htmlspecialchars($c['phone'] ?? '—') ?></td>
            <td>
                <a href="customer_edit.php?id=<?= $c['id'] ?>&workspace_id=<?= $workspace_id ?>" class="btn" style="background:#2196F3">Ред.</a>
                <a href="?delete=<?= $c['id'] ?>&workspace_id=<?= $workspace_id ?>" class="btn" style="background:#f44336" onclick="return confirm('Удалить?')">Уд.</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>