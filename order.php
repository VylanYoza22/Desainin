<?php
/**
 * Order Creation System - Main Entry Point
 * Redirects to the proper order creation page
 */
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: pages/auth/login.php');
    exit();
}

// Redirect to the actual order creation page
header('Location: pages/orders/create.php');
exit();
?>
