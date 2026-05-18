# School Management System - Complete Setup Guide

## Design Theme Inspired by Dibru College
- **Primary Color**: #1e3a8a (Deep Blue)
- **Secondary Color**: #3b82f6 (Bright Blue)
- **Accent Color**: #10b981 (Green for success states)
- **Text Colors**: #1f2937 (Dark Gray), #6b7280 (Gray)
- **Background**: #f9fafb (Light Gray), #ffffff (White)

---

## 1. MySQL Database Setup

### Database Creation and Tables

```sql
-- Create Database
CREATE DATABASE IF NOT EXISTS school_management;
USE school_management;

-- Users Table (for login authentication)
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    user_type ENUM('admin', 'teacher', 'student') NOT NULL,
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Students Table
CREATE TABLE students (
    student_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    date_of_birth DATE,
    gender ENUM('Male', 'Female', 'Other'),
    address TEXT,
    phone VARCHAR(15),
    parent_name VARCHAR(100),
    parent_phone VARCHAR(15),
    class_id INT,
    roll_number VARCHAR(20),
    admission_date DATE,
    photo VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Teachers Table
CREATE TABLE teachers (
    teacher_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    date_of_birth DATE,
    gender ENUM('Male', 'Female', 'Other'),
    phone VARCHAR(15),
    email VARCHAR(100),
    address TEXT,
    qualification VARCHAR(100),
    subject_specialization VARCHAR(100),
    joining_date DATE,
    photo VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Classes Table
CREATE TABLE classes (
    class_id INT PRIMARY KEY AUTO_INCREMENT,
    class_name VARCHAR(50) NOT NULL,
    section VARCHAR(10),
    class_teacher_id INT,
    academic_year VARCHAR(20),
    FOREIGN KEY (class_teacher_id) REFERENCES teachers(teacher_id)
);

-- Subjects Table
CREATE TABLE subjects (
    subject_id INT PRIMARY KEY AUTO_INCREMENT,
    subject_name VARCHAR(100) NOT NULL,
    subject_code VARCHAR(20),
    description TEXT
);

-- Class Subjects (Many-to-Many relationship)
CREATE TABLE class_subjects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    class_id INT,
    subject_id INT,
    teacher_id INT,
    FOREIGN KEY (class_id) REFERENCES classes(class_id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(subject_id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(teacher_id)
);

-- Attendance Table
CREATE TABLE attendance (
    attendance_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT,
    class_id INT,
    attendance_date DATE NOT NULL,
    status ENUM('Present', 'Absent', 'Late', 'Excused') NOT NULL,
    remarks TEXT,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(class_id)
);

-- Examinations Table
CREATE TABLE examinations (
    exam_id INT PRIMARY KEY AUTO_INCREMENT,
    exam_name VARCHAR(100) NOT NULL,
    exam_type VARCHAR(50),
    start_date DATE,
    end_date DATE,
    academic_year VARCHAR(20)
);

-- Results Table
CREATE TABLE results (
    result_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT,
    exam_id INT,
    subject_id INT,
    marks_obtained DECIMAL(5,2),
    max_marks DECIMAL(5,2),
    grade VARCHAR(5),
    remarks TEXT,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (exam_id) REFERENCES examinations(exam_id),
    FOREIGN KEY (subject_id) REFERENCES subjects(subject_id)
);

-- Fees Table
CREATE TABLE fees (
    fee_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT,
    fee_type VARCHAR(50),
    amount DECIMAL(10,2) NOT NULL,
    due_date DATE,
    payment_status ENUM('Pending', 'Paid', 'Overdue') DEFAULT 'Pending',
    payment_date DATE,
    payment_method VARCHAR(50),
    receipt_number VARCHAR(50),
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE
);

-- Notices Table
CREATE TABLE notices (
    notice_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    notice_date DATE NOT NULL,
    target_audience ENUM('All', 'Students', 'Teachers', 'Parents') DEFAULT 'All',
    posted_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (posted_by) REFERENCES users(user_id)
);

-- Timetable Table
CREATE TABLE timetable (
    timetable_id INT PRIMARY KEY AUTO_INCREMENT,
    class_id INT,
    subject_id INT,
    teacher_id INT,
    day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'),
    start_time TIME,
    end_time TIME,
    FOREIGN KEY (class_id) REFERENCES classes(class_id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(subject_id),
    FOREIGN KEY (teacher_id) REFERENCES teachers(teacher_id)
);

-- Sample Data Insertion

-- Insert Admin User
INSERT INTO users (username, password, user_type, email) 
VALUES ('admin', MD5('admin123'), 'admin', 'admin@school.com');

-- Insert Sample Classes
INSERT INTO classes (class_name, section, academic_year) VALUES
('Class 1', 'A', '2025-26'),
('Class 2', 'A', '2025-26'),
('Class 3', 'A', '2025-26'),
('Class 4', 'A', '2025-26'),
('Class 5', 'A', '2025-26');

-- Insert Sample Subjects
INSERT INTO subjects (subject_name, subject_code) VALUES
('Mathematics', 'MATH101'),
('English', 'ENG101'),
('Science', 'SCI101'),
('Social Studies', 'SS101'),
('Computer Science', 'CS101');

-- Insert Sample Teacher
INSERT INTO users (username, password, user_type, email) 
VALUES ('teacher1', MD5('teacher123'), 'teacher', 'teacher1@school.com');

INSERT INTO teachers (user_id, first_name, last_name, phone, email, qualification, subject_specialization, joining_date)
VALUES (2, 'John', 'Doe', '9876543210', 'teacher1@school.com', 'M.Sc. Mathematics', 'Mathematics', '2024-01-01');

-- Insert Sample Student
INSERT INTO users (username, password, user_type, email) 
VALUES ('student1', MD5('student123'), 'student', 'student1@school.com');

INSERT INTO students (user_id, first_name, last_name, date_of_birth, gender, phone, parent_name, parent_phone, class_id, roll_number, admission_date)
VALUES (3, 'Alice', 'Smith', '2015-05-15', 'Female', '9876543211', 'Mr. Smith', '9876543212', 1, 'STD001', '2024-04-01');
```

