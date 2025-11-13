<?php
session_start();
require_once '../../config/database.php';

// If logged in, mark user offline
if (isset($_SESSION['user_id'])) {
    try {
        $uid = (int)$_SESSION['user_id'];
        $stmt = $conn->prepare("UPDATE users SET is_online = 0 WHERE id = ?");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $stmt->close();
    } catch (Exception $e) { /* ignore tracking errors */ }
}

// Hapus semua session variables
$_SESSION = array();

// Hapus session cookie jika ada
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Hancurkan session
session_destroy();

// Redirect ke halaman login
header("Location: ../../index.php?message=logout_success");
exit();
?>
