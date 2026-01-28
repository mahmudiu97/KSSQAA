<?php
/**
 * Setup script to create/update admin user
 * Run this once to set up the default admin account
 * DELETE THIS FILE AFTER SETUP FOR SECURITY
 */

require_once __DIR__ . '/../includes/db_connect.php';

// Default admin credentials
$username = 'admin';
$email = 'admin@sqms.kaduna.gov.ng';
$password = 'admin123'; // CHANGE THIS IN PRODUCTION!
$full_name = 'System Administrator';

try {
    $pdo = getDBConnection();
    
    // Generate password hash
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Check if admin user exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // Update existing admin
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, full_name = ?, is_active = 1 WHERE id = ?");
        $stmt->execute([$password_hash, $full_name, $existing['id']]);
        echo "✓ Admin user updated successfully!\n";
        echo "Username: $username\n";
        echo "Password: $password\n";
    } else {
        // Create new admin
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, full_name, role, is_active) VALUES (?, ?, ?, ?, 'SMO', 1)");
        $stmt->execute([$username, $email, $password_hash, $full_name]);
        echo "✓ Admin user created successfully!\n";
        echo "Username: $username\n";
        echo "Password: $password\n";
    }
    
    echo "\n⚠️  IMPORTANT: Delete this file (setup/create_admin.php) after setup for security!\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Make sure the database is created and the schema is imported.\n";
}

