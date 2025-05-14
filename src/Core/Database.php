<?php
// src/Core/Database.php

namespace App\Core;

use PDO;
use PDOException;
use PDOStatement;

/**
 * Database Connection Class (Singleton)
 *
 * Manages the PDO database connection.
 */
class Database {
    private static ?Database $instance = null;
    private PDO $connection;

    /**
     * Private constructor to prevent direct object creation.
     */
    private function __construct() {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Important for error handling
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Default fetch mode
            PDO::ATTR_EMULATE_PREPARES   => false,                  // Use native prepared statements
        ];

        try {
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // In a real app, log this error and show a generic message
            // For development, it's okay to die with the error
            error_log("Database Connection Error: " . $e->getMessage());
            if (defined('APP_DEBUG') && APP_DEBUG) {
                die("Database Connection Error: " . $e->getMessage());
            } else {
                die("A critical error occurred. Please try again later.");
            }
        }
    }

    /**
     * Gets the single instance of the Database class.
     *
     * @return Database The single Database instance.
     */
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    /**
     * Gets the PDO connection object.
     *
     * @return PDO The PDO connection object.
     */
    public function getConnection(): PDO {
        return $this->connection;
    }

    /**
     * Executes a prepared statement.
     *
     * @param string $sql The SQL query with placeholders.
     * @param array $params An array of parameters to bind.
     * @return PDOStatement|false The PDOStatement object, or false on failure.
     */
    public function query(string $sql, array $params = []): PDOStatement|false {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            // Log the error
            error_log("Database Query Error: " . $e->getMessage() . " | SQL: " . $sql . " | Params: " . json_encode($params));
             if (defined('APP_DEBUG') && APP_DEBUG) {
                // Optionally re-throw or handle more gracefully
                 throw $e; // Re-throw to be caught by a global error handler or controller
            }
            return false; // Or throw an exception
        }
    }

    /**
     * Get the ID of the last inserted row.
     *
     * @return string|false The ID of the last inserted row, or false if not applicable.
     */
    public function lastInsertId(): string|false {
        return $this->connection->lastInsertId();
    }

    // You can add more helper methods here like beginTransaction, commit, rollBack if needed.
}