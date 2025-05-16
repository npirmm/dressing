<?php
// src/Utils/Helper.php

namespace App\Utils;

class Helper {
    /**
     * Escapes HTML special characters for output.
     * A shorthand for htmlspecialchars.
     *
     * @param string|null $string The string to escape.
     * @return string The escaped string.
     */
    public static function e(?string $string): string {
        return htmlspecialchars((string)$string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Generates a CSRF token input field.
     * @return string HTML for CSRF input.
     */
    public static function csrfInput(): string {
        if (empty($_SESSION[CSRF_TOKEN_NAME])) {
            $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
        }
        return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . $_SESSION[CSRF_TOKEN_NAME] . '">';
    }

    /**
     * Verifies CSRF token.
     * Call this at the beginning of POST/PUT/DELETE handling methods in controllers.
     * @return bool True if valid, false otherwise.
     */
    public static function verifyCsrfToken(): bool {
        if (!isset($_POST[CSRF_TOKEN_NAME]) || !isset($_SESSION[CSRF_TOKEN_NAME])) {
            return false;
        }
        if (!hash_equals($_SESSION[CSRF_TOKEN_NAME], $_POST[CSRF_TOKEN_NAME])) {
            return false;
        }
        // Optional: Regenerate token after successful verification for one-time use
        // unset($_SESSION[CSRF_TOKEN_NAME]);
        return true;
    }

    /**
     * Redirects to a given URL.
     *
     * @param string $url The URL to redirect to.
     * @param array $flashMessages Optional array of flash messages ['type' => 'message']
     */
    public static function redirect(string $url, array $flashMessages = []): void {
        if (!empty($flashMessages)) {
            $_SESSION['flash_messages'] = $flashMessages;
        }
        header('Location: ' . APP_URL . '/' . ltrim($url, '/'));
        exit;
    }

    /**
     * Gets and clears flash messages.
     * @return array Array of flash messages or empty array.
     */
    public static function getFlashMessages(): array {
        $messages = $_SESSION['flash_messages'] ?? [];
        unset($_SESSION['flash_messages']);
        return $messages;
    }

    /**
     * Logs an action to the action_logs table.
     *
     * @param string $actionType e.g., BRAND_CREATE, ARTICLE_UPDATE
     * @param string|null $entityType e.g., Brand, Article
     * @param int|null $entityId ID of the affected entity
     * @param string|null $description Human-readable description
     * @param array|null $details Additional JSON details (e.g., old/new values)
     * @param int|null $userId User performing the action. Defaults to SYSTEM_USER_ID if null.
     */
    public static function logAction(
        string $actionType,
        ?string $entityType = null,
        ?int $entityId = null,
        ?string $description = null,
        ?array $details = null,
        ?int $userId = null
    ): void {
        try {
            $db = \App\Core\Database::getInstance();
            $sql = "INSERT INTO action_logs (user_id, action_type, entity_type, entity_id, description, details, ip_address, user_agent)
                    VALUES (:user_id, :action_type, :entity_type, :entity_id, :description, :details, :ip_address, :user_agent)";

            // Attempt to get logged-in user ID if not provided. For now, this part is placeholder.
            // Later, when Auth system is in place, replace this.
            if ($userId === null) {
                // $userId = Auth::id() ?? SYSTEM_USER_ID; // Example for future Auth
                $userId = defined('SYSTEM_USER_ID') ? SYSTEM_USER_ID : null; // Fallback to system
            }


            $params = [
                ':user_id' => $userId,
                ':action_type' => $actionType,
                ':entity_type' => $entityType,
                ':entity_id' => $entityId,
                ':description' => $description,
                ':details' => $details ? json_encode($details) : null,
                ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            ];
            $db->query($sql, $params);
        } catch (\Exception $e) {
            error_log("Failed to log action: " . $e->getMessage());
            // Do not let logging failure break the main application flow
        }
    }
    public static function generateSortLink(string $column, string $displayName, string $currentSortColumn, string $currentSortOrder, string $baseUrl, string $idPrefix = ''): string {
        $nextOrder = 'asc';
        // Déterminer l'icône en fonction du type de données (approximatif)
        $sortIconType = 'alpha'; // défaut
        if (in_array($column, ['id', 'count', 'quantity', 'weight_grams', 'purchase_price'])) { // Ajoutez d'autres colonnes numériques
            $sortIconType = 'numeric';
        } elseif (in_array($column, ['created_at', 'updated_at', 'purchase_date', 'last_worn_at'])) { // Dates
            $sortIconType = 'down'; // Icône de date/temps générique
        }

        $iconClass = 'bi-arrow-down-up'; // Icône par défaut
        $isActiveSort = false;

        if ($column === $currentSortColumn) {
            $isActiveSort = true;
            if ($currentSortOrder === 'asc') {
                $iconClass = 'bi-sort-' . $sortIconType . '-down';
                $nextOrder = 'desc';
            } else {
                $iconClass = 'bi-sort-' . $sortIconType . '-up';
                $nextOrder = 'asc';
            }
        }
        $iconHtml = ' <span class="sort-icon-wrapper ' . ($isActiveSort ? 'active-sort-icon' : 'inactive-sort-icon') . '">';
        $iconHtml .= '<i class="bi ' . $iconClass . '"></i>';
        $iconHtml .= '</span>';
        $link = '<a href="' . $baseUrl . '?sort=' . $column . '&order=' . $nextOrder . '">';
        $link .= self::e($displayName); // Utiliser Helper::e()
        $link .= $iconHtml;
        $link .= '</a>';
        return $link;
    }
}