<?php
/**
 * Test Email Script
 * 
 * This script tests the email functionality to help debug email issues.
 * Access: http://localhost/KSSQAA/setup/test_email.php
 * 
 * WARNING: Delete this file after testing for security.
 */

require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';

// Test email configuration
$testEmail = 'test@example.com'; // Change this to your test email
$testName = 'Test User';
$testSubject = 'Test Email from Kaduna SQMS';
$testBody = '<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #2563eb; color: white; padding: 20px; text-align: center; }
        .content { background-color: #f9fafb; padding: 30px; border: 1px solid #e5e7eb; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Test Email</h1>
        </div>
        <div class="content">
            <p>This is a test email from the Kaduna State SQMS system.</p>
            <p>If you received this email, the email configuration is working correctly.</p>
            <p>Time sent: ' . date('Y-m-d H:i:s') . '</p>
        </div>
    </div>
</body>
</html>';

echo "<h2>Email Test</h2>";
echo "<pre>";

// Check if PHPMailer is available
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    echo "❌ ERROR: PHPMailer autoload not found at: $autoloadPath\n";
    exit;
}

require_once $autoloadPath;

if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    echo "❌ ERROR: PHPMailer class not found after autoload\n";
    exit;
}

echo "✓ PHPMailer loaded successfully\n";

// Check email configuration
require_once __DIR__ . '/../includes/email_config.php';

echo "\nEmail Configuration:\n";
echo "  SMTP Host: " . SMTP_HOST . "\n";
echo "  SMTP Port: " . SMTP_PORT . "\n";
echo "  SMTP Username: " . SMTP_USERNAME . "\n";
echo "  SMTP Password: " . (strlen(SMTP_PASSWORD) > 0 ? str_repeat('*', strlen(SMTP_PASSWORD)) : 'NOT SET') . "\n";
echo "  From Email: " . SMTP_FROM_EMAIL . "\n";
echo "  From Name: " . SMTP_FROM_NAME . "\n";

echo "\nAttempting to send test email to: $testEmail\n";
echo "----------------------------------------\n";

// Send test email
$result = sendEmail($testEmail, $testName, $testSubject, $testBody);

if ($result) {
    echo "\n✅ SUCCESS: Email sent successfully!\n";
    echo "Check your Mailtrap inbox at: https://mailtrap.io/inboxes\n";
} else {
    echo "\n❌ FAILED: Email could not be sent.\n";
    echo "Check the error logs for more details.\n";
    echo "\nCommon issues:\n";
    echo "1. Check SMTP credentials in includes/email_config.php\n";
    echo "2. Verify Mailtrap credentials are correct\n";
    echo "3. Check PHP error logs for detailed error messages\n";
    echo "4. Ensure port 2525 is not blocked by firewall\n";
}

echo "</pre>";
?>

