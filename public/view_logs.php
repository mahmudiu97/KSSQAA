<?php
/**
 * View Audit Logs (SMO Only) - AdminLTE
 */

$requiredRole = 'SMO';
require_once __DIR__ . '/../includes/check_auth.php';
require_once __DIR__ . '/../includes/layout.php';

$user = getCurrentUser();
$pdo = getDBConnection();
$currentPage = basename($_SERVER['PHP_SELF']);

// Get filter parameters
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$action_filter = isset($_GET['action']) ? sanitizeInput($_GET['action']) : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build query
$where = [];
$params = [];

if ($search) {
    $where[] = "(al.description LIKE ? OR u.full_name LIKE ? OR u.username LIKE ?)";
    $searchParam = "%{$search}%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if ($action_filter) {
    $where[] = "al.action = ?";
    $params[] = $action_filter;
}

if ($date_from) {
    $where[] = "DATE(al.created_at) >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $where[] = "DATE(al.created_at) <= ?";
    $params[] = $date_to;
}

$whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Get audit logs
$logs = [];
try {
    $sql = "SELECT al.*, u.username, u.full_name, u.role 
            FROM audit_logs al 
            LEFT JOIN users u ON al.user_id = u.id 
            {$whereClause}
            ORDER BY al.created_at DESC 
            LIMIT 500";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching audit logs: " . $e->getMessage());
}

// Get unique actions for filter
$actions = [];
try {
    $stmt = $pdo->query("SELECT DISTINCT action FROM audit_logs ORDER BY action");
    $actions = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    error_log("Error fetching actions: " . $e->getMessage());
}

// Menu items
// Get menu items based on role
$menuItems = getMenuItems('SMO');

renderAdminLTEHead('Audit Logs - Kaduna State SQMS');
?>

<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">
        <?php renderAdminLTESidebar($menuItems, $currentPage, $user, 'SMO'); ?>

        <div class="content-wrapper">
            <?php renderAdminLTENavbar('Audit Logs', $user); ?>

            <!-- Content Header -->
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">Audit Logs</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="smo_dashboard.php">Home</a></li>
                                <li class="breadcrumb-item active">Audit Logs</li>
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
                                            placeholder="Description, user name..." class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="action">Action</label>
                                        <select id="action" name="action" class="form-control">
                                            <option value="">All Actions</option>
                                            <?php foreach ($actions as $action): ?>
                                                <option value="<?php echo htmlspecialchars($action); ?>" <?php echo $action_filter === $action ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($action); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="date_from">Date From</label>
                                        <input type="date" id="date_from" name="date_from"
                                            value="<?php echo htmlspecialchars($date_from); ?>" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="date_to">Date To</label>
                                        <input type="date" id="date_to" name="date_to"
                                            value="<?php echo htmlspecialchars($date_to); ?>" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>&nbsp;</label>
                                        <div>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-search"></i> Filter
                                            </button>
                                            <a href="view_logs.php" class="btn btn-secondary">
                                                <i class="fas fa-times"></i> Clear
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Results -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Audit Logs (<?php echo count($logs); ?>)</h3>
                        </div>
                        <div class="card-body table-responsive p-0">
                            <?php if (empty($logs)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-clipboard-list fa-4x text-muted mb-3"></i>
                                    <h4>No Logs Found</h4>
                                    <p class="text-muted">Try adjusting your search or filter criteria.</p>
                                </div>
                            <?php else: ?>
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date & Time</th>
                                            <th>User</th>
                                            <th>Action</th>
                                            <th>Description</th>
                                            <th>IP Address</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($logs as $log): ?>
                                            <tr>
                                                <td><?php echo date('M d, Y g:i A', strtotime($log['created_at'])); ?></td>
                                                <td>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($log['full_name'] ?? 'System'); ?></strong>
                                                    </div>
                                                    <?php if ($log['username']): ?>
                                                        <div class="text-muted small">
                                                            <?php echo htmlspecialchars($log['username']); ?></div>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge badge-primary">
                                                        <?php echo htmlspecialchars($log['action']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($log['description'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?></td>
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