<?php
/**
 * Upload Subject Curriculum - School Admin Only
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

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_curriculum') {
    $subjectId = $_POST['subject_id'] ?? 0;
    $sessionId = $_POST['session_id'] ?? 0;
    $termId = $_POST['term_id'] ?? 0;
    $description = trim($_POST['description'] ?? '');
    
    if (empty($subjectId) || empty($sessionId) || empty($termId)) {
        $error = 'Please select subject, session, and term.';
    } elseif (!isset($_FILES['curriculum_file']) || $_FILES['curriculum_file']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Please select a file to upload.';
    } else {
        try {
            // Verify subject, session, and term belong to school
            $stmt = $pdo->prepare("SELECT id FROM subjects WHERE id = ? AND school_id = ?");
            $stmt->execute([$subjectId, $user['school_id']]);
            if (!$stmt->fetch()) {
                throw new Exception('Invalid subject selected.');
            }
            
            $stmt = $pdo->prepare("SELECT id FROM sessions WHERE id = ? AND school_id = ?");
            $stmt->execute([$sessionId, $user['school_id']]);
            if (!$stmt->fetch()) {
                throw new Exception('Invalid session selected.');
            }
            
            $stmt = $pdo->prepare("SELECT id FROM terms WHERE id = ? AND session_id = ?");
            $stmt->execute([$termId, $sessionId]);
            if (!$stmt->fetch()) {
                throw new Exception('Invalid term selected.');
            }
            
            // Handle file upload
            $uploadDir = 'uploads/curriculum/';
            $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain'];
            $maxSize = 10485760; // 10MB
            
            $uploadResult = handleFileUpload($_FILES['curriculum_file'], $uploadDir, $allowedTypes, $maxSize);
            
            if (!$uploadResult['success']) {
                throw new Exception($uploadResult['message']);
            }
            
            // Get file info
            $fileSize = $_FILES['curriculum_file']['size'];
            $fileType = $_FILES['curriculum_file']['type'];
            $fileName = $_FILES['curriculum_file']['name'];
            
            // Insert into database
            $stmt = $pdo->prepare("INSERT INTO subject_curriculum (school_id, subject_id, session_id, term_id, file_path, file_name, file_size, file_type, description, uploaded_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $user['school_id'],
                $subjectId,
                $sessionId,
                $termId,
                $uploadResult['filename'],
                $fileName,
                $fileSize,
                $fileType,
                $description ?: null,
                $user['id']
            ]);
            
            log_action($user['id'], 'UPLOAD_CURRICULUM', "Uploaded curriculum for subject ID: $subjectId, session ID: $sessionId, term ID: $termId");
            $success = 'Curriculum uploaded successfully!';
        } catch (Exception $e) {
            error_log("Error uploading curriculum: " . $e->getMessage());
            $error = $e->getMessage();
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

// Get terms for selected session
$terms = [];
$selectedSessionId = $_GET['session_id'] ?? ($_POST['session_id'] ?? null);
if ($selectedSessionId) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM terms WHERE session_id = ? ORDER BY start_date ASC");
        $stmt->execute([$selectedSessionId]);
        $terms = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error fetching terms: " . $e->getMessage());
    }
}

// Get subjects for this school
$subjects = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM subjects WHERE school_id = ? AND is_active = 1 ORDER BY name ASC");
    $stmt->execute([$user['school_id']]);
    $subjects = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching subjects: " . $e->getMessage());
}

// Get uploaded curriculum files
$curriculumFiles = [];
try {
    $stmt = $pdo->prepare("SELECT sc.*, s.name as subject_name, ses.name as session_name, t.name as term_name 
                           FROM subject_curriculum sc
                           INNER JOIN subjects s ON sc.subject_id = s.id
                           INNER JOIN sessions ses ON sc.session_id = ses.id
                           INNER JOIN terms t ON sc.term_id = t.id
                           WHERE sc.school_id = ?
                           ORDER BY sc.created_at DESC");
    $stmt->execute([$user['school_id']]);
    $curriculumFiles = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching curriculum files: " . $e->getMessage());
}

// Get menu items based on role
$menuItems = getMenuItems('SA', $user['school_id']);

renderAdminLTEHead('Upload Curriculum - Kaduna State SQMS');
?>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
    <?php renderAdminLTESidebar($menuItems, $currentPage, $user, 'SA'); ?>
    
    <div class="content-wrapper">
        <?php renderAdminLTENavbar('Upload Curriculum', $user); ?>
        
        <!-- Content Header -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Upload Subject Curriculum</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="sa_dashboard.php">Home</a></li>
                            <li class="breadcrumb-item active">Upload Curriculum</li>
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
                
                <!-- Upload Form -->
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Upload Curriculum File</h3>
                    </div>
                    <form method="POST" action="" enctype="multipart/form-data">
                        <div class="card-body">
                            <input type="hidden" name="action" value="upload_curriculum">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="upload_subject">Subject <span class="text-danger">*</span></label>
                                        <select class="form-control" id="upload_subject" name="subject_id" required>
                                            <option value="">-- Select Subject --</option>
                                            <?php foreach ($subjects as $subject): ?>
                                                <option value="<?php echo $subject['id']; ?>">
                                                    <?php echo htmlspecialchars($subject['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="upload_session">Session <span class="text-danger">*</span></label>
                                        <select class="form-control" id="upload_session" name="session_id" required onchange="this.form.submit()">
                                            <option value="">-- Select Session --</option>
                                            <?php foreach ($sessions as $session): ?>
                                                <option value="<?php echo $session['id']; ?>" <?php echo ($selectedSessionId == $session['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($session['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="upload_term">Term <span class="text-danger">*</span></label>
                                        <select class="form-control" id="upload_term" name="term_id" required>
                                            <option value="">-- Select Term --</option>
                                            <?php foreach ($terms as $term): ?>
                                                <option value="<?php echo $term['id']; ?>">
                                                    <?php echo htmlspecialchars($term['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="curriculum_file">Curriculum File <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" id="curriculum_file" name="curriculum_file" accept=".pdf,.doc,.docx,.txt" required>
                                        <label class="custom-file-label" for="curriculum_file">Choose file</label>
                                    </div>
                                </div>
                                <small class="form-text text-muted">Accepted formats: PDF, DOC, DOCX, TXT (Max 10MB)</small>
                            </div>
                            <div class="form-group">
                                <label for="curriculum_description">Description</label>
                                <textarea class="form-control" id="curriculum_description" name="description" rows="3" placeholder="Optional description or notes about this curriculum"></textarea>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-upload"></i> Upload Curriculum
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Uploaded Files List -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Uploaded Curriculum Files</h3>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($curriculumFiles)): ?>
                            <div class="p-3 text-center text-muted">
                                <p>No curriculum files uploaded yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Subject</th>
                                            <th>Session</th>
                                            <th>Term</th>
                                            <th>File Name</th>
                                            <th>Size</th>
                                            <th>Uploaded</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($curriculumFiles as $file): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($file['subject_name']); ?></td>
                                                <td><?php echo htmlspecialchars($file['session_name']); ?></td>
                                                <td><?php echo htmlspecialchars($file['term_name']); ?></td>
                                                <td><?php echo htmlspecialchars($file['file_name']); ?></td>
                                                <td><?php echo number_format($file['file_size'] / 1024, 2); ?> KB</td>
                                                <td><?php echo date('M d, Y', strtotime($file['created_at'])); ?></td>
                                                <td>
                                                    <a href="download_curriculum.php?id=<?php echo $file['id']; ?>" class="btn btn-sm btn-primary" target="_blank">
                                                        <i class="fas fa-download"></i> Download
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
<script>
// Update file input label
document.getElementById('curriculum_file').addEventListener('change', function(e) {
    const fileName = e.target.files[0]?.name || 'Choose file';
    e.target.nextElementSibling.textContent = fileName;
});
</script>
</body>
</html>

