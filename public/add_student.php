<?php
/**
 * Add New Student (SA Only) - AdminLTE
 */

$requiredRole = 'SA';
require_once __DIR__ . '/../includes/check_auth.php';
require_once __DIR__ . '/../includes/layout.php';

$user = getCurrentUser();
$pdo = getDBConnection();
$currentPage = basename($_SERVER['PHP_SELF']);

// Check if school is assigned
if (!$user['school_id']) {
    $_SESSION['error'] = 'No school has been assigned to your account. Please contact the system administrator.';
    redirect('sa_dashboard.php');
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
            // Generate unique student ID
            $student_id = generateStudentID($user['school_id']);

            // Insert student
            $stmt = $pdo->prepare("INSERT INTO students (school_id, student_id, first_name, last_name, middle_name, date_of_birth, gender, admission_number, admission_date, class_level, parent_guardian_name, parent_guardian_phone, parent_guardian_email, address, phone, email, picture, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $user['school_id'],
                $student_id,
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
                $status
            ]);

            // Log the action
            log_action($user['id'], 'ADD_STUDENT', "Added student: {$student_id} - {$first_name} {$last_name}");

            $success = "Student added successfully! Student ID: <strong>{$student_id}</strong>";
            // Clear form
            $_POST = [];
        } catch (PDOException $e) {
            error_log("Error adding student: " . $e->getMessage());
            $error = 'An error occurred while adding the student. Please try again.';
        }
    }
}

// Get menu items based on role
$menuItems = getMenuItems('SA', $user['school_id']);

renderAdminLTEHead('Add Student - Kaduna State SQMS');
?>

