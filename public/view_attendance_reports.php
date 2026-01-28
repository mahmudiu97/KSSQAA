<?php
/**
 * View Staff Attendance Reports - SMO Only
 */

$requiredRole = 'SMO';
require_once __DIR__ . '/../includes/check_auth.php';
require_once __DIR__ . '/../includes/layout.php';

$user = getCurrentUser();
$pdo = getDBConnection();

$currentPage = basename($_SERVER['PHP_SELF']);

// Get filter parameters
$schoolId = $_GET['school_id'] ?? null;
$sessionId = $_GET['session_id'] ?? null;
$termId = $_GET['term_id'] ?? null;
$startDate = $_GET['start_date'] ?? null;
$endDate = $_GET['end_date'] ?? null;

// Get all schools
$schools = [];
try {
    $stmt = $pdo->query("SELECT id, name FROM schools WHERE status = 'Active' ORDER BY name ASC");
    $schools = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching schools: " . $e->getMessage());
}

// Get sessions for selected school
$sessions = [];
if ($schoolId) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM sessions WHERE school_id = ? ORDER BY start_date DESC");
        $stmt->execute([$schoolId]);
        $sessions = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error fetching sessions: " . $e->getMessage());
    }
}

// Get terms for selected session
$terms = [];
if ($sessionId) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM terms WHERE session_id = ? ORDER BY start_date ASC");
        $stmt->execute([$sessionId]);
        $terms = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error fetching terms: " . $e->getMessage());
    }
}

// Get attendance data
$attendanceData = [];
$summaryStats = [
    'total_days' => 0,
    'total_present' => 0,
    'total_absent' => 0,
    'total_late' => 0,
    'total_excused' => 0
];

