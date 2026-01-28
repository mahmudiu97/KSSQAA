<?php
/**
 * School Registration Form
 */

require_once __DIR__ . '/../includes/functions.php';

startSession();

// Allow anyone to register (no auth required)
// If already logged in, redirect to appropriate dashboard
if (isLoggedIn()) {
    $role = getCurrentUserRole();
    redirect(strtolower($role) . '_dashboard.php');
    exit();
}

$error = '';
$success = '';
$pdo = getDBConnection();

// Get wards for dropdown (Kaduna North LGA)
$wards = [];
try {
    $stmt = $pdo->query("SELECT id, name FROM wards WHERE lga = 'Kaduna North' ORDER BY name");
    $wards = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching wards: " . $e->getMessage());
}

// All 23 Local Government Areas of Kaduna State
$kadunaLGAs = [
    'Birnin Gwari',
    'Chikun',
    'Giwa',
    'Igabi',
    'Ikara',
    'Jaba',
    'Jema\'a',
    'Kachia',
    'Kaduna North',
    'Kaduna South',
    'Kagarko',
    'Kajuru',
    'Kaura',
    'Kauru',
    'Kubau',
    'Kudan',
    'Lere',
    'Makarfi',
    'Sabon Gari',
    'Sanga',
    'Soba',
    'Zangon Kataf',
    'Zaria'
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $lga = sanitizeInput($_POST['lga'] ?? '');
    $school_type = sanitizeInput($_POST['school_type'] ?? '');
    $cac_number = sanitizeInput($_POST['cac_number'] ?? '');
    $tin_number = sanitizeInput($_POST['tin_number'] ?? '');
    $address = sanitizeInput($_POST['address'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $ward_id = isset($_POST['ward_id']) ? (int)$_POST['ward_id'] : null;
    
    // Handle file uploads
    $tax_clearance_file = null;
    $logo = null;
    
    if (isset($_FILES['tax_clearance_file']) && $_FILES['tax_clearance_file']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = handleFileUpload($_FILES['tax_clearance_file']);
        if (!$uploadResult['success']) {
            $error = $uploadResult['message'];
        } else {
            $tax_clearance_file = $uploadResult['filename'];
        }
    }
    
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = handleImageUpload($_FILES['logo'], 'uploads/logos');
        if (!$uploadResult['success']) {
            $error = $uploadResult['message'];
        } else {
            $logo = $uploadResult['filename'];
        }
    }
    
    // Validation - All fields are required
    if (empty($name)) {
        $error = 'School Name is required.';
    } elseif (empty($lga)) {
        $error = 'Local Government Area (LGA) is required.';
    } elseif (empty($school_type)) {
        $error = 'School Type is required. Please select whether the school is Private or Government.';
    } elseif (empty($ward_id)) {
        $error = 'Ward is required. Please select a ward.';
    } elseif (empty($cac_number)) {
        $error = 'CAC Registration Number is required.';
    } elseif (empty($tin_number)) {
        $error = 'TIN (Tax Identification Number) is required.';
    } else {
        // Validate CAC number format
        $cacValidation = validateCAC($cac_number);
        if (!$cacValidation['valid']) {
            $error = $cacValidation['message'];
        } else {
            $cac_number = $cacValidation['cleaned']; // Use cleaned version
            
            // Validate TIN number format
            $tinValidation = validateTIN($tin_number);
            if (!$tinValidation['valid']) {
                $error = $tinValidation['message'];
            } else {
                $tin_number = $tinValidation['cleaned']; // Use cleaned version
                
                // Continue with other validations
                if (empty($tax_clearance_file)) {
                    $error = 'Tax Clearance Certificate is required. Please upload the file.';
                } elseif (empty($address)) {
                    $error = 'Address is required.';
                } elseif (empty($phone)) {
                    $error = 'Phone Number is required.';
                } elseif (empty($email)) {
                    $error = 'Email Address is required.';
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error = 'Please enter a valid email address.';
                } else {
                    try {
                        // Check if CAC number already exists
                        $stmt = $pdo->prepare("SELECT id FROM schools WHERE cac_number = ?");
                        $stmt->execute([$cac_number]);
                        if ($stmt->fetch()) {
                            $error = 'A school with this CAC number already exists.';
                        }
                        
                        if (empty($error)) {
                            // Insert school with Pending status
                            $stmt = $pdo->prepare("INSERT INTO schools (name, lga, school_type, cac_number, tin_number, tax_clearance_file, logo, address, phone, email, ward_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')");
                            $stmt->execute([
                                $name, 
                                $lga, 
                                $school_type,
                                !empty($cac_number) ? $cac_number : null, 
                                !empty($tin_number) ? $tin_number : null, 
                                $tax_clearance_file, 
                                $logo,
                                !empty($address) ? $address : null, 
                                !empty($phone) ? $phone : null, 
                                !empty($email) ? $email : null, 
                                $ward_id
                            ]);
                            
                            $schoolId = $pdo->lastInsertId();
                            
                            // Send email notification if email is provided
                            if (!empty($email)) {
                                $subject = 'School Registration Received - Kaduna State SQMS';
                                $body = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #2563eb; color: white; padding: 20px; text-align: center; }
        .content { background-color: #f9fafb; padding: 30px; border: 1px solid #e5e7eb; }
        .info-box { background-color: #dbeafe; border-left: 4px solid #2563eb; padding: 15px; margin: 20px 0; }
        .footer { text-align: center; padding: 20px; color: #6b7280; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Kaduna State School Quality Management System</h1>
        </div>
        <div class="content">
            <p>Dear ' . htmlspecialchars($name) . ',</p>
            
            <p>Thank you for registering your school with the Kaduna State School Quality Management System (SQMS).</p>
            
            <div class="info-box">
                <strong>Application Status: PENDING REVIEW</strong>
            </div>
            
            <p>We have received your registration application and it is currently under review by our system administrators.</p>
            
            <p><strong>What happens next?</strong></p>
            <ul>
                <li>Our team will review your application and verify the information provided</li>
                <li>You will receive an email notification once your application has been reviewed</li>
                <li>If approved, you will receive login credentials for your School Administrator account</li>
                <li>You can then begin managing your school records in the system</li>
            </ul>
            
            <p><strong>Registration Details:</strong></p>
            <ul>
                <li>School Name: ' . htmlspecialchars($name) . '</li>
                <li>LGA: ' . htmlspecialchars($lga) . '</li>';
                                
                                if (!empty($cac_number)) {
                                    $body .= '<li>CAC Number: ' . htmlspecialchars($cac_number) . '</li>';
                                }
                                
                                if (!empty($tin_number)) {
                                    $body .= '<li>TIN Number: ' . htmlspecialchars($tin_number) . '</li>';
                                }
                                
                                if (!empty($school_type)) {
                                    $body .= '<li>School Type: ' . htmlspecialchars($school_type) . '</li>';
                                }
                                
                                $body .= '<li>Tax Clearance Certificate: ' . (!empty($tax_clearance_file) ? 'Uploaded' : 'Not provided') . '</li>';
                                $body .= '</ul>
            
            <p>Please keep this email for your records. If you have any questions or need to update your registration information, please contact the system administrator.</p>
            
            <p>We appreciate your interest in joining the Kaduna State SQMS.</p>
            
            <p>Best regards,<br>
            Kaduna State SQMS Administration</p>
        </div>
        <div class="footer">
            <p>This is an automated message. Please do not reply to this email.</p>
            <p>&copy; ' . date('Y') . ' Kaduna State Government. All rights reserved.</p>
        </div>
    </div>
</body>
</html>';
                                
                                $emailSent = sendEmail($email, $name, $subject, $body);
                                
                                if (!$emailSent) {
                                    error_log("Failed to send registration confirmation email to: " . $email);
                                }
                            }
                            
                            $success = 'School registered successfully! Your registration is pending approval from the system administrator.';
                            if (!empty($email)) {
                                $success .= ' A confirmation email has been sent to your registered email address.';
                            }
                            // Clear form
                            $_POST = [];
                        }
                    } catch (PDOException $e) {
                        error_log("Error registering school: " . $e->getMessage());
                        error_log("SQL Error Code: " . $e->getCode());
                        error_log("SQL Error Info: " . print_r($e->errorInfo, true));
                        
                        // Provide more specific error message
                        $errorInfo = $e->errorInfo;
                        $errorCode = $e->getCode();
                        
                        if (isset($errorInfo[1]) && $errorInfo[1] == 1054) {
                            // Unknown column error - database schema mismatch
                            $error = 'Database schema error: Missing columns (tin_number or tax_clearance_file). Please run the migration script: database/add_tin_tax_clearance.sql';
                        } elseif ($errorCode == '42S22' || (isset($errorInfo[1]) && $errorInfo[1] == 1054)) {
                            $error = 'Database schema error. Please run the migration script: database/add_tin_tax_clearance.sql to add the required columns.';
                        } elseif ($errorCode == '23000' || (isset($errorInfo[1]) && $errorInfo[1] == 1062)) {
                            $error = 'A school with this information already exists. Please check your CAC number or contact support.';
                        } elseif (isset($errorInfo[1]) && $errorInfo[1] == 1452) {
                            $error = 'Invalid ward selected. Please select a valid ward from the dropdown.';
                        } else {
                            $error = 'Database error: ' . htmlspecialchars($e->getMessage()) . '. Please check all required fields are filled and try again. If the problem persists, run: database/add_tin_tax_clearance.sql';
                        }
                    } catch (Exception $e) {
                        error_log("General error registering school: " . $e->getMessage());
                        $error = 'An unexpected error occurred. Please try again or contact support.';
                    }
                }
            }
        }
    }
}

// Get error/success from session
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register School - Kaduna State SQMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="max-w-4xl mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 md:p-8">
            <!-- Header -->
            <div class="mb-6 text-center">
                <img src="../logo.png" alt="Kaduna State SQMS Logo" class="h-16 w-auto mx-auto mb-4">
                <h1 class="text-2xl font-bold text-gray-900">School Registration</h1>
                <p class="mt-2 text-gray-600">Register your school to join the Kaduna State School Quality Management System</p>
            </div>

            <!-- Error/Success Messages -->
            <?php if ($error): ?>
                <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <!-- Registration Form -->
            <form method="POST" action="" enctype="multipart/form-data" id="schoolRegistrationForm" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- School Name -->
                    <div class="md:col-span-2">
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                            School Name <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="name" 
                            name="name" 
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                            placeholder="Enter school name"
                            value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                        >
                    </div>

                    <!-- LGA -->
                    <div>
                        <label for="lga" class="block text-sm font-medium text-gray-700 mb-2">
                            Local Government Area (LGA) <span class="text-red-500">*</span>
                        </label>
                        <select 
                            id="lga" 
                            name="lga" 
                            required
                            disabled
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm bg-gray-100 text-gray-700 cursor-not-allowed"
                        >
                            <?php foreach ($kadunaLGAs as $lgaOption): ?>
                                <option value="<?php echo htmlspecialchars($lgaOption); ?>" 
                                    <?php echo ($lgaOption === 'Kaduna North' || (isset($_POST['lga']) && $_POST['lga'] === $lgaOption)) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($lgaOption); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="lga" value="Kaduna North">
                        <p class="mt-1 text-xs text-gray-500">LGA is set to Kaduna North and cannot be changed.</p>
                    </div>

                    <!-- School Type -->
                    <div>
                        <label for="school_type" class="block text-sm font-medium text-gray-700 mb-2">
                            School Type <span class="text-red-500">*</span>
                        </label>
                        <select 
                            id="school_type" 
                            name="school_type"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                        >
                            <option value="">Select School Type</option>
                            <option value="Private" <?php echo (isset($_POST['school_type']) && $_POST['school_type'] === 'Private') ? 'selected' : ''; ?>>Private School</option>
                            <option value="Government" <?php echo (isset($_POST['school_type']) && $_POST['school_type'] === 'Government') ? 'selected' : ''; ?>>Government School</option>
                        </select>
                    </div>

                    <!-- Ward -->
                    <div>
                        <label for="ward_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Ward <span class="text-red-500">*</span>
                        </label>
                        <select 
                            id="ward_id" 
                            name="ward_id"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                        >
                            <option value="">Select Ward</option>
                            <?php foreach ($wards as $ward): ?>
                                <option value="<?php echo $ward['id']; ?>" <?php echo (isset($_POST['ward_id']) && $_POST['ward_id'] == $ward['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($ward['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- CAC Number -->
                    <div>
                        <label for="cac_number" class="block text-sm font-medium text-gray-700 mb-2">
                            CAC Registration Number <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="cac_number" 
                            name="cac_number"
                            required
                            pattern="^(BN|RC|IT)\d+$"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors uppercase"
                            placeholder="e.g., BN1234567, RC1234567, IT123456"
                            value="<?php echo isset($_POST['cac_number']) ? htmlspecialchars($_POST['cac_number']) : ''; ?>"
                            oninput="this.value = this.value.toUpperCase(); validateCAC(this);"
                        >
                        <p class="mt-1 text-xs text-gray-500">
                            Format: <span class="font-mono">BN</span> (Business Name), <span class="font-mono">RC</span> (Registered Company), or <span class="font-mono">IT</span> (Incorporated Trustee) followed by digits
                        </p>
                        <p id="cac_error" class="mt-1 text-xs text-red-600 hidden"></p>
                    </div>

                    <!-- TIN Number -->
                    <div>
                        <label for="tin_number" class="block text-sm font-medium text-gray-700 mb-2">
                            TIN (Tax Identification Number) <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="tin_number" 
                            name="tin_number"
                            required
                            pattern="^\d{12}$"
                            maxlength="12"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                            placeholder="Enter 12-digit TIN number"
                            value="<?php echo isset($_POST['tin_number']) ? htmlspecialchars($_POST['tin_number']) : ''; ?>"
                            oninput="this.value = this.value.replace(/\D/g, ''); validateTIN(this);"
                        >
                        <p class="mt-1 text-xs text-gray-500">
                            Must be exactly 12 digits (numeric only)
                        </p>
                        <p id="tin_error" class="mt-1 text-xs text-red-600 hidden"></p>
                    </div>

                    <!-- Tax Clearance Upload -->
                    <div class="md:col-span-2">
                        <label for="tax_clearance_file" class="block text-sm font-medium text-gray-700 mb-2">
                            Upload Latest Tax Clearance Certificate <span class="text-red-500">*</span>
                        </label>
                        <div class="mt-1 flex items-center">
                            <input 
                                type="file" 
                                id="tax_clearance_file" 
                                name="tax_clearance_file"
                                accept=".pdf,.jpg,.jpeg,.png"
                                required
                                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            >
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Accepted formats: PDF, JPEG, PNG (Max size: 5MB)</p>
                    </div>

                    <!-- Phone -->
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                            Phone Number <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="tel" 
                            id="phone" 
                            name="phone"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                            placeholder="Enter phone number"
                            value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                        >
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                            Email Address <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                            placeholder="Enter email address"
                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                        >
                    </div>

                    <!-- Address -->
                    <div class="md:col-span-2">
                        <label for="address" class="block text-sm font-medium text-gray-700 mb-2">
                            Address <span class="text-red-500">*</span>
                        </label>
                        <textarea 
                            id="address" 
                            name="address"
                            required
                            rows="3"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                            placeholder="Enter school address"
                        ><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200">
                    <a href="index.php" class="px-6 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        Cancel
                    </a>
                    <button 
                        type="submit"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors font-medium"
                    >
                        Register School
                    </button>
                </div>
            </form>
        </div>
    </div>
    <script>
        // Validate CAC number format
        function validateCAC(input) {
            const cac = input.value.trim().toUpperCase();
            const errorElement = document.getElementById('cac_error');
            
            if (cac === '') {
                errorElement.classList.add('hidden');
                input.classList.remove('border-red-500');
                input.classList.add('border-gray-300');
                return;
            }
            
            // Check format: BN, RC, or IT followed by digits
            const pattern = /^(BN|RC|IT)\d+$/;
            
            if (!pattern.test(cac)) {
                errorElement.textContent = 'Invalid format. Must start with BN, RC, or IT followed by digits.';
                errorElement.classList.remove('hidden');
                input.classList.remove('border-gray-300');
                input.classList.add('border-red-500');
            } else {
                errorElement.classList.add('hidden');
                input.classList.remove('border-red-500');
                input.classList.add('border-gray-300');
            }
        }
        
        // Validate TIN number format
        function validateTIN(input) {
            const tin = input.value.replace(/\D/g, ''); // Remove non-digits
            const errorElement = document.getElementById('tin_error');
            
            if (tin === '') {
                errorElement.classList.add('hidden');
                input.classList.remove('border-red-500');
                input.classList.add('border-gray-300');
                return;
            }
            
            // Check if exactly 12 digits
            if (tin.length !== 12) {
                errorElement.textContent = `TIN must be exactly 12 digits. Current: ${tin.length} digit(s).`;
                errorElement.classList.remove('hidden');
                input.classList.remove('border-gray-300');
                input.classList.add('border-red-500');
            } else {
                errorElement.classList.add('hidden');
                input.classList.remove('border-red-500');
                input.classList.add('border-gray-300');
            }
        }
        
        // Form submission validation
        document.getElementById('schoolRegistrationForm')?.addEventListener('submit', function(e) {
            const cacInput = document.getElementById('cac_number');
            const tinInput = document.getElementById('tin_number');
            
            const cac = cacInput.value.trim().toUpperCase();
            const tin = tinInput.value.replace(/\D/g, '');
            
            // Validate CAC
            const cacPattern = /^(BN|RC|IT)\d+$/;
            if (!cacPattern.test(cac)) {
                e.preventDefault();
                validateCAC(cacInput);
                cacInput.focus();
                return false;
            }
            
            // Validate TIN
            if (tin.length !== 12) {
                e.preventDefault();
                validateTIN(tinInput);
                tinInput.focus();
                return false;
            }
            
            return true;
        });
    </script>
</body>
</html>
