<?php
/**
 * Mark Staff Attendance - School Admin Only
 */

$requiredRole = 'SA';
require_once __DIR__ . '/../includes/check_auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/functions.php';

$user = getCurrentUser();
$pdo = getDBConnection();

if (!$user['school_id']) {
    header('Location: sa_dashboard.php');
    exit;
}

$currentPage = basename($_SERVER['PHP_SELF']);
$success = '';
$error = '';

// Handle bulk attendance submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_attendance') {
    $sessionId = $_POST['session_id'] ?? 0;
    $termId = $_POST['term_id'] ?? 0;
    $attendanceDate = $_POST['attendance_date'] ?? '';
    $attendanceData = $_POST['attendance'] ?? [];
    
    if (empty($sessionId) || empty($termId) || empty($attendanceDate)) {
        $error = 'Please select session, term, and date.';
    } elseif (empty($attendanceData)) {
        $error = 'No attendance data provided.';
    } else {
        try {
            $pdo->beginTransaction();
            
            // Verify session and term belong to school
            $stmt = $pdo->prepare("SELECT id FROM sessions WHERE id = ? AND school_id = ?");
            $stmt->execute([$sessionId, $user['school_id']]);
            if (!$stmt->fetch()) {
                throw new Exception('Invalid session selected.');
            }
            
            $stmt = $pdo->prepare("SELECT id FROM terms WHERE id = ? AND session_id = ?");
            $stmt->execute([$termId, $sessionId]);
            if (!$stmt->fetch()) {
                throw new Exception('Invalid term selected.');
            }
            
            // Get all staff for this school
            $stmt = $pdo->prepare("SELECT id FROM staff WHERE school_id = ? AND status = 'Active'");
            $stmt->execute([$user['school_id']]);
            $allStaff = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $marked = 0;
            $updated = 0;
            
            foreach ($allStaff as $staffId) {
                $status = $attendanceData[$staffId] ?? 'Absent';
                
                // Check if attendance already exists
                $checkStmt = $pdo->prepare("SELECT id FROM staff_attendance WHERE school_id = ? AND session_id = ? AND term_id = ? AND staff_id = ? AND attendance_date = ?");
                $checkStmt->execute([$user['school_id'], $sessionId, $termId, $staffId, $attendanceDate]);
                $existing = $checkStmt->fetch();
                
                if ($existing) {
                    // Update existing record
                    $updateStmt = $pdo->prepare("UPDATE staff_attendance SET status = ?, marked_by = ?, updated_at = NOW() WHERE id = ?");
                    $updateStmt->execute([$status, $user['id'], $existing['id']]);
                    $updated++;
                } else {
                    // Insert new record
                    $insertStmt = $pdo->prepare("INSERT INTO staff_attendance (school_id, session_id, term_id, staff_id, attendance_date, status, marked_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $insertStmt->execute([$user['school_id'], $sessionId, $termId, $staffId, $attendanceDate, $status, $user['id']]);
                    $marked++;
                }
            }
            
            $pdo->commit();
            log_action($user['id'], 'MARK_ATTENDANCE', "Marked attendance for $attendanceDate: $marked new, $updated updated");
            $success = "Attendance marked successfully! ($marked new records, $updated updated)";
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Error marking attendance: " . $e->getMessage());
            $error = 'Failed to mark attendance: ' . $e->getMessage();
        }
    }
}

// Get sessions for this school
$sessions = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM sessions WHERE school_id = ? ORDER BY start_date DESC");
    $stmt->execute([$user['school_id']]);
    $sessions = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching sessions: " . $e->getMessage());
}

// Get terms for selected session
$terms = [];
$selectedSessionId = $_GET['session_id'] ?? ($_POST['session_id'] ?? null);
if ($selectedSessionId) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM terms WHERE session_id = ? ORDER BY start_date ASC");
        $stmt->execute([$selectedSessionId]);
        $terms = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error fetching terms: " . $e->getMessage());
    }
}

// Get staff for this school
$staff = [];
try {
    $stmt = $pdo->prepare("SELECT id, staff_id, first_name, last_name, middle_name, position FROM staff WHERE school_id = ? AND status = 'Active' ORDER BY first_name, last_name");
    $stmt->execute([$user['school_id']]);
    $staff = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching staff: " . $e->getMessage());
}

// Get existing attendance for selected date
$existingAttendance = [];
$selectedDate = $_GET['date'] ?? ($_POST['attendance_date'] ?? date('Y-m-d'));
$selectedTermId = $_GET['term_id'] ?? ($_POST['term_id'] ?? null);

