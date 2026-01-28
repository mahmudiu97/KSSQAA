<?php
/**
 * Add New Staff (SA Only) - AdminLTE
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
    $employee_number = sanitizeInput($_POST['employee_number'] ?? '');
    $employment_date = $_POST['employment_date'] ?? '';
    $position = sanitizeInput($_POST['position'] ?? '');
    $department = sanitizeInput($_POST['department'] ?? '');
    $qualification = sanitizeInput($_POST['qualification'] ?? '');
    $emergency_contact_name = sanitizeInput($_POST['emergency_contact_name'] ?? '');
    $emergency_contact_phone = sanitizeInput($_POST['emergency_contact_phone'] ?? '');
    $address = sanitizeInput($_POST['address'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $status = $_POST['status'] ?? 'Active';

    // Handle picture upload
    $picture = null;
    if (isset($_FILES['picture']) && $_FILES['picture']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = handleImageUpload($_FILES['picture'], 'uploads/staff');
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
    } else {
        try {
            // Generate unique staff ID
            $staff_id = generateStaffID($user['school_id']);

            // Insert staff
            $stmt = $pdo->prepare("INSERT INTO staff (school_id, staff_id, first_name, last_name, middle_name, date_of_birth, gender, employee_number, employment_date, position, department, qualification, phone, email, picture, address, emergency_contact_name, emergency_contact_phone, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $user['school_id'],
                $staff_id,
                $first_name,
                $last_name,
                $middle_name ?: null,
                $date_of_birth ?: null,
                $gender,
                $employee_number ?: null,
                $employment_date ?: null,
                $position ?: null,
                $department ?: null,
                $qualification ?: null,
                $phone ?: null,
                $email ?: null,
                $picture,
                $address ?: null,
                $emergency_contact_name ?: null,
                $emergency_contact_phone ?: null,
                $status
            ]);

            // Log the action
            log_action($user['id'], 'ADD_STAFF', "Added staff: {$staff_id} - {$first_name} {$last_name}");

            $success = "Staff added successfully! Staff ID: <strong>{$staff_id}</strong>";
            // Clear form
            $_POST = [];
        } catch (PDOException $e) {
            error_log("Error adding staff: " . $e->getMessage());
            $error = 'An error occurred while adding the staff. Please try again.';
        }
    }
}

// Menu items
// Get menu items based on role
$menuItems = getMenuItems('SA', $user['school_id']);

renderAdminLTEHead('Add Staff - Kaduna State SQMS');
?>

<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">
        <?php renderAdminLTESidebar($menuItems, $currentPage, $user, 'SA'); ?>

        <div class="content-wrapper">
            <?php renderAdminLTENavbar('Add Staff', $user); ?>

            <!-- Content Header -->
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">Add New Staff</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="sa_dashboard.php">Home</a></li>
                                <li class="breadcrumb-item"><a href="view_staff.php">Staff</a></li>
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
                                    <h3 class="card-title"><i class="fas fa-user-tie mr-2"></i>New Staff Registration
                                    </h3>
                                    <div class="card-tools">
                                        <a href="view_staff.php" class="btn btn-sm btn-secondary">
                                            <i class="fas fa-arrow-left"></i> Back to Staff
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

                                <form method="POST" action="" enctype="multipart/form-data" id="staffForm">
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
                                                    <label for="position">Position</label>
                                                    <input type="text" id="position" name="position"
                                                        class="form-control"
                                                        placeholder="e.g., Teacher, Principal, Secretary"
                                                        value="<?php echo isset($_POST['position']) ? htmlspecialchars($_POST['position']) : ''; ?>">
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="department">Department</label>
                                                    <input type="text" id="department" name="department"
                                                        class="form-control"
                                                        placeholder="e.g., Mathematics, Administration"
                                                        value="<?php echo isset($_POST['department']) ? htmlspecialchars($_POST['department']) : ''; ?>">
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="employee_number">Employee Number</label>
                                                    <input type="text" id="employee_number" name="employee_number"
                                                        class="form-control"
                                                        value="<?php echo isset($_POST['employee_number']) ? htmlspecialchars($_POST['employee_number']) : ''; ?>">
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="employment_date">Employment Date</label>
                                                    <input type="date" id="employment_date" name="employment_date"
                                                        class="form-control"
                                                        value="<?php echo isset($_POST['employment_date']) ? htmlspecialchars($_POST['employment_date']) : ''; ?>">
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="qualification">Qualification</label>
                                                    <input type="text" id="qualification" name="qualification"
                                                        class="form-control" placeholder="e.g., B.Ed, M.Sc, PhD"
                                                        value="<?php echo isset($_POST['qualification']) ? htmlspecialchars($_POST['qualification']) : ''; ?>">
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="phone">Staff Phone</label>
                                                    <input type="tel" id="phone" name="phone" class="form-control"
                                                        value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="email">Staff Email</label>
                                                    <input type="email" id="email" name="email" class="form-control"
                                                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="emergency_contact_name">Emergency Contact Name</label>
                                                    <input type="text" id="emergency_contact_name"
                                                        name="emergency_contact_name" class="form-control"
                                                        value="<?php echo isset($_POST['emergency_contact_name']) ? htmlspecialchars($_POST['emergency_contact_name']) : ''; ?>">
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="emergency_contact_phone">Emergency Contact Phone</label>
                                                    <input type="tel" id="emergency_contact_phone"
                                                        name="emergency_contact_phone" class="form-control"
                                                        value="<?php echo isset($_POST['emergency_contact_phone']) ? htmlspecialchars($_POST['emergency_contact_phone']) : ''; ?>">
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
                                                        <option value="Resigned" <?php echo (isset($_POST['status']) && $_POST['status'] === 'Resigned') ? 'selected' : ''; ?>>
                                                            Resigned</option>
                                                        <option value="Terminated" <?php echo (isset($_POST['status']) && $_POST['status'] === 'Terminated') ? 'selected' : ''; ?>>
                                                            Terminated</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="picture">Staff Picture</label>
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
                                            <i class="fas fa-save mr-2"></i>Add Staff
                                        </button>
                                        <a href="view_staff.php" class="btn btn-secondary">
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
        document.getElementById('staffForm').addEventListener('submit', function (e) {
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