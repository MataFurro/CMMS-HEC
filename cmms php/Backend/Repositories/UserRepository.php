<?php

namespace Backend\Repositories;

use Backend\Core\DatabaseService;
use PDO;

/**
 * repositories/UserRepository.php
 * ─────────────────────────────────────────────────────
 * Gestión de persistencia para users y technicians.
 * ─────────────────────────────────────────────────────
 */
class UserRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = DatabaseService::getInstance();
    }

    /**
     * Buscar usuario por email
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email AND active = 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * Crear un nuevo usuario
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO users (name, email, password_hash, role, active) 
                VALUES (:name, :email, :password_hash, :role, 1)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'name'          => $data['name'],
            'email'         => $data['email'],
            'password_hash' => password_hash($data['password'] ?? 'BioPass2026', PASSWORD_DEFAULT),
            'role'          => $data['role']
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Vincular datos técnicos a un usuario
     */
    public function createTechnician(int $userId, string $specialty = 'General'): bool
    {
        $sql = "INSERT INTO technicians (user_id, specialty) VALUES (:user_id, :specialty)";
        return $this->db->prepare($sql)->execute([
            'user_id'   => $userId,
            'specialty' => $specialty
        ]);
    }

    /**
     * Obtener técnicos con métricas
     */
    public function findAllTechnicians(): array
    {
        $sql = "SELECT u.id, u.name, u.role, u.avatar_url, 
                       t.specialty, t.active_ots as active, t.completed_ots as completed, 
                       t.completed_ots as ot_terminadas, t.capacity_pct as capacity 
                FROM users u
                JOIN technicians t ON u.id = t.user_id
                WHERE u.active = 1";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Obtener técnicos por productividad
     */
    public function getTechniciansByProductivity(): array
    {
        $sql = "SELECT u.name, t.specialty, t.completed_ots as ot_terminadas, t.capacity_pct as capacity
                FROM users u
                JOIN technicians t ON u.id = t.user_id
                WHERE u.active = 1
                ORDER BY t.completed_ots DESC";
        return $this->db->query($sql)->fetchAll();
    }
}
