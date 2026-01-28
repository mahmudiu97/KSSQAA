<?php
/**
 * AdminLTE Base Layout
 * Reusable layout components for AdminLTE template
 */

/**
 * Render AdminLTE Head Section
 */
function renderAdminLTEHead($pageTitle = 'Kaduna State SQMS')
{
    ?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?php echo htmlspecialchars($pageTitle); ?></title>

        <!-- Google Font: Source Sans Pro -->
        <link rel="stylesheet"
            href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
        <!-- Font Awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <!-- AdminLTE -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
        <!-- Chart.js -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
        <!-- Custom CSS -->
        <style>
            .main-header .navbar-brand img {
                height: 40px;
                width: auto;
            }

            .user-panel img {
                width: 45px;
                height: 45px;
            }

            /* Ensure proper layout structure */
            .wrapper {
                position: relative;
            }

            .content-wrapper {
                margin-left: 250px;
            }

            @media (max-width: 991.98px) {
                .content-wrapper {
                    margin-left: 0;
                }
            }

            .sidebar-mini.sidebar-collapse .content-wrapper {
                margin-left: 57px;
            }

            @media (max-width: 991.98px) {
                .sidebar-mini.sidebar-collapse .content-wrapper {
                    margin-left: 0;
                }
            }
        </style>
    </head>
    <?php
}

/**
 * Render AdminLTE Sidebar
 */
function renderAdminLTESidebar($menuItems, $currentPage, $user, $role = 'SMO')
{
    $sidebarColor = $role === 'SMO' ? 'primary' : 'success';

    // Fetch school name for SA users
    $schoolName = null;
    if ($role === 'SA' && !empty($user['school_id'])) {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("SELECT name FROM schools WHERE id = ?");
            $stmt->execute([$user['school_id']]);
            $school = $stmt->fetch();
            if ($school) {
                $schoolName = $school['name'];
            }
        } catch (PDOException $e) {
            error_log("Error fetching school name for sidebar: " . $e->getMessage());
        }
    }
    ?>
    <aside class="main-sidebar sidebar-dark-<?php echo $sidebarColor; ?> elevation-4">
        <!-- Brand Logo -->
        <a href="<?php echo $role === 'SMO' ? 'smo_dashboard.php' : 'sa_dashboard.php'; ?>" class="brand-link">
            <img src="../logo.png" alt="Kaduna State SQMS Logo" class="brand-image img-circle elevation-3"
                style="opacity: .8; height: 40px; width: auto;">
            <span class="brand-text font-weight-light">Kaduna SQMS</span>
        </a>

        <!-- Sidebar -->
        <div class="sidebar">
            <!-- Sidebar user panel -->
            <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                <div class="image">
                    <div class="img-circle elevation-2 bg-<?php echo $sidebarColor; ?>"
                        style="width: 45px; height: 45px; line-height: 45px; text-align: center; color: white; font-weight: bold;">
                        <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                    </div>
                </div>
                <div class="info">
                    <?php if ($schoolName): ?>
                        <a href="school_profile.php?id=<?php echo $user['school_id']; ?>" class="d-block"
                            style="font-weight: 600; color: #fff; font-size: 13px; margin-bottom: 2px;"
                            title="<?php echo htmlspecialchars($schoolName); ?>">
                            <i
                                class="fas fa-school mr-1"></i><?php echo htmlspecialchars(strlen($schoolName) > 25 ? substr($schoolName, 0, 25) . '...' : $schoolName); ?>
                        </a>
                    <?php endif; ?>
                    <a href="#" class="d-block"><?php echo htmlspecialchars($user['full_name']); ?></a>
                    <small
                        class="text-muted"><?php echo htmlspecialchars($role === 'SMO' ? 'System Manager' : 'School Administrator'); ?></small>
                </div>
            </div>

            <!-- Sidebar Menu -->
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                    <?php foreach ($menuItems as $item): ?>
                        <?php
                        $isActive = ($currentPage === basename($item['url']));
                        $iconClass = 'fas fa-circle';
                        // Use icon from menu item or map from name
                        if (isset($item['icon'])) {
                            $iconClass = 'fas ' . $item['icon'];
                        } else {
                            $iconClass = 'fas ' . mapIconToFontAwesome('', $item['name']);
                        }
                        ?>
                        <li class="nav-item">
                            <a href="<?php echo htmlspecialchars($item['url']); ?>"
                                class="nav-link <?php echo $isActive ? 'active' : ''; ?>">
                                <i class="nav-icon <?php echo $iconClass; ?>"></i>
                                <p><?php echo htmlspecialchars($item['name']); ?></p>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </nav>
        </div>
    </aside>
    <?php
}

/**
 * Get menu items based on user role
 * @param string $role User role (SMO or SA)
 * @param int|null $schoolId School ID for SA users
 * @return array Menu items array
 */
