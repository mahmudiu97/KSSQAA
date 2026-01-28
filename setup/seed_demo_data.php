<?php
/**
 * Demo Data Seeder
 * 
 * This script populates the database with sample data for testing and demonstration.
 * Run this script once after setting up the database.
 * 
 * Access: http://localhost/KSSQAA/setup/seed_demo_data.php
 * 
 * WARNING: This will add sample data to your database. 
 * Delete this file after use for security.
 */

require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = getDBConnection();

// Start transaction
$pdo->beginTransaction();

try {
    echo "<h2>Seeding Demo Data...</h2>";
    echo "<pre>";
    
    // 1. Seed Wards (Kaduna North LGA)
    echo "1. Seeding Wards...\n";
    $wards = [
        ['name' => 'Badarawa', 'lga' => 'Kaduna North', 'description' => 'Badarawa Ward'],
        ['name' => 'Dadi Riba', 'lga' => 'Kaduna North', 'description' => 'Dadi Riba Ward'],
        ['name' => 'Hayin Banki', 'lga' => 'Kaduna North', 'description' => 'Hayin Banki Ward'],
        ['name' => 'Kabala', 'lga' => 'Kaduna North', 'description' => 'Kabala Ward'],
        ['name' => 'Kawo', 'lga' => 'Kaduna North', 'description' => 'Kawo Ward'],
        ['name' => 'Maiburiji', 'lga' => 'Kaduna North', 'description' => 'Maiburiji Ward'],
        ['name' => 'Sardauna', 'lga' => 'Kaduna North', 'description' => 'Sardauna Ward'],
        ['name' => 'Shaba', 'lga' => 'Kaduna North', 'description' => 'Shaba Ward'],
        ['name' => 'Unguwan Dosa', 'lga' => 'Kaduna North', 'description' => 'Unguwan Dosa Ward'],
        ['name' => 'Unguwar Rimi', 'lga' => 'Kaduna North', 'description' => 'Unguwar Rimi Ward'],
        ['name' => 'Unguwar Sarki', 'lga' => 'Kaduna North', 'description' => 'Unguwar Sarki Ward'],
        ['name' => 'Unguwar Shanu', 'lga' => 'Kaduna North', 'description' => 'Unguwar Shanu Ward'],
        ['name' => 'Unguwar kanawa', 'lga' => 'Kaduna North', 'description' => 'Unguwar kanawa Ward'],
    ];
    
    $wardIds = [];
    foreach ($wards as $ward) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO wards (name, lga, description) VALUES (?, ?, ?)");
        $stmt->execute([$ward['name'], $ward['lga'], $ward['description']]);
        $stmt = $pdo->prepare("SELECT id FROM wards WHERE name = ?");
        $stmt->execute([$ward['name']]);
        $wardIds[$ward['name']] = $stmt->fetch()['id'];
    }
    echo "   ✓ Wards seeded\n";
    
    // 2. Seed Schools
    echo "2. Seeding Schools...\n";
    $schools = [
        ['name' => 'Government Secondary School Kaduna', 'lga' => 'Kaduna North', 'cac_number' => 'CAC-001', 'status' => 'Active', 'ward' => 'Kawo'],
        ['name' => 'St. Mary\'s Secondary School', 'lga' => 'Kaduna North', 'cac_number' => 'CAC-002', 'status' => 'Active', 'ward' => 'Kabala'],
        ['name' => 'El-Amin International School', 'lga' => 'Kaduna North', 'cac_number' => 'CAC-003', 'status' => 'Active', 'ward' => 'Badarawa'],
        ['name' => 'Federal Government College Kaduna', 'lga' => 'Kaduna North', 'cac_number' => 'CAC-004', 'status' => 'Active', 'ward' => 'Unguwar Sarki'],
        ['name' => 'Queen Amina College', 'lga' => 'Kaduna North', 'cac_number' => 'CAC-005', 'status' => 'Pending', 'ward' => 'Hayin Banki'],
        ['name' => 'Ahmadu Bello Memorial School', 'lga' => 'Kaduna North', 'cac_number' => 'CAC-006', 'status' => 'Active', 'ward' => 'Sardauna'],
        ['name' => 'Unity Secondary School', 'lga' => 'Kaduna North', 'cac_number' => 'CAC-007', 'status' => 'Active', 'ward' => 'Unguwar Rimi'],
        ['name' => 'Alhudahuda Islamic School', 'lga' => 'Kaduna North', 'cac_number' => 'CAC-008', 'status' => 'Pending', 'ward' => 'Shaba'],
    ];
    
    $schoolIds = [];
    foreach ($schools as $school) {
        $wardId = $wardIds[$school['ward']] ?? null;
        $stmt = $pdo->prepare("INSERT IGNORE INTO schools (name, lga, cac_number, status, ward_id, email, phone, address) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $email = strtolower(str_replace([' ', '\''], ['', ''], $school['name'])) . '@school.kaduna.gov.ng';
        $phone = '080' . rand(10000000, 99999999);
        $address = $school['lga'] . ' LGA, Kaduna State';
        $stmt->execute([
            $school['name'],
            $school['lga'],
            $school['cac_number'],
            $school['status'],
            $wardId,
            $email,
            $phone,
            $address
        ]);
        $stmt = $pdo->prepare("SELECT id FROM schools WHERE name = ?");
        $stmt->execute([$school['name']]);
        $result = $stmt->fetch();
        if ($result) {
            $schoolIds[] = $result['id'];
        }
    }
    echo "   ✓ " . count($schoolIds) . " schools seeded\n";
    
    // 3. Seed Users (SMO and SA)
    echo "3. Seeding Users...\n";
    
    // Get first school ID for SA users
    $firstSchoolId = !empty($schoolIds) ? $schoolIds[0] : null;
    
    // SMO Users
    $smoUsers = [
        ['username' => 'admin', 'email' => 'admin@sqms.kaduna.gov.ng', 'full_name' => 'System Administrator', 'password' => 'admin123'],
        ['username' => 'smo1', 'email' => 'smo1@sqms.kaduna.gov.ng', 'full_name' => 'John Manager', 'password' => 'smo123'],
    ];
    
    foreach ($smoUsers as $smo) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$smo['username']]);
        if (!$stmt->fetch()) {
            $passwordHash = hashPassword($smo['password']);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, full_name, role, is_active) VALUES (?, ?, ?, ?, 'SMO', 1)");
            $stmt->execute([$smo['username'], $smo['email'], $passwordHash, $smo['full_name']]);
        }
    }
    
    // SA Users (one for each active school)
    $saNames = [
        'Musa Abdullahi', 'Amina Hassan', 'Ibrahim Usman', 'Fatima Bello',
        'Yusuf Mohammed', 'Hauwa Sani', 'Aliyu Musa', 'Zainab Adamu'
    ];
    
    $saCount = 0;
    foreach ($schoolIds as $index => $schoolId) {
        if ($saCount >= count($saNames)) break;
        
        $saName = $saNames[$saCount];
        $username = 'sa' . ($saCount + 1);
        $email = strtolower(str_replace(' ', '', $saName)) . '@school.kaduna.gov.ng';
        
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if (!$stmt->fetch()) {
            $passwordHash = hashPassword('sa123');
            $wardId = $wardIds[array_rand($wardIds)];
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, full_name, role, school_id, ward_id, is_active) VALUES (?, ?, ?, ?, 'SA', ?, ?, 1)");
            $stmt->execute([$username, $email, $passwordHash, $saName, $schoolId, $wardId]);
        }
        $saCount++;
    }
    echo "   ✓ Users seeded (2 SMO, {$saCount} SA)\n";
    
    // 4. Seed Students
    echo "4. Seeding Students...\n";
    $firstNames = ['Ahmad', 'Fatima', 'Ibrahim', 'Aisha', 'Musa', 'Hauwa', 'Yusuf', 'Zainab', 'Aliyu', 'Amina', 'Mohammed', 'Maryam', 'Usman', 'Hadiza', 'Sani', 'Rahama'];
    $lastNames = ['Abdullahi', 'Hassan', 'Usman', 'Bello', 'Mohammed', 'Sani', 'Musa', 'Adamu', 'Ibrahim', 'Aliyu', 'Yakubu', 'Danjuma', 'Garba', 'Shehu', 'Iliyasu', 'Yusuf'];
    $classLevels = ['JSS 1', 'JSS 2', 'JSS 3', 'SS 1', 'SS 2', 'SS 3'];
    $genders = ['Male', 'Female'];
    
    $studentCount = 0;
    foreach ($schoolIds as $schoolId) {
        // Add 15-25 students per school
        $numStudents = rand(15, 25);
        for ($i = 0; $i < $numStudents; $i++) {
            $firstName = $firstNames[array_rand($firstNames)];
            $lastName = $lastNames[array_rand($lastNames)];
            $middleName = rand(0, 1) ? $firstNames[array_rand($firstNames)] : null;
            $gender = $genders[array_rand($genders)];
            $classLevel = $classLevels[array_rand($classLevels)];
            
            // Generate student ID
            $year = date('Y');
            $schoolPrefix = str_pad($schoolId, 3, '0', STR_PAD_LEFT);
            $studentNum = str_pad($i + 1, 4, '0', STR_PAD_LEFT);
            $studentId = "STU-{$year}-{$schoolPrefix}-{$studentNum}";
            
            $dob = date('Y-m-d', strtotime('-' . rand(13, 18) . ' years'));
            $admissionDate = date('Y-m-d', strtotime('-' . rand(0, 3) . ' years'));
            $admissionNumber = 'ADM-' . $schoolId . '-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT);
            
            $stmt = $pdo->prepare("INSERT IGNORE INTO students (school_id, student_id, first_name, last_name, middle_name, date_of_birth, gender, admission_number, admission_date, class_level, parent_guardian_name, parent_guardian_phone, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active')");
            $parentName = $lastNames[array_rand($lastNames)] . ' ' . $firstNames[array_rand($firstNames)];
            $parentPhone = '080' . rand(10000000, 99999999);
            $stmt->execute([
                $schoolId,
                $studentId,
                $firstName,
                $lastName,
                $middleName,
                $dob,
                $gender,
                $admissionNumber,
                $admissionDate,
                $classLevel,
                $parentName,
                $parentPhone
            ]);
            $studentCount++;
        }
    }
    echo "   ✓ {$studentCount} students seeded\n";
    
    // 5. Seed Staff
    echo "5. Seeding Staff...\n";
    $positions = ['Principal', 'Vice Principal', 'Teacher', 'Mathematics Teacher', 'English Teacher', 'Science Teacher', 'Secretary', 'Accountant', 'Librarian', 'Security Officer'];
    $departments = ['Administration', 'Mathematics', 'English', 'Science', 'Social Studies', 'Arts', 'Physical Education', 'Library'];
    $qualifications = ['B.Ed', 'B.Sc', 'M.Ed', 'M.Sc', 'PhD', 'NCE', 'B.A'];
    
    $staffCount = 0;
    foreach ($schoolIds as $schoolId) {
        // Add 8-15 staff per school
        $numStaff = rand(8, 15);
        for ($i = 0; $i < $numStaff; $i++) {
            $firstName = $firstNames[array_rand($firstNames)];
            $lastName = $lastNames[array_rand($lastNames)];
            $middleName = rand(0, 1) ? $firstNames[array_rand($firstNames)] : null;
            $gender = $genders[array_rand($genders)];
            $position = $positions[array_rand($positions)];
            $department = $departments[array_rand($departments)];
            $qualification = $qualifications[array_rand($qualifications)];
            
            // Generate staff ID
            $year = date('Y');
            $schoolPrefix = str_pad($schoolId, 3, '0', STR_PAD_LEFT);
            $staffNum = str_pad($i + 1, 4, '0', STR_PAD_LEFT);
            $staffId = "STF-{$year}-{$schoolPrefix}-{$staffNum}";
            
            $dob = date('Y-m-d', strtotime('-' . rand(25, 55) . ' years'));
            $employmentDate = date('Y-m-d', strtotime('-' . rand(0, 10) . ' years'));
            $employeeNumber = 'EMP-' . $schoolId . '-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT);
            
            $statuses = ['Active', 'Active', 'Active', 'Active', 'Inactive']; // Mostly active
            $status = $statuses[array_rand($statuses)];
            
            $stmt = $pdo->prepare("INSERT IGNORE INTO staff (school_id, staff_id, first_name, last_name, middle_name, date_of_birth, gender, employee_number, employment_date, position, department, qualification, phone, email, emergency_contact_name, emergency_contact_phone, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $phone = '080' . rand(10000000, 99999999);
            $email = strtolower($firstName . '.' . $lastName . '@school.kaduna.gov.ng');
            $emergencyName = $lastNames[array_rand($lastNames)] . ' ' . $firstNames[array_rand($firstNames)];
            $emergencyPhone = '080' . rand(10000000, 99999999);
            
            $stmt->execute([
                $schoolId,
                $staffId,
                $firstName,
                $lastName,
                $middleName,
                $dob,
                $gender,
                $employeeNumber,
                $employmentDate,
                $position,
                $department,
                $qualification,
                $phone,
                $email,
                $emergencyName,
                $emergencyPhone,
                $status
            ]);
            $staffCount++;
        }
    }
    echo "   ✓ {$staffCount} staff members seeded\n";
    
    // 6. Seed Announcements
    echo "6. Seeding Announcements...\n";
    $announcements = [
        ['title' => 'Welcome to the New Academic Year', 'content' => 'We welcome all schools to the new academic year. Please ensure all student and staff records are up to date.', 'target' => 'All'],
        ['title' => 'Important: Data Submission Deadline', 'content' => 'All schools are required to submit their student and staff data by the end of this month. Please ensure compliance.', 'target' => 'SA'],
        ['title' => 'System Maintenance Notice', 'content' => 'The system will undergo scheduled maintenance this weekend. Please save all your work before the maintenance period.', 'target' => 'All'],
        ['title' => 'Training Workshop for School Administrators', 'content' => 'A training workshop will be held next week for all School Administrators. Details will be sent via email.', 'target' => 'SA'],
    ];
    
    // Get an SMO user ID
    $stmt = $pdo->query("SELECT id FROM users WHERE role = 'SMO' LIMIT 1");
    $smoUserId = $stmt->fetch()['id'] ?? null;
    
    if ($smoUserId) {
        foreach ($announcements as $announcement) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO announcements (title, content, target_audience, created_by, is_active) VALUES (?, ?, ?, ?, 1)");
            $stmt->execute([$announcement['title'], $announcement['content'], $announcement['target'], $smoUserId]);
        }
        echo "   ✓ " . count($announcements) . " announcements seeded\n";
    }
    
    // 7. Seed some audit logs
    echo "7. Seeding Audit Logs...\n";
    $actions = ['LOGIN', 'LOGOUT', 'APPROVE_SCHOOL', 'ADD_STUDENT', 'ADD_STAFF', 'UPDATE_STUDENT', 'CREATE_ANNOUNCEMENT'];
    $descriptions = [
        'User logged in',
        'User logged out',
        'School approved: Sample School',
        'Added student: STU-2024-001-0001',
        'Added staff: STF-2024-001-0001',
        'Updated student record',
        'Created announcement: Welcome Message'
    ];
    
    // Get some user IDs
    $stmt = $pdo->query("SELECT id FROM users LIMIT 5");
    $userIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    for ($i = 0; $i < 20; $i++) {
        $action = $actions[array_rand($actions)];
        $description = $descriptions[array_rand($descriptions)];
        $userId = $userIds[array_rand($userIds)] ?? null;
        $ipAddress = '192.168.1.' . rand(1, 255);
        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)';
        $createdAt = date('Y-m-d H:i:s', strtotime('-' . rand(0, 30) . ' days'));
        
        $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, description, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $action, $description, $ipAddress, $userAgent, $createdAt]);
    }
    echo "   ✓ 20 audit log entries seeded\n";
    
    // Commit transaction
    $pdo->commit();
    
    echo "\n";
    echo "========================================\n";
    echo "✓ Demo data seeding completed successfully!\n";
    echo "========================================\n";
    echo "\n";
    echo "Summary:\n";
    echo "- Wards: " . count($wards) . "\n";
    echo "- Schools: " . count($schoolIds) . "\n";
    echo "- Users: " . (count($smoUsers) + $saCount) . " (SMO: " . count($smoUsers) . ", SA: {$saCount})\n";
    echo "- Students: {$studentCount}\n";
    echo "- Staff: {$staffCount}\n";
    echo "- Announcements: " . count($announcements) . "\n";
    echo "- Audit Logs: 20\n";
    echo "\n";
    echo "Default Login Credentials:\n";
    echo "SMO: admin / admin123\n";
    echo "SA: sa1 / sa123 (and sa2, sa3, etc.)\n";
    echo "\n";
    echo "⚠️  IMPORTANT: Delete this file (setup/seed_demo_data.php) after use for security!\n";
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "\n";
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Transaction rolled back.\n";
    error_log("Seeder error: " . $e->getMessage());
}

echo "</pre>";
?>

