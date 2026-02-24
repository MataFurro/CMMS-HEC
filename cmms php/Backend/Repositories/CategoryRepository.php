<?php

namespace Backend\Repositories;

use PDO;

class CategoryRepository
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getAll()
    {
        try {
            $stmt = $this->db->query("SELECT * FROM asset_categories ORDER BY name ASC");
            if (!$stmt) return [];
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            // Si la tabla no existe, devolver vacío en lugar de crash
            return [];
        }
    }

    public function findByName($name)
    {
        $stmt = $this->db->prepare("SELECT * FROM asset_categories WHERE name = ?");
        $stmt->execute([$name]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($name)
    {
        $stmt = $this->db->prepare("INSERT IGNORE INTO asset_categories (name) VALUES (?)");
        $stmt->execute([$name]);
        return $this->db->lastInsertId();
    }
}
