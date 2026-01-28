<?php
/**
 * Seeder for Kaduna North Secondary Schools
 * This seeder populates the database with realistic data for secondary schools
 * within Kaduna North Local Government Area, Kaduna State
 */

require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = getDBConnection();

// Start transaction
$pdo->beginTransaction();

try {
    echo "Starting seeder for Kaduna North Secondary Schools...\n";

    // ============================================
    // 1. SEED WARDS (Kaduna North LGA)
    // ============================================
    echo "Seeding wards...\n";
    $wards = [
        'Badarawa',
        'Dadi Riba',
        'Hayin Banki',
        'Kabala',
        'Kawo',
        'Maiburiji',
        'Sardauna',
        'Shaba',
        'Unguwan Dosa',
        'Unguwar Rimi',
        'Unguwar Sarki',
        'Unguwar Shanu',
        'Unguwar kanawa'
    ];

    $wardIds = [];
    foreach ($wards as $wardName) {
        $stmt = $pdo->prepare("INSERT INTO wards (name, lga, description) VALUES (?, 'Kaduna North', ?) ON DUPLICATE KEY UPDATE name=name");
        $stmt->execute([$wardName, "{$wardName} Ward in Kaduna North LGA"]);
        $wardId = $pdo->lastInsertId();
        if (!$wardId) {
            $stmt = $pdo->prepare("SELECT id FROM wards WHERE name = ?");
            $stmt->execute([$wardName]);
            $wardId = $stmt->fetch()['id'];
        }
        $wardIds[$wardName] = $wardId;
    }
    echo "✓ Wards seeded: " . count($wardIds) . "\n";

    // ============================================
    // 2. SEED SCHOOLS (Secondary Schools in Kaduna North)
    // ============================================
    echo "Seeding schools...\n";

    $secondarySchools = [
        // Government Schools
        ['name' => 'Government Girls Secondary School Kawo', 'type' => 'Government', 'ward' => 'Kawo', 'cac' => 'RC1234567', 'tin' => '123456789012', 'address' => 'Off Kaduna-Zaria Road by WAEC, Kawo', 'phone' => '08023456789', 'email' => 'ggsskawo@kaduna.gov.ng'],
        ['name' => 'Government Technical College Kaduna', 'type' => 'Government', 'ward' => 'Kabala', 'cac' => 'RC1234568', 'tin' => '123456789013', 'address' => 'Water Board Road, Kaduna', 'phone' => '08023456790', 'email' => 'gtckaduna@kaduna.gov.ng'],
        ['name' => 'Rimi College Senior', 'type' => 'Government', 'ward' => 'Unguwar Rimi', 'cac' => 'RC1234569', 'tin' => '123456789014', 'address' => 'Tafawa Balewa Way, Kaduna', 'phone' => '08023456791', 'email' => 'rimicollege@kaduna.gov.ng'],
        ['name' => 'Sardauna Memorial College', 'type' => 'Government', 'ward' => 'Unguwan Dosa', 'cac' => 'RC1234570', 'tin' => '123456789015', 'address' => 'College Road, Unguwan Dosa', 'phone' => '08023456792', 'email' => 'sardaunacollege@kaduna.gov.ng'],
        ['name' => 'Government Girls Secondary School Angwa Rimi', 'type' => 'Government', 'ward' => 'Unguwar Rimi', 'cac' => 'RC1234571', 'tin' => '123456789016', 'address' => 'Angwa Rimi, Kaduna North', 'phone' => '08023456793', 'email' => 'ggssangwarimi@kaduna.gov.ng'],
        ['name' => 'Government Secondary School Angwan Sarki', 'type' => 'Government', 'ward' => 'Unguwar Sarki', 'cac' => 'RC1234572', 'tin' => '123456789017', 'address' => 'Angwan Sarki, Kaduna North', 'phone' => '08023456794', 'email' => 'gssangwansarki@kaduna.gov.ng'],
        ['name' => 'Government Girls Secondary School Independence Way', 'type' => 'Government', 'ward' => 'Sardauna', 'cac' => 'RC1234573', 'tin' => '123456789018', 'address' => 'Independence Way, Kaduna North', 'phone' => '08023456795', 'email' => 'ggssindependence@kaduna.gov.ng'],
        ['name' => 'Government Junior Secondary School Badarawa', 'type' => 'Government', 'ward' => 'Badarawa', 'cac' => 'RC1234574', 'tin' => '123456789019', 'address' => 'Badarawa, Kaduna North', 'phone' => '08023456796', 'email' => 'gjssbadarawa@kaduna.gov.ng'],

        // Private Schools
        ['name' => 'Talent International School', 'type' => 'Private', 'ward' => 'Hayin Banki', 'cac' => 'BN1234567', 'tin' => '123456789020', 'address' => '62A Shehu Usman Crescent, Hayin Banki, Kaduna', 'phone' => '08023456797', 'email' => 'info@talentinternational.edu.ng'],
        ['name' => 'Nuruddeen Secondary School Malali', 'type' => 'Private', 'ward' => 'Shaba', 'cac' => 'BN1234568', 'tin' => '123456789021', 'address' => 'Malali, Kaduna North', 'phone' => '08023456798', 'email' => 'info@nuruddeenschool.edu.ng'],
        ['name' => 'El-Amin International School', 'type' => 'Private', 'ward' => 'Kawo', 'cac' => 'BN1234569', 'tin' => '123456789022', 'address' => 'Kawo Road, Kaduna North', 'phone' => '08023456799', 'email' => 'info@elamin.edu.ng'],
        ['name' => 'Capital Science Academy', 'type' => 'Private', 'ward' => 'Kabala', 'cac' => 'BN1234570', 'tin' => '123456789023', 'address' => 'Kabala, Kaduna North', 'phone' => '08023456800', 'email' => 'info@capitalscience.edu.ng'],
        ['name' => 'Ahmadu Bello Memorial Secondary School', 'type' => 'Private', 'ward' => 'Unguwar Sarki', 'cac' => 'BN1234571', 'tin' => '123456789024', 'address' => 'Unguwar Sarki, Kaduna North', 'phone' => '08023456801', 'email' => 'info@abmss.edu.ng'],
        ['name' => 'St. Michael\'s Secondary School', 'type' => 'Private', 'ward' => 'Dadi Riba', 'cac' => 'BN1234572', 'tin' => '123456789025', 'address' => 'Dadi Riba, Kaduna North', 'phone' => '08023456802', 'email' => 'info@stmichaels.edu.ng'],
        ['name' => 'Al-Iman Secondary School', 'type' => 'Private', 'ward' => 'Maiburiji', 'cac' => 'BN1234573', 'tin' => '123456789026', 'address' => 'Maiburiji, Kaduna North', 'phone' => '08023456803', 'email' => 'info@aliman.edu.ng'],
        ['name' => 'Greenfield Academy', 'type' => 'Private', 'ward' => 'Unguwar Shanu', 'cac' => 'BN1234574', 'tin' => '123456789027', 'address' => 'Unguwar Shanu, Kaduna North', 'phone' => '08023456804', 'email' => 'info@greenfieldacademy.edu.ng'],
        ['name' => 'Crescent International School', 'type' => 'Private', 'ward' => 'Unguwar kanawa', 'cac' => 'BN1234575', 'tin' => '123456789028', 'address' => 'Unguwar kanawa, Kaduna North', 'phone' => '08023456805', 'email' => 'info@crescent.edu.ng'],
        ['name' => 'Royal Academy Kaduna', 'type' => 'Private', 'ward' => 'Badarawa', 'cac' => 'BN1234576', 'tin' => '123456789029', 'address' => 'Badarawa, Kaduna North', 'phone' => '08023456806', 'email' => 'info@royalacademy.edu.ng'],
        ['name' => 'Excellence Secondary School', 'type' => 'Private', 'ward' => 'Hayin Banki', 'cac' => 'BN1234577', 'tin' => '123456789030', 'address' => 'Hayin Banki, Kaduna North', 'phone' => '08023456807', 'email' => 'info@excellence.edu.ng'],
    ];

    $schoolIds = [];
    $schoolStatuses = ['Active', 'Active', 'Active', 'Active', 'Active', 'Pending', 'Active', 'Active', 'Active', 'Active', 'Active', 'Active', 'Active', 'Active', 'Active', 'Active', 'Active', 'Active'];

    // Get existing CAC numbers to avoid duplicates
    $stmt = $pdo->query("SELECT cac_number FROM schools WHERE cac_number IS NOT NULL");
    $existingCACs = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Get existing TIN numbers to avoid duplicates
    $stmt = $pdo->query("SELECT tin_number FROM schools WHERE tin_number IS NOT NULL");
    $existingTINs = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Get existing school names
    $stmt = $pdo->query("SELECT name, id FROM schools");
    $existingSchools = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $existingSchools[$row['name']] = $row['id'];
    }

    $cacCounter = 1000;
    $tinCounter = 1000;

    foreach ($secondarySchools as $index => $school) {
        $wardId = $wardIds[$school['ward']];
        $status = $schoolStatuses[$index] ?? 'Active';

        // Check if school already exists by name
        if (isset($existingSchools[$school['name']])) {
            // School already exists, use its ID
            $schoolIds[] = $existingSchools[$school['name']];
            echo "  - Skipping existing school: {$school['name']}\n";
            continue;
        }

        // Generate unique CAC number if the provided one exists
        $cacNumber = $school['cac'];
        while (in_array($cacNumber, $existingCACs)) {
            if ($school['type'] === 'Private') {
                $cacNumber = 'BN' . str_pad($cacCounter, 7, '0', STR_PAD_LEFT);
            } else {
                $cacNumber = 'RC' . str_pad($cacCounter, 7, '0', STR_PAD_LEFT);
            }
            $cacCounter++;
        }
        $existingCACs[] = $cacNumber;

        // Generate unique TIN number if the provided one exists
        $tinNumber = $school['tin'];
        while (in_array($tinNumber, $existingTINs)) {
            $tinNumber = str_pad(123456789000 + $tinCounter, 12, '0', STR_PAD_LEFT);
            $tinCounter++;
        }
        $existingTINs[] = $tinNumber;

        // Check if email already exists
        $emailCheckStmt = $pdo->prepare("SELECT id FROM schools WHERE email = ?");
        $emailCheckStmt->execute([$school['email']]);
        if ($emailCheckStmt->fetch()) {
            // Generate unique email
            $school['email'] = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $school['name'])) . rand(100, 999) . '@school.kaduna.gov.ng';
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO schools (name, lga, school_type, cac_number, tin_number, address, phone, email, ward_id, status) VALUES (?, 'Kaduna North', ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $school['name'],
                $school['type'],
                $cacNumber,
                $tinNumber,
                $school['address'],
                $school['phone'],
                $school['email'],
                $wardId,
                $status
            ]);
            $newSchoolId = $pdo->lastInsertId();
            $schoolIds[] = $newSchoolId;
            $existingSchools[$school['name']] = $newSchoolId;
            echo "  - Added school: {$school['name']} (CAC: $cacNumber, TIN: $tinNumber)\n";
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                // Duplicate entry, skip this school
                echo "  - Skipping duplicate school: {$school['name']} (Error: " . $e->getMessage() . ")\n";
                continue;
            } else {
                throw $e; // Re-throw if it's a different error
            }
        }
    }
    echo "✓ Schools seeded: " . count($schoolIds) . "\n";

    // ============================================
    // 3. SEED USERS (SMO and SA for each school)
    // ============================================
    echo "Seeding users...\n";

    // Create SMO user
    $smoPassword = hashPassword('admin123');
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, full_name, role, is_active) VALUES (?, ?, ?, ?, 'SMO', 1) ON DUPLICATE KEY UPDATE username=username");
    $stmt->execute(['admin', 'admin@sqms.kaduna.gov.ng', $smoPassword, 'System Administrator']);
    $smoId = $pdo->lastInsertId();
    if (!$smoId) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = 'admin'");
        $stmt->execute();
        $smoId = $stmt->fetch()['id'];
    }

    // Create SA users for each active school
    $saUsers = [];
    $nigerianFirstNames = ['Ahmadu', 'Ibrahim', 'Musa', 'Yusuf', 'Hassan', 'Aminu', 'Bello', 'Sani', 'Aliyu', 'Umar', 'Fatima', 'Aisha', 'Hauwa', 'Zainab', 'Maryam', 'Amina', 'Halima', 'Hadiza'];
    $nigerianLastNames = ['Bello', 'Ibrahim', 'Musa', 'Yusuf', 'Hassan', 'Aminu', 'Sani', 'Aliyu', 'Umar', 'Mohammed', 'Abdullahi', 'Shehu', 'Usman', 'Danjuma', 'Yakubu', 'Garba', 'Bashir', 'Adamu'];

    foreach ($schoolIds as $schoolIndex => $schoolId) {
        // Get school name
        $stmt = $pdo->prepare("SELECT name FROM schools WHERE id = ?");
        $stmt->execute([$schoolId]);
        $schoolName = $stmt->fetch()['name'];

        // Generate SA user
        $firstName = $nigerianFirstNames[array_rand($nigerianFirstNames)];
        $lastName = $nigerianLastNames[array_rand($nigerianLastNames)];
        $fullName = $firstName . ' ' . $lastName;
        $username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $schoolName));
        $username = substr($username, 0, 15) . '_sa';
        $email = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $schoolName)) . '@school.kaduna.gov.ng';
        $email = str_replace(' ', '', $email);

        // Ensure unique username and email
        $checkStmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE username = ? OR email = ?");
        $checkStmt->execute([$username, $email]);
        if ($checkStmt->fetch()['count'] > 0) {
            $username .= $schoolIndex;
            $email = str_replace('@', $schoolIndex . '@', $email);
        }

        $saPassword = hashPassword('school123');

        // Get the ward_id for this school
        $schoolWardStmt = $pdo->prepare("SELECT ward_id FROM schools WHERE id = ?");
        $schoolWardStmt->execute([$schoolId]);
        $schoolWardId = $schoolWardStmt->fetchColumn();
        if (!$schoolWardId) {
            $schoolWardId = $wardIds[array_rand($wardIds)];
        }

        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, full_name, role, school_id, ward_id, is_active) VALUES (?, ?, ?, ?, 'SA', ?, ?, 1) ON DUPLICATE KEY UPDATE username=username");
        $stmt->execute([
            $username,
            $email,
            $saPassword,
            $fullName,
            $schoolId,
            $schoolWardId
        ]);
        $saUsers[] = $pdo->lastInsertId();
    }
    echo "✓ Users seeded: 1 SMO + " . count($saUsers) . " SA users\n";

    // ============================================
    // 4. SEED STUDENTS (for each school)
    // ============================================
    echo "Seeding students...\n";

    $studentFirstNames = ['Ahmadu', 'Ibrahim', 'Musa', 'Yusuf', 'Hassan', 'Aminu', 'Bello', 'Sani', 'Aliyu', 'Umar', 'Fatima', 'Aisha', 'Hauwa', 'Zainab', 'Maryam', 'Amina', 'Halima', 'Hadiza', 'Aminat', 'Khadija', 'Rukayya', 'Safiya', 'Rabiatu', 'Jamila'];
    $studentLastNames = ['Bello', 'Ibrahim', 'Musa', 'Yusuf', 'Hassan', 'Aminu', 'Sani', 'Aliyu', 'Umar', 'Mohammed', 'Abdullahi', 'Shehu', 'Usman', 'Danjuma', 'Yakubu', 'Garba', 'Bashir', 'Adamu', 'Ali', 'Ishaq', 'Yakub', 'Suleiman'];
    $middleNames = ['Ahmad', 'Ibrahim', 'Musa', 'Yusuf', 'Hassan', 'Aminu', 'Bello', 'Sani', 'Aliyu', 'Umar', 'Mohammed', 'Abdullahi', 'Shehu', 'Usman', 'Danjuma', 'Yakubu', 'Garba', 'Bashir', 'Adamu', 'Ali', 'Ishaq', 'Yakub', 'Suleiman', ''];
    $classLevels = ['JSS 1', 'JSS 2', 'JSS 3', 'SS 1', 'SS 2', 'SS 3'];
    $genders = ['Male', 'Female'];
    $statuses = ['Active', 'Active', 'Active', 'Active', 'Active', 'Active', 'Active', 'Active', 'Active', 'Inactive', 'Graduated'];

    $totalStudents = 0;
    foreach ($schoolIds as $schoolId) {
        // Generate 20-50 students per school
        $studentCount = rand(20, 50);

        for ($i = 0; $i < $studentCount; $i++) {
            $firstName = $studentFirstNames[array_rand($studentFirstNames)];
            $lastName = $studentLastNames[array_rand($studentLastNames)];
            $middleName = $middleNames[array_rand($middleNames)];
            $gender = $genders[array_rand($genders)];
            $classLevel = $classLevels[array_rand($classLevels)];
            $status = $statuses[array_rand($statuses)];

            // Generate date of birth (ages 12-18)
            $age = rand(12, 18);
            $dob = date('Y-m-d', strtotime("-$age years -" . rand(0, 365) . " days"));

            // Generate admission date (within last 3 years)
            $admissionDate = date('Y-m-d', strtotime("-" . rand(0, 3) . " years -" . rand(0, 365) . " days"));

            $studentId = generateStudentID($schoolId);
            $admissionNumber = 'ADM' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);

            $parentName = $studentLastNames[array_rand($studentLastNames)] . ' ' . $studentFirstNames[array_rand($studentFirstNames)];
            $parentPhone = '080' . rand(10000000, 99999999);
            $parentEmail = strtolower($parentName) . '@email.com';
            $parentEmail = str_replace(' ', '', $parentEmail);

            $phone = '080' . rand(10000000, 99999999);
            $email = strtolower($firstName . $lastName) . rand(100, 999) . '@student.com';

            $address = rand(1, 100) . ' Street, ' . array_rand($wardIds) . ', Kaduna North';

            $stmt = $pdo->prepare("INSERT INTO students (school_id, student_id, first_name, last_name, middle_name, date_of_birth, gender, admission_number, admission_date, class_level, parent_guardian_name, parent_guardian_phone, parent_guardian_email, address, phone, email, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $schoolId,
                $studentId,
                $firstName,
                $lastName,
                $middleName ?: null,
                $dob,
                $gender,
                $admissionNumber,
                $admissionDate,
                $classLevel,
                $parentName,
                $parentPhone,
                $parentEmail,
                $address,
                $phone,
                $email,
                $status
            ]);
            $totalStudents++;
        }
    }
    echo "✓ Students seeded: $totalStudents\n";

    // ============================================
    // 5. SEED STAFF (for each school)
    // ============================================
    echo "Seeding staff...\n";

    $staffFirstNames = ['Ahmadu', 'Ibrahim', 'Musa', 'Yusuf', 'Hassan', 'Aminu', 'Bello', 'Sani', 'Aliyu', 'Umar', 'Fatima', 'Aisha', 'Hauwa', 'Zainab', 'Maryam', 'Amina', 'Halima', 'Hadiza'];
    $staffLastNames = ['Bello', 'Ibrahim', 'Musa', 'Yusuf', 'Hassan', 'Aminu', 'Sani', 'Aliyu', 'Umar', 'Mohammed', 'Abdullahi', 'Shehu', 'Usman', 'Danjuma', 'Yakubu', 'Garba', 'Bashir', 'Adamu'];
    $positions = ['Principal', 'Vice Principal', 'Teacher', 'Teacher', 'Teacher', 'Teacher', 'Teacher', 'Teacher', 'Teacher', 'Teacher', 'Bursar', 'Secretary', 'Librarian', 'Laboratory Technician', 'Security Officer'];
    $departments = ['Mathematics', 'English', 'Science', 'Social Studies', 'Arts', 'Languages', 'Physical Education', 'Administration', 'Library', 'Laboratory'];
    $qualifications = ['B.Ed', 'B.Sc (Ed)', 'M.Ed', 'M.Sc (Ed)', 'B.A (Ed)', 'M.A (Ed)', 'PhD (Ed)', 'NCE', 'PGDE'];
    $staffStatuses = ['Active', 'Active', 'Active', 'Active', 'Active', 'Active', 'Active', 'Active', 'Active', 'Inactive', 'Resigned'];

    $totalStaff = 0;
    foreach ($schoolIds as $schoolId) {
        // Generate 10-25 staff per school
        $staffCount = rand(10, 25);

        for ($i = 0; $i < $staffCount; $i++) {
            $firstName = $staffFirstNames[array_rand($staffFirstNames)];
            $lastName = $staffLastNames[array_rand($staffLastNames)];
            $middleName = $middleNames[array_rand($middleNames)];
            $gender = $genders[array_rand($genders)];
            $position = $positions[array_rand($positions)];
            $department = $departments[array_rand($departments)];
            $qualification = $qualifications[array_rand($qualifications)];
            $status = $staffStatuses[array_rand($staffStatuses)];

            // Generate date of birth (ages 25-60)
            $age = rand(25, 60);
            $dob = date('Y-m-d', strtotime("-$age years -" . rand(0, 365) . " days"));

            // Generate employment date (within last 10 years)
            $employmentDate = date('Y-m-d', strtotime("-" . rand(0, 10) . " years -" . rand(0, 365) . " days"));

            $staffId = generateStaffID($schoolId);
            $employeeNumber = 'EMP' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);

            $emergencyName = $staffLastNames[array_rand($staffLastNames)] . ' ' . $staffFirstNames[array_rand($staffFirstNames)];
            $emergencyPhone = '080' . rand(10000000, 99999999);

            $phone = '080' . rand(10000000, 99999999);
            $email = strtolower($firstName . $lastName) . rand(100, 999) . '@staff.com';

            $address = rand(1, 100) . ' Street, ' . array_rand($wardIds) . ', Kaduna North';

            $stmt = $pdo->prepare("INSERT INTO staff (school_id, staff_id, first_name, last_name, middle_name, date_of_birth, gender, employee_number, employment_date, position, department, qualification, phone, email, address, emergency_contact_name, emergency_contact_phone, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $schoolId,
                $staffId,
                $firstName,
                $lastName,
                $middleName ?: null,
                $dob,
                $gender,
                $employeeNumber,
                $employmentDate,
                $position,
                $department,
                $qualification,
                $phone,
                $email,
                $address,
                $emergencyName,
                $emergencyPhone,
                $status
            ]);
            $totalStaff++;
        }
    }
    echo "✓ Staff seeded: $totalStaff\n";

    // ============================================
    // 6. SEED ANNOUNCEMENTS
    // ============================================
    echo "Seeding announcements...\n";

    $announcements = [
        ['title' => 'Welcome to Kaduna State SQMS', 'content' => 'We are pleased to welcome all schools to the Kaduna State School Quality Management System. This platform will help us manage and monitor school activities effectively.', 'audience' => 'All'],
        ['title' => 'Quarterly Assessment Reminder', 'content' => 'All schools are reminded that quarterly assessments will commence next month. Please ensure all student records are up to date.', 'audience' => 'SA'],
        ['title' => 'Staff Training Program', 'content' => 'A training program for all school administrators will be held next week. Please confirm your attendance.', 'audience' => 'SA'],
        ['title' => 'New Policy Guidelines', 'content' => 'New policy guidelines for school operations have been released. Please review and implement accordingly.', 'audience' => 'All'],
        ['title' => 'Annual School Inspection', 'content' => 'Annual school inspections will begin next month. All schools should prepare their documentation.', 'audience' => 'All'],
    ];

    foreach ($announcements as $announcement) {
        $stmt = $pdo->prepare("INSERT INTO announcements (title, content, target_audience, created_by, is_active) VALUES (?, ?, ?, ?, 1)");
        $stmt->execute([
            $announcement['title'],
            $announcement['content'],
            $announcement['audience'],
            $smoId
        ]);
    }
    echo "✓ Announcements seeded: " . count($announcements) . "\n";

    // ============================================
    // 7. SEED AUDIT LOGS
    // ============================================
    echo "Seeding audit logs...\n";

    $actions = ['LOGIN', 'LOGOUT', 'CREATE_SCHOOL', 'APPROVE_SCHOOL', 'ADD_STUDENT', 'ADD_STAFF', 'UPDATE_STUDENT', 'UPDATE_STAFF', 'CREATE_ANNOUNCEMENT'];
    $descriptions = [
        'User logged into the system',
        'User logged out of the system',
        'New school registered',
        'School registration approved',
        'New student added',
        'New staff member added',
        'Student information updated',
        'Staff information updated',
        'New announcement created'
    ];

    // Generate audit logs for the last 30 days
    for ($i = 0; $i < 50; $i++) {
        $action = $actions[array_rand($actions)];
        $description = $descriptions[array_rand($descriptions)];
        $userId = rand(0, 1) ? $smoId : ($saUsers[array_rand($saUsers)] ?? $smoId);
        $daysAgo = rand(0, 30);
        $createdAt = date('Y-m-d H:i:s', strtotime("-$daysAgo days -" . rand(0, 1440) . " minutes"));

        $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, description, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $userId,
            $action,
            $description,
            '192.168.1.' . rand(1, 255),
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
            $createdAt
        ]);
    }
    echo "✓ Audit logs seeded: 50\n";

    // Commit transaction
    $pdo->commit();

    echo "\n========================================\n";
    echo "Seeder completed successfully!\n";
    echo "========================================\n";
    echo "Summary:\n";
    echo "- Wards: " . count($wardIds) . "\n";
    echo "- Schools: " . count($schoolIds) . " (8 Government + 10 Private)\n";
    echo "- Users: 1 SMO + " . count($saUsers) . " SA\n";
    echo "- Students: $totalStudents\n";
    echo "- Staff: $totalStaff\n";
    echo "- Announcements: " . count($announcements) . "\n";
    echo "- Audit Logs: 50\n";
    echo "\nDefault Login Credentials:\n";
    echo "SMO: admin / admin123\n";
    echo "SA: [school_username]_sa / school123\n";
    echo "========================================\n";

} catch (PDOException $e) {
    $pdo->rollBack();
    echo "Error: " . $e->getMessage() . "\n";
    echo "Rolling back transaction...\n";
    exit(1);
} catch (Exception $e) {
    $pdo->rollBack();
    echo "Error: " . $e->getMessage() . "\n";
    echo "Rolling back transaction...\n";
    exit(1);
}

