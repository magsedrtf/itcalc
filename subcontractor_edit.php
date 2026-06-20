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

$editId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$sub = null;
if ($editId) {
    $stmt = $db->prepare("SELECT * FROM subcontractors WHERE id = ? AND workspace_id = ?");
    $stmt->execute([$editId, $workspace_id]);
    $sub = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'];
    $inn = $_POST['inn'] ?: null;
    $name = $_POST['name'] ?: null;
    $last_name = $_POST['last_name'] ?: null;
    $first_name = $_POST['first_name'] ?: null;
    $email = $_POST['email'];
    $phone = $_POST['phone'];

    if ($editId) {
        $stmt = $db->prepare("UPDATE subcontractors SET type=?, inn=?, name=?, last_name=?, first_name=?, email=?, phone=?, workspace_id=? WHERE id=?");
        $stmt->execute([$type, $inn, $name, $last_name, $first_name, $email, $phone, $workspace_id, $editId]);
    } else {
        $stmt = $db->prepare("INSERT INTO subcontractors (workspace_id, type, inn, name, last_name, first_name, email, phone) 
                              VALUES (?,?,?,?,?,?,?,?)");
        $stmt->execute([$workspace_id, $type, $inn, $name, $last_name, $first_name, $email, $phone]);
    }

    header("Location: subcontractors.php?workspace_id=$workspace_id");
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?= $editId ? 'Редактирование' : 'Добавление' ?> субподрядчика</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; }
        form { max-width: 600px; }
        label { display: block; margin: 12px 0 5px; font-weight: bold; }
        input, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #4CAF50; color: white; padding: 12px 25px; border: none; border-radius: 4px; cursor: pointer; }
    </style>
</head>
<body>
    <a href="subcontractors.php?workspace_id=<?= $workspace_id ?>">← К списку субподрядчиков</a>
    <h1><?= $editId ? '✏️ Редактирование' : '➕ Добавление' ?> субподрядчика</h1>
    
    <form method="POST">
        <label>Тип *</label>
        <select name="type" required>
            <option value="Индивидуальный предприниматель" <?= ($sub['type']??'')=='Индивидуальный предприниматель'?'selected':'' ?>>ИП</option>
            <option value="Юридическое лицо" <?= ($sub['type']??'')=='Юридическое лицо'?'selected':'' ?>>Юридическое лицо</option>
        </select>

        <label>ИНН</label>
        <input type="text" name="inn" value="<?= htmlspecialchars($sub['inn'] ?? '') ?>">

        <label>Название организации</label>
        <input type="text" name="name" value="<?= htmlspecialchars($sub['name'] ?? '') ?>">

        <label>Фамилия руководителя</label>
        <input type="text" name="last_name" value="<?= htmlspecialchars($sub['last_name'] ?? '') ?>">

        <label>Имя руководителя</label>
        <input type="text" name="first_name" value="<?= htmlspecialchars($sub['first_name'] ?? '') ?>">

        <label>Email *</label>
        <input type="email" name="email" value="<?= htmlspecialchars($sub['email'] ?? '') ?>" required>

        <label>Телефон</label>
        <input type="text" name="phone" value="<?= htmlspecialchars($sub['phone'] ?? '') ?>">

        <button type="submit">💾 Сохранить</button>
    </form>
</body>
</html>