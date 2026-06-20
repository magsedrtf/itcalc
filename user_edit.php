<?php
require_once 'config.php';

$user_id = $_COOKIE['user_id'] ?? null;
if (!$user_id) {
    header('Location: login.php');
    exit;
}

$editId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$allRoles = $db->query("SELECT * FROM roles")->fetchAll();

$user = null;
$userRoles = [];
if ($editId) {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$editId]);
    $user = $stmt->fetch();
    
    $stmt = $db->prepare("SELECT role_id FROM user_roles WHERE user_id = ?");
    $stmt->execute([$editId]);
    $userRoles = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lastName = $_POST['last_name'];
    $firstName = $_POST['first_name'];
    $middleName = $_POST['middle_name'] ?: null;
    $email = $_POST['email'];
    $position = $_POST['position'];
    $roles = $_POST['roles'] ?? [];
    
    if ($editId) {
        $stmt = $db->prepare("UPDATE users SET last_name=?, first_name=?, middle_name=?, email=?, position=? WHERE id=?");
        $stmt->execute([$lastName, $firstName, $middleName, $email, $position, $editId]);
        $db->prepare("DELETE FROM user_roles WHERE user_id=?")->execute([$editId]);
    } else {
        $stmt = $db->prepare("INSERT INTO users (last_name, first_name, middle_name, email, position) VALUES (?,?,?,?,?)");
        $stmt->execute([$lastName, $firstName, $middleName, $email, $position]);
        $editId = $db->lastInsertId();
    }
    
    $insertRole = $db->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?,?)");
    foreach ($roles as $roleId) {
        $insertRole->execute([$editId, $roleId]);
    }
    
    header('Location: users.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $editId ? 'Редактирование' : 'Создание' ?> пользователя</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .role-checkbox {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 6px 0;
        }
        .role-checkbox input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        .role-checkbox label {
            cursor: pointer;
            font-weight: normal;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="users.php" class="back-link">← К списку пользователей</a>

        <div class="card max-w-md mx-auto">
            <div class="card-header">
                <h1><?= $editId ? '✏️ Редактирование' : '➕ Создание' ?> пользователя</h1>
            </div>
            
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Фамилия <span class="required">*</span></label>
                        <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($user['last_name'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Имя <span class="required">*</span></label>
                        <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($user['first_name'] ?? '') ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Отчество</label>
                    <input type="text" name="middle_name" class="form-control" value="<?= htmlspecialchars($user['middle_name'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email <span class="required">*</span></label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Должность <span class="required">*</span></label>
                    <input type="text" name="position" class="form-control" value="<?= htmlspecialchars($user['position'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Роли</label>
                    <div style="margin-top:6px;">
                        <?php foreach ($allRoles as $role): ?>
                            <div class="role-checkbox">
                                <input type="checkbox" name="roles[]" value="<?= $role['id'] ?>" id="role_<?= $role['id'] ?>"
                                    <?= in_array($role['id'], $userRoles) ? 'checked' : '' ?>>
                                <label for="role_<?= $role['id'] ?>"><?= htmlspecialchars($role['name']) ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-success">💾 Сохранить</button>
            </form>
        </div>
    </div>
</body>
</html>