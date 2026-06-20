<?php
require_once 'config.php';

echo "<h2>🔧 Исправление CHECK constraint для ресурсов...</h2>";

try {
    // Удаляем старую таблицу и создаём новую с правильным CHECK
    $db->exec("DROP TABLE IF EXISTS project_resources_old");
    $db->exec("ALTER TABLE project_resources RENAME TO project_resources_old");

    $db->exec("
        CREATE TABLE project_resources (
            id INTEGER PRIMARY KEY,
            project_id INTEGER NOT NULL,
            resource_type TEXT NOT NULL CHECK (resource_type IN ('Сотрудник', 'Исполнитель', 'Субподрядчик', 'Оборудование')),
            resource_id INTEGER,
            resource_name TEXT NOT NULL,
            service_name TEXT,
            start_date DATE,
            end_date DATE,
            quantity REAL NOT NULL,
            unit_type TEXT NOT NULL,
            unit_cost REAL NOT NULL,
            margin_percent REAL DEFAULT 0,
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
        )
    ");

    // Копируем данные из старой таблицы
    $db->exec("
        INSERT INTO project_resources 
        (id, project_id, resource_type, resource_id, resource_name, service_name, 
         start_date, end_date, quantity, unit_type, unit_cost, margin_percent)
        SELECT 
        id, project_id, resource_type, resource_id, resource_name, service_name, 
        start_date, end_date, quantity, unit_type, unit_cost, margin_percent
        FROM project_resources_old
    ");

    $db->exec("DROP TABLE project_resources_old");

    echo "<p style='color:green; font-weight:bold'>✅ CHECK constraint успешно обновлён! Теперь можно добавлять Субподрядчиков.</p>";

} catch (Exception $e) {
    echo "<p style='color:red'>Ошибка: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<br><a href='projects.php'>→ Вернуться к проектам</a>";
?>