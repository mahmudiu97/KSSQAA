<?php
/**
 * Edit Staff (SA Only) - AdminLTE
 */

$requiredRole = 'SA';
require_once __DIR__ . '/../includes/check_auth.php';
require_once __DIR__ . '/../includes/layout.php';

$user = getCurrentUser();
$pdo = getDBConnection();
$currentPage = basename($_SERVER['PHP_SELF']);

// Get staff ID
$staff_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($staff_id <= 0) {
    $_SESSION['error'] = 'Invalid staff ID.';
    redirect('view_staff.php');
    exit();
}

// Get staff data
$staff = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM staff WHERE id = ? AND school_id = ?");
    $stmt->execute([$staff_id, $user['school_id']]);
    $staff = $stmt->fetch();
} catch (PDOException $e) {
    error_log("Error fetching staff: " . $e->getMessage());
}

if (!$staff) {
    $_SESSION['error'] = 'Staff member not found or you do not have permission to edit this staff member.';
    redirect('view_staff.php');
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
            // Update query - include picture if uploaded
            if ($picture) {
                $stmt = $pdo->prepare("UPDATE staff SET first_name = ?, last_name = ?, middle_name = ?, date_of_birth = ?, gender = ?, employee_number = ?, employment_date = ?, position = ?, department = ?, qualification = ?, phone = ?, email = ?, picture = ?, address = ?, emergency_contact_name = ?, emergency_contact_phone = ?, status = ?, updated_at = NOW() WHERE id = ? AND school_id = ?");
                $stmt->execute([
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
                    $status,
                    $staff_id,
                    $user['school_id']
                ]);
            } else {
                $stmt = $pdo->prepare("UPDATE staff SET first_name = ?, last_name = ?, middle_name = ?, date_of_birth = ?, gender = ?, employee_number = ?, employment_date = ?, position = ?, department = ?, qualification = ?, phone = ?, email = ?, address = ?, emergency_contact_name = ?, emergency_contact_phone = ?, status = ?, updated_at = NOW() WHERE id = ? AND school_id = ?");
                $stmt->execute([
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
                    $address ?: null,
                    $emergency_contact_name ?: null,
                    $emergency_contact_phone ?: null,
                    $status,
                    $staff_id,
                    $user['school_id']
                ]);
            }

            // Log the action
            log_action($user['id'], 'UPDATE_STAFF', "Updated staff: {$staff['staff_id']} - {$first_name} {$last_name}");

            $success = 'Staff member updated successfully!';
            // Refresh staff data
            $stmt = $pdo->prepare("SELECT * FROM staff WHERE id = ? AND school_id = ?");
            $stmt->execute([$staff_id, $user['school_id']]);
            $staff = $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error updating staff: " . $e->getMessage());
            $error = 'An error occurred while updating the staff member. Please try again.';
        }
    }
}

// Menu items
// Get menu items based on role
$menuItems = getMenuItems('SA', $user['school_id']);

renderAdminLTEHead('Edit Staff - Kaduna State SQMS');
?>

