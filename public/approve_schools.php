<?php
/**
 * School Approval Page (SMO Only) - AdminLTE
 */

$requiredRole = 'SMO';
require_once __DIR__ . '/../includes/check_auth.php';
require_once __DIR__ . '/../includes/layout.php';

$user = getCurrentUser();
$pdo = getDBConnection();
$currentPage = basename($_SERVER['PHP_SELF']);

$error = '';
$success = '';

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $school_id = isset($_POST['school_id']) ? (int) $_POST['school_id'] : 0;
    $action = $_POST['action'] ?? '';
    $rejection_reason = isset($_POST['rejection_reason']) ? sanitizeInput($_POST['rejection_reason']) : '';

    if ($school_id > 0 && in_array($action, ['approve', 'reject'])) {
        // Validate rejection reason if rejecting
        if ($action === 'reject' && empty($rejection_reason)) {
            $error = 'Please provide a reason for rejection.';
        } else {
            try {
                $status = $action === 'approve' ? 'Active' : 'Rejected';

                // Get school details for logging and email
                $schoolStmt = $pdo->prepare("SELECT * FROM schools WHERE id = ?");
                $schoolStmt->execute([$school_id]);
                $schoolData = $schoolStmt->fetch();

                if (!$schoolData) {
                    $error = 'School not found.';
                } else {
                    $school_name = $schoolData['name'];

                    // Update school status and rejection reason
                    $stmt = $pdo->prepare("UPDATE schools SET status = ?, rejection_reason = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$status, $action === 'reject' ? $rejection_reason : null, $school_id]);

                    // If approving, create school admin user account
                    $generatedPassword = null;
                    $username = null;
                    if ($action === 'approve' && !empty($schoolData['email'])) {
                        // Check if user already exists
                        $checkUser = $pdo->prepare("SELECT id FROM users WHERE email = ? OR school_id = ?");
                        $checkUser->execute([$schoolData['email'], $school_id]);
                        $existingUser = $checkUser->fetch();

                        if (!$existingUser) {
                            // Generate username from school name
                            $username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $schoolData['name']));
                            $username = substr($username, 0, 20);

                            // Ensure username is unique
                            $originalUsername = $username;
                            $counter = 1;
                            while (true) {
                                $checkUsername = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                                $checkUsername->execute([$username]);
                                if (!$checkUsername->fetch()) {
                                    break;
                                }
                                $username = $originalUsername . $counter;
                                $counter++;
                            }

                            // Generate random password
                            $generatedPassword = generateRandomPassword(12);
                            $passwordHash = hashPassword($generatedPassword);

                            // Create user account
                            $createUser = $pdo->prepare("INSERT INTO users (username, email, password_hash, full_name, role, school_id, is_active) VALUES (?, ?, ?, ?, 'SA', ?, 1)");
                            $createUser->execute([
                                $username,
                                $schoolData['email'],
                                $passwordHash,
                                $schoolData['name'] . ' Administrator',
                                $school_id
                            ]);

                            // Log user creation
                            log_action($user['id'], 'CREATE_USER', "Created School Administrator account for: {$schoolData['name']} (Username: {$username})");
                        }
                    }

                    // Log the action
                    log_action($user['id'], strtoupper($action) . '_SCHOOL', "School {$action}d: {$school_name}");

                    // Send email notification if school has an email address
                    if (!empty($schoolData['email'])) {
                        $subject = $action === 'approve'
                            ? 'School Registration Approved - Kaduna State SQMS'
                            : 'School Registration Status Update - Kaduna State SQMS';

                        $body = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #2563eb; color: white; padding: 20px; text-align: center; }
        .content { background-color: #f9fafb; padding: 30px; border: 1px solid #e5e7eb; }
        .status-box { background-color: ' . ($action === 'approve' ? '#10b981' : '#ef4444') . '; color: white; padding: 15px; border-radius: 5px; margin: 20px 0; text-align: center; font-weight: bold; }
        .footer { text-align: center; padding: 20px; color: #6b7280; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Kaduna State School Quality Management System</h1>
        </div>
        <div class="content">
            <p>Dear ' . htmlspecialchars($schoolData['name']) . ',</p>
            
            <p>We are writing to inform you about the status of your school registration application.</p>
            
            <div class="status-box">
                Status: ' . strtoupper($status) . '
            </div>';

                        if ($action === 'approve') {
                            $body .= '<p><strong>Congratulations!</strong> Your school registration has been approved.</p>
            <p>Your school is now active in the Kaduna State School Quality Management System. You can now:</p>
            <ul>
                <li>Access the system with your assigned School Administrator account</li>
                <li>Begin managing student and staff records</li>
                <li>Receive important announcements and updates</li>
            </ul>';

                            // Include login credentials if user was created
                            if ($generatedPassword && $username) {
                                $body .= '<div style="background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin: 20px 0;">
                <p style="font-weight: bold; margin-bottom: 10px;">Your Login Credentials:</p>
                <p><strong>Username:</strong> ' . htmlspecialchars($username) . '</p>
                <p><strong>Password:</strong> ' . htmlspecialchars($generatedPassword) . '</p>
                <p style="margin-top: 10px; font-size: 12px; color: #92400e;">Please save these credentials securely. You can change your password after logging in.</p>
            </div>
            <p><strong>Important:</strong> Please log in and change your password immediately for security purposes.';
                            } else {
                                $body .= '<p>If you have not yet received your School Administrator login credentials, please contact the system administrator.</p>';
                            }
                        } else {
                            $body .= '<p>We regret to inform you that your school registration application has been rejected.</p>';

                            if (!empty($rejection_reason)) {
                                $body .= '<div style="background-color: #fee2e2; border-left: 4px solid #ef4444; padding: 15px; margin: 20px 0;">
                <p style="font-weight: bold; margin-bottom: 10px;">Reason for Rejection:</p>
                <p>' . nl2br(htmlspecialchars($rejection_reason)) . '</p>
            </div>';
                            }

                            $body .= '<p>If you believe this is an error or would like to appeal this decision, please contact the system administrator for further assistance.</p>
            <p>You may also submit a new application if you have additional information or documentation to provide.</p>';
                        }

                        $body .= '<p>For any questions or concerns, please contact the system administrator.</p>
            
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

                        $emailSent = sendEmail(
                            $schoolData['email'],
                            $schoolData['name'],
                            $subject,
                            $body
                        );

                        if (!$emailSent) {
                            error_log("Failed to send email notification to: " . $schoolData['email']);
                        }
                    }

                    $success = "School " . ($action === 'approve' ? 'approved' : 'rejected') . " successfully.";
                    if (!empty($schoolData['email'])) {
                        $success .= " Email notification " . (isset($emailSent) && $emailSent ? "sent" : "failed to send") . ".";
                    }
                }
            } catch (PDOException $e) {
                error_log("Error updating school status: " . $e->getMessage());
                $error = 'An error occurred while updating the school status.';
            }
        }
    }
}

// Get pending schools
$pendingSchools = [];
try {
    $stmt = $pdo->query("
        SELECT s.*, w.name as ward_name
        FROM schools s
        LEFT JOIN wards w ON s.ward_id = w.id
        WHERE s.status = 'Pending'
        ORDER BY s.created_at DESC
    ");
    $pendingSchools = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching pending schools: " . $e->getMessage());
}

// Menu items
// Get menu items based on role
$menuItems = getMenuItems('SMO');

renderAdminLTEHead('Approve Schools - Kaduna State SQMS');
?>

<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">
        <?php renderAdminLTESidebar($menuItems, $currentPage, $user, 'SMO'); ?>

        <div class="content-wrapper">
            <?php renderAdminLTENavbar('Approve Schools', $user); ?>

            <!-- Content Header -->
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">Approve Schools</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="smo_dashboard.php">Home</a></li>
                                <li class="breadcrumb-item active">Approve Schools</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main content -->
            <section class="content">
                <div class="container-fluid">
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                            <i class="icon fas fa-ban"></i> <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                            <i class="icon fas fa-check"></i> <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (empty($pendingSchools)): ?>
                        <div class="card">
                            <div class="card-body text-center py-5">
                                <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                                <h3>No Pending Schools</h3>
                                <p class="text-muted">All school registrations have been processed.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Pending School Approvals (<?php echo count($pendingSchools); ?>)</h3>
                            </div>
                            <div class="card-body table-responsive p-0">
                                <table class="table table-hover text-nowrap">
                                    <thead>
                                        <tr>
                                            <th>School Name</th>
                                            <th>Type</th>
                                            <th>LGA</th>
                                            <th>Ward</th>
                                            <th>CAC Number</th>
                                            <th>TIN Number</th>
                                            <th>Tax Clearance</th>
                                            <th>Contact</th>
                                            <th>Registered</th>
                                            <th class="text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pendingSchools as $school): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($school['name']); ?></strong></td>
                                                <td>
                                                    <?php if (!empty($school['school_type'])): ?>
                                                        <span
                                                            class="badge badge-<?php echo $school['school_type'] === 'Private' ? 'warning' : 'info'; ?>">
                                                            <?php echo htmlspecialchars($school['school_type']); ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted">N/A</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($school['lga']); ?></td>
                                                <td><?php echo htmlspecialchars($school['ward_name'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($school['cac_number'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($school['tin_number'] ?? 'N/A'); ?></td>
                                                <td>
                                                    <?php if ($school['tax_clearance_file']): ?>
                                                        <?php
                                                        $fileExtension = strtolower(pathinfo($school['tax_clearance_file'], PATHINFO_EXTENSION));
                                                        $isImage = in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif']);
                                                        $filePath = $school['tax_clearance_file'];
                                                        if (strpos($filePath, '../') !== 0 && strpos($filePath, 'http') !== 0) {
                                                            $filePath = '../' . ltrim($filePath, '/');
                                                        }
                                                        ?>
                                                        <?php if ($isImage): ?>
                                                            <img src="<?php echo htmlspecialchars($filePath); ?>" alt="Tax Clearance"
                                                                class="img-thumbnail"
                                                                style="width: 60px; height: 60px; object-fit: cover; cursor: pointer;"
                                                                onclick="openImageModal('<?php echo htmlspecialchars($filePath); ?>', '<?php echo htmlspecialchars($school['name']); ?>')"
                                                                onerror="this.style.display='none';">
                                                            <br>
                                                            <button type="button" class="btn btn-sm btn-link p-0 mt-1"
                                                                onclick="openImageModal('<?php echo htmlspecialchars($filePath); ?>', '<?php echo htmlspecialchars($school['name']); ?>')">
                                                                View Full
                                                            </button>
                                                        <?php else: ?>
                                                            <a href="<?php echo htmlspecialchars($filePath); ?>" target="_blank"
                                                                class="btn btn-sm btn-primary">
                                                                <i class="fas fa-file-pdf"></i> View PDF
                                                            </a>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">N/A</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($school['email']): ?>
                                                        <div><i class="fas fa-envelope"></i>
                                                            <?php echo htmlspecialchars($school['email']); ?></div>
                                                    <?php endif; ?>
                                                    <?php if ($school['phone']): ?>
                                                        <div><i class="fas fa-phone"></i>
                                                            <?php echo htmlspecialchars($school['phone']); ?></div>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($school['created_at'])); ?></td>
                                                <td class="text-right">
                                                    <form method="POST" action="" class="d-inline">
                                                        <input type="hidden" name="school_id"
                                                            value="<?php echo $school['id']; ?>">
                                                        <input type="hidden" name="action" value="approve">
                                                        <button type="submit"
                                                            onclick="return confirm('Are you sure you want to approve this school?')"
                                                            class="btn btn-sm btn-success">
                                                            <i class="fas fa-check"></i> Approve
                                                        </button>
                                                    </form>
                                                    <button type="button"
                                                        onclick="openRejectModal(<?php echo $school['id']; ?>)"
                                                        class="btn btn-sm btn-danger">
                                                        <i class="fas fa-times"></i> Reject
                                                    </button>
                                                    <a href="school_profile.php?id=<?php echo $school['id']; ?>"
                                                        class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye"></i> View
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <?php renderAdminLTEFooter(); ?>
    </div>

    <!-- Reject Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reject School Registration</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="school_id" id="reject_school_id">
                        <input type="hidden" name="action" value="reject">
                        <div class="form-group">
                            <label for="rejection_reason">Reason for Rejection <span
                                    class="text-danger">*</span></label>
                            <textarea id="rejection_reason" name="rejection_reason" class="form-control" rows="4"
                                required
                                placeholder="Please provide a reason for rejecting this school registration..."></textarea>
                            <small class="form-text text-muted">This reason will be included in the email notification
                                to the school.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Confirm Rejection</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Image Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Tax Clearance Certificate</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalImage" src="" alt="Tax Clearance Certificate" class="img-fluid">
                </div>
            </div>
        </div>
    </div>

    <?php renderAdminLTEScripts(); ?>

    <script>
        function openImageModal(imageSrc, schoolName) {
            if (imageSrc && !imageSrc.startsWith('http') && !imageSrc.startsWith('../') && !imageSrc.startsWith('/')) {
                imageSrc = '../' + imageSrc;
            }
            document.getElementById('modalImage').src = imageSrc;
            document.getElementById('modalImage').onerror = function () {
                this.src = '';
                this.alt = 'Image not found';
                alert('Unable to load image. Please check if the file exists.');
            };
            document.getElementById('modalTitle').textContent = 'Tax Clearance Certificate - ' + (schoolName || 'School');
            $('#imageModal').modal('show');
        }

        function openRejectModal(schoolId) {
            document.getElementById('reject_school_id').value = schoolId;
            document.getElementById('rejection_reason').value = '';
            $('#rejectModal').modal('show');
        }
    </script>