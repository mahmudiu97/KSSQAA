<?php
/**
 * Manage Subjects - School Admin Only
 */

$requiredRole = 'SA';
require_once __DIR__ . '/../includes/check_auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/functions.php';

$user = getCurrentUser();
$pdo = getDBConnection();

if (!$user['school_id']) {
    header('Location: sa_dashboard.php');
    exit;
}

$currentPage = basename($_SERVER['PHP_SELF']);
$success = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'create_subject') {
            $name = trim($_POST['name'] ?? '');
            $code = trim($_POST['code'] ?? '');
            $description = trim($_POST['description'] ?? '');
            
            if (empty($name)) {
                $error = 'Subject name is required.';
            } else {
                try {
                    $stmt = $pdo->prepare("INSERT INTO subjects (school_id, name, code, description, is_active) VALUES (?, ?, ?, ?, 1)");
                    $stmt->execute([$user['school_id'], $name, $code ?: null, $description ?: null]);
                    
                    log_action($user['id'], 'CREATE_SUBJECT', "Created subject: $name");
                    $success = 'Subject created successfully!';
                } catch (PDOException $e) {
                    error_log("Error creating subject: " . $e->getMessage());
                    if ($e->getCode() == 23000) {
                        $error = 'A subject with this name or code already exists.';
                    } else {
                        $error = 'Failed to create subject. Please try again.';
                    }
                }
            }
        } elseif ($_POST['action'] === 'update_subject') {
            $subjectId = $_POST['subject_id'] ?? 0;
            $name = trim($_POST['name'] ?? '');
            $code = trim($_POST['code'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $isActive = isset($_POST['is_active']) ? 1 : 0;
            
            if (empty($subjectId) || empty($name)) {
                $error = 'Subject ID and name are required.';
            } else {
                try {
                    // Verify subject belongs to school
                    $stmt = $pdo->prepare("SELECT id FROM subjects WHERE id = ? AND school_id = ?");
                    $stmt->execute([$subjectId, $user['school_id']]);
                    if (!$stmt->fetch()) {
                        $error = 'Invalid subject selected.';
                    } else {
                        $stmt = $pdo->prepare("UPDATE subjects SET name = ?, code = ?, description = ?, is_active = ? WHERE id = ?");
                        $stmt->execute([$name, $code ?: null, $description ?: null, $isActive, $subjectId]);
                        
                        log_action($user['id'], 'UPDATE_SUBJECT', "Updated subject: $name");
                        $success = 'Subject updated successfully!';
                    }
                } catch (PDOException $e) {
                    error_log("Error updating subject: " . $e->getMessage());
                    $error = 'Failed to update subject. Please try again.';
                }
            }
        }
    }
}

// Get subjects for this school
$subjects = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM subjects WHERE school_id = ? ORDER BY name ASC");
    $stmt->execute([$user['school_id']]);
    $subjects = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching subjects: " . $e->getMessage());
}

// Get menu items based on role
$menuItems = getMenuItems('SA', $user['school_id']);

renderAdminLTEHead('Manage Subjects - Kaduna State SQMS');
?>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
    <?php renderAdminLTESidebar($menuItems, $currentPage, $user, 'SA'); ?>
    
    <div class="content-wrapper">
        <?php renderAdminLTENavbar('Manage Subjects', $user); ?>
        
        <!-- Content Header -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Manage Subjects</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="sa_dashboard.php">Home</a></li>
                            <li class="breadcrumb-item active">Subjects</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <div class="row">
                    <!-- Create Subject Form -->
                    <div class="col-md-6">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">Add New Subject</h3>
                            </div>
                            <form method="POST" action="">
                                <div class="card-body">
                                    <input type="hidden" name="action" value="create_subject">
                                    <div class="form-group">
                                        <label for="subject_name">Subject Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="subject_name" name="name" placeholder="e.g., Mathematics" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="subject_code">Subject Code</label>
                                        <input type="text" class="form-control" id="subject_code" name="code" placeholder="e.g., MATH" maxlength="20">
                                    </div>
                                    <div class="form-group">
                                        <label for="subject_description">Description</label>
                                        <textarea class="form-control" id="subject_description" name="description" rows="3" placeholder="Optional description"></textarea>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <button type="submit" class="btn btn-primary">Add Subject</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Subjects List -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Subjects List</h3>
                            </div>
                            <div class="card-body p-0">
                                <?php if (empty($subjects)): ?>
                                    <div class="p-3 text-center text-muted">
                                        <p>No subjects added yet. Add your first subject on the left.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Name</th>
                                                    <th>Code</th>
                                                    <th>Status</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($subjects as $subject): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($subject['name']); ?></td>
                                                        <td><?php echo htmlspecialchars($subject['code'] ?? 'N/A'); ?></td>
                                                        <td>
                                                            <?php if ($subject['is_active']): ?>
                                                                <span class="badge badge-success">Active</span>
                                                            <?php else: ?>
                                                                <span class="badge badge-secondary">Inactive</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <button type="button" class="btn btn-sm btn-info" data-toggle="modal" data-target="#editSubjectModal<?php echo $subject['id']; ?>">
                                                                <i class="fas fa-edit"></i> Edit
                                                            </button>
                                                        </td>
                                                    </tr>
                                                    
                                                    <!-- Edit Modal -->
                                                    <div class="modal fade" id="editSubjectModal<?php echo $subject['id']; ?>" tabindex="-1">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <form method="POST" action="">
                                                                    <div class="modal-header">
                                                                        <h4 class="modal-title">Edit Subject</h4>
                                                                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        <input type="hidden" name="action" value="update_subject">
                                                                        <input type="hidden" name="subject_id" value="<?php echo $subject['id']; ?>">
                                                                        <div class="form-group">
                                                                            <label>Subject Name <span class="text-danger">*</span></label>
                                                                            <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($subject['name']); ?>" required>
                                                                        </div>
                                                                        <div class="form-group">
                                                                            <label>Subject Code</label>
                                                                            <input type="text" class="form-control" name="code" value="<?php echo htmlspecialchars($subject['code'] ?? ''); ?>" maxlength="20">
                                                                        </div>
                                                                        <div class="form-group">
                                                                            <label>Description</label>
                                                                            <textarea class="form-control" name="description" rows="3"><?php echo htmlspecialchars($subject['description'] ?? ''); ?></textarea>
                                                                        </div>
                                                                        <div class="form-group">
                                                                            <div class="form-check">
                                                                                <input type="checkbox" class="form-check-input" name="is_active" id="active<?php echo $subject['id']; ?>" <?php echo $subject['is_active'] ? 'checked' : ''; ?>>
                                                                                <label class="form-check-label" for="active<?php echo $subject['id']; ?>">Active</label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                                        <button type="submit" class="btn btn-primary">Update Subject</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
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
</body>
</html>

