<?php
/**
 * Download Curriculum File - Protected Access
 */

// Check authentication (allow both SMO and SA)
session_start();
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.0 401 Unauthorized');
    die('Unauthorized access.');
}

require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = getDBConnection();
$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'] ?? '';
$userSchoolId = $_SESSION['school_id'] ?? null;

$user = getCurrentUser();
$pdo = getDBConnection();

$fileId = $_GET['id'] ?? 0;

if (empty($fileId)) {
    header('HTTP/1.0 400 Bad Request');
    die('File ID is required.');
}

try {
    // Get file information
    $stmt = $pdo->prepare("SELECT sc.*, s.school_id 
                           FROM subject_curriculum sc
                           INNER JOIN subjects s ON sc.subject_id = s.id
                           WHERE sc.id = ?");
    $stmt->execute([$fileId]);
    $file = $stmt->fetch();
    
    if (!$file) {
        header('HTTP/1.0 404 Not Found');
        die('File not found.');
    }
    
    // Check access permissions
    // SMO can access all files, SA can only access files from their school
    if ($userRole === 'SA' && $userSchoolId != $file['school_id']) {
        header('HTTP/1.0 403 Forbidden');
        die('Access denied.');
    }
    
    $filePath = __DIR__ . '/../' . $file['file_path'];
    
    if (!file_exists($filePath)) {
        header('HTTP/1.0 404 Not Found');
        die('File not found on server.');
    }
    
    // Log download
    log_action($userId, 'DOWNLOAD_CURRICULUM', "Downloaded curriculum file: {$file['file_name']}");
    
    // Set headers for file download
    header('Content-Type: ' . ($file['file_type'] ?? 'application/octet-stream'));
    header('Content-Disposition: attachment; filename="' . basename($file['file_name']) . '"');
    header('Content-Length: ' . filesize($filePath));
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    
    // Output file
    readfile($filePath);
    exit;
    
} catch (PDOException $e) {
    error_log("Error downloading curriculum file: " . $e->getMessage());
    header('HTTP/1.0 500 Internal Server Error');
    die('Error downloading file.');
}

