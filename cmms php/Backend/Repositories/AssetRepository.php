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
     */
    public function search(string $query = '', string $status = 'ALL', array $filters = []): Generator
    {
        return $this->searchPaginated($query, $status, 0, 0, $filters);
    }

    /**
     * Buscar activos con filtros y paginación
     */
    public function searchPaginated(string $query = '', string $status = 'ALL', int $limit = 0, int $offset = 0, array $filters = []): Generator
    {
        $sql = "SELECT * FROM assets 
                WHERE (name LIKE :q1 OR brand LIKE :q2 OR id LIKE :q3 OR serial_number LIKE :q4)";

        $params = [
            ':q1' => "%$query%",
            ':q2' => "%$query%",
            ':q3' => "%$query%",
            ':q4' => "%$query%"
        ];

        if ($status !== 'ALL') {
            $sql .= " AND status = :status";
            $params[':status'] = $status;
        }

        if (!empty($filters['location']) && $filters['location'] !== 'ALL') {
            $sql .= " AND location = :location";
            $params[':location'] = $filters['location'];
        }

        if (!empty($filters['brand']) && $filters['brand'] !== 'ALL') {
            $sql .= " AND brand = :brand";
            $params[':brand'] = $filters['brand'];
        }

        if (!empty($filters['criticality']) && $filters['criticality'] !== 'ALL') {
            $sql .= " AND criticality = :criticality";
            $params[':criticality'] = $filters['criticality'];
        }

        if (!empty($filters['family']) && $filters['family'] !== 'ALL') {
            $sql .= " AND riesgo_ge = :riesgo_ge";
            $params[':riesgo_ge'] = $filters['family'];
        }

        if (!empty($filters['category_id']) && $filters['category_id'] !== 'ALL') {
            $sql .= " AND category_id = :category_id";
            $params[':category_id'] = $filters['category_id'];
        }

        // Ordenar por riesgo_ge primero si estamos viendo "Todos" para facilitar la agrupación visual
        $orderBy = !empty($filters['family']) && $filters['family'] === 'ALL'
            ? "riesgo_ge ASC, criticality DESC, name ASC"
            : "criticality DESC, name ASC";

        $sql .= " ORDER BY $orderBy";

        if ($limit > 0) {
            $sql .= " LIMIT $limit OFFSET $offset";
        }

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            while ($row = $stmt->fetch()) {
                yield AssetEntity::fromArray($row);
            }
        } catch (\Exception $e) {
            LoggerService::error("Error en AssetRepository::searchPaginated", ['query' => $query, 'filters' => $filters, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Contar resultados de búsqueda para paginación
     */
    public function countSearchResults(string $query = '', string $status = 'ALL', array $filters = []): int
    {
        $sql = "SELECT COUNT(*) FROM assets 
                WHERE (name LIKE :q1 OR brand LIKE :q2 OR id LIKE :q3 OR serial_number LIKE :q4)";

        $params = [
            ':q1' => "%$query%",
            ':q2' => "%$query%",
            ':q3' => "%$query%",
            ':q4' => "%$query%"
        ];

        if ($status !== 'ALL') {
            $sql .= " AND status = :status";
            $params[':status'] = $status;
        }

        if (!empty($filters['location']) && $filters['location'] !== 'ALL') {
            $sql .= " AND location = :location";
            $params[':location'] = $filters['location'];
        }

        if (!empty($filters['brand']) && $filters['brand'] !== 'ALL') {
            $sql .= " AND brand = :brand";
            $params[':brand'] = $filters['brand'];
        }

        if (!empty($filters['criticality']) && $filters['criticality'] !== 'ALL') {
            $sql .= " AND criticality = :criticality";
            $params[':criticality'] = $filters['criticality'];
        }

        if (!empty($filters['family']) && $filters['family'] !== 'ALL') {
            $sql .= " AND riesgo_ge = :riesgo_ge";
            $params[':riesgo_ge'] = $filters['family'];
        }

        if (!empty($filters['category_id']) && $filters['category_id'] !== 'ALL') {
            $sql .= " AND category_id = :category_id";
            $params[':category_id'] = $filters['category_id'];
        }

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return (int) $stmt->fetchColumn();
        } catch (\Exception $e) {
            LoggerService::error("Error en AssetRepository::countSearchResults", ['query' => $query, 'filters' => $filters, 'error' => $e->getMessage()]);
            return 0;
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
     * Obtener ubicaciones únicas desde la tabla de activos
     */
    public function getUniqueLocations(): array
    {
        $stmt = $this->db->query("SELECT DISTINCT location FROM assets WHERE location IS NOT NULL AND location != '' ORDER BY location ASC");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Obtener marcas únicas desde la tabla de activos
     */
    public function getUniqueBrands(): array
    {
        $stmt = $this->db->query("SELECT DISTINCT brand FROM assets WHERE brand IS NOT NULL AND brand != '' ORDER BY brand ASC");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Obtener criticidades únicas
     */
    public function getUniqueCriticalities(): array
    {
        $stmt = $this->db->query("SELECT DISTINCT criticality FROM assets WHERE criticality IS NOT NULL AND criticality != '' ORDER BY criticality ASC");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Obtener categorías únicas
     */
    public function getUniqueCategories(): array
    {
        $stmt = $this->db->query("SELECT DISTINCT category_id FROM assets WHERE category_id IS NOT NULL AND category_id != '' ORDER BY category_id ASC");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Crear un nuevo activo
     */
    public function create(array $data): bool
    {
        try {
            $sql = "INSERT INTO assets (
                id, name, serial_number, brand, model, location, sub_location, 
                vendor, ownership, criticality, status, riesgo_ge, codigo_umdns, 
                fecha_instalacion, purchased_year, acquisition_cost, 
                total_useful_life, useful_life_pct, years_remaining, 
                warranty_expiration, under_maintenance_plan, en_uso, 
                image_url, observations
            ) VALUES (
                :id, :name, :serial_number, :brand, :model, :location, :sub_location, 
                :vendor, :ownership, :criticality, :status, :riesgo_ge, :codigo_umdns, 
                :fecha_instalacion, :purchased_year, :acquisition_cost, 
                :total_useful_life, :useful_life_pct, :years_remaining, 
                :warranty_expiration, :under_maintenance_plan, :en_uso, 
                :image_url, :observations
            )";

            $stmt = $this->db->prepare($sql);

            $params = [
                ':id' => $data['id'] ?? null,
                ':name' => $data['name'] ?? null,
                ':serial_number' => $data['serial_number'] ?? null,
                ':brand' => $data['brand'] ?? null,
                ':model' => $data['model'] ?? null,
                ':location' => $data['location'] ?? null,
                ':sub_location' => $data['sub_location'] ?? null,
                ':vendor' => $data['vendor'] ?? null,
                ':ownership' => $data['ownership'] ?? 'Propio',
                ':criticality' => $data['criticality'] ?? 'RELEVANT',
                ':status' => $data['status'] ?? 'OPERATIVE',
                ':riesgo_ge' => $data['riesgo_ge'] ?? null,
                ':codigo_umdns' => $data['codigo_umdns'] ?? null,
                ':fecha_instalacion' => $data['fecha_instalacion'] ?? null,
                ':purchased_year' => $data['purchased_year'] ?? null,
                ':acquisition_cost' => $data['acquisition_cost'] ?? 0.0,
                ':total_useful_life' => $data['total_useful_life'] ?? 10,
                ':useful_life_pct' => $data['useful_life_pct'] ?? 100,
                ':years_remaining' => $data['years_remaining'] ?? 10,
                ':warranty_expiration' => $data['warranty_expiration'] ?? null,
                ':under_maintenance_plan' => (int)($data['under_maintenance_plan'] ?? 0),
                ':en_uso' => (int)($data['en_uso'] ?? 1),
                ':image_url' => $data['image_url'] ?? 'https://via.placeholder.com/300',
                ':observations' => $data['observations'] ?? null
            ];

            return $stmt->execute($params);
        } catch (\Exception $e) {
            LoggerService::error("Error en AssetRepository::create", ['id' => $data['id'] ?? 'N/A', 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Actualización parcial de un activo (útil para completar datos desde la OT)
     */
    public function partialUpdate(string $id, array $data): bool
    {
        try {
            $fields = [];
            $params = [':id' => $id];

            $allowedFields = [
                'name',
                'brand',
                'model',
                'serial_number',
                'location',
                'sub_location',
                'status',
                'useful_life_pct',
                'total_useful_life',
                'years_remaining',
                'purchased_year',
                'clase_riesgo',
                'riesgo_biomedico',
                'valor_reposicion',
                'frecuencia_mp_meses'
            ];
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $fields[] = "$field = :$field";
                    $params[":$field"] = $data[$field];
                }
            }

            if (empty($fields)) return true;

            $sql = "UPDATE assets SET " . implode(", ", $fields) . ", updated_at = NOW() WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } catch (\Exception $e) {
            LoggerService::error("Error en AssetRepository::partialUpdate", ['id' => $id, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Eliminar un activo por ID
     */
    public function delete(string $id): bool
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM assets WHERE id = :id");
            return $stmt->execute(['id' => $id]);
        } catch (\Exception $e) {
            LoggerService::error("Error en AssetRepository::delete", ['id' => $id, 'error' => $e->getMessage()]);
            return false;
        }
    }
}
