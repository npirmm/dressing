<?php
// src/Models/Status.php

namespace App\Models; // Vérifiez ce namespace

use App\Core\Database;
use App\Utils\Helper;

class Status { // Vérifiez le nom de la classe
    private Database $dbInstance;
    private string $tableName = 'statuses';

    public function __construct() {
        $this->dbInstance = Database::getInstance();
    }

    public function getAll(string $sortBy = 'name', string $sortOrder = 'ASC'): array {
        $allowedSortColumns = ['id', 'name', 'availability_type', 'created_at'];
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

    public function create(array $data): int|false {
        $sql = "INSERT INTO {$this->tableName} (name, availability_type, description, created_at, updated_at) 
                VALUES (:name, :availability_type, :description, NOW(), NOW())";
        $params = [
            ':name' => $data['name'],
            ':availability_type' => $data['availability_type'],
            ':description' => $data['description'] ?? null
        ];
        $stmt = $this->dbInstance->query($sql, $params);
        if ($stmt) {
            $id = (int)$this->dbInstance->lastInsertId();
            Helper::logAction(strtoupper($this->tableName).'_CREATE', ucfirst($this->tableName), $id, "Status '{$data['name']}' created.");
            return $id;
        }
        return false;
    }

    public function update(int $id, array $data): bool {
        $sql = "UPDATE {$this->tableName} SET 
                name = :name, 
                availability_type = :availability_type,
                description = :description,
                updated_at = NOW() 
                WHERE id = :id";
        $params = [
            ':id' => $id,
            ':name' => $data['name'],
            ':availability_type' => $data['availability_type'],
            ':description' => $data['description'] ?? null
        ];
        $stmt = $this->dbInstance->query($sql, $params);
        if ($stmt) {
            Helper::logAction(strtoupper($this->tableName).'_UPDATE', ucfirst($this->tableName), $id, "Status '{$data['name']}' (ID: {$id}) updated.");
            return true;
        }
        return false;
    }

    public function delete(int $id): bool {
        $status = $this->findById($id);
        if (!$status) return false;
        // Vérifier si utilisé dans articles.current_status_id
        // La FK devrait avoir ON DELETE RESTRICT ou ON DELETE SET NULL
        // Si RESTRICT, la suppression échouera si en cours d'utilisation.
        $sql = "DELETE FROM {$this->tableName} WHERE id = :id";
        $stmt = $this->dbInstance->query($sql, [':id' => $id]);
        if ($stmt && $stmt->rowCount() > 0) {
            Helper::logAction(strtoupper($this->tableName).'_DELETE', ucfirst($this->tableName), $id, "Status '{$status['name']}' (ID: {$id}) deleted.");
            return true;
        }
        return false;
    }
    
    // Pour le formulaire Article, et aussi pour le formulaire Status lui-même
    public static function getAvailableAvailabilityTypes(): array {
        return ['in_stock', 'out_of_stock', 'limbo'];
    }
}