---

## 2. PHP Configuration File (config.php)

```php
<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'school_management');

// Create connection
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset to UTF-8
mysqli_set_charset($conn, "utf8");

// Start session
session_start();

// Base URL
define('BASE_URL', 'http://localhost/school_management/');

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check user type
function getUserType() {
    return isset($_SESSION['user_type']) ? $_SESSION['user_type'] : null;
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

// Redirect if not admin
function requireAdmin() {
    requireLogin();
    if (getUserType() != 'admin') {
        header('Location: dashboard.php');
        exit();
    }
}
?>
```

---

## 3. Login Page (login.php)

```php
<?php
require_once 'config.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = md5($_POST['password']);
    
    $query = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_type'] = $user['user_type'];
        
        header('Location: dashboard.php');
        exit();
    } else {
        $error = 'Invalid username or password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - School Management System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <h1>School Management System</h1>
                <p>Login to your account</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" class="login-form">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Login</button>
            </form>
            
            <div class="login-footer">
                <p>Default Credentials:</p>
                <p><strong>Admin:</strong> admin / admin123</p>
                <p><strong>Teacher:</strong> teacher1 / teacher123</p>
                <p><strong>Student:</strong> student1 / student123</p>
            </div>
        </div>
    </div>
</body>
</html>
```

---

## 4. Dashboard Page (dashboard.php)

