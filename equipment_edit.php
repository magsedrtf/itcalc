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

$eq = null;
if ($editId) {
    $stmt = $db->prepare("SELECT * FROM equipment WHERE id = ? AND workspace_id = ?");
    $stmt->execute([$editId, $workspace_id]);
    $eq = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $description = $_POST['description'] ?: null;
    $acquisition_type = $_POST['acquisition_type'];
    $unit_type = $_POST['unit_type'];
    $unit_cost = (float)$_POST['unit_cost'];

    if ($editId) {
        $stmt = $db->prepare("UPDATE equipment SET name=?, description=?, acquisition_type=?, unit_type=?, unit_cost=?, workspace_id=? WHERE id=?");
        $stmt->execute([$name, $description, $acquisition_type, $unit_type, $unit_cost, $workspace_id, $editId]);
    } else {
        $stmt = $db->prepare("INSERT INTO equipment (name, description, acquisition_type, unit_type, unit_cost, workspace_id) 
                              VALUES (?,?,?,?,?,?)");
        $stmt->execute([$name, $description, $acquisition_type, $unit_type, $unit_cost, $workspace_id]);
    }

    header("Location: equipment.php?workspace_id=$workspace_id");
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?= $editId ? 'Редактирование' : 'Добавление' ?> оборудования</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; }
        form { max-width: 600px; }
        label { display: block; margin: 12px 0 5px; font-weight: bold; }
        input, select, textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #4CAF50; color: white; padding: 12px 25px; border: none; border-radius: 4px; cursor: pointer; }
    </style>
</head>
<body>
    <a href="equipment.php?workspace_id=<?= $workspace_id ?>">← К списку оборудования</a>
    <h1><?= $editId ? 'Редактирование' : 'Добавление' ?> оборудования</h1>
    
    <form method="POST">
        <label>Название оборудования *</label>
        <input type="text" name="name" value="<?= htmlspecialchars($eq['name'] ?? '') ?>" required>

        <label>Описание</label>
        <textarea name="description" rows="3"><?= htmlspecialchars($eq['description'] ?? '') ?></textarea>

        <label>Тип приобретения *</label>
        <select name="acquisition_type" required>
            <option value="Собственное" <?= ($eq['acquisition_type']??'')=='Собственное'?'selected':'' ?>>Собственное</option>
            <option value="В аренде" <?= ($eq['acquisition_type']??'')=='В аренде'?'selected':'' ?>>В аренде</option>
        </select>

        <label>Единица измерения *</label>
        <select name="unit_type" required>
            <option value="часы">Часы</option>
            <option value="дни">Дни</option>
            <option value="полная стоимость">Полная стоимость</option>
        </select>

        <label>Стоимость за единицу, ₽ *</label>
        <input type="number" step="0.01" name="unit_cost" value="<?= $eq['unit_cost'] ?? '' ?>" required>

        <button type="submit">Сохранить</button>
    </form>
</body>
</html>