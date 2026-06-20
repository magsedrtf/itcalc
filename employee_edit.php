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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $editId ? 'Редактирование' : 'Добавление' ?> сотрудника</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <a href="employees.php?workspace_id=<?= $workspace_id ?>" class="back-link">← К списку сотрудников</a>

        <div class="card max-w-md mx-auto">
            <div class="card-header">
                <h1><?= $editId ? '✏️ Редактирование' : '➕ Добавление' ?> сотрудника</h1>
            </div>
            
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Фамилия <span class="required">*</span></label>
                        <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($employee['last_name'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Имя <span class="required">*</span></label>
                        <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($employee['first_name'] ?? '') ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Отчество</label>
                    <input type="text" name="middle_name" class="form-control" value="<?= htmlspecialchars($employee['middle_name'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Должность <span class="required">*</span></label>
                    <input type="text" name="position" class="form-control" value="<?= htmlspecialchars($employee['position'] ?? '') ?>" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Оклад в месяц, ₽ <span class="required">*</span></label>
                        <input type="number" name="salary" class="form-control" value="<?= $employee['salary'] ?? '' ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Налоговая ставка, %</label>
                        <input type="number" name="tax_rate" step="0.1" class="form-control" value="<?= $employee['tax_rate'] ?? '30.2' ?>" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-success">💾 Сохранить</button>
            </form>
        </div>
    </div>
</body>
</html>