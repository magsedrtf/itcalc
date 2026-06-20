<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'calculate.php';

$user_id = $_COOKIE['user_id'] ?? null;
if (!$user_id) {
    header('Location: login.php');
    exit;
}

$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
if (!$project_id) {
    header('Location: projects.php');
    exit;
}

if (!canManageResources()) {
    die("У вас нет прав на управление ресурсами проекта.");
}


$stmt = $db->prepare("SELECT * FROM projects WHERE id = ?");
$stmt->execute([$project_id]);
$project = $stmt->fetch();

if (!$project) die("Проект не найден");

$workspace_id = $project['workspace_id'];


if (!hasWorkspaceAccess($workspace_id)) {
    die("Нет доступа к этому проекту");
}


$employees = $db->prepare("SELECT id, last_name, first_name, position, salary, tax_rate 
                           FROM employees WHERE workspace_id = ? ORDER BY last_name");
$employees->execute([$workspace_id]);
$employees = $employees->fetchAll();

$executors = $db->prepare("SELECT id, last_name, first_name, contract_type, tax_rate, unit_type, unit_cost 
                           FROM executors WHERE workspace_id = ? ORDER BY last_name");
$executors->execute([$workspace_id]);
$executors = $executors->fetchAll();

$equipment_list = $db->prepare("SELECT id, name, unit_type, unit_cost 
                                FROM equipment WHERE workspace_id = ? ORDER BY name");
$equipment_list->execute([$workspace_id]);
$equipment_list = $equipment_list->fetchAll();

$subcontractors = $db->prepare("SELECT id, name, last_name, first_name 
                                FROM subcontractors WHERE workspace_id = ? ORDER BY name, last_name");
$subcontractors->execute([$workspace_id]);
$subcontractors = $subcontractors->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resource_type = $_POST['resource_type'];
    $resource_id = !empty($_POST['resource_id']) ? (int)$_POST['resource_id'] : null;
    $resource_name = trim($_POST['resource_name']);
    $service_name = trim($_POST['service_name']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $quantity = (float)$_POST['quantity'];
    $unit_type = $_POST['unit_type'];
    $unit_cost = (float)$_POST['unit_cost'];
    $margin_percent = (float)$_POST['margin_percent'];

    $stmt = $db->prepare("INSERT INTO project_resources 
        (project_id, resource_type, resource_id, resource_name, service_name, 
         start_date, end_date, quantity, unit_type, unit_cost, margin_percent) 
        VALUES (?,?,?,?,?,?,?,?,?,?,?)");
    
    $stmt->execute([$project_id, $resource_type, $resource_id, $resource_name, $service_name, 
                    $start_date, $end_date, $quantity, $unit_type, $unit_cost, $margin_percent]);

    header("Location: project_manage.php?id=$project_id");
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Добавление ресурса</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; }
        form { max-width: 800px; }
        label { display: block; margin: 12px 0 5px; font-weight: bold; }
        input, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #4CAF50; color: white; padding: 12px 25px; border: none; border-radius: 4px; cursor: pointer; }
    </style>
</head>
<body>
    <a href="project_manage.php?id=<?= $project_id ?>">← Назад к проекту</a>
    <h1>Добавление ресурса в проект</h1>

    <form method="POST">
        <label>Тип ресурса *</label>
        <select name="resource_type" id="resource_type" required onchange="loadResources(this.value)">
            <option value="Сотрудник">Сотрудник</option>
            <option value="Исполнитель">Исполнитель (ФЛ)</option>
            <option value="Субподрядчик">Субподрядчик</option>
            <option value="Оборудование">Оборудование</option>
        </select>

        <label>Выбор из реестра</label>
        <select name="resource_id" id="resource_select" onchange="fillResourceData()">
            <option value="">— Выбрать из реестра —</option>
        </select>

        <label>Название ресурса в документах *</label>
        <input type="text" name="resource_name" id="resource_name" required>

        <label>Название услуги *</label>
        <input type="text" name="service_name" required>

        <label>Дата начала *</label>
        <input type="date" name="start_date" value="<?= $project['start_date'] ?>" required>

        <label>Дата окончания *</label>
        <input type="date" name="end_date" value="<?= $project['end_date'] ?>" required>

        <label>Количество *</label>
        <input type="number" name="quantity" step="0.01" value="1" required>

        <label>Единица измерения *</label>
        <select name="unit_type" id="unit_type" required>
            <option value="часы">Часы</option>
            <option value="дни">Дни</option>
            <option value="полная стоимость">Полная стоимость</option>
        </select>

        <label>Стоимость за единицу (₽) *</label>
        <input type="number" name="unit_cost" id="unit_cost" step="0.01" required>

        <label>Маржинальность (%)</label>
        <input type="number" name="margin_percent" value="30" step="0.1">

        <button type="submit">Добавить в проект</button>
    </form>

    <script>
    function loadResources(type) {
        const select = document.getElementById('resource_select');
        select.innerHTML = '<option value="">— Выбрать из реестра —</option>';

        <?php if (!empty($employees)): ?>
        if (type === 'Сотрудник') {
            <?php foreach ($employees as $e): ?>
                select.innerHTML += `<option value="<?= $e['id'] ?>" 
                    data-unit="дни" 
                    data-cost="<?= $e['salary'] ?>"
                    data-name="<?= addslashes($e['last_name'].' '.$e['first_name']) ?>">
                    <?= addslashes($e['last_name'].' '.$e['first_name'].' ('.$e['position'].')') ?>
                </option>`;
            <?php endforeach; ?>
        }
        <?php endif; ?>

        <?php if (!empty($executors)): ?>
        else if (type === 'Исполнитель') {
            <?php foreach ($executors as $e): ?>
                select.innerHTML += `<option value="<?= $e['id'] ?>" 
                    data-unit="<?= $e['unit_type'] ?>" 
                    data-cost="<?= $e['unit_cost'] ?>"
                    data-name="<?= addslashes($e['last_name'].' '.$e['first_name']) ?>">
                    <?= addslashes($e['last_name'].' '.$e['first_name']) ?>
                </option>`;
            <?php endforeach; ?>
        }
        <?php endif; ?>

        <?php if (!empty($equipment_list)): ?>
        else if (type === 'Оборудование') {
            <?php foreach ($equipment_list as $e): ?>
                select.innerHTML += `<option value="<?= $e['id'] ?>" 
                    data-unit="<?= $e['unit_type'] ?>" 
                    data-cost="<?= $e['unit_cost'] ?>"
                    data-name="<?= addslashes($e['name']) ?>">
                    <?= addslashes($e['name']) ?>
                </option>`;
            <?php endforeach; ?>
        }
        <?php endif; ?>

        <?php if (!empty($subcontractors)): ?>
        else if (type === 'Субподрядчик') {
            <?php foreach ($subcontractors as $s): ?>
                select.innerHTML += `<option value="<?= $s['id'] ?>" 
                    data-name="<?= addslashes($s['name'] ?: $s['last_name'].' '.$s['first_name']) ?>">
                    <?= addslashes($s['name'] ?: $s['last_name'].' '.$s['first_name']) ?>
                </option>`;
            <?php endforeach; ?>
        }
        <?php endif; ?>
    }

    function fillResourceData() {
        const select = document.getElementById('resource_select');
        const option = select.options[select.selectedIndex];
        if (!option || !option.dataset.name) return;

        document.getElementById('resource_name').value = option.dataset.name;

        if (option.dataset.unit) document.getElementById('unit_type').value = option.dataset.unit;
        if (option.dataset.cost) document.getElementById('unit_cost').value = option.dataset.cost;
    }

    window.onload = () => loadResources(document.getElementById('resource_type').value);
    </script>
</body>
</html>