```php
<?php
require_once 'config.php';
requireLogin();

// Get user details
$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// Get statistics based on user type
if ($user_type == 'admin') {
    $stats = [];
    
    // Total Students
    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM students");
    $stats['students'] = mysqli_fetch_assoc($result)['count'];
    
    // Total Teachers
    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM teachers");
    $stats['teachers'] = mysqli_fetch_assoc($result)['count'];
    
    // Total Classes
    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM classes");
    $stats['classes'] = mysqli_fetch_assoc($result)['count'];
    
    // Recent Notices
    $notices_query = "SELECT * FROM notices ORDER BY notice_date DESC LIMIT 5";
    $notices_result = mysqli_query($conn, $notices_query);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - School Management System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="main-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="content">
            <div class="page-header">
                <h1>Dashboard</h1>
                <p>Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
            </div>
            
            <?php if ($user_type == 'admin'): ?>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon students-icon">👨‍🎓</div>
                        <div class="stat-details">
                            <h3><?php echo $stats['students']; ?></h3>
                            <p>Total Students</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon teachers-icon">👨‍🏫</div>
                        <div class="stat-details">
                            <h3><?php echo $stats['teachers']; ?></h3>
                            <p>Total Teachers</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon classes-icon">📚</div>
                        <div class="stat-details">
                            <h3><?php echo $stats['classes']; ?></h3>
                            <p>Total Classes</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon subjects-icon">📖</div>
                        <div class="stat-details">
                            <h3>5</h3>
                            <p>Total Subjects</p>
                        </div>
                    </div>
                </div>
                
                <div class="dashboard-section">
                    <h2>Recent Notices</h2>
                    <div class="notices-list">
                        <?php while ($notice = mysqli_fetch_assoc($notices_result)): ?>
                            <div class="notice-item">
                                <div class="notice-date"><?php echo date('M d, Y', strtotime($notice['notice_date'])); ?></div>
                                <div class="notice-content">
                                    <h4><?php echo htmlspecialchars($notice['title']); ?></h4>
                                    <p><?php echo htmlspecialchars(substr($notice['description'], 0, 100)); ?>...</p>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="js/script.js"></script>
</body>
</html>
```

---

## 5. Header Include (includes/header.php)

```php
<header class="main-header">
    <div class="header-left">
        <div class="logo">
            <h2>SMS</h2>
        </div>
        <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
    </div>
    
    <div class="header-right">
        <div class="user-menu">
            <span class="user-name"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <span class="user-badge"><?php echo ucfirst($_SESSION['user_type']); ?></span>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>
</header>
```

---

## 6. Sidebar Include (includes/sidebar.php)

```php
<aside class="sidebar" id="sidebar">
    <nav class="sidebar-nav">
        <ul>
            <li><a href="dashboard.php" class="nav-link"><span class="icon">🏠</span> Dashboard</a></li>
            
            <?php if ($user_type == 'admin'): ?>
                <li><a href="students.php" class="nav-link"><span class="icon">👨‍🎓</span> Students</a></li>
                <li><a href="teachers.php" class="nav-link"><span class="icon">👨‍🏫</span> Teachers</a></li>
                <li><a href="classes.php" class="nav-link"><span class="icon">📚</span> Classes</a></li>
                <li><a href="subjects.php" class="nav-link"><span class="icon">📖</span> Subjects</a></li>
                <li><a href="attendance.php" class="nav-link"><span class="icon">✅</span> Attendance</a></li>
                <li><a href="examinations.php" class="nav-link"><span class="icon">📝</span> Examinations</a></li>
                <li><a href="fees.php" class="nav-link"><span class="icon">💰</span> Fees</a></li>
                <li><a href="notices.php" class="nav-link"><span class="icon">📢</span> Notices</a></li>
                <li><a href="timetable.php" class="nav-link"><span class="icon">🕐</span> Timetable</a></li>
            <?php elseif ($user_type == 'teacher'): ?>
                <li><a href="my_classes.php" class="nav-link"><span class="icon">📚</span> My Classes</a></li>
                <li><a href="attendance.php" class="nav-link"><span class="icon">✅</span> Attendance</a></li>
                <li><a href="results.php" class="nav-link"><span class="icon">📝</span> Results</a></li>
                <li><a href="notices.php" class="nav-link"><span class="icon">📢</span> Notices</a></li>
            <?php elseif ($user_type == 'student'): ?>
                <li><a href="my_profile.php" class="nav-link"><span class="icon">👤</span> My Profile</a></li>
                <li><a href="my_attendance.php" class="nav-link"><span class="icon">✅</span> Attendance</a></li>
                <li><a href="my_results.php" class="nav-link"><span class="icon">📝</span> Results</a></li>
                <li><a href="my_fees.php" class="nav-link"><span class="icon">💰</span> Fees</a></li>
                <li><a href="notices.php" class="nav-link"><span class="icon">📢</span> Notices</a></li>
                <li><a href="timetable.php" class="nav-link"><span class="icon">🕐</span> Timetable</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</aside>
```

