<?php
// src/Models/CategoryType.php

namespace App\Models;

use App\Core\Database;
use App\Utils\Helper;

class CategoryType {
    private Database $dbInstance;
    private string $tableName = 'categories_types';

    public function __construct() {
        $this->dbInstance = Database::getInstance();
    }

	public function getAll(string $sortBy = 'name', string $sortOrder = 'ASC'): array {
		$allowedSortColumns = ['id', 'name', 'category', 'code', 'created_at'];
		if (!in_array(strtolower($sortBy), $allowedSortColumns)) {
			$sortBy = 'name';
		}
		$sortOrderSanitized = strtoupper($sortOrder);
		if ($sortOrderSanitized !== 'ASC' && $sortOrderSanitized !== 'DESC') {
			$sortOrderSanitized = 'ASC';
		}

		// Clause ORDER BY par défaut
		$orderByField = "`{$sortBy}`";

		// Si on trie par la colonne 'category', qui est un ENUM,
		// on caste en CHAR pour obtenir un tri alphabétique et non par index d'ENUM.
		if ($sortBy === 'category') {
			$orderByField = "CAST(`{$sortBy}` AS CHAR)";
		}

		$sql = "SELECT * FROM {$this->tableName} ORDER BY {$orderByField} {$sortOrderSanitized}";
		
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

    public function codeExists(string $code, ?int $excludeId = null): bool {
        $sql = "SELECT id FROM {$this->tableName} WHERE code = :code";
        $params = [':code' => $code];
        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
            $params[':exclude_id'] = $excludeId;
        }
        $stmt = $this->dbInstance->query($sql, $params);
        return $stmt && $stmt->fetch() !== false;
    }

    public function create(array $data): int|false {
        $sql = "INSERT INTO {$this->tableName} (name, category, code, created_at, updated_at) 
                VALUES (:name, :category, :code, NOW(), NOW())";
        $params = [
            ':name' => $data['name'],
            ':category' => $data['category'],
            ':code' => strtoupper($data['code']) // Mettre le code en majuscules
        ];
        $stmt = $this->dbInstance->query($sql, $params);
        if ($stmt) {
            $id = (int)$this->dbInstance->lastInsertId();
            Helper::logAction(strtoupper($this->tableName).'_CREATE', ucfirst($this->tableName), $id, "CategoryType '{$data['name']}' created.");
            return $id;
        }
        return false;
    }

    public function update(int $id, array $data): bool {
        $sql = "UPDATE {$this->tableName} SET 
                name = :name, 
                category = :category,
                code = :code,
                updated_at = NOW() 
                WHERE id = :id";
        $params = [
            ':id' => $id,
            ':name' => $data['name'],
            ':category' => $data['category'],
            ':code' => strtoupper($data['code'])
        ];
        $stmt = $this->dbInstance->query($sql, $params);
        if ($stmt) {
            Helper::logAction(strtoupper($this->tableName).'_UPDATE', ucfirst($this->tableName), $id, "CategoryType '{$data['name']}' (ID: {$id}) updated.");
            return true;
        }
        return false;
    }

    public function delete(int $id): bool {
        $item = $this->findById($id);
        if (!$item) return false;
        $sql = "DELETE FROM {$this->tableName} WHERE id = :id";
        $stmt = $this->dbInstance->query($sql, [':id' => $id]);
        if ($stmt && $stmt->rowCount() > 0) {
            Helper::logAction(strtoupper($this->tableName).'_DELETE', ucfirst($this->tableName), $id, "CategoryType '{$item['name']}' (ID: {$id}) deleted.");
            return true;
        }
        return false;
    }

    // Pour le formulaire, afin de peupler le select <category>
    public static function getAvailableCategories(): array {
        return ['vêtement', 'chaussures', 'bijou', 'accessoire'];
    }
}