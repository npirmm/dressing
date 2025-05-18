<?php
// src/Models/StorageLocation.php

namespace App\Models;

use App\Core\Database;
use App\Utils\Helper;

class StorageLocation {
    private Database $dbInstance;
    private string $tableName = 'storage_locations';

    public function __construct() {
        $this->dbInstance = Database::getInstance();
    }

    public function getAll(string $sortBy = 'room', string $sortOrder = 'ASC'): array {
        // full_location_path est une colonne générée, on peut trier dessus
        $allowedSortColumns = ['id', 'room', 'area', 'shelf_or_rack', 'level_or_section', 'specific_spot_or_box', 'full_location_path', 'created_at'];
        if (!in_array(strtolower($sortBy), $allowedSortColumns)) {
            $sortBy = 'room'; // Trier par 'room' puis 'area' etc. serait plus logique, ou 'full_location_path'
        }
        if ($sortBy === 'room') { // Tri par défaut plus logique
            $orderByClause = '`room` ASC, `area` ASC, `shelf_or_rack` ASC, `level_or_section` ASC, `specific_spot_or_box` ASC';
            if (strtoupper($sortOrder) === 'DESC') {
                 $orderByClause = '`room` DESC, `area` DESC, `shelf_or_rack` DESC, `level_or_section` DESC, `specific_spot_or_box` DESC';
            }
        } else {
            $sortOrderSanitized = strtoupper($sortOrder);
            if ($sortOrderSanitized !== 'ASC' && $sortOrderSanitized !== 'DESC') {
                $sortOrderSanitized = 'ASC';
            }
            $orderByClause = "`{$sortBy}` {$sortOrderSanitized}";
        }

        $sql = "SELECT * FROM {$this->tableName} ORDER BY {$orderByClause}";
        $stmt = $this->dbInstance->query($sql);
        return $stmt ? $stmt->fetchAll() : [];
    }

    public function findById(int $id): array|false {
        $stmt = $this->dbInstance->query("SELECT * FROM {$this->tableName} WHERE id = :id", [':id' => $id]);
        return $stmt ? $stmt->fetch() : false;
    }

    // Optionnel: Vérifier si un chemin complet exact existe déjà (si vous voulez imposer l'unicité)
    public function fullPathExists(array $data, ?int $excludeId = null): bool {
        // Construire le chemin comme la BDD le ferait (approximativement) pour la vérification
        $pathParts = [];
        if (!empty($data['room'])) $pathParts[] = $data['room'];
        if (!empty($data['area'])) $pathParts[] = $data['area'];
        if (!empty($data['shelf_or_rack'])) $pathParts[] = $data['shelf_or_rack'];
        if (!empty($data['level_or_section'])) $pathParts[] = $data['level_or_section'];
        if (!empty($data['specific_spot_or_box'])) $pathParts[] = $data['specific_spot_or_box'];
        $testPath = implode(' > ', $pathParts);

        if (empty($testPath)) return false; // Ne pas vérifier un chemin vide

        $sql = "SELECT id FROM {$this->tableName} WHERE full_location_path = :full_path";
        $params = [':full_path' => $testPath];
        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
            $params[':exclude_id'] = $excludeId;
        }
        $stmt = $this->dbInstance->query($sql, $params);
        return $stmt && $stmt->fetch() !== false;
    }


    public function create(array $data): int|false {
        $sql = "INSERT INTO {$this->tableName} 
                (room, area, shelf_or_rack, level_or_section, specific_spot_or_box, created_at, updated_at) 
                VALUES (:room, :area, :shelf_or_rack, :level_or_section, :specific_spot_or_box, NOW(), NOW())";
        $params = [
            ':room' => $data['room'],
            ':area' => $data['area'] ?? null,
            ':shelf_or_rack' => $data['shelf_or_rack'] ?? null,
            ':level_or_section' => $data['level_or_section'] ?? null,
            ':specific_spot_or_box' => $data['specific_spot_or_box'] ?? null
        ];
        $stmt = $this->dbInstance->query($sql, $params);
        if ($stmt) {
            $id = (int)$this->dbInstance->lastInsertId();
            // Pour le log, on pourrait récupérer le full_location_path généré
            $newItem = $this->findById($id);
            $logName = $newItem ? $newItem['full_location_path'] : 'ID ' . $id;
            Helper::logAction(strtoupper($this->tableName).'_CREATE', ucfirst(str_replace('_','',$this->tableName)), $id, "Storage Location '{$logName}' created.");
            return $id;
        }
        return false;
    }

    public function update(int $id, array $data): bool {
        $sql = "UPDATE {$this->tableName} SET 
                room = :room, 
                area = :area,
                shelf_or_rack = :shelf_or_rack,
                level_or_section = :level_or_section,
                specific_spot_or_box = :specific_spot_or_box,
                updated_at = NOW() 
                WHERE id = :id";
        $params = [
            ':id' => $id,
            ':room' => $data['room'],
            ':area' => $data['area'] ?? null,
            ':shelf_or_rack' => $data['shelf_or_rack'] ?? null,
            ':level_or_section' => $data['level_or_section'] ?? null,
            ':specific_spot_or_box' => $data['specific_spot_or_box'] ?? null
        ];
        $stmt = $this->dbInstance->query($sql, $params);
        if ($stmt) {
            $updatedItem = $this->findById($id); // Pour loguer le nom mis à jour
            $logName = $updatedItem ? $updatedItem['full_location_path'] : 'ID ' . $id;
            Helper::logAction(strtoupper($this->tableName).'_UPDATE', ucfirst(str_replace('_','',$this->tableName)), $id, "Storage Location '{$logName}' updated.");
            return true;
        }
        return false;
    }

    public function delete(int $id): bool {
        $location = $this->findById($id);
        if (!$location) return false;
        // Vérifier si utilisé dans articles.current_storage_location_id
        // Pour l'instant, suppression directe. La FK devrait gérer (SET NULL ou RESTRICT)
        $sql = "DELETE FROM {$this->tableName} WHERE id = :id";
        $stmt = $this->dbInstance->query($sql, [':id' => $id]);
        if ($stmt && $stmt->rowCount() > 0) {
             $logName = $location['full_location_path'] ?? 'ID ' . $id;
            Helper::logAction(strtoupper($this->tableName).'_DELETE', ucfirst(str_replace('_','',$this->tableName)), $id, "Storage Location '{$logName}' deleted.");
            return true;
        }
        return false;
    }
}