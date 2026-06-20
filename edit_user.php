<?php
require_once 'config.php';
require_once 'auth.php';

if (!hasPermission('manage_users')) {
    header('Location: index.php');
    exit;
}

$user_id = $_COOKIE['user_id'] ?? null;
if (!$user_id) {
    header('Location: login.php');
    exit;
}

$editId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$editId) header('Location: team.php');

$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$editId]);
$member = $stmt->fetch();

if (!$member) die("Пользователь не найден");

$allPermissions = [
    'view_margin'       => 'Видеть маржинальность и прибыль',
    'manage_projects'   => 'Создавать и редактировать проекты',
    'delete_projects'   => 'Удалять проекты',
    'manage_resources'  => 'Управлять ресурсами в проектах',
    'generate_documents'=> 'Генерировать документы (КП, НМА)',
    'manage_customers'  => 'Управлять заказчиками',
    'manage_employees'  => 'Работать с реестрами',
    'manage_users'      => 'Управлять пользователями и ролями',
    'view_user_credentials' => 'Просмотр логинов и паролей'
];

$currentPerm = [];
$stmt = $db->prepare("SELECT permission, value FROM user_permissions WHERE user_id = ?");
$stmt->execute([$editId]);
foreach ($stmt->fetchAll() as $p) {
    $currentPerm[$p['permission']] = $p['value'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $db->prepare("UPDATE users SET first_name=?, last_name=?, position=? WHERE id=?");
    $stmt->execute([$_POST['first_name'], $_POST['last_name'], $_POST['position'], $editId]);

    $db->prepare("DELETE FROM user_permissions WHERE user_id=?")->execute([$editId]);

    foreach ($allPermissions as $perm => $label) {
        $value = isset($_POST['perm'][$perm]) ? 1 : 0;
        $stmt = $db->prepare("INSERT INTO user_permissions (user_id, permission, value) VALUES (?,?,?)");
        $stmt->execute([$editId, $perm, $value]);
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
    <title>Редактировать права</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .perm-checkbox {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            border-radius: var(--radius);
            transition: var(--transition);
        }
        .perm-checkbox:hover {
            background: var(--gray-50);
        }
        .perm-checkbox input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        .perm-checkbox label {
            cursor: pointer;
            font-weight: normal;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="team.php" class="back-link">← Назад</a>
        
        <div class="card max-w-lg mx-auto">
            <div class="card-header">
                <h1>✏️ Редактирование прав</h1>
                <span style="color:var(--gray-500); font-size:14px;">
                    <?= htmlspecialchars($member['first_name'] . ' ' . $member['last_name']) ?>
                </span>
            </div>
            
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Имя <span class="required">*</span></label>
                        <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($member['first_name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Фамилия <span class="required">*</span></label>
                        <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($member['last_name']) ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Должность</label>
                    <input type="text" name="position" class="form-control" value="<?= htmlspecialchars($member['position']) ?>">
                </div>

                <h3 style="margin-top:24px;">🔐 Права и функции</h3>
                <p class="text-muted" style="font-size:13px;">Включите необходимые права для этого пользователя</p>
                
                <?php foreach ($allPermissions as $perm => $label): ?>
                    <div class="perm-checkbox">
                        <input type="checkbox" name="perm[<?= $perm ?>]" id="perm_<?= $perm ?>" 
                               <?= ($currentPerm[$perm] ?? 1) ? 'checked' : '' ?>>
                        <label for="perm_<?= $perm ?>"><?= $label ?></label>
                    </div>
                <?php endforeach; ?>

                <button type="submit" class="btn btn-success" style="margin-top:20px;">💾 Сохранить права</button>
            </form>
        </div>
    </div>
</body>
</html>