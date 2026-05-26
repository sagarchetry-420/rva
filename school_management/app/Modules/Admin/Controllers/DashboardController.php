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

        $this->render('Modules/Admin/Views/dashboard', [
            'pageTitle'      => 'Admin Dashboard',
            'stats'          => $stats,
            'recentStudents' => $recentStudents,
            'recentNotices'  => $recentNotices,
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
