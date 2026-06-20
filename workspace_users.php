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

// Проверяем доступ к workspace
$stmt = $db->prepare("SELECT * FROM workspace_users WHERE workspace_id = ? AND user_id = ?");
$stmt->execute([$workspace_id, $user_id]);
if (!$stmt->fetch()) {
    die("Нет доступа к этой рабочей области");
}

// Получаем информацию о workspace
$stmt = $db->prepare("SELECT * FROM workspaces WHERE id = ?");
$stmt->execute([$workspace_id]);
$workspace = $stmt->fetch();

// Получаем текущих участников
$stmt = $db->prepare("
    SELECT wu.*, u.last_name, u.first_name, u.email 
    FROM workspace_users wu
    JOIN users u ON wu.user_id = u.id
    WHERE wu.workspace_id = ?
    ORDER BY u.last_name
");
$stmt->execute([$workspace_id]);
$participants = $stmt->fetchAll();

// Добавление участника
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $new_user_id = (int)$_POST['user_id'];
    $role = $_POST['role'];

    $stmt = $db->prepare("INSERT INTO workspace_users (workspace_id, user_id, role) VALUES (?,?,?)");
    $stmt->execute([$workspace_id, $new_user_id, $role]);

    header("Location: workspace_users.php?workspace_id=$workspace_id");
    exit;
}

// Удаление участника
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
    <title>Участники: <?= htmlspecialchars($workspace['name']) ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 10px; }
        th { background: #4CAF50; color: white; }
        .btn { padding: 8px 15px; color: white; text-decoration: none; border-radius: 4px; }
        .btn-add { background: #4CAF50; }
        .btn-delete { background: #f44336; }
    </style>
</head>
<body>
    <a href="workspaces.php">← Все рабочие области</a>
    <h1>👥 Участники рабочей области: <?= htmlspecialchars($workspace['name']) ?></h1>

    <!-- Форма добавления -->
    <h3>+ Добавить участника</h3>
    <form method="POST">
        <select name="user_id" required>
            <option value="">— Выберите пользователя —</option>
            <?php
            $allUsers = $db->query("SELECT id, last_name, first_name FROM users ORDER BY last_name")->fetchAll();
            foreach ($allUsers as $u):
            ?>
                <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['last_name'].' '.$u['first_name']) ?></option>
            <?php endforeach; ?>
        </select>
        
        <select name="role" required>
            <option value="Просмотр">Просмотр</option>
            <option value="Редактирование">Редактирование</option>
        </select>
        
        <button type="submit" name="add_user" class="btn btn-add">Добавить</button>
    </form>

    <br><br>
    <table>
        <tr>
            <th>ФИО</th>
            <th>Email</th>
            <th>Права</th>
            <th>Действия</th>
        </tr>
        <?php foreach ($participants as $p): ?>
        <tr>
            <td><?= htmlspecialchars($p['last_name'].' '.$p['first_name']) ?></td>
            <td><?= htmlspecialchars($p['email']) ?></td>
            <td><?= htmlspecialchars($p['role']) ?></td>
            <td>
                <a href="?workspace_id=<?= $workspace_id ?>&delete=<?= $p['id'] ?>" 
                   class="btn btn-delete" onclick="return confirm('Удалить участника?')">Удалить</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>