<?php
require_once 'config.php';

// Cek status login
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Validasi role
function requireRole($allowedRoles) {
    if (!isLoggedIn() || !in_array($_SESSION['role'], $allowedRoles)) {
        redirect('index.php', 'error', 'Akses ditolak!');
    }
}

// Get user data
function getUserData() {
    global $conn;
    if (isLoggedIn()) {
        $userId = (int) $_SESSION['user_id'];
        $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ? LIMIT 1");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    return null;
}
?>