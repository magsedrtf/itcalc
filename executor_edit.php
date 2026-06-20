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

$executor = null;
if ($editId) {
    $stmt = $db->prepare("SELECT * FROM executors WHERE id = ? AND workspace_id = ?");
    $stmt->execute([$editId, $workspace_id]);
    $executor = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $last_name = $_POST['last_name'];
    $first_name = $_POST['first_name'];
    $middle_name = $_POST['middle_name'] ?: null;
    $contract_type = $_POST['contract_type'];
    $tax_rate = (float)$_POST['tax_rate'];
    $unit_type = $_POST['unit_type'];
    $unit_cost = (float)$_POST['unit_cost'];

    if ($editId) {
        $stmt = $db->prepare("UPDATE executors SET last_name=?, first_name=?, middle_name=?, contract_type=?, tax_rate=?, unit_type=?, unit_cost=?, workspace_id=? WHERE id=?");
        $stmt->execute([$last_name, $first_name, $middle_name, $contract_type, $tax_rate, $unit_type, $unit_cost, $workspace_id, $editId]);
    } else {
        $stmt = $db->prepare("INSERT INTO executors (last_name, first_name, middle_name, contract_type, tax_rate, unit_type, unit_cost, workspace_id) 
                              VALUES (?,?,?,?,?,?,?,?)");
        $stmt->execute([$last_name, $first_name, $middle_name, $contract_type, $tax_rate, $unit_type, $unit_cost, $workspace_id]);
    }

    header("Location: executors.php?workspace_id=$workspace_id");
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?= $editId ? 'Редактирование' : 'Добавление' ?> исполнителя</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; }
        form { max-width: 600px; }
        label { display: block; margin: 12px 0 5px; font-weight: bold; }
        input, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #4CAF50; color: white; padding: 12px 25px; border: none; border-radius: 4px; cursor: pointer; }
    </style>
</head>
<body>
    <a href="executors.php?workspace_id=<?= $workspace_id ?>">← К списку исполнителей</a>
    <h1><?= $editId ? 'Редактирование' : 'Добавление' ?> исполнителя</h1>
    
    <form method="POST">
        <label>Фамилия *</label>
        <input type="text" name="last_name" value="<?= htmlspecialchars($executor['last_name'] ?? '') ?>" required>

        <label>Имя *</label>
        <input type="text" name="first_name" value="<?= htmlspecialchars($executor['first_name'] ?? '') ?>" required>

        <label>Отчество</label>
        <input type="text" name="middle_name" value="<?= htmlspecialchars($executor['middle_name'] ?? '') ?>">

        <label>Тип оформления *</label>
        <select name="contract_type" required>
            <option value="ГПХ" <?= ($executor['contract_type']??'')=='ГПХ'?'selected':'' ?>>ГПХ</option>
            <option value="НПД" <?= ($executor['contract_type']??'')=='НПД'?'selected':'' ?>>НПД (самозанятый)</option>
        </select>

        <label>Налоговая ставка, %</label>
        <input type="number" step="0.1" name="tax_rate" value="<?= $executor['tax_rate'] ?? '0' ?>">

        <label>Единица измерения *</label>
        <select name="unit_type" required>
            <option value="часы">Часы</option>
            <option value="дни">Дни</option>
            <option value="полная стоимость">Полная стоимость</option>
        </select>

        <label>Стоимость за единицу, ₽ *</label>
        <input type="number" step="0.01" name="unit_cost" value="<?= $executor['unit_cost'] ?? '' ?>" required>

        <button type="submit">Сохранить</button>
    </form>
</body>
</html>