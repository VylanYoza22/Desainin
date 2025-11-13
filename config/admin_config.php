<?php
/**
 * Admin Configuration & Access Control
 */
require_once __DIR__ . '/database.php';

// Define allowed admin emails or usernames
// Update this list to grant admin access
// Development bypass (allow admin pages without login)
const ALLOW_ADMIN_BYPASS = true; // WARNING: Do not use in production
const ALLOWED_ADMIN_EMAILS = [
    // Example: 'admin@example.com'
    'leywin22@gmail.com'
];
const ALLOWED_ADMIN_USERNAMES = [
    // Example: 'admin'
];

/**
 * Check if current logged-in user is admin
 * @return bool
 */
function isAdmin($conn): bool {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    $uid = (int)$_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT username, email, role FROM users WHERE id = ?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $user = $res->fetch_assoc();
        $stmt->close();
        // Prefer role from database
        if (isset($user['role']) && strtolower((string)$user['role']) === 'admin') {
            return true;
        }
        // Normalize for case-insensitive exact match
        $email = strtolower(trim((string)$user['email']));
        $username = strtolower(trim((string)$user['username']));
        $allowedEmails = array_map(fn($e) => strtolower(trim((string)$e)), ALLOWED_ADMIN_EMAILS);
        $allowedUsernames = array_map(fn($u) => strtolower(trim((string)$u)), ALLOWED_ADMIN_USERNAMES);
        if (in_array($email, $allowedEmails, true)) return true;
        if (in_array($username, $allowedUsernames, true)) return true;
    } else {
        $stmt->close();
    }
    return false;
}

/**
 * Enforce admin access (redirect to login or 403)
 */
function requireAdmin($conn) {
    // Bypass all checks if enabled
    if (defined('ALLOW_ADMIN_BYPASS') && ALLOW_ADMIN_BYPASS === true) {
        return;
    }
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../auth/login.php');
        exit();
    }
    if (!isAdmin($conn)) {
        http_response_code(403);
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>403 Forbidden</title></head><body style="font-family:system-ui;background:#0b0b0b;color:#fff;padding:2rem"><h1 style="color:#f87171">403 - Akses Ditolak</h1><p>Anda tidak memiliki hak akses untuk halaman ini.</p><p>Pastikan Anda login dengan email/username yang terdaftar sebagai admin.</p><a href="../../index.php" style="color:#93c5fd">Kembali ke Beranda</a></body></html>';
        exit();
    }
}

// Simple CSRF utilities
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf_token'];
}
function csrf_validate($token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$token);
}
