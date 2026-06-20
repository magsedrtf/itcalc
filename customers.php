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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Реестр Заказчиков</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <a href="projects.php?workspace_id=<?= $workspace_id ?>" class="back-link">← К проектам</a>

        <div class="card">
            <div class="card-header">
                <div>
                    <h1>🏢 Реестр Заказчиков</h1>
                    <p class="page-subtitle">Управление клиентами</p>
                </div>
                <?php if (hasPermission('manage_customers')): ?>
                    <a href="customer_edit.php?workspace_id=<?= $workspace_id ?>" class="btn btn-success">+ Добавить заказчика</a>
                <?php endif; ?>
            </div>

            <?php if (empty($customers)): ?>
                <div class="text-center" style="padding:30px 0; color:var(--gray-500);">
                    <p>Пока нет заказчиков</p>
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
                            <?php foreach ($customers as $c): ?>
                            <tr>
                                <td><?= $c['id'] ?></td>
                                <td><?= htmlspecialchars($c['type']) ?></td>
                                <td><strong><?= htmlspecialchars($c['name'] ?: $c['director_name']) ?></strong></td>
                                <td><?= htmlspecialchars($c['inn'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($c['email'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($c['phone'] ?? '—') ?></td>
                                <td>
                                    <div class="actions">
                                        <a href="customer_edit.php?id=<?= $c['id'] ?>&workspace_id=<?= $workspace_id ?>" class="btn btn-info btn-sm">✏️ Ред.</a>
                                        <a href="?delete=<?= $c['id'] ?>&workspace_id=<?= $workspace_id ?>" class="btn btn-danger btn-sm" 
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