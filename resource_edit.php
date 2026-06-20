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

    $stmt = $db->prepare("UPDATE project_resources SET resource_name=?, service_name=?, start_date=?, end_date=?, quantity=?, unit_type=?, unit_cost=?, margin_percent=? WHERE id=?");
    $stmt->execute([$resource_name, $service_name, $start_date, $end_date, $quantity, $unit_type, $unit_cost, $margin_percent, $resource_id]);

    header("Location: project_manage.php?id=$project_id");
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактирование ресурса</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <a href="project_manage.php?id=<?= $project_id ?>" class="back-link">← Назад к проекту</a>

        <div class="card max-w-lg mx-auto">
            <div class="card-header">
                <h1>✏️ Редактирование ресурса</h1>
            </div>
            
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Название ресурса <span class="required">*</span></label>
                    <input type="text" name="resource_name" class="form-control" value="<?= htmlspecialchars($resource['resource_name']) ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Название услуги <span class="required">*</span></label>
                    <input type="text" name="service_name" class="form-control" value="<?= htmlspecialchars($resource['service_name'] ?? '') ?>" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Дата начала <span class="required">*</span></label>
                        <input type="date" name="start_date" class="form-control" value="<?= $resource['start_date'] ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Дата окончания <span class="required">*</span></label>
                        <input type="date" name="end_date" class="form-control" value="<?= $resource['end_date'] ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Количество <span class="required">*</span></label>
                        <input type="number" name="quantity" step="0.01" class="form-control" value="<?= $resource['quantity'] ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Единица измерения <span class="required">*</span></label>
                        <select name="unit_type" class="form-control" required>
                            <option value="часы" <?= $resource['unit_type']=='часы'?'selected':'' ?>>Часы</option>
                            <option value="дни" <?= $resource['unit_type']=='дни'?'selected':'' ?>>Дни</option>
                            <option value="полная стоимость" <?= $resource['unit_type']=='полная стоимость'?'selected':'' ?>>Полная стоимость</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Стоимость за ед. (₽) <span class="required">*</span></label>
                        <input type="number" name="unit_cost" step="0.01" class="form-control" value="<?= $resource['unit_cost'] ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Маржинальность (%)</label>
                        <input type="number" name="margin_percent" step="0.1" class="form-control" value="<?= $resource['margin_percent'] ?? 0 ?>">
                    </div>
                </div>

                <button type="submit" class="btn btn-success">💾 Сохранить изменения</button>
            </form>
        </div>
    </div>
</body>
</html>