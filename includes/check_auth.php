<?php
/**
 * Authentication Check
 * Include this file at the top of protected pages
 */

require_once __DIR__ . '/functions.php';

startSession();

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('public/login.php');
    exit();
}

// Check role if required role is specified
if (isset($requiredRole)) {
    if (!hasRole($requiredRole)) {
        // User doesn't have required role
        $_SESSION['error'] = 'You do not have permission to access this page.';
        redirect('public/' . strtolower(getCurrentUserRole()) . '_dashboard.php');
        exit();
    }
}

// Update last login time
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $stmt->execute([getCurrentUserId()]);
} catch (PDOException $e) {
    error_log("Error updating last login: " . $e->getMessage());
}

