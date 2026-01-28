<?php
/**
 * Index page - redirects to login or dashboard based on auth status
 */

require_once __DIR__ . '/../includes/functions.php';

startSession();

if (isLoggedIn()) {
    $role = getCurrentUserRole();
    header("Location: " . strtolower($role) . "_dashboard.php");
} else {
    header("Location: login.php");
}
exit();

