<?php
require_once 'config.php';
require_once 'auth.php';
if (!hasPermission('manage_users')) {
    header('Location: index.php');
    exit;
}

$user_id = $_COOKIE['user_id'] ?? null;
if (!$user_id) {
    header('Location: login.php');
    exit;
}

$editId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$editId) header('Location: team.php');

$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$editId]);
$member = $stmt->fetch();

if (!$member) die("Пользователь не найден");


$allPermissions = [
    'view_margin'       => 'Видеть маржинальность и прибыль',
    'manage_projects'   => 'Создавать и редактировать проекты',
    'delete_projects'   => 'Удалять проекты',
    'manage_resources'  => 'Управлять ресурсами в проектах',
    'generate_documents'=> 'Генерировать документы (КП, НМА)',
    'manage_customers'  => 'Управлять заказчиками',
    'manage_employees'  => 'Работать с реестрами',
    'manage_users'      => 'Управлять пользователями и ролями',
    'view_user_credentials'    => 'Просмотр логинов и паролей других пользователей'
];

$currentPerm = [];
$stmt = $db->prepare("SELECT permission, value FROM user_permissions WHERE user_id = ?");
$stmt->execute([$editId]);
foreach ($stmt->fetchAll() as $p) {
    $currentPerm[$p['permission']] = $p['value'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $stmt = $db->prepare("UPDATE users SET first_name=?, last_name=?, position=? WHERE id=?");
    $stmt->execute([$_POST['first_name'], $_POST['last_name'], $_POST['position'], $editId]);


    $db->prepare("DELETE FROM user_permissions WHERE user_id=?")->execute([$editId]);

    foreach ($allPermissions as $perm => $label) {
        $value = isset($_POST['perm'][$perm]) ? 1 : 0;
        $stmt = $db->prepare("INSERT INTO user_permissions (user_id, permission, value) VALUES (?,?,?)");
        $stmt->execute([$editId, $perm, $value]);
    }

    header('Location: team.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактировать права — <?= htmlspecialchars($member['first_name']) ?></title>
    <style>
        body { font-family: Arial; margin: 30px; }
        label { display: block; margin: 8px 0; }
    </style>
</head>
<body>
    <a href="team.php">← Назад</a>
    <h1>Редактирование прав пользователя</h1>

    <form method="POST">
        <label>Имя</label>
        <input type="text" name="first_name" value="<?= htmlspecialchars($member['first_name']) ?>" required>

        <label>Фамилия</label>
        <input type="text" name="last_name" value="<?= htmlspecialchars($member['last_name']) ?>" required>

        <label>Должность</label>
        <input type="text" name="position" value="<?= htmlspecialchars($member['position']) ?>">

        <h3>Права и функции</h3>
        <?php foreach ($allPermissions as $perm => $label): ?>
            <label>
                <input type="checkbox" name="perm[<?= $perm ?>]" <?= ($currentPerm[$perm] ?? 1) ? 'checked' : '' ?>>
                <?= $label ?>
            </label>
        <?php endforeach; ?>

        <button type="submit">Сохранить права</button>
    </form>
</body>
</html>