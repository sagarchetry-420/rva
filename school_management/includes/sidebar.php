<?php
/**
 * Sidebar Include - Role-Based Navigation
 * Shows different menu items based on user type
 */
$userType = getUserType();
$currentPage = getCurrentPage();
$currentFolder = getCurrentFolder();
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h3><?php echo APP_NAME; ?></h3>
        <p class="sidebar-subtitle"><?php echo ucfirst($userType); ?> Panel</p>
    </div>
    
    <nav class="sidebar-nav">
        <ul>
            <?php if ($userType === 'admin'): ?>
                <!-- ═══ ADMIN NAVIGATION ═══ -->
                <li class="nav-section-title">Main</li>
                <li>
                    <a href="<?php echo BASE_URL; ?>/admin/dashboard.php" 
                       class="nav-link <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
                        <span class="icon">🏠</span> Dashboard
                    </a>
                </li>
                
                <li class="nav-section-title">Management</li>
                <li>
                    <a href="<?php echo BASE_URL; ?>/admin/students.php" 
                       class="nav-link <?php echo $currentPage === 'students.php' ? 'active' : ''; ?>">
                        <span class="icon">👨‍🎓</span> Students
                    </a>
                </li>
                <li>
                    <a href="<?php echo BASE_URL; ?>/admin/teachers.php" 
                       class="nav-link <?php echo $currentPage === 'teachers.php' ? 'active' : ''; ?>">
                        <span class="icon">👨‍🏫</span> Teachers
                    </a>
                </li>
                <li>
                    <a href="<?php echo BASE_URL; ?>/admin/classes.php" 
                       class="nav-link <?php echo $currentPage === 'classes.php' ? 'active' : ''; ?>">
                        <span class="icon">📚</span> Classes
                    </a>
                </li>
                <li>
                    <a href="<?php echo BASE_URL; ?>/admin/subjects.php" 
                       class="nav-link <?php echo $currentPage === 'subjects.php' ? 'active' : ''; ?>">
                        <span class="icon">📖</span> Subjects
                    </a>
                </li>
                
                <li class="nav-section-title">Academic</li>
                <li>
                    <a href="<?php echo BASE_URL; ?>/admin/attendance.php" 
                       class="nav-link <?php echo $currentPage === 'attendance.php' ? 'active' : ''; ?>">
                        <span class="icon">✅</span> Attendance
                    </a>
                </li>
                <li>
                    <a href="<?php echo BASE_URL; ?>/admin/examinations.php" 
                       class="nav-link <?php echo $currentPage === 'examinations.php' ? 'active' : ''; ?>">
                        <span class="icon">📝</span> Examinations
                    </a>
                </li>
                
                <li class="nav-section-title">Finance & Info</li>
                <li>
                    <a href="<?php echo BASE_URL; ?>/admin/fees.php" 
                       class="nav-link <?php echo $currentPage === 'fees.php' ? 'active' : ''; ?>">
                        <span class="icon">💰</span> Fees
                    </a>
                </li>
                <li>
                    <a href="<?php echo BASE_URL; ?>/admin/notices.php" 
                       class="nav-link <?php echo $currentPage === 'notices.php' ? 'active' : ''; ?>">
                        <span class="icon">📢</span> Notices
                    </a>
                </li>
                <li>
                    <a href="<?php echo BASE_URL; ?>/admin/timetable.php" 
                       class="nav-link <?php echo $currentPage === 'timetable.php' ? 'active' : ''; ?>">
                        <span class="icon">🕐</span> Timetable
                    </a>
                </li>

            <?php elseif ($userType === 'teacher'): ?>
                <!-- ═══ TEACHER NAVIGATION ═══ -->
                <li class="nav-section-title">Main</li>
                <li>
                    <a href="<?php echo BASE_URL; ?>/teacher/dashboard.php" 
                       class="nav-link <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
                        <span class="icon">🏠</span> Dashboard
                    </a>
                </li>
                
                <li class="nav-section-title">Teaching</li>
                <li>
                    <a href="<?php echo BASE_URL; ?>/teacher/my_classes.php" 
                       class="nav-link <?php echo $currentPage === 'my_classes.php' ? 'active' : ''; ?>">
                        <span class="icon">📚</span> My Classes
                    </a>
                </li>
                <li>
                    <a href="<?php echo BASE_URL; ?>/teacher/attendance.php" 
                       class="nav-link <?php echo $currentPage === 'attendance.php' ? 'active' : ''; ?>">
                        <span class="icon">✅</span> Attendance
                    </a>
                </li>
                <li>
                    <a href="<?php echo BASE_URL; ?>/teacher/results.php" 
                       class="nav-link <?php echo $currentPage === 'results.php' ? 'active' : ''; ?>">
                        <span class="icon">📝</span> Results
                    </a>
                </li>
                
                <li class="nav-section-title">Info</li>
                <li>
                    <a href="<?php echo BASE_URL; ?>/teacher/notices.php" 
                       class="nav-link <?php echo $currentPage === 'notices.php' ? 'active' : ''; ?>">
                        <span class="icon">📢</span> Notices
                    </a>
                </li>
                <li>
                    <a href="<?php echo BASE_URL; ?>/teacher/timetable.php" 
                       class="nav-link <?php echo $currentPage === 'timetable.php' ? 'active' : ''; ?>">
                        <span class="icon">🕐</span> Timetable
                    </a>
                </li>

            <?php elseif ($userType === 'student'): ?>
                <!-- ═══ STUDENT NAVIGATION ═══ -->
                <li class="nav-section-title">Main</li>
                <li>
                    <a href="<?php echo BASE_URL; ?>/student/dashboard.php" 
                       class="nav-link <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
                        <span class="icon">🏠</span> Dashboard
                    </a>
                </li>
                
                <li class="nav-section-title">My Academic</li>
                <li>
                    <a href="<?php echo BASE_URL; ?>/student/profile.php" 
                       class="nav-link <?php echo $currentPage === 'profile.php' ? 'active' : ''; ?>">
                        <span class="icon">👤</span> My Profile
                    </a>
                </li>
                <li>
                    <a href="<?php echo BASE_URL; ?>/student/attendance.php" 
                       class="nav-link <?php echo $currentPage === 'attendance.php' ? 'active' : ''; ?>">
                        <span class="icon">✅</span> My Attendance
                    </a>
                </li>
                <li>
                    <a href="<?php echo BASE_URL; ?>/student/results.php" 
                       class="nav-link <?php echo $currentPage === 'results.php' ? 'active' : ''; ?>">
                        <span class="icon">📝</span> My Results
                    </a>
                </li>
                <li>
                    <a href="<?php echo BASE_URL; ?>/student/fees.php" 
                       class="nav-link <?php echo $currentPage === 'fees.php' ? 'active' : ''; ?>">
                        <span class="icon">💰</span> My Fees
                    </a>
                </li>
                
                <li class="nav-section-title">Info</li>
                <li>
                    <a href="<?php echo BASE_URL; ?>/student/notices.php" 
                       class="nav-link <?php echo $currentPage === 'notices.php' ? 'active' : ''; ?>">
                        <span class="icon">📢</span> Notices
                    </a>
                </li>
                <li>
                    <a href="<?php echo BASE_URL; ?>/student/timetable.php" 
                       class="nav-link <?php echo $currentPage === 'timetable.php' ? 'active' : ''; ?>">
                        <span class="icon">🕐</span> Timetable
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
</aside>

