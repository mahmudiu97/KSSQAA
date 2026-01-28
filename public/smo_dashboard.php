<?php
/**
 * SMO Dashboard - AdminLTE with Charts
 */

$requiredRole = 'SMO';
require_once __DIR__ . '/../includes/check_auth.php';
require_once __DIR__ . '/../includes/layout.php';

$user = getCurrentUser();
$pdo = getDBConnection();

// Get current page for active menu highlighting
$currentPage = basename($_SERVER['PHP_SELF']);

// Get statistics
try {
    $stats = [];
    
    // Total schools
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM schools");
    $stats['total_schools'] = $stmt->fetch()['total'];
    
    // Active schools
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM schools WHERE status = 'Active'");
    $stats['active_schools'] = $stmt->fetch()['total'];
    
    // Pending schools
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM schools WHERE status = 'Pending'");
    $stats['pending_schools'] = $stmt->fetch()['total'];
    
    // Rejected schools
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM schools WHERE status = 'Rejected'");
    $stats['rejected_schools'] = $stmt->fetch()['total'];
    
    // Total users
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $stats['total_users'] = $stmt->fetch()['total'];
    
    // Total SAs
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'SA'");
    $stats['total_sas'] = $stmt->fetch()['total'];
    
    // Total SMOs
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'SMO'");
    $stats['total_smos'] = $stmt->fetch()['total'];
    
    // Total students
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM students");
    $stats['total_students'] = $stmt->fetch()['total'];
    
    // Total staff
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM staff");
    $stats['total_staff'] = $stmt->fetch()['total'];
    
    // Private vs Government Schools Statistics
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM schools WHERE school_type = 'Private'");
    $stats['private_schools'] = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM schools WHERE school_type = 'Government'");
    $stats['government_schools'] = $stmt->fetch()['total'];
    
    // Active schools by type
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM schools WHERE school_type = 'Private' AND status = 'Active'");
    $stats['active_private_schools'] = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM schools WHERE school_type = 'Government' AND status = 'Active'");
    $stats['active_government_schools'] = $stmt->fetch()['total'];
    
    // Students by school type
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM students s 
                         INNER JOIN schools sc ON s.school_id = sc.id 
                         WHERE sc.school_type = 'Private'");
    $stats['private_students'] = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM students s 
                         INNER JOIN schools sc ON s.school_id = sc.id 
                         WHERE sc.school_type = 'Government'");
    $stats['government_students'] = $stmt->fetch()['total'];
    
    // Staff by school type
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM staff s 
                         INNER JOIN schools sc ON s.school_id = sc.id 
                         WHERE sc.school_type = 'Private'");
    $stats['private_staff'] = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM staff s 
                         INNER JOIN schools sc ON s.school_id = sc.id 
                         WHERE sc.school_type = 'Government'");
    $stats['government_staff'] = $stmt->fetch()['total'];
    
    // Get monthly school registrations for the last 6 months
    $monthlyRegistrations = [];
    $monthlyRegistrationsByType = ['Private' => [], 'Government' => []];
    for ($i = 5; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM schools WHERE DATE_FORMAT(created_at, '%Y-%m') = ?");
        $stmt->execute([$month]);
        $monthlyRegistrations[] = [
            'month' => date('M Y', strtotime("-$i months")),
            'count' => $stmt->fetch()['total']
        ];
        
        // By type
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM schools WHERE DATE_FORMAT(created_at, '%Y-%m') = ? AND school_type = 'Private'");
        $stmt->execute([$month]);
        $monthlyRegistrationsByType['Private'][] = $stmt->fetch()['total'];
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM schools WHERE DATE_FORMAT(created_at, '%Y-%m') = ? AND school_type = 'Government'");
        $stmt->execute([$month]);
        $monthlyRegistrationsByType['Government'][] = $stmt->fetch()['total'];
    }
    
} catch (PDOException $e) {
    error_log("Error fetching statistics: " . $e->getMessage());
    $stats = [
        'total_schools' => 0,
        'active_schools' => 0,
        'pending_schools' => 0,
        'rejected_schools' => 0,
        'total_users' => 0,
        'total_sas' => 0,
        'total_smos' => 0,
        'total_students' => 0,
        'total_staff' => 0,
        'private_schools' => 0,
        'government_schools' => 0,
        'active_private_schools' => 0,
        'active_government_schools' => 0,
        'private_students' => 0,
        'government_students' => 0,
        'private_staff' => 0,
        'government_staff' => 0
    ];
    $monthlyRegistrations = [];
    $monthlyRegistrationsByType = ['Private' => [], 'Government' => []];
}

