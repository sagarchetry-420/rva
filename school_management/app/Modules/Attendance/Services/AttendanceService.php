<?php
namespace App\Modules\Attendance\Services;

use Database;
use App\Modules\Attendance\Repositories\AttendanceRepository;
use App\Modules\Academic\Repositories\ClassSubjectRepository; // Used to get active session

/**
 * AttendanceService — Business logic for Attendance tracking
 */
class AttendanceService
{
    private AttendanceRepository $repo;
    private ClassSubjectRepository $academicRepo;

    public function __construct()
    {
        $this->repo = new AttendanceRepository();
        $this->academicRepo = new ClassSubjectRepository();
    }

    /**
     * Get attendance list for marking
     */
    public function getAttendanceList(int $classId, string $date): array
    {
        $session = $this->academicRepo->getActiveSession();
        if (!$session) {
            return [];
        }
        return $this->repo->getAttendanceByDateAndClass($classId, $date, $session['session_id']);
    }

    /**
     * Check if attendance has already been marked
     */
    public function isAttendanceMarked(int $classId, string $date): bool
    {
        $session = $this->academicRepo->getActiveSession();
        if (!$session) {
            return false;
        }
        return $this->repo->isAttendanceMarked($classId, $date, $session['session_id']);
    }

    /**
     * Save bulk attendance data for a class
     */
    public function saveBulkAttendance(int $classId, string $date, array $attendanceData, int $markedByUserId): array
    {
        $session = $this->academicRepo->getActiveSession();
        if (!$session) {
            return ['success' => false, 'message' => 'No active academic session found.'];
        }

        try {
            Database::getInstance()->transaction(function() use ($classId, $date, $attendanceData, $markedByUserId, $session) {
                foreach ($attendanceData as $studentId => $data) {
                    // Only process valid statuses
                    if (in_array($data['status'], ['Present', 'Absent'])) {
                        $this->repo->upsertAttendance([
                            'student_id'      => $studentId,
                            'class_id'        => $classId,
                            'session_id'      => $session['session_id'],
                            'attendance_date' => $date,
                            'status'          => $data['status'],
                            'remarks'         => $data['remarks'] ?? null,
                            'marked_by'       => $markedByUserId
                        ]);
                    }
                }
            });
            return ['success' => true, 'message' => 'Attendance saved successfully.'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Failed to save attendance: ' . $e->getMessage()];
        }
    }
}
