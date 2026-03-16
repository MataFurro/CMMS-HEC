<?php

namespace Backend\Repositories;

use Backend\Core\DatabaseService;
use Backend\Core\LoggerService;
use Backend\Models\AssetEntity;

require_once __DIR__ . '/../Core/DatabaseService.php';
require_once __DIR__ . '/../Core/LoggerService.php';

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
            $stmt = $this->db->query("SELECT * FROM assets WHERE en_uso = 1 ORDER BY created_at DESC");
            LoggerService::info("Consulta exitosa: Lista total de activos generada.");
            while ($row = $stmt->fetch()) {
                yield AssetEntity::fromArray($row);
            }
        } catch (\Exception $e) {
            LoggerService::error("Error en AssetRepository::findAll", ['error' => $e->getMessage()]);
            throw new \Backend\Core\Exceptions\DatabaseException("Error al consultar lista de activos.");
        }
    }

    public function findById($id): ?AssetEntity
    {
        try {
            // Buscamos primero por la llave primaria ID, luego por inventory_id o hec_id
            $stmt = $this->db->prepare("SELECT * FROM assets WHERE id = :id OR inventory_id = :id_fallback OR hec_id = :id_hec LIMIT 1");
            $stmt->execute(['id' => $id, 'id_fallback' => $id, 'id_hec' => $id]);
            $asset = $stmt->fetch();

            return $asset ? AssetEntity::fromArray($asset) : null;
        } catch (\Exception $e) {
            LoggerService::error("Error en AssetRepository::findById", ['id' => $id, 'error' => $e->getMessage()]);
            return null;
        }
    }


    /**
     * Buscar activo por huella digital (número de serie + nombre + ubicación).
     * Usado para detectar duplicados durante importación de Excel.
     * Se usa como fallback cuando el inventory_id no coincide.
     */
    public function findByFingerprint(string $serial, string $name, string $location): ?AssetEntity
    {
        try {
            // Intentar coincidencia por serie (más confiable), excluyendo series genéricas
            $genericSerials = ['S/S', 'S/I', 'N/A', 'SIN SERIE', '0', '-', 'COMODATO', ''];
            $hasValidSerial = !in_array(strtoupper(trim($serial)), $genericSerials) && strlen(trim($serial)) > 2;

            if ($hasValidSerial) {
                $stmt = $this->db->prepare("SELECT * FROM assets WHERE LOWER(TRIM(serial_number)) = LOWER(TRIM(:serial)) AND en_uso = 1 LIMIT 1");
                $stmt->execute(['serial' => $serial]);
                $asset = $stmt->fetch();
                if ($asset) return AssetEntity::fromArray($asset);
            }

            // Fallback: coincidencia por nombre + ubicación (para activos sin serie única)
            if (!empty($name) && !empty($location)) {
                $stmt = $this->db->prepare("SELECT * FROM assets WHERE LOWER(TRIM(name)) = LOWER(TRIM(:name)) AND LOWER(TRIM(location)) = LOWER(TRIM(:location)) AND en_uso = 1 LIMIT 1");
                $stmt->execute(['name' => $name, 'location' => $location]);
                $asset = $stmt->fetch();
                if ($asset) return AssetEntity::fromArray($asset);
            }

            return null;
        } catch (\Exception $e) {
            LoggerService::error("Error en AssetRepository::findByFingerprint", ['serial' => $serial, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Buscar activo EXCLUSIVAMENTE por inventory_id (columna de BD).
     * Distinto de findById() que usa is_numeric para decidir el campo.
     * Usar siempre este método durante importación Excel para evitar
     * confundir inventory_id numéricos (ej. '500000010669') con PKs internas.
     */
    public function findByInventoryId(string $inventoryId): ?AssetEntity
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM assets WHERE inventory_id = :inventory_id AND en_uso = 1 LIMIT 1");
            $stmt->execute(['inventory_id' => $inventoryId]);
            $asset = $stmt->fetch();
            return $asset ? AssetEntity::fromArray($asset) : null;
        } catch (\Exception $e) {
            LoggerService::error("Error en AssetRepository::findByInventoryId", ['inventory_id' => $inventoryId, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Buscar TODOS los activos que comparten un determinado inventory_id.
     * Útil para deduplicar por N° de Inventario + N° de Serie.
     */
    public function findAllByInventoryId(string $inventoryId): array
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM assets WHERE inventory_id = :inventory_id AND en_uso = 1");
            $stmt->execute(['inventory_id' => $inventoryId]);
            $results = [];
            while ($row = $stmt->fetch()) {
                $results[] = AssetEntity::fromArray($row);
            }
            return $results;
        } catch (\Exception $e) {
            LoggerService::error("Error en AssetRepository::findAllByInventoryId", ['inventory_id' => $inventoryId, 'error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Buscar un activo EXCLUSIVAMENTE por N° de Serie.
     * Usado durante importación cuando el SN es el identificador primario del equipo físico.
     * Si el SN es válido y único, este método prevalece sobre el inventory_id.
     */
    public function findBySerialNumber(string $serial): ?AssetEntity
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM assets WHERE UPPER(TRIM(serial_number)) = UPPER(TRIM(:serial)) AND en_uso = 1 LIMIT 1"
            );
            $stmt->execute(['serial' => $serial]);
            $asset = $stmt->fetch();
            return $asset ? AssetEntity::fromArray($asset) : null;
        } catch (\Exception $e) {
            LoggerService::error("Error en AssetRepository::findBySerialNumber", ['serial' => $serial, 'error' => $e->getMessage()]);
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
        $params = [
            ':q1' => "%$query%",
            ':q2' => "%$query%",
            ':q3' => "%$query%",
            ':q4' => "%$query%",
            ':q5' => "%$query%"
        ];

        $sql = "SELECT * FROM assets 
                WHERE en_uso = 1 
                AND (name LIKE :q1 OR brand LIKE :q2 OR inventory_id LIKE :q3 OR serial_number LIKE :q4 OR hec_id LIKE :q5 OR status LIKE :q6)";
        $params[':q6'] = "%$query%";

        if ($status !== 'ALL') {
            if ($status === 'OPERATIVE') {
                $sql .= " AND status IN ('OPERATIVE', 'BUENO')";
            } elseif ($status === 'NO_OPERATIVE') {
                $sql .= " AND status IN ('NO_OPERATIVE', 'MALO')";
            } elseif ($status === 'OPERATIVE_WITH_OBS') {
                $sql .= " AND status IN ('OPERATIVE_WITH_OBS', 'REGULAR')";
            } else {
                $sql .= " AND status = :status";
                $params[':status'] = $status;
            }
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
            if ($filters['criticality'] === 'LOW' || $filters['criticality'] === 'NA') {
                $sql .= " AND (criticality IS NULL OR criticality = '' OR criticality = 'NA' OR criticality = 'LOW' OR criticality NOT IN ('CRITICAL', 'RELEVANT'))";
            } else {
                $sql .= " AND criticality = :criticality";
                $params[':criticality'] = $filters['criticality'];
            }
        }

        if (!empty($filters['family']) && $filters['family'] !== 'ALL') {
            $sql .= " AND riesgo_ge = :riesgo_ge";
            $params[':riesgo_ge'] = $filters['family'];
        }

        if (!empty($filters['category_id']) && $filters['category_id'] !== 'ALL') {
            $sql .= " AND category_id = :category_id";
            $params[':category_id'] = $filters['category_id'];
        }

        // Ordenar por HEC ID agrupando las familias y luego por nombre si estamos viendo "Todos"
        $orderBy = !empty($filters['family']) && $filters['family'] === 'ALL'
            ? "hec_id ASC, name ASC"
            : "hec_id ASC, name ASC";

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
        $params = [
            ':q1' => "%$query%",
            ':q2' => "%$query%",
            ':q3' => "%$query%",
            ':q4' => "%$query%",
            ':q5' => "%$query%"
        ];

        $sql = "SELECT COUNT(*) FROM assets 
                WHERE en_uso = 1 
                AND (name LIKE :q1 OR brand LIKE :q2 OR inventory_id LIKE :q3 OR serial_number LIKE :q4 OR hec_id LIKE :q5 OR status LIKE :q6)";
        $params[':q6'] = "%$query%";

        if ($status !== 'ALL') {
            if ($status === 'OPERATIVE') {
                $sql .= " AND status IN ('OPERATIVE', 'BUENO')";
            } elseif ($status === 'NO_OPERATIVE') {
                $sql .= " AND status IN ('NO_OPERATIVE', 'MALO')";
            } elseif ($status === 'OPERATIVE_WITH_OBS') {
                $sql .= " AND status IN ('OPERATIVE_WITH_OBS', 'REGULAR')";
            } else {
                $sql .= " AND status = :status";
                $params[':status'] = $status;
            }
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
            if ($filters['criticality'] === 'LOW' || $filters['criticality'] === 'NA') {
                $sql .= " AND (criticality IS NULL OR criticality = '' OR criticality = 'NA' OR criticality = 'LOW' OR criticality NOT IN ('CRITICAL', 'RELEVANT'))";
            } else {
                $sql .= " AND criticality = :criticality";
                $params[':criticality'] = $filters['criticality'];
            }
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
                    SUM(CASE WHEN status IN ('OPERATIVE', 'BUENO') THEN 1 ELSE 0 END) as operative,
                    SUM(CASE WHEN status = 'MAINTENANCE' THEN 1 ELSE 0 END) as maintenance,
                    SUM(CASE WHEN status IN ('NO_OPERATIVE', 'MALO') THEN 1 ELSE 0 END) as no_operative,
                    SUM(CASE WHEN status IN ('OPERATIVE_WITH_OBS', 'REGULAR') THEN 1 ELSE 0 END) as with_obs
                FROM assets
                WHERE en_uso = 1";

        return $this->db->query($sql)->fetch();
    }

    /**
     * Obtener ubicaciones únicas desde la tabla de activos con sus conteos
     */
    public function getCountsByLocation(): array
    {
        $sql = "SELECT location, COUNT(*) as count 
                FROM assets 
                WHERE en_uso = 1 AND location IS NOT NULL AND location != '' 
                GROUP BY location 
                ORDER BY location ASC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener marcas únicas desde la tabla de activos con sus conteos
     */
    public function getCountsByBrand(): array
    {
        $sql = "SELECT brand, COUNT(*) as count 
                FROM assets 
                WHERE en_uso = 1 AND brand IS NOT NULL AND brand != '' 
                GROUP BY brand 
                ORDER BY brand ASC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener ubicaciones únicas desde la tabla de activos (legacy support)
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
                inventory_id, hec_id, name, serial_number, brand, model, location, sub_location, 
                vendor, contract_id, ownership, criticality, status, riesgo_ge, codigo_umdns, 
                fecha_instalacion, purchased_year, acquisition_cost, 
                total_useful_life, useful_life_pct, years_remaining, 
                warranty_expiration, under_maintenance_plan, en_uso, 
                image_url, observations, annual_maint_cost, subclase
            ) VALUES (
                :inventory_id, :hec_id, :name, :serial_number, :brand, :model, :location, :sub_location, 
                :vendor, :contract_id, :ownership, :criticality, :status, :riesgo_ge, :codigo_umdns, 
                :fecha_instalacion, :purchased_year, :acquisition_cost, 
                :total_useful_life, :useful_life_pct, :years_remaining, 
                :warranty_expiration, :under_maintenance_plan, :en_uso, 
                :image_url, :observations, :annual_maint_cost, :subclase
            )";

            $stmt = $this->db->prepare($sql);

            $params = [
                ':inventory_id' => $data['inventory_id'] ?? ($data['id'] ?? null),
                ':hec_id' => $data['hec_id'] ?? null,
                ':name' => $data['name'],
                ':serial_number' => $data['serial_number'] ?? null,
                ':brand' => $data['brand'] ?? null,
                ':model' => $data['model'] ?? null,
                ':location' => $data['location'] ?? null,
                ':sub_location' => $data['sub_location'] ?? null,
                ':vendor' => $data['vendor'] ?? null,
                ':contract_id' => $data['contract_id'] ?? null,
                ':ownership' => $data['ownership'] ?? 'PROPIO',
                ':criticality' => $data['criticality'] ?? 'LOW',
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
                ':observations' => $data['observations'] ?? null,
                ':annual_maint_cost' => $data['annual_maint_cost'] ?? 0.0,
                ':subclase' => $data['subclase'] ?? null
            ];

            if ($stmt->execute($params)) {
                return (int)$this->db->lastInsertId();
            }
            return false;
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
                'hec_id',
                'name',
                'inventory_id',
                'brand',
                'model',
                'serial_number',
                'location',
                'sub_location',
                'vendor',
                'contract_id',
                'ownership',
                'criticality',
                'status',
                'riesgo_ge',
                'codigo_umdns',
                'fecha_instalacion',
                'purchased_year',
                'acquisition_cost',
                'total_useful_life',
                'useful_life_pct',
                'years_remaining',
                'warranty_expiration',
                'under_maintenance_plan',
                'en_uso',
                'image_url',
                'observations',
                'annual_maint_cost',
                'clase_riesgo',
                'riesgo_biomedico',
                'valor_reposicion',
                'frecuencia_mp_meses',
                'subclase',
                'ip_address',
                'mac_address',
                'firmware_version',
                'os_version',
                'is_aem',
                'next_maintenance_date'
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
     * Eliminar un activo por ID (Hard Delete)
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

    /**
     * Soft Delete (marcar como fuera de uso)
     */
    public function softDelete(string $id): bool
    {
        try {
            $stmt = $this->db->prepare("UPDATE assets SET en_uso = 0, status = 'RETIRED', updated_at = NOW() WHERE id = :id");
            return $stmt->execute(['id' => $id]);
        } catch (\Exception $e) {
            LoggerService::error("Error en AssetRepository::softDelete", ['id' => $id, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Restaurar un activo
     */
    public function restore(string $id): bool
    {
        try {
            $stmt = $this->db->prepare("UPDATE assets SET en_uso = 1, updated_at = NOW() WHERE id = :id");
            return $stmt->execute(['id' => $id]);
        } catch (\Exception $e) {
            LoggerService::error("Error en AssetRepository::restore", ['id' => $id, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Borrado masivo (Soft Delete)
     */
    public function bulkSoftDelete(array $ids): bool
    {
        if (empty($ids)) return true;
        try {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $sql = "UPDATE assets SET en_uso = 0, updated_at = NOW() WHERE id IN ($placeholders)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($ids);
        } catch (\Exception $e) {
            LoggerService::error("Error en AssetRepository::bulkSoftDelete", ['ids' => $ids, 'error' => $e->getMessage()]);
            return false;
        }
    }
}