function getMenuItems($role, $schoolId = null)
{
    if ($role === 'SMO') {
        return [
            ['name' => 'Dashboard', 'url' => 'smo_dashboard.php', 'icon' => 'fa-tachometer-alt'],
            ['name' => 'Approve Schools', 'url' => 'approve_schools.php', 'icon' => 'fa-check-circle'],
            ['name' => 'All Schools', 'url' => 'schools.php', 'icon' => 'fa-school'],
            ['name' => 'Users', 'url' => 'users.php', 'icon' => 'fa-users'],
            ['name' => 'Reports', 'url' => 'reports.php', 'icon' => 'fa-file-alt'],
            ['name' => 'Attendance Reports', 'url' => 'view_attendance_reports.php', 'icon' => 'fa-calendar-check'],
            ['name' => 'Curriculum Reports', 'url' => 'view_curriculum_reports.php', 'icon' => 'fa-book'],
            ['name' => 'Announcements', 'url' => 'announcements.php', 'icon' => 'fa-bullhorn'],
            ['name' => 'Audit Logs', 'url' => 'view_logs.php', 'icon' => 'fa-clipboard-list'],
        ];
    } else {
        // SA (School Administrator) menu
        $menu = [
            ['name' => 'Dashboard', 'url' => 'sa_dashboard.php', 'icon' => 'fa-tachometer-alt'],
            ['name' => 'Sessions & Terms', 'url' => 'manage_sessions.php', 'icon' => 'fa-calendar-alt'],
            ['name' => 'Add Student', 'url' => 'add_student.php', 'icon' => 'fa-user-plus'],
            ['name' => 'View Students', 'url' => 'view_students.php', 'icon' => 'fa-user-graduate'],
            ['name' => 'Add Staff', 'url' => 'add_staff.php', 'icon' => 'fa-user-tie'],
            ['name' => 'View Staff', 'url' => 'view_staff.php', 'icon' => 'fa-users-cog'],
            ['name' => 'Staff Attendance', 'url' => 'mark_attendance.php', 'icon' => 'fa-calendar-check'],
            ['name' => 'Manage Subjects', 'url' => 'manage_subjects.php', 'icon' => 'fa-book'],
            ['name' => 'Upload Curriculum', 'url' => 'upload_curriculum.php', 'icon' => 'fa-upload'],
            ['name' => 'Announcements', 'url' => 'announcements.php', 'icon' => 'fa-bullhorn'],
        ];
        
        if ($schoolId) {
            $menu[] = ['name' => 'School Profile', 'url' => 'school_profile.php?id=' . $schoolId, 'icon' => 'fa-building'];
        }
        
        return $menu;
    }
}

/**
 * Map SVG icon paths to Font Awesome icons
 */
function mapIconToFontAwesome($svgPath, $itemName)
{
    $iconMap = [
        'Dashboard' => 'fa-tachometer-alt',
        'Approve Schools' => 'fa-check-circle',
        'All Schools' => 'fa-school',
        'Users' => 'fa-users',
        'Reports' => 'fa-file-alt',
        'Announcements' => 'fa-bullhorn',
        'Audit Logs' => 'fa-clipboard-list',
        'Add Student' => 'fa-user-plus',
        'View Students' => 'fa-user-graduate',
        'Add Staff' => 'fa-user-tie',
        'View Staff' => 'fa-users-cog',
        'School Profile' => 'fa-building',
        'Sessions & Terms' => 'fa-calendar-alt',
        'Staff Attendance' => 'fa-calendar-check',
        'Manage Subjects' => 'fa-book',
        'Upload Curriculum' => 'fa-upload',
        'Attendance Reports' => 'fa-calendar-check',
        'Curriculum Reports' => 'fa-book',
    ];

    return $iconMap[$itemName] ?? 'fa-circle';
}

/**
 * Render AdminLTE Navbar
 */
function renderAdminLTENavbar($pageTitle, $user)
{
    ?>
    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <!-- Left navbar links -->
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="<?php echo getCurrentUserRole() === 'SMO' ? 'smo_dashboard.php' : 'sa_dashboard.php'; ?>"
                    class="nav-link">Home</a>
            </li>
        </ul>

        <!-- Right navbar links -->
        <ul class="navbar-nav ml-auto">
            <!-- User Dropdown Menu -->
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#">
                    <i class="far fa-user"></i>
                    <span class="ml-2"><?php echo htmlspecialchars($user['full_name']); ?></span>
                    <i class="fas fa-caret-down ml-1"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                    <span class="dropdown-item dropdown-header"><?php echo htmlspecialchars($user['role']); ?></span>
                    <div class="dropdown-divider"></div>
                    <a href="change_password.php" class="dropdown-item">
                        <i class="fas fa-key mr-2"></i> Change Password
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="logout.php" class="dropdown-item">
                        <i class="fas fa-sign-out-alt mr-2"></i> Logout
                    </a>
                </div>
            </li>
        </ul>
    </nav>
    <?php
}

/**
 * Render AdminLTE Footer
 */
function renderAdminLTEFooter()
{
    ?>
    <footer class="main-footer">
        <strong>Copyright &copy; <?php echo date('Y'); ?> <a href="#">Kaduna State SQMS</a>.</strong>
        All rights reserved.
        <div class="float-right d-none d-sm-inline-block">
            <b>Version</b> 1.0.0
        </div>
    </footer>
    <?php
}

/**
 * Render AdminLTE Scripts
 */
function renderAdminLTEScripts()
{
    ?>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap 4 -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AdminLTE App -->
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
    </body>

    </html>
    <?php
}

