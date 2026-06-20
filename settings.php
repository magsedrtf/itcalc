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
        $stmt = $db->prepare("INSERT INTO company_settings (company_name, director_name, director_position, phone, email) 
                              VALUES (?,?,?,?,?)");
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
    <title>Настройки компании</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; }
        form { max-width: 700px; }
        label { display: block; margin: 15px 0 5px; font-weight: bold; }
        input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #4CAF50; color: white; padding: 12px 25px; border: none; border-radius: 4px; cursor: pointer; }
    </style>
</head>
<body>
    <a href="index.php">← На главную</a>
    <h1>Настройки компании</h1>
    
    <form method="POST">
        <label>Название компании *</label>
        <input type="text" name="company_name" value="<?= htmlspecialchars($settings['company_name'] ?? '') ?>" required>
        
        <label>ФИО руководителя *</label>
        <input type="text" name="director_name" value="<?= htmlspecialchars($settings['director_name'] ?? '') ?>" required>
        
        <label>Должность руководителя *</label>
        <input type="text" name="director_position" value="<?= htmlspecialchars($settings['director_position'] ?? 'Генеральный директор') ?>" required>
        
        <label>Контактный телефон</label>
        <input type="text" name="phone" value="<?= htmlspecialchars($settings['phone'] ?? '') ?>">
        
        <label>Контактный email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($settings['email'] ?? '') ?>">
        
        <button type="submit">Сохранить настройки</button>
    </form>
</body>
</html>