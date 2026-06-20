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

$customer = null;
if ($editId) {
    $stmt = $db->prepare("SELECT * FROM customers WHERE id = ? AND workspace_id = ?");
    $stmt->execute([$editId, $workspace_id]);
    $customer = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['customer_type'];
    $inn = $_POST['inn'] ?: null;
    $name = $_POST['name'] ?: null;
    $director_name = $_POST['director_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];

    if ($editId) {
        $stmt = $db->prepare("UPDATE customers SET type=?, inn=?, name=?, director_name=?, email=?, phone=?, workspace_id=? WHERE id=?");
        $stmt->execute([$type, $inn, $name, $director_name, $email, $phone, $workspace_id, $editId]);
    } else {
        $stmt = $db->prepare("INSERT INTO customers (workspace_id, type, inn, name, director_name, email, phone) VALUES (?,?,?,?,?,?,?)");
        $stmt->execute([$workspace_id, $type, $inn, $name, $director_name, $email, $phone]);
    }

    header("Location: customers.php?workspace_id=$workspace_id");
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $editId ? 'Редактирование' : 'Добавление' ?> заказчика</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <a href="customers.php?workspace_id=<?= $workspace_id ?>" class="back-link">← К списку заказчиков</a>

        <div class="card max-w-md mx-auto">
            <div class="card-header">
                <h1><?= $editId ? '✏️ Редактирование' : '➕ Добавление' ?> заказчика</h1>
            </div>
            
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Тип заказчика <span class="required">*</span></label>
                    <select name="customer_type" class="form-control" required>
                        <option value="Физическое лицо" <?= ($customer['type']??'')=='Физическое лицо'?'selected':'' ?>>Физическое лицо</option>
                        <option value="Индивидуальный предприниматель" <?= ($customer['type']??'')=='Индивидуальный предприниматель'?'selected':'' ?>>ИП</option>
                        <option value="Юридическое лицо" <?= ($customer['type']??'')=='Юридическое лицо'?'selected':'' ?>>Юридическое лицо</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">ИНН</label>
                    <input type="text" name="inn" class="form-control" value="<?= htmlspecialchars($customer['inn'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Название (для ИП/ЮЛ)</label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($customer['name'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">ФИО руководителя / Заказчика <span class="required">*</span></label>
                    <input type="text" name="director_name" class="form-control" value="<?= htmlspecialchars($customer['director_name'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Email <span class="required">*</span></label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($customer['email'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Телефон <span class="required">*</span></label>
                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($customer['phone'] ?? '') ?>" required>
                </div>

                <button type="submit" class="btn btn-success">💾 Сохранить</button>
            </form>
        </div>
    </div>
</body>
</html>