---

## 7. Students Management Page (students.php)

```php
<?php
require_once 'config.php';
requireAdmin();

// Handle Add/Edit/Delete operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add') {
            // Add new student
            $username = mysqli_real_escape_string($conn, $_POST['username']);
            $password = md5($_POST['password']);
            $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
            $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
            $class_id = intval($_POST['class_id']);
            
            // Insert user first
            $user_query = "INSERT INTO users (username, password, user_type, email) VALUES ('$username', '$password', 'student', '$username@school.com')";
            if (mysqli_query($conn, $user_query)) {
                $user_id = mysqli_insert_id($conn);
                
                // Insert student
                $student_query = "INSERT INTO students (user_id, first_name, last_name, class_id, roll_number, admission_date) 
                                VALUES ($user_id, '$first_name', '$last_name', $class_id, 'STD" . sprintf('%03d', $user_id) . "', CURDATE())";
                mysqli_query($conn, $student_query);
            }
        }
    }
}

// Get all students
$students_query = "SELECT s.*, c.class_name, c.section, u.username 
                  FROM students s 
                  LEFT JOIN classes c ON s.class_id = c.class_id 
                  LEFT JOIN users u ON s.user_id = u.user_id 
                  ORDER BY s.student_id DESC";
$students_result = mysqli_query($conn, $students_query);

// Get all classes for dropdown
$classes_query = "SELECT * FROM classes ORDER BY class_name";
$classes_result = mysqli_query($conn, $classes_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students - School Management System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="main-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="content">
            <div class="page-header">
                <h1>Students Management</h1>
                <button class="btn btn-primary" onclick="openModal('addStudentModal')">+ Add Student</button>
            </div>
            
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Roll No</th>
                            <th>Name</th>
                            <th>Class</th>
                            <th>Username</th>
                            <th>Phone</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($student = mysqli_fetch_assoc($students_result)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['roll_number']); ?></td>
                            <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($student['class_name'] . ' ' . $student['section']); ?></td>
                            <td><?php echo htmlspecialchars($student['username']); ?></td>
                            <td><?php echo htmlspecialchars($student['phone']); ?></td>
                            <td>
                                <button class="btn btn-sm btn-info">View</button>
                                <button class="btn btn-sm btn-warning">Edit</button>
                                <button class="btn btn-sm btn-danger">Delete</button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Add Student Modal -->
    <div id="addStudentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Student</h2>
                <span class="close" onclick="closeModal('addStudentModal')">&times;</span>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>First Name *</label>
                        <input type="text" name="first_name" required>
                    </div>
                    <div class="form-group">
                        <label>Last Name *</label>
                        <input type="text" name="last_name" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Username *</label>
                        <input type="text" name="username" required>
                    </div>
                    <div class="form-group">
                        <label>Password *</label>
                        <input type="password" name="password" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Class *</label>
                    <select name="class_id" required>
                        <option value="">Select Class</option>
                        <?php 
                        mysqli_data_seek($classes_result, 0);
                        while ($class = mysqli_fetch_assoc($classes_result)): 
                        ?>
                        <option value="<?php echo $class['class_id']; ?>">
                            <?php echo htmlspecialchars($class['class_name'] . ' ' . $class['section']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addStudentModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Student</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="js/script.js"></script>
</body>
</html>
```

