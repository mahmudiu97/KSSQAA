<?php
/**
 * Edit Student (SA Only) - AdminLTE
 */

$requiredRole = 'SA';
require_once __DIR__ . '/../includes/check_auth.php';
require_once __DIR__ . '/../includes/layout.php';

$user = getCurrentUser();
$pdo = getDBConnection();
$currentPage = basename($_SERVER['PHP_SELF']);

// Get student ID
$student_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($student_id <= 0) {
    $_SESSION['error'] = 'Invalid student ID.';
    redirect('view_students.php');
    exit();
}

// Get student data
$student = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ? AND school_id = ?");
    $stmt->execute([$student_id, $user['school_id']]);
    $student = $stmt->fetch();
} catch (PDOException $e) {
    error_log("Error fetching student: " . $e->getMessage());
}

if (!$student) {
    $_SESSION['error'] = 'Student not found or you do not have permission to edit this student.';
    redirect('view_students.php');
    exit();
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = sanitizeInput($_POST['first_name'] ?? '');
    $last_name = sanitizeInput($_POST['last_name'] ?? '');
    $middle_name = sanitizeInput($_POST['middle_name'] ?? '');
    $date_of_birth = $_POST['date_of_birth'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $admission_number = sanitizeInput($_POST['admission_number'] ?? '');
    $admission_date = $_POST['admission_date'] ?? '';
    $class_level = sanitizeInput($_POST['class_level'] ?? '');
    $parent_guardian_name = sanitizeInput($_POST['parent_guardian_name'] ?? '');
    $parent_guardian_phone = sanitizeInput($_POST['parent_guardian_phone'] ?? '');
    $parent_guardian_email = sanitizeInput($_POST['parent_guardian_email'] ?? '');
    $address = sanitizeInput($_POST['address'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $status = $_POST['status'] ?? 'Active';
    
    // Handle picture upload
    $picture = null;
    if (isset($_FILES['picture']) && $_FILES['picture']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = handleImageUpload($_FILES['picture'], 'uploads/students');
        if (!$uploadResult['success']) {
            $error = $uploadResult['message'];
        } else {
            $picture = $uploadResult['filename'];
        }
    }
    
    // Validation
    if (empty($first_name) || empty($last_name) || empty($gender)) {
        $error = 'First Name, Last Name, and Gender are required fields.';
    } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!empty($parent_guardian_email) && !filter_var($parent_guardian_email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid parent/guardian email address.';
    } else {
        try {
            // Update query - include picture if uploaded
            if ($picture) {
                $stmt = $pdo->prepare("UPDATE students SET first_name = ?, last_name = ?, middle_name = ?, date_of_birth = ?, gender = ?, admission_number = ?, admission_date = ?, class_level = ?, parent_guardian_name = ?, parent_guardian_phone = ?, parent_guardian_email = ?, address = ?, phone = ?, email = ?, picture = ?, status = ?, updated_at = NOW() WHERE id = ? AND school_id = ?");
                $stmt->execute([
                    $first_name,
                    $last_name,
                    $middle_name ?: null,
                    $date_of_birth ?: null,
                    $gender,
                    $admission_number ?: null,
                    $admission_date ?: null,
                    $class_level ?: null,
                    $parent_guardian_name ?: null,
                    $parent_guardian_phone ?: null,
                    $parent_guardian_email ?: null,
                    $address ?: null,
                    $phone ?: null,
                    $email ?: null,
                    $picture,
                    $status,
                    $student_id,
                    $user['school_id']
                ]);
            } else {
                $stmt = $pdo->prepare("UPDATE students SET first_name = ?, last_name = ?, middle_name = ?, date_of_birth = ?, gender = ?, admission_number = ?, admission_date = ?, class_level = ?, parent_guardian_name = ?, parent_guardian_phone = ?, parent_guardian_email = ?, address = ?, phone = ?, email = ?, status = ?, updated_at = NOW() WHERE id = ? AND school_id = ?");
                $stmt->execute([
                    $first_name,
                    $last_name,
                    $middle_name ?: null,
                    $date_of_birth ?: null,
                    $gender,
                    $admission_number ?: null,
                    $admission_date ?: null,
                    $class_level ?: null,
                    $parent_guardian_name ?: null,
                    $parent_guardian_phone ?: null,
                    $parent_guardian_email ?: null,
                    $address ?: null,
                    $phone ?: null,
                    $email ?: null,
                    $status,
                    $student_id,
                    $user['school_id']
                ]);
            }
            
            // Log the action
            log_action($user['id'], 'UPDATE_STUDENT', "Updated student: {$student['student_id']} - {$first_name} {$last_name}");
            
            $success = 'Student updated successfully!';
            // Refresh student data
            $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ? AND school_id = ?");
            $stmt->execute([$student_id, $user['school_id']]);
            $student = $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error updating student: " . $e->getMessage());
            $error = 'An error occurred while updating the student. Please try again.';
        }
    }
}

// Menu items
// Get menu items based on role
$menuItems = getMenuItems('SA', $user['school_id']);

renderAdminLTEHead('Edit Student - Kaduna State SQMS');
?>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
    <?php renderAdminLTESidebar($menuItems, $currentPage, $user, 'SA'); ?>
    
    <div class="content-wrapper">
        <?php renderAdminLTENavbar('Edit Student', $user); ?>
        
        <!-- Content Header -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Edit Student</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="sa_dashboard.php">Home</a></li>
                            <li class="breadcrumb-item"><a href="view_students.php">Students</a></li>
                            <li class="breadcrumb-item active">Edit</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-10 offset-md-1">
                        <div class="card card-success">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-user-edit mr-2"></i>Edit Student Information</h3>
                                <div class="card-tools">
                                    <span class="badge badge-info">ID: <?php echo htmlspecialchars($student['student_id']); ?></span>
                                </div>
                            </div>
                            
                            <?php if ($error): ?>
                                <div class="alert alert-danger alert-dismissible m-3">
                                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                                    <i class="icon fas fa-ban"></i> <?php echo htmlspecialchars($error); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($success): ?>
                                <div class="alert alert-success alert-dismissible m-3">
                                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                                    <i class="icon fas fa-check"></i> <?php echo htmlspecialchars($success); ?>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" action="" enctype="multipart/form-data" id="studentForm">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="first_name">First Name <span class="text-danger">*</span></label>
                                                <input type="text" id="first_name" name="first_name" required
                                                       class="form-control"
                                                       value="<?php echo htmlspecialchars($student['first_name']); ?>">
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="last_name">Last Name <span class="text-danger">*</span></label>
                                                <input type="text" id="last_name" name="last_name" required
                                                       class="form-control"
                                                       value="<?php echo htmlspecialchars($student['last_name']); ?>">
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="middle_name">Middle Name</label>
                                                <input type="text" id="middle_name" name="middle_name"
                                                       class="form-control"
                                                       value="<?php echo htmlspecialchars($student['middle_name'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="gender">Gender <span class="text-danger">*</span></label>
                                                <select id="gender" name="gender" required class="form-control">
                                                    <option value="Male" <?php echo $student['gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                                                    <option value="Female" <?php echo $student['gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                                                    <option value="Other" <?php echo $student['gender'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="date_of_birth">Date of Birth</label>
                                                <input type="date" id="date_of_birth" name="date_of_birth"
                                                       class="form-control"
                                                       value="<?php echo $student['date_of_birth'] ? date('Y-m-d', strtotime($student['date_of_birth'])) : ''; ?>">
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="class_level">Class Level</label>
                                                <input type="text" id="class_level" name="class_level"
                                                       class="form-control"
                                                       value="<?php echo htmlspecialchars($student['class_level'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="admission_number">Admission Number</label>
                                                <input type="text" id="admission_number" name="admission_number"
                                                       class="form-control"
                                                       value="<?php echo htmlspecialchars($student['admission_number'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="admission_date">Admission Date</label>
                                                <input type="date" id="admission_date" name="admission_date"
                                                       class="form-control"
                                                       value="<?php echo $student['admission_date'] ? date('Y-m-d', strtotime($student['admission_date'])) : ''; ?>">
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="parent_guardian_name">Parent/Guardian Name</label>
                                                <input type="text" id="parent_guardian_name" name="parent_guardian_name"
                                                       class="form-control"
                                                       value="<?php echo htmlspecialchars($student['parent_guardian_name'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="parent_guardian_phone">Parent/Guardian Phone</label>
                                                <input type="tel" id="parent_guardian_phone" name="parent_guardian_phone"
                                                       class="form-control"
                                                       value="<?php echo htmlspecialchars($student['parent_guardian_phone'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="parent_guardian_email">Parent/Guardian Email</label>
                                                <input type="email" id="parent_guardian_email" name="parent_guardian_email"
                                                       class="form-control"
                                                       value="<?php echo htmlspecialchars($student['parent_guardian_email'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="phone">Student Phone</label>
                                                <input type="tel" id="phone" name="phone"
                                                       class="form-control"
                                                       value="<?php echo htmlspecialchars($student['phone'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="email">Student Email</label>
                                                <input type="email" id="email" name="email"
                                                       class="form-control"
                                                       value="<?php echo htmlspecialchars($student['email'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="status">Status</label>
                                                <select id="status" name="status" class="form-control">
                                                    <option value="Active" <?php echo $student['status'] === 'Active' ? 'selected' : ''; ?>>Active</option>
                                                    <option value="Inactive" <?php echo $student['status'] === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                                                    <option value="Graduated" <?php echo $student['status'] === 'Graduated' ? 'selected' : ''; ?>>Graduated</option>
                                                    <option value="Transferred" <?php echo $student['status'] === 'Transferred' ? 'selected' : ''; ?>>Transferred</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="picture">Student Picture</label>
                                                <?php if (!empty($student['picture'])): ?>
                                                    <?php 
                                                    $picturePath = $student['picture'];
                                                    if (strpos($picturePath, 'uploads/') === 0) {
                                                        $picturePath = '../' . $picturePath;
                                                    }
                                                    ?>
                                                    <div class="mb-2">
                                                        <img src="<?php echo htmlspecialchars($picturePath); ?>" 
                                                             alt="Current Picture" 
                                                             class="img-thumbnail" 
                                                             style="max-width: 150px; max-height: 150px; object-fit: cover;"
                                                             onerror="this.style.display='none';">
                                                        <p class="text-muted small mt-1">Current picture</p>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="custom-file">
                                                    <input type="file" class="custom-file-input" id="picture" name="picture" accept=".jpg,.jpeg,.png,.gif">
                                                    <label class="custom-file-label" for="picture">Choose new file (leave empty to keep current)</label>
                                                </div>
                                                <small class="form-text text-muted">Accepted formats: JPEG, PNG, GIF (Max 5MB)</small>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="address">Address</label>
                                                <textarea id="address" name="address" rows="3" class="form-control"><?php echo htmlspecialchars($student['address'] ?? ''); ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="card-footer">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-save mr-2"></i>Update Student
                                    </button>
                                    <a href="view_students.php" class="btn btn-secondary">
                                        <i class="fas fa-times mr-2"></i>Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
    
    <?php renderAdminLTEFooter(); ?>
</div>

<?php renderAdminLTEScripts(); ?>

<script>
// Update custom file input label
$('.custom-file-input').on('change', function() {
    let fileName = $(this).val().split('\\').pop();
    $(this).siblings('.custom-file-label').addClass('selected').html(fileName);
});

// Form validation
document.getElementById('studentForm').addEventListener('submit', function(e) {
    const firstName = document.getElementById('first_name').value.trim();
    const lastName = document.getElementById('last_name').value.trim();
    const gender = document.getElementById('gender').value;
    
    if (!firstName || !lastName || !gender) {
        e.preventDefault();
        alert('Please fill in all required fields (First Name, Last Name, and Gender).');
        return false;
    }
});
</script>
