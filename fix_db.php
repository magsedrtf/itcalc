<?php
require_once 'config.php';

$tables = ['project_resources', 'projects', 'customers', 'employees', 'executors', 'equipment', 'subcontractors', 'workspace_users', 'workspaces', 'users','company_settings'];

foreach ($tables as $table) {
    $db->exec("DELETE FROM `$table`");
    echo "Очищено: $table<br>";
}

echo "<h2>База очищена. Перейди на <a href='login.php'>login.php</a></h2>";
?>