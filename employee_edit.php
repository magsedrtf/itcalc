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

$employee = null;
if ($editId) {
    $stmt = $db->prepare("SELECT * FROM employees WHERE id = ? AND workspace_id = ?");
    $stmt->execute([$editId, $workspace_id]);
    $employee = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lastName = $_POST['last_name'];
    $firstName = $_POST['first_name'];
    $middleName = $_POST['middle_name'] ?: null;
    $position = $_POST['position'];
    $salary = (float)$_POST['salary'];
    $taxRate = (float)$_POST['tax_rate'];
    
    if ($editId) {
        $stmt = $db->prepare("UPDATE employees SET last_name=?, first_name=?, middle_name=?, position=?, salary=?, tax_rate=?, workspace_id=? WHERE id=?");
        $stmt->execute([$lastName, $firstName, $middleName, $position, $salary, $taxRate, $workspace_id, $editId]);
    } else {
        $stmt = $db->prepare("INSERT INTO employees (last_name, first_name, middle_name, position, salary, tax_rate, workspace_id) VALUES (?,?,?,?,?,?,?)");
        $stmt->execute([$lastName, $firstName, $middleName, $position, $salary, $taxRate, $workspace_id]);
    }
    
    header("Location: employees.php?workspace_id=$workspace_id");
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?= $editId ? 'Редактирование' : 'Добавление' ?> сотрудника</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; }
        form { max-width: 500px; }
        label { display: block; margin: 10px 0 5px; font-weight: bold; }
        input { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #4CAF50; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; margin-top: 15px; }
    </style>
</head>
<body>
    <a href="employees.php?workspace_id=<?= $workspace_id ?>">← К списку сотрудников</a>
    <h1><?= $editId ? 'Редактирование' : 'Добавление' ?> сотрудника</h1>
    
    <form method="POST">
        <label>Фамилия *</label>
        <input type="text" name="last_name" value="<?= htmlspecialchars($employee['last_name'] ?? '') ?>" required>
        
        <label>Имя *</label>
        <input type="text" name="first_name" value="<?= htmlspecialchars($employee['first_name'] ?? '') ?>" required>
        
        <label>Отчество</label>
        <input type="text" name="middle_name" value="<?= htmlspecialchars($employee['middle_name'] ?? '') ?>">
        
        <label>Должность *</label>
        <input type="text" name="position" value="<?= htmlspecialchars($employee['position'] ?? '') ?>" required>
        
        <label>Оклад в месяц, ₽ *</label>
        <input type="number" name="salary" value="<?= $employee['salary'] ?? '' ?>" required>
        
        <label>Налоговая ставка, %</label>
        <input type="number" name="tax_rate" step="0.1" value="<?= $employee['tax_rate'] ?? '30.2' ?>" required>
        
        <button type="submit">Сохранить</button>
    </form>
</body>
</html>