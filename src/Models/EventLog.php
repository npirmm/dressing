<?php
// src/Models/EventLog.php

namespace App\Models;

use App\Core\Database;
use App\Utils\Helper; // Pour le logging de l'action de création du log lui-même si besoin
use PDO;

class EventLog {
    private Database $dbInstance;
    private string $tableName = 'event_log';

    public function __construct() {
        $this->dbInstance = Database::getInstance();
    }

    /**
     * Crée une nouvelle entrée dans le journal des événements.
     * @param array $data Données à insérer. Doit inclure au minimum article_id, log_date, status_id.
     * @return int|false L'ID de la nouvelle entrée ou false en cas d'échec.
     */
    public function create(array $data): int|false {
        // Assurer que les champs requis sont présents (une validation plus poussée se fait dans le contrôleur)
        if (empty($data['article_id']) || empty($data['log_date']) || empty($data['status_id'])) {
            error_log("EventLog::create - Missing required fields.");
            return false;
        }

        $sql = "INSERT INTO {$this->tableName} (
                    article_id, log_date, log_time, status_id, 
                    event_type_id, event_name, description, item_user_id, 
                    related_supplier_id, cost_associated, currency, created_by_app_user_id
                    -- created_at_log_entry a une valeur par défaut CURRENT_TIMESTAMP
                ) VALUES (
                    :article_id, :log_date, :log_time, :status_id, 
                    :event_type_id, :event_name, :description, :item_user_id, 
                    :related_supplier_id, :cost_associated, :currency, :created_by_app_user_id
                )";
        
        $params = [
            ':article_id' => (int)$data['article_id'],
            ':log_date' => $data['log_date'], // Format YYYY-MM-DD
            ':log_time' => !empty($data['log_time']) ? $data['log_time'] : null, // Format HH:MM:SS ou HH:MM
            ':status_id' => (int)$data['status_id'],
            ':event_type_id' => isset($data['event_type_id']) && !empty($data['event_type_id']) ? (int)$data['event_type_id'] : null,
            ':event_name' => isset($data['event_name']) && !empty($data['event_name']) ? trim($data['event_name']) : null,
            ':description' => isset($data['description']) && !empty($data['description']) ? trim($data['description']) : null,
            ':item_user_id' => isset($data['item_user_id']) && !empty($data['item_user_id']) ? (int)$data['item_user_id'] : null,
            ':related_supplier_id' => isset($data['related_supplier_id']) && !empty($data['related_supplier_id']) ? (int)$data['related_supplier_id'] : null,
            ':cost_associated' => isset($data['cost_associated']) && is_numeric($data['cost_associated']) ? (float)$data['cost_associated'] : null,
            ':currency' => $data['currency'] ?? 'EUR',
            ':created_by_app_user_id' => $data['created_by_app_user_id'] ?? (defined('SYSTEM_USER_ID') ? SYSTEM_USER_ID : null)
        ];

        $stmt = $this->dbInstance->query($sql, $params);
        if ($stmt) {
            $id = (int)$this->dbInstance->lastInsertId();
            // Pas de log d'action ici, car c'est déjà une action de log.
            return $id;
        }
        error_log("EventLog::create - DB query failed. SQL: $sql, Params: " . json_encode($params) . " Error: " . json_encode($this->dbInstance->getConnection()->errorInfo()));
        return false;
    }

    /**
     * Récupère tous les événements pour un article donné, triés par date/heure (plus récent en premier).
     * @param int $articleId
     * @return array
     */
    public function getForArticle(int $articleId): array {
        $sql = "SELECT 
                    el.*,
                    s.name as status_name,
                    et.name as event_type_name,
                    iu.name as item_user_name,
                    sup.name as supplier_name,
                    app_u.username as app_user_username -- Utilisateur de l'application qui a logué
                FROM {$this->tableName} el
                JOIN statuses s ON el.status_id = s.id
                LEFT JOIN event_types et ON el.event_type_id = et.id
                LEFT JOIN item_users iu ON el.item_user_id = iu.id
                LEFT JOIN suppliers sup ON el.related_supplier_id = sup.id
                LEFT JOIN users app_u ON el.created_by_app_user_id = app_u.id -- Jointure sur users
                WHERE el.article_id = :article_id
                ORDER BY el.log_date DESC, el.log_time DESC, el.id DESC"; // Tri robuste
        
        $stmt = $this->dbInstance->query($sql, [':article_id' => $articleId]);
        return $stmt ? $stmt->fetchAll() : [];
    }

    // Vous pourriez ajouter des méthodes findById, update, delete pour event_log si nécessaire,
    // mais souvent les entrées de log sont immuables une fois créées.
    // Une méthode pour récupérer les images d'un event_log_id pourrait être utile.
    public function getImagesForEvent(int $eventLogId): array {
        $sql = "SELECT * FROM event_log_images WHERE event_log_id = :event_log_id ORDER BY id ASC";
        $stmt = $this->dbInstance->query($sql, [':event_log_id' => $eventLogId]);
        return $stmt ? $stmt->fetchAll() : [];
    }

    public function addImage(int $eventLogId, string $imagePath, ?string $caption = null): bool {
        $sql = "INSERT INTO event_log_images (event_log_id, image_path, caption) 
                VALUES (:event_log_id, :image_path, :caption)";
        $stmt = $this->dbInstance->query($sql, [
            ':event_log_id' => $eventLogId,
            ':image_path' => $imagePath,
            ':caption' => $caption,
        ]);
        return $stmt !== false;
    }
    // Méthodes pour gérer grouped_event_articles et event_log_grouped_event_link si nécessaire
    // (Peuvent être dans des modèles séparés ou ici pour la simplicité au début)

    public function createGroupedEvent(array $data): int|false {
        $sql = "INSERT INTO grouped_event_articles (group_event_name, group_event_date, group_event_time, notes)
                VALUES (:name, :date, :time, :notes)"; // created_at_group a une valeur par défaut
        $params = [
            ':name' => $data['group_event_name'] ?? null,
            ':date' => $data['group_event_date'], // Requis
            ':time' => $data['group_event_time'] ?? null,
            ':notes' => $data['notes'] ?? null,
        ];
        $stmt = $this->dbInstance->query($sql, $params);
        if ($stmt) {
            $id = (int)$this->dbInstance->lastInsertId();
            // Log d'action de l'application si nécessaire pour la création d'un groupe
            Helper::logAction('GROUPED_EVENT_CREATE', 'GroupedEventArticle', $id, "Grouped event '{$data['group_event_name']}' created.");
            return $id;
        }
        error_log("EventLog::createGroupedEvent - DB query failed. Error: " . json_encode($this->dbInstance->getConnection()->errorInfo()));
        return false;
    }

    public function linkEventToGroup(int $eventLogId, int $groupedEventId): bool {
        $sql = "INSERT INTO event_log_grouped_event_link (event_log_id, grouped_event_id)
                VALUES (:event_log_id, :grouped_event_id)";
        $stmt = $this->dbInstance->query($sql, [
            ':event_log_id' => $eventLogId,
            ':grouped_event_id' => $groupedEventId
        ]);
        return $stmt !== false;
    }
}