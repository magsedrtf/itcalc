<?php
require_once 'config.php';
require_once 'calculate.php';

$project_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$project_id) die("Проект не указан");

$stmt = $db->prepare("SELECT * FROM projects WHERE id = ?");
$stmt->execute([$project_id]);
$project = $stmt->fetch();

$stmt = $db->prepare("SELECT * FROM project_resources WHERE project_id = ? ORDER BY id");
$stmt->execute([$project_id]);
$resources = $stmt->fetchAll();


$settings = $db->query("SELECT * FROM company_settings LIMIT 1")->fetch();


$totalCostPrice = 0;
foreach ($resources as &$r) {
    switch ($r['resource_type']) {
        case 'Сотрудник':
            $emp = $db->prepare("SELECT salary, tax_rate FROM employees WHERE id = ?");
            $emp->execute([$r['resource_id']]);
            $data = $emp->fetch();
            $r['cost_price'] = $data ? calcEmployeeCostExact($data['salary'], $data['tax_rate'], $r['start_date'], $r['end_date']) : 0;
            break;

        case 'Исполнитель':
            $exec = $db->prepare("SELECT contract_type, tax_rate, unit_cost FROM executors WHERE id = ?");
            $exec->execute([$r['resource_id']]);
            $data = $exec->fetch();
            $tax = ($data && $data['contract_type'] === 'НПД') ? 0 : ($data['tax_rate'] ?? 0);
            $r['cost_price'] = calcExecutorCost($r['quantity'], $r['unit_cost'], $tax);
            break;

        case 'Оборудование':
            $r['cost_price'] = calcEquipmentCost($r['quantity'], $r['unit_cost']);
            break;

        case 'Субподрядчик':
        default:
            $r['cost_price'] = $r['quantity'] * $r['unit_cost'];
    }
    $totalCostPrice += $r['cost_price'];
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Стоимость НМА — <?= htmlspecialchars($project['name']) ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; line-height: 1.6; }
        .header { text-align: center; margin-bottom: 40px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #000; padding: 10px; text-align: left; }
        th { background: #f0f0f0; }
        .total { font-weight: bold; background: #e8f5e9; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Стоимость нематериального актива (НМА)</h1>
        <p>Дата: <?= date('d.m.Y') ?></p>
    </div>

    <h2><?= htmlspecialchars($project['name']) ?></h2>
    <p><strong>Срок разработки:</strong> <?= $project['start_date'] ?> — <?= $project['end_date'] ?></p>

    <table>
        <tr>
            <th>Ресурс</th>
            <th>Услуга</th>
            <th>Кол-во</th>
            <th>Ед.</th>
            <th>Период</th>
            <th>Себестоимость, ₽</th>
        </tr>
        <?php foreach ($resources as $r): ?>
        <tr>
            <td><?= htmlspecialchars($r['resource_name']) ?></td>
            <td><?= htmlspecialchars($r['service_name'] ?? '-') ?></td>
            <td><?= $r['quantity'] ?></td>
            <td><?= $r['unit_type'] ?></td>
            <td><?= $r['start_date'] ?> — <?= $r['end_date'] ?></td>
            <td><?= number_format($r['cost_price'], 2) ?></td>
        </tr>
        <?php endforeach; ?>
        <tr class="total">
            <td colspan="5"><strong>ИТОГО СЕБЕСТОИМОСТЬ НМА</strong></td>
            <td><strong><?= number_format($totalCostPrice, 2) ?> ₽</strong></td>
        </tr>
    </table>

    <p>С уважением,<br>
       <?= htmlspecialchars($settings['director_position'] ?? 'Генеральный директор') ?> 
       <?= htmlspecialchars($settings['director_name'] ?? '') ?></p>

    <p><em>Документ для постановки на баланс предприятия.</em></p>
    <p><a href="#" onclick="window.print()">Печать / Сохранить как PDF</a></p>
</body>
</html>