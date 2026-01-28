<?php
/**
 * View Subject Curriculum Reports - SMO Only
 */

$requiredRole = 'SMO';
require_once __DIR__ . '/../includes/check_auth.php';
require_once __DIR__ . '/../includes/layout.php';

$user = getCurrentUser();
$pdo = getDBConnection();

$currentPage = basename($_SERVER['PHP_SELF']);

// Get filter parameters
$schoolId = $_GET['school_id'] ?? null;
$sessionId = $_GET['session_id'] ?? null;
$termId = $_GET['term_id'] ?? null;
$subjectId = $_GET['subject_id'] ?? null;

// Get all schools
$schools = [];
try {
    $stmt = $pdo->query("SELECT id, name FROM schools WHERE status = 'Active' ORDER BY name ASC");
    $schools = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching schools: " . $e->getMessage());
}

// Get sessions for selected school
$sessions = [];
if ($schoolId) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM sessions WHERE school_id = ? ORDER BY start_date DESC");
        $stmt->execute([$schoolId]);
        $sessions = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error fetching sessions: " . $e->getMessage());
    }
}

// Get terms for selected session
$terms = [];
if ($sessionId) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM terms WHERE session_id = ? ORDER BY start_date ASC");
        $stmt->execute([$sessionId]);
        $terms = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error fetching terms: " . $e->getMessage());
    }
}

// Get subjects for selected school
$subjects = [];
if ($schoolId) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM subjects WHERE school_id = ? ORDER BY name ASC");
        $stmt->execute([$schoolId]);
        $subjects = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error fetching subjects: " . $e->getMessage());
    }
}

// Get curriculum files
$curriculumFiles = [];
if ($schoolId) {
    try {
        $whereClause = "sc.school_id = ?";
        $params = [$schoolId];
        
        if ($sessionId) {
            $whereClause .= " AND sc.session_id = ?";
            $params[] = $sessionId;
        }
        
        if ($termId) {
            $whereClause .= " AND sc.term_id = ?";
            $params[] = $termId;
        }
        
        if ($subjectId) {
            $whereClause .= " AND sc.subject_id = ?";
            $params[] = $subjectId;
        }
        
        $stmt = $pdo->prepare("SELECT sc.*, s.name as subject_name, s.code as subject_code,
                               sch.name as school_name, ses.name as session_name, t.name as term_name,
                               u.full_name as uploaded_by_name
                               FROM subject_curriculum sc
                               INNER JOIN subjects s ON sc.subject_id = s.id
                               INNER JOIN schools sch ON sc.school_id = sch.id
                               INNER JOIN sessions ses ON sc.session_id = ses.id
                               INNER JOIN terms t ON sc.term_id = t.id
                               LEFT JOIN users u ON sc.uploaded_by = u.id
                               WHERE $whereClause
                               ORDER BY sc.created_at DESC");
        $stmt->execute($params);
        $curriculumFiles = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error fetching curriculum files: " . $e->getMessage());
    }
}

// Get menu items based on role
$menuItems = getMenuItems('SMO');

renderAdminLTEHead('Curriculum Reports - Kaduna State SQMS');
?>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
    <?php renderAdminLTESidebar($menuItems, $currentPage, $user, 'SMO'); ?>
    
    <div class="content-wrapper">
        <?php renderAdminLTENavbar('Curriculum Reports', $user); ?>
        
        <!-- Content Header -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Subject Curriculum Reports</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="smo_dashboard.php">Home</a></li>
                            <li class="breadcrumb-item active">Curriculum Reports</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <!-- Filter Form -->
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Filter Reports</h3>
                    </div>
                    <form method="GET" action="">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="filter_school">School</label>
                                        <select class="form-control" id="filter_school" name="school_id" onchange="this.form.submit()">
                                            <option value="">-- All Schools --</option>
                                            <?php foreach ($schools as $school): ?>
                                                <option value="<?php echo $school['id']; ?>" <?php echo ($schoolId == $school['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($school['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="filter_session">Session</label>
                                        <select class="form-control" id="filter_session" name="session_id" onchange="this.form.submit()">
                                            <option value="">-- All Sessions --</option>
                                            <?php foreach ($sessions as $session): ?>
                                                <option value="<?php echo $session['id']; ?>" <?php echo ($sessionId == $session['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($session['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="filter_term">Term</label>
                                        <select class="form-control" id="filter_term" name="term_id" onchange="this.form.submit()">
                                            <option value="">-- All Terms --</option>
                                            <?php foreach ($terms as $term): ?>
                                                <option value="<?php echo $term['id']; ?>" <?php echo ($termId == $term['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($term['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="filter_subject">Subject</label>
                                        <select class="form-control" id="filter_subject" name="subject_id" onchange="this.form.submit()">
                                            <option value="">-- All Subjects --</option>
                                            <?php foreach ($subjects as $subject): ?>
                                                <option value="<?php echo $subject['id']; ?>" <?php echo ($subjectId == $subject['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($subject['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>&nbsp;</label>
                                        <button type="submit" class="btn btn-primary btn-block">
                                            <i class="fas fa-filter"></i> Apply Filters
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Curriculum Files Table -->
                <?php if (!empty($curriculumFiles)): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Curriculum Files (<?php echo count($curriculumFiles); ?>)</h3>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th>School</th>
                                            <th>Subject</th>
                                            <th>Session</th>
                                            <th>Term</th>
                                            <th>File Name</th>
                                            <th>Size</th>
                                            <th>Uploaded By</th>
                                            <th>Upload Date</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($curriculumFiles as $file): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($file['school_name']); ?></td>
                                                <td>
                                                    <?php echo htmlspecialchars($file['subject_name']); ?>
                                                    <?php if ($file['subject_code']): ?>
                                                        <small class="text-muted">(<?php echo htmlspecialchars($file['subject_code']); ?>)</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($file['session_name']); ?></td>
                                                <td><?php echo htmlspecialchars($file['term_name']); ?></td>
                                                <td><?php echo htmlspecialchars($file['file_name']); ?></td>
                                                <td><?php echo number_format($file['file_size'] / 1024, 2); ?> KB</td>
                                                <td><?php echo htmlspecialchars($file['uploaded_by_name'] ?? 'N/A'); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($file['created_at'])); ?></td>
                                                <td>
                                                    <a href="download_curriculum.php?id=<?php echo $file['id']; ?>" class="btn btn-sm btn-primary" target="_blank">
                                                        <i class="fas fa-download"></i> Download
                                                    </a>
                                                    <?php if ($file['description']): ?>
                                                        <button type="button" class="btn btn-sm btn-info" data-toggle="modal" data-target="#descModal<?php echo $file['id']; ?>">
                                                            <i class="fas fa-info-circle"></i> Info
                                                        </button>
                                                        
                                                        <!-- Description Modal -->
                                                        <div class="modal fade" id="descModal<?php echo $file['id']; ?>" tabindex="-1">
                                                            <div class="modal-dialog">
                                                                <div class="modal-content">
                                                                    <div class="modal-header">
                                                                        <h4 class="modal-title">Description</h4>
                                                                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        <p><?php echo nl2br(htmlspecialchars($file['description'])); ?></p>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php elseif ($schoolId): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No curriculum files found for the selected criteria.
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Please select a school to view curriculum reports.
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
    
    <?php renderAdminLTEFooter(); ?>
</div>

<?php renderAdminLTEScripts(); ?>
</body>
</html>

