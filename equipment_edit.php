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
        $stmt = $db->prepare("INSERT INTO equipment (name, description, acquisition_type, unit_type, unit_cost, workspace_id) VALUES (?,?,?,?,?,?)");
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $editId ? 'Редактирование' : 'Добавление' ?> оборудования</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <a href="equipment.php?workspace_id=<?= $workspace_id ?>" class="back-link">← К списку оборудования</a>

        <div class="card max-w-md mx-auto">
            <div class="card-header">
                <h1><?= $editId ? '✏️ Редактирование' : '➕ Добавление' ?> оборудования</h1>
            </div>
            
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Название оборудования <span class="required">*</span></label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($eq['name'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Описание</label>
                    <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($eq['description'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Тип приобретения <span class="required">*</span></label>
                    <select name="acquisition_type" class="form-control" required>
                        <option value="Собственное" <?= ($eq['acquisition_type']??'')=='Собственное'?'selected':'' ?>>Собственное</option>
                        <option value="В аренде" <?= ($eq['acquisition_type']??'')=='В аренде'?'selected':'' ?>>В аренде</option>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Единица измерения <span class="required">*</span></label>
                        <select name="unit_type" class="form-control" required>
                            <option value="часы" <?= ($eq['unit_type']??'')=='часы'?'selected':'' ?>>Часы</option>
                            <option value="дни" <?= ($eq['unit_type']??'')=='дни'?'selected':'' ?>>Дни</option>
                            <option value="полная стоимость" <?= ($eq['unit_type']??'')=='полная стоимость'?'selected':'' ?>>Полная стоимость</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Стоимость за ед., ₽ <span class="required">*</span></label>
                        <input type="number" step="0.01" name="unit_cost" class="form-control" value="<?= $eq['unit_cost'] ?? '' ?>" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-success">💾 Сохранить</button>
            </form>
        </div>
    </div>
</body>
</html>