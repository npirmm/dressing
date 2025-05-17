<?php
// src/Models/DayMoment.php

namespace App\Models;

use App\Core\Database;

class DayMoment {
    private Database $dbInstance;
    private string $tableName = 'day_moments';

    public function __construct() {
        $this->dbInstance = Database::getInstance();
    }

    /**
     * Fetches all day moments, ordered by sort_order.
     * @return array
     */
    public function getAllOrdered(): array {
        $sql = "SELECT * FROM {$this->tableName} ORDER BY sort_order ASC, name ASC";
        $stmt = $this->dbInstance->query($sql);
        return $stmt ? $stmt->fetchAll() : [];
    }

    /**
     * Fetches day moments by their IDs.
     * @param array $ids Array of day moment IDs.
     * @return array
     */
    public function findByIds(array $ids): array {
        if (empty($ids)) {
            return [];
        }
        // Crée des placeholders : ?,?,?
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT * FROM {$this->tableName} WHERE id IN ({$placeholders}) ORDER BY sort_order ASC, name ASC";
        
        // PDO attend que les types soient corrects pour les IN, ou on passe les paramètres 1 par 1
        // Pour la simplicité ici, si on utilise le query wrapper qui gère les types.
        // Si query() ne fait pas de binding intelligent pour les IN, il faudrait adapter
        $stmt = $this->dbInstance->query($sql, $ids);
        return $stmt ? $stmt->fetchAll() : [];
    }
}