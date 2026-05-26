<?php
namespace App\Modules\Fees\Repositories;

use Database;

/**
 * FeeRepository — Data access for student fees and receipts
 */
class FeeRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findFeesByStudent(int $studentId, int $sessionId): array
    {
        $sql = "SELECT f.*, fc.category_name, s.service_name 
                FROM fees f
                JOIN fee_categories fc ON f.category_id = fc.category_id
                LEFT JOIN services s ON f.service_id = s.service_id
                WHERE f.student_id = ? AND f.session_id = ?
                ORDER BY f.due_date ASC";
        return $this->db->fetchAll($sql, [$studentId, $sessionId]);
    }

    public function findById(int $feeId): ?array
    {
        $sql = "SELECT f.*, fc.category_name, s.service_name 
                FROM fees f
                JOIN fee_categories fc ON f.category_id = fc.category_id
                LEFT JOIN services s ON f.service_id = s.service_id
                WHERE f.fee_id = ?";
        return $this->db->fetch($sql, [$feeId]);
    }

    public function createFee(array $data): int
    {
        return $this->db->insert('fees', $data);
    }

    public function updateFee(int $feeId, array $data): bool
    {
        return $this->db->update('fees', $data, 'fee_id = ?', [$feeId]) > 0;
    }

    public function searchStudents(string $term, int $sessionId, int $page = 1, int $perPage = 20): array
    {
        $term = '%' . trim($term) . '%';
        $sql = "SELECT DISTINCT s.student_id, s.roll_number, s.first_name, s.last_name, c.class_name, c.section,
                       (SELECT SUM(amount) FROM fees f WHERE f.student_id = s.student_id AND f.session_id = ? AND f.payment_status = 'Pending') as pending_amount
                FROM students s
                LEFT JOIN student_academics sa ON s.student_id = sa.student_id AND sa.session_id = ?
                LEFT JOIN classes c ON COALESCE(sa.class_id, s.current_class_id) = c.class_id
                LEFT JOIN fees f ON f.student_id = s.student_id AND f.session_id = ?
                WHERE (sa.session_id IS NOT NULL OR f.fee_id IS NOT NULL)
                AND (sa.admission_status = 'Active' OR sa.admission_status IS NULL)
                AND (s.roll_number LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ? OR f.receipt_number LIKE ? OR f.remarks LIKE ?)
                ORDER BY c.class_name, s.first_name";
        return $this->db->paginate($sql, [$sessionId, $sessionId, $sessionId, $term, $term, $term, $term, $term], $page, $perPage);
    }

    public function findStudentsByClass(int $classId, int $sessionId, int $page = 1, int $perPage = 20): array
    {
        $sql = "SELECT s.student_id, s.roll_number, s.first_name, s.last_name, c.class_name, c.section,
                       (SELECT SUM(amount) FROM fees f WHERE f.student_id = s.student_id AND f.session_id = ? AND f.payment_status = 'Pending') as pending_amount
                FROM students s
                LEFT JOIN student_academics sa ON s.student_id = sa.student_id AND sa.session_id = ?
                LEFT JOIN classes c ON COALESCE(sa.class_id, s.current_class_id) = c.class_id
                WHERE COALESCE(sa.class_id, s.current_class_id) = ? 
                AND (sa.session_id IS NOT NULL OR EXISTS (SELECT 1 FROM fees f2 WHERE f2.student_id = s.student_id AND f2.session_id = ?))
                AND (sa.admission_status = 'Active' OR sa.admission_status IS NULL)
                ORDER BY s.roll_number ASC, s.first_name ASC";
        return $this->db->paginate($sql, [$sessionId, $sessionId, $classId, $sessionId], $page, $perPage);
    }

    public function getReceiptData(string $receiptNumber): ?array
    {
        $sql = "SELECT f.*, fc.category_name, s.service_name, 
                       st.first_name, st.last_name, st.roll_number, 
                       c.class_name, c.section, u.username as received_by
                FROM fees f
                JOIN fee_categories fc ON f.category_id = fc.category_id
                LEFT JOIN services s ON f.service_id = s.service_id
                JOIN students st ON f.student_id = st.student_id
                LEFT JOIN student_academics sa ON sa.student_id = st.student_id AND sa.session_id = f.session_id
                LEFT JOIN classes c ON sa.class_id = c.class_id
                LEFT JOIN users u ON f.created_by = u.user_id
                WHERE f.receipt_number = ?";
        return $this->db->fetch($sql, [$receiptNumber]);
    }
}