if ($schoolId && $sessionId && $termId) {
    try {
        $whereClause = "sa.school_id = ? AND sa.session_id = ? AND sa.term_id = ?";
        $params = [$schoolId, $sessionId, $termId];
        
        if ($startDate) {
            $whereClause .= " AND sa.attendance_date >= ?";
            $params[] = $startDate;
        }
        
        if ($endDate) {
            $whereClause .= " AND sa.attendance_date <= ?";
            $params[] = $endDate;
        }
        
        $stmt = $pdo->prepare("SELECT sa.*, s.staff_id, s.first_name, s.last_name, s.middle_name, s.position,
                               sch.name as school_name, ses.name as session_name, t.name as term_name
                               FROM staff_attendance sa
                               INNER JOIN staff s ON sa.staff_id = s.id
                               INNER JOIN schools sch ON sa.school_id = sch.id
                               INNER JOIN sessions ses ON sa.session_id = ses.id
                               INNER JOIN terms t ON sa.term_id = t.id
                               WHERE $whereClause
                               ORDER BY sa.attendance_date DESC, s.first_name, s.last_name");
        $stmt->execute($params);
        $attendanceData = $stmt->fetchAll();
        
        // Calculate summary statistics
        $stmt = $pdo->prepare("SELECT 
                               COUNT(DISTINCT sa.attendance_date) as total_days,
                               SUM(CASE WHEN sa.status = 'Present' THEN 1 ELSE 0 END) as total_present,
                               SUM(CASE WHEN sa.status = 'Absent' THEN 1 ELSE 0 END) as total_absent,
                               SUM(CASE WHEN sa.status = 'Late' THEN 1 ELSE 0 END) as total_late,
                               SUM(CASE WHEN sa.status = 'Excused' THEN 1 ELSE 0 END) as total_excused
                               FROM staff_attendance sa
                               WHERE $whereClause");
        $stmt->execute($params);
        $summaryStats = $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error fetching attendance data: " . $e->getMessage());
    }
}

// Get menu items based on role
$menuItems = getMenuItems('SMO');

renderAdminLTEHead('Staff Attendance Reports - Kaduna State SQMS');
?>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
    <?php renderAdminLTESidebar($menuItems, $currentPage, $user, 'SMO'); ?>
    
    <div class="content-wrapper">
        <?php renderAdminLTENavbar('Attendance Reports', $user); ?>
        
        <!-- Content Header -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Staff Attendance Reports</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="smo_dashboard.php">Home</a></li>
                            <li class="breadcrumb-item active">Attendance Reports</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <!-- Filter Form -->
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Filter Reports</h3>
                    </div>
                    <form method="GET" action="">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="filter_school">School</label>
                                        <select class="form-control" id="filter_school" name="school_id" onchange="this.form.submit()">
                                            <option value="">-- All Schools --</option>
                                            <?php foreach ($schools as $school): ?>
                                                <option value="<?php echo $school['id']; ?>" <?php echo ($schoolId == $school['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($school['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="filter_session">Session</label>
                                        <select class="form-control" id="filter_session" name="session_id" onchange="this.form.submit()">
                                            <option value="">-- Select Session --</option>
                                            <?php foreach ($sessions as $session): ?>
                                                <option value="<?php echo $session['id']; ?>" <?php echo ($sessionId == $session['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($session['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="filter_term">Term</label>
                                        <select class="form-control" id="filter_term" name="term_id" onchange="this.form.submit()">
                                            <option value="">-- Select Term --</option>
                                            <?php foreach ($terms as $term): ?>
                                                <option value="<?php echo $term['id']; ?>" <?php echo ($termId == $term['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($term['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="filter_start">Start Date</label>
                                        <input type="date" class="form-control" id="filter_start" name="start_date" value="<?php echo htmlspecialchars($startDate ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="filter_end">End Date</label>
                                        <input type="date" class="form-control" id="filter_end" name="end_date" value="<?php echo htmlspecialchars($endDate ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>&nbsp;</label>
                                        <button type="submit" class="btn btn-primary btn-block">
                                            <i class="fas fa-filter"></i> Apply Filters
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Summary Statistics -->
                <?php if ($schoolId && $sessionId && $termId): ?>
                    <div class="row">
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-info">
                                <div class="inner">
                                    <h3><?php echo $summaryStats['total_days'] ?? 0; ?></h3>
                                    <p>Total Days</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-calendar"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-success">
                                <div class="inner">
                                    <h3><?php echo $summaryStats['total_present'] ?? 0; ?></h3>
                                    <p>Present</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-danger">
                                <div class="inner">
                                    <h3><?php echo $summaryStats['total_absent'] ?? 0; ?></h3>
                                    <p>Absent</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-times-circle"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-warning">
                                <div class="inner">
                                    <h3><?php echo ($summaryStats['total_late'] ?? 0) + ($summaryStats['total_excused'] ?? 0); ?></h3>
                                    <p>Late/Excused</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Attendance Data Table -->
                <?php if (!empty($attendanceData)): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Attendance Records</h3>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Staff ID</th>
                                            <th>Name</th>
                                            <th>Position</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($attendanceData as $record): ?>
                                            <?php
                                            $fullName = $record['first_name'] . ' ' . ($record['middle_name'] ? $record['middle_name'] . ' ' : '') . $record['last_name'];
                                            $statusClass = [
                                                'Present' => 'success',
                                                'Absent' => 'danger',
                                                'Late' => 'warning',
                                                'Excused' => 'info'
                                            ];
                                            ?>
                                            <tr>
                                                <td><?php echo date('M d, Y', strtotime($record['attendance_date'])); ?></td>
                                                <td><?php echo htmlspecialchars($record['staff_id']); ?></td>
                                                <td><?php echo htmlspecialchars($fullName); ?></td>
                                                <td><?php echo htmlspecialchars($record['position'] ?? 'N/A'); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo $statusClass[$record['status']] ?? 'secondary'; ?>">
                                                        <?php echo htmlspecialchars($record['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php elseif ($schoolId && $sessionId && $termId): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No attendance records found for the selected criteria.
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Please select a school, session, and term to view attendance reports.
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
    
    <?php renderAdminLTEFooter(); ?>
</div>

<?php renderAdminLTEScripts(); ?>
</body>
</html>

