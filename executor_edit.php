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
        $stmt = $db->prepare("INSERT INTO executors (last_name, first_name, middle_name, contract_type, tax_rate, unit_type, unit_cost, workspace_id) VALUES (?,?,?,?,?,?,?,?)");
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $editId ? 'Редактирование' : 'Добавление' ?> исполнителя</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <a href="executors.php?workspace_id=<?= $workspace_id ?>" class="back-link">← К списку исполнителей</a>

        <div class="card max-w-md mx-auto">
            <div class="card-header">
                <h1><?= $editId ? '✏️ Редактирование' : '➕ Добавление' ?> исполнителя</h1>
            </div>
            
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Фамилия <span class="required">*</span></label>
                        <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($executor['last_name'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Имя <span class="required">*</span></label>
                        <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($executor['first_name'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Отчество</label>
                    <input type="text" name="middle_name" class="form-control" value="<?= htmlspecialchars($executor['middle_name'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Тип оформления <span class="required">*</span></label>
                    <select name="contract_type" class="form-control" required>
                        <option value="ГПХ" <?= ($executor['contract_type']??'')=='ГПХ'?'selected':'' ?>>ГПХ</option>
                        <option value="НПД" <?= ($executor['contract_type']??'')=='НПД'?'selected':'' ?>>НПД (самозанятый)</option>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Налоговая ставка, %</label>
                        <input type="number" step="0.1" name="tax_rate" class="form-control" value="<?= $executor['tax_rate'] ?? '0' ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Единица измерения <span class="required">*</span></label>
                        <select name="unit_type" class="form-control" required>
                            <option value="часы" <?= ($executor['unit_type']??'')=='часы'?'selected':'' ?>>Часы</option>
                            <option value="дни" <?= ($executor['unit_type']??'')=='дни'?'selected':'' ?>>Дни</option>
                            <option value="полная стоимость" <?= ($executor['unit_type']??'')=='полная стоимость'?'selected':'' ?>>Полная стоимость</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Стоимость за единицу, ₽ <span class="required">*</span></label>
                    <input type="number" step="0.01" name="unit_cost" class="form-control" value="<?= $executor['unit_cost'] ?? '' ?>" required>
                </div>

                <button type="submit" class="btn btn-success">💾 Сохранить</button>
            </form>
        </div>
    </div>
</body>
</html>