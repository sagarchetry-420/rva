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
    <!-- Sidebar header removed as requested -->
    
    
    <nav class="sidebar-nav">
        <ul>
            <?php if ($userType === 'admin'): ?>
                <!-- ═══ ADMIN NAVIGATION ═══ -->
                <li class="nav-section-title">Main</li>
                <li>
                    <a href="<?php echo BASE_URL; ?>/admin/dashboard.php" 
                       class="nav-link <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
                        <span class="icon"><i class="fa-solid fa-house"></i></span> Dashboard
                    </a>
                </li>
                
                <li class="nav-section-title">Management</li>
                <li>
                    <a href="<?php echo BASE_URL; ?>/admin/students.php" 
                       class="nav-link <?php echo $currentPage === 'students.php' ? 'active' : ''; ?>">
                        <span class="icon"><i class="fa-solid fa-user-graduate"></i></span> Students
                    </a>
                </li>
                <li>
                    <a href="<?php echo BASE_URL; ?>/admin/teachers.php" 
                       class="nav-link <?php echo $currentPage === 'teachers.php' ? 'active' : ''; ?>">
                        <span class="icon"><i class="fa-solid fa-chalkboard-teacher"></i></span> Teachers
                    </a>
                </li>
                <li>
                    <a href="<?php echo BASE_URL; ?>/admin/classes.php" 
                       class="nav-link <?php echo $currentPage === 'classes.php' ? 'active' : ''; ?>">
                        <span class="icon"><i class="fa-solid fa-book-open"></i></span> Classes
                    </a>
                </li>
                <li>
                    <a href="<?php echo BASE_URL; ?>/admin/subjects.php" 
                       class="nav-link <?php echo $currentPage === 'subjects.php' ? 'active' : ''; ?>">
                        <span class="icon"><i class="fa-solid fa-book"></i></span> Subjects
                    </a>
                </li>
                
                <li class="nav-section-title">Academic</li>
                <li>
                    <a href="<?php echo BASE_URL; ?>/admin/attendance.php"
                       class="nav-link <?php echo $currentPage === 'attendance.php' ? 'active' : ''; ?>">
                        <span class="icon"><i class="fa-solid fa-check-to-slot"></i></span> Attendance
                    </a>
                </li>
                <li>
                    <a href="<?php echo BASE_URL; ?>/admin/examinations.php"
                       class="nav-link <?php echo $currentPage === 'examinations.php' ? 'active' : ''; ?>">
                        <span class="icon"><i class="fa-solid fa-file-pen"></i></span> Examinations
                    </a>
                </li>
                <li>
                    <a href="<?php echo BASE_URL; ?>/admin/promotion_panel.php"
                       class="nav-link <?php echo $currentPage === 'promotion_panel.php' ? 'active' : ''; ?>">
                        <span class="icon"><i class="fa-solid fa-arrow-up"></i></span> Student Promotions
                    </a>
                </li>
                
                <li class="nav-section-title">Finance & Info</li>
                <li>
                    <a href="<?php echo BASE_URL; ?>/admin/fees.php" 
                       class="nav-link <?php echo $currentPage === 'fees.php' ? 'active' : ''; ?>">
                        <span class="icon"><i class="fa-solid fa-money-bill-wave"></i></span> Fees
                    </a>
                </li>
                <li>
                    <a href="<?php echo BASE_URL; ?>/admin/notices.php" 
                       class="nav-link <?php echo $currentPage === 'notices.php' ? 'active' : ''; ?>">
                        <span class="icon"><i class="fa-solid fa-bullhorn"></i></span> Notices
                    </a>
                </li>
                <li>
                    <a href="<?php echo BASE_URL; ?>/admin/timetable.php" 
                       class="nav-link <?php echo $currentPage === 'timetable.php' ? 'active' : ''; ?>">
                        <span class="icon"><i class="fa-solid fa-clock"></i></span> Timetable
                    </a>
                </li>

            <?php elseif ($userType === 'teacher'): ?>
                <!-- ═══ TEACHER NAVIGATION ═══ -->
                <li class="nav-section-title">Main</li>
                <li>
                    <a href="<?php echo BASE_URL; ?>/teacher/dashboard.php" 
                       class="nav-link <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
                        <span class="icon"><i class="fa-solid fa-house"></i></span> Dashboard
                    </a>
                </li>
                
                <li class="nav-section-title">Teaching</li>
                <li>
                    <a href="<?php echo BASE_URL; ?>/teacher/my_classes.php" 
                       class="nav-link <?php echo $currentPage === 'my_classes.php' ? 'active' : ''; ?>">
                        <span class="icon"><i class="fa-solid fa-book-open"></i></span> My Classes
                    </a>
                </li>
                <li>
                    <a href="<?php echo BASE_URL; ?>/teacher/attendance.php" 
                       class="nav-link <?php echo $currentPage === 'attendance.php' ? 'active' : ''; ?>">
                        <span class="icon"><i class="fa-solid fa-check-to-slot"></i></span> Attendance
                    </a>
                </li>
                <li>
                    <a href="<?php echo BASE_URL; ?>/teacher/examinations.php" 
                       class="nav-link <?php echo $currentPage === 'examinations.php' ? 'active' : ''; ?>">
                        <span class="icon"><i class="fa-solid fa-file-signature"></i></span> Class Tests
                    </a>
                </li>
                <li>
                    <a href="<?php echo BASE_URL; ?>/teacher/results.php" 
                       class="nav-link <?php echo $currentPage === 'results.php' ? 'active' : ''; ?>">
                        <span class="icon"><i class="fa-solid fa-file-pen"></i></span> Results
                    </a>
                </li>
                
                <li class="nav-section-title">Info</li>
                <li>
                    <a href="<?php echo BASE_URL; ?>/teacher/notices.php" 
                       class="nav-link <?php echo $currentPage === 'notices.php' ? 'active' : ''; ?>">
                        <span class="icon"><i class="fa-solid fa-bullhorn"></i></span> Notices
                    </a>
                </li>
                <li>
                    <a href="<?php echo BASE_URL; ?>/teacher/timetable.php" 
                       class="nav-link <?php echo $currentPage === 'timetable.php' ? 'active' : ''; ?>">
                        <span class="icon"><i class="fa-solid fa-clock"></i></span> Timetable
                    </a>
                </li>

            <?php elseif ($userType === 'student'): ?>
                <!-- ═══ STUDENT NAVIGATION ═══ -->
                <li class="nav-section-title">Main</li>
                <li>
                    <a href="<?php echo BASE_URL; ?>/student/dashboard.php" 
                       class="nav-link <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
                        <span class="icon"><i class="fa-solid fa-house"></i></span> Dashboard
                    </a>
                </li>
                
                <li class="nav-section-title">My Academic</li>
                <li>
                    <a href="<?php echo BASE_URL; ?>/student/profile.php" 
                       class="nav-link <?php echo $currentPage === 'profile.php' ? 'active' : ''; ?>">
                        <span class="icon"><i class="fa-solid fa-user"></i></span> My Profile
                    </a>
                </li>
                <li>
                    <a href="<?php echo BASE_URL; ?>/student/attendance.php" 
                       class="nav-link <?php echo $currentPage === 'attendance.php' ? 'active' : ''; ?>">
                        <span class="icon"><i class="fa-solid fa-check-to-slot"></i></span> My Attendance
                    </a>
                </li>
                <li>
                    <a href="<?php echo BASE_URL; ?>/student/results.php"
                       class="nav-link <?php echo $currentPage === 'results.php' ? 'active' : ''; ?>">
                        <span class="icon"><i class="fa-solid fa-file-pen"></i></span> My Results
                    </a>
                </li>
                <li>
                    <a href="<?php echo BASE_URL; ?>/student/results_improved.php"
                       class="nav-link <?php echo $currentPage === 'results_improved.php' ? 'active' : ''; ?>">
                        <span class="icon"><i class="fa-solid fa-chart-line"></i></span> Academic Results (Enhanced)
                    </a>
                </li>
                <li>
                    <a href="<?php echo BASE_URL; ?>/student/download_transcript.php"
                       class="nav-link <?php echo $currentPage === 'download_transcript.php' ? 'active' : ''; ?>">
                        <span class="icon"><i class="fa-solid fa-file-download"></i></span> Download Transcript
                    </a>
                </li>
                <li>
                    <a href="<?php echo BASE_URL; ?>/student/fees.php" 
                       class="nav-link <?php echo $currentPage === 'fees.php' ? 'active' : ''; ?>">
                        <span class="icon"><i class="fa-solid fa-money-bill-wave"></i></span> My Fees
                    </a>
                </li>
                
                <li class="nav-section-title">Info</li>
                <li>
                    <a href="<?php echo BASE_URL; ?>/student/notices.php" 
                       class="nav-link <?php echo $currentPage === 'notices.php' ? 'active' : ''; ?>">
                        <span class="icon"><i class="fa-solid fa-bullhorn"></i></span> Notices
                    </a>
                </li>
                <li>
                    <a href="<?php echo BASE_URL; ?>/student/timetable.php" 
                       class="nav-link <?php echo $currentPage === 'timetable.php' ? 'active' : ''; ?>">
                        <span class="icon"><i class="fa-solid fa-clock"></i></span> Timetable
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
</aside>

