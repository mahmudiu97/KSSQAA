<?php
/**
 * View Announcements - AdminLTE
 */

require_once __DIR__ . '/../includes/check_auth.php';
require_once __DIR__ . '/../includes/layout.php';

$user = getCurrentUser();
$pdo = getDBConnection();
$currentPage = basename($_SERVER['PHP_SELF']);

$isSMO = $user['role'] === 'SMO';

// Get announcements based on user role
$announcements = [];
try {
    if ($isSMO) {
        // SMO can see all announcements
        $stmt = $pdo->query("SELECT a.*, u.full_name as created_by_name, s.name as school_name 
                             FROM announcements a 
                             LEFT JOIN users u ON a.created_by = u.id 
                             LEFT JOIN schools s ON a.target_school_id = s.id 
                             ORDER BY a.created_at DESC");
        $announcements = $stmt->fetchAll();
    } else {
        // SA sees announcements targeted to them
        $stmt = $pdo->prepare("SELECT a.*, u.full_name as created_by_name, s.name as school_name 
                              FROM announcements a 
                              LEFT JOIN users u ON a.created_by = u.id 
                              LEFT JOIN schools s ON a.target_school_id = s.id 
                              WHERE a.is_active = 1 
                              AND (
                                  a.target_audience = 'All' 
                                  OR a.target_audience = 'SA' 
                                  OR (a.target_audience = 'School' AND a.target_school_id = ?)
                              )
                              ORDER BY a.created_at DESC");
        $stmt->execute([$user['school_id']]);
        $announcements = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    error_log("Error fetching announcements: " . $e->getMessage());
}

// Menu items (different for SMO and SA)
if ($isSMO) {
    // Get menu items based on role
    $menuItems = getMenuItems('SMO');
} else {
    $menuItems = getMenuItems('SA', $user['school_id']);
}

renderAdminLTEHead('Announcements - Kaduna State SQMS');
?>

<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">
        <?php renderAdminLTESidebar($menuItems, $currentPage, $user, $isSMO ? 'SMO' : 'SA'); ?>

        <div class="content-wrapper">
            <?php renderAdminLTENavbar('Announcements', $user); ?>

            <!-- Content Header -->
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">Announcements</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a
                                        href="<?php echo $isSMO ? 'smo_dashboard.php' : 'sa_dashboard.php'; ?>">Home</a>
                                </li>
                                <li class="breadcrumb-item active">Announcements</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main content -->
            <section class="content">
                <div class="container-fluid">
                    <?php if ($isSMO): ?>
                        <div class="row mb-3">
                            <div class="col-12">
                                <a href="create_announcement.php" class="btn btn-primary">
                                    <i class="fas fa-plus mr-2"></i>Create Announcement
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (empty($announcements)): ?>
                        <div class="card">
                            <div class="card-body text-center py-5">
                                <i class="fas fa-bullhorn fa-4x text-muted mb-3"></i>
                                <h3>No Announcements</h3>
                                <p class="text-muted">
                                    <?php echo $isSMO ? 'Create your first announcement to communicate with users.' : 'There are no announcements at this time.'; ?>
                                </p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($announcements as $announcement): ?>
                                <div class="col-md-12 mb-3">
                                    <div
                                        class="card <?php echo !$announcement['is_active'] ? 'card-secondary' : 'card-primary'; ?> card-outline">
                                        <div class="card-header">
                                            <h3 class="card-title">
                                                <i class="fas fa-bullhorn mr-2"></i>
                                                <?php echo htmlspecialchars($announcement['title']); ?>
                                            </h3>
                                            <div class="card-tools">
                                                <span
                                                    class="badge badge-<?php echo $announcement['is_active'] ? 'success' : 'secondary'; ?>">
                                                    <?php echo $announcement['is_active'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <small class="text-muted">
                                                    <i class="fas fa-user mr-1"></i>By:
                                                    <?php echo htmlspecialchars($announcement['created_by_name'] ?? 'Unknown'); ?>
                                                    <span class="mx-2">•</span>
                                                    <i
                                                        class="fas fa-calendar mr-1"></i><?php echo date('M d, Y g:i A', strtotime($announcement['created_at'])); ?>
                                                    <?php if ($announcement['target_audience'] !== 'All'): ?>
                                                        <span class="mx-2">•</span>
                                                        <span class="badge badge-info">
                                                            <?php
                                                            if ($announcement['target_audience'] === 'School' && $announcement['school_name']) {
                                                                echo htmlspecialchars($announcement['school_name']);
                                                            } else {
                                                                echo htmlspecialchars($announcement['target_audience']);
                                                            }
                                                            ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                            <div class="text-gray-700">
                                                <?php echo nl2br(htmlspecialchars($announcement['content'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <?php renderAdminLTEFooter(); ?>
    </div>

    <?php renderAdminLTEScripts(); ?>