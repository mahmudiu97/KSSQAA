<?php
/**
 * Logout Script
 */

require_once __DIR__ . '/../includes/functions.php';

startSession();

// Log logout if user was logged in
if (isset($_SESSION['user_id'])) {
    log_action($_SESSION['user_id'], 'LOGOUT', "User logged out: " . ($_SESSION['username'] ?? 'Unknown'));
}

// Destroy all session data
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Redirect to login
redirect('login.php');
exit();

