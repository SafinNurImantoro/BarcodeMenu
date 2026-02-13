<?php
/**
 * Audit Logging Helper
 * Logs all admin actions for security and compliance
 */

require_once __DIR__ . '/config.php';

/**
 * Log an admin action/activity
 * @param string $action Action name (e.g., 'menu_added', 'order_status_changed')
 * @param string $category Category (e.g., 'menu', 'order', 'admin', 'security')
 * @param string $details Details of the action
 * @param string $level Log level (info, warning, danger, success)
 * @param mixed $data Additional data (e.g., order id, menu id)
 * @return bool Success or failure
 */
function logAuditActivity($action, $category, $details = '', $level = 'info', $data = null) {
    if (!ENABLE_AUDIT_LOG) {
        return false;
    }
    
    try {
        $log_dir = __DIR__ . '/logs';
        
        // Create logs directory if doesn't exist
        if (!is_dir($log_dir)) {
            @mkdir($log_dir, 0755, true);
        }
        
        // Check if directory is writable
        if (!is_writable($log_dir)) {
            error_log("Log directory not writable: " . $log_dir);
            return false;
        }
        
        $log_file = $log_dir . '/audit.log';
        
        $timestamp = date('Y-m-d H:i:s');
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $admin_user = getAdminUsername() ?? 'unknown';
        $user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 100);
        
        // Build log entry
        $log_entry = "[{$timestamp}] ";
        $log_entry .= "ACTION: {$action} | ";
        $log_entry .= "CATEGORY: {$category} | ";
        $log_entry .= "ADMIN: {$admin_user} | ";
        $log_entry .= "LEVEL: {$level} | ";
        $log_entry .= "IP: {$ip_address}";
        
        if (!empty($details)) {
            $log_entry .= " | DETAILS: {$details}";
        }
        
        if ($data !== null) {
            $data_str = is_array($data) ? json_encode($data) : (string)$data;
            $log_entry .= " | DATA: {$data_str}";
        }
        
        $log_entry .= " | UA: {$user_agent}\n";
        
        // Write to log file
        @error_log($log_entry, 3, $log_file);
        return true;
        
    } catch (Exception $e) {
        error_log("Audit logging error: " . $e->getMessage());
        return false;
    }
}

/**
 * Log menu action
 * @param string $action (added, updated, deleted)
 * @param int $menu_id Menu ID
 * @param array $menu_data Menu data
 */
function logMenuAction($action, $menu_id, $menu_data = []) {
    $action_map = [
        'added' => 'Menu Item Added',
        'updated' => 'Menu Item Updated',
        'deleted' => 'Menu Item Deleted'
    ];
    
    $details = isset($action_map[$action]) ? $action_map[$action] : $action;
    
    logAuditActivity(
        'menu_' . $action,
        'menu',
        $details . ' (ID: ' . $menu_id . ')',
        $action === 'deleted' ? 'warning' : 'info',
        ['menu_id' => $menu_id, 'menu_data' => $menu_data]
    );
}

/**
 * Log order action
 * @param string $action (status_changed, deleted, viewed)
 * @param int $order_id Order ID
 * @param string $details Details of the change
 */
function logOrderAction($action, $order_id, $details = '') {
    $action_map = [
        'status_changed' => 'Order Status Changed',
        'deleted' => 'Order Deleted',
        'viewed' => 'Order Viewed'
    ];
    
    $action_label = isset($action_map[$action]) ? $action_map[$action] : $action;
    $detail_msg = $action_label . ' (ID: ' . $order_id . ')';
    
    if (!empty($details)) {
        $detail_msg .= ' - ' . $details;
    }
    
    logAuditActivity(
        'order_' . $action,
        'order',
        $detail_msg,
        $action === 'deleted' ? 'warning' : 'info',
        ['order_id' => $order_id, 'details' => $details]
    );
}

/**
 * Log security event
 * @param string $event Event description
 * @param string $level Log level (info, warning, danger, success)
 * @param string $details Additional details
 */
function logSecurityEvent($event, $level = 'info', $details = '') {
    logAuditActivity(
        'security_event',
        'security',
        $event . ($details ? ' - ' . $details : ''),
        $level,
        ['event' => $event]
    );
}

?>
