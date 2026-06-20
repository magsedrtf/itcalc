<?php
require_once 'config.php';

$user_id = $_COOKIE['user_id'] ?? null;
if (!$user_id) {
    header('Location: login.php');
    exit;
}

$editId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$workspace = null;

if ($editId) {
    $stmt = $db->prepare("SELECT * FROM workspaces WHERE id = ?");
    $stmt->execute([$editId]);
    $workspace = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $subdomain = trim($_POST['subdomain']);
    $admin_user_id = (int)$_POST['admin_user_id'];

    if ($editId) {
        $stmt = $db->prepare("UPDATE workspaces SET name=?, subdomain=?, admin_user_id=? WHERE id=?");
        $stmt->execute([$name, $subdomain, $admin_user_id, $editId]);
    } else {
        $stmt = $db->prepare("INSERT INTO workspaces (name, subdomain, admin_user_id) VALUES (?,?,?)");
        $stmt->execute([$name, $subdomain, $admin_user_id]);
        $newWorkspaceId = $db->lastInsertId();

        $stmt = $db->prepare("INSERT INTO workspace_users (workspace_id, user_id, role) VALUES (?,?, 'Редактирование')");
        $stmt->execute([$newWorkspaceId, $user_id]);
    }

    header('Location: workspaces.php');
    exit;
}

$users = $db->query("SELECT id, last_name, first_name FROM users ORDER BY last_name")->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $editId ? 'Редактирование' : 'Создание' ?> рабочей области</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <a href="workspaces.php" class="back-link">← К списку рабочих областей</a>

        <div class="card max-w-md mx-auto">
            <div class="card-header">
                <h1><?= $editId ? '✏️ Редактирование' : '➕ Создание' ?> рабочей области</h1>
            </div>
            
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Название рабочей области <span class="required">*</span></label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($workspace['name'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Поддомен (например: myteam)</label>
                    <input type="text" name="subdomain" class="form-control" value="<?= htmlspecialchars($workspace['subdomain'] ?? '') ?>" placeholder="myteam">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Администратор рабочей области <span class="required">*</span></label>
                    <select name="admin_user_id" class="form-control" required>
                        <option value="">— Выберите администратора —</option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= ($workspace['admin_user_id'] ?? 0) == $u['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['last_name'] . ' ' . $u['first_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-success">💾 Сохранить рабочую область</button>
            </form>
        </div>
    </div>
</body>
</html>