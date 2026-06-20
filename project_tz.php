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
    <title>Техническое задание — <?= htmlspecialchars($project['name']) ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; }
        textarea { width: 100%; height: 600px; padding: 15px; font-size: 16px; line-height: 1.6; }
        button { background: #4CAF50; color: white; padding: 12px 25px; border: none; border-radius: 4px; font-size: 16px; }
        .back { margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="back">
        <a href="project_manage.php?id=<?= $project_id ?>">← К управлению проектом</a>
    </div>

    <h1>Техническое задание</h1>
    <h2><?= htmlspecialchars($project['name']) ?></h2>

    <form method="POST">
        <textarea name="technical_task" placeholder="Опишите требования к проекту..."><?= htmlspecialchars($technical_task) ?></textarea>
        <br><br>
        <button type="submit">Сохранить Техническое задание</button>
    </form>

    <p><small>Это ТЗ можно будет вставить в Коммерческое предложение в следующих версиях.</small></p>
</body>
</html>