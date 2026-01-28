<?php
/**
 * Change Password - AdminLTE
 * Available for both SMO and SA
 */

require_once __DIR__ . '/../includes/check_auth.php';
require_once __DIR__ . '/../includes/layout.php';

$user = getCurrentUser();
$pdo = getDBConnection();
$currentPage = basename($_SERVER['PHP_SELF']);

$isSMO = getCurrentUserRole() === 'SMO';
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($current_password)) {
        $error = 'Current password is required.';
    } elseif (empty($new_password)) {
        $error = 'New password is required.';
    } elseif (strlen($new_password) < 8) {
        $error = 'New password must be at least 8 characters long.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New password and confirmation password do not match.';
    } else {
        try {
            // Get current user's password hash
            $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
            $stmt->execute([$user['id']]);
            $userData = $stmt->fetch();

            if (!$userData) {
                $error = 'User not found.';
            } elseif (!verifyPassword($current_password, $userData['password_hash'])) {
                $error = 'Current password is incorrect.';
            } elseif (verifyPassword($new_password, $userData['password_hash'])) {
                $error = 'New password must be different from your current password.';
            } else {
                // Update password
                $newPasswordHash = hashPassword($new_password);
                $updateStmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                $updateStmt->execute([$newPasswordHash, $user['id']]);

                // Log the action
                log_action($user['id'], 'CHANGE_PASSWORD', "User changed their password");

                $success = 'Password changed successfully!';

                // Clear form
                $_POST = [];
            }
        } catch (PDOException $e) {
            error_log("Error changing password: " . $e->getMessage());
            $error = 'An error occurred while changing the password. Please try again.';
        }
    }
}

// Menu items based on role
if ($isSMO) {
    // Get menu items based on role
    $menuItems = getMenuItems('SMO');
} else {
    $menuItems = getMenuItems('SA', $user['school_id']);
}

renderAdminLTEHead('Change Password - Kaduna State SQMS');
?>

<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">
        <?php renderAdminLTESidebar($menuItems, $currentPage, $user, $isSMO ? 'SMO' : 'SA'); ?>

        <div class="content-wrapper">
            <?php renderAdminLTENavbar('Change Password', $user); ?>

            <!-- Content Header -->
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">Change Password</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a
                                        href="<?php echo $isSMO ? 'smo_dashboard.php' : 'sa_dashboard.php'; ?>">Home</a>
                                </li>
                                <li class="breadcrumb-item active">Change Password</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main content -->
            <section class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-6 offset-md-3">
                            <div class="card card-primary">
                                <div class="card-header">
                                    <h3 class="card-title"><i class="fas fa-key mr-2"></i>Change Your Password</h3>
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

                                <form method="POST" action="" id="changePasswordForm">
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label for="current_password">Current Password <span
                                                    class="text-danger">*</span></label>
                                            <input type="password" id="current_password" name="current_password"
                                                required class="form-control" placeholder="Enter your current password"
                                                autocomplete="current-password">
                                        </div>

                                        <div class="form-group">
                                            <label for="new_password">New Password <span
                                                    class="text-danger">*</span></label>
                                            <input type="password" id="new_password" name="new_password" required
                                                class="form-control"
                                                placeholder="Enter new password (minimum 8 characters)"
                                                autocomplete="new-password" minlength="8">
                                            <small class="form-text text-muted">
                                                Password must be at least 8 characters long.
                                            </small>
                                        </div>

                                        <div class="form-group">
                                            <label for="confirm_password">Confirm New Password <span
                                                    class="text-danger">*</span></label>
                                            <input type="password" id="confirm_password" name="confirm_password"
                                                required class="form-control" placeholder="Confirm your new password"
                                                autocomplete="new-password" minlength="8">
                                            <small class="form-text text-muted" id="passwordMatch"></small>
                                        </div>

                                        <div class="alert alert-info">
                                            <h5><i class="icon fas fa-info"></i> Password Requirements:</h5>
                                            <ul class="mb-0">
                                                <li>Minimum 8 characters</li>
                                                <li>Use a combination of letters, numbers, and special characters for
                                                    better security</li>
                                                <li>Do not share your password with anyone</li>
                                            </ul>
                                        </div>
                                    </div>

                                    <div class="card-footer">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save mr-2"></i>Change Password
                                        </button>
                                        <a href="<?php echo $isSMO ? 'smo_dashboard.php' : 'sa_dashboard.php'; ?>"
                                            class="btn btn-secondary">
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
        // Real-time password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function () {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            const matchMessage = document.getElementById('passwordMatch');

            if (confirmPassword.length > 0) {
                if (newPassword === confirmPassword) {
                    matchMessage.textContent = '✓ Passwords match';
                    matchMessage.className = 'form-text text-success';
                    this.setCustomValidity('');
                } else {
                    matchMessage.textContent = '✗ Passwords do not match';
                    matchMessage.className = 'form-text text-danger';
                    this.setCustomValidity('Passwords do not match');
                }
            } else {
                matchMessage.textContent = '';
                this.setCustomValidity('');
            }
        });

        // Form validation
        document.getElementById('changePasswordForm').addEventListener('submit', function (e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('New password and confirmation password do not match.');
                return false;
            }

            if (newPassword.length < 8) {
                e.preventDefault();
                alert('New password must be at least 8 characters long.');
                return false;
            }

            return true;
        });
    </script>