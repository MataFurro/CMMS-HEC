<?php

namespace Backend\Repositories;

use Backend\Core\DatabaseService;
use Backend\Core\LoggerService;
use Backend\Models\AssetEntity;
use PDO;
use Generator;

/**
 * repositories/AssetRepository.php
 * ─────────────────────────────────────────────────────
 * Repositorio de persistencia puros para la tabla 'assets'.
 * No contiene lógica de negocio, solo acceso a datos SQL.
 * ─────────────────────────────────────────────────────
 */
class AssetRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = DatabaseService::getInstance();
    }

    /**
     * Obtener todos los activos usando un Generador (Memory Efficient)
     * @return Generator<AssetEntity>
     */
    public function findAll(): Generator
    {
        try {
            $stmt = $this->db->query("SELECT * FROM assets ORDER BY created_at DESC");
            LoggerService::info("Consulta exitosa: Lista total de activos generada.");
            while ($row = $stmt->fetch()) {
                yield AssetEntity::fromArray($row);
            }
        } catch (\Exception $e) {
            LoggerService::error("Error en AssetRepository::findAll", ['error' => $e->getMessage()]);
            throw new \Backend\Core\Exceptions\DatabaseException("Error al consultar lista de activos.");
        }
    }

    /**
     * Buscar un activo por ID
     */
    public function findById(string $id): ?AssetEntity
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM assets WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $asset = $stmt->fetch();
            return $asset ? AssetEntity::fromArray($asset) : null;
        } catch (\Exception $e) {
            LoggerService::error("Error en AssetRepository::findById", ['id' => $id, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Buscar activos con filtros usando Generadores
     * @return Generator<AssetEntity>
     */
    public function search(string $query = '', string $status = 'ALL'): Generator
    {
        $sql = "SELECT * FROM assets 
                WHERE (name LIKE :q OR brand LIKE :q OR id LIKE :q)";

        $params = [':q' => "%$query%"];

        if ($status !== 'ALL') {
            $sql .= " AND status = :status";
            $params[':status'] = $status;
        }

        $sql .= " ORDER BY criticality DESC, name ASC";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            while ($row = $stmt->fetch()) {
                yield AssetEntity::fromArray($row);
            }
        } catch (\Exception $e) {
            LoggerService::error("Error en AssetRepository::search", ['query' => $query, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Obtener estadísticas de conteo por estado
     */
    public function getStatusCounts(): array
    {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'OPERATIVE' THEN 1 ELSE 0 END) as operative,
                    SUM(CASE WHEN status = 'MAINTENANCE' THEN 1 ELSE 0 END) as maintenance,
                    SUM(CASE WHEN status = 'NO_OPERATIVE' THEN 1 ELSE 0 END) as no_operative,
                    SUM(CASE WHEN status = 'OPERATIVE_WITH_OBS' THEN 1 ELSE 0 END) as with_obs
                FROM assets";

        return $this->db->query($sql)->fetch();
    }

    /**
     * Obtener ubicaciones únicas
     */
    public function getUniqueLocations(): array
    {
        $stmt = $this->db->query("SELECT DISTINCT location FROM assets WHERE location IS NOT NULL ORDER BY location ASC");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
