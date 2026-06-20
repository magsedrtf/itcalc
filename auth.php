<?php
require_once 'config.php';

function hasRole($requiredRoles) {
    global $db;
    $user_id = $_COOKIE['user_id'] ?? null;
    if (!$user_id) return false;

    if (!is_array($requiredRoles)) {
        $requiredRoles = [$requiredRoles];
    }

    $stmt = $db->prepare("
        SELECT r.name 
        FROM roles r
        JOIN user_roles ur ON r.id = ur.role_id
        WHERE ur.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $userRoles = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($requiredRoles as $role) {
        if (in_array($role, $userRoles)) {
            return true;
        }
    }
    return false;
}


function hasPermission($permission) {
    global $db;
    $user_id = $_COOKIE['user_id'] ?? null;
    if (!$user_id) return false;

    if (hasRole('Глобальный администратор')) return true;

    $stmt = $db->prepare("SELECT value FROM user_permissions WHERE user_id = ? AND permission = ?");
    $stmt->execute([$user_id, $permission]);
    $result = $stmt->fetchColumn();
    
    return $result !== false ? (bool)$result : true; // по умолчанию разрешено
}

function canViewMargin() {
    return hasPermission('view_margin');
}

function canManageProjects() {
    return hasPermission('manage_projects');
}

function canDeleteProjects() {
    return hasPermission('delete_projects');
}

function canManageResources() {
    return hasPermission('manage_resources');
}

function canGenerateDocuments() {
    return hasPermission('generate_documents');
}

function canManageCustomers() {
    return hasPermission('manage_customers');
}

// Обновлённые defaults (как ты прислал)
function setDefaultPermissions($user_id, $role_name) {
    global $db;
    $db->prepare("DELETE FROM user_permissions WHERE user_id = ?")->execute([$user_id]);

    $defaults = [
        'view_margin'            => in_array($role_name, ['Глобальный администратор', 'Коммерческий директор']),
        'manage_projects'        => true,
        'delete_projects'        => in_array($role_name, ['Глобальный администратор', 'Коммерческий директор']),
        'manage_resources'       => true,   // реестры вне проектов
        'generate_documents'     => true,
        'manage_customers'       => true,
        'manage_employees'       => in_array($role_name, ['Глобальный администратор', 'Кадровик']), // оставляем для совместимости
        'manage_users'           => in_array($role_name, ['Глобальный администратор']),
        'view_user_credentials'  => in_array($role_name, ['Глобальный администратор'])
    ];

    $stmt = $db->prepare("INSERT INTO user_permissions (user_id, permission, value) VALUES (?,?,?)");
    foreach ($defaults as $perm => $value) {
        $stmt->execute([$user_id, $perm, $value ? 1 : 0]);
    }
}
// в auth.php после остальных функций

function isGlobalAdmin() {
    return hasRole('Глобальный администратор');
}
/**
 * Проверяет, имеет ли текущий пользователь доступ к рабочей области
 * @param int $workspace_id ID рабочей области
 * @return bool
 */
function hasWorkspaceAccess($workspace_id) {
    global $db;
    $user_id = $_COOKIE['user_id'] ?? null;
    if (!$user_id) return false;

    // Глобальный администратор имеет доступ ко всем рабочим областям
    if (isGlobalAdmin()) return true;

    $stmt = $db->prepare("SELECT 1 FROM workspace_users WHERE user_id = ? AND workspace_id = ?");
    $stmt->execute([$user_id, $workspace_id]);
    return $stmt->fetchColumn() !== false;
}

?>