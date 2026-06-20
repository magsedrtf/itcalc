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

    $stmt = $db->prepare("INSERT INTO users (last_name, first_name, email, password, position, party_id) VALUES (?,?,?,?,?,?)");
    $stmt->execute([$last_name, $first_name, $email, $password, $position, $party_id]);

    $new_user_id = $db->lastInsertId();

    $stmt = $db->prepare("INSERT INTO user_roles (user_id, role_id) SELECT ?, id FROM roles WHERE name = ?");
    $stmt->execute([$new_user_id, $role_name]);

    setDefaultPermissions($new_user_id, $role_name);

    $stmt = $db->prepare("SELECT workspace_id FROM workspace_users WHERE user_id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $ws = $stmt->fetch();

    if ($ws) {
        $db->prepare("INSERT OR IGNORE INTO workspace_users (workspace_id, user_id, role) VALUES (?,?, 'Редактирование')")->execute([$ws['workspace_id'], $new_user_id]);
    }

    header('Location: team.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавить в команду</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <a href="team.php" class="back-link">← Назад</a>
        
        <div class="card max-w-md mx-auto">
            <div class="card-header">
                <h1>➕ Добавить в команду</h1>
            </div>
            
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Имя <span class="required">*</span></label>
                    <input type="text" name="first_name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Фамилия <span class="required">*</span></label>
                    <input type="text" name="last_name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Должность</label>
                    <input type="text" name="position" class="form-control" value="Сотрудник">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Роль <span class="required">*</span></label>
                    <select name="role" class="form-control" required>
                        <option value="Коммерческий директор">Коммерческий директор</option>
                        <option value="Бухгалтер">Бухгалтер</option>
                        <option value="Кадровик">Кадровик</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-success">Добавить</button>
            </form>
        </div>
    </div>
</body>
</html>