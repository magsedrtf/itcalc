<?php
require_once 'config.php';

$user_id = $_COOKIE['user_id'] ?? null;
if (!$user_id) {
    header('Location: login.php');
    exit;
}

$project_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$project_id) {
    header('Location: projects.php');
    exit;
}

$stmt = $db->prepare("SELECT * FROM projects WHERE id = ?");
$stmt->execute([$project_id]);
$project = $stmt->fetch();

if (!$project) die("Проект не найден");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $technical_task = $_POST['technical_task'];
    $stmt = $db->prepare("UPDATE projects SET technical_task = ? WHERE id = ?");
    $stmt->execute([$technical_task, $project_id]);
    header("Location: project_manage.php?id=$project_id");
    exit;
}

$technical_task = $project['technical_task'] ?? '';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Техническое задание</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <a href="project_manage.php?id=<?= $project_id ?>" class="back-link">← К управлению проектом</a>

        <div class="card max-w-lg mx-auto">
            <div class="card-header">
                <h1>📝 Техническое задание</h1>
                <span style="color:var(--gray-500); font-size:14px;"><?= htmlspecialchars($project['name']) ?></span>
            </div>
            
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Содержание ТЗ</label>
                    <textarea name="technical_task" class="form-control" rows="15" 
                              placeholder="Опишите требования к проекту..."><?= htmlspecialchars($technical_task) ?></textarea>
                </div>
                <button type="submit" class="btn btn-success">💾 Сохранить ТЗ</button>
            </form>
            
            <p class="text-muted" style="margin-top:12px; font-size:13px;">
                Это ТЗ можно будет использовать в коммерческом предложении
            </p>
        </div>
    </div>
</body>
</html>