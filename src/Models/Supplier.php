<?php
// src/Models/Supplier.php

namespace App\Models;

use App\Core\Database;
use App\Utils\Helper;

class Supplier {
    private Database $dbInstance;
    private string $tableName = 'suppliers';

    public function __construct() {
        $this->dbInstance = Database::getInstance();
    }

    public function getAll(string $sortBy = 'name', string $sortOrder = 'ASC'): array {
        $allowedSortColumns = ['id', 'name', 'contact_person', 'email', 'phone', 'created_at'];
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

    public function emailExists(?string $email, ?int $excludeId = null): bool {
        if (empty($email)) { // Ne pas vérifier l'unicité des emails vides ou null
            return false;
        }
        $sql = "SELECT id FROM {$this->tableName} WHERE email = :email";
        $params = [':email' => $email];
        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
            $params[':exclude_id'] = $excludeId;
        }
        $stmt = $this->dbInstance->query($sql, $params);
        return $stmt && $stmt->fetch() !== false;
    }

    public function create(array $data): int|false {
        $sql = "INSERT INTO {$this->tableName} 
                (name, contact_person, email, phone, address, notes, created_at, updated_at) 
                VALUES (:name, :contact_person, :email, :phone, :address, :notes, NOW(), NOW())";
        $params = [
            ':name' => $data['name'],
            ':contact_person' => $data['contact_person'] ?? null,
            ':email' => $data['email'] ?? null,
            ':phone' => $data['phone'] ?? null,
            ':address' => $data['address'] ?? null,
            ':notes' => $data['notes'] ?? null
        ];
        $stmt = $this->dbInstance->query($sql, $params);
        if ($stmt) {
            $id = (int)$this->dbInstance->lastInsertId();
            Helper::logAction(strtoupper($this->tableName).'_CREATE', ucfirst($this->tableName), $id, "Supplier '{$data['name']}' created.");
            return $id;
        }
        return false;
    }

    public function update(int $id, array $data): bool {
        $sql = "UPDATE {$this->tableName} SET 
                name = :name, 
                contact_person = :contact_person,
                email = :email,
                phone = :phone,
                address = :address,
                notes = :notes,
                updated_at = NOW() 
                WHERE id = :id";
        $params = [
            ':id' => $id,
            ':name' => $data['name'],
            ':contact_person' => $data['contact_person'] ?? null,
            ':email' => $data['email'] ?? null,
            ':phone' => $data['phone'] ?? null,
            ':address' => $data['address'] ?? null,
            ':notes' => $data['notes'] ?? null
        ];
        $stmt = $this->dbInstance->query($sql, $params);
        if ($stmt) {
            Helper::logAction(strtoupper($this->tableName).'_UPDATE', ucfirst($this->tableName), $id, "Supplier '{$data['name']}' (ID: {$id}) updated.");
            return true;
        }
        return false;
    }

    public function delete(int $id): bool {
        $supplier = $this->findById($id);
        if (!$supplier) return false;
        // Avant de supprimer, vérifier si le fournisseur est utilisé dans la table articles (articles.supplier_id)
        // Si oui, la suppression sera bloquée par FK si ON DELETE RESTRICT, ou mettra NULL si ON DELETE SET NULL.
        // Il est préférable d'avertir l'utilisateur si le fournisseur est en cours d'utilisation.
        // Pour l'instant, suppression directe.
        $sql = "DELETE FROM {$this->tableName} WHERE id = :id";
        $stmt = $this->dbInstance->query($sql, [':id' => $id]);
        if ($stmt && $stmt->rowCount() > 0) {
            Helper::logAction(strtoupper($this->tableName).'_DELETE', ucfirst($this->tableName), $id, "Supplier '{$supplier['name']}' (ID: {$id}) deleted.");
            return true;
        }
        return false;
    }
}