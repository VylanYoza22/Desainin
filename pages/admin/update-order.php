<?php
/**
 * Admin: Update Order Status Endpoint
 */
session_start();
require_once '../../config/database.php';
require_once '../../config/admin_config.php';
require_once '../../config/status_functions.php';

// Require admin
requireAdmin($conn);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method Not Allowed';
    exit();
}

$orderId = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
$newStatus = $_POST['new_status'] ?? '';
$description = trim($_POST['description'] ?? '');
$token = $_POST['csrf_token'] ?? '';

if (!$orderId || !$newStatus) {
    http_response_code(400);
    echo 'Invalid request';
    exit();
}

if (!csrf_validate($token)) {
    http_response_code(403);
    echo 'Invalid CSRF token';
    exit();
}

// Validate status
$validStatuses = array_keys(getStatusDefinitions());
if (!in_array($newStatus, $validStatuses, true)) {
    http_response_code(400);
    echo 'Invalid status';
    exit();
}

// Update status & progress
$ok = updateOrderStatus($conn, $orderId, $newStatus, $description !== '' ? $description : null);

if ($ok) {
    header('Location: orders.php?updated=1');
    exit();
}

http_response_code(500);
Echo 'Failed to update order';