<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">
        <?php renderAdminLTESidebar($menuItems, $currentPage, $user, 'SA'); ?>

        <div class="content-wrapper">
            <?php renderAdminLTENavbar('Add Student', $user); ?>

            <!-- Content Header -->
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">Add New Student</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="sa_dashboard.php">Home</a></li>
                                <li class="breadcrumb-item"><a href="view_students.php">Students</a></li>
                                <li class="breadcrumb-item active">Add</li>
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
                                    <h3 class="card-title"><i class="fas fa-user-plus mr-2"></i>New Student Registration
                                    </h3>
                                    <div class="card-tools">
                                        <a href="view_students.php" class="btn btn-sm btn-secondary">
                                            <i class="fas fa-arrow-left"></i> Back to Students
                                        </a>
                                    </div>
                                </div>

                                <?php if ($error): ?>
                                    <div class="alert alert-danger alert-dismissible m-3">
                                        <button type="button" class="close" data-dismiss="alert"
                                            aria-hidden="true">&times;</button>
                                        <i class="icon fas fa-ban"></i> <?php echo htmlspecialchars($error); ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ($success): ?>
                                    <div class="alert alert-success alert-dismissible m-3">
                                        <button type="button" class="close" data-dismiss="alert"
                                            aria-hidden="true">&times;</button>
                                        <i class="icon fas fa-check"></i> <?php echo $success; ?>
                                    </div>
                                <?php endif; ?>

                                <form method="POST" action="" enctype="multipart/form-data" id="studentForm">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="first_name">First Name <span
                                                            class="text-danger">*</span></label>
                                                    <input type="text" id="first_name" name="first_name" required
                                                        class="form-control"
                                                        value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="last_name">Last Name <span
                                                            class="text-danger">*</span></label>
                                                    <input type="text" id="last_name" name="last_name" required
                                                        class="form-control"
                                                        value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="middle_name">Middle Name</label>
                                                    <input type="text" id="middle_name" name="middle_name"
                                                        class="form-control"
                                                        value="<?php echo isset($_POST['middle_name']) ? htmlspecialchars($_POST['middle_name']) : ''; ?>">
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="gender">Gender <span
                                                            class="text-danger">*</span></label>
                                                    <select id="gender" name="gender" required class="form-control">
                                                        <option value="">Select Gender</option>
                                                        <option value="Male" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Male') ? 'selected' : ''; ?>>Male
                                                        </option>
                                                        <option value="Female" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Female') ? 'selected' : ''; ?>>Female
                                                        </option>
                                                        <option value="Other" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Other') ? 'selected' : ''; ?>>Other
                                                        </option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="date_of_birth">Date of Birth</label>
                                                    <input type="date" id="date_of_birth" name="date_of_birth"
                                                        class="form-control"
                                                        value="<?php echo isset($_POST['date_of_birth']) ? htmlspecialchars($_POST['date_of_birth']) : ''; ?>">
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="class_level">Class Level</label>
                                                    <input type="text" id="class_level" name="class_level"
                                                        class="form-control" placeholder="e.g., JSS 1, SS 2"
                                                        value="<?php echo isset($_POST['class_level']) ? htmlspecialchars($_POST['class_level']) : ''; ?>">
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="admission_number">Admission Number</label>
                                                    <input type="text" id="admission_number" name="admission_number"
                                                        class="form-control"
                                                        value="<?php echo isset($_POST['admission_number']) ? htmlspecialchars($_POST['admission_number']) : ''; ?>">
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="admission_date">Admission Date</label>
                                                    <input type="date" id="admission_date" name="admission_date"
                                                        class="form-control"
                                                        value="<?php echo isset($_POST['admission_date']) ? htmlspecialchars($_POST['admission_date']) : ''; ?>">
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="parent_guardian_name">Parent/Guardian Name</label>
                                                    <input type="text" id="parent_guardian_name"
                                                        name="parent_guardian_name" class="form-control"
                                                        value="<?php echo isset($_POST['parent_guardian_name']) ? htmlspecialchars($_POST['parent_guardian_name']) : ''; ?>">
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="parent_guardian_phone">Parent/Guardian Phone</label>
                                                    <input type="tel" id="parent_guardian_phone"
                                                        name="parent_guardian_phone" class="form-control"
                                                        value="<?php echo isset($_POST['parent_guardian_phone']) ? htmlspecialchars($_POST['parent_guardian_phone']) : ''; ?>">
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="parent_guardian_email">Parent/Guardian Email</label>
                                                    <input type="email" id="parent_guardian_email"
                                                        name="parent_guardian_email" class="form-control"
                                                        value="<?php echo isset($_POST['parent_guardian_email']) ? htmlspecialchars($_POST['parent_guardian_email']) : ''; ?>">
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="phone">Student Phone</label>
                                                    <input type="tel" id="phone" name="phone" class="form-control"
                                                        value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="email">Student Email</label>
                                                    <input type="email" id="email" name="email" class="form-control"
                                                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="status">Status</label>
                                                    <select id="status" name="status" class="form-control">
                                                        <option value="Active" <?php echo (!isset($_POST['status']) || $_POST['status'] === 'Active') ? 'selected' : ''; ?>>Active
                                                        </option>
                                                        <option value="Inactive" <?php echo (isset($_POST['status']) && $_POST['status'] === 'Inactive') ? 'selected' : ''; ?>>
                                                            Inactive</option>
                                                        <option value="Graduated" <?php echo (isset($_POST['status']) && $_POST['status'] === 'Graduated') ? 'selected' : ''; ?>>
                                                            Graduated</option>
                                                        <option value="Transferred" <?php echo (isset($_POST['status']) && $_POST['status'] === 'Transferred') ? 'selected' : ''; ?>>
                                                            Transferred</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="picture">Student Picture</label>
                                                    <div class="custom-file">
                                                        <input type="file" class="custom-file-input" id="picture"
                                                            name="picture" accept=".jpg,.jpeg,.png,.gif">
                                                        <label class="custom-file-label" for="picture">Choose file
                                                            (JPEG, PNG, GIF - Max 5MB)</label>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="address">Address</label>
                                                    <textarea id="address" name="address" rows="3"
                                                        class="form-control"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="card-footer">
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-save mr-2"></i>Add Student
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
        $('.custom-file-input').on('change', function () {
            let fileName = $(this).val().split('\\').pop();
            $(this).siblings('.custom-file-label').addClass('selected').html(fileName);
        });

        // Form validation
        document.getElementById('studentForm').addEventListener('submit', function (e) {
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