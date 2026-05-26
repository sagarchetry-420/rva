<?php
namespace App\Modules\Fees\Repositories;

use Database;

/**
 * FeeCategoryRepository — Data access for fee_categories
 */
class FeeCategoryRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findAll(): array
    {
        return $this->db->fetchAll("SELECT * FROM fee_categories ORDER BY category_name");
    }

    public function findById(int $categoryId): ?array
    {
        return $this->db->fetch("SELECT * FROM fee_categories WHERE category_id = ?", [$categoryId]);
    }

    public function create(array $data): int
    {
        return $this->db->insert('fee_categories', $data);
    }

    public function update(int $categoryId, array $data): bool
    {
        return $this->db->update('fee_categories', $data, 'category_id = ?', [$categoryId]) > 0;
    }

    public function delete(int $categoryId): bool
    {
        return $this->db->delete('fee_categories', 'category_id = ?', [$categoryId]) > 0;
    }

    public function categoryExists(string $categoryName, ?int $exceptId = null): bool
    {
        $sql = "SELECT 1 FROM fee_categories WHERE category_name = ?";
        $params = [$categoryName];
        if ($exceptId) {
            $sql .= " AND category_id != ?";
            $params[] = $exceptId;
        }
        return $this->db->fetch($sql, $params) !== null;
    }
}
