<?php
/**
 * School Directory - AdminLTE
 * List all active schools with search and filter
 */

// Allow both SMO and SA to view
require_once __DIR__ . '/../includes/check_auth.php';
require_once __DIR__ . '/../includes/layout.php';

$user = getCurrentUser();
$pdo = getDBConnection();
$currentPage = basename($_SERVER['PHP_SELF']);
$isSMO = getCurrentUserRole() === 'SMO';

// Get filter parameters
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$lga_filter = isset($_GET['lga']) ? sanitizeInput($_GET['lga']) : '';
$ward_filter = isset($_GET['ward']) ? (int) $_GET['ward'] : 0;
$status_filter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : 'Active';

// Build query
$where = [];
$params = [];

// SMO can see all statuses, SA only sees Active
if (!$isSMO) {
    $status_filter = 'Active';
}

if ($status_filter) {
    $where[] = "s.status = ?";
    $params[] = $status_filter;
}

if ($search) {
    $where[] = "(s.name LIKE ? OR s.lga LIKE ? OR s.cac_number LIKE ?)";
    $searchParam = "%{$search}%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if ($lga_filter) {
    $where[] = "s.lga = ?";
    $params[] = $lga_filter;
}

if ($ward_filter > 0) {
    $where[] = "s.ward_id = ?";
    $params[] = $ward_filter;
}

$whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Get schools
$schools = [];
try {
    $sql = "SELECT s.*, w.name as ward_name 
            FROM schools s 
            LEFT JOIN wards w ON s.ward_id = w.id 
            {$whereClause}
            ORDER BY s.name ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $schools = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching schools: " . $e->getMessage());
}

// Get unique LGAs for filter
$lgas = [];
try {
    $stmt = $pdo->query("SELECT DISTINCT lga FROM schools WHERE lga IS NOT NULL AND lga != '' ORDER BY lga");
    $lgas = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    error_log("Error fetching LGAs: " . $e->getMessage());
}

// Get wards for filter
$wards = [];
try {
    $stmt = $pdo->query("SELECT id, name FROM wards WHERE lga = 'Kaduna North' ORDER BY name");
    $wards = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching wards: " . $e->getMessage());
}

// Menu items based on role
if ($isSMO) {
    // Get menu items based on role
    $menuItems = getMenuItems('SMO');
} else {
    $menuItems = getMenuItems('SA', $user['school_id']);
}

renderAdminLTEHead('School Directory - Kaduna State SQMS');
?>

<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">
        <?php renderAdminLTESidebar($menuItems, $currentPage, $user, $isSMO ? 'SMO' : 'SA'); ?>

        <div class="content-wrapper">
            <?php renderAdminLTENavbar('School Directory', $user); ?>

            <!-- Content Header -->
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">School Directory</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a
                                        href="<?php echo $isSMO ? 'smo_dashboard.php' : 'sa_dashboard.php'; ?>">Home</a>
                                </li>
                                <li class="breadcrumb-item active">Schools</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main content -->
            <section class="content">
                <div class="container-fluid">
                    <!-- Filters -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-filter mr-2"></i>Filters</h3>
                        </div>
                        <div class="card-body">
                            <form method="GET" action="" class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="search">Search</label>
                                        <input type="text" id="search" name="search"
                                            value="<?php echo htmlspecialchars($search); ?>"
                                            placeholder="School name, LGA, CAC..." class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="lga">LGA</label>
                                        <select id="lga" name="lga" class="form-control">
                                            <option value="">All LGAs</option>
                                            <?php foreach ($lgas as $lga): ?>
                                                <option value="<?php echo htmlspecialchars($lga); ?>" <?php echo $lga_filter === $lga ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($lga); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="ward">Ward</label>
                                        <select id="ward" name="ward" class="form-control">
                                            <option value="0">All Wards</option>
                                            <?php foreach ($wards as $ward): ?>
                                                <option value="<?php echo $ward['id']; ?>" <?php echo $ward_filter == $ward['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($ward['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <?php if ($isSMO): ?>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="status">Status</label>
                                            <select id="status" name="status" class="form-control">
                                                <option value="Active" <?php echo $status_filter === 'Active' ? 'selected' : ''; ?>>Active</option>
                                                <option value="Pending" <?php echo $status_filter === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="Rejected" <?php echo $status_filter === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                                                <option value="Suspended" <?php echo $status_filter === 'Suspended' ? 'selected' : ''; ?>>Suspended</option>
                                            </select>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <div class="col-md-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i> Filter
                                    </button>
                                    <a href="schools.php" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Clear
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Results -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Schools (<?php echo count($schools); ?>)</h3>
                        </div>
                        <div class="card-body table-responsive p-0">
                            <?php if (empty($schools)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-school fa-4x text-muted mb-3"></i>
                                    <h4>No Schools Found</h4>
                                    <p class="text-muted">Try adjusting your search or filter criteria.</p>
                                </div>
                            <?php else: ?>
                                <table class="table table-hover text-nowrap">
                                    <thead>
                                        <tr>
                                            <th>School Name</th>
                                            <th>Type</th>
                                            <th>LGA</th>
                                            <th>Ward</th>
                                            <th>Status</th>
                                            <th>Contact</th>
                                            <th class="text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($schools as $school): ?>
                                            <tr>
                                                <td>
                                                    <div><strong><?php echo htmlspecialchars($school['name']); ?></strong></div>
                                                    <?php if ($school['cac_number']): ?>
                                                        <div class="text-muted small">CAC:
                                                            <?php echo htmlspecialchars($school['cac_number']); ?></div>
                                                    <?php endif; ?>
                                                </td>
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
                                                <td>
                                                    <span class="badge badge-<?php
                                                    echo $school['status'] === 'Active' ? 'success' :
                                                        ($school['status'] === 'Pending' ? 'warning' :
                                                            ($school['status'] === 'Rejected' ? 'danger' : 'secondary'));
                                                    ?>">
                                                        <?php echo htmlspecialchars($school['status']); ?>
                                                    </span>
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
                                                <td class="text-right">
                                                    <a href="school_profile.php?id=<?php echo $school['id']; ?>"
                                                        class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye"></i> View Details
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <?php renderAdminLTEFooter(); ?>
    </div>

    <?php renderAdminLTEScripts(); ?>