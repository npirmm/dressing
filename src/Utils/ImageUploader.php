<?php
// src/Utils/ImageUploader.php

namespace App\Utils;

class ImageUploader {
    private string $targetDir;
    private array $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    private int $maxFileSize = 5 * 1024 * 1024; // 5 MB
    private ?string $uploadedFileName = null;
    private array $errors = [];
    private string $deletedPrefix = 'DELETED_'; // Préfixe pour les fichiers "supprimés"
	

    public function __construct(string $subDirectory = 'articles/') {
        // Chemin vers la racine du projet (où se trouvent src, html, config, etc.)
        $projectRoot = dirname(__DIR__, 2); // Remonte de src/Utils -> src -> racine_projet

        // Chemin de base pour les uploads DANS le dossier public
        $baseUploadPath = $projectRoot . '/html/assets/media/'; // 

        $this->targetDir = $baseUploadPath . rtrim($subDirectory, '/') . '/';

        if (!is_dir($this->targetDir) && !mkdir($this->targetDir, 0775, true) && !is_dir($this->targetDir)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $this->targetDir));
        }
        if (!is_writable($this->targetDir)) {
            error_log("ImageUploader Error: Target directory {$this->targetDir} is not writable.");
            $this->errors[] = "Image upload directory is not writable. Please contact an administrator.";
        }
    }
    public function getTargetDir(): string {
        return $this->targetDir;
    }
	
    public function setAllowedExtensions(array $extensions): self {
        $this->allowedExtensions = array_map('strtolower', $extensions);
        return $this;
    }

    public function setMaxFileSize(int $bytes): self {
        $this->maxFileSize = $bytes;
        return $this;
    }

    /**
     * Uploads a file from the $_FILES array.
     * @param array $fileData The file array from $_FILES (e.g., $_FILES['image']).
     * @param string|null $newFileNameWithoutExtension Optional: new name for the file (without extension).
     * @return bool True on success, false on failure.
     */
    public function upload(array $fileData, ?string $newFileNameWithoutExtension = null): bool {
        $this->errors = []; // Reset errors
        $this->uploadedFileName = null;

        if (!isset($fileData['error']) || is_array($fileData['error'])) {
            $this->errors[] = 'Invalid file upload parameters.';
            return false;
        }

        switch ($fileData['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                $this->errors[] = 'No file sent.'; // Not always an error, could be optional upload
                return true; // Or false if file is required
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $this->errors[] = 'Exceeded filesize limit.';
                return false;
            default:
                $this->errors[] = 'Unknown errors.';
                return false;
        }

        if ($fileData['size'] > $this->maxFileSize) {
            $this->errors[] = 'Exceeded filesize limit (' . ($this->maxFileSize / 1024 / 1024) . 'MB).';
            return false;
        }

       $fileExtension = strtolower(pathinfo($fileData['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExtension, $this->allowedExtensions)) {
            $this->errors[] = 'Invalid file type. Allowed types: ' . implode(', ', $this->allowedExtensions) . '.';
            return false;
        }

        if ($newFileNameWithoutExtension) {
            // Sanitize filename: allow alphanumeric, underscore, hyphen.
            // Replace other problematic characters with underscore.
            $safeFileName = preg_replace('/[^A-Za-z0-9\-_.]/', '_', $newFileNameWithoutExtension);
            // Remove multiple underscores
            $safeFileName = preg_replace('/_+/', '_', $safeFileName);
            $fileName = trim($safeFileName, '_') . '.' . $fileExtension; // Trim leading/trailing underscores
        } else {
            $fileName = uniqid('img_', true) . '.' . $fileExtension;
        }

        $targetFilePath = $this->targetDir . $fileName;

        if (move_uploaded_file($fileData['tmp_name'], $targetFilePath)) {
            $this->uploadedFileName = $fileName; // Store only the filename, not the full path
            return true;
        } else {
            $this->errors[] = 'Failed to move uploaded file. Check permissions and path.';
            error_log("ImageUploader Error: Failed to move {$fileData['tmp_name']} to {$targetFilePath}");
            return false;
        }
    }

    public function getUploadedFileName(): ?string {
        return $this->uploadedFileName;
    }

    public function getErrors(): array {
        return $this->errors;
    }

    /**
     * "Deletes" a file by renaming it with a DELETED_ prefix.
     * If a file with the DELETED_ prefix already exists, it might be overwritten or suffixed.
     * For simplicity, this version overwrites if DELETED_filename already exists.
     *
     * @param string|null $fileName The original name of the file to "delete".
     * @return bool True if renaming was successful or file didn't exist, false on failure.
     */
    public function deleteFile(?string $fileName): bool {
        if (empty($fileName)) {
            return true; // No file to "delete"
        }

        $originalFilePath = $this->targetDir . $fileName;
        $deletedFilePath = $this->targetDir . $this->deletedPrefix . $fileName;

        if (file_exists($originalFilePath)) {
            // Optionnel : Gérer le cas où $deletedFilePath existe déjà
            // Par exemple, en ajoutant un timestamp ou un compteur au nom du fichier DELETED_
            // if (file_exists($deletedFilePath)) {
            //     $deletedFilePath = $this->targetDir . $this->deletedPrefix . pathinfo($fileName, PATHINFO_FILENAME) . '_' . time() . '.' . pathinfo($fileName, PATHINFO_EXTENSION);
            // }

            if (rename($originalFilePath, $deletedFilePath)) {
                return true;
            } else {
                $this->errors[] = "Could not rename file for deletion: {$fileName}. Check permissions.";
                error_log("ImageUploader Error: Could not rename file {$originalFilePath} to {$deletedFilePath}");
                return false;
            }
        }
        return true; // File doesn't exist, so considered "deleted"
    }

    /**
     * Physically deletes a file. Use with caution.
     * Useful if you want to permanently remove a "DELETED_" file or any other file.
     * @param string|null $fileName
     * @return bool
     */
    public function permanentlyDeleteFile(?string $fileName): bool {
        if (empty($fileName)) {
            return true;
        }
        $filePath = $this->targetDir . $fileName;
        if (file_exists($filePath)) {
            if (unlink($filePath)) {
                return true;
            } else {
                $this->errors[] = "Could not permanently delete file: {$fileName}. Check permissions.";
                error_log("ImageUploader Error: Could not permanently delete file {$filePath}");
                return false;
            }
        }
        return true; // File doesn't exist
    }
}