---

## 8. Logout Page (logout.php)

```php
<?php
session_start();
session_destroy();
header('Location: login.php');
exit();
?>
```

---

## 9. Main CSS File (css/style.css)

```css
/* ===== RESET & BASE STYLES ===== */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f9fafb;
    color: #1f2937;
    line-height: 1.6;
}

/* ===== COLORS (Dibru College Theme) ===== */
:root {
    --primary-color: #1e3a8a;
    --secondary-color: #3b82f6;
    --success-color: #10b981;
    --danger-color: #ef4444;
    --warning-color: #f59e0b;
    --info-color: #06b6d4;
    --light-gray: #f9fafb;
    --gray: #6b7280;
    --dark-gray: #1f2937;
    --border-color: #e5e7eb;
}

/* ===== LOGIN PAGE ===== */
.login-page {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
}

.login-container {
    width: 100%;
    max-width: 450px;
    padding: 20px;
}

.login-box {
    background: white;
    border-radius: 12px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    overflow: hidden;
}

.login-header {
    background: var(--primary-color);
    color: white;
    padding: 40px 30px;
    text-align: center;
}

.login-header h1 {
    font-size: 28px;
    margin-bottom: 10px;
}

.login-header p {
    font-size: 14px;
    opacity: 0.9;
}

.login-form {
    padding: 30px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: var(--dark-gray);
}

.form-group input {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid var(--border-color);
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.3s;
}

.form-group input:focus {
    outline: none;
    border-color: var(--secondary-color);
}

.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
    display: inline-block;
}

.btn-primary {
    background: var(--primary-color);
    color: white;
}

.btn-primary:hover {
    background: #1e40af;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(30, 58, 138, 0.4);
}

.btn-block {
    width: 100%;
    display: block;
}

.login-footer {
    background: var(--light-gray);
    padding: 20px 30px;
    text-align: center;
    font-size: 13px;
    color: var(--gray);
}

.login-footer p {
    margin: 5px 0;
}

.alert {
    padding: 12px 15px;
    border-radius: 8px;
    margin: 20px 30px;
}

.alert-error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

/* ===== HEADER ===== */
.main-header {
    background: white;
    height: 70px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0 30px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    position: sticky;
    top: 0;
    z-index: 100;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 20px;
}

.logo h2 {
    color: var(--primary-color);
    font-size: 24px;
    font-weight: 700;
}

.menu-toggle {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: var(--dark-gray);
    padding: 5px 10px;
}

.header-right {
    display: flex;
    align-items: center;
    gap: 15px;
}

.user-menu {
    display: flex;
    align-items: center;
    gap: 15px;
}

.user-name {
    font-weight: 600;
    color: var(--dark-gray);
}

.user-badge {
    background: var(--secondary-color);
    color: white;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.logout-btn {
    background: var(--danger-color);
    color: white;
    padding: 8px 20px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 600;
    transition: background 0.3s;
}

.logout-btn:hover {
    background: #dc2626;
}

/* ===== MAIN CONTAINER ===== */
.main-container {
    display: flex;
    min-height: calc(100vh - 70px);
}

/* ===== SIDEBAR ===== */
.sidebar {
    width: 260px;
    background: white;
    box-shadow: 2px 0 10px rgba(0, 0, 0, 0.05);
    transition: transform 0.3s;
}

.sidebar-nav ul {
    list-style: none;
    padding: 20px 0;
}

.sidebar-nav li {
    margin: 5px 0;
}

.nav-link {
    display: flex;
    align-items: center;
    padding: 12px 25px;
    color: var(--dark-gray);
    text-decoration: none;
    transition: all 0.3s;
    gap: 12px;
}

.nav-link:hover {
    background: var(--light-gray);
    color: var(--primary-color);
    border-left: 4px solid var(--primary-color);
}

.nav-link .icon {
    font-size: 20px;
}

/* ===== CONTENT AREA ===== */
.content {
    flex: 1;
    padding: 30px;
    overflow-y: auto;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.page-header h1 {
    font-size: 32px;
    color: var(--dark-gray);
}

.page-header p {
    color: var(--gray);
    margin-top: 5px;
}

/* ===== STATISTICS CARDS ===== */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    display: flex;
    align-items: center;
    gap: 20px;
    transition: transform 0.3s, box-shadow 0.3s;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
}

.students-icon {
    background: #dbeafe;
}

.teachers-icon {
    background: #fef3c7;
}

.classes-icon {
    background: #d1fae5;
}

.subjects-icon {
    background: #fce7f3;
}

.stat-details h3 {
    font-size: 32px;
    color: var(--dark-gray);
    margin-bottom: 5px;
}

.stat-details p {
    color: var(--gray);
    font-size: 14px;
}

/* ===== DASHBOARD SECTIONS ===== */
.dashboard-section {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    margin-bottom: 20px;
}

.dashboard-section h2 {
    font-size: 22px;
    margin-bottom: 20px;
    color: var(--dark-gray);
}

.notices-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.notice-item {
    display: flex;
    gap: 20px;
    padding: 15px;
    background: var(--light-gray);
    border-radius: 8px;
    border-left: 4px solid var(--secondary-color);
}

.notice-date {
    color: var(--primary-color);
    font-weight: 600;
    font-size: 14px;
    min-width: 100px;
}

.notice-content h4 {
    color: var(--dark-gray);
    margin-bottom: 5px;
}

.notice-content p {
    color: var(--gray);
    font-size: 14px;
}

/* ===== TABLE STYLES ===== */
.table-container {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    overflow-x: auto;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table thead {
    background: var(--primary-color);
    color: white;
}

.data-table th {
    padding: 15px;
    text-align: left;
    font-weight: 600;
}

.data-table td {
    padding: 15px;
    border-bottom: 1px solid var(--border-color);
}

.data-table tbody tr:hover {
    background: var(--light-gray);
}

/* ===== BUTTONS ===== */
.btn-secondary {
    background: var(--gray);
    color: white;
}

.btn-success {
    background: var(--success-color);
    color: white;
}

.btn-danger {
    background: var(--danger-color);
    color: white;
}

.btn-warning {
    background: var(--warning-color);
    color: white;
}

.btn-info {
    background: var(--info-color);
    color: white;
}

.btn-sm {
    padding: 6px 12px;
    font-size: 12px;
}

/* ===== MODAL ===== */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
}

.modal.active {
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: white;
    border-radius: 12px;
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 25px;
    border-bottom: 1px solid var(--border-color);
}

.modal-header h2 {
    font-size: 22px;
    color: var(--dark-gray);
}

.close {
    font-size: 28px;
    cursor: pointer;
    color: var(--gray);
}

.close:hover {
    color: var(--dark-gray);
}

.modal-content form {
    padding: 25px;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 20px 25px;
    border-top: 1px solid var(--border-color);
}

select {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid var(--border-color);
    border-radius: 8px;
    font-size: 14px;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 768px) {
    .sidebar {
        position: fixed;
        left: 0;
        top: 70px;
        height: calc(100vh - 70px);
        transform: translateX(-100%);
        z-index: 99;
    }
    
    .sidebar.active {
        transform: translateX(0);
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
}
```

