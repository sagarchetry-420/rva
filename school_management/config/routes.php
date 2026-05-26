<?php
/**
 * ============================================================
 * Route Definitions
 * ============================================================
 * Maps module/action pairs to Controller methods with role requirements.
 * 
 * Format: $router->method('module/action', ControllerClass, 'method', ['role' => 'xxx'])
 */

// ─── Public Routes (no role required) ───
$router->any('public/admission',      'App\Modules\Admission\Controllers\PublicAdmissionController', 'index');
$router->any('public/check-result',   'App\Modules\Exams\Controllers\PublicResultController', 'check');
$router->get('public/admission_success',     'App\Modules\Admission\Controllers\PublicAdmissionController', 'success');
$router->get('public/application_receipt',   'App\Modules\Admission\Controllers\PublicAdmissionController', 'downloadReceipt');

// ─── API Routes (no role required) ───
$router->get('api/notices',      'App\Modules\Notice\Controllers\NoticeController', 'apiNotices');
$router->get('api/quotes',       'App\Modules\CMS\Controllers\QuoteController', 'apiQuotes');
$router->get('api/gallery',      'App\Modules\CMS\Controllers\GalleryController', 'apiGallery');
$router->get('api/hall-of-fame', 'App\Modules\CMS\Controllers\HallOfFameController', 'apiHallOfFame');

// ─── Auth Routes (no role required) ───
$router->get('auth/login',            'App\Modules\Auth\Controllers\AuthController', 'showLoginForm');
$router->post('auth/login',           'App\Modules\Auth\Controllers\AuthController', 'login');
$router->any('auth/logout',           'App\Modules\Auth\Controllers\AuthController', 'logout');
$router->get('auth/forgot-password',  'App\Modules\Auth\Controllers\AuthController', 'showForgotPassword');
$router->post('auth/forgot-password', 'App\Modules\Auth\Controllers\AuthController', 'sendResetLink');
$router->get('auth/reset-password',   'App\Modules\Auth\Controllers\AuthController', 'showResetPassword');
$router->post('auth/reset-password',  'App\Modules\Auth\Controllers\AuthController', 'resetPassword');

// ─── Admin Routes ───
$router->get('admin/index',      'App\Modules\Admin\Controllers\DashboardController', 'index',  ['role' => 'admin']);
$router->get('admin/dashboard',  'App\Modules\Admin\Controllers\DashboardController', 'index',  ['role' => 'admin']);
$router->get('admin/clear-cache','App\Modules\Admin\Controllers\DashboardController', 'clearCache',  ['role' => 'admin']);

$router->get('admin/students',   'App\Modules\Student\Controllers\StudentController', 'index',  ['role' => 'admin']);
$router->post('admin/students',  'App\Modules\Student\Controllers\StudentController', 'handleAction', ['role' => 'admin']);

$router->get('admin/teachers',   'App\Modules\Teacher\Controllers\TeacherController', 'index',  ['role' => 'admin']);
$router->post('admin/teachers',  'App\Modules\Teacher\Controllers\TeacherController', 'handleAction', ['role' => 'admin']);

$router->get('admin/classes',    'App\Modules\Academic\Controllers\ClassController',   'index',  ['role' => 'admin']);
$router->post('admin/classes',   'App\Modules\Academic\Controllers\ClassController',   'handleAction', ['role' => 'admin']);

$router->get('admin/academic_sessions',  'App\Modules\Academic\Controllers\AcademicSessionController', 'index',  ['role' => 'admin']);
$router->post('admin/academic_sessions', 'App\Modules\Academic\Controllers\AcademicSessionController', 'handleAction', ['role' => 'admin']);

$router->get('admin/subjects',   'App\Modules\Academic\Controllers\SubjectController', 'index',  ['role' => 'admin']);
$router->post('admin/subjects',  'App\Modules\Academic\Controllers\SubjectController', 'handleAction', ['role' => 'admin']);

$router->get('admin/assignments',  'App\Modules\Academic\Controllers\AssignmentController', 'index',  ['role' => 'admin']);
$router->post('admin/assignments', 'App\Modules\Academic\Controllers\AssignmentController', 'handleAction', ['role' => 'admin']);

