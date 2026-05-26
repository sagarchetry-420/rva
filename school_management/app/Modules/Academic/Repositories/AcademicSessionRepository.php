<?php
namespace App\Modules\Academic\Repositories;

use Database;

/**
 * AcademicSessionRepository — Database operations for academic_sessions table
 */
class AcademicSessionRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findAll(): array
    {
        return $this->db->fetchAll("SELECT * FROM academic_sessions ORDER BY start_date DESC");
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetch("SELECT * FROM academic_sessions WHERE session_id = ?", [$id]);
    }

    public function getActiveSession(): ?array
    {
        return $this->db->fetch("SELECT * FROM academic_sessions WHERE is_current = 1 LIMIT 1");
    }

    public function create(array $data): int
    {
        // If creating the very first session and want it active, check if any exists
        if (isset($data['is_current']) && $data['is_current'] == 1) {
            $this->db->execute("UPDATE academic_sessions SET is_current = 0");
        }
        
        return $this->db->insert('academic_sessions', $data);
    }

    public function update(int $id, array $data): bool
    {
        if (isset($data['is_current']) && $data['is_current'] == 1) {
            $this->db->execute("UPDATE academic_sessions SET is_current = 0");
        }
        
        return $this->db->update('academic_sessions', $data, 'session_id = ?', [$id]) > 0;
    }

    public function setAsCurrent(int $sessionId): bool
    {
        $this->db->beginTransaction();
        try {
            // Unset current for all
            $this->db->execute("UPDATE academic_sessions SET is_current = 0");
            
            // Set for the chosen one
            $this->db->update('academic_sessions', ['is_current' => 1], 'session_id = ?', [$sessionId]);
            
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            return false;
        }
    }

    public function delete(int $id): bool
    {
        // Don't delete if it's the current session
        $session = $this->findById($id);
        if ($session && $session['is_current']) {
            return false;
        }
        
        return $this->db->delete('academic_sessions', 'session_id = ?', [$id]) > 0;
    }
}
