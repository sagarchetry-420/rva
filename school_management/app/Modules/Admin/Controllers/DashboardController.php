<?php
namespace App\Modules\Admin\Controllers;

/**
 * DashboardController — Admin Dashboard
 */
class DashboardController extends \Controller
{
    public function index(): void
    {
        $db = $this->db;

        // Get active session
        $academicRepo = new \App\Modules\Academic\Repositories\ClassSubjectRepository();
        $activeSession = $academicRepo->getActiveSession();
        $sessionId = $activeSession ? $activeSession['session_id'] : 0;

        // Gather dashboard statistics
        $stats = [
            'total_students'  => $db->count('student_academics', 'session_id = ? AND admission_status = ?', [$sessionId, 'Active']),
            'total_teachers'  => $db->count('teachers'),
            'total_classes'   => $db->count('classes'),
            'total_subjects'  => $db->count('subjects'),
            'pending_fees'    => $db->fetchColumn("SELECT COALESCE(SUM(amount), 0) FROM fees WHERE payment_status = 'Pending' AND session_id = ?", [$sessionId]) ?: 0,
            'paid_fees'       => $db->fetchColumn("SELECT COALESCE(SUM(amount), 0) FROM fees WHERE payment_status = 'Paid' AND session_id = ?", [$sessionId]) ?: 0,
            'total_notices'   => $db->count('notices', 'is_active = 1'),
        ];

        // Recent students
        $recentStudents = $db->fetchAll(
            "SELECT s.*, c.class_name, c.section FROM students s
             LEFT JOIN classes c ON s.current_class_id = c.class_id
             ORDER BY s.student_id DESC LIMIT 5"
        );

        // Recent notices
        $recentNotices = $db->fetchAll(
            "SELECT * FROM notices ORDER BY created_at DESC LIMIT 5"
        );

        // --- NEW CHART DATA QUERIES ---
        
        // 1. Student Distribution
        $studentDistribution = $db->fetchAll(
            "SELECT c.class_name, c.section, COUNT(sa.student_id) as student_count 
             FROM classes c 
             LEFT JOIN student_academics sa ON c.class_id = sa.class_id AND sa.session_id = ? AND sa.admission_status = 'Active' 
             GROUP BY c.class_id 
             ORDER BY c.class_id", [$sessionId]
        );

        // 2. Daily Attendance Trend (Last 7 Days)
        $rawAttendanceTrend = $db->fetchAll(
            "SELECT attendance_date, 
                    SUM(CASE WHEN status='Present' THEN 1 ELSE 0 END) as present_count,
                    COUNT(student_id) as total_count
             FROM attendance 
             WHERE session_id = ? 
               AND attendance_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) 
               AND attendance_date <= CURDATE()
             GROUP BY attendance_date 
             ORDER BY attendance_date ASC", [$sessionId]
        );

        // Fill in missing dates to ensure exactly 7 days are shown
        $attendanceTrend = [];
        for ($i = 6; $i >= 0; $i--) {
            $dateStr = date('Y-m-d', strtotime("-$i days"));
            $found = false;
            foreach ($rawAttendanceTrend as $row) {
                if ($row['attendance_date'] === $dateStr) {
                    $attendanceTrend[] = $row;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $attendanceTrend[] = [
                    'attendance_date' => $dateStr,
                    'present_count'   => 0,
                    'total_count'     => 0
                ];
            }
        }


        // 4. Gender Demographics
        $genderDemographics = $db->fetchAll(
            "SELECT s.gender, COUNT(s.student_id) as count 
             FROM students s 
             JOIN student_academics sa ON s.student_id = sa.student_id 
             WHERE sa.session_id = ? AND sa.admission_status = 'Active' 
             GROUP BY s.gender", [$sessionId]
        );

        // 5. Examination Performance (Average Percentage per Class)
        $examPerformance = $db->fetchAll(
            "SELECT c.class_name, c.section, AVG((r.marks_obtained / es.full_marks) * 100) as avg_percentage 
             FROM results r 
             JOIN exam_schedules es ON r.schedule_id = es.schedule_id 
             JOIN classes c ON es.class_id = c.class_id 
             WHERE r.session_id = ? AND r.is_absent = 0 
             GROUP BY c.class_id 
             ORDER BY c.class_id", [$sessionId]
        );

        $this->render('Modules/Admin/Views/dashboard', [
            'pageTitle'           => 'Admin Dashboard',
            'stats'               => $stats,
            'recentStudents'      => $recentStudents,
            'recentNotices'       => $recentNotices,
            'studentDistribution' => $studentDistribution,
            'attendanceTrend'     => $attendanceTrend,
            'genderDemographics'  => $genderDemographics,
            'examPerformance'     => $examPerformance,
        ], 'admin');
    }

    public function clearCache(): void
    {
        $cleared = [];
        if (function_exists('opcache_reset')) {
            opcache_reset();
            $cleared[] = 'OPcache';
        }
        if (function_exists('apcu_clear_cache')) {
            apcu_clear_cache();
            $cleared[] = 'APCu';
        }
        
        if (empty($cleared)) {
            $this->flash('info', 'No active cache found (OPcache/APCu).');
        } else {
            $this->flash('success', implode(' and ', $cleared) . ' cleared successfully.');
        }
        $this->back();
    }
}
