<?php
namespace App\Modules\Attendance\Repositories;

use Database;

/**
 * AttendanceRepository — Data access for attendance table
 */
class AttendanceRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get students and their attendance for a specific class, date, and session
     */
    public function getAttendanceByDateAndClass(int $classId, string $date, int $sessionId): array
    {
        $sql = "SELECT sa.student_id, sa.roll_number, s.first_name, s.last_name, 
                       a.attendance_id, a.status, a.remarks, a.leave_document
                FROM student_academics sa
                JOIN students s ON sa.student_id = s.student_id
                LEFT JOIN attendance a ON sa.student_id = a.student_id 
                                       AND a.class_id = sa.class_id 
                                       AND a.session_id = sa.session_id 
                                       AND a.attendance_date = ?
                WHERE sa.class_id = ? AND sa.session_id = ? AND sa.admission_status = 'Active'
                ORDER BY sa.roll_number ASC";
                
        return $this->db->fetchAll($sql, [$date, $classId, $sessionId]);
    }

    /**
     * Check if attendance has already been marked for a specific class, date, and session
     * (We check for 'Present' or 'Absent' because 'Leave' records can be pre-applied by admins and shouldn't lock the class)
     */
    public function isAttendanceMarked(int $classId, string $date, int $sessionId): bool
    {
        $sql = "SELECT 1 FROM attendance WHERE class_id = ? AND attendance_date = ? AND session_id = ? AND status IN ('Present', 'Absent') LIMIT 1";
        return $this->db->fetch($sql, [$classId, $date, $sessionId]) !== null;
    }

    /**
     * Insert or update attendance record
     */
    public function upsertAttendance(array $data): void
    {
        $sql = "INSERT INTO attendance (student_id, class_id, session_id, attendance_date, status, remarks, leave_document, marked_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                status = VALUES(status), remarks = VALUES(remarks), leave_document = COALESCE(VALUES(leave_document), leave_document), marked_by = VALUES(marked_by)";
                
        $this->db->execute($sql, [
            $data['student_id'],
            $data['class_id'],
            $data['session_id'],
            $data['attendance_date'],
            $data['status'],
            $data['remarks'] ?? null,
            $data['leave_document'] ?? null,
            $data['marked_by']
        ]);
    }

    /**
     * Calculate attendance summary for a student in a session
     */
    public function getStudentSummary(int $studentId, int $sessionId): array
    {
        $sql = "SELECT status, COUNT(*) as total 
                FROM attendance 
                WHERE student_id = ? AND session_id = ? 
                GROUP BY status";
        $results = $this->db->fetchAll($sql, [$studentId, $sessionId]);
        $summary = ['Present' => 0, 'Absent' => 0, 'Leave' => 0, 'Half Leave' => 0];
        foreach ($results as $row) {
            $summary[$row['status']] = (int)$row['total'];
        }
        return $summary;
    }

    /**
     * Get all attendance records for a class in a given month
     */
    public function getMonthlyClassAttendance(int $classId, string $yearMonth, int $sessionId): array
    {
        $sql = "SELECT sa.student_id, sa.roll_number, s.first_name, s.last_name, 
                       a.attendance_date, a.status
                FROM student_academics sa
                JOIN students s ON sa.student_id = s.student_id
                LEFT JOIN attendance a ON sa.student_id = a.student_id 
                                       AND a.class_id = sa.class_id 
                                       AND a.session_id = sa.session_id 
                                       AND a.attendance_date LIKE ?
                WHERE sa.class_id = ? AND sa.session_id = ? AND sa.admission_status = 'Active'
                ORDER BY sa.roll_number ASC, a.attendance_date ASC";
                
        $results = $this->db->fetchAll($sql, [$yearMonth . '%', $classId, $sessionId]);
        
        $students = [];
        foreach ($results as $row) {
            $studentId = $row['student_id'];
            if (!isset($students[$studentId])) {
                $students[$studentId] = [
                    'roll_number' => $row['roll_number'],
                    'name' => $row['first_name'] . ' ' . $row['last_name'],
                    'attendance' => []
                ];
            }
            if ($row['attendance_date']) {
                $day = (int)date('d', strtotime($row['attendance_date']));
                $students[$studentId]['attendance'][$day] = $row['status'];
            }
        }
        return $students;
    }
}
