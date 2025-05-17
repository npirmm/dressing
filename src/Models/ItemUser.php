<?php
// src/Models/ItemUser.php

namespace App\Models;

use App\Core\Database;
use App\Utils\Helper;

class ItemUser {
    private Database $dbInstance;
    private string $tableName = 'item_users';

    public function __construct() {
        $this->dbInstance = Database::getInstance();
    }

    public function getAll(string $sortBy = 'name', string $sortOrder = 'ASC'): array {
        $allowedSortColumns = ['id', 'name', 'abbreviation', 'created_at'];
        if (!in_array(strtolower($sortBy), $allowedSortColumns)) {
            $sortBy = 'name';
        }
        $sortOrder = strtoupper($sortOrder);
        if ($sortOrder !== 'ASC' && $sortOrder !== 'DESC') {
            $sortOrder = 'ASC';
        }
        $sql = "SELECT * FROM {$this->tableName} ORDER BY `{$sortBy}` {$sortOrder}";
        $stmt = $this->dbInstance->query($sql);
        return $stmt ? $stmt->fetchAll() : [];
    }

    public function findById(int $id): array|false {
        $stmt = $this->dbInstance->query("SELECT * FROM {$this->tableName} WHERE id = :id", [':id' => $id]);
        return $stmt ? $stmt->fetch() : false;
    }

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

    public function abbreviationExists(string $abbreviation, ?int $excludeId = null): bool {
        // La vérification empty() n'est plus nécessaire ici si le champ est NOT NULL en BDD
        // et que la validation en amont s'assure qu'il n'est pas vide.
        // Cependant, la garder ne fait pas de mal si la validation PHP laissait passer une chaîne vide.
        if (empty($abbreviation)) return false; // Théoriquement, ne devrait pas arriver si 'required'

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
        // $data['abbreviation'] est maintenant supposé être NOT NULL
        $sql = "INSERT INTO {$this->tableName} (name, abbreviation, created_at, updated_at) 
                VALUES (:name, :abbreviation, NOW(), NOW())";
        $params = [
            ':name' => $data['name'],
            ':abbreviation' => $data['abbreviation'] // Plus de '?? null' ici
        ];
        // ... (reste de la méthode)
        $stmt = $this->dbInstance->query($sql, $params);
        if ($stmt) {
            $id = (int)$this->dbInstance->lastInsertId();
            Helper::logAction(strtoupper($this->tableName).'_CREATE', ucfirst(str_replace('_', '', $this->tableName)), $id, "Item User '{$data['name']}' created.");
            return $id;
        }
        return false;
    }

    public function update(int $id, array $data): bool {
        // $data['abbreviation'] est maintenant supposé être NOT NULL
        $sql = "UPDATE {$this->tableName} SET 
                name = :name, 
                abbreviation = :abbreviation,
                updated_at = NOW() 
                WHERE id = :id";
        $params = [
            ':id' => $id,
            ':name' => $data['name'],
            ':abbreviation' => $data['abbreviation'] // Plus de '?? null' ici
        ];
        // ... (reste de la méthode)
        $stmt = $this->dbInstance->query($sql, $params);
        if ($stmt) {
            Helper::logAction(strtoupper($this->tableName).'_UPDATE', ucfirst(str_replace('_', '', $this->tableName)), $id, "Item User '{$data['name']}' (ID: {$id}) updated.");
            return true;
        }
        return false;
    }

    public function delete(int $id): bool {
        $itemUser = $this->findById($id);
        if (!$itemUser) return false;
        $sql = "DELETE FROM {$this->tableName} WHERE id = :id";
        $stmt = $this->dbInstance->query($sql, [':id' => $id]);
        if ($stmt && $stmt->rowCount() > 0) {
            Helper::logAction(strtoupper($this->tableName).'_DELETE', ucfirst(str_replace('_', '', $this->tableName)), $id, "Item User '{$itemUser['name']}' (ID: {$id}) deleted.");
            return true;
        }
        return false;
    }
}