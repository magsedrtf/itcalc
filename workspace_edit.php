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

        // Добавляем создателя как администратора
        $stmt = $db->prepare("INSERT INTO workspace_users (workspace_id, user_id, role) VALUES (?,?, 'Редактирование')");
        $stmt->execute([$newWorkspaceId, $user_id]);
    }

    header('Location: workspaces.php');
    exit;
}

// Получаем список пользователей для выбора администратора
$users = $db->query("SELECT id, last_name, first_name FROM users ORDER BY last_name")->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?= $editId ? 'Редактирование' : 'Создание' ?> рабочей области</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; }
        form { max-width: 600px; }
        label { display: block; margin: 15px 0 5px; font-weight: bold; }
        input, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #4CAF50; color: white; padding: 12px 25px; border: none; border-radius: 4px; cursor: pointer; }
    </style>
</head>
<body>
    <a href="workspaces.php">← К списку рабочих областей</a>
    <h1><?= $editId ? '✏️ Редактирование' : '➕ Создание' ?> рабочей области</h1>
    
    <form method="POST">
        <label>Название рабочей области *</label>
        <input type="text" name="name" value="<?= htmlspecialchars($workspace['name'] ?? '') ?>" required>
        
        <label>Поддомен (например: myteam)</label>
        <input type="text" name="subdomain" value="<?= htmlspecialchars($workspace['subdomain'] ?? '') ?>" placeholder="myteam">
        
        <label>Администратор рабочей области *</label>
        <select name="admin_user_id" required>
            <option value="">— Выберите администратора —</option>
            <?php foreach ($users as $u): ?>
                <option value="<?= $u['id'] ?>" <?= ($workspace['admin_user_id'] ?? 0) == $u['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($u['last_name'] . ' ' . $u['first_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <button type="submit">💾 Сохранить рабочую область</button>
    </form>
</body>
</html>