<?php
// src/Models/EventType.php

namespace App\Models;

use App\Core\Database;
use App\Utils\Helper;
use PDO;

class EventType {
    private Database $dbInstance;
    private string $tableName = 'event_types';
    private string $pivotTableName = 'event_type_day_moment';

    public function __construct() {
        $this->dbInstance = Database::getInstance();
    }

    public function getAll(string $sortBy = 'name', string $sortOrder = 'ASC'): array {
        $allowedSortColumns = ['id', 'name', 'description', 'created_at'];
        // Le tri par 'typical_day_moments' est plus complexe maintenant, on le retire du tri simple
        if (!in_array(strtolower($sortBy), $allowedSortColumns)) {
            $sortBy = 'name';
        }
        $sortOrder = strtoupper($sortOrder);
        if ($sortOrder !== 'ASC' && $sortOrder !== 'DESC') {
            $sortOrder = 'ASC';
        }

        $sql = "SELECT et.*, GROUP_CONCAT(dm.name ORDER BY dm.sort_order SEPARATOR ', ') as day_moments_names
                FROM {$this->tableName} et
                LEFT JOIN {$this->pivotTableName} etdm ON et.id = etdm.event_type_id
                LEFT JOIN day_moments dm ON etdm.day_moment_id = dm.id
                GROUP BY et.id
                ORDER BY `et`.`{$sortBy}` {$sortOrder}";
        
        $stmt = $this->dbInstance->query($sql);
        return $stmt ? $stmt->fetchAll() : [];
    }

    public function findById(int $id): array|false {
        $sql = "SELECT * FROM {$this->tableName} WHERE id = :id";
        $stmt = $this->dbInstance->query($sql, [':id' => $id]);
        $eventType = $stmt ? $stmt->fetch() : false;

        if ($eventType) {
            $eventType['selected_day_moment_ids'] = $this->getSelectedDayMomentIds($id);
        }
        return $eventType;
    }

    public function getSelectedDayMomentIds(int $eventTypeId): array {
        $sql = "SELECT day_moment_id FROM {$this->pivotTableName} WHERE event_type_id = :event_type_id";
        $stmt = $this->dbInstance->query($sql, [':event_type_id' => $eventTypeId]);
        return $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : []; // Récupère juste une colonne d'IDs
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

    public function create(array $data, array $dayMomentIds = []): int|false {
        $pdo = $this->dbInstance->getConnection(); // Get PDO for transaction
        try {
            $pdo->beginTransaction();

            $sql = "INSERT INTO {$this->tableName} (name, description, created_at, updated_at) 
                    VALUES (:name, :description, NOW(), NOW())";
            $params = [
                ':name' => $data['name'],
                ':description' => $data['description'] ?? null
            ];
            // Important: Utiliser une instance séparée de statement pour le modèle Database
            $stmtMain = $this->dbInstance->query($sql, $params);

            if (!$stmtMain) {
                $pdo->rollBack();
                return false;
            }
            $id = (int)$this->dbInstance->lastInsertId();

            $this->syncDayMoments($id, $dayMomentIds); // Gère la table pivot

            $pdo->commit();
            Helper::logAction(strtoupper($this->tableName).'_CREATE', ucfirst($this->tableName), $id, "EventType '{$data['name']}' created.");
            return $id;

        } catch (\Exception $e) {
            $pdo->rollBack();
            error_log("Error creating EventType: " . $e->getMessage());
            return false;
        }
    }

    public function update(int $id, array $data, array $dayMomentIds = []): bool {
        $pdo = $this->dbInstance->getConnection();
        try {
            $pdo->beginTransaction();

            $sql = "UPDATE {$this->tableName} SET 
                    name = :name, 
                    description = :description,
                    updated_at = NOW() 
                    WHERE id = :id";
            $params = [
                ':id' => $id,
                ':name' => $data['name'],
                ':description' => $data['description'] ?? null
            ];
            $stmtMain = $this->dbInstance->query($sql, $params);

            if (!$stmtMain) {
                $pdo->rollBack();
                return false;
            }

            $this->syncDayMoments($id, $dayMomentIds);

            $pdo->commit();
            Helper::logAction(strtoupper($this->tableName).'_UPDATE', ucfirst($this->tableName), $id, "EventType '{$data['name']}' (ID: {$id}) updated.");
            return true;

        } catch (\Exception $e) {
            $pdo->rollBack();
            error_log("Error updating EventType: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Synchronizes the day moments for a given event type in the pivot table.
     * Deletes old associations and adds new ones.
     */
    private function syncDayMoments(int $eventTypeId, array $dayMomentIds): void {
        // 1. Delete existing associations for this event_type_id
        $this->dbInstance->query("DELETE FROM {$this->pivotTableName} WHERE event_type_id = :event_type_id", [':event_type_id' => $eventTypeId]);

        // 2. Add new associations
        if (!empty($dayMomentIds)) {
            $sqlInsertPivot = "INSERT INTO {$this->pivotTableName} (event_type_id, day_moment_id) VALUES (:event_type_id, :day_moment_id)";
            foreach ($dayMomentIds as $momentId) {
                $this->dbInstance->query($sqlInsertPivot, [
                    ':event_type_id' => $eventTypeId,
                    ':day_moment_id' => (int)$momentId // S'assurer que c'est un entier
                ]);
            }
        }
    }

    public function delete(int $id): bool {
        // La suppression en cascade sur les FK de la table pivot s'occupera de event_type_day_moment
        $item = $this->findById($id);
        if (!$item) return false;
        $sql = "DELETE FROM {$this->tableName} WHERE id = :id";
        $stmt = $this->dbInstance->query($sql, [':id' => $id]);
        if ($stmt && $stmt->rowCount() > 0) {
            Helper::logAction(strtoupper($this->tableName).'_DELETE', ucfirst($this->tableName), $id, "EventType '{$item['name']}' (ID: {$id}) deleted.");
            return true;
        }
        return false;
    }
}