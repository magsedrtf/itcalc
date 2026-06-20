<?php
require_once 'config.php';
require_once 'auth.php';

$user_id = $_COOKIE['user_id'] ?? null;
if (!$user_id) {
    header('Location: login.php');
    exit;
}


$stmt = $db->prepare("SELECT party_id FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$party_id = $stmt->fetchColumn();

function generateRandomString($length = 8) {
    $numbers = '0123456789';
    $letters = 'abcdefghijklmnopqrstuvwxyz';
    $all = $numbers . $letters;
    $str = '';
    for ($i = 0; $i < $length; $i++) $str .= $all[rand(0, strlen($all)-1)];
    
    $pos1 = rand(1, $length-3);
    $pos2 = rand($pos1+1, $length-1);
    $str = substr_replace($str, '-', $pos1, 0);
    $str = substr_replace($str, '-', $pos2, 0);
    return $str;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $position = trim($_POST['position'] ?? 'Сотрудник');
    $role_name = $_POST['role'];

    $login = generateRandomString(8);
    $password = generateRandomString(8);
    $email = $login . '@test.ru';

    $stmt = $db->prepare("INSERT INTO users (last_name, first_name, email, password, position, party_id) 
                          VALUES (?,?,?,?,?,?)");
    $stmt->execute([$last_name, $first_name, $email, $password, $position, $party_id]);

    $new_user_id = $db->lastInsertId();

    $stmt = $db->prepare("INSERT INTO user_roles (user_id, role_id) SELECT ?, id FROM roles WHERE name = ?");
    $stmt->execute([$new_user_id, $role_name]);

    setDefaultPermissions($new_user_id, $role_name);

    // Привязка к workspace
    $stmt = $db->prepare("SELECT workspace_id FROM workspace_users WHERE user_id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $ws = $stmt->fetch();

    if ($ws) {
        $db->prepare("INSERT OR IGNORE INTO workspace_users (workspace_id, user_id, role) VALUES (?,?, 'Редактирование')")
           ->execute([$ws['workspace_id'], $new_user_id]);
    }

    header('Location: team.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Добавить в команду</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; }
        input, select { width: 100%; padding: 10px; margin: 8px 0; }
        button { padding: 12px 25px; background: #4CAF50; color: white; border: none; border-radius: 4px; }
    </style>
</head>
<body>
    <a href="team.php">← Назад</a>
    <h1>+ Добавить в команду</h1>
    <form method="POST">
        <label>Имя *</label>
        <input type="text" name="first_name" required>
        <label>Фамилия *</label>
        <input type="text" name="last_name" required>
        <label>Должность</label>
        <input type="text" name="position" value="Сотрудник">
        <label>Роль *</label>
        <select name="role" required>
            <option value="Коммерческий директор">Коммерческий директор</option>
            <option value="Бухгалтер">Бухгалтер</option>
            <option value="Кадровик">Кадровик</option>
        </select>
        <button type="submit">Добавить</button>
    </form>
</body>
</html>