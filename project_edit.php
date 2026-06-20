<?php
require_once 'config.php';

$user_id = $_COOKIE['user_id'] ?? null;
if (!$user_id) {
    header('Location: login.php');
    exit;
}

$workspace_id = isset($_GET['workspace_id']) ? (int)$_GET['workspace_id'] : 0;
if (!$workspace_id) {
    $stmt = $db->prepare("SELECT workspace_id FROM workspace_users WHERE user_id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $row = $stmt->fetch();
    $workspace_id = $row ? $row['workspace_id'] : 0;
}
if (!$workspace_id) {
    die("Сначала создайте рабочую область");
}

$editId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$project = null;
if ($editId) {
    $stmt = $db->prepare("SELECT * FROM projects WHERE id = ?");
    $stmt->execute([$editId]);
    $project = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $description = trim($_POST['description'] ?? '');
    $technical_task = trim($_POST['technical_task'] ?? '');
    $tax_rate = (float)$_POST['tax_rate'];
    $status = $_POST['status'] ?? 'В процессе';
    $customer_id = !empty($_POST['customer_id']) ? (int)$_POST['customer_id'] : null;

    if ($editId) {
        $stmt = $db->prepare("UPDATE projects SET name=?, start_date=?, end_date=?, description=?, technical_task=?, tax_rate=?, status=?, customer_id=? WHERE id=?");
        $stmt->execute([$name, $start_date, $end_date, $description, $technical_task, $tax_rate, $status, $customer_id, $editId]);
    } else {
        $stmt = $db->prepare("INSERT INTO projects (name, start_date, end_date, description, technical_task, tax_rate, status, customer_id, workspace_id, created_at) VALUES (?,?,?,?,?,?,?,?,?, CURRENT_TIMESTAMP)");
        $stmt->execute([$name, $start_date, $end_date, $description, $technical_task, $tax_rate, $status, $customer_id, $workspace_id]);
        $editId = $db->lastInsertId();
    }

    header("Location: project_manage.php?id=$editId");
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $editId ? 'Редактирование' : 'Создание' ?> проекта</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <a href="projects.php?workspace_id=<?= $workspace_id ?>" class="back-link">← К списку проектов</a>
        
        <div class="card max-w-lg mx-auto">
            <div class="card-header">
                <h1><?= $editId ? '✏️ Редактирование' : '➕ Создание' ?> проекта</h1>
            </div>
            
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Название проекта <span class="required">*</span></label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($project['name'] ?? '') ?>" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Дата начала <span class="required">*</span></label>
                        <input type="date" name="start_date" class="form-control" value="<?= $project['start_date'] ?? '' ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Дата окончания <span class="required">*</span></label>
                        <input type="date" name="end_date" class="form-control" value="<?= $project['end_date'] ?? '' ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Описание проекта</label>
                    <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($project['description'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Техническое задание</label>
                    <textarea name="technical_task" class="form-control" rows="5"><?= htmlspecialchars($project['technical_task'] ?? '') ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Статус <span class="required">*</span></label>
                        <select name="status" class="form-control" required>
                            <option value="В процессе" <?= ($project['status'] ?? '') == 'В процессе' ? 'selected' : '' ?>>В процессе</option>
                            <option value="Завершен" <?= ($project['status'] ?? '') == 'Завершен' ? 'selected' : '' ?>>Завершен</option>
                            <option value="Заброшен" <?= ($project['status'] ?? '') == 'Заброшен' ? 'selected' : '' ?>>Заброшен</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Налоговая ставка, % <span class="required">*</span></label>
                        <input type="number" step="0.1" name="tax_rate" class="form-control" value="<?= $project['tax_rate'] ?? '6.0' ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Заказчик</label>
                    <select name="customer_id" class="form-control">
                        <option value="">— Без заказчика —</option>
                        <?php 
                        $stmt = $db->prepare("SELECT id, name, director_name FROM customers WHERE workspace_id = ? ORDER BY name, director_name");
                        $stmt->execute([$workspace_id]);
                        $customers = $stmt->fetchAll();
                        foreach ($customers as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= ($project['customer_id']??0)==$c['id']?'selected':'' ?>>
                                <?= htmlspecialchars($c['name'] ?: $c['director_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-success">💾 Сохранить проект</button>
            </form>
        </div>
    </div>
</body>
</html>