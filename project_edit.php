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
        $stmt = $db->prepare("UPDATE projects SET 
            name=?, start_date=?, end_date=?, description=?, 
            technical_task=?, tax_rate=?, status=?, customer_id=?, 
            updated_at=CURRENT_TIMESTAMP WHERE id=?");
        $stmt->execute([$name, $start_date, $end_date, $description, 
                        $technical_task, $tax_rate, $status, $customer_id, $editId]);
    } else {
        $stmt = $db->prepare("INSERT INTO projects 
            (name, start_date, end_date, description, technical_task, 
             tax_rate, status, customer_id, workspace_id, created_at) 
            VALUES (?,?,?,?,?,?,?,?,?, CURRENT_TIMESTAMP)");
        $stmt->execute([$name, $start_date, $end_date, $description, 
                        $technical_task, $tax_rate, $status, $customer_id, $workspace_id]);
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
    <title><?= $editId ? 'Редактирование' : 'Создание' ?> проекта</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; }
        form { max-width: 700px; }
        label { display: block; margin: 15px 0 5px; font-weight: bold; }
        input, textarea, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #4CAF50; color: white; padding: 12px 25px; border: none; border-radius: 4px; font-size: 16px; cursor: pointer; }
    </style>
</head>
<body>
    <a href="projects.php">← К списку проектов</a>
    <h1><?= $editId ? 'Редактирование проекта' : 'Создание проекта' ?></h1>
    
    <form method="POST">
        <label>Название проекта *</label>
        <input type="text" name="name" value="<?= htmlspecialchars($project['name'] ?? '') ?>" required>
        
        <label>Дата начала *</label>
        <input type="date" name="start_date" value="<?= $project['start_date'] ?? '' ?>" required>
        
        <label>Дата окончания *</label>
        <input type="date" name="end_date" value="<?= $project['end_date'] ?? '' ?>" required>
        
        <label>Описание проекта</label>
        <textarea name="description" rows="3"><?= htmlspecialchars($project['description'] ?? '') ?></textarea>

        <label>Техническое задание</label>
        <textarea name="technical_task" rows="6"><?= htmlspecialchars($project['technical_task'] ?? '') ?></textarea>
        
        <label>Статус проекта *</label>
        <select name="status" required>
            <option value="В процессе" <?= ($project['status'] ?? '') == 'В процессе' ? 'selected' : '' ?>>В процессе</option>
            <option value="Завершен" <?= ($project['status'] ?? '') == 'Завершен' ? 'selected' : '' ?>>Завершен</option>
            <option value="Заброшен" <?= ($project['status'] ?? '') == 'Заброшен' ? 'selected' : '' ?>>Заброшен</option>
        </select>
        
        <label>Налоговая ставка, % *</label>
        <input type="number" step="0.1" name="tax_rate" value="<?= $project['tax_rate'] ?? '6.0' ?>" required>
        
        <label>Заказчик</label>
        <select name="customer_id">
            <option value="">— Без заказчика —</option>
            <?php 
            $customers = $db->query("SELECT id, name, director_name FROM customers ORDER BY name, director_name")->fetchAll();
            foreach ($customers as $c): ?>
                <option value="<?= $c['id'] ?>" <?= ($project['customer_id']??0)==$c['id']?'selected':'' ?>>
                    <?= htmlspecialchars($c['name'] ?: $c['director_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <button type="submit">Сохранить проект</button>
    </form>
</body>
</html>