$router->get('admin/timetable',  'App\Modules\Timetable\Controllers\TimetableController', 'index', ['role' => 'admin']);
$router->post('admin/timetable', 'App\Modules\Timetable\Controllers\TimetableController', 'handleAction', ['role' => 'admin']);

$router->get('admin/examinations',  'App\Modules\Exams\Controllers\ExamController', 'index',  ['role' => 'admin']);
$router->post('admin/examinations', 'App\Modules\Exams\Controllers\ExamController', 'handleAction', ['role' => 'admin']);
$router->get('admin/schedules',     'App\Modules\Exams\Controllers\ExamController', 'schedules', ['role' => 'admin']);
$router->post('admin/schedules',    'App\Modules\Exams\Controllers\ExamController', 'handleAction', ['role' => 'admin']);

$router->get('admin/attendance',  'App\Modules\Attendance\Controllers\AttendanceController', 'index', ['role' => 'admin']);
$router->post('admin/attendance', 'App\Modules\Attendance\Controllers\AttendanceController', 'handleAction', ['role' => 'admin']);
$router->get('admin/attendance/monthly', 'App\Modules\Attendance\Controllers\AttendanceController', 'monthlyReport', ['role' => 'admin']);
$router->post('admin/attendance/monthly', 'App\Modules\Attendance\Controllers\AttendanceController', 'exportMonthlyCsv', ['role' => 'admin']);

$router->get('admin/fees',       'App\Modules\Fees\Controllers\FeeController',    'collection',  ['role' => 'admin']);
$router->post('admin/fees',      'App\Modules\Fees\Controllers\FeeController',    'handleAction', ['role' => 'admin']);
$router->get('admin/fee_collection',  'App\Modules\Fees\Controllers\FeeController', 'collection', ['role' => 'admin']);
$router->post('admin/fee_collection', 'App\Modules\Fees\Controllers\FeeController', 'handleAction', ['role' => 'admin']);
$router->get('admin/receipt',         'App\Modules\Fees\Controllers\ReceiptController', 'view', ['role' => 'admin']);

$router->get('admin/notices',    'App\Modules\Notice\Controllers\NoticeController', 'index', ['role' => 'admin']);
$router->post('admin/notices',   'App\Modules\Notice\Controllers\NoticeController', 'handleAction', ['role' => 'admin']);

// CMS Routes
$router->get('admin/hall-of-fame',  'App\Modules\CMS\Controllers\HallOfFameController', 'index', ['role' => 'admin']);
$router->post('admin/hall-of-fame', 'App\Modules\CMS\Controllers\HallOfFameController', 'handleAction', ['role' => 'admin']);
$router->get('admin/quotes',        'App\Modules\CMS\Controllers\QuoteController', 'index', ['role' => 'admin']);
$router->post('admin/quotes',       'App\Modules\CMS\Controllers\QuoteController', 'handleAction', ['role' => 'admin']);
$router->get('admin/gallery',       'App\Modules\CMS\Controllers\GalleryController', 'index', ['role' => 'admin']);
$router->post('admin/gallery',      'App\Modules\CMS\Controllers\GalleryController', 'handleAction', ['role' => 'admin']);

$router->get('admin/applications',       'App\Modules\Admission\Controllers\AdmissionController', 'applications', ['role' => 'admin']);
$router->post('admin/applications',      'App\Modules\Admission\Controllers\AdmissionController', 'handleAction', ['role' => 'admin']);
$router->get('admin/admission-settings', 'App\Modules\Admission\Controllers\AdmissionController', 'settings', ['role' => 'admin']);
$router->post('admin/admission-settings','App\Modules\Admission\Controllers\AdmissionController', 'saveSettings', ['role' => 'admin']);

$router->get('admin/promotions',  'App\Modules\Student\Controllers\PromotionController', 'index', ['role' => 'admin']);
$router->post('admin/promotions', 'App\Modules\Student\Controllers\PromotionController', 'handleAction', ['role' => 'admin']);

$router->get('admin/services',   'App\Modules\Fee\Controllers\ServiceController', 'index', ['role' => 'admin']);
$router->post('admin/services',  'App\Modules\Fee\Controllers\ServiceController', 'handleAction', ['role' => 'admin']);

