<?php
namespace App\Modules\Student\Repositories;

/**
 * StudentRepository — Database operations for the students table
 */
class StudentRepository
{
    private \Database $db;

    public function __construct()
    {
        $this->db = \Database::getInstance();
    }

    /**
     * Find all students with optional class filter, paginated
     */
    public function findAll(?int $classId = null, int $page = 1, int $perPage = 20, ?string $searchQuery = null): array
    {
        $sql = "SELECT s.*, c.class_name, c.section, u.username, u.email as user_email
                FROM students s
                LEFT JOIN classes c ON s.current_class_id = c.class_id
                LEFT JOIN users u ON s.user_id = u.user_id";
        $params = [];
        $conditions = [];

        if ($classId && $classId > 0) {
            $conditions[] = "s.current_class_id = ?";
            $params[] = $classId;
        }

        if (!empty($searchQuery)) {
            $conditions[] = "(s.first_name LIKE ? OR s.last_name LIKE ? OR CONCAT(s.first_name, ' ', s.last_name) LIKE ? OR s.roll_number LIKE ? OR s.email LIKE ? OR s.phone LIKE ? OR u.username LIKE ?)";
            $searchTerm = "%{$searchQuery}%";
            $cleanSearch = ltrim($searchQuery, '@');
            $usernameSearchTerm = "%{$cleanSearch}%";
            array_push($params, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $usernameSearchTerm);
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $sql .= " ORDER BY s.student_id DESC";
        return $this->db->paginate($sql, $params, $page, $perPage);
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetch(
            "SELECT s.*, c.class_name, c.section, u.username, u.email as user_email
             FROM students s
             LEFT JOIN classes c ON s.current_class_id = c.class_id
             LEFT JOIN users u ON s.user_id = u.user_id
             WHERE s.student_id = ?",
            [$id]
        );
    }

    public function findByUserId(int $userId): ?array
    {
        return $this->db->fetch("SELECT * FROM students WHERE user_id = ?", [$userId]);
    }

    public function create(array $data): int
    {
        return $this->db->insert('students', $data);
    }

    public function update(int $id, array $data): bool
    {
        return $this->db->update('students', $data, 'student_id = ?', [$id]) > 0;
    }

    public function delete(int $id): bool
    {
        // Get user_id first for cascade delete
        $student = $this->db->fetch("SELECT user_id FROM students WHERE student_id = ?", [$id]);
        if (!$student) return false;

        $this->db->delete('students', 'student_id = ?', [$id]);
        $this->db->delete('users', 'user_id = ?', [$student['user_id']]);
        return true;
    }

    public function rollNumberExists(string $rollNumber, int $classId, ?int $exceptStudentId = null): bool
    {
        // Check in students table
        $sql1 = "SELECT 1 FROM students WHERE current_class_id = ? AND roll_number = ?";
        $params1 = [$classId, $rollNumber];
        if ($exceptStudentId) {
            $sql1 .= " AND student_id != ?";
            $params1[] = $exceptStudentId;
        }
        
        if ($this->db->fetch($sql1, $params1) !== null) {
            return true;
        }

        // Also check in student_academics to ensure no historic collisions for the active session
        // (Assuming we only care about the current session, but checking all sessions for safety is fine)
        $sql2 = "SELECT 1 FROM student_academics WHERE class_id = ? AND roll_number = ?";
        $params2 = [$classId, $rollNumber];
        if ($exceptStudentId) {
            $sql2 .= " AND student_id != ?";
            $params2[] = $exceptStudentId;
        }

        return $this->db->fetch($sql2, $params2) !== null;
    }

    public function countByClass(int $classId): int
    {
        return $this->db->count('students', 'current_class_id = ?', [$classId]);
    }

    public function getForExport(?int $classId = null): array
    {
        $sql = "SELECT s.roll_number, s.first_name, s.last_name, u.username,
                       c.class_name, c.section, s.date_of_birth, s.gender,
                       s.phone, s.email, s.parent_name, s.parent_phone
                FROM students s
                LEFT JOIN classes c ON s.current_class_id = c.class_id
                LEFT JOIN users u ON s.user_id = u.user_id";
        $params = [];

        if ($classId && $classId > 0) {
            $sql .= " WHERE s.current_class_id = ?";
            $params[] = $classId;
        }

        $sql .= " ORDER BY c.class_name, s.roll_number";
        return $this->db->fetchAll($sql, $params);
    }
}
