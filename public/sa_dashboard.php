<?php
/**
 * SA (School Administrator) Dashboard - AdminLTE with Charts
 */

$requiredRole = 'SA';
require_once __DIR__ . '/../includes/check_auth.php';
require_once __DIR__ . '/../includes/layout.php';

$user = getCurrentUser();
$pdo = getDBConnection();

// Get current page for active menu highlighting
$currentPage = basename($_SERVER['PHP_SELF']);

// Get school information
$school = null;
if ($user['school_id']) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM schools WHERE id = ?");
        $stmt->execute([$user['school_id']]);
        $school = $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error fetching school data: " . $e->getMessage());
    }
}

// Get statistics for this school
$stats = [
    'total_students' => 0,
    'total_staff' => 0,
    'active_students' => 0,
    'active_staff' => 0
];

if ($user['school_id']) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM students WHERE school_id = ?");
        $stmt->execute([$user['school_id']]);
        $stats['total_students'] = $stmt->fetch()['total'];

        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM students WHERE school_id = ? AND status = 'Active'");
        $stmt->execute([$user['school_id']]);
        $stats['active_students'] = $stmt->fetch()['total'];

        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM staff WHERE school_id = ?");
        $stmt->execute([$user['school_id']]);
        $stats['total_staff'] = $stmt->fetch()['total'];

        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM staff WHERE school_id = ? AND status = 'Active'");
        $stmt->execute([$user['school_id']]);
        $stats['active_staff'] = $stmt->fetch()['total'];

        // Get students by gender
        $stmt = $pdo->prepare("SELECT gender, COUNT(*) as count FROM students WHERE school_id = ? GROUP BY gender");
        $stmt->execute([$user['school_id']]);
        $studentsByGender = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        // Get staff by gender
        $stmt = $pdo->prepare("SELECT gender, COUNT(*) as count FROM staff WHERE school_id = ? GROUP BY gender");
        $stmt->execute([$user['school_id']]);
        $staffByGender = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    } catch (PDOException $e) {
        error_log("Error fetching statistics: " . $e->getMessage());
        $studentsByGender = [];
        $staffByGender = [];
    }
}

