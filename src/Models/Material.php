<?php
// src/Models/Material.php

namespace App\Models;

use App\Core\Database;
use App\Utils\Helper;

class Material {
    private Database $dbInstance;
    private string $tableName = 'materials';

    public function __construct() {
        $this->dbInstance = Database::getInstance();
    }

    /**
     * Fetches all materials with sorting options.
     * @param string $sortBy The column to sort by.
     * @param string $sortOrder The order of sorting ('ASC' or 'DESC').
     * @return array An array of all materials.
     */
    public function getAll(string $sortBy = 'name', string $sortOrder = 'ASC'): array {
        // Liste blanche des colonnes autorisées pour le tri pour éviter l'injection SQL
        $allowedSortColumns = ['id', 'name', 'created_at'];
        if (!in_array(strtolower($sortBy), $allowedSortColumns)) {
            $sortBy = 'name'; // Colonne de tri par défaut si non autorisée
        }

        // S'assurer que sortOrder est soit ASC soit DESC
        $sortOrder = strtoupper($sortOrder);
        if ($sortOrder !== 'ASC' && $sortOrder !== 'DESC') {
            $sortOrder = 'ASC'; // Ordre par défaut
        }

        // La colonne est mise directement dans la requête, d'où l'importance de la liste blanche.
        $sql = "SELECT * FROM {$this->tableName} ORDER BY `{$sortBy}` {$sortOrder}";
        
        $stmt = $this->dbInstance->query($sql); // Pas de paramètres à binder ici pour ORDER BY
        return $stmt ? $stmt->fetchAll() : [];
    }

    public function findById(int $id): array|false {
        $stmt = $this->dbInstance->query("SELECT * FROM {$this->tableName} WHERE id = :id", [':id' => $id]);
        return $stmt ? $stmt->fetch() : false;
    }

    /**
     * Checks if a material name exists, optionally excluding an ID.
     * Used by the Validation class.
     * @param string $name The name to check.
     * @param int|null $excludeId The ID to exclude from the check.
     * @return bool
     */
    public function nameExists(string $name, ?int $excludeId = null): bool {
        $sql = "SELECT id FROM {$this->tableName} WHERE name = :name";
        $params = [':name' => $name];
        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
            $params[':exclude_id'] = $excludeId;
        }
        $stmt = $this->dbInstance->query($sql, $params);
        return $stmt && $stmt->fetch() !== false;
    }

    /**
     * Creates a new material.
     * @param array $data Data containing 'name'.
     * @return int|false The ID of the newly created material, or false on failure.
     */
    public function create(array $data): int|false {
        $sql = "INSERT INTO {$this->tableName} (name, created_at, updated_at) 
                VALUES (:name, NOW(), NOW())";
        
        $params = [
            ':name' => $data['name']
        ];
        
        $stmt = $this->dbInstance->query($sql, $params);

        if ($stmt) {
            $id = (int)$this->dbInstance->lastInsertId();
            Helper::logAction(strtoupper($this->tableName).'_CREATE', ucfirst($this->tableName), $id, "Material '{$data['name']}' created.");
            return $id;
        }
        return false;
    }

    /**
     * Updates an existing material.
     * @param int $id The ID of the material to update.
     * @param array $data Data containing 'name'.
     * @return bool True on success, false on failure.
     */
    public function update(int $id, array $data): bool {
        $sql = "UPDATE {$this->tableName} SET 
                name = :name, 
                updated_at = NOW() 
                WHERE id = :id";
        
        $params = [
            ':id' => $id,
            ':name' => $data['name']
        ];

        $stmt = $this->dbInstance->query($sql, $params);

        if ($stmt) { // No need to check rowCount if we allow "update with same data"
             Helper::logAction(strtoupper($this->tableName).'_UPDATE', ucfirst($this->tableName), $id, "Material '{$data['name']}' (ID: {$id}) updated.");
            return true;
        }
        return false;
    }

    /**
     * Deletes a material.
     * @param int $id The ID of the material to delete.
     * @return bool True on success, false on failure.
     */
    public function delete(int $id): bool {
        $material = $this->findById($id); // For logging
        if (!$material) return false;

        // Before deleting, you might want to check if this material is used in any articles.
        // For now, we'll assume direct deletion is okay.
        // The articles.material_id is SET NULL ON DELETE if that's how you've set up the FK.

        $sql = "DELETE FROM {$this->tableName} WHERE id = :id";
        $stmt = $this->dbInstance->query($sql, [':id' => $id]);

        if ($stmt && $stmt->rowCount() > 0) {
            Helper::logAction(strtoupper($this->tableName).'_DELETE', ucfirst($this->tableName), $id, "Material '{$material['name']}' (ID: {$id}) deleted.");
            return true;
        }
        return false;
    }
}