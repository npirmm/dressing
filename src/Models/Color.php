<?php
// src/Models/Color.php

namespace App\Models;

use App\Core\Database;
use App\Utils\Helper;
use App\Utils\ImageUploader; // We might need it here for deleting old image on update if filename changes

class Color {
    private Database $dbInstance;
    private string $tableName = 'colors';
    private string $imagePathConstant = 'COLOR_IMAGE_PATH'; // To build full path for deletion

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
     * Checks if a color name exists, optionally excluding an ID.
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
     * Checks if a hex_code exists (if not null/empty), optionally excluding an ID.
     */
    public function hexCodeExists(?string $hexCode, ?int $excludeId = null): bool {
        if (empty($hexCode)) return false; // Don't check for unique empty/null hex codes
        $sql = "SELECT id FROM {$this->tableName} WHERE hex_code = :hex_code";
        $params = [':hex_code' => $hexCode];
        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
            $params[':exclude_id'] = $excludeId;
        }
        $stmt = $this->dbInstance->query($sql, $params);
        return $stmt && $stmt->fetch() !== false;
    }

    public function create(array $data): int|false {
        $sql = "INSERT INTO {$this->tableName} (name, hex_code, base_color_category, image_filename, created_at, updated_at) 
                VALUES (:name, :hex_code, :base_color_category, :image_filename, NOW(), NOW())";
        
        $params = [
            ':name' => $data['name'],
            ':hex_code' => empty($data['hex_code']) ? null : $data['hex_code'],
            ':base_color_category' => empty($data['base_color_category']) ? null : $data['base_color_category'],
            ':image_filename' => $data['image_filename'] ?? null
        ];
        
        $stmt = $this->dbInstance->query($sql, $params);

        if ($stmt) {
            $id = (int)$this->dbInstance->lastInsertId();
            Helper::logAction(strtoupper($this->tableName).'_CREATE', ucfirst($this->tableName), $id, "Color '{$data['name']}' created.");
            return $id;
        }
        return false;
    }

    public function update(int $id, array $data, ?string $oldImageFilename = null): bool {
        // $oldImageFilename est le nom de l'image qui était associée à la couleur AVANT cette mise à jour.
        // $data['image_filename'] est le nom de la NOUVELLE image (ou null si on la supprime).

        $sql = "UPDATE {$this->tableName} SET 
                name = :name, 
                hex_code = :hex_code, 
                base_color_category = :base_color_category, 
                image_filename = :image_filename,  
                updated_at = NOW() 
                WHERE id = :id";
        
        $params = [
            ':id' => $id,
            ':name' => $data['name'],
            ':hex_code' => empty($data['hex_code']) ? null : $data['hex_code'],
            ':base_color_category' => empty($data['base_color_category']) ? null : $data['base_color_category'],
            ':image_filename' => $data['image_filename'] ?? null // Correct: $data['image_filename'] sera NULL si 'remove_image' est cochée
        ];

        $stmt = $this->dbInstance->query($sql, $params);

        if ($stmt) {
            Helper::logAction(strtoupper($this->tableName).'_UPDATE', ucfirst($this->tableName), $id, "Color '{$data['name']}' (ID: {$id}) updated.");

            if (!empty($oldImageFilename) && 
                ( (isset($data['image_filename']) && $data['image_filename'] !== $oldImageFilename) || !isset($data['image_filename']) || is_null($data['image_filename']) )
            ) {
                // Assurez-vous que COLOR_IMAGE_PATH est bien défini et accessible
                if (defined('COLOR_IMAGE_PATH')) {
                    $uploader = new ImageUploader(COLOR_IMAGE_PATH);
                    $uploader->deleteFile($oldImageFilename);
                } else {
                    error_log("COLOR_IMAGE_PATH constant not defined in ColorModel::update()");
                }
            }
            return true;
        }
        return false;
    }

    public function delete(int $id): bool {
        $color = $this->findById($id);
        if (!$color) return false;

        $sql = "DELETE FROM {$this->tableName} WHERE id = :id";
        $stmt = $this->dbInstance->query($sql, [':id' => $id]);

        if ($stmt && $stmt->rowCount() > 0) {
            Helper::logAction(strtoupper($this->tableName).'_DELETE', ucfirst($this->tableName), $id, "Color '{$color['name']}' (ID: {$id}) deleted.");
            // Delete associated image file
            if (!empty($color['image_filename'])) {
                $uploader = new ImageUploader(constant($this->imagePathConstant));
                $uploader->deleteFile($color['image_filename']);
            }
            return true;
        }
        return false;
    }
}