// Get recent announcements for this SA
$announcements = [];
if ($user['school_id']) {
    try {
        $stmt = $pdo->prepare("SELECT a.*, u.full_name as created_by_name 
                               FROM announcements a 
                               LEFT JOIN users u ON a.created_by = u.id 
                               WHERE a.is_active = 1 
                               AND (
                                   a.target_audience = 'All' 
                                   OR a.target_audience = 'SA' 
                                   OR (a.target_audience = 'School' AND a.target_school_id = ?)
                               )
                               ORDER BY a.created_at DESC 
                               LIMIT 5");
        $stmt->execute([$user['school_id']]);
        $announcements = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error fetching announcements: " . $e->getMessage());
    }
}

// Get menu items based on role
$menuItems = getMenuItems('SA', $user['school_id']);

renderAdminLTEHead('SA Dashboard - Kaduna State SQMS');
?>

<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">
        <?php renderAdminLTESidebar($menuItems, $currentPage, $user, 'SA'); ?>

        <div class="content-wrapper">
            <?php renderAdminLTENavbar('Dashboard', $user); ?>

            <!-- Content Header -->
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">Dashboard</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="sa_dashboard.php">Home</a></li>
                                <li class="breadcrumb-item active">Dashboard</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main content -->
            <section class="content">
                <div class="container-fluid">
                    <?php if ($school): ?>
                        <!-- School Information Card -->
                        <div class="card card-success card-outline">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-school mr-2"></i>School Information</h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <dl>
                                            <dt>School Name</dt>
                                            <dd><?php echo htmlspecialchars($school['name']); ?></dd>
                                            <dt>LGA</dt>
                                            <dd><?php echo htmlspecialchars($school['lga']); ?></dd>
                                            <dt>Status</dt>
                                            <dd>
                                                <span
                                                    class="badge badge-<?php echo $school['status'] === 'Active' ? 'success' : ($school['status'] === 'Pending' ? 'warning' : 'danger'); ?>">
                                                    <?php echo htmlspecialchars($school['status']); ?>
                                                </span>
                                            </dd>
                                        </dl>
                                    </div>
                                    <div class="col-md-6">
                                        <dl>
                                            <?php if ($school['cac_number']): ?>
                                                <dt>CAC Number</dt>
                                                <dd><?php echo htmlspecialchars($school['cac_number']); ?></dd>
                                            <?php endif; ?>
                                            <?php if ($school['email']): ?>
                                                <dt>Email</dt>
                                                <dd><?php echo htmlspecialchars($school['email']); ?></dd>
                                            <?php endif; ?>
                                            <?php if ($school['phone']): ?>
                                                <dt>Phone</dt>
                                                <dd><?php echo htmlspecialchars($school['phone']); ?></dd>
                                            <?php endif; ?>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="icon fas fa-exclamation-triangle"></i>
                            No school has been assigned to your account yet. Please contact the system administrator.
                        </div>
                    <?php endif; ?>

                    <!-- Statistics Row -->
                    <div class="row">
                        <!-- Total Students -->
                        <div class="col-lg-6 col-6">
                            <div class="small-box bg-info">
                                <div class="inner">
                                    <h3><?php echo $stats['total_students']; ?></h3>
                                    <p>Total Students</p>
                                    <small>Active: <?php echo $stats['active_students']; ?></small>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-user-graduate"></i>
                                </div>
                                <a href="view_students.php" class="small-box-footer">
                                    More info <i class="fas fa-arrow-circle-right"></i>
                                </a>
                            </div>
                        </div>

                        <!-- Total Staff -->
                        <div class="col-lg-6 col-6">
                            <div class="small-box bg-success">
                                <div class="inner">
                                    <h3><?php echo $stats['total_staff']; ?></h3>
                                    <p>Total Staff</p>
                                    <small>Active: <?php echo $stats['active_staff']; ?></small>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-users-cog"></i>
                                </div>
                                <a href="view_staff.php" class="small-box-footer">
                                    More info <i class="fas fa-arrow-circle-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Row -->
                    <?php if ($user['school_id']): ?>
                        <div class="row">
                            <!-- Students by Gender -->
                            <div class="col-lg-6">
                                <div class="card">
                                    <div class="card-header border-transparent">
                                        <h3 class="card-title">
                                            <i class="fas fa-chart-pie mr-1"></i>
                                            Students by Gender
                                        </h3>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="studentsGenderChart"
                                            style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                                    </div>
                                </div>
                            </div>

                            <!-- Staff by Gender -->
                            <div class="col-lg-6">
                                <div class="card">
                                    <div class="card-header border-transparent">
                                        <h3 class="card-title">
                                            <i class="fas fa-chart-pie mr-1"></i>
                                            Staff by Gender
                                        </h3>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="staffGenderChart"
                                            style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Recent Announcements -->
                    <?php if (!empty($announcements)): ?>
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-bullhorn mr-2"></i>Recent Announcements</h3>
                                <div class="card-tools">
                                    <a href="announcements.php" class="btn btn-sm btn-primary">View All</a>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="timeline">
                                    <?php foreach ($announcements as $announcement): ?>
                                        <div class="time-label">
                                            <span
                                                class="bg-primary"><?php echo date('M d, Y', strtotime($announcement['created_at'])); ?></span>
                                        </div>
                                        <div>
                                            <i class="fas fa-bullhorn bg-blue"></i>
                                            <div class="timeline-item">
                                                <span class="time"><i class="fas fa-clock"></i>
                                                    <?php echo date('h:i A', strtotime($announcement['created_at'])); ?></span>
                                                <h3 class="timeline-header">
                                                    <?php echo htmlspecialchars($announcement['title']); ?></h3>
                                                <div class="timeline-body">
                                                    <?php echo htmlspecialchars(substr($announcement['content'], 0, 200)); ?>        <?php echo strlen($announcement['content']) > 200 ? '...' : ''; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <?php renderAdminLTEFooter(); ?>
    </div>

    <?php renderAdminLTEScripts(); ?>

    <?php if ($user['school_id']): ?>
        <script>
            // Students by Gender Chart
            const studentsGenderCtx = document.getElementById('studentsGenderChart').getContext('2d');
            new Chart(studentsGenderCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Male', 'Female', 'Other'],
                    datasets: [{
                        data: [
                            <?php echo isset($studentsByGender['Male']) ? $studentsByGender['Male'] : 0; ?>,
                            <?php echo isset($studentsByGender['Female']) ? $studentsByGender['Female'] : 0; ?>,
                            <?php echo isset($studentsByGender['Other']) ? $studentsByGender['Other'] : 0; ?>
                        ],
                        backgroundColor: [
                            '#007bff',
                            '#e83e8c',
                            '#6c757d'
                        ],
                        borderWidth: 2
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });

            // Staff by Gender Chart
            const staffGenderCtx = document.getElementById('staffGenderChart').getContext('2d');
            new Chart(staffGenderCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Male', 'Female', 'Other'],
                    datasets: [{
                        data: [
                            <?php echo isset($staffByGender['Male']) ? $staffByGender['Male'] : 0; ?>,
                            <?php echo isset($staffByGender['Female']) ? $staffByGender['Female'] : 0; ?>,
                            <?php echo isset($staffByGender['Other']) ? $staffByGender['Other'] : 0; ?>
                        ],
                        backgroundColor: [
                            '#28a745',
                            '#ffc107',
                            '#6c757d'
                        ],
                        borderWidth: 2
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        </script>
    <?php endif; ?>