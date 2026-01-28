<?php
/**
 * Users Management (SMO Only) - AdminLTE
 */

$requiredRole = 'SMO';
require_once __DIR__ . '/../includes/check_auth.php';
require_once __DIR__ . '/../includes/layout.php';

$user = getCurrentUser();
$pdo = getDBConnection();
$currentPage = basename($_SERVER['PHP_SELF']);

$error = '';
$success = '';

// Handle user status toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    $user_id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
    $new_status = isset($_POST['new_status']) ? (int) $_POST['new_status'] : 0;

    if ($user_id > 0 && $user_id != $user['id']) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?");
            $stmt->execute([$new_status, $user_id]);

            // Get user info for logging
            $userStmt = $pdo->prepare("SELECT username, full_name FROM users WHERE id = ?");
            $userStmt->execute([$user_id]);
            $targetUser = $userStmt->fetch();

            // Log the action
            $statusText = $new_status ? 'activated' : 'deactivated';
            log_action($user['id'], 'UPDATE_USER_STATUS', "User {$statusText}: {$targetUser['username']} ({$targetUser['full_name']})");

            $success = "User " . ($new_status ? 'activated' : 'deactivated') . " successfully.";
        } catch (PDOException $e) {
            error_log("Error updating user status: " . $e->getMessage());
            $error = 'An error occurred while updating the user status.';
        }
    }
}

// Get filter parameters
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$role_filter = isset($_GET['role']) ? sanitizeInput($_GET['role']) : '';
$status_filter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';

// Build query
$where = [];
$params = [];

if ($search) {
    $where[] = "(u.username LIKE ? OR u.full_name LIKE ? OR u.email LIKE ? OR s.name LIKE ?)";
    $searchParam = "%{$search}%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if ($role_filter) {
    $where[] = "u.role = ?";
    $params[] = $role_filter;
}

if ($status_filter !== '') {
    $where[] = "u.is_active = ?";
    $params[] = (int) $status_filter;
}

$whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Get users
$users = [];
try {
    $sql = "SELECT u.*, s.name as school_name, w.name as ward_name 
            FROM users u 
            LEFT JOIN schools s ON u.school_id = s.id 
            LEFT JOIN wards w ON u.ward_id = w.id 
            {$whereClause}
            ORDER BY u.role, u.full_name ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching users: " . $e->getMessage());
}

// Menu items
// Get menu items based on role
$menuItems = getMenuItems('SMO');

renderAdminLTEHead('Users Management - Kaduna State SQMS');
?>

<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">
        <?php renderAdminLTESidebar($menuItems, $currentPage, $user, 'SMO'); ?>

        <div class="content-wrapper">
            <?php renderAdminLTENavbar('Users Management', $user); ?>

            <!-- Content Header -->
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">Users Management</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="smo_dashboard.php">Home</a></li>
                                <li class="breadcrumb-item active">Users</li>
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
                                            placeholder="Username, name, email..." class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="role">Role</label>
                                        <select id="role" name="role" class="form-control">
                                            <option value="">All Roles</option>
                                            <option value="SMO" <?php echo $role_filter === 'SMO' ? 'selected' : ''; ?>>
                                                SMO</option>
                                            <option value="SA" <?php echo $role_filter === 'SA' ? 'selected' : ''; ?>>SA
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="status">Status</label>
                                        <select id="status" name="status" class="form-control">
                                            <option value="">All Statuses</option>
                                            <option value="1" <?php echo $status_filter === '1' ? 'selected' : ''; ?>>
                                                Active</option>
                                            <option value="0" <?php echo $status_filter === '0' ? 'selected' : ''; ?>>
                                                Inactive</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>&nbsp;</label>
                                        <div>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-search"></i> Filter
                                            </button>
                                            <a href="users.php" class="btn btn-secondary">
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
                            <h3 class="card-title">Users (<?php echo count($users); ?>)</h3>
                        </div>
                        <div class="card-body table-responsive p-0">
                            <?php if (empty($users)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-users fa-4x text-muted mb-3"></i>
                                    <h4>No Users Found</h4>
                                    <p class="text-muted">Try adjusting your search or filter criteria.</p>
                                </div>
                            <?php else: ?>
                                <table class="table table-hover text-nowrap">
                                    <thead>
                                        <tr>
                                            <th>User</th>
                                            <th>Role</th>
                                            <th>School</th>
                                            <th>Ward</th>
                                            <th>Status</th>
                                            <th>Last Login</th>
                                            <th class="text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $u): ?>
                                            <tr>
                                                <td>
                                                    <div><strong><?php echo htmlspecialchars($u['full_name']); ?></strong></div>
                                                    <div class="text-muted small">
                                                        <?php echo htmlspecialchars($u['username']); ?></div>
                                                    <div class="text-muted small"><?php echo htmlspecialchars($u['email']); ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span
                                                        class="badge badge-<?php echo $u['role'] === 'SMO' ? 'primary' : 'success'; ?>">
                                                        <?php echo htmlspecialchars($u['role']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($u['school_name'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($u['ward_name'] ?? 'N/A'); ?></td>
                                                <td>
                                                    <span
                                                        class="badge badge-<?php echo $u['is_active'] ? 'success' : 'danger'; ?>">
                                                        <?php echo $u['is_active'] ? 'Active' : 'Inactive'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php echo $u['last_login'] ? date('M d, Y g:i A', strtotime($u['last_login'])) : 'Never'; ?>
                                                </td>
                                                <td class="text-right">
                                                    <?php if ($u['id'] != $user['id']): ?>
                                                        <form method="POST" action="" class="d-inline">
                                                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                                            <input type="hidden" name="new_status"
                                                                value="<?php echo $u['is_active'] ? '0' : '1'; ?>">
                                                            <button type="submit" name="toggle_status"
                                                                class="btn btn-sm btn-<?php echo $u['is_active'] ? 'danger' : 'success'; ?>"
                                                                onclick="return confirm('Are you sure you want to <?php echo $u['is_active'] ? 'deactivate' : 'activate'; ?> this user?');">
                                                                <i
                                                                    class="fas fa-<?php echo $u['is_active'] ? 'ban' : 'check'; ?>"></i>
                                                                <?php echo $u['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        <span class="text-muted">Current User</span>
                                                    <?php endif; ?>
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