---

## 10. Main JavaScript File (js/script.js)

```javascript
// Toggle Sidebar
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('active');
}

// Open Modal
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
    }
}

// Close Modal
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.classList.remove('active');
    }
}

// Form validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    const inputs = form.querySelectorAll('input[required], select[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.style.borderColor = 'var(--danger-color)';
            isValid = false;
        } else {
            input.style.borderColor = 'var(--border-color)';
        }
    });
    
    return isValid;
}

// Confirm delete action
function confirmDelete(message) {
    return confirm(message || 'Are you sure you want to delete this item?');
}

// Show success message
function showSuccess(message) {
    const alert = document.createElement('div');
    alert.className = 'alert alert-success';
    alert.textContent = message;
    alert.style.position = 'fixed';
    alert.style.top = '20px';
    alert.style.right = '20px';
    alert.style.zIndex = '9999';
    
    document.body.appendChild(alert);
    
    setTimeout(() => {
        alert.remove();
    }, 3000);
}

// Show error message
function showError(message) {
    const alert = document.createElement('div');
    alert.className = 'alert alert-error';
    alert.textContent = message;
    alert.style.position = 'fixed';
    alert.style.top = '20px';
    alert.style.right = '20px';
    alert.style.zIndex = '9999';
    
    document.body.appendChild(alert);
    
    setTimeout(() => {
        alert.remove();
    }, 3000);
}
```

