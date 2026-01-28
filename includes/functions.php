<?php
/**
 * Reusable Functions
 */

require_once __DIR__ . '/db_connect.php';

/**
 * Start session if not already started
 */
function startSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Sanitize user input
 * @param string $data
 * @return string
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Validate Nigerian TIN (Tax Identification Number)
 * Must be exactly 12 digits, numeric only
 * @param string $tin
 * @return array ['valid' => bool, 'message' => string]
 */
function validateTIN($tin) {
    $tin = trim($tin);
    
    if (empty($tin)) {
        return ['valid' => false, 'message' => 'TIN number is required.'];
    }
    
    // Remove any spaces or dashes
    $tin = preg_replace('/[\s\-]/', '', $tin);
    
    // Check if it's exactly 12 digits
    if (!preg_match('/^\d{12}$/', $tin)) {
        return ['valid' => false, 'message' => 'TIN number must be exactly 12 digits (numeric only).'];
    }
    
    return ['valid' => true, 'message' => '', 'cleaned' => $tin];
}

/**
 * Validate Nigerian CAC (Corporate Affairs Commission) Number
 * Format: BN + digits (Business Name), RC + digits (Registered Company), or IT + digits (Incorporated Trustee)
 * @param string $cac
 * @return array ['valid' => bool, 'message' => string, 'type' => string]
 */
function validateCAC($cac) {
    $cac = trim($cac);
    
    if (empty($cac)) {
        return ['valid' => false, 'message' => 'CAC number is required.'];
    }
    
    // Remove any spaces or dashes
    $cac = preg_replace('/[\s\-]/', '', $cac);
    
    // Convert to uppercase for consistency
    $cac = strtoupper($cac);
    
    // Check for Business Name format: BN + digits
    if (preg_match('/^BN\d+$/', $cac)) {
        return ['valid' => true, 'message' => '', 'cleaned' => $cac, 'type' => 'Business Name'];
    }
    
    // Check for Registered Company format: RC + digits
    if (preg_match('/^RC\d+$/', $cac)) {
        return ['valid' => true, 'message' => '', 'cleaned' => $cac, 'type' => 'Registered Company'];
    }
    
    // Check for Incorporated Trustee format: IT + digits
    if (preg_match('/^IT\d+$/', $cac)) {
        return ['valid' => true, 'message' => '', 'cleaned' => $cac, 'type' => 'Incorporated Trustee'];
    }
    
    return ['valid' => false, 'message' => 'Invalid CAC format. Must start with BN (Business Name), RC (Registered Company), or IT (Incorporated Trustee) followed by digits. Examples: BN1234567, RC1234567, IT123456'];
}

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn() {
    startSession();
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

/**
 * Get current user ID
 * @return int|null
 */
function getCurrentUserId() {
    startSession();
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user role
 * @return string|null
 */
function getCurrentUserRole() {
    startSession();
    return $_SESSION['role'] ?? null;
}

/**
 * Get current user data
 * @return array|null
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT id, username, email, full_name, role, school_id, ward_id FROM users WHERE id = ? AND is_active = 1");
        $stmt->execute([getCurrentUserId()]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error fetching user data: " . $e->getMessage());
        return null;
    }
}

/**
 * Redirect to a page
 * @param string $url
 */
function redirect($url) {
    header("Location: " . $url);
    exit();
}

/**
 * Check if user has required role
 * @param string|array $requiredRoles
 * @return bool
 */
function hasRole($requiredRoles) {
    $userRole = getCurrentUserRole();
    if (is_array($requiredRoles)) {
        return in_array($userRole, $requiredRoles);
    }
    return $userRole === $requiredRoles;
}

/**
 * Generate a random password
 * @param int $length Password length (default 12)
 * @return string
 */
function generateRandomPassword($length = 12) {
    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    $password = '';
    $max = strlen($characters) - 1;
    for ($i = 0; $i < $length; $i++) {
        $password .= $characters[random_int(0, $max)];
    }
    return $password;
}

/**
 * Verify password
 * @param string $password
 * @param string $hash
 * @return bool
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Hash password
 * @param string $password
 * @return string
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Generate unique student ID
 * Format: STU-YYYY-SCHOOLID-XXXX (e.g., STU-2024-001-0001)
 * @param int $school_id
 * @return string
 */
function generateStudentID($school_id) {
    $pdo = getDBConnection();
    $year = date('Y');
    $school_prefix = str_pad($school_id, 3, '0', STR_PAD_LEFT);
    
    // Get the last student number for this school this year
    $stmt = $pdo->prepare("SELECT student_id FROM students WHERE school_id = ? AND student_id LIKE ? ORDER BY student_id DESC LIMIT 1");
    $pattern = "STU-{$year}-{$school_prefix}-%";
    $stmt->execute([$school_id, $pattern]);
    $last = $stmt->fetch();
    
    if ($last) {
        // Extract the number part and increment
        $parts = explode('-', $last['student_id']);
        $last_num = (int)end($parts);
        $next_num = $last_num + 1;
    } else {
        $next_num = 1;
    }
    
    $student_num = str_pad($next_num, 4, '0', STR_PAD_LEFT);
    return "STU-{$year}-{$school_prefix}-{$student_num}";
}

/**
 * Generate unique staff ID
 * Format: STF-YYYY-SCHOOLID-XXXX (e.g., STF-2024-001-0001)
 * @param int $school_id
 * @return string
 */
function generateStaffID($school_id) {
    $pdo = getDBConnection();
    $year = date('Y');
    $school_prefix = str_pad($school_id, 3, '0', STR_PAD_LEFT);
    
    // Get the last staff number for this school this year
    $stmt = $pdo->prepare("SELECT staff_id FROM staff WHERE school_id = ? AND staff_id LIKE ? ORDER BY staff_id DESC LIMIT 1");
    $pattern = "STF-{$year}-{$school_prefix}-%";
    $stmt->execute([$school_id, $pattern]);
    $last = $stmt->fetch();
    
    if ($last) {
        // Extract the number part and increment
        $parts = explode('-', $last['staff_id']);
        $last_num = (int)end($parts);
        $next_num = $last_num + 1;
    } else {
        $next_num = 1;
    }
    
    $staff_num = str_pad($next_num, 4, '0', STR_PAD_LEFT);
    return "STF-{$year}-{$school_prefix}-{$staff_num}";
}

/**
 * Log an action to the audit log
 * @param int|null $user_id
 * @param string $action
 * @param string|null $description
 * @return bool
 */
function log_action($user_id, $action, $description = null) {
    try {
        $pdo = getDBConnection();
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $action, $description, $ip_address, $user_agent]);
        return true;
    } catch (PDOException $e) {
        error_log("Error logging action: " . $e->getMessage());
        return false;
    }
}

