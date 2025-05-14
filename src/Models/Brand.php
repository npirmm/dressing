<?php
// src/Models/Brand.php

namespace App\Models;

use App\Core\Database; // Déjà là
use App\Utils\Helper;
// use PDO; // Peut-être plus nécessaire directement on utitlise plus que notre wrapper
// use PDOStatement; // Idem

class Brand {
    // private PDO $db; // Ancienne déclaration
    private Database $dbInstance; // Nouvelle déclaration: stocke notre wrapper

    public function __construct() {
        // $this->db = Database::getInstance()->getConnection(); // Ancienne initialisation
        $this->dbInstance = Database::getInstance(); // Nouvelle: obtenir l'instance de notre classe Database
    }

    public function getAll(): array {
        // Utiliser votre méthode query personnalisée
        $stmt = $this->dbInstance->query("SELECT * FROM brands ORDER BY name ASC");
        return $stmt ? $stmt->fetchAll() : []; // Ajouter une vérification si $stmt est false
    }

    public function findById(int $id): array|false {
        // Utiliser votre méthode query personnalisée
        $stmt = $this->dbInstance->query("SELECT * FROM brands WHERE id = :id", [':id' => $id]);
        return $stmt ? $stmt->fetch() : false;
    }
    /**
     * Checks if a brand with the given name already exists.
     * Optionally excludes a specific brand ID (useful for updates).
     * @param string $name The name to check.
     * @param int|null $excludeId The ID of the brand to exclude from the check.
     * @return bool True if a brand with that name exists (excluding excludeId), false otherwise.
     */
    public function nameExists(string $name, ?int $excludeId = null): bool { 
        $sql = "SELECT id FROM brands WHERE name = :name";
        $params = [':name' => $name];

        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
            $params[':exclude_id'] = $excludeId;
        }

        $stmt = $this->dbInstance->query($sql, $params);
        return $stmt && $stmt->fetch() !== false;
    }


    /**
     * Checks if a brand with the given abbreviation already exists (if abbreviation is not null or empty).
     * Optionally excludes a specific brand ID.
     * @param string|null $abbreviation The abbreviation to check.
     * @param int|null $excludeId The ID of the brand to exclude from the check.
     * @return bool True if a non-empty abbreviation exists (excluding excludeId), false otherwise.
     */
    public function abbreviationExists(?string $abbreviation, ?int $excludeId = null): bool {
        if (empty($abbreviation)) { // Don't check for uniqueness of empty or null abbreviations
            return false;
        }

        $sql = "SELECT id FROM brands WHERE abbreviation = :abbreviation";
        $params = [':abbreviation' => $abbreviation];

        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
            $params[':exclude_id'] = $excludeId;
        }

        $stmt = $this->dbInstance->query($sql, $params);
        return $stmt && $stmt->fetch() !== false;
    }

    public function create(string $name, ?string $abbreviation): int|false {
        // Check for duplicates BEFORE attempting to insert
        if ($this->nameExists($name)) {
            // This specific return value or mechanism can be improved.
            // For now, we can rely on the controller to set a flash message based on this.
            // Or throw a custom exception.
            return -1; // Or some other indicator of a duplicate, or throw an exception
        }
        if ($this->abbreviationExists($abbreviation)) {
            return -2; // Duplicate abbreviation (chiffre arbitraire pour le distinguer)
        }

        $sql = "INSERT INTO brands (name, abbreviation, created_at, updated_at) VALUES (:name, :abbreviation, NOW(), NOW())";
        $stmt = $this->dbInstance->query($sql, [
            ':name' => $name,
            ':abbreviation' => $abbreviation ?? null // Assurer que c'est NULL si vide
        ]);

        if ($stmt) {
            $id = (int)$this->dbInstance->lastInsertId();
            Helper::logAction('BRAND_CREATE', 'Brand', $id, "Brand '{$name}' created.");
            return $id;
        }
        return false; // General DB error
    }

    public function update(int $id, string $name, ?string $abbreviation): bool {
        // Check if the new name already exists for ANOTHER brand
        if ($this->nameExists($name, $id)) {
            // Similar to create, indicate duplicate
            // We need a way for the controller to know it's a duplicate error
            $_SESSION['validation_error_type'] = 'duplicate_name'; // Temporary mechanism
            return false;
        }
        if ($this->abbreviationExists($abbreviation, $id)) {
            $_SESSION['validation_error_type'] = 'duplicate_abbreviation';
            return false;
        }

        $sql = "UPDATE brands SET name = :name, abbreviation = :abbreviation, updated_at = NOW() WHERE id = :id";
        $stmt = $this->dbInstance->query($sql, [
            ':id' => $id,
            ':name' => $name,
            ':abbreviation' => $abbreviation ?? null // Assurer que c'est NULL si vide
        ]);


        if ($stmt) { // No need to check rowCount > 0 if we allow "update with same data"
             Helper::logAction('BRAND_UPDATE', 'Brand', $id, "Brand '{$name}' (ID: {$id}) updated.");
            return true;
        }
        return false;
    }

    public function delete(int $id): bool {
        $brand = $this->findById($id);
        if (!$brand) return false;

        $sql = "DELETE FROM brands WHERE id = :id";
        // Utiliser votre méthode query personnalisée
        $stmt = $this->dbInstance->query($sql, [':id' => $id]);

        if ($stmt && $stmt->rowCount() > 0) {
            Helper::logAction('BRAND_DELETE', 'Brand', $id, "Brand '{$brand['name']}' (ID: {$id}) deleted.");
            return true;
        }
        return false;
    }
}