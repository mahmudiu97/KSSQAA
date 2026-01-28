<?php
/**
 * View Staff (SA Only) - AdminLTE
 */

$requiredRole = 'SA';
require_once __DIR__ . '/../includes/check_auth.php';
require_once __DIR__ . '/../includes/layout.php';

$user = getCurrentUser();
$pdo = getDBConnection();
$currentPage = basename($_SERVER['PHP_SELF']);

// Check if school is assigned
if (!$user['school_id']) {
    $_SESSION['error'] = 'No school has been assigned to your account.';
    redirect('sa_dashboard.php');
    exit();
}

// Get filter parameters
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$position_filter = isset($_GET['position']) ? sanitizeInput($_GET['position']) : '';
$department_filter = isset($_GET['department']) ? sanitizeInput($_GET['department']) : '';
$status_filter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';

// Build query
$where = ["school_id = ?"];
$params = [$user['school_id']];

if ($search) {
    $where[] = "(first_name LIKE ? OR last_name LIKE ? OR middle_name LIKE ? OR staff_id LIKE ? OR employee_number LIKE ?)";
    $searchParam = "%{$search}%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if ($position_filter) {
    $where[] = "position = ?";
    $params[] = $position_filter;
}

if ($department_filter) {
    $where[] = "department = ?";
    $params[] = $department_filter;
}

if ($status_filter) {
    $where[] = "status = ?";
    $params[] = $status_filter;
}

$whereClause = "WHERE " . implode(" AND ", $where);

// Get staff
$staff = [];
try {
    $sql = "SELECT * FROM staff {$whereClause} ORDER BY last_name, first_name ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $staff = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching staff: " . $e->getMessage());
}

// Get unique positions for filter
$positions = [];
try {
    $stmt = $pdo->prepare("SELECT DISTINCT position FROM staff WHERE school_id = ? AND position IS NOT NULL AND position != '' ORDER BY position");
    $stmt->execute([$user['school_id']]);
    $positions = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    error_log("Error fetching positions: " . $e->getMessage());
}

// Get unique departments for filter
$departments = [];
try {
    $stmt = $pdo->prepare("SELECT DISTINCT department FROM staff WHERE school_id = ? AND department IS NOT NULL AND department != '' ORDER BY department");
    $stmt->execute([$user['school_id']]);
    $departments = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    error_log("Error fetching departments: " . $e->getMessage());
}

// Menu items
// Get menu items based on role
$menuItems = getMenuItems('SA', $user['school_id']);

renderAdminLTEHead('View Staff - Kaduna State SQMS');
?>