/**
 * Send email using PHPMailer
 * @param string $to Email address
 * @param string $toName Recipient name
 * @param string $subject Email subject
 * @param string $body Email body (HTML)
 * @return bool
 */
function sendEmail($to, $toName, $subject, $body) {
    // Load email configuration
    require_once __DIR__ . '/email_config.php';
    
    // Load PHPMailer via Composer autoload
    $autoloadPath = __DIR__ . '/../vendor/autoload.php';
    if (!file_exists($autoloadPath)) {
        error_log("PHPMailer autoload not found at: " . $autoloadPath);
        return false;
    }
    
    require_once $autoloadPath;
    
    // Check if PHPMailer is available
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        error_log("PHPMailer class not found after autoload");
        return false;
    }
    
    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->Port = SMTP_PORT;
        $mail->CharSet = 'UTF-8';
        
        // Enable TLS encryption for Mailtrap (port 2525 uses STARTTLS)
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        
        // Enable verbose debug output (set to 0 for production)
        $mail->SMTPDebug = 0;
        $mail->Debugoutput = function($str, $level) {
            error_log("PHPMailer Debug (Level $level): $str");
        };
        
        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to, $toName);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->AltBody = strip_tags($body);
        
        // Send email
        $result = $mail->send();
        
        if ($result) {
            error_log("Email sent successfully to: $to");
            return true;
        } else {
            error_log("Email send returned false for: $to");
            return false;
        }
        
    } catch (PHPMailer\PHPMailer\Exception $e) {
        error_log("PHPMailer Exception: " . $e->getMessage());
        error_log("PHPMailer ErrorInfo: " . $mail->ErrorInfo);
        return false;
    } catch (Exception $e) {
        error_log("General Exception in sendEmail: " . $e->getMessage());
        return false;
    }
}

