<?php
/**
 * Manage Sessions and Terms - School Admin Only
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
        if ($_POST['action'] === 'create_session') {
            $name = trim($_POST['name'] ?? '');
            $startDate = $_POST['start_date'] ?? '';
            $endDate = $_POST['end_date'] ?? '';
            
            if (empty($name) || empty($startDate) || empty($endDate)) {
                $error = 'All fields are required.';
            } elseif ($startDate >= $endDate) {
                $error = 'End date must be after start date.';
            } else {
                try {
                    // Deactivate other sessions for this school
                    $stmt = $pdo->prepare("UPDATE sessions SET is_active = 0 WHERE school_id = ?");
                    $stmt->execute([$user['school_id']]);
                    
                    $stmt = $pdo->prepare("INSERT INTO sessions (school_id, name, start_date, end_date, is_active) VALUES (?, ?, ?, ?, 1)");
                    $stmt->execute([$user['school_id'], $name, $startDate, $endDate]);
                    $sessionId = $pdo->lastInsertId();
                    
                    log_action($user['id'], 'CREATE_SESSION', "Created session: $name");
                    $success = 'Session created successfully!';
                } catch (PDOException $e) {
                    error_log("Error creating session: " . $e->getMessage());
                    $error = 'Failed to create session. Please try again.';
                }
            }
        } elseif ($_POST['action'] === 'create_term') {
            $sessionId = $_POST['session_id'] ?? 0;
            $name = trim($_POST['name'] ?? '');
            $startDate = $_POST['start_date'] ?? '';
            $endDate = $_POST['end_date'] ?? '';
            
            if (empty($sessionId) || empty($name) || empty($startDate) || empty($endDate)) {
                $error = 'All fields are required.';
            } elseif ($startDate >= $endDate) {
                $error = 'End date must be after start date.';
            } else {
                try {
                    // Verify session belongs to school
                    $stmt = $pdo->prepare("SELECT id FROM sessions WHERE id = ? AND school_id = ?");
                    $stmt->execute([$sessionId, $user['school_id']]);
                    if (!$stmt->fetch()) {
                        $error = 'Invalid session selected.';
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO terms (session_id, name, start_date, end_date, is_active) VALUES (?, ?, ?, ?, 1)");
                        $stmt->execute([$sessionId, $name, $startDate, $endDate]);
                        
                        log_action($user['id'], 'CREATE_TERM', "Created term: $name for session ID: $sessionId");
                        $success = 'Term created successfully!';
                    }
                } catch (PDOException $e) {
                    error_log("Error creating term: " . $e->getMessage());
                    $error = 'Failed to create term. Please try again.';
                }
            }
        }
    }
}

// Get sessions for this school
$sessions = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM sessions WHERE school_id = ? ORDER BY start_date DESC");
    $stmt->execute([$user['school_id']]);
    $sessions = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching sessions: " . $e->getMessage());
}

// Get terms for each session
$termsBySession = [];
foreach ($sessions as $session) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM terms WHERE session_id = ? ORDER BY start_date ASC");
        $stmt->execute([$session['id']]);
        $termsBySession[$session['id']] = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error fetching terms: " . $e->getMessage());
        $termsBySession[$session['id']] = [];
    }
}

// Get menu items based on role
$menuItems = getMenuItems('SA', $user['school_id']);

renderAdminLTEHead('Manage Sessions & Terms - Kaduna State SQMS');
?>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
    <?php renderAdminLTESidebar($menuItems, $currentPage, $user, 'SA'); ?>
    
    <div class="content-wrapper">
        <?php renderAdminLTENavbar('Manage Sessions & Terms', $user); ?>
        
        <!-- Content Header -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Manage Sessions & Terms</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="sa_dashboard.php">Home</a></li>
                            <li class="breadcrumb-item active">Sessions & Terms</li>
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
                    <!-- Create Session Form -->
                    <div class="col-md-6">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">Create New Session</h3>
                            </div>
                            <form method="POST" action="">
                                <div class="card-body">
                                    <input type="hidden" name="action" value="create_session">
                                    <div class="form-group">
                                        <label for="session_name">Session Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="session_name" name="name" placeholder="e.g., 2024/2025" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="session_start">Start Date <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="session_start" name="start_date" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="session_end">End Date <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="session_end" name="end_date" required>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <button type="submit" class="btn btn-primary">Create Session</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Create Term Form -->
                    <div class="col-md-6">
                        <div class="card card-success">
                            <div class="card-header">
                                <h3 class="card-title">Create New Term</h3>
                            </div>
                            <form method="POST" action="">
                                <div class="card-body">
                                    <input type="hidden" name="action" value="create_term">
                                    <div class="form-group">
                                        <label for="term_session">Select Session <span class="text-danger">*</span></label>
                                        <select class="form-control" id="term_session" name="session_id" required>
                                            <option value="">-- Select Session --</option>
                                            <?php foreach ($sessions as $session): ?>
                                                <option value="<?php echo $session['id']; ?>">
                                                    <?php echo htmlspecialchars($session['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="term_name">Term Name <span class="text-danger">*</span></label>
                                        <select class="form-control" id="term_name" name="name" required>
                                            <option value="">-- Select Term --</option>
                                            <option value="First Term">First Term</option>
                                            <option value="Second Term">Second Term</option>
                                            <option value="Third Term">Third Term</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="term_start">Start Date <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="term_start" name="start_date" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="term_end">End Date <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="term_end" name="end_date" required>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <button type="submit" class="btn btn-success">Create Term</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Sessions List -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Sessions & Terms</h3>
                            </div>
                            <div class="card-body p-0">
                                <?php if (empty($sessions)): ?>
                                    <div class="p-3 text-center text-muted">
                                        <p>No sessions created yet. Create your first session above.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Session</th>
                                                    <th>Start Date</th>
                                                    <th>End Date</th>
                                                    <th>Status</th>
                                                    <th>Terms</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($sessions as $session): ?>
                                                    <tr>
                                                        <td>
                                                            <strong><?php echo htmlspecialchars($session['name']); ?></strong>
                                                            <?php if ($session['is_active']): ?>
                                                                <span class="badge badge-success ml-2">Active</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?php echo date('M d, Y', strtotime($session['start_date'])); ?></td>
                                                        <td><?php echo date('M d, Y', strtotime($session['end_date'])); ?></td>
                                                        <td>
                                                            <?php if ($session['is_active']): ?>
                                                                <span class="badge badge-success">Active</span>
                                                            <?php else: ?>
                                                                <span class="badge badge-secondary">Inactive</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php 
                                                            $terms = $termsBySession[$session['id']] ?? [];
                                                            if (empty($terms)): 
                                                            ?>
                                                                <span class="text-muted">No terms</span>
                                                            <?php else: ?>
                                                                <ul class="mb-0">
                                                                    <?php foreach ($terms as $term): ?>
                                                                        <li>
                                                                            <?php echo htmlspecialchars($term['name']); ?>
                                                                            (<?php echo date('M d', strtotime($term['start_date'])); ?> - <?php echo date('M d, Y', strtotime($term['end_date'])); ?>)
                                                                        </li>
                                                                    <?php endforeach; ?>
                                                                </ul>
                                                            <?php endif; ?>
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
                </div>
            </div>
        </section>
    </div>
    
    <?php renderAdminLTEFooter(); ?>
</div>

<?php renderAdminLTEScripts(); ?>
</body>
</html>

