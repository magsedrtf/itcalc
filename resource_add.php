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

$employees = $db->prepare("SELECT id, last_name, first_name, position, salary, tax_rate FROM employees WHERE workspace_id = ? ORDER BY last_name");
$employees->execute([$workspace_id]);
$employees = $employees->fetchAll();

$executors = $db->prepare("SELECT id, last_name, first_name, contract_type, tax_rate, unit_type, unit_cost FROM executors WHERE workspace_id = ? ORDER BY last_name");
$executors->execute([$workspace_id]);
$executors = $executors->fetchAll();

$equipment_list = $db->prepare("SELECT id, name, unit_type, unit_cost FROM equipment WHERE workspace_id = ? ORDER BY name");
$equipment_list->execute([$workspace_id]);
$equipment_list = $equipment_list->fetchAll();

$subcontractors = $db->prepare("SELECT id, name, last_name, first_name FROM subcontractors WHERE workspace_id = ? ORDER BY name, last_name");
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

    $stmt = $db->prepare("INSERT INTO project_resources (project_id, resource_type, resource_id, resource_name, service_name, start_date, end_date, quantity, unit_type, unit_cost, margin_percent) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->execute([$project_id, $resource_type, $resource_id, $resource_name, $service_name, $start_date, $end_date, $quantity, $unit_type, $unit_cost, $margin_percent]);

    header("Location: project_manage.php?id=$project_id");
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавление ресурса</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <a href="project_manage.php?id=<?= $project_id ?>" class="back-link">← Назад к проекту</a>

        <div class="card max-w-lg mx-auto">
            <div class="card-header">
                <h1>➕ Добавление ресурса</h1>
            </div>
            
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Тип ресурса <span class="required">*</span></label>
                    <select name="resource_type" id="resource_type" class="form-control" required onchange="loadResources(this.value)">
                        <option value="Сотрудник">Сотрудник</option>
                        <option value="Исполнитель">Исполнитель (ФЛ)</option>
                        <option value="Субподрядчик">Субподрядчик</option>
                        <option value="Оборудование">Оборудование</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Выбор из реестра</label>
                    <select name="resource_id" id="resource_select" class="form-control" onchange="fillResourceData()">
                        <option value="">— Выбрать из реестра —</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Название ресурса <span class="required">*</span></label>
                    <input type="text" name="resource_name" id="resource_name" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Название услуги <span class="required">*</span></label>
                    <input type="text" name="service_name" class="form-control" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Дата начала <span class="required">*</span></label>
                        <input type="date" name="start_date" class="form-control" value="<?= $project['start_date'] ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Дата окончания <span class="required">*</span></label>
                        <input type="date" name="end_date" class="form-control" value="<?= $project['end_date'] ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Количество <span class="required">*</span></label>
                        <input type="number" name="quantity" step="0.01" class="form-control" value="1" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Единица измерения <span class="required">*</span></label>
                        <select name="unit_type" id="unit_type" class="form-control" required>
                            <option value="часы">Часы</option>
                            <option value="дни">Дни</option>
                            <option value="полная стоимость">Полная стоимость</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Стоимость за ед. (₽) <span class="required">*</span></label>
                        <input type="number" name="unit_cost" id="unit_cost" step="0.01" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Маржинальность (%)</label>
                        <input type="number" name="margin_percent" step="0.1" class="form-control" value="30">
                    </div>
                </div>

                <button type="submit" class="btn btn-success">➕ Добавить в проект</button>
            </form>
        </div>
    </div>

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