/**
 * Handle file upload with security validation
 * @param array $file $_FILES array element
 * @param string $uploadDir Directory to upload to (relative to project root)
 * @param array $allowedTypes Allowed MIME types
 * @param int $maxSize Maximum file size in bytes (default 5MB)
 * @return array ['success' => bool, 'message' => string, 'filename' => string|null]
 */
function handleFileUpload($file, $uploadDir = 'uploads/tax_clearance', $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'], $maxSize = 5242880) {
    // Check if file was uploaded
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
            $maxSizeMB = round($maxSize / 1048576, 1);
            return ['success' => false, 'message' => "File size exceeds maximum allowed size ({$maxSizeMB}MB).", 'filename' => null];
        }
        return ['success' => false, 'message' => 'File upload failed. Please try again.', 'filename' => null];
    }
    
    // Validate file size
    if ($file['size'] > $maxSize) {
        $maxSizeMB = round($maxSize / 1048576, 1);
        return ['success' => false, 'message' => "File size exceeds maximum allowed size ({$maxSizeMB}MB).", 'filename' => null];
    }
    
    // Validate file type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid file type. Please check allowed file types.', 'filename' => null];
    }
    
    // Validate file extension based on allowed types
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExtensions = [];
    
    // Map MIME types to extensions
    if (in_array('application/pdf', $allowedTypes)) {
        $allowedExtensions[] = 'pdf';
    }
    if (in_array('image/jpeg', $allowedTypes) || in_array('image/jpg', $allowedTypes)) {
        $allowedExtensions[] = 'jpg';
        $allowedExtensions[] = 'jpeg';
    }
    if (in_array('image/png', $allowedTypes)) {
        $allowedExtensions[] = 'png';
    }
    if (in_array('image/gif', $allowedTypes)) {
        $allowedExtensions[] = 'gif';
    }
    if (in_array('application/msword', $allowedTypes)) {
        $allowedExtensions[] = 'doc';
    }
    if (in_array('application/vnd.openxmlformats-officedocument.wordprocessingml.document', $allowedTypes)) {
        $allowedExtensions[] = 'docx';
    }
    if (in_array('text/plain', $allowedTypes)) {
        $allowedExtensions[] = 'txt';
    }
    
    if (!in_array($extension, $allowedExtensions)) {
        return ['success' => false, 'message' => 'Invalid file extension. Please check allowed file types.', 'filename' => null];
    }
    
    // Create upload directory if it doesn't exist
    $fullUploadDir = __DIR__ . '/../' . $uploadDir;
    if (!file_exists($fullUploadDir)) {
        if (!mkdir($fullUploadDir, 0755, true)) {
            return ['success' => false, 'message' => 'Failed to create upload directory.', 'filename' => null];
        }
    }
    
    // Generate unique filename based on upload directory
    $prefix = 'file_';
    if (strpos($uploadDir, 'curriculum') !== false) {
        $prefix = 'curriculum_';
    } elseif (strpos($uploadDir, 'tax_clearance') !== false) {
        $prefix = 'tax_clearance_';
    } elseif (strpos($uploadDir, 'logo') !== false) {
        $prefix = 'logo_';
    }
    
    $filename = uniqid($prefix, true) . '_' . time() . '.' . $extension;
    $filepath = $fullUploadDir . '/' . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => false, 'message' => 'Failed to save uploaded file.', 'filename' => null];
    }
    
    // Return relative path for database storage
    $relativePath = $uploadDir . '/' . $filename;
    return ['success' => true, 'message' => 'File uploaded successfully.', 'filename' => $relativePath];
}

/**
 * Handle image upload (for logos, pictures, etc.)
 * @param array $file $_FILES array element
 * @param string $uploadDir Directory to upload to (relative to project root)
 * @param int $maxSize Maximum file size in bytes (default 2MB)
 * @return array ['success' => bool, 'message' => string, 'filename' => string|null]
 */
function handleImageUpload($file, $uploadDir = 'uploads/images', $maxSize = 2097152) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
    return handleFileUpload($file, $uploadDir, $allowedTypes, $maxSize);
}

