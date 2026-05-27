<?php
namespace App\Modules\Academic\Repositories;

use Database;

/**
 * ClassRepository — Database operations for the classes table
 */
class ClassRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findById(int $classId): ?array
    {
        return $this->db->fetch("SELECT * FROM classes WHERE class_id = ?", [$classId]);
    }

    public function findAll(): array
    {
        $sql = "SELECT c.*, t.first_name, t.last_name 
                FROM classes c
                LEFT JOIN teachers t ON c.class_teacher_id = t.teacher_id
                ORDER BY LENGTH(c.class_name), c.class_name, c.section";
        return $this->db->fetchAll($sql);
    }

    public function create(array $data): int
    {
        return $this->db->insert('classes', $data);
    }

    public function update(int $classId, array $data): bool
    {
        return $this->db->update('classes', $data, 'class_id = ?', [$classId]) > 0;
    }

    public function delete(int $classId): bool
    {
        return $this->db->delete('classes', 'class_id = ?', [$classId]) > 0;
    }

    /**
     * Check if a class and section combination already exists
     */
    public function classExists(string $className, string $section, ?int $exceptClassId = null): bool
    {
        $sql = "SELECT 1 FROM classes WHERE class_name = ? AND section = ?";
        $params = [$className, $section];
        
        if ($exceptClassId) {
            $sql .= " AND class_id != ?";
            $params[] = $exceptClassId;
        }
        
        return $this->db->fetch($sql, $params) !== null;
    }
}
