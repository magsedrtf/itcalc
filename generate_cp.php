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


$customer = null;
if ($project['customer_id']) {
    $stmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$project['customer_id']]);
    $customer = $stmt->fetch();
}


$totalWithMargin = 0;
foreach ($resources as &$r) {

    switch ($r['resource_type']) {
        case 'Сотрудник':
            $emp = $db->prepare("SELECT salary, tax_rate FROM employees WHERE id = ?");
            $emp->execute([$r['resource_id']]);
            $data = $emp->fetch();
            $r['cost_price'] = $data ? calcEmployeeCostExact($data['salary'], $data['tax_rate'], $r['start_date'], $r['end_date']) : 0;
            break;
        case 'Исполнитель':
            $exec = $db->prepare("SELECT contract_type, tax_rate FROM executors WHERE id = ?");
            $exec->execute([$r['resource_id']]);
            $data = $exec->fetch();
            $tax = ($data && $data['contract_type'] === 'НПД') ? 0 : ($data['tax_rate'] ?? 0);
            $r['cost_price'] = calcExecutorCost($r['quantity'], $r['unit_cost'], $tax);
            break;
        case 'Оборудование':
            $r['cost_price'] = calcEquipmentCost($r['quantity'], $r['unit_cost']);
            break;
        default:
            $r['cost_price'] = $r['quantity'] * $r['unit_cost'];
    }
    
    $r['total_cost'] = calcTotalWithMargin($r['cost_price'], $r['margin_percent'] ?? 0);
    $totalWithMargin += $r['total_cost'];
}

$totalWithTax = calcProjectTotal($totalWithMargin, $project['tax_rate'] ?? 6);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Коммерческое предложение</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; line-height: 1.6; }
        .header { text-align: center; margin-bottom: 40px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
        th { background: #f0f0f0; }
        .total { font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Коммерческое предложение</h1>
        <p>Дата: <?= date('d.m.Y') ?></p>
    </div>

    <?php if ($customer): ?>
    <p><strong>Заказчику:</strong><br>
       <?= htmlspecialchars($customer['director_name'] ?? '') ?><br>
       <?= htmlspecialchars($customer['name'] ?? $customer['director_name'] ?? $customer['email'] ?? '') ?></p>
    <?php endif; ?>

    <h2><?= htmlspecialchars($project['name']) ?></h2>
    <p><strong>Срок выполнения:</strong> <?= $project['start_date'] ?> — <?= $project['end_date'] ?></p>

    <table>
        <tr>
            <th>№</th>
            <th>Наименование услуги</th>
            <th>Кол-во</th>
            <th>Ед.</th>
            <th>Период</th>
            <th>Стоимость, ₽</th>
        </tr>
        <?php 
        $i = 1;
        foreach ($resources as $r): 
            $cost = $r['total_cost'];
        ?>
        <tr>
            <td><?= $i++ ?></td>
            <td><?= htmlspecialchars($r['service_name']) ?></td>
            <td><?= $r['quantity'] ?></td>
            <td><?= $r['unit_type'] ?></td>
            <td><?= $r['start_date'] ?> — <?= $r['end_date'] ?></td>
            <td><?= number_format($cost, 2) ?></td>
        </tr>
        <?php endforeach; ?>
        <tr class="total">
            <td colspan="5"><strong>Итого с НДС:</strong></td>
            <td><strong><?= number_format($totalWithTax, 2) ?> ₽</strong></td>
        </tr>
    </table>

    <p>С уважением,<br>
       <?= htmlspecialchars($settings['director_position'] ?? 'Генеральный директор') ?> 
       <?= htmlspecialchars($settings['director_name'] ?? '') ?></p>

    <p><a href="#" onclick="window.print()">Печать / Сохранить как PDF</a></p>
</body>
</html>