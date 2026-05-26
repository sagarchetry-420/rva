<?php
namespace App\Modules\Teacher\Repositories;

use Database;

/**
 * TeacherRepository — Handles database operations for the teachers table
 */
class TeacherRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Find a teacher by ID
     */
    public function findById(int $teacherId): ?array
    {
        return $this->db->fetch("SELECT * FROM teachers WHERE teacher_id = ?", [$teacherId]);
    }

    /**
     * Get all teachers paginated
     */
    public function findAll(?string $status = null, int $page = 1, int $perPage = 20): array
    {
        $sql = "SELECT t.*, u.username 
                FROM teachers t
                JOIN users u ON t.user_id = u.user_id";
        $params = [];
        
        if ($status !== null) {
            $sql .= " WHERE t.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY t.first_name, t.last_name";
        return $this->db->paginate($sql, $params, $page, $perPage);
    }

    /**
     * Insert a new teacher
     */
    public function create(array $data): int
    {
        return $this->db->insert('teachers', $data);
    }

    /**
     * Update an existing teacher
     */
    public function update(int $teacherId, array $data): bool
    {
        return $this->db->update('teachers', $data, 'teacher_id = ?', [$teacherId]) > 0;
    }

    /**
     * Delete a teacher
     */
    public function delete(int $teacherId): bool
    {
        return $this->db->delete('teachers', 'teacher_id = ?', [$teacherId]) > 0;
    }

    /**
     * Check if email exists in teachers table
     */
    public function teacherEmailExists(string $email, ?int $exceptTeacherId = null): bool
    {
        $sql = "SELECT 1 FROM teachers WHERE email = ?";
        $params = [$email];
        
        if ($exceptTeacherId) {
            $sql .= " AND teacher_id != ?";
            $params[] = $exceptTeacherId;
        }
        
        return $this->db->fetch($sql, $params) !== null;
    }
}
