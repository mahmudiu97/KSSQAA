<?php
/**
 * School Profile Page - AdminLTE
 */

require_once __DIR__ . '/../includes/check_auth.php';
require_once __DIR__ . '/../includes/layout.php';

$user = getCurrentUser();
$pdo = getDBConnection();
$currentPage = basename($_SERVER['PHP_SELF']);

// Get school ID from URL
$school_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($school_id <= 0) {
    $_SESSION['error'] = 'Invalid school ID.';
    redirect('schools.php');
    exit();
}

// Get school details
$school = null;
try {
    $stmt = $pdo->prepare("
        SELECT s.*, w.name as ward_name 
        FROM schools s 
        LEFT JOIN wards w ON s.ward_id = w.id 
        WHERE s.id = ?
    ");
    $stmt->execute([$school_id]);
    $school = $stmt->fetch();
} catch (PDOException $e) {
    error_log("Error fetching school: " . $e->getMessage());
    $_SESSION['error'] = 'Error loading school information.';
    redirect('schools.php');
    exit();
}

if (!$school) {
    $_SESSION['error'] = 'School not found.';
    redirect('schools.php');
    exit();
}

// Check if SA is trying to view a school that's not assigned to them
if (getCurrentUserRole() === 'SA' && $user['school_id'] != $school_id) {
    $_SESSION['error'] = 'You do not have permission to view this school.';
    redirect('sa_dashboard.php');
    exit();
}

$isSMO = getCurrentUserRole() === 'SMO';

// Menu items based on role
if ($isSMO) {
    // Get menu items based on role
    $menuItems = getMenuItems('SMO');
} else {
    $menuItems = getMenuItems('SA', $user['school_id']);
}

renderAdminLTEHead(htmlspecialchars($school['name']) . ' - Kaduna State SQMS');
?>

<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">
        <?php renderAdminLTESidebar($menuItems, $currentPage, $user, $isSMO ? 'SMO' : 'SA'); ?>

        <div class="content-wrapper">
            <?php renderAdminLTENavbar('School Profile', $user); ?>

            <!-- Content Header -->
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">School Profile</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a
                                        href="<?php echo $isSMO ? 'smo_dashboard.php' : 'sa_dashboard.php'; ?>">Home</a>
                                </li>
                                <?php if ($isSMO): ?>
                                    <li class="breadcrumb-item"><a href="schools.php">Schools</a></li>
                                <?php endif; ?>
                                <li class="breadcrumb-item active"><?php echo htmlspecialchars($school['name']); ?></li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main content -->
            <section class="content">
                <div class="container-fluid">
                    <!-- School Header -->
                    <div class="card card-<?php echo $isSMO ? 'primary' : 'success'; ?> card-outline">
                        <div class="card-header">
                            <h3 class="card-title"><i
                                    class="fas fa-school mr-2"></i><?php echo htmlspecialchars($school['name']); ?></h3>
                            <div class="card-tools">
                                <span class="badge badge-<?php
                                echo $school['status'] === 'Active' ? 'success' :
                                    ($school['status'] === 'Pending' ? 'warning' :
                                        ($school['status'] === 'Rejected' ? 'danger' : 'secondary'));
                                ?>">
                                    <?php echo htmlspecialchars($school['status']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <dl class="row">
                                        <dt class="col-sm-4">Registered:</dt>
                                        <dd class="col-sm-8">
                                            <?php echo date('M d, Y', strtotime($school['created_at'])); ?>
                                        </dd>

                                        <dt class="col-sm-4">Last Updated:</dt>
                                        <dd class="col-sm-8">
                                            <?php echo date('M d, Y', strtotime($school['updated_at'])); ?>
                                        </dd>
                                    </dl>
                                </div>
                                <?php if ($isSMO): ?>
                                    <div class="col-md-6 text-right">
                                        <a href="schools.php" class="btn btn-secondary">
                                            <i class="fas fa-arrow-left mr-1"></i> Back to Directory
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- School Details -->
                    <div class="card card-<?php echo $isSMO ? 'primary' : 'success'; ?> card-outline">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>School Information</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <dl class="row">
                                        <dt class="col-sm-5">Local Government Area (LGA):</dt>
                                        <dd class="col-sm-7"><?php echo htmlspecialchars($school['lga']); ?></dd>

                                        <?php if (!empty($school['school_type'])): ?>
                                            <dt class="col-sm-5">School Type:</dt>
                                            <dd class="col-sm-7">
                                                <span
                                                    class="badge badge-<?php echo $school['school_type'] === 'Private' ? 'warning' : 'info'; ?>">
                                                    <?php echo htmlspecialchars($school['school_type']); ?>
                                                </span>
                                            </dd>
                                        <?php endif; ?>

                                        <dt class="col-sm-5">Ward:</dt>
                                        <dd class="col-sm-7">
                                            <?php echo htmlspecialchars($school['ward_name'] ?? 'Not assigned'); ?>
                                        </dd>

                                        <?php if ($school['cac_number']): ?>
                                            <dt class="col-sm-5">CAC Registration Number:</dt>
                                            <dd class="col-sm-7"><?php echo htmlspecialchars($school['cac_number']); ?></dd>
                                        <?php endif; ?>

                                        <?php if ($school['tin_number']): ?>
                                            <dt class="col-sm-5">TIN Number:</dt>
                                            <dd class="col-sm-7"><?php echo htmlspecialchars($school['tin_number']); ?></dd>
                                        <?php endif; ?>
                                    </dl>
                                </div>

                                <div class="col-md-6">
                                    <dl class="row">
                                        <?php if ($school['address']): ?>
                                            <dt class="col-sm-4">Address:</dt>
                                            <dd class="col-sm-8"><?php echo htmlspecialchars($school['address']); ?></dd>
                                        <?php endif; ?>

                                        <?php if ($school['phone']): ?>
                                            <dt class="col-sm-4">Phone Number:</dt>
                                            <dd class="col-sm-8">
                                                <a href="tel:<?php echo htmlspecialchars($school['phone']); ?>"
                                                    class="text-primary">
                                                    <i
                                                        class="fas fa-phone mr-1"></i><?php echo htmlspecialchars($school['phone']); ?>
                                                </a>
                                            </dd>
                                        <?php endif; ?>

                                        <?php if ($school['email']): ?>
                                            <dt class="col-sm-4">Email Address:</dt>
                                            <dd class="col-sm-8">
                                                <a href="mailto:<?php echo htmlspecialchars($school['email']); ?>"
                                                    class="text-primary">
                                                    <i
                                                        class="fas fa-envelope mr-1"></i><?php echo htmlspecialchars($school['email']); ?>
                                                </a>
                                            </dd>
                                        <?php endif; ?>

                                        <?php if ($school['logo']): ?>
                                            <dt class="col-sm-4">School Logo:</dt>
                                            <dd class="col-sm-8">
                                                <?php
                                                $logoPath = $school['logo'];
                                                if (strpos($logoPath, 'uploads/') === 0) {
                                                    $logoPath = '../' . $logoPath;
                                                }
                                                ?>
                                                <img src="<?php echo htmlspecialchars($logoPath); ?>" alt="School Logo"
                                                    class="img-thumbnail" style="max-width: 150px; max-height: 150px;"
                                                    onerror="this.style.display='none';">
                                            </dd>
                                        <?php endif; ?>

                                        <?php if ($school['tax_clearance_file']): ?>
                                            <dt class="col-sm-4">Tax Clearance:</dt>
                                            <dd class="col-sm-8">
                                                <?php
                                                $taxPath = $school['tax_clearance_file'];
                                                if (strpos($taxPath, 'uploads/') === 0) {
                                                    $taxPath = '../' . $taxPath;
                                                }
                                                $extension = strtolower(pathinfo($taxPath, PATHINFO_EXTENSION));
                                                if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])):
                                                    ?>
                                                    <button type="button" class="btn btn-sm btn-info"
                                                        onclick="openImageModal('<?php echo htmlspecialchars($taxPath); ?>')">
                                                        <i class="fas fa-image mr-1"></i> View Image
                                                    </button>
                                                <?php else: ?>
                                                    <a href="<?php echo htmlspecialchars($taxPath); ?>" target="_blank"
                                                        class="btn btn-sm btn-info">
                                                        <i class="fas fa-file-pdf mr-1"></i> View Document
                                                    </a>
                                                <?php endif; ?>
                                            </dd>
                                        <?php endif; ?>
                                    </dl>
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

    <!-- Image Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tax Clearance Certificate</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalImage" src="" alt="Tax Clearance Certificate" class="img-fluid">
                </div>
            </div>
        </div>
    </div>

    <script>
        function openImageModal(imageSrc) {
            document.getElementById('modalImage').src = imageSrc;
            $('#imageModal').modal('show');
        }
    </script>