<?php
// src/Models/Brand.php

namespace App\Models;

use App\Core\Database;
use App\Utils\Helper;

class Brand {
    private Database $dbInstance;
    private string $tableName = 'brands'; // Helper for generic unique checks

    public function __construct() {
        $this->dbInstance = Database::getInstance();
    }

    public function getAll(): array {
        $stmt = $this->dbInstance->query("SELECT * FROM {$this->tableName} ORDER BY name ASC");
        return $stmt ? $stmt->fetchAll() : [];
    }

    public function findById(int $id): array|false {
        $stmt = $this->dbInstance->query("SELECT * FROM {$this->tableName} WHERE id = :id", [':id' => $id]);
        return $stmt ? $stmt->fetch() : false;
    }

    /**
     * Checks if a brand name exists, optionally excluding an ID.
     * Used by the Validation class.
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
     * Checks if an abbreviation exists, optionally excluding an ID.
     * Used by the Validation class.
     */
    public function abbreviationExists(?string $abbreviation, ?int $excludeId = null): bool {
        if (empty($abbreviation)) return false;
        $sql = "SELECT id FROM {$this->tableName} WHERE abbreviation = :abbreviation";
        $params = [':abbreviation' => $abbreviation];
        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
            $params[':exclude_id'] = $excludeId;
        }
        $stmt = $this->dbInstance->query($sql, $params);
        return $stmt && $stmt->fetch() !== false;
    }


    public function create(array $data): int|false {
        // Data should already be validated by the controller using the Validation class
        $sql = "INSERT INTO {$this->tableName} (name, abbreviation, created_at, updated_at) 
                VALUES (:name, :abbreviation, NOW(), NOW())";
        $stmt = $this->dbInstance->query($sql, [
            ':name' => $data['name'],
            ':abbreviation' => $data['abbreviation'] ?? null
        ]);

        if ($stmt) {
            $id = (int)$this->dbInstance->lastInsertId();
            Helper::logAction('BRAND_CREATE', $this->tableName, $id, "Brand '{$data['name']}' created.");
            return $id;
        }
        return false;
    }

    public function update(int $id, array $data): bool {
        // Data should already be validated
        $sql = "UPDATE {$this->tableName} SET name = :name, abbreviation = :abbreviation, updated_at = NOW() 
                WHERE id = :id";
        $stmt = $this->dbInstance->query($sql, [
            ':id' => $id,
            ':name' => $data['name'],
            ':abbreviation' => $data['abbreviation'] ?? null
        ]);

        if ($stmt) {
             Helper::logAction('BRAND_UPDATE', $this->tableName, $id, "Brand '{$data['name']}' (ID: {$id}) updated.");
            return true;
        }
        return false;
    }

    public function delete(int $id): bool {
        $brand = $this->findById($id);
        if (!$brand) return false;

        $sql = "DELETE FROM {$this->tableName} WHERE id = :id";
        $stmt = $this->dbInstance->query($sql, [':id' => $id]);

        if ($stmt && $stmt->rowCount() > 0) {
            Helper::logAction('BRAND_DELETE', $this->tableName, $id, "Brand '{$brand['name']}' (ID: {$id}) deleted.");
            return true;
        }
        return false;
    }
}