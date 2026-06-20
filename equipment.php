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

$stmt = $db->prepare("SELECT * FROM equipment WHERE workspace_id = ? ORDER BY name");
$stmt->execute([$workspace_id]);
$equipment = $stmt->fetchAll();

if (isset($_GET['delete'])) {
    $stmt = $db->prepare("DELETE FROM equipment WHERE id = ? AND workspace_id = ?");
    $stmt->execute([(int)$_GET['delete'], $workspace_id]);
    header("Location: equipment.php?workspace_id=$workspace_id");
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Реестр Оборудования</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <a href="projects.php?workspace_id=<?= $workspace_id ?>" class="back-link">← К проектам</a>

        <div class="card">
            <div class="card-header">
                <div>
                    <h1>🖥️ Реестр Оборудования</h1>
                    <p class="page-subtitle">Техника и оборудование</p>
                </div>
                <?php if (hasPermission('manage_employees')): ?>
                    <a href="equipment_edit.php?workspace_id=<?= $workspace_id ?>" class="btn btn-success">+ Добавить оборудование</a>
                <?php endif; ?>
            </div>

            <?php if (empty($equipment)): ?>
                <div class="text-center" style="padding:30px 0; color:var(--gray-500);">
                    <p>Пока нет оборудования</p>
                </div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Название</th>
                                <th>Тип приобретения</th>
                                <th>Ед.изм</th>
                                <th>Стоимость за ед.</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($equipment as $e): ?>
                            <tr>
                                <td><?= $e['id'] ?></td>
                                <td><strong><?= htmlspecialchars($e['name']) ?></strong></td>
                                <td><?= htmlspecialchars($e['acquisition_type']) ?></td>
                                <td><?= htmlspecialchars($e['unit_type']) ?></td>
                                <td><?= number_format($e['unit_cost'], 2) ?></td>
                                <td>
                                    <div class="actions">
                                        <a href="equipment_edit.php?id=<?= $e['id'] ?>&workspace_id=<?= $workspace_id ?>" class="btn btn-info btn-sm">✏️ Ред.</a>
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