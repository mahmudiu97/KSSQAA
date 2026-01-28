<?php
/**
 * Reports Module (SMO Only) - AdminLTE
 */

$requiredRole = 'SMO';
require_once __DIR__ . '/../includes/check_auth.php';
require_once __DIR__ . '/../includes/layout.php';

$user = getCurrentUser();
$pdo = getDBConnection();
$currentPage = basename($_SERVER['PHP_SELF']);

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $report_type = $_GET['type'] ?? 'schools';

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="sqms_report_' . $report_type . '_' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');

    try {
        switch ($report_type) {
            case 'schools':
                fputcsv($output, ['ID', 'Name', 'LGA', 'CAC Number', 'Status', 'Email', 'Phone', 'Created At']);
                $stmt = $pdo->query("SELECT id, name, lga, cac_number, status, email, phone, created_at FROM schools ORDER BY name");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    fputcsv($output, $row);
                }
                break;

            case 'students':
                fputcsv($output, ['Student ID', 'First Name', 'Last Name', 'Gender', 'Class Level', 'School Name', 'Status', 'Admission Date']);
                $stmt = $pdo->query("SELECT s.student_id, s.first_name, s.last_name, s.gender, s.class_level, sc.name as school_name, s.status, s.admission_date 
                                     FROM students s 
                                     LEFT JOIN schools sc ON s.school_id = sc.id 
                                     ORDER BY sc.name, s.last_name, s.first_name");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    fputcsv($output, $row);
                }
                break;

            case 'staff':
                fputcsv($output, ['Staff ID', 'First Name', 'Last Name', 'Position', 'Department', 'School Name', 'Status', 'Employment Date']);
                $stmt = $pdo->query("SELECT st.staff_id, st.first_name, st.last_name, st.position, st.department, sc.name as school_name, st.status, st.employment_date 
                                     FROM staff st 
                                     LEFT JOIN schools sc ON st.school_id = sc.id 
                                     ORDER BY sc.name, st.last_name, st.first_name");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    fputcsv($output, $row);
                }
                break;

            case 'users':
                fputcsv($output, ['Username', 'Full Name', 'Email', 'Role', 'School Name', 'Is Active', 'Last Login']);
                $stmt = $pdo->query("SELECT u.username, u.full_name, u.email, u.role, sc.name as school_name, u.is_active, u.last_login 
                                     FROM users u 
                                     LEFT JOIN schools sc ON u.school_id = sc.id 
                                     ORDER BY u.role, u.full_name");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $row['is_active'] = $row['is_active'] ? 'Yes' : 'No';
                    fputcsv($output, $row);
                }
                break;
        }
    } catch (PDOException $e) {
        error_log("Error generating report: " . $e->getMessage());
    }

    fclose($output);
    exit();
}

// Get statistics for display
$stats = [];
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM schools");
    $stats['schools'] = $stmt->fetch()['total'];

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM students");
    $stats['students'] = $stmt->fetch()['total'];

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM staff");
    $stats['staff'] = $stmt->fetch()['total'];

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $stats['users'] = $stmt->fetch()['total'];
} catch (PDOException $e) {
    error_log("Error fetching statistics: " . $e->getMessage());
}

// Menu items
// Get menu items based on role
$menuItems = getMenuItems('SMO');

renderAdminLTEHead('Reports - Kaduna State SQMS');
?>

<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">
        <?php renderAdminLTESidebar($menuItems, $currentPage, $user, 'SMO'); ?>

        <div class="content-wrapper">
            <?php renderAdminLTENavbar('Reports', $user); ?>

            <!-- Content Header -->
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">Generate Reports</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="smo_dashboard.php">Home</a></li>
                                <li class="breadcrumb-item active">Reports</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main content -->
            <section class="content">
                <div class="container-fluid">
                    <div class="row">
                        <!-- Schools Report -->
                        <div class="col-lg-6 col-md-6">
                            <div class="card card-primary card-outline">
                                <div class="card-header">
                                    <h3 class="card-title"><i class="fas fa-school mr-2"></i>Schools Report</h3>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted">Total: <strong><?php echo $stats['schools']; ?></strong>
                                        schools</p>
                                    <p class="text-sm">Export all school information including status, contact details,
                                        and registration information.</p>
                                    <a href="?export=csv&type=schools" class="btn btn-primary mt-3">
                                        <i class="fas fa-download mr-2"></i>Export CSV
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Students Report -->
                        <div class="col-lg-6 col-md-6">
                            <div class="card card-success card-outline">
                                <div class="card-header">
                                    <h3 class="card-title"><i class="fas fa-user-graduate mr-2"></i>Students Report</h3>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted">Total: <strong><?php echo $stats['students']; ?></strong>
                                        students</p>
                                    <p class="text-sm">Export all student records including personal information, class
                                        levels, and admission details.</p>
                                    <a href="?export=csv&type=students" class="btn btn-success mt-3">
                                        <i class="fas fa-download mr-2"></i>Export CSV
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Staff Report -->
                        <div class="col-lg-6 col-md-6">
                            <div class="card card-warning card-outline">
                                <div class="card-header">
                                    <h3 class="card-title"><i class="fas fa-users-cog mr-2"></i>Staff Report</h3>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted">Total: <strong><?php echo $stats['staff']; ?></strong> staff
                                        members</p>
                                    <p class="text-sm">Export all staff records including positions, departments, and
                                        employment information.</p>
                                    <a href="?export=csv&type=staff" class="btn btn-warning mt-3">
                                        <i class="fas fa-download mr-2"></i>Export CSV
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Users Report -->
                        <div class="col-lg-6 col-md-6">
                            <div class="card card-info card-outline">
                                <div class="card-header">
                                    <h3 class="card-title"><i class="fas fa-users mr-2"></i>Users Report</h3>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted">Total: <strong><?php echo $stats['users']; ?></strong> users
                                    </p>
                                    <p class="text-sm">Export all user accounts including roles, assigned schools, and
                                        activity information.</p>
                                    <a href="?export=csv&type=users" class="btn btn-info mt-3">
                                        <i class="fas fa-download mr-2"></i>Export CSV
                                    </a>
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