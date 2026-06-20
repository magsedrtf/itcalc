<?php
require_once 'config.php';

$user_id = $_COOKIE['user_id'] ?? null;
if (!$user_id) {
    header('Location: login.php');
    exit;
}

$resource_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$resource_id) {
    header('Location: projects.php');
    exit;
}


$stmt = $db->prepare("SELECT * FROM project_resources WHERE id = ?");
$stmt->execute([$resource_id]);
$resource = $stmt->fetch();

if (!$resource) die("Ресурс не найден");

$project_id = $resource['project_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resource_name = trim($_POST['resource_name']);
    $service_name = trim($_POST['service_name']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $quantity = (float)$_POST['quantity'];
    $unit_type = $_POST['unit_type'];
    $unit_cost = (float)$_POST['unit_cost'];
    $margin_percent = (float)$_POST['margin_percent'];

    $stmt = $db->prepare("UPDATE project_resources SET 
        resource_name=?, service_name=?, start_date=?, end_date=?, 
        quantity=?, unit_type=?, unit_cost=?, margin_percent=? 
        WHERE id=?");
    
    $stmt->execute([$resource_name, $service_name, $start_date, $end_date, 
                    $quantity, $unit_type, $unit_cost, $margin_percent, $resource_id]);

    header("Location: project_manage.php?id=$project_id");
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактирование ресурса</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; }
        form { max-width: 700px; }
        label { display: block; margin: 12px 0 5px; font-weight: bold; }
        input, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #4CAF50; color: white; padding: 12px 25px; border: none; border-radius: 4px; cursor: pointer; }
    </style>
</head>
<body>
    <a href="project_manage.php?id=<?= $project_id ?>">← Назад к проекту</a>
    <h1>Редактирование ресурса</h1>

    <form method="POST">
        <label>Название ресурса *</label>
        <input type="text" name="resource_name" value="<?= htmlspecialchars($resource['resource_name']) ?>" required>

        <label>Название услуги *</label>
        <input type="text" name="service_name" value="<?= htmlspecialchars($resource['service_name'] ?? '') ?>" required>

        <label>Дата начала *</label>
        <input type="date" name="start_date" value="<?= $resource['start_date'] ?>" required>

        <label>Дата окончания *</label>
        <input type="date" name="end_date" value="<?= $resource['end_date'] ?>" required>

        <label>Количество единиц *</label>
        <input type="number" name="quantity" step="0.01" value="<?= $resource['quantity'] ?>" required>

        <label>Единица измерения *</label>
        <select name="unit_type" required>
            <option value="часы" <?= $resource['unit_type']=='часы'?'selected':'' ?>>Часы</option>
            <option value="дни" <?= $resource['unit_type']=='дни'?'selected':'' ?>>Дни</option>
            <option value="полная стоимость" <?= $resource['unit_type']=='полная стоимость'?'selected':'' ?>>Полная стоимость</option>
        </select>

        <label>Стоимость за единицу (₽) *</label>
        <input type="number" name="unit_cost" step="0.01" value="<?= $resource['unit_cost'] ?>" required>

        <label>Маржинальность (%)</label>
        <input type="number" name="margin_percent" step="0.1" value="<?= $resource['margin_percent'] ?? 0 ?>">

        <button type="submit">Сохранить изменения</button>
    </form>
</body>
</html>