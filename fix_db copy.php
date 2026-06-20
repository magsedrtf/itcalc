<?php
require_once 'config.php';
require_once 'auth.php';

$user_id = $_COOKIE['user_id'] ?? null;
if (!$user_id) die("Не авторизован");

echo "<h2>Проверка прав для пользователя ID: $user_id</h2>";

echo "isGlobalAdmin(): " . (isGlobalAdmin() ? '✅ Да' : '❌ Нет') . "<br><br>";

$perms = ['manage_projects', 'manage_resources', 'view_margin', 'generate_documents'];

foreach ($perms as $p) {
    $val = hasPermission($p);
    echo "$p → <b>" . ($val ? '✅ TRUE' : '❌ FALSE') . "</b><br>";
}

// Покажи что в базе
$stmt = $db->prepare("SELECT permission, value FROM user_permissions WHERE user_id = ?");
$stmt->execute([$user_id]);
$rows = $stmt->fetchAll();

echo "<h3>Записи в базе:</h3><pre>";
print_r($rows);
echo "</pre>";
?>