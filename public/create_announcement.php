<?php
/**
 * Create Announcement (SMO Only) - AdminLTE
 */

$requiredRole = 'SMO';
require_once __DIR__ . '/../includes/check_auth.php';
require_once __DIR__ . '/../includes/layout.php';

$user = getCurrentUser();
$pdo = getDBConnection();
$currentPage = basename($_SERVER['PHP_SELF']);

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitizeInput($_POST['title'] ?? '');
    $content = sanitizeInput($_POST['content'] ?? '');
    $target_audience = $_POST['target_audience'] ?? 'All';
    $target_school_id = !empty($_POST['target_school_id']) ? (int) $_POST['target_school_id'] : null;
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // Validation
    if (empty($title) || empty($content)) {
        $error = 'Title and Content are required fields.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO announcements (title, content, target_audience, target_school_id, created_by, is_active) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $content, $target_audience, $target_school_id, $user['id'], $is_active]);

            // Log the action
            log_action($user['id'], 'CREATE_ANNOUNCEMENT', "Created announcement: {$title}");

            $success = 'Announcement created successfully!';
            $_POST = [];
        } catch (PDOException $e) {
            error_log("Error creating announcement: " . $e->getMessage());
            $error = 'An error occurred while creating the announcement. Please try again.';
        }
    }
}

// Get all schools for target selection
$schools = [];
try {
    $stmt = $pdo->query("SELECT id, name FROM schools WHERE status = 'Active' ORDER BY name");
    $schools = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching schools: " . $e->getMessage());
}

// Menu items
// Get menu items based on role
$menuItems = getMenuItems('SMO');

renderAdminLTEHead('Create Announcement - Kaduna State SQMS');
?>

<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">
        <?php renderAdminLTESidebar($menuItems, $currentPage, $user, 'SMO'); ?>

        <div class="content-wrapper">
            <?php renderAdminLTENavbar('Create Announcement', $user); ?>

            <!-- Content Header -->
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">Create Announcement</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="smo_dashboard.php">Home</a></li>
                                <li class="breadcrumb-item"><a href="announcements.php">Announcements</a></li>
                                <li class="breadcrumb-item active">Create</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main content -->
            <section class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-8 offset-md-2">
                            <div class="card card-primary">
                                <div class="card-header">
                                    <h3 class="card-title"><i class="fas fa-bullhorn mr-2"></i>New Announcement</h3>
                                    <div class="card-tools">
                                        <a href="announcements.php" class="btn btn-sm btn-secondary">
                                            <i class="fas fa-arrow-left"></i> Back to Announcements
                                        </a>
                                    </div>
                                </div>

                                <?php if ($error): ?>
                                    <div class="alert alert-danger alert-dismissible m-3">
                                        <button type="button" class="close" data-dismiss="alert"
                                            aria-hidden="true">&times;</button>
                                        <i class="icon fas fa-ban"></i> <?php echo htmlspecialchars($error); ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ($success): ?>
                                    <div class="alert alert-success alert-dismissible m-3">
                                        <button type="button" class="close" data-dismiss="alert"
                                            aria-hidden="true">&times;</button>
                                        <i class="icon fas fa-check"></i> <?php echo htmlspecialchars($success); ?>
                                    </div>
                                <?php endif; ?>

                                <form method="POST" action="">
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label for="title">Title <span class="text-danger">*</span></label>
                                            <input type="text" id="title" name="title" required class="form-control"
                                                placeholder="Enter announcement title"
                                                value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                                        </div>

                                        <div class="form-group">
                                            <label for="content">Content <span class="text-danger">*</span></label>
                                            <textarea id="content" name="content" rows="8" required class="form-control"
                                                placeholder="Enter announcement content"><?php echo isset($_POST['content']) ? htmlspecialchars($_POST['content']) : ''; ?></textarea>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="target_audience">Target Audience <span
                                                            class="text-danger">*</span></label>
                                                    <select id="target_audience" name="target_audience" required
                                                        class="form-control">
                                                        <option value="All" <?php echo (isset($_POST['target_audience']) && $_POST['target_audience'] === 'All') ? 'selected' : ''; ?>>
                                                            All Users</option>
                                                        <option value="SMO" <?php echo (isset($_POST['target_audience']) && $_POST['target_audience'] === 'SMO') ? 'selected' : ''; ?>>
                                                            SMO Only</option>
                                                        <option value="SA" <?php echo (isset($_POST['target_audience']) && $_POST['target_audience'] === 'SA') ? 'selected' : ''; ?>>
                                                            School Administrators Only</option>
                                                        <option value="School" <?php echo (isset($_POST['target_audience']) && $_POST['target_audience'] === 'School') ? 'selected' : ''; ?>>
                                                            Specific School</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-md-6" id="schoolSelectContainer"
                                                style="display: <?php echo (isset($_POST['target_audience']) && $_POST['target_audience'] === 'School') ? 'block' : 'none'; ?>;">
                                                <div class="form-group">
                                                    <label for="target_school_id">Select School</label>
                                                    <select id="target_school_id" name="target_school_id"
                                                        class="form-control">
                                                        <option value="">Select a school</option>
                                                        <?php foreach ($schools as $school): ?>
                                                            <option value="<?php echo $school['id']; ?>" <?php echo (isset($_POST['target_school_id']) && $_POST['target_school_id'] == $school['id']) ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($school['name']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="is_active"
                                                    name="is_active" value="1" <?php echo (!isset($_POST['is_active']) || $_POST['is_active']) ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="is_active">
                                                    Active (announcement will be visible to target audience)
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="card-footer">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save mr-2"></i>Create Announcement
                                        </button>
                                        <a href="announcements.php" class="btn btn-secondary">
                                            <i class="fas fa-times mr-2"></i>Cancel
                                        </a>
                                    </div>
                                </form>
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
        // Show/hide school select based on target audience
        document.getElementById('target_audience').addEventListener('change', function () {
            const schoolContainer = document.getElementById('schoolSelectContainer');
            const schoolSelect = document.getElementById('target_school_id');
            if (this.value === 'School') {
                schoolContainer.style.display = 'block';
                schoolSelect.required = true;
            } else {
                schoolContainer.style.display = 'none';
                schoolSelect.required = false;
                schoolSelect.value = '';
            }
        });
    </script>