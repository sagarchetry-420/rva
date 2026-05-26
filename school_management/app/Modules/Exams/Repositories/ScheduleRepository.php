<?php
namespace App\Modules\Exams\Repositories;

use Database;

/**
 * ScheduleRepository — Data access for exam_schedules
 */
class ScheduleRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findByExamAndClass(int $examId, int $classId): array
    {
        $sql = "SELECT es.*, s.subject_name, s.subject_code 
                FROM exam_schedules es
                JOIN subjects s ON es.subject_id = s.subject_id
                WHERE es.exam_id = ? AND es.class_id = ?
                ORDER BY es.exam_date ASC, es.start_time ASC";
        return $this->db->fetchAll($sql, [$examId, $classId]);
    }

    public function create(array $data): int
    {
        return $this->db->insert('exam_schedules', $data);
    }

    public function delete(int $scheduleId): bool
    {
        return $this->db->delete('exam_schedules', 'schedule_id = ?', [$scheduleId]) > 0;
    }

    public function exists(int $examId, int $classId, int $subjectId): bool
    {
        $sql = "SELECT 1 FROM exam_schedules WHERE exam_id = ? AND class_id = ? AND subject_id = ?";
        return $this->db->fetch($sql, [$examId, $classId, $subjectId]) !== null;
    }
}
