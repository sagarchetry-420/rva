<?php
namespace App\Modules\Auth\Repositories;

/**
 * UserRepository — Database operations for the users table
 */
class UserRepository
{
    private \Database $db;

    public function __construct()
    {
        $this->db = \Database::getInstance();
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetch("SELECT * FROM users WHERE user_id = ?", [$id]);
    }

    public function findByEmail(string $email): ?array
    {
        return $this->db->fetch("SELECT * FROM users WHERE email = ?", [$email]);
    }

    public function findByUsername(string $username): ?array
    {
        return $this->db->fetch("SELECT * FROM users WHERE username = ?", [$username]);
    }

    public function findByEmailOrUsername(string $identifier): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM users WHERE email = ? OR username = ?",
            [$identifier, $identifier]
        );
    }

    public function updatePassword(int $userId, string $hashedPassword): bool
    {
        return $this->db->execute(
            "UPDATE users SET password_hash = ? WHERE user_id = ?",
            [$hashedPassword, $userId]
        ) > 0;
    }

    public function updateResetToken(int $userId, ?string $token, ?string $expiry): bool
    {
        return $this->db->execute(
            "UPDATE users SET reset_token_hash = ?, reset_token_expiry = ? WHERE user_id = ?",
            [$token, $expiry, $userId]
        ) > 0;
    }

    public function findByResetToken(string $token): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM users WHERE reset_token_hash = ? AND reset_token_expiry > NOW()",
            [$token]
        );
    }

    public function create(array $data): int
    {
        return $this->db->insert('users', $data);
    }

    public function update(int $userId, array $data): bool
    {
        return $this->db->update('users', $data, 'user_id = ?', [$userId]) > 0;
    }

    public function delete(int $userId): bool
    {
        return $this->db->delete('users', 'user_id = ?', [$userId]) > 0;
    }

    public function emailExists(string $email, ?int $exceptUserId = null): bool
    {
        $sql = "SELECT 1 FROM users WHERE email = ?";
        $params = [$email];
        if ($exceptUserId) {
            $sql .= " AND user_id != ?";
            $params[] = $exceptUserId;
        }
        return $this->db->fetch($sql, $params) !== null;
    }

    public function usernameExists(string $username): bool
    {
        return $this->db->fetch("SELECT 1 FROM users WHERE username = ?", [$username]) !== null;
    }

    public function getLoginAttempts(string $ipAddress, string $identifier): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM login_attempts WHERE ip_address = ? AND identifier = ?",
            [$ipAddress, $identifier]
        );
    }

    public function incrementLoginAttempts(string $ipAddress, string $identifier): void
    {
        $record = $this->getLoginAttempts($ipAddress, $identifier);
        
        if ($record) {
            $this->db->execute(
                "UPDATE login_attempts SET attempts = attempts + 1, last_attempt = NOW() WHERE ip_address = ? AND identifier = ?",
                [$ipAddress, $identifier]
            );
        } else {
            $this->db->insert('login_attempts', [
                'ip_address' => $ipAddress,
                'identifier' => $identifier,
                'attempts' => 1,
                'last_attempt' => date('Y-m-d H:i:s')
            ]);
        }
    }

    public function clearLoginAttempts(string $ipAddress, string $identifier): void
    {
        $this->db->execute(
            "DELETE FROM login_attempts WHERE ip_address = ? AND identifier = ?",
            [$ipAddress, $identifier]
        );
    }
}
