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
    /** @var PDO|\Backend\Core\SafePDO */
    private $db;

    public function __construct()
    {
        $this->db = DatabaseService::getInstance();
    }

    /**
     * Buscar usuario por email
     */
    public function findByEmail(string $email): ?array
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email AND active = 1");
            if (!$stmt) return null;
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();
            return $user ?: null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Buscar usuario por ID
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute(['id' => $id]);
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
     * Obtener todos los técnicos activos con métricas calculadas en tiempo real
     */
    public function findAllTechnicians(): array
    {
        try {
            $sql = "SELECT u.id, u.name, u.role, u.avatar_url, t.specialty,
                           (SELECT COUNT(*) FROM work_orders WHERE assigned_tech_id = u.id AND status IN ('En Curso', 'En Espera')) as active,
                           (SELECT COUNT(*) FROM work_orders WHERE assigned_tech_id = u.id AND status = 'Terminada') as completed,
                           (SELECT COUNT(*) FROM work_orders WHERE assigned_tech_id = u.id AND status = 'Terminada') as ot_terminadas,
                           -- Cálculo de capacidad: (OTs activas / 10) * 100, tope 100% (Estimación base)
                           LEAST(ROUND(((SELECT COUNT(*) FROM work_orders WHERE assigned_tech_id = u.id AND status IN ('En Curso', 'En Espera')) / 10) * 100), 100) as capacity
                    FROM users u
                    JOIN technicians t ON u.id = t.user_id
                    WHERE u.active = 1";
            $stmt = $this->db->query($sql);
            if (!$stmt) return [];
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Obtener técnicos por productividad (OTs terminadas)
     */
    public function getTechniciansByProductivity(): array
    {
        try {
            $sql = "SELECT u.id, u.name, t.specialty, 
                           (SELECT COUNT(*) FROM work_orders WHERE assigned_tech_id = u.id AND status = 'Terminada') as ot_terminadas,
                           -- MTTR: Promedio de horas de duración de OTs terminadas
                           COALESCE((SELECT ROUND(AVG(duration_hours), 1) FROM work_orders WHERE assigned_tech_id = u.id AND status = 'Terminada'), 0) as mttr,
                           -- SLA: % de OTs terminadas en <= 48h (aprox 2 días entre creación y terminación)
                           COALESCE((SELECT ROUND((COUNT(*) * 100.0) / NULLIF((SELECT COUNT(*) FROM work_orders WHERE assigned_tech_id = u.id AND status = 'Terminada'), 0)) 
                                     FROM work_orders 
                                     WHERE assigned_tech_id = u.id 
                                       AND status = 'Terminada' 
                                       AND DATEDIFF(completed_date, created_date) <= 2), 0) as sla_compliance,
                           -- Proporción: % de Preventivas sobre el total de terminadas
                           COALESCE((SELECT ROUND((COUNT(*) * 100.0) / NULLIF((SELECT COUNT(*) FROM work_orders WHERE assigned_tech_id = u.id AND status = 'Terminada'), 0)) 
                                     FROM work_orders 
                                     WHERE assigned_tech_id = u.id 
                                       AND status = 'Terminada' 
                                       AND type = 'Preventiva'), 0) as prev_ratio,
                           LEAST(ROUND(((SELECT COUNT(*) FROM work_orders WHERE assigned_tech_id = u.id AND status IN ('En Curso', 'En Espera')) / 10) * 100), 100) as capacity
                    FROM users u
                    JOIN technicians t ON u.id = t.user_id
                    WHERE u.active = 1
                    ORDER BY ot_terminadas DESC";
            $stmt = $this->db->query($sql);
            if (!$stmt) return [];
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Verificar contraseña de un usuario
     */
    public function verifyPassword(int $userId, string $password): bool
    {
        $stmt = $this->db->prepare("SELECT password_hash FROM users WHERE id = :id AND active = 1");
        $stmt->execute(['id' => $userId]);
        $hash = $stmt->fetchColumn();
        if (!$hash) return false;
        return password_verify($password, $hash);
    }
}