// $router->get('admin/marksheet',  'App\Modules\Exam\Controllers\ExamController', 'marksheet', ['role' => 'admin']);
// $router->get('admin/export-fees','App\Modules\Fee\Controllers\FeeController', 'export', ['role' => 'admin']);
// $router->post('admin/export-fees','App\Modules\Fee\Controllers\FeeController', 'export', ['role' => 'admin']);

// ─── Teacher Routes ───
$router->get('teacher/index',        'App\Modules\Teacher\Controllers\TeacherPortalController', 'dashboard', ['role' => 'teacher']);
$router->get('teacher/dashboard',    'App\Modules\Teacher\Controllers\TeacherPortalController', 'dashboard', ['role' => 'teacher']);
$router->get('teacher/my-classes',   'App\Modules\Teacher\Controllers\TeacherPortalController', 'myClasses', ['role' => 'teacher']);
$router->get('teacher/attendance',   'App\Modules\Teacher\Controllers\TeacherPortalController', 'attendance', ['role' => 'teacher']);
$router->post('teacher/attendance',  'App\Modules\Teacher\Controllers\TeacherPortalController', 'saveAttendance', ['role' => 'teacher']);
$router->get('teacher/examinations', 'App\Modules\Teacher\Controllers\TeacherPortalController', 'examinations', ['role' => 'teacher']);
$router->post('teacher/examinations','App\Modules\Teacher\Controllers\TeacherPortalController', 'handleExamAction', ['role' => 'teacher']);
$router->get('teacher/results',      'App\Modules\Teacher\Controllers\TeacherPortalController', 'results', ['role' => 'teacher']);
$router->post('teacher/results',     'App\Modules\Teacher\Controllers\TeacherPortalController', 'saveResults', ['role' => 'teacher']);
$router->get('teacher/notices',      'App\Modules\Teacher\Controllers\TeacherPortalController', 'notices', ['role' => 'teacher']);
$router->get('teacher/timetable',    'App\Modules\Teacher\Controllers\TeacherPortalController', 'timetable', ['role' => 'teacher']);
$router->get('teacher/id_card',      'App\Modules\Teacher\Controllers\TeacherPortalController', 'idCard', ['role' => 'teacher']);

// ─── Student Routes ───
$router->get('student/index',      'App\Modules\Student\Controllers\StudentPortalController', 'dashboard', ['role' => 'student']);
$router->get('student/dashboard',  'App\Modules\Student\Controllers\StudentPortalController', 'dashboard', ['role' => 'student']);
$router->get('student/profile',    'App\Modules\Student\Controllers\StudentPortalController', 'profile', ['role' => 'student']);
$router->get('student/attendance', 'App\Modules\Student\Controllers\StudentPortalController', 'attendance', ['role' => 'student']);
$router->get('student/results',    'App\Modules\Student\Controllers\StudentPortalController', 'results', ['role' => 'student']);
$router->get('student/view_result','App\Modules\Student\Controllers\StudentPortalController', 'viewResult', ['role' => 'student']);
$router->get('student/fees',       'App\Modules\Student\Controllers\StudentPortalController', 'fees', ['role' => 'student']);
$router->get('student/notices',    'App\Modules\Student\Controllers\StudentPortalController', 'notices', ['role' => 'student']);
$router->get('student/timetable',  'App\Modules\Student\Controllers\StudentPortalController', 'timetable', ['role' => 'student']);
$router->get('student/id_card',    'App\Modules\Student\Controllers\StudentPortalController', 'idCard', ['role' => 'student']);
$router->get('student/exam_routine','App\Modules\Student\Controllers\StudentPortalController', 'exam_routine', ['role' => 'student']);
$router->get('student/transcript', 'App\Modules\Student\Controllers\StudentPortalController', 'transcript', ['role' => 'student']);
$router->get('student/download_transcript', 'App\Modules\Student\Controllers\StudentPortalController', 'downloadTranscript', ['role' => 'student']);
$router->get('student/routine',    'App\Modules\Student\Controllers\StudentPortalController', 'downloadRoutine', ['role' => 'student']);
$router->get('student/export-fees','App\Modules\Student\Controllers\StudentPortalController', 'exportFees', ['role' => 'student']);
$router->get('student/receipt',    'App\Modules\Fees\Controllers\ReceiptController', 'view', ['role' => 'student']);

