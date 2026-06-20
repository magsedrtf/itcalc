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

$stmt = $db->prepare("SELECT * FROM subcontractors WHERE workspace_id = ? ORDER BY name, last_name");
$stmt->execute([$workspace_id]);
$subs = $stmt->fetchAll();

if (isset($_GET['delete'])) {
    $stmt = $db->prepare("DELETE FROM subcontractors WHERE id = ? AND workspace_id = ?");
    $stmt->execute([(int)$_GET['delete'], $workspace_id]);
    header("Location: subcontractors.php?workspace_id=$workspace_id");
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Реестр Субподрядчиков</title>
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
    <h1>🤝 Реестр Субподрядчиков (ЮЛ / ИП)</h1>
    
    <?php if (hasPermission('manage_customers')): ?>   <!-- или manage_employees — как хочешь -->
    <a href="subcontractor_edit.php?workspace_id=<?= $workspace_id ?>" class="btn btn-add">+ Добавить субподрядчика</a>
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
        <?php foreach ($subs as $s): ?>
        <tr>
            <td><?= $s['id'] ?></td>
            <td><?= htmlspecialchars($s['type']) ?></td>
            <td><?= htmlspecialchars($s['name'] ?: $s['last_name'] . ' ' . $s['first_name']) ?></td>
            <td><?= htmlspecialchars($s['inn'] ?? '—') ?></td>
            <td><?= htmlspecialchars($s['email'] ?? '—') ?></td>
            <td><?= htmlspecialchars($s['phone'] ?? '—') ?></td>
            <td>
                <a href="subcontractor_edit.php?id=<?= $s['id'] ?>&workspace_id=<?= $workspace_id ?>" class="btn" style="background:#2196F3">Ред.</a>
                <a href="?delete=<?= $s['id'] ?>&workspace_id=<?= $workspace_id ?>" class="btn" style="background:#f44336" onclick="return confirm('Удалить?')">Уд.</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>