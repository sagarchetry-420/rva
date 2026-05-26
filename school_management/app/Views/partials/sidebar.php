<?php
/**
 * Sidebar Partial — Role-Based Navigation
 * Adapted for new modular routing system using moduleUrl()
 */
$userType = getUserType();
?>
<aside class="sidebar" id="sidebar">
    <nav class="sidebar-nav">
        <ul>
            <?php if ($userType === 'admin'): ?>
                <!-- ═══ ADMIN NAVIGATION ═══ -->
                <li class="nav-section-title">Main</li>
                <li><a href="<?php echo moduleUrl('admin', 'dashboard'); ?>" class="nav-link <?php echo isActivePage('admin', 'dashboard') ? 'active' : ''; ?>"><span class="icon"><i class="fa-solid fa-house"></i></span> Dashboard</a></li>

                <li class="nav-section-title">Management</li>
                <li><a href="<?php echo moduleUrl('admin', 'students'); ?>" class="nav-link <?php echo isActivePage('admin', 'students') ? 'active' : ''; ?>"><span class="icon"><i class="fa-solid fa-user-graduate"></i></span> Students</a></li>
                <li><a href="<?php echo moduleUrl('admin', 'teachers'); ?>" class="nav-link <?php echo isActivePage('admin', 'teachers') ? 'active' : ''; ?>"><span class="icon"><i class="fa-solid fa-chalkboard-teacher"></i></span> Teachers</a></li>
                <li><a href="<?php echo moduleUrl('admin', 'classes'); ?>" class="nav-link <?php echo isActivePage('admin', 'classes') ? 'active' : ''; ?>"><span class="icon"><i class="fa-solid fa-book-open"></i></span> Classes</a></li>
                <li><a href="<?php echo moduleUrl('admin', 'subjects'); ?>" class="nav-link <?php echo isActivePage('admin', 'subjects') ? 'active' : ''; ?>"><span class="icon"><i class="fa-solid fa-book"></i></span> Subjects</a></li>
                <li><a href="<?php echo moduleUrl('admin', 'assignments'); ?>" class="nav-link <?php echo isActivePage('admin', 'assignments') ? 'active' : ''; ?>"><span class="icon"><i class="fa-solid fa-project-diagram"></i></span> Subject Assignments</a></li>

                <li class="nav-section-title">Academic</li>
                <li><a href="<?php echo moduleUrl('admin', 'academic_sessions'); ?>" class="nav-link <?php echo isActivePage('admin', 'academic_sessions') ? 'active' : ''; ?>"><span class="icon"><i class="fa-solid fa-calendar-check"></i></span> Academic Sessions</a></li>
                <li><a href="<?php echo moduleUrl('admin', 'attendance'); ?>" class="nav-link <?php echo isActivePage('admin', 'attendance') ? 'active' : ''; ?>"><span class="icon"><i class="fa-solid fa-check-to-slot"></i></span> Attendance</a></li>
                <li><a href="<?php echo moduleUrl('admin', 'examinations'); ?>" class="nav-link <?php echo isActivePage('admin', 'examinations') ? 'active' : ''; ?>"><span class="icon"><i class="fa-solid fa-file-pen"></i></span> Examinations</a></li>
                <li><a href="<?php echo moduleUrl('admin', 'promotions'); ?>" class="nav-link <?php echo isActivePage('admin', 'promotions') ? 'active' : ''; ?>"><span class="icon"><i class="fa-solid fa-arrow-up"></i></span> Student Promotions</a></li>

                <li class="nav-section-title">Admissions</li>
                <li><a href="<?php echo moduleUrl('admin', 'applications'); ?>" class="nav-link <?php echo isActivePage('admin', 'applications') ? 'active' : ''; ?>"><span class="icon"><i class="fa-solid fa-pen-to-square"></i></span> Student Applications</a></li>
                <li><a href="<?php echo moduleUrl('admin', 'admission-settings'); ?>" class="nav-link <?php echo isActivePage('admin', 'admission-settings') ? 'active' : ''; ?>"><span class="icon"><i class="fa-solid fa-sliders"></i></span> Admission Settings</a></li>

                <li class="nav-section-title">Finance & Info</li>
                <li><a href="<?php echo moduleUrl('admin', 'fee_collection'); ?>" class="nav-link <?php echo isActivePage('admin', 'fee_collection') ? 'active' : ''; ?>"><span class="icon"><i class="fa-solid fa-file-invoice-dollar"></i></span> Fee Collection</a></li>
                <li><a href="<?php echo moduleUrl('admin', 'services'); ?>" class="nav-link <?php echo isActivePage('admin', 'services') ? 'active' : ''; ?>"><span class="icon"><i class="fa-solid fa-hand-holding-dollar"></i></span> Student Services</a></li>
                <li><a href="<?php echo moduleUrl('admin', 'notices'); ?>" class="nav-link <?php echo isActivePage('admin', 'notices') ? 'active' : ''; ?>"><span class="icon"><i class="fa-solid fa-bullhorn"></i></span> Notices</a></li>
                <li><a href="<?php echo moduleUrl('admin', 'timetable'); ?>" class="nav-link <?php echo isActivePage('admin', 'timetable') ? 'active' : ''; ?>"><span class="icon"><i class="fa-solid fa-clock"></i></span> Timetable</a></li>

                <li class="nav-section-title">Frontend CMS</li>
                <li><a href="<?php echo moduleUrl('admin', 'hall-of-fame'); ?>" class="nav-link <?php echo isActivePage('admin', 'hall-of-fame') ? 'active' : ''; ?>"><span class="icon"><i class="fa-solid fa-trophy"></i></span> Hall of Fame</a></li>
                <li><a href="<?php echo moduleUrl('admin', 'quotes'); ?>" class="nav-link <?php echo isActivePage('admin', 'quotes') ? 'active' : ''; ?>"><span class="icon"><i class="fa-solid fa-quote-left"></i></span> Quotes</a></li>
                <li><a href="<?php echo moduleUrl('admin', 'gallery'); ?>" class="nav-link <?php echo isActivePage('admin', 'gallery') ? 'active' : ''; ?>"><span class="icon"><i class="fa-solid fa-images"></i></span> Gallery</a></li>

            <?php elseif ($userType === 'teacher'): ?>
                <!-- ═══ TEACHER NAVIGATION ═══ -->
                <li class="nav-section-title">Main</li>
                <li><a href="<?php echo moduleUrl('teacher', 'dashboard'); ?>" class="nav-link <?php echo isActivePage('teacher', 'dashboard') ? 'active' : ''; ?>"><span class="icon"><i class="fa-solid fa-house"></i></span> Dashboard</a></li>

                <li class="nav-section-title">Teaching</li>
                <li><a href="<?php echo moduleUrl('teacher', 'my-classes'); ?>" class="nav-link <?php echo isActivePage('teacher', 'my-classes') ? 'active' : ''; ?>"><span class="icon"><i class="fa-solid fa-book-open"></i></span> My Classes</a></li>
                <li><a href="<?php echo moduleUrl('teacher', 'attendance'); ?>" class="nav-link <?php echo isActivePage('teacher', 'attendance') ? 'active' : ''; ?>"><span class="icon"><i class="fa-solid fa-check-to-slot"></i></span> Attendance</a></li>
                <li><a href="<?php echo moduleUrl('teacher', 'examinations'); ?>" class="nav-link <?php echo isActivePage('teacher', 'examinations') ? 'active' : ''; ?>"><span class="icon"><i class="fa-solid fa-file-signature"></i></span> Class Tests</a></li>
                <li><a href="<?php echo moduleUrl('teacher', 'results'); ?>" class="nav-link <?php echo isActivePage('teacher', 'results') ? 'active' : ''; ?>"><span class="icon"><i class="fa-solid fa-file-pen"></i></span> Results</a></li>

                <li class="nav-section-title">Info</li>
                <li><a href="<?php echo moduleUrl('teacher', 'notices'); ?>" class="nav-link <?php echo isActivePage('teacher', 'notices') ? 'active' : ''; ?>"><span class="icon"><i class="fa-solid fa-bullhorn"></i></span> Notices</a></li>
                <li><a href="<?php echo moduleUrl('teacher', 'timetable'); ?>" class="nav-link <?php echo isActivePage('teacher', 'timetable') ? 'active' : ''; ?>"><span class="icon"><i class="fa-solid fa-clock"></i></span> Timetable</a></li>

            <?php elseif ($userType === 'student'): ?>
                <!-- ═══ STUDENT NAVIGATION ═══ -->
                <li class="nav-section-title">Main</li>
                <li><a href="<?php echo moduleUrl('student', 'dashboard'); ?>" class="nav-link <?php echo isActivePage('student', 'dashboard') ? 'active' : ''; ?>"><span class="icon"><i class="fa-solid fa-house"></i></span> Dashboard</a></li>

                <li class="nav-section-title">My Academic</li>
                <li><a href="<?php echo moduleUrl('student', 'profile'); ?>" class="nav-link <?php echo isActivePage('student', 'profile') ? 'active' : ''; ?>"><span class="icon"><i class="fa-solid fa-user"></i></span> My Profile</a></li>
                <li><a href="<?php echo moduleUrl('student', 'attendance'); ?>" class="nav-link <?php echo isActivePage('student', 'attendance') ? 'active' : ''; ?>"><span class="icon"><i class="fa-solid fa-check-to-slot"></i></span> My Attendance</a></li>
                <li><a href="<?php echo moduleUrl('student', 'results'); ?>" class="nav-link <?php echo isActivePage('student', 'results') ? 'active' : ''; ?>"><span class="icon"><i class="fa-solid fa-file-pen"></i></span> My Results</a></li>
                <li><a href="<?php echo moduleUrl('student', 'exam_routine'); ?>" class="nav-link <?php echo isActivePage('student', 'exam_routine') ? 'active' : ''; ?>"><span class="icon"><i class="fa-solid fa-calendar-check"></i></span> Exam Routine</a></li>
                <li><a href="<?php echo moduleUrl('student', 'transcript'); ?>" class="nav-link <?php echo isActivePage('student', 'transcript') ? 'active' : ''; ?>"><span class="icon"><i class="fa-solid fa-file-download"></i></span> Download Transcript</a></li>

                <li class="nav-section-title">Info</li>
                <li><a href="<?php echo moduleUrl('student', 'fees'); ?>" class="nav-link <?php echo isActivePage('student', 'fees') ? 'active' : ''; ?>"><span class="icon"><i class="fa-solid fa-file-invoice-dollar"></i></span> Fee Invoices</a></li>
                <li><a href="<?php echo moduleUrl('student', 'notices'); ?>" class="nav-link <?php echo isActivePage('student', 'notices') ? 'active' : ''; ?>"><span class="icon"><i class="fa-solid fa-bullhorn"></i></span> Notices</a></li>
                <li><a href="<?php echo moduleUrl('student', 'timetable'); ?>" class="nav-link <?php echo isActivePage('student', 'timetable') ? 'active' : ''; ?>"><span class="icon"><i class="fa-solid fa-clock"></i></span> Timetable</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</aside>
