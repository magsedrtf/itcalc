<?php
require_once 'config.php';

$user_id = $_COOKIE['user_id'] ?? null;
if (!$user_id || !isset($_GET['id'])) {
    header('Location: projects.php');
    exit;
}

$id = (int)$_GET['id'];

$stmt = $db->prepare("SELECT project_id FROM project_resources WHERE id = ?");
$stmt->execute([$id]);
$project = $stmt->fetch();

if ($project) {
    $stmt = $db->prepare("DELETE FROM project_resources WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: project_manage.php?id=" . $project['project_id']);
} else {
    header('Location: projects.php');
}
exit;
?>