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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Реестр Субподрядчиков</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <a href="projects.php?workspace_id=<?= $workspace_id ?>" class="back-link">← К проектам</a>

        <div class="card">
            <div class="card-header">
                <div>
                    <h1>🤝 Реестр Субподрядчиков</h1>
                    <p class="page-subtitle">Юридические лица и ИП</p>
                </div>
                <?php if (hasPermission('manage_customers')): ?>
                    <a href="subcontractor_edit.php?workspace_id=<?= $workspace_id ?>" class="btn btn-success">+ Добавить субподрядчика</a>
                <?php endif; ?>
            </div>

            <?php if (empty($subs)): ?>
                <div class="text-center" style="padding:30px 0; color:var(--gray-500);">
                    <p>Пока нет субподрядчиков</p>
                </div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Тип</th>
                                <th>Название / ФИО</th>
                                <th>ИНН</th>
                                <th>Email</th>
                                <th>Телефон</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($subs as $s): ?>
                            <tr>
                                <td><?= $s['id'] ?></td>
                                <td><?= htmlspecialchars($s['type']) ?></td>
                                <td><strong><?= htmlspecialchars($s['name'] ?: $s['last_name'] . ' ' . $s['first_name']) ?></strong></td>
                                <td><?= htmlspecialchars($s['inn'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($s['email'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($s['phone'] ?? '—') ?></td>
                                <td>
                                    <div class="actions">
                                        <a href="subcontractor_edit.php?id=<?= $s['id'] ?>&workspace_id=<?= $workspace_id ?>" class="btn btn-info btn-sm">✏️ Ред.</a>
                                        <a href="?delete=<?= $s['id'] ?>&workspace_id=<?= $workspace_id ?>" class="btn btn-danger btn-sm" 
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