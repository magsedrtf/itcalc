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

$customer = null;
if ($editId) {
    $stmt = $db->prepare("SELECT * FROM customers WHERE id = ? AND workspace_id = ?");
    $stmt->execute([$editId, $workspace_id]);
    $customer = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['customer_type'];
    $inn = $_POST['inn'] ?: null;
    $name = $_POST['name'] ?: null;
    $director_name = $_POST['director_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];

    if ($editId) {
        $stmt = $db->prepare("UPDATE customers SET type=?, inn=?, name=?, director_name=?, email=?, phone=?, workspace_id=? WHERE id=?");
        $stmt->execute([$type, $inn, $name, $director_name, $email, $phone, $workspace_id, $editId]);
    } else {
        $stmt = $db->prepare("INSERT INTO customers (workspace_id, type, inn, name, director_name, email, phone) 
                              VALUES (?,?,?,?,?,?,?)");
        $stmt->execute([$workspace_id, $type, $inn, $name, $director_name, $email, $phone]);
    }

    header("Location: customers.php?workspace_id=$workspace_id");
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?= $editId ? 'Редактирование' : 'Добавление' ?> заказчика</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; }
        form { max-width: 600px; }
        label { display: block; margin: 12px 0 5px; font-weight: bold; }
        input, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #4CAF50; color: white; padding: 12px 25px; border: none; border-radius: 4px; cursor: pointer; }
    </style>
</head>
<body>
    <a href="customers.php?workspace_id=<?= $workspace_id ?>">← К списку заказчиков</a>
    <h1><?= $editId ? 'Редактирование' : 'Добавление' ?> заказчика</h1>
    
    <form method="POST">
        <label>Тип заказчика *</label>
        <select name="customer_type" required>
            <option value="Физическое лицо" <?= ($customer['type']??'')=='Физическое лицо'?'selected':'' ?>>Физическое лицо</option>
            <option value="Индивидуальный предприниматель" <?= ($customer['type']??'')=='Индивидуальный предприниматель'?'selected':'' ?>>ИП</option>
            <option value="Юридическое лицо" <?= ($customer['type']??'')=='Юридическое лицо'?'selected':'' ?>>Юридическое лицо</option>
        </select>

        <label>ИНН</label>
        <input type="text" name="inn" value="<?= htmlspecialchars($customer['inn'] ?? '') ?>">

        <label>Название (для ИП/ЮЛ)</label>
        <input type="text" name="name" value="<?= htmlspecialchars($customer['name'] ?? '') ?>">

        <label>ФИО руководителя / Заказчика *</label>
        <input type="text" name="director_name" value="<?= htmlspecialchars($customer['director_name'] ?? '') ?>" required>

        <label>Email *</label>
        <input type="email" name="email" value="<?= htmlspecialchars($customer['email'] ?? '') ?>" required>

        <label>Телефон *</label>
        <input type="text" name="phone" value="<?= htmlspecialchars($customer['phone'] ?? '') ?>" required>

        <button type="submit">Сохранить</button>
    </form>
</body>
</html>