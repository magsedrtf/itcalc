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


if (!hasWorkspaceAccess($workspace_id)) {
    die("Нет доступа к этому проекту");
}


$canManageRes = canManageResources();
$canGenerateDocs = canGenerateDocuments();
$canSeeMargin = canViewMargin();


if (!$canManageRes) {

}


$stmt = $db->prepare("SELECT * FROM project_resources WHERE project_id = ? ORDER BY id");
$stmt->execute([$project_id]);
$resources = $stmt->fetchAll();


$totalCostPrice = 0;
$totalWithMargin = 0;
$totalProfit = 0;

foreach ($resources as &$r) {
    switch ($r['resource_type']) {
        case 'Сотрудник':
            $emp = $db->prepare("SELECT salary, tax_rate FROM employees WHERE id = ? AND workspace_id = ?");
            $emp->execute([$r['resource_id'], $workspace_id]);
            $data = $emp->fetch();
            $r['cost_price'] = $data ? calcEmployeeCostExact($data['salary'], $data['tax_rate'], $r['start_date'], $r['end_date']) : 0;
            break;

        case 'Исполнитель':
            $exec = $db->prepare("SELECT contract_type, tax_rate, unit_cost FROM executors WHERE id = ? AND workspace_id = ?");
            $exec->execute([$r['resource_id'], $workspace_id]);
            $data = $exec->fetch();
            $tax = ($data && $data['contract_type'] === 'НПД') ? 0 : ($data['tax_rate'] ?? 0);
            $r['cost_price'] = calcExecutorCost($r['quantity'], $data['unit_cost'] ?? $r['unit_cost'], $tax);
            break;

        case 'Оборудование':
            $r['cost_price'] = calcEquipmentCost($r['quantity'], $r['unit_cost']);
            break;

        case 'Субподрядчик':
        default:
            $r['cost_price'] = $r['quantity'] * $r['unit_cost'];
            break;
    }

    $r['total_cost'] = calcTotalWithMargin($r['cost_price'], $r['margin_percent'] ?? 0);
    $r['profit'] = calcProfit($r['total_cost'], $r['cost_price']);

    $totalCostPrice += $r['cost_price'];
    $totalWithMargin += $r['total_cost'];
    $totalProfit += $r['profit'];
}

$projectTotal = calcProjectTotal($totalWithMargin, $project['tax_rate'] ?? 6);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление проектом: <?= htmlspecialchars($project['name']) ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; background: #f9f9f9; }
        table { border-collapse: collapse; width: 100%; background: white; }
        th, td { border: 1px solid #ddd; padding: 10px; }
        th { background: #4CAF50; color: white; }
        .btn { padding: 8px 15px; color: white; text-decoration: none; border-radius: 4px; }
        .btn-add { background: #4CAF50; }
        .result { background: #e8f5e9; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .disabled { opacity: 0.6; pointer-events: none; }
    </style>
</head>
<body>
    <a href="projects.php?workspace_id=<?= $workspace_id ?>">← Все проекты</a>
    <h1>Управление проектом: <?= htmlspecialchars($project['name']) ?></h1>

    <p><b>Сроки:</b> <?= $project['start_date'] ?> — <?= $project['end_date'] ?> | 
       <b>Налог:</b> <?= $project['tax_rate'] ?? 6 ?>%</p>

    <h2>Управление ресурсами проекта</h2>
    <?php if ($canManageRes): ?>
        <a href="resource_add.php?project_id=<?= $project_id ?>" class="btn btn-add">+ Добавить ресурс</a>
    <?php endif; ?>

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
            <th>Действия</th>
        </tr>
        <?php foreach ($resources as $r): ?>
        <tr>
            <td><?= htmlspecialchars($r['resource_name']) ?></td>
            <td><?= htmlspecialchars($r['resource_type']) ?></td>
            <td><?= htmlspecialchars($r['service_name'] ?? '-') ?></td>
            <td><?= $r['quantity'] ?></td>
            <td><?= $r['unit_type'] ?></td>
            <td><?= number_format($r['cost_price'], 2) ?></td>
            <?php if ($canSeeMargin): ?>
            <td><?= $r['margin_percent'] ?? 0 ?>%</td>
            <td><?= number_format($r['total_cost'], 2) ?></td>
            <td><?= number_format($r['profit'], 2) ?></td>
            <?php endif; ?>
            <td>
                <?php if ($canManageRes): ?>
                <a href="resource_edit.php?id=<?= $r['id'] ?>" class="btn" style="background:#2196F3">Ред.</a>
                <a href="resource_delete.php?id=<?= $r['id'] ?>" class="btn" style="background:#f44336" onclick="return confirm('Удалить?')">Уд.</a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>

    <div class="result">
        <h3>Итоги проекта</h3>
        <p><b>Себестоимость проекта:</b> <?= number_format($totalCostPrice, 2) ?> ₽</p>
        <?php if ($canSeeMargin): ?>
        <p><b>Стоимость с маржинальностью:</b> <?= number_format($totalWithMargin, 2) ?> ₽</p>
        <p><b>Чистая прибыль:</b> <?= number_format($totalProfit, 2) ?> ₽</p>
        <?php endif; ?>
        <p><b>ИТОГО ДЛЯ ЗАКАЗЧИКА (с налогом <?= $project['tax_rate'] ?>%):</b> <?= number_format($projectTotal, 2) ?> ₽</p>
    </div>
    
    <a href="project_calc.php?id=<?= $project_id ?>" class="btn" style="background:#4CAF50">📊 Полный расчёт проекта</a>

    <h3>Документы</h3>
    <?php if ($canGenerateDocs): ?>
    <a href="generate_cp.php?id=<?= $project_id ?>" target="_blank" class="btn" style="background:#2196F3">Коммерческое предложение</a>
    <a href="generate_nma.php?id=<?= $project_id ?>" target="_blank" class="btn" style="background:#FF9800">Стоимость НМА</a>
    <a href="project_tz.php?id=<?= $project_id ?>" class="btn" style="background:#9C27B0">📝 Техническое задание</a>
    <?php else: ?>
    <p style="color:#999;">У вас нет прав на генерацию документов.</p>
    <?php endif; ?>
</body>
</html>