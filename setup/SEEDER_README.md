# Kaduna North Secondary Schools Seeder

This seeder populates the database with realistic data for secondary schools within Kaduna North Local Government Area, Kaduna State.

## What It Seeds

1. **Wards** - All 13 wards in Kaduna North LGA
2. **Schools** - 18 secondary schools (8 Government + 10 Private)
3. **Users** - 1 SMO admin + 1 SA (School Administrator) for each school
4. **Students** - 20-50 students per school (total ~540-900 students)
5. **Staff** - 10-25 staff members per school (total ~180-450 staff)
6. **Announcements** - 5 sample announcements
7. **Audit Logs** - 50 sample audit log entries

## How to Run

### Option 1: Via Browser
Navigate to: `http://localhost/KSSQAA/setup/seed_kaduna_north_schools.php`

### Option 2: Via Command Line
```bash
cd C:\wamp64\www\KSSQAA
php setup/seed_kaduna_north_schools.php
```

## Default Login Credentials

After running the seeder, you can log in with:

- **SMO (System Manager)**: 
  - Username: `admin`
  - Password: `admin123`

- **SA (School Administrator)**: 
  - Username: `[schoolname]_sa` (e.g., `governmentgirlssecondaryschoolkawo_sa`)
  - Password: `school123`

## Schools Included

### Government Schools (8)
1. Government Girls Secondary School Kawo
2. Government Technical College Kaduna
3. Rimi College Senior
4. Sardauna Memorial College
5. Government Girls Secondary School Angwa Rimi
6. Government Secondary School Angwan Sarki
7. Government Girls Secondary School Independence Way
8. Government Junior Secondary School Badarawa

### Private Schools (10)
1. Talent International School
2. Nuruddeen Secondary School Malali
3. El-Amin International School
4. Capital Science Academy
5. Ahmadu Bello Memorial Secondary School
6. St. Michael's Secondary School
7. Al-Iman Secondary School
8. Greenfield Academy
9. Crescent International School
10. Royal Academy Kaduna
11. Excellence Secondary School

## Data Characteristics

- **Realistic Nigerian names** for students and staff
- **Valid CAC numbers** (BN/RC format)
- **Valid TIN numbers** (12-digit numeric)
- **Proper ward assignments** based on actual locations
- **Mixed school statuses** (mostly Active, some Pending)
- **Diverse class levels** (JSS 1-3, SS 1-3)
- **Various staff positions** (Principal, Teachers, Bursar, etc.)
- **Realistic dates** (birth dates, admission dates, employment dates)

## Notes

- The seeder uses transactions, so if any error occurs, all changes will be rolled back
- Existing data will not be duplicated (uses ON DUPLICATE KEY UPDATE)
- All schools are assigned to Kaduna North LGA
- Student and Staff IDs are auto-generated using the system's ID generation functions

## Security Warning

⚠️ **Important**: After running the seeder, consider removing or securing this file, especially in production environments, as it contains default credentials.



