<?php
require_once 'config.php';
require_once 'auth.php';

if (!isGlobalAdmin()) {
    header('Location: index.php');
    exit;
}

$user_id = $_COOKIE['user_id'] ?? null;
if (!$user_id) {
    header('Location: login.php');
    exit;
}

$settings = $db->query("SELECT * FROM company_settings LIMIT 1")->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_name = $_POST['company_name'];
    $director_name = $_POST['director_name'];
    $director_position = $_POST['director_position'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];

    if ($settings) {
        $stmt = $db->prepare("UPDATE company_settings SET company_name=?, director_name=?, director_position=?, phone=?, email=? WHERE id=1");
        $stmt->execute([$company_name, $director_name, $director_position, $phone, $email]);
    } else {
        $stmt = $db->prepare("INSERT INTO company_settings (company_name, director_name, director_position, phone, email) VALUES (?,?,?,?,?)");
        $stmt->execute([$company_name, $director_name, $director_position, $phone, $email]);
    }

    header('Location: settings.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Настройки компании</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-link">← На главную</a>

        <div class="card max-w-md mx-auto">
            <div class="card-header">
                <h1>⚙️ Настройки компании</h1>
            </div>
            
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Название компании <span class="required">*</span></label>
                    <input type="text" name="company_name" class="form-control" value="<?= htmlspecialchars($settings['company_name'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">ФИО руководителя <span class="required">*</span></label>
                    <input type="text" name="director_name" class="form-control" value="<?= htmlspecialchars($settings['director_name'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Должность руководителя <span class="required">*</span></label>
                    <input type="text" name="director_position" class="form-control" value="<?= htmlspecialchars($settings['director_position'] ?? 'Генеральный директор') ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Контактный телефон</label>
                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($settings['phone'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Контактный email</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($settings['email'] ?? '') ?>">
                </div>
                
                <button type="submit" class="btn btn-success">💾 Сохранить настройки</button>
            </form>
        </div>
    </div>
</body>
</html>