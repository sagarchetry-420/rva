<?php
namespace App\Modules\Fees\Repositories;

use Database;

/**
 * ServiceRepository — Data access for services and student_services
 */
class ServiceRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findAll(): array
    {
        return $this->db->fetchAll("SELECT * FROM services ORDER BY service_name");
    }

    public function findById(int $serviceId): ?array
    {
        return $this->db->fetch("SELECT * FROM services WHERE service_id = ?", [$serviceId]);
    }

    public function create(array $data): int
    {
        return $this->db->insert('services', $data);
    }

    public function update(int $serviceId, array $data): bool
    {
        return $this->db->update('services', $data, 'service_id = ?', [$serviceId]) > 0;
    }

    public function delete(int $serviceId): bool
    {
        return $this->db->delete('services', 'service_id = ?', [$serviceId]) > 0;
    }

    public function serviceExists(string $serviceName, ?int $exceptId = null): bool
    {
        $sql = "SELECT 1 FROM services WHERE service_name = ?";
        $params = [$serviceName];
        if ($exceptId) {
            $sql .= " AND service_id != ?";
            $params[] = $exceptId;
        }
        return $this->db->fetch($sql, $params) !== null;
    }

    // --- Student Services Mapping ---

    public function getStudentServices(int $studentId, int $sessionId): array
    {
        $sql = "SELECT ss.*, s.service_name 
                FROM student_services ss
                JOIN services s ON ss.service_id = s.service_id
                WHERE ss.student_id = ? AND ss.session_id = ?";
        return $this->db->fetchAll($sql, [$studentId, $sessionId]);
    }
}
