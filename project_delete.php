<?php
require_once 'config.php';

$user_id = $_COOKIE['user_id'] ?? null;
if (!$user_id || !isset($_GET['id'])) {
    header('Location: projects.php');
    exit;
}

$id = (int)$_GET['id'];

$stmt = $db->prepare("DELETE FROM projects WHERE id = ?");
$stmt->execute([$id]);

$stmt = $db->prepare("DELETE FROM project_resources WHERE project_id = ?");
$stmt->execute([$id]);

header('Location: projects.php');
exit;
?>