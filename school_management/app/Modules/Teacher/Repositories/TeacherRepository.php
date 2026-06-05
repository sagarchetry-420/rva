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
        $sql = "SELECT t.*, u.username 
                FROM teachers t 
                LEFT JOIN users u ON t.user_id = u.user_id 
                WHERE t.teacher_id = ?";
        return $this->db->fetch($sql, [$teacherId]);
    }

    /**
     * Get all teachers paginated
     */
    public function findAll(?string $status = null, int $page = 1, int $perPage = 20, string $search = ''): array
    {
        $sql = "SELECT t.*, u.username 
                FROM teachers t
                JOIN users u ON t.user_id = u.user_id
                WHERE 1=1";
        $params = [];
        
        if ($status !== null) {
            $sql .= " AND t.status = ?";
            $params[] = $status;
        }

        if (!empty($search)) {
            $sql .= " AND (t.first_name LIKE ? OR t.last_name LIKE ? OR CONCAT(t.first_name, ' ', t.last_name) LIKE ? OR t.email LIKE ? OR t.phone LIKE ? OR u.username LIKE ?)";
            $searchTerm = "%{$search}%";
            $cleanSearch = ltrim($search, '@');
            $usernameSearchTerm = "%{$cleanSearch}%";
            
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $usernameSearchTerm;
        }
        
        $sql .= " ORDER BY t.first_name, t.last_name";
        return $this->db->paginate($sql, $params, $page, $perPage);
    }

    /**
     * Get all teachers without pagination (for export)
     */
    public function getAll(?string $status = null, string $search = ''): array
    {
        $sql = "SELECT t.*, u.username 
                FROM teachers t
                JOIN users u ON t.user_id = u.user_id
                WHERE 1=1";
        $params = [];
        
        if ($status !== null) {
            $sql .= " AND t.status = ?";
            $params[] = $status;
        }

        if (!empty($search)) {
            $sql .= " AND (t.first_name LIKE ? OR t.last_name LIKE ? OR CONCAT(t.first_name, ' ', t.last_name) LIKE ? OR t.email LIKE ? OR t.phone LIKE ? OR u.username LIKE ?)";
            $searchTerm = "%{$search}%";
            $cleanSearch = ltrim($search, '@');
            $usernameSearchTerm = "%{$cleanSearch}%";
            
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $usernameSearchTerm;
        }
        
        $sql .= " ORDER BY t.first_name, t.last_name";
        return $this->db->fetchAll($sql, $params);
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
