<?php
require_once 'config.php';

$user_id = $_COOKIE['user_id'] ?? null;
if (!$user_id) {
    header('Location: login.php');
    exit;
}

$workspace_id = isset($_GET['workspace_id']) ? (int)$_GET['workspace_id'] : 0;
if (!$workspace_id) {
    header('Location: workspaces.php');
    exit;
}

$editId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$sub = null;
if ($editId) {
    $stmt = $db->prepare("SELECT * FROM subcontractors WHERE id = ? AND workspace_id = ?");
    $stmt->execute([$editId, $workspace_id]);
    $sub = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'];
    $inn = $_POST['inn'] ?: null;
    $name = $_POST['name'] ?: null;
    $last_name = $_POST['last_name'] ?: null;
    $first_name = $_POST['first_name'] ?: null;
    $email = $_POST['email'];
    $phone = $_POST['phone'];

    if ($editId) {
        $stmt = $db->prepare("UPDATE subcontractors SET type=?, inn=?, name=?, last_name=?, first_name=?, email=?, phone=?, workspace_id=? WHERE id=?");
        $stmt->execute([$type, $inn, $name, $last_name, $first_name, $email, $phone, $workspace_id, $editId]);
    } else {
        $stmt = $db->prepare("INSERT INTO subcontractors (workspace_id, type, inn, name, last_name, first_name, email, phone) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->execute([$workspace_id, $type, $inn, $name, $last_name, $first_name, $email, $phone]);
    }

    header("Location: subcontractors.php?workspace_id=$workspace_id");
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $editId ? 'Редактирование' : 'Добавление' ?> субподрядчика</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <a href="subcontractors.php?workspace_id=<?= $workspace_id ?>" class="back-link">← К списку субподрядчиков</a>

        <div class="card max-w-md mx-auto">
            <div class="card-header">
                <h1><?= $editId ? '✏️ Редактирование' : '➕ Добавление' ?> субподрядчика</h1>
            </div>
            
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Тип <span class="required">*</span></label>
                    <select name="type" class="form-control" required>
                        <option value="Индивидуальный предприниматель" <?= ($sub['type']??'')=='Индивидуальный предприниматель'?'selected':'' ?>>ИП</option>
                        <option value="Юридическое лицо" <?= ($sub['type']??'')=='Юридическое лицо'?'selected':'' ?>>Юридическое лицо</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">ИНН</label>
                    <input type="text" name="inn" class="form-control" value="<?= htmlspecialchars($sub['inn'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Название организации</label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($sub['name'] ?? '') ?>">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Фамилия руководителя</label>
                        <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($sub['last_name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Имя руководителя</label>
                        <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($sub['first_name'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Email <span class="required">*</span></label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($sub['email'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Телефон</label>
                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($sub['phone'] ?? '') ?>">
                </div>

                <button type="submit" class="btn btn-success">💾 Сохранить</button>
            </form>
        </div>
    </div>
</body>
</html>