<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">
        <?php renderAdminLTESidebar($menuItems, $currentPage, $user, 'SA'); ?>

        <div class="content-wrapper">
            <?php renderAdminLTENavbar('Edit Staff', $user); ?>

            <!-- Content Header -->
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">Edit Staff</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="sa_dashboard.php">Home</a></li>
                                <li class="breadcrumb-item"><a href="view_staff.php">Staff</a></li>
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
                                    <h3 class="card-title"><i class="fas fa-user-edit mr-2"></i>Edit Staff Information
                                    </h3>
                                    <div class="card-tools">
                                        <span class="badge badge-info">ID:
                                            <?php echo htmlspecialchars($staff['staff_id']); ?></span>
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
                                        <i class="icon fas fa-check"></i> <?php echo htmlspecialchars($success); ?>
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
                                                        value="<?php echo htmlspecialchars($staff['first_name']); ?>">
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="last_name">Last Name <span
                                                            class="text-danger">*</span></label>
                                                    <input type="text" id="last_name" name="last_name" required
                                                        class="form-control"
                                                        value="<?php echo htmlspecialchars($staff['last_name']); ?>">
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="middle_name">Middle Name</label>
                                                    <input type="text" id="middle_name" name="middle_name"
                                                        class="form-control"
                                                        value="<?php echo htmlspecialchars($staff['middle_name'] ?? ''); ?>">
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="gender">Gender <span
                                                            class="text-danger">*</span></label>
                                                    <select id="gender" name="gender" required class="form-control">
                                                        <option value="Male" <?php echo $staff['gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                                                        <option value="Female" <?php echo $staff['gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                                                        <option value="Other" <?php echo $staff['gender'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="date_of_birth">Date of Birth</label>
                                                    <input type="date" id="date_of_birth" name="date_of_birth"
                                                        class="form-control"
                                                        value="<?php echo $staff['date_of_birth'] ? date('Y-m-d', strtotime($staff['date_of_birth'])) : ''; ?>">
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="position">Position</label>
                                                    <input type="text" id="position" name="position"
                                                        class="form-control"
                                                        placeholder="e.g., Teacher, Principal, Secretary"
                                                        value="<?php echo htmlspecialchars($staff['position'] ?? ''); ?>">
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="department">Department</label>
                                                    <input type="text" id="department" name="department"
                                                        class="form-control"
                                                        placeholder="e.g., Mathematics, Administration"
                                                        value="<?php echo htmlspecialchars($staff['department'] ?? ''); ?>">
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="employee_number">Employee Number</label>
                                                    <input type="text" id="employee_number" name="employee_number"
                                                        class="form-control"
                                                        value="<?php echo htmlspecialchars($staff['employee_number'] ?? ''); ?>">
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="employment_date">Employment Date</label>
                                                    <input type="date" id="employment_date" name="employment_date"
                                                        class="form-control"
                                                        value="<?php echo $staff['employment_date'] ? date('Y-m-d', strtotime($staff['employment_date'])) : ''; ?>">
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="qualification">Qualification</label>
                                                    <input type="text" id="qualification" name="qualification"
                                                        class="form-control" placeholder="e.g., B.Ed, M.Sc, PhD"
                                                        value="<?php echo htmlspecialchars($staff['qualification'] ?? ''); ?>">
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="phone">Staff Phone</label>
                                                    <input type="tel" id="phone" name="phone" class="form-control"
                                                        value="<?php echo htmlspecialchars($staff['phone'] ?? ''); ?>">
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="email">Staff Email</label>
                                                    <input type="email" id="email" name="email" class="form-control"
                                                        value="<?php echo htmlspecialchars($staff['email'] ?? ''); ?>">
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="emergency_contact_name">Emergency Contact Name</label>
                                                    <input type="text" id="emergency_contact_name"
                                                        name="emergency_contact_name" class="form-control"
                                                        value="<?php echo htmlspecialchars($staff['emergency_contact_name'] ?? ''); ?>">
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="emergency_contact_phone">Emergency Contact Phone</label>
                                                    <input type="tel" id="emergency_contact_phone"
                                                        name="emergency_contact_phone" class="form-control"
                                                        value="<?php echo htmlspecialchars($staff['emergency_contact_phone'] ?? ''); ?>">
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="status">Status</label>
                                                    <select id="status" name="status" class="form-control">
                                                        <option value="Active" <?php echo $staff['status'] === 'Active' ? 'selected' : ''; ?>>Active</option>
                                                        <option value="Inactive" <?php echo $staff['status'] === 'Inactive' ? 'selected' : ''; ?>>Inactive
                                                        </option>
                                                        <option value="Resigned" <?php echo $staff['status'] === 'Resigned' ? 'selected' : ''; ?>>Resigned
                                                        </option>
                                                        <option value="Terminated" <?php echo $staff['status'] === 'Terminated' ? 'selected' : ''; ?>>
                                                            Terminated</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="picture">Staff Picture</label>
                                                    <?php if (!empty($staff['picture'])): ?>
                                                        <?php
                                                        $picturePath = $staff['picture'];
                                                        if (strpos($picturePath, 'uploads/') === 0) {
                                                            $picturePath = '../' . $picturePath;
                                                        }
                                                        ?>
                                                        <div class="mb-2">
                                                            <img src="<?php echo htmlspecialchars($picturePath); ?>"
                                                                alt="Current Picture" class="img-thumbnail"
                                                                style="max-width: 150px; max-height: 150px; object-fit: cover;"
                                                                onerror="this.style.display='none';">
                                                            <p class="text-muted small mt-1">Current picture</p>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div class="custom-file">
                                                        <input type="file" class="custom-file-input" id="picture"
                                                            name="picture" accept=".jpg,.jpeg,.png,.gif">
                                                        <label class="custom-file-label" for="picture">Choose new file
                                                            (leave empty to keep current)</label>
                                                    </div>
                                                    <small class="form-text text-muted">Accepted formats: JPEG, PNG, GIF
                                                        (Max 5MB)</small>
                                                </div>
                                            </div>

                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="address">Address</label>
                                                    <textarea id="address" name="address" rows="3"
                                                        class="form-control"><?php echo htmlspecialchars($staff['address'] ?? ''); ?></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="card-footer">
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-save mr-2"></i>Update Staff
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