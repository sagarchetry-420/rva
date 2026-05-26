<?php
namespace App\Modules\Exams\Repositories;

use Database;

/**
 * ResultRepository — Data access for exam results
 */
class ResultRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get students and their results for a specific schedule
     */
    public function getResultsBySchedule(int $classId, int $sessionId, int $scheduleId): array
    {
        $sql = "SELECT sa.student_id, sa.roll_number, s.first_name, s.last_name, 
                       r.result_id, r.marks_obtained, r.is_absent, r.remarks, r.grade
                FROM student_academics sa
                JOIN students s ON sa.student_id = s.student_id
                LEFT JOIN results r ON sa.student_id = r.student_id AND r.schedule_id = ?
                WHERE sa.class_id = ? AND sa.session_id = ? AND sa.admission_status = 'Active'
                ORDER BY sa.roll_number ASC";
                
        return $this->db->fetchAll($sql, [$scheduleId, $classId, $sessionId]);
    }

    public function getScheduleDetails(int $scheduleId): ?array
    {
        $sql = "SELECT es.*, s.subject_name 
                FROM exam_schedules es
                JOIN subjects s ON es.subject_id = s.subject_id
                WHERE es.schedule_id = ?";
        return $this->db->fetch($sql, [$scheduleId]);
    }

    public function upsertResult(array $data): void
    {
        $sql = "INSERT INTO results (student_id, schedule_id, session_id, marks_obtained, is_absent, grade, remarks, entered_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                marks_obtained = VALUES(marks_obtained), 
                is_absent = VALUES(is_absent),
                grade = VALUES(grade),
                remarks = VALUES(remarks), 
                entered_by = VALUES(entered_by)";
                
        $this->db->execute($sql, [
            $data['student_id'],
            $data['schedule_id'],
            $data['session_id'],
            $data['marks_obtained'],
            $data['is_absent'],
            $data['grade'] ?? null,
            $data['remarks'] ?? null,
            $data['entered_by']
        ]);
    }

    public function getTeacherExamStats(int $teacherId, int $sessionId, array $examIds, array $classIds): array
    {
        if (empty($examIds) || empty($classIds)) {
            return [];
        }
        
        $examPlaceholders = implode(',', array_fill(0, count($examIds), '?'));
        $classPlaceholders = implode(',', array_fill(0, count($classIds), '?'));
        
        $sql = "
            SELECT 
                es.exam_id,
                es.class_id,
                COUNT(DISTINCT es.schedule_id) as total_schedules,
                COUNT(DISTINCT CASE WHEN r.entered_by = ? THEN r.schedule_id ELSE NULL END) as entered_schedules
            FROM class_subjects cs
            JOIN exam_schedules es ON cs.class_id = es.class_id AND cs.subject_id = es.subject_id
            LEFT JOIN results r ON r.schedule_id = es.schedule_id AND r.session_id = ?
            WHERE cs.teacher_id = ? AND cs.session_id = ?
              AND es.exam_id IN ($examPlaceholders)
              AND es.class_id IN ($classPlaceholders)
            GROUP BY es.exam_id, es.class_id
        ";
        
        $params = array_merge([$teacherId, $sessionId, $teacherId, $sessionId], $examIds, $classIds);
        
        $results = $this->db->fetchAll($sql, $params);
        $stats = [];
        foreach ($results as $row) {
            $stats[$row['exam_id']][$row['class_id']] = [
                'total' => (int)$row['total_schedules'],
                'entered' => (int)$row['entered_schedules']
            ];
        }
        
        return $stats;
    }
}
