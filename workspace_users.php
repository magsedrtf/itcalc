<?php
require_once 'config.php';

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

$stmt = $db->prepare("SELECT * FROM workspace_users WHERE workspace_id = ? AND user_id = ?");
$stmt->execute([$workspace_id, $user_id]);
if (!$stmt->fetch()) {
    die("Нет доступа к этой рабочей области");
}

$stmt = $db->prepare("SELECT * FROM workspaces WHERE id = ?");
$stmt->execute([$workspace_id]);
$workspace = $stmt->fetch();

$stmt = $db->prepare("
    SELECT wu.*, u.last_name, u.first_name, u.email 
    FROM workspace_users wu
    JOIN users u ON wu.user_id = u.id
    WHERE wu.workspace_id = ?
    ORDER BY u.last_name
");
$stmt->execute([$workspace_id]);
$participants = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $new_user_id = (int)$_POST['user_id'];
    $role = $_POST['role'];

    $stmt = $db->prepare("INSERT INTO workspace_users (workspace_id, user_id, role) VALUES (?,?,?)");
    $stmt->execute([$workspace_id, $new_user_id, $role]);

    header("Location: workspace_users.php?workspace_id=$workspace_id");
    exit;
}

if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    $stmt = $db->prepare("DELETE FROM workspace_users WHERE id = ? AND workspace_id = ?");
    $stmt->execute([$delete_id, $workspace_id]);
    header("Location: workspace_users.php?workspace_id=$workspace_id");
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Участники рабочей области</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <a href="workspaces.php" class="back-link">← Все рабочие области</a>

        <div class="card">
            <div class="card-header">
                <div>
                    <h1>👥 Участники</h1>
                    <p class="page-subtitle">Рабочая область: <?= htmlspecialchars($workspace['name']) ?></p>
                </div>
            </div>

            <div class="card" style="background:var(--gray-50);">
                <h3>➕ Добавить участника</h3>
                <form method="POST" style="display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end;">
                    <div class="form-group" style="flex:1; min-width:180px; margin-bottom:0;">
                        <label class="form-label">Пользователь</label>
                        <select name="user_id" class="form-control" required>
                            <option value="">— Выберите —</option>
                            <?php
                            $allUsers = $db->query("SELECT id, last_name, first_name FROM users ORDER BY last_name")->fetchAll();
                            foreach ($allUsers as $u): ?>
                                <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['last_name'].' '.$u['first_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group" style="flex:1; min-width:140px; margin-bottom:0;">
                        <label class="form-label">Права</label>
                        <select name="role" class="form-control" required>
                            <option value="Просмотр">Просмотр</option>
                            <option value="Редактирование">Редактирование</option>
                        </select>
                    </div>
                    
                    <button type="submit" name="add_user" class="btn btn-success" style="margin-bottom:2px;">➕ Добавить</button>
                </form>
            </div>

            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ФИО</th>
                            <th>Email</th>
                            <th>Права</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($participants as $p): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($p['last_name'].' '.$p['first_name']) ?></strong></td>
                            <td><?= htmlspecialchars($p['email']) ?></td>
                            <td>
                                <span class="role-badge"><?= htmlspecialchars($p['role']) ?></span>
                            </td>
                            <td>
                                <a href="?workspace_id=<?= $workspace_id ?>&delete=<?= $p['id'] ?>" 
                                   class="btn btn-danger btn-sm" onclick="return confirm('Удалить участника?')">🗑️ Удалить</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>