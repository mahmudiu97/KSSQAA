<?php
/**
 * View Students (SA Only) - AdminLTE
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
$class_filter = isset($_GET['class']) ? sanitizeInput($_GET['class']) : '';
$status_filter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';

// Build query
$where = ["school_id = ?"];
$params = [$user['school_id']];

if ($search) {
    $where[] = "(first_name LIKE ? OR last_name LIKE ? OR middle_name LIKE ? OR student_id LIKE ? OR admission_number LIKE ?)";
    $searchParam = "%{$search}%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if ($class_filter) {
    $where[] = "class_level = ?";
    $params[] = $class_filter;
}

if ($status_filter) {
    $where[] = "status = ?";
    $params[] = $status_filter;
}

$whereClause = "WHERE " . implode(" AND ", $where);

// Get students
$students = [];
try {
    $sql = "SELECT * FROM students {$whereClause} ORDER BY last_name, first_name ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $students = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching students: " . $e->getMessage());
}

// Get unique class levels for filter
$class_levels = [];
try {
    $stmt = $pdo->prepare("SELECT DISTINCT class_level FROM students WHERE school_id = ? AND class_level IS NOT NULL AND class_level != '' ORDER BY class_level");
    $stmt->execute([$user['school_id']]);
    $class_levels = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    error_log("Error fetching class levels: " . $e->getMessage());
}

// Menu items
// Get menu items based on role
$menuItems = getMenuItems('SA', $user['school_id']);

renderAdminLTEHead('View Students - Kaduna State SQMS');
?>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
    <?php renderAdminLTESidebar($menuItems, $currentPage, $user, 'SA'); ?>
    
    <div class="content-wrapper">
        <?php renderAdminLTENavbar('Students', $user); ?>
        
        <!-- Content Header -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Students</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="sa_dashboard.php">Home</a></li>
                            <li class="breadcrumb-item active">Students</li>
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
                        <h3 class="card-title"><i class="fas fa-filter mr-2"></i>Filter Students</h3>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="" class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="search">Search</label>
                                    <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                                           placeholder="Name, Student ID, Admission #..." 
                                           class="form-control">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="class">Class Level</label>
                                    <select id="class" name="class" class="form-control">
                                        <option value="">All Classes</option>
                                        <?php foreach ($class_levels as $class): ?>
                                            <option value="<?php echo htmlspecialchars($class); ?>" <?php echo $class_filter === $class ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($class); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="status">Status</label>
                                    <select id="status" name="status" class="form-control">
                                        <option value="">All Statuses</option>
                                        <option value="Active" <?php echo $status_filter === 'Active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="Inactive" <?php echo $status_filter === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                                        <option value="Graduated" <?php echo $status_filter === 'Graduated' ? 'selected' : ''; ?>>Graduated</option>
                                        <option value="Transferred" <?php echo $status_filter === 'Transferred' ? 'selected' : ''; ?>>Transferred</option>
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
                                        <a href="view_students.php" class="btn btn-secondary">
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
                        <h3 class="card-title"><i class="fas fa-user-graduate mr-2"></i>Students (<?php echo count($students); ?>)</h3>
                        <div class="card-tools">
                            <a href="add_student.php" class="btn btn-success btn-sm">
                                <i class="fas fa-plus mr-1"></i> Add Student
                            </a>
                        </div>
                    </div>
                    
                    <div class="card-body p-0">
                        <?php if (empty($students)): ?>
                            <div class="text-center p-5">
                                <i class="fas fa-user-graduate fa-4x text-muted mb-3"></i>
                                <h4 class="text-muted">No Students Found</h4>
                                <p class="text-muted">Try adjusting your search or filter criteria.</p>
                                <a href="add_student.php" class="btn btn-success">
                                    <i class="fas fa-plus mr-1"></i> Add First Student
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Picture</th>
                                            <th>Student ID</th>
                                            <th>Name</th>
                                            <th>Gender</th>
                                            <th>Class Level</th>
                                            <th>Admission #</th>
                                            <th>Status</th>
                                            <th>Contact</th>
                                            <th class="text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($students as $student): ?>
                                            <tr>
                                                <td>
                                                    <?php 
                                                    $picturePath = $student['picture'];
                                                    $initials = strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1));
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
                                                             alt="<?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>" 
                                                             class="img-circle elevation-2" style="width: 40px; height: 40px; object-fit: cover;"
                                                             onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'40\' height=\'40\'%3E%3Ccircle cx=\'20\' cy=\'20\' r=\'20\' fill=\'%23e5e7eb\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'%239ca3af\' font-size=\'14\' font-weight=\'bold\'%3E<?php echo $initials; ?>%3C/text%3E%3C/svg%3E';">
                                                    <?php else: ?>
                                                        <div class="img-circle elevation-2 bg-secondary d-inline-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                            <span class="text-white font-weight-bold" style="font-size: 14px;"><?php echo $initials; ?></span>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="font-weight-bold"><?php echo htmlspecialchars($student['student_id']); ?></td>
                                                <td>
                                                    <div class="font-weight-bold"><?php echo htmlspecialchars($student['first_name'] . ' ' . ($student['middle_name'] ? $student['middle_name'] . ' ' : '') . $student['last_name']); ?></div>
                                                    <?php if ($student['date_of_birth']): ?>
                                                        <small class="text-muted">DOB: <?php echo date('M d, Y', strtotime($student['date_of_birth'])); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($student['gender']); ?></td>
                                                <td><?php echo htmlspecialchars($student['class_level'] ?: 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($student['admission_number'] ?: 'N/A'); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php 
                                                        echo $student['status'] === 'Active' ? 'success' : 
                                                            ($student['status'] === 'Graduated' ? 'info' : 
                                                            ($student['status'] === 'Transferred' ? 'warning' : 'secondary')); 
                                                    ?>">
                                                        <?php echo htmlspecialchars($student['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($student['phone']): ?>
                                                        <div><i class="fas fa-phone text-muted"></i> <?php echo htmlspecialchars($student['phone']); ?></div>
                                                    <?php endif; ?>
                                                    <?php if ($student['email']): ?>
                                                        <div><i class="fas fa-envelope text-muted"></i> <?php echo htmlspecialchars($student['email']); ?></div>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-right">
                                                    <a href="edit_student.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-primary">
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
