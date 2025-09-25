<?php
/**
 * Edit Order - Redirect to proper location
 */
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: pages/auth/login.php');
    exit();
}

// Redirect to the actual edit page
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
header("Location: pages/orders/edit.php?id=$order_id");
exit();
?>
