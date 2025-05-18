<?php
// src/Models/StorageLocation.php

namespace App\Models;

use App\Core\Database;
use App\Utils\Helper;
// use PDO; // <-- Importer la classe PDO si vous utilisez beaucoup de constantes PDO dans un fichier

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

	public function fullPathExists(array $data, ?int $excludeId = null): bool {
		// Nettoyer et construire le chemin de test.
		// S'assurer que les champs vides deviennent NULL pour CONCAT_WS
		$room = trim($data['room'] ?? '');
		$area = trim($data['area'] ?? '');
		$shelf = trim($data['shelf_or_rack'] ?? '');
		$level = trim($data['level_or_section'] ?? '');
		$spot = trim($data['specific_spot_or_box'] ?? '');

		if (empty($room)) { // Room est requis, donc il ne devrait pas être vide si la validation de base est passée
			return false; 
		}

		// Simuler la logique de CONCAT_WS avec NULLIF pour la comparaison
		// Note: La logique exacte de NULLIF dans CONCAT_WS est que si le champ est une chaîne vide, il est omis.
		// Si le champ est NULL, il est omis.
		$pathParts = [];
		$pathParts[] = $room; // Room est toujours là
		if (!empty($area)) $pathParts[] = $area;
		if (!empty($shelf)) $pathParts[] = $shelf;
		if (!empty($level)) $pathParts[] = $level;
		if (!empty($spot)) $pathParts[] = $spot;
		
		$testPath = implode(' > ', $pathParts);

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

    public function getDistinctValuesForField(string $fieldName): array {
        $allowedFields = ['room', 'area', 'shelf_or_rack', 'level_or_section'];
        if (!in_array($fieldName, $allowedFields)) {
            return [];
        }

        $sql = "SELECT DISTINCT `{$fieldName}` FROM {$this->tableName} WHERE `{$fieldName}` IS NOT NULL AND `{$fieldName}` != '' ORDER BY `{$fieldName}` ASC";
        $stmt = $this->dbInstance->query($sql);
        // --- MODIFICATION ICI ---
        return $stmt ? $stmt->fetchAll(\PDO::FETCH_COLUMN) : []; // Utiliser \PDO::FETCH_COLUMN
	}
}