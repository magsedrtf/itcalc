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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление проектом</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <a href="projects.php?workspace_id=<?= $workspace_id ?>" class="back-link">← Все проекты</a>

        <div class="card">
            <div class="card-header">
                <div>
                    <h1>📊 <?= htmlspecialchars($project['name']) ?></h1>
                    <p class="page-subtitle">
                        Сроки: <?= $project['start_date'] ?> — <?= $project['end_date'] ?> | 
                        Налог: <?= $project['tax_rate'] ?? 6 ?>%
                    </p>
                </div>
                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                    <?php if ($canManageRes): ?>
                        <a href="resource_add.php?project_id=<?= $project_id ?>" class="btn btn-success">+ Добавить ресурс</a>
                    <?php endif; ?>
                </div>
            </div>

            <h2>Ресурсы проекта</h2>
            
            <?php if (empty($resources)): ?>
                <div class="text-center" style="padding:30px 0; color:var(--gray-500);">
                    <p>В проекте пока нет ресурсов</p>
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
                                <?php if ($canManageRes): ?>
                                    <th>Действия</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($resources as $r): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($r['resource_name']) ?></strong></td>
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
                                <?php if ($canManageRes): ?>
                                    <td>
                                        <div class="actions">
                                            <a href="resource_edit.php?id=<?= $r['id'] ?>" class="btn btn-info btn-sm">✏️</a>
                                            <a href="resource_delete.php?id=<?= $r['id'] ?>" class="btn btn-danger btn-sm" 
                                               onclick="return confirm('Удалить?')">🗑️</a>
                                        </div>
                                    </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <div class="result-box">
                <h3>📈 Итоги проекта</h3>
                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px,1fr)); gap:16px; margin-top:12px;">
                    <div><strong>Себестоимость:</strong><br><?= number_format($totalCostPrice, 2) ?> ₽</div>
                    <?php if ($canSeeMargin): ?>
                        <div><strong>С маржинальностью:</strong><br><?= number_format($totalWithMargin, 2) ?> ₽</div>
                        <div><strong>Чистая прибыль:</strong><br><?= number_format($totalProfit, 2) ?> ₽</div>
                    <?php endif; ?>
                    <div><strong style="color:var(--primary);">Итого для заказчика:</strong><br>
                        <?= number_format($projectTotal, 2) ?> ₽ (налог <?= $project['tax_rate'] ?? 6 ?>%)
                    </div>
                </div>
            </div>

            <div style="display:flex; flex-wrap:wrap; gap:10px; margin-top:16px;">
                <a href="project_calc.php?id=<?= $project_id ?>" class="btn btn-primary">📊 Полный расчёт</a>
                <?php if ($canGenerateDocs): ?>
                    <a href="generate_cp.php?id=<?= $project_id ?>" target="_blank" class="btn btn-info">📄 Коммерческое предложение</a>
                    <a href="generate_nma.php?id=<?= $project_id ?>" target="_blank" class="btn btn-warning">📄 Стоимость НМА</a>
                    <a href="project_tz.php?id=<?= $project_id ?>" class="btn btn-primary">📝 Техническое задание</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>