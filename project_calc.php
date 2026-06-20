<?php
require_once 'config.php';
require_once 'calculate.php';
require_once 'auth.php';

$user_id = $_COOKIE['user_id'] ?? null;
if (!$user_id) {
    header('Location: login.php');
    exit;
}

$project_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$project_id) {
    header('Location: projects.php');
    exit;
}


$stmt = $db->prepare("SELECT * FROM projects WHERE id = ?");
$stmt->execute([$project_id]);
$project = $stmt->fetch();

if (!$project) die("Проект не найден");

$workspace_id = $project['workspace_id'];


$stmt = $db->prepare("SELECT * FROM project_resources WHERE project_id = ? ORDER BY id");
$stmt->execute([$project_id]);
$resources = $stmt->fetchAll();


$roleStmt = $db->prepare("SELECT r.name FROM roles r 
    JOIN user_roles ur ON r.id = ur.role_id 
    WHERE ur.user_id = ?");
$roleStmt->execute([$user_id]);
$userRoles = $roleStmt->fetchAll(PDO::FETCH_COLUMN);

$canSeeMargin = canViewMargin();


$totalCostPrice = 0;
$totalWithMargin = 0;
$totalProfit = 0;
$calculatedResources = [];

foreach ($resources as $r) {
    switch ($r['resource_type']) {
        case 'Сотрудник':
            $emp = $db->prepare("SELECT salary, tax_rate FROM employees WHERE id = ?");
            $emp->execute([$r['resource_id']]);
            $data = $emp->fetch();
            $cost_price = $data ? calcEmployeeCostExact($data['salary'], $data['tax_rate'], $r['start_date'], $r['end_date']) : 0;
            break;

        case 'Исполнитель':
            $exec = $db->prepare("SELECT contract_type, tax_rate, unit_cost FROM executors WHERE id = ?");
            $exec->execute([$r['resource_id']]);
            $data = $exec->fetch();
            $tax = ($data && $data['contract_type'] === 'НПД') ? 0 : ($data['tax_rate'] ?? 0);
            $cost_price = calcExecutorCost($r['quantity'], $data['unit_cost'] ?? $r['unit_cost'], $tax);
            break;

        case 'Оборудование':
            $cost_price = calcEquipmentCost($r['quantity'], $r['unit_cost']);
            break;

        case 'Субподрядчик':
        default:
            $cost_price = $r['quantity'] * $r['unit_cost'];
    }

    $total_cost = calcTotalWithMargin($cost_price, $r['margin_percent'] ?? 0);
    $profit = calcProfit($total_cost, $cost_price);

    $totalCostPrice += $cost_price;
    $totalWithMargin += $total_cost;
    $totalProfit += $profit;

    $calculatedResources[] = array_merge($r, [
        'cost_price' => $cost_price,
        'total_cost' => $total_cost,
        'profit' => $profit
    ]);
}

$projectTotal = calcProjectTotal($totalWithMargin, $project['tax_rate'] ?? 6);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Подробный расчёт: <?= htmlspecialchars($project['name']) ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 10px; }
        th { background: #4CAF50; color: white; }
        .total { font-weight: bold; background: #f0f0f0; }
        .result { background: #e8f5e9; padding: 20px; border-radius: 8px; }
        .back { margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="back">
        <a href="project_manage.php?id=<?= $project_id ?>">← К управлению проектом</a>
    </div>

    <h1>Подробный расчёт проекта: <?= htmlspecialchars($project['name']) ?></h1>

    <table>
        <tr>
            <th>Ресурс</th>
            <th>Тип</th>
            <th>Услуга</th>
            <th>Кол-во</th>
            <th>Ед.</th>
            <th>Себестоимость</th>
            <?php if ($canSeeMargin): ?>
            <th>Маржа %</th>
            <th>С маржой</th>
            <th>Прибыль</th>
            <?php endif; ?>
        </tr>
        <?php foreach ($calculatedResources as $r): ?>
        <tr>
            <td><?= htmlspecialchars($r['resource_name']) ?></td>
            <td><?= $r['resource_type'] ?></td>
            <td><?= htmlspecialchars($r['service_name'] ?? '-') ?></td>
            <td><?= $r['quantity'] ?></td>
            <td><?= $r['unit_type'] ?></td>
            <td><?= number_format($r['cost_price'], 2, '.', ' ') ?></td>
            <?php if ($canSeeMargin): ?>
            <td><?= $r['margin_percent'] ?? 0 ?>%</td>
            <td><?= number_format($r['total_cost'], 2, '.', ' ') ?></td>
            <td><?= number_format($r['profit'], 2, '.', ' ') ?></td>
            <?php endif; ?>
        </tr>
        <?php endforeach; ?>
        <tr class="total">
            <td colspan="5"><strong>ИТОГО</strong></td>
            <td><strong><?= number_format($totalCostPrice, 2, '.', ' ') ?></strong></td>
            <?php if ($canSeeMargin): ?>
            <td></td>
            <td><strong><?= number_format($totalWithMargin, 2, '.', ' ') ?></strong></td>
            <td><strong><?= number_format($totalProfit, 2, '.', ' ') ?></strong></td>
            <?php endif; ?>
        </tr>
    </table>

    <div class="result">
        <h3>Финальные показатели</h3>
        <p><strong>Себестоимость проекта:</strong> <?= number_format($totalCostPrice, 2, '.', ' ') ?> ₽</p>
        <?php if ($canSeeMargin): ?>
        <p><strong>Стоимость с маржинальностью:</strong> <?= number_format($totalWithMargin, 2, '.', ' ') ?> ₽</p>
        <p><strong>Чистая прибыль:</strong> <?= number_format($totalProfit, 2, '.', ' ') ?> ₽</p>
        <?php endif; ?>
        <p><strong>ИТОГО ДЛЯ ЗАКАЗЧИКА (с налогом <?= $project['tax_rate'] ?>%):</strong> <?= number_format($projectTotal, 2, '.', ' ') ?> ₽</p>
    </div>
</body>
</html>