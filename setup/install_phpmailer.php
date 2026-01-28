<?php
/**
 * PHPMailer Installation Helper
 * 
 * This script helps install PHPMailer if Composer is not available.
 * 
 * Option 1: Use Composer (Recommended)
 *   Run: composer require phpmailer/phpmailer
 * 
 * Option 2: Manual Installation
 *   Download PHPMailer from: https://github.com/PHPMailer/PHPMailer
 *   Extract to: vendor/phpmailer/phpmailer/
 */

echo "<h2>PHPMailer Installation</h2>";
echo "<pre>";

// Check if Composer is available
$composerPath = __DIR__ . '/../composer.json';
if (file_exists($composerPath)) {
    echo "✓ composer.json found\n";
    echo "\n";
    echo "To install PHPMailer, run the following command in your project root:\n";
    echo "  composer require phpmailer/phpmailer\n";
    echo "\n";
    echo "Or if you don't have Composer installed:\n";
    echo "1. Download Composer from: https://getcomposer.org/download/\n";
    echo "2. Run: php composer.phar require phpmailer/phpmailer\n";
} else {
    echo "composer.json not found. Creating it...\n";
}

// Check if PHPMailer is already installed
$phpmailerPaths = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php',
];

$found = false;
foreach ($phpmailerPaths as $path) {
    if (file_exists($path)) {
        echo "✓ PHPMailer found at: " . $path . "\n";
        $found = true;
        break;
    }
}

if (!$found) {
    echo "\n";
    echo "⚠️  PHPMailer not found.\n";
    echo "\n";
    echo "Installation Options:\n";
    echo "1. Using Composer (Recommended):\n";
    echo "   cd " . dirname(__DIR__) . "\n";
    echo "   composer require phpmailer/phpmailer\n";
    echo "\n";
    echo "2. Manual Installation:\n";
    echo "   - Download from: https://github.com/PHPMailer/PHPMailer/releases\n";
    echo "   - Extract to: vendor/phpmailer/phpmailer/\n";
    echo "   - Ensure vendor/autoload.php exists or include manually\n";
}

echo "</pre>";
?>

