<?php
namespace App\Modules\Academic\Repositories;

use Database;

/**
 * ClassSubjectRepository — Handles many-to-many relationship mappings
 */
class ClassSubjectRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get all assigned subjects and teachers for a specific class and session
     */
    public function findByClass(int $classId, int $sessionId): array
    {
        $sql = "SELECT cs.*, s.subject_name, s.subject_code, t.first_name, t.last_name, t.status 
                FROM class_subjects cs
                JOIN subjects s ON cs.subject_id = s.subject_id
                LEFT JOIN teachers t ON cs.teacher_id = t.teacher_id
                WHERE cs.class_id = ? AND cs.session_id = ?
                ORDER BY s.subject_name";
        return $this->db->fetchAll($sql, [$classId, $sessionId]);
    }

    /**
     * Get active academic session
     */
    public function getActiveSession(): ?array
    {
        return $this->db->fetch("SELECT * FROM academic_sessions WHERE is_current = 1 LIMIT 1");
    }

    public function createAssignment(array $data): int
    {
        return $this->db->insert('class_subjects', $data);
    }

    public function deleteAssignment(int $id): bool
    {
        return $this->db->delete('class_subjects', 'id = ?', [$id]) > 0;
    }

    /**
     * Check if the subject is already assigned to this class in this session
     */
    public function assignmentExists(int $classId, int $subjectId, int $sessionId): bool
    {
        $sql = "SELECT 1 FROM class_subjects 
                WHERE class_id = ? AND subject_id = ? AND session_id = ?";
        return $this->db->fetch($sql, [$classId, $subjectId, $sessionId]) !== null;
    }
    public function update(int $id, array $data): bool
    {
        return $this->db->update('class_subjects', $data, 'id = ?', [$id]) > 0;
    }
}