<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">
        <?php renderAdminLTESidebar($menuItems, $currentPage, $user, 'SA'); ?>

        <div class="content-wrapper">
            <?php renderAdminLTENavbar('Staff', $user); ?>

            <!-- Content Header -->
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">Staff</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="sa_dashboard.php">Home</a></li>
                                <li class="breadcrumb-item active">Staff</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main content -->
            <section class="content">
                <div class="container-fluid">
                    <!-- Filters -->
                    <div class="card card-success card-outline">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-filter mr-2"></i>Filter Staff</h3>
                        </div>
                        <div class="card-body">
                            <form method="GET" action="" class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="search">Search</label>
                                        <input type="text" id="search" name="search"
                                            value="<?php echo htmlspecialchars($search); ?>"
                                            placeholder="Name, Staff ID, Employee #..." class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="position">Position</label>
                                        <select id="position" name="position" class="form-control">
                                            <option value="">All Positions</option>
                                            <?php foreach ($positions as $position): ?>
                                                <option value="<?php echo htmlspecialchars($position); ?>" <?php echo $position_filter === $position ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($position); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="department">Department</label>
                                        <select id="department" name="department" class="form-control">
                                            <option value="">All Departments</option>
                                            <?php foreach ($departments as $department): ?>
                                                <option value="<?php echo htmlspecialchars($department); ?>" <?php echo $department_filter === $department ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($department); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="status">Status</label>
                                        <select id="status" name="status" class="form-control">
                                            <option value="">All Statuses</option>
                                            <option value="Active" <?php echo $status_filter === 'Active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="Inactive" <?php echo $status_filter === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                                            <option value="Resigned" <?php echo $status_filter === 'Resigned' ? 'selected' : ''; ?>>Resigned</option>
                                            <option value="Terminated" <?php echo $status_filter === 'Terminated' ? 'selected' : ''; ?>>Terminated</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>&nbsp;</label>
                                        <div class="btn-group w-100">
                                            <button type="submit" class="btn btn-success">
                                                <i class="fas fa-search mr-1"></i> Filter
                                            </button>
                                            <a href="view_staff.php" class="btn btn-secondary">
                                                <i class="fas fa-times"></i> Clear
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Results -->
                    <div class="card card-success card-outline">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-users-cog mr-2"></i>Staff
                                (<?php echo count($staff); ?>)</h3>
                            <div class="card-tools">
                                <a href="add_staff.php" class="btn btn-success btn-sm">
                                    <i class="fas fa-plus mr-1"></i> Add Staff
                                </a>
                            </div>
                        </div>

                        <div class="card-body p-0">
                            <?php if (empty($staff)): ?>
                                <div class="text-center p-5">
                                    <i class="fas fa-users-cog fa-4x text-muted mb-3"></i>
                                    <h4 class="text-muted">No Staff Found</h4>
                                    <p class="text-muted">Try adjusting your search or filter criteria.</p>
                                    <a href="add_staff.php" class="btn btn-success">
                                        <i class="fas fa-plus mr-1"></i> Add First Staff Member
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Picture</th>
                                                <th>Staff ID</th>
                                                <th>Name</th>
                                                <th>Gender</th>
                                                <th>Position</th>
                                                <th>Department</th>
                                                <th>Employee #</th>
                                                <th>Status</th>
                                                <th>Contact</th>
                                                <th class="text-right">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($staff as $member): ?>
                                                <tr>
                                                    <td>
                                                        <?php
                                                        $picturePath = $member['picture'];
                                                        $initials = strtoupper(substr($member['first_name'], 0, 1) . substr($member['last_name'], 0, 1));
                                                        $displayPath = '';
                                                        if ($picturePath) {
                                                            if (strpos($picturePath, 'uploads/') === 0) {
                                                                $displayPath = '../' . $picturePath;
                                                            } else {
                                                                $displayPath = $picturePath;
                                                            }
                                                        }
                                                        ?>
                                                        <?php if ($displayPath): ?>
                                                            <img src="<?php echo htmlspecialchars($displayPath); ?>"
                                                                alt="<?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?>"
                                                                class="img-circle elevation-2"
                                                                style="width: 40px; height: 40px; object-fit: cover;"
                                                                onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'40\' height=\'40\'%3E%3Ccircle cx=\'20\' cy=\'20\' r=\'20\' fill=\'%23e5e7eb\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'%239ca3af\' font-size=\'14\' font-weight=\'bold\'%3E<?php echo $initials; ?>%3C/text%3E%3C/svg%3E';">
                                                        <?php else: ?>
                                                            <div class="img-circle elevation-2 bg-secondary d-inline-flex align-items-center justify-content-center"
                                                                style="width: 40px; height: 40px;">
                                                                <span class="text-white font-weight-bold"
                                                                    style="font-size: 14px;"><?php echo $initials; ?></span>
                                                            </div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="font-weight-bold">
                                                        <?php echo htmlspecialchars($member['staff_id']); ?></td>
                                                    <td>
                                                        <div class="font-weight-bold">
                                                            <?php echo htmlspecialchars($member['first_name'] . ' ' . ($member['middle_name'] ? $member['middle_name'] . ' ' : '') . $member['last_name']); ?>
                                                        </div>
                                                        <?php if ($member['date_of_birth']): ?>
                                                            <small class="text-muted">DOB:
                                                                <?php echo date('M d, Y', strtotime($member['date_of_birth'])); ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($member['gender']); ?></td>
                                                    <td><?php echo htmlspecialchars($member['position'] ?: 'N/A'); ?></td>
                                                    <td><?php echo htmlspecialchars($member['department'] ?: 'N/A'); ?></td>
                                                    <td><?php echo htmlspecialchars($member['employee_number'] ?: 'N/A'); ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-<?php
                                                        echo $member['status'] === 'Active' ? 'success' :
                                                            ($member['status'] === 'Resigned' ? 'warning' :
                                                                ($member['status'] === 'Terminated' ? 'danger' : 'secondary'));
                                                        ?>">
                                                            <?php echo htmlspecialchars($member['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if ($member['phone']): ?>
                                                            <div><i class="fas fa-phone text-muted"></i>
                                                                <?php echo htmlspecialchars($member['phone']); ?></div>
                                                        <?php endif; ?>
                                                        <?php if ($member['email']): ?>
                                                            <div><i class="fas fa-envelope text-muted"></i>
                                                                <?php echo htmlspecialchars($member['email']); ?></div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-right">
                                                        <a href="edit_staff.php?id=<?php echo $member['id']; ?>"
                                                            class="btn btn-sm btn-primary">
                                                            <i class="fas fa-edit"></i> Edit
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <?php renderAdminLTEFooter(); ?>
    </div>

    <?php renderAdminLTEScripts(); ?>