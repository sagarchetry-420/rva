<?php
namespace App\Modules\Academic\Repositories;

use Database;

/**
 * SubjectRepository — Database operations for the subjects table
 */
class SubjectRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findById(int $subjectId): ?array
    {
        return $this->db->fetch("SELECT * FROM subjects WHERE subject_id = ?", [$subjectId]);
    }

    public function findAll(): array
    {
        return $this->db->fetchAll("SELECT * FROM subjects ORDER BY subject_name");
    }

    public function create(array $data): int
    {
        return $this->db->insert('subjects', $data);
    }

    public function update(int $subjectId, array $data): bool
    {
        return $this->db->update('subjects', $data, 'subject_id = ?', [$subjectId]) > 0;
    }

    public function delete(int $subjectId): bool
    {
        return $this->db->delete('subjects', 'subject_id = ?', [$subjectId]) > 0;
    }

    /**
     * Check if a subject code already exists
     */
    public function codeExists(string $subjectCode, ?int $exceptSubjectId = null): bool
    {
        $sql = "SELECT 1 FROM subjects WHERE subject_code = ?";
        $params = [$subjectCode];
        
        if ($exceptSubjectId) {
            $sql .= " AND subject_id != ?";
            $params[] = $exceptSubjectId;
        }
        
        return $this->db->fetch($sql, $params) !== null;
    }

    /**
     * Check if a subject name already exists (case-insensitive)
     */
    public function nameExists(string $subjectName, ?int $exceptSubjectId = null): bool
    {
        $sql = "SELECT 1 FROM subjects WHERE LOWER(subject_name) = LOWER(?)";
        $params = [$subjectName];
        
        if ($exceptSubjectId) {
            $sql .= " AND subject_id != ?";
            $params[] = $exceptSubjectId;
        }
        
        return $this->db->fetch($sql, $params) !== null;
    }
}