if ($selectedDate && $selectedTermId && $selectedSessionId) {
    try {
        $stmt = $pdo->prepare("SELECT staff_id, status FROM staff_attendance WHERE school_id = ? AND session_id = ? AND term_id = ? AND attendance_date = ?");
        $stmt->execute([$user['school_id'], $selectedSessionId, $selectedTermId, $selectedDate]);
        $existingAttendance = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (PDOException $e) {
        error_log("Error fetching existing attendance: " . $e->getMessage());
    }
}

// Get menu items based on role
$menuItems = getMenuItems('SA', $user['school_id']);

renderAdminLTEHead('Mark Staff Attendance - Kaduna State SQMS');
?>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
    <?php renderAdminLTESidebar($menuItems, $currentPage, $user, 'SA'); ?>
    
    <div class="content-wrapper">
        <?php renderAdminLTENavbar('Mark Staff Attendance', $user); ?>
        
        <!-- Content Header -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Mark Staff Attendance</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="sa_dashboard.php">Home</a></li>
                            <li class="breadcrumb-item active">Staff Attendance</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Selection Form -->
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Select Session, Term & Date</h3>
                    </div>
                    <form method="GET" action="" id="selectionForm">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="session_id">Session <span class="text-danger">*</span></label>
                                        <select class="form-control" id="session_id" name="session_id" required onchange="this.form.submit()">
                                            <option value="">-- Select Session --</option>
                                            <?php foreach ($sessions as $session): ?>
                                                <option value="<?php echo $session['id']; ?>" <?php echo ($selectedSessionId == $session['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($session['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="term_id">Term <span class="text-danger">*</span></label>
                                        <select class="form-control" id="term_id" name="term_id" required onchange="this.form.submit()">
                                            <option value="">-- Select Term --</option>
                                            <?php foreach ($terms as $term): ?>
                                                <option value="<?php echo $term['id']; ?>" <?php echo ($selectedTermId == $term['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($term['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="attendance_date">Date <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="attendance_date" name="date" value="<?php echo htmlspecialchars($selectedDate); ?>" required onchange="this.form.submit()">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Attendance Form -->
                <?php if ($selectedSessionId && $selectedTermId && $selectedDate && !empty($staff)): ?>
                    <form method="POST" action="" id="attendanceForm">
                        <input type="hidden" name="action" value="mark_attendance">
                        <input type="hidden" name="session_id" value="<?php echo htmlspecialchars($selectedSessionId); ?>">
                        <input type="hidden" name="term_id" value="<?php echo htmlspecialchars($selectedTermId); ?>">
                        <input type="hidden" name="attendance_date" value="<?php echo htmlspecialchars($selectedDate); ?>">
                        
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    Staff Attendance for <?php echo date('l, F d, Y', strtotime($selectedDate)); ?>
                                </h3>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-sm btn-info" onclick="markAll('Present')">Mark All Present</button>
                                    <button type="button" class="btn btn-sm btn-warning" onclick="markAll('Absent')">Mark All Absent</button>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Staff ID</th>
                                                <th>Name</th>
                                                <th>Position</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($staff as $member): ?>
                                                <?php
                                                $currentStatus = $existingAttendance[$member['id']] ?? 'Present';
                                                $fullName = $member['first_name'] . ' ' . ($member['middle_name'] ? $member['middle_name'] . ' ' : '') . $member['last_name'];
                                                ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($member['staff_id']); ?></td>
                                                    <td><?php echo htmlspecialchars($fullName); ?></td>
                                                    <td><?php echo htmlspecialchars($member['position'] ?? 'N/A'); ?></td>
                                                    <td>
                                                        <div class="btn-group btn-group-toggle" data-toggle="buttons">
                                                            <label class="btn btn-sm btn-success <?php echo ($currentStatus === 'Present') ? 'active' : ''; ?>">
                                                                <input type="radio" name="attendance[<?php echo $member['id']; ?>]" value="Present" <?php echo ($currentStatus === 'Present') ? 'checked' : ''; ?>> Present
                                                            </label>
                                                            <label class="btn btn-sm btn-danger <?php echo ($currentStatus === 'Absent') ? 'active' : ''; ?>">
                                                                <input type="radio" name="attendance[<?php echo $member['id']; ?>]" value="Absent" <?php echo ($currentStatus === 'Absent') ? 'checked' : ''; ?>> Absent
                                                            </label>
                                                            <label class="btn btn-sm btn-warning <?php echo ($currentStatus === 'Late') ? 'active' : ''; ?>">
                                                                <input type="radio" name="attendance[<?php echo $member['id']; ?>]" value="Late" <?php echo ($currentStatus === 'Late') ? 'checked' : ''; ?>> Late
                                                            </label>
                                                            <label class="btn btn-sm btn-info <?php echo ($currentStatus === 'Excused') ? 'active' : ''; ?>">
                                                                <input type="radio" name="attendance[<?php echo $member['id']; ?>]" value="Excused" <?php echo ($currentStatus === 'Excused') ? 'checked' : ''; ?>> Excused
                                                            </label>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-save"></i> Save Attendance
                                </button>
                            </div>
                        </div>
                    </form>
                <?php elseif ($selectedSessionId && $selectedTermId && $selectedDate): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No active staff found for this school.
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Please select a session, term, and date to mark attendance.
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
    
    <?php renderAdminLTEFooter(); ?>
</div>

<?php renderAdminLTEScripts(); ?>
<script>
function markAll(status) {
    const buttons = document.querySelectorAll('input[type="radio"][value="' + status + '"]');
    buttons.forEach(btn => {
        btn.checked = true;
        btn.closest('label').classList.add('active');
        // Remove active from siblings
        btn.closest('.btn-group').querySelectorAll('label').forEach(label => {
            if (label !== btn.closest('label')) {
                label.classList.remove('active');
            }
        });
    });
}
</script>
</body>
</html>

