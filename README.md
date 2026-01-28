# Kaduna State School Quality Management System (SQMS)

A comprehensive school management system built with PHP, MySQL, HTML, CSS (Tailwind), and JavaScript.

## Phase 1: Foundation, Database & Core Authentication ✅

This phase includes:
- Project structure setup
- Database schema (users, schools, wards tables)
- Secure authentication system with password hashing
- Role-based access control (SMO and SA roles)
- Modern UI using Tailwind CSS (shadcn-inspired design)
- SMO and SA dashboards with sidebar navigation

## Phase 2: School Management Module ✅

This phase includes:
- School registration form (public access)
- School approval system for SMOs
- School directory with search and filter functionality
- Individual school profile pages
- Role-based access to school information

## Installation

### Prerequisites
- XAMPP, WAMP, or similar (Apache, MySQL, PHP 7.4+)
- Web browser

### Setup Steps

1. **Database Setup:**
   - Open phpMyAdmin (usually at `http://localhost/phpmyadmin`)
   - Create a new database named `kaduna_sqms`
   - Import the SQL file from `database/schema.sql` or copy and execute the SQL statements

2. **Database Configuration:**
   - Edit `includes/db_connect.php` if your database credentials differ:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_NAME', 'kaduna_sqms');
     define('DB_USER', 'root');
     define('DB_PASS', '');
     ```

3. **Create Admin User:**
   - Run the setup script: Navigate to `http://localhost/KSSQAA/setup/create_admin.php` in your browser
   - This will create the default admin user with credentials:
     - Username: `admin`
     - Password: `admin123`
   - **⚠️ IMPORTANT: Delete the `setup/` folder after setup for security!**
   - **⚠️ IMPORTANT: Change the default password in production!**

4. **Seed Demo Data (Optional):**
   - For testing and demonstration, you can populate the database with sample data
   - Navigate to `http://localhost/KSSQAA/setup/seed_demo_data.php` in your browser
   - This will create:
     - 13 wards (Kaduna North LGA)
     - 8 schools (with various statuses)
     - Multiple users (SMO and SA)
     - 15-25 students per school
     - 8-15 staff members per school
     - Sample announcements
     - Sample audit logs
   - Default credentials after seeding:
     - SMO: `admin` / `admin123` or `smo1` / `smo123`
     - SA: `sa1` / `sa123`, `sa2` / `sa123`, etc.
   - **⚠️ IMPORTANT: Delete the `setup/` folder after use for security!**

4. **Install PHPMailer (for email notifications):**
   - Install Composer if you don't have it: https://getcomposer.org/download/
   - Navigate to your project root directory
   - Run: `composer require phpmailer/phpmailer`
   - Or check `setup/install_phpmailer.php` for installation instructions
   - Email notifications are configured for Mailtrap (sandbox) - see `includes/email_config.php`

5. **Access the Application:**
   - Navigate to `http://localhost/KSSQAA/public/login.php` or `http://localhost/KSSQAA/public/`
   - Use the admin credentials created in step 3

## Project Structure

```
KSSQAA/
├── database/
│   └── schema.sql          # Database schema
├── includes/
│   ├── db_connect.php      # Database connection
│   ├── functions.php       # Reusable functions
│   └── check_auth.php      # Authentication check
├── public/
│   ├── index.php           # Index page (redirects)
│   ├── login.php           # Login page
│   ├── logout.php          # Logout script
│   ├── register_school.php # School registration form
│   ├── smo_dashboard.php   # SMO dashboard
│   ├── sa_dashboard.php    # SA dashboard
│   ├── approve_schools.php # School approval (SMO only)
│   ├── schools.php         # School directory
│   └── school_profile.php  # Individual school profile
├── setup/
│   └── create_admin.php    # Admin user setup script (delete after use)
├── css/                    # CSS files (if needed)
├── js/                     # JavaScript files (if needed)
├── images/                 # Image assets
├── plan.md                 # Development plan
├── .htaccess               # Apache configuration
└── README.md               # This file
```

## Features Implemented

### Phase 1 ✅
- ✅ Secure login system with password hashing
- ✅ Session management
- ✅ Role-based access control
- ✅ SMO Dashboard with statistics
- ✅ SA Dashboard with school information
- ✅ Modern, responsive UI with Tailwind CSS
- ✅ Sidebar navigation menu
- ✅ Database schema with proper relationships

### Phase 2 ✅
- ✅ Public school registration form
- ✅ School approval/rejection system (SMO)
- ✅ School directory with search and filters
- ✅ Individual school profile pages
- ✅ LGA and Ward filtering
- ✅ Status-based filtering (for SMO)
- ✅ Responsive tables and forms
- ✅ Email notifications for school approval/rejection

## Security Features

- Password hashing using PHP's `password_hash()` and `password_verify()`
- PDO prepared statements to prevent SQL injection
- Input sanitization functions
- Session-based authentication
- Role-based access control

## Email Notifications

- Email notifications are sent when schools are approved or rejected
- Uses PHPMailer with Mailtrap SMTP (sandbox) for testing
- Configure email settings in `includes/email_config.php`
- For production, update SMTP credentials to your production email server

## Next Steps (Phase 3)

- Student management module (add, view, edit students)
- Staff management module (add, view, edit staff)
- Unique Student ID generation
- Search and filter for students/staff

## Notes

- The application uses Tailwind CSS via CDN for styling
- All database operations use PDO for security
- Default admin password should be changed before production use
- Make sure PHP sessions are properly configured

## License

© 2024 Kaduna State Government. All rights reserved.

