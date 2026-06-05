<?php
namespace App\Modules\Exams\Repositories;

use Database;

/**
 * ExamRepository — Data access for examinations and exam_classes
 */
class ExamRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findAllBySession(int $sessionId): array
    {
        return $this->db->fetchAll("
            SELECT e.*, 
                   IF(e.created_by_role = 'teacher', CONCAT('Teacher: ', t.first_name, ' ', t.last_name), 'Admin') as creator_name
            FROM examinations e
            LEFT JOIN teachers t ON e.created_by = t.user_id AND e.created_by_role = 'teacher'
            WHERE e.session_id = ? 
            ORDER BY e.start_date DESC
        ", [$sessionId]);
    }

    public function findById(int $examId): ?array
    {
        return $this->db->fetch("SELECT * FROM examinations WHERE exam_id = ?", [$examId]);
    }

    public function findDuplicateExam(int $sessionId, string $examName, string $examType, string $startDate, string $endDate): ?array
    {
        if (in_array($examType, ['Mid-Term', 'Final'])) {
            // Only one Mid-Term or Final allowed per session
            return $this->db->fetch("SELECT * FROM examinations WHERE session_id = ? AND exam_type = ?", [$sessionId, $examType]);
        } else {
            // For Unit/Class tests, prevent overlapping dates for the same type AND same name
            $sql = "SELECT * FROM examinations 
                    WHERE session_id = ? 
                    AND exam_type = ? 
                    AND exam_name = ?
                    AND (start_date <= ? AND end_date >= ?)";
            return $this->db->fetch($sql, [$sessionId, $examType, $examName, $endDate, $startDate]);
        }
    }

    public function create(array $data): int
    {
        return $this->db->insert('examinations', $data);
    }

    public function update(int $examId, array $data): bool
    {
        return $this->db->update('examinations', $data, 'exam_id = ?', [$examId]) > 0;
    }

    public function delete(int $examId): bool
    {
        return $this->db->delete('examinations', 'exam_id = ?', [$examId]) > 0;
    }

    // --- Exam Classes Mapping ---

    public function getExamClasses(int $examId): array
    {
        $sql = "SELECT ec.*, c.class_name, c.section,
                (SELECT COUNT(*) FROM results r 
                 JOIN exam_schedules es ON r.schedule_id = es.schedule_id 
                 WHERE es.exam_id = ec.exam_id AND es.class_id = ec.class_id) > 0 AS marks_entered
                FROM exam_classes ec
                JOIN classes c ON ec.class_id = c.class_id
                WHERE ec.exam_id = ?";
        return $this->db->fetchAll($sql, [$examId]);
    }

    public function assignClass(int $examId, int $classId): void
    {
        $this->db->insert('exam_classes', ['exam_id' => $examId, 'class_id' => $classId]);
    }

    public function removeClass(int $examId, int $classId): void
    {
        $this->db->delete('exam_classes', 'exam_id = ? AND class_id = ?', [$examId, $classId]);
    }

    public function syncExamClasses(int $examId, array $newClassIds): void
    {
        $currentClasses = $this->db->fetchAll("SELECT class_id FROM exam_classes WHERE exam_id = ?", [$examId]);
        $currentClassIds = array_column($currentClasses, 'class_id');

        $toAdd = array_diff($newClassIds, $currentClassIds);
        foreach ($toAdd as $classId) {
            $this->assignClass($examId, (int)$classId);
        }

        $toRemove = array_diff($currentClassIds, $newClassIds);
        foreach ($toRemove as $classId) {
            $hasMarks = $this->db->fetch("SELECT COUNT(*) as cnt FROM results r 
                 JOIN exam_schedules es ON r.schedule_id = es.schedule_id 
                 WHERE es.exam_id = ? AND es.class_id = ?", [$examId, $classId]);
                 
            if ($hasMarks['cnt'] == 0) {
                $this->db->delete('exam_schedules', 'exam_id = ? AND class_id = ?', [$examId, $classId]);
                $this->removeClass($examId, (int)$classId);
            }
        }
    }

    public function getExamMarksProgressStats(int $examId, int $sessionId): array
    {
        $sql = "
            SELECT 
                es.schedule_id, 
                es.class_id, 
                es.subject_id, 
                c.class_name, 
                c.section, 
                s.subject_name,
                t.first_name, 
                t.last_name,
                (
                    SELECT COUNT(*) 
                    FROM student_academics sa_cnt 
                    WHERE sa_cnt.class_id = es.class_id AND sa_cnt.session_id = ? AND sa_cnt.admission_status = 'Active'
                ) as student_count,
                (
                    SELECT COUNT(*) 
                    FROM results r_cnt
                    JOIN student_academics sa_cnt2 ON r_cnt.student_id = sa_cnt2.student_id AND sa_cnt2.session_id = ?
                    WHERE r_cnt.schedule_id = es.schedule_id AND r_cnt.session_id = ? AND sa_cnt2.class_id = es.class_id AND sa_cnt2.admission_status = 'Active'
                ) as result_count
            FROM exam_schedules es
            JOIN classes c ON es.class_id = c.class_id
            JOIN subjects s ON es.subject_id = s.subject_id
            LEFT JOIN class_subjects cs ON cs.class_id = es.class_id AND cs.subject_id = es.subject_id AND cs.session_id = ?
            LEFT JOIN teachers t ON cs.teacher_id = t.teacher_id
            WHERE es.exam_id = ?
        ";
        
        $schedules = $this->db->fetchAll($sql, [$sessionId, $sessionId, $sessionId, $sessionId, $examId]);
        
        $missingReports = [];
        $totalSchedules = count($schedules);
        $completeSchedules = 0;
        
        foreach ($schedules as $row) {
            $studentCount = (int)$row['student_count'];
            $resultCount = (int)$row['result_count'];
            
            if ($studentCount == 0) {
                $completeSchedules++;
                continue;
            }
            
            if ($resultCount > 0) {
                $completeSchedules++;
            } else {
                $teacherName = ($row['first_name'] || $row['last_name']) ? ($row['first_name'] . ' ' . $row['last_name']) : 'Unassigned';
                $missingReports[] = [
                    'class_name' => $row['class_name'] . ' ' . $row['section'],
                    'subject_name' => $row['subject_name'],
                    'teacher_name' => trim($teacherName),
                    'entered' => $resultCount,
                    'total' => $studentCount
                ];
            }
        }
        
        return [
            'is_complete' => ($totalSchedules > 0 && $completeSchedules === $totalSchedules),
            'missing_reports' => $missingReports
        ];
    }
}
