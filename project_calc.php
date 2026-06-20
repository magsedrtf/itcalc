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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Подробный расчёт</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <a href="project_manage.php?id=<?= $project_id ?>" class="back-link">← К управлению проектом</a>

        <div class="card">
            <div class="card-header">
                <h1>📊 Подробный расчёт</h1>
                <span style="color:var(--gray-500); font-size:14px;"><?= htmlspecialchars($project['name']) ?></span>
            </div>

            <?php if (empty($calculatedResources)): ?>
                <div class="text-center" style="padding:40px 0; color:var(--gray-500);">
                    <p>В проекте пока нет ресурсов для расчёта</p>
                </div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
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
                        </thead>
                        <tbody>
                            <?php foreach ($calculatedResources as $r): ?>
                            <tr>
                                <td><?= htmlspecialchars($r['resource_name']) ?></td>
                                <td><?= $r['resource_type'] ?></td>
                                <td><?= htmlspecialchars($r['service_name'] ?? '-') ?></td>
                                <td><?= $r['quantity'] ?></td>
                                <td><?= $r['unit_type'] ?></td>
                                <td><?= number_format($r['cost_price'], 2) ?></td>
                                <?php if ($canSeeMargin): ?>
                                    <td><?= $r['margin_percent'] ?? 0 ?>%</td>
                                    <td><?= number_format($r['total_cost'], 2) ?></td>
                                    <td><?= number_format($r['profit'], 2) ?></td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot style="font-weight:bold; background:var(--gray-100);">
                            <tr>
                                <td colspan="5">ИТОГО</td>
                                <td><?= number_format($totalCostPrice, 2) ?></td>
                                <?php if ($canSeeMargin): ?>
                                    <td></td>
                                    <td><?= number_format($totalWithMargin, 2) ?></td>
                                    <td><?= number_format($totalProfit, 2) ?></td>
                                <?php endif; ?>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php endif; ?>

            <div class="result-box">
                <h3>Финальные показатели</h3>
                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px,1fr)); gap:16px; margin-top:12px;">
                    <div><strong>Себестоимость:</strong><br><?= number_format($totalCostPrice, 2) ?> ₽</div>
                    <?php if ($canSeeMargin): ?>
                        <div><strong>С маржинальностью:</strong><br><?= number_format($totalWithMargin, 2) ?> ₽</div>
                        <div><strong>Чистая прибыль:</strong><br><?= number_format($totalProfit, 2) ?> ₽</div>
                    <?php endif; ?>
                    <div><strong style="color:var(--primary);">ИТОГО ДЛЯ ЗАКАЗЧИКА:</strong><br>
                        <?= number_format($projectTotal, 2) ?> ₽ (налог <?= $project['tax_rate'] ?? 6 ?>%)
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>