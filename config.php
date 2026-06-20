<?php
$db = new PDO('sqlite:' . __DIR__ . '/database.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec("PRAGMA foreign_keys = ON");


$db->exec("
    CREATE TABLE IF NOT EXISTS workspaces (
        id INTEGER PRIMARY KEY,
        name TEXT NOT NULL,
        subdomain TEXT UNIQUE,
        admin_user_id INTEGER,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )
");

$db->exec("
    CREATE TABLE IF NOT EXISTS workspace_users (
        id INTEGER PRIMARY KEY,
        workspace_id INTEGER,
        user_id INTEGER,
        role TEXT DEFAULT 'Просмотр',
        FOREIGN KEY (workspace_id) REFERENCES workspaces(id),
        FOREIGN KEY (user_id) REFERENCES users(id)
    )
");


$db->exec("
    CREATE TABLE IF NOT EXISTS projects (
        id INTEGER PRIMARY KEY,
        workspace_id INTEGER NOT NULL DEFAULT 1,
        name TEXT NOT NULL,
        start_date DATE,
        end_date DATE,
        description TEXT,
        technical_task TEXT,
        customer_id INTEGER,
        tax_rate REAL DEFAULT 6.0,
        status TEXT DEFAULT 'Новый',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (workspace_id) REFERENCES workspaces(id)
    )
");

$db->exec("
    CREATE TABLE IF NOT EXISTS project_resources (
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


$db->exec("
    CREATE TABLE IF NOT EXISTS customers (
        id INTEGER PRIMARY KEY,
        workspace_id INTEGER DEFAULT 1,
        type TEXT NOT NULL,
        inn TEXT,
        name TEXT,
        director_name TEXT NOT NULL,
        email TEXT NOT NULL,
        phone TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )
");

$db->exec("
    CREATE TABLE IF NOT EXISTS employees (
        id INTEGER PRIMARY KEY,
        workspace_id INTEGER DEFAULT 1,
        last_name TEXT NOT NULL,
        first_name TEXT NOT NULL,
        middle_name TEXT,
        position TEXT NOT NULL,
        salary REAL NOT NULL,
        tax_rate REAL DEFAULT 30.2,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )
");

$db->exec("
    CREATE TABLE IF NOT EXISTS executors (
        id INTEGER PRIMARY KEY,
        workspace_id INTEGER DEFAULT 1,
        last_name TEXT NOT NULL,
        first_name TEXT NOT NULL,
        middle_name TEXT,
        contract_type TEXT NOT NULL,
        tax_rate REAL DEFAULT 0,
        unit_type TEXT NOT NULL,
        unit_cost REAL NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )
");

$db->exec("
    CREATE TABLE IF NOT EXISTS equipment (
        id INTEGER PRIMARY KEY,
        workspace_id INTEGER DEFAULT 1,
        name TEXT NOT NULL,
        description TEXT,
        acquisition_type TEXT NOT NULL,
        unit_type TEXT NOT NULL,
        unit_cost REAL NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )
");

$db->exec("
    CREATE TABLE IF NOT EXISTS subcontractors (
        id INTEGER PRIMARY KEY,
        workspace_id INTEGER DEFAULT 1,
        type TEXT NOT NULL,
        inn TEXT,
        name TEXT,
        last_name TEXT,
        first_name TEXT,
        email TEXT NOT NULL,
        phone TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )
");


$db->exec("
    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY,
        last_name TEXT NOT NULL,
        first_name TEXT NOT NULL,
        middle_name TEXT,
        email TEXT UNIQUE NOT NULL,
        position TEXT NOT NULL,
        password TEXT DEFAULT '123456',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )
");

$db->exec("
    CREATE TABLE IF NOT EXISTS roles (
        id INTEGER PRIMARY KEY,
        name TEXT UNIQUE NOT NULL
    )
");

$db->exec("
    CREATE TABLE IF NOT EXISTS user_roles (
        user_id INTEGER,
        role_id INTEGER,
        PRIMARY KEY (user_id, role_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
    )
");

$db->exec("
    CREATE TABLE IF NOT EXISTS company_settings (
        id INTEGER PRIMARY KEY DEFAULT 1,
        company_name TEXT,
        director_name TEXT,
        director_position TEXT,
        phone TEXT,
        email TEXT,
        logo_path TEXT
    )
");

$db->exec("
    CREATE TABLE IF NOT EXISTS user_permissions (
        user_id INTEGER,
        permission TEXT NOT NULL,
        value INTEGER DEFAULT 1,   -- 1 = разрешено, 0 = запрещено
        PRIMARY KEY (user_id, permission)
    )
");


$db->exec("INSERT OR IGNORE INTO roles (name) VALUES 
    ('Глобальный администратор'),
    ('Коммерческий директор'),
    ('Бухгалтер'),
    ('Кадровик')
");

try {
    $db->exec("ALTER TABLE projects ADD COLUMN technical_task TEXT");
    echo "<!-- Колонка technical_task добавлена -->";
} catch (Exception $e) {
    
}

try {
    $db->exec("ALTER TABLE projects ADD COLUMN status TEXT DEFAULT 'В процессе'");
    echo "<!-- Статус добавлен -->";
} catch (Exception $e) {

}

?>