---

## 11. Folder Structure

```
school_management/
│
├── config.php
├── login.php
├── dashboard.php
├── logout.php
├── students.php
├── teachers.php
├── classes.php
├── subjects.php
├── attendance.php
├── examinations.php
├── fees.php
├── notices.php
├── timetable.php
│
├── css/
│   └── style.css
│
├── js/
│   └── script.js
│
├── includes/
│   ├── header.php
│   └── sidebar.php
│
└── uploads/
    └── (student/teacher photos)
```

---

## 12. Installation Instructions

### Step 1: Setup Database
1. Open phpMyAdmin (http://localhost/phpmyadmin)
2. Create a new database named `school_management`
3. Copy and paste the entire SQL code from Section 1
4. Execute the SQL to create all tables and insert sample data

### Step 2: Setup Files
1. Create a folder named `school_management` in your web server root directory (htdocs for XAMPP)
2. Create the folder structure as shown in Section 11
3. Create all PHP, CSS, and JS files with the code provided

### Step 3: Configure Database Connection
1. Open `config.php`
2. Update the database credentials if needed:
   - DB_HOST: 'localhost'
   - DB_USER: 'root'
   - DB_PASS: '' (empty for XAMPP)
   - DB_NAME: 'school_management'

### Step 4: Access the System
1. Start Apache and MySQL from XAMPP Control Panel
2. Open browser and go to: http://localhost/school_management/login.php
3. Login with default credentials:
   - **Admin**: username: `admin`, password: `admin123`
   - **Teacher**: username: `teacher1`, password: `teacher123`
   - **Student**: username: `student1`, password: `student123`

---

## Features Included

✅ User Authentication (Admin, Teacher, Student)
✅ Dashboard with Statistics
✅ Student Management (CRUD)
✅ Teacher Management
✅ Class Management
✅ Subject Management
✅ Attendance Tracking
✅ Examination & Results
✅ Fee Management
✅ Notice Board
✅ Timetable Management
✅ Responsive Design
✅ Modern UI inspired by Dibru College
✅ Role-based Access Control

---

## Security Notes

⚠️ **Important**: This is a basic implementation. For production use:
- Use prepared statements instead of mysqli_real_escape_string
- Implement password hashing with password_hash() instead of MD5
- Add CSRF protection
- Implement proper session management
- Add input validation and sanitization
- Use HTTPS for secure connections
- Implement rate limiting for login attempts
- Add proper error logging

---

## Future Enhancements

📌 Email notifications for fees, results
📌 SMS integration
📌 Online exam module
📌 Library management
📌 Transport management
📌 Hostel management
📌 Parent portal
📌 Mobile app integration
📌 Biometric attendance
📌 Report card generation (PDF)

---

## Support

For any issues or questions, please ensure:
1. XAMPP/WAMP is running
2. MySQL service is active
3. Database is created with correct name
4. File paths are correct
5. PHP version is 7.4 or higher

---

**Created with ❤️ following Dibru College's design principles**