// Get menu items based on role
$menuItems = getMenuItems('SMO');

renderAdminLTEHead('SMO Dashboard - Kaduna State SQMS');
?>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
    <?php renderAdminLTESidebar($menuItems, $currentPage, $user, 'SMO'); ?>
    
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
                            <li class="breadcrumb-item"><a href="smo_dashboard.php">Home</a></li>
                            <li class="breadcrumb-item active">Dashboard</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <!-- Statistics Row -->
                <div class="row">
                    <!-- Total Schools -->
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-info">
                            <div class="inner">
                                <h3><?php echo $stats['total_schools']; ?></h3>
                                <p>Total Schools</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-school"></i>
                            </div>
                            <a href="schools.php" class="small-box-footer">
                                More info <i class="fas fa-arrow-circle-right"></i>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Active Schools -->
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-success">
                            <div class="inner">
                                <h3><?php echo $stats['active_schools']; ?></h3>
                                <p>Active Schools</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <a href="schools.php?status=Active" class="small-box-footer">
                                More info <i class="fas fa-arrow-circle-right"></i>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Pending Schools -->
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-warning">
                            <div class="inner">
                                <h3><?php echo $stats['pending_schools']; ?></h3>
                                <p>Pending Approval</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <a href="approve_schools.php" class="small-box-footer">
                                More info <i class="fas fa-arrow-circle-right"></i>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Total Users -->
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-danger">
                            <div class="inner">
                                <h3><?php echo $stats['total_users']; ?></h3>
                                <p>Total Users</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <a href="users.php" class="small-box-footer">
                                More info <i class="fas fa-arrow-circle-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Second Row -->
                <div class="row">
                    <!-- School Administrators -->
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-primary">
                            <div class="inner">
                                <h3><?php echo $stats['total_sas']; ?></h3>
                                <p>School Administrators</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-user-tie"></i>
                            </div>
                            <a href="users.php?role=SA" class="small-box-footer">
                                More info <i class="fas fa-arrow-circle-right"></i>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Total Students -->
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-info">
                            <div class="inner">
                                <h3><?php echo $stats['total_students']; ?></h3>
                                <p>Total Students</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <a href="#" class="small-box-footer">
                                More info <i class="fas fa-arrow-circle-right"></i>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Total Staff -->
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-success">
                            <div class="inner">
                                <h3><?php echo $stats['total_staff']; ?></h3>
                                <p>Total Staff</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-users-cog"></i>
                            </div>
                            <a href="#" class="small-box-footer">
                                More info <i class="fas fa-arrow-circle-right"></i>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Total SMOs -->
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-secondary">
                            <div class="inner">
                                <h3><?php echo $stats['total_smos']; ?></h3>
                                <p>System Managers</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-user-shield"></i>
                            </div>
                            <a href="users.php?role=SMO" class="small-box-footer">
                                More info <i class="fas fa-arrow-circle-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Charts Row -->
                <div class="row">
                    <!-- School Status Distribution -->
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header border-transparent">
                                <h3 class="card-title">
                                    <i class="fas fa-chart-pie mr-1"></i>
                                    School Status Distribution
                                </h3>
                            </div>
                            <div class="card-body">
                                <canvas id="schoolStatusChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Students vs Staff -->
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header border-transparent">
                                <h3 class="card-title">
                                    <i class="fas fa-chart-bar mr-1"></i>
                                    Students vs Staff
                                </h3>
                            </div>
                            <div class="card-body">
                                <canvas id="studentsStaffChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Private vs Government Summary Row -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-primary card-outline">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-balance-scale mr-1"></i>
                                    Private vs Government Schools Summary
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-school"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Private Schools</span>
                                                <span class="info-box-number"><?php echo $stats['private_schools']; ?></span>
                                                <small class="text-muted">Active: <?php echo $stats['active_private_schools']; ?></small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-info elevation-1"><i class="fas fa-landmark"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Government Schools</span>
                                                <span class="info-box-number"><?php echo $stats['government_schools']; ?></span>
                                                <small class="text-muted">Active: <?php echo $stats['active_government_schools']; ?></small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-user-graduate"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Private School Students</span>
                                                <span class="info-box-number"><?php echo $stats['private_students']; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-info elevation-1"><i class="fas fa-user-graduate"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Government School Students</span>
                                                <span class="info-box-number"><?php echo $stats['government_students']; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-md-3">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-users-cog"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Private School Staff</span>
                                                <span class="info-box-number"><?php echo $stats['private_staff']; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-info elevation-1"><i class="fas fa-users-cog"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Government School Staff</span>
                                                <span class="info-box-number"><?php echo $stats['government_staff']; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Second Charts Row -->
                <div class="row">
                    <!-- Private vs Government Schools -->
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header border-transparent">
                                <h3 class="card-title">
                                    <i class="fas fa-chart-pie mr-1"></i>
                                    Private vs Government Schools
                                </h3>
                            </div>
                            <div class="card-body">
                                <canvas id="schoolTypeChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Active Schools by Type -->
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header border-transparent">
                                <h3 class="card-title">
                                    <i class="fas fa-chart-bar mr-1"></i>
                                    Active Schools by Type
                                </h3>
                            </div>
                            <div class="card-body">
                                <canvas id="activeSchoolsByTypeChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Third Charts Row -->
                <div class="row">
                    <!-- Students by School Type -->
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header border-transparent">
                                <h3 class="card-title">
                                    <i class="fas fa-chart-bar mr-1"></i>
                                    Students by School Type
                                </h3>
                            </div>
                            <div class="card-body">
                                <canvas id="studentsByTypeChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Staff by School Type -->
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header border-transparent">
                                <h3 class="card-title">
                                    <i class="fas fa-chart-bar mr-1"></i>
                                    Staff by School Type
                                </h3>
                            </div>
                            <div class="card-body">
                                <canvas id="staffByTypeChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Fourth Charts Row -->
                <div class="row">
                    <!-- User Role Distribution -->
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header border-transparent">
                                <h3 class="card-title">
                                    <i class="fas fa-chart-pie mr-1"></i>
                                    User Role Distribution
                                </h3>
                            </div>
                            <div class="card-body">
                                <canvas id="userRoleChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Monthly Registrations by Type -->
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header border-transparent">
                                <h3 class="card-title">
                                    <i class="fas fa-chart-line mr-1"></i>
                                    Monthly Registrations by Type
                                </h3>
                            </div>
                            <div class="card-body">
                                <canvas id="monthlyRegistrationsByTypeChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                            </div>
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
// School Status Distribution Chart
const schoolStatusCtx = document.getElementById('schoolStatusChart').getContext('2d');
new Chart(schoolStatusCtx, {
    type: 'doughnut',
    data: {
        labels: ['Active', 'Pending', 'Rejected'],
        datasets: [{
            data: [
                <?php echo $stats['active_schools']; ?>,
                <?php echo $stats['pending_schools']; ?>,
                <?php echo $stats['rejected_schools']; ?>
            ],
            backgroundColor: [
                '#28a745',
                '#ffc107',
                '#dc3545'
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

// Students vs Staff Chart
const studentsStaffCtx = document.getElementById('studentsStaffChart').getContext('2d');
new Chart(studentsStaffCtx, {
    type: 'bar',
    data: {
        labels: ['Students', 'Staff'],
        datasets: [{
            label: 'Count',
            data: [
                <?php echo $stats['total_students']; ?>,
                <?php echo $stats['total_staff']; ?>
            ],
            backgroundColor: [
                '#17a2b8',
                '#28a745'
            ],
            borderWidth: 1
        }]
    },
    options: {
        maintainAspectRatio: false,
        responsive: true,
        scales: {
            y: {
                beginAtZero: true
            }
        },
        plugins: {
            legend: {
                display: false
            }
        }
    }
});

// User Role Distribution Chart
const userRoleCtx = document.getElementById('userRoleChart').getContext('2d');
new Chart(userRoleCtx, {
    type: 'pie',
    data: {
        labels: ['School Administrators', 'System Managers'],
        datasets: [{
            data: [
                <?php echo $stats['total_sas']; ?>,
                <?php echo $stats['total_smos']; ?>
            ],
            backgroundColor: [
                '#007bff',
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

// Private vs Government Schools Chart
const schoolTypeCtx = document.getElementById('schoolTypeChart').getContext('2d');
new Chart(schoolTypeCtx, {
    type: 'doughnut',
    data: {
        labels: ['Private Schools', 'Government Schools'],
        datasets: [{
            data: [
                <?php echo $stats['private_schools']; ?>,
                <?php echo $stats['government_schools']; ?>
            ],
            backgroundColor: [
                '#ffc107',
                '#17a2b8'
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

// Active Schools by Type Chart
const activeSchoolsByTypeCtx = document.getElementById('activeSchoolsByTypeChart').getContext('2d');
new Chart(activeSchoolsByTypeCtx, {
    type: 'bar',
    data: {
        labels: ['Private', 'Government'],
        datasets: [{
            label: 'Active Schools',
            data: [
                <?php echo $stats['active_private_schools']; ?>,
                <?php echo $stats['active_government_schools']; ?>
            ],
            backgroundColor: [
                '#ffc107',
                '#17a2b8'
            ],
            borderWidth: 1
        }]
    },
    options: {
        maintainAspectRatio: false,
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        },
        plugins: {
            legend: {
                display: false
            }
        }
    }
});

// Students by School Type Chart
const studentsByTypeCtx = document.getElementById('studentsByTypeChart').getContext('2d');
new Chart(studentsByTypeCtx, {
    type: 'bar',
    data: {
        labels: ['Private Schools', 'Government Schools'],
        datasets: [{
            label: 'Students',
            data: [
                <?php echo $stats['private_students']; ?>,
                <?php echo $stats['government_students']; ?>
            ],
            backgroundColor: [
                '#ffc107',
                '#17a2b8'
            ],
            borderWidth: 1
        }]
    },
    options: {
        maintainAspectRatio: false,
        responsive: true,
        scales: {
            y: {
                beginAtZero: true
            }
        },
        plugins: {
            legend: {
                display: false
            }
        }
    }
});

// Staff by School Type Chart
const staffByTypeCtx = document.getElementById('staffByTypeChart').getContext('2d');
new Chart(staffByTypeCtx, {
    type: 'bar',
    data: {
        labels: ['Private Schools', 'Government Schools'],
        datasets: [{
            label: 'Staff',
            data: [
                <?php echo $stats['private_staff']; ?>,
                <?php echo $stats['government_staff']; ?>
            ],
            backgroundColor: [
                '#ffc107',
                '#17a2b8'
            ],
            borderWidth: 1
        }]
    },
    options: {
        maintainAspectRatio: false,
        responsive: true,
        scales: {
            y: {
                beginAtZero: true
            }
        },
        plugins: {
            legend: {
                display: false
            }
        }
    }
});

// Monthly Registrations by Type Chart
const monthlyRegistrationsByTypeCtx = document.getElementById('monthlyRegistrationsByTypeChart').getContext('2d');
new Chart(monthlyRegistrationsByTypeCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode(array_column($monthlyRegistrations, 'month')); ?>,
        datasets: [
            {
                label: 'Private Schools',
                data: <?php echo json_encode($monthlyRegistrationsByType['Private']); ?>,
                borderColor: '#ffc107',
                backgroundColor: 'rgba(255, 193, 7, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            },
            {
                label: 'Government Schools',
                data: <?php echo json_encode($monthlyRegistrationsByType['Government']); ?>,
                borderColor: '#17a2b8',
                backgroundColor: 'rgba(23, 162, 184, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }
        ]
    },
    options: {
        maintainAspectRatio: false,
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        },
        plugins: {
            legend: {
                display: true,
                position: 'top'
            }
        }
    }
});
</script>
