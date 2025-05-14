<?php
// src/Utils/Validation.php

namespace App\Utils;

use App\Core\Database;

/**
 * Simple Validation Class
 */
class Validation {
    private array $data = [];
    private array $rules = [];
    private array $errors = [];
    private array $customMessages = [];
    private $modelInstance = null; // For unique checks etc.

    /**
     * Constructor.
     * @param array $data The data to validate (e.g., $_POST).
     * @param object|null $modelInstance Optional model instance for database checks (e.g., unique).
     */
    public function __construct(array $data, ?object $modelInstance = null) {
        $this->data = $data;
        $this->modelInstance = $modelInstance;
    }

    /**
     * Sets the validation rules.
     * Example: ['name' => 'required|min:3|unique:brands,name,except,id']
     * @param array $rules An array of rules.
     * @param array $customMessages Custom error messages for specific rules/fields.
     */
    public function setRules(array $rules, array $customMessages = []): void {
        $this->rules = $rules;
        $this->customMessages = $customMessages;
    }

    /**
     * Runs the validation.
     * @return bool True if validation passes, false otherwise.
     */
    public function validate(): bool {
        $this->errors = []; // Reset errors

        foreach ($this->rules as $field => $fieldRules) {
            $value = $this->data[$field] ?? null;
            $rulesArray = explode('|', $fieldRules);

            foreach ($rulesArray as $rule) {
                $params = [];
                if (strpos($rule, ':') !== false) {
                    list($rule, $paramString) = explode(':', $rule, 2);
                    $params = explode(',', $paramString);
                }

                $methodName = 'validate' . ucfirst($rule);
                if (method_exists($this, $methodName)) {
                    // Pass field name, value, and rule parameters to the validation method
                    if (!$this->$methodName($field, $value, $params)) {
                        // If a rule for this field fails, no need to check other rules for the same field
                        break;
                    }
                } else {
                    // Log or throw an exception for an unknown validation rule
                    error_log("Validation: Unknown rule '{$rule}' for field '{$field}'.");
                }
            }
        }
        return empty($this->errors);
    }

    /**
     * Checks if validation passed.
     * @return bool True if no errors, false otherwise.
     */
    public function passes(): bool {
        return empty($this->errors);
    }

    /**
     * Checks if validation failed.
     * @return bool True if there are errors, false otherwise.
     */
    public function fails(): bool {
        return !$this->passes();
    }

    /**
     * Gets all error messages.
     * @return array An array of error messages, keyed by field name.
     */
    public function getErrors(): array {
        return $this->errors;
    }

    /**
     * Gets the error message for a specific field.
     * @param string $field The field name.
     * @return string|null The error message, or null if no error for this field.
     */
    public function getError(string $field): ?string {
        return $this->errors[$field][0] ?? null; // Return the first error for the field
    }

    /**
     * Gets the validated data. Can be useful if you want to get only fields that had rules.
     * Or if data was sanitized/transformed during validation (not implemented here).
     * @return array The original data passed to the constructor.
     */
    public function validatedData(): array {
        // For now, just returns all original data.
        // Could be enhanced to return only fields that were in rules and passed.
        $validated = [];
        foreach (array_keys($this->rules) as $field) {
            if (isset($this->data[$field]) && !isset($this->errors[$field])) {
                $validated[$field] = $this->data[$field];
            } elseif (!isset($this->errors[$field])) { // Handle cases where field might not be in data but has rules (e.g. optional file)
                 $validated[$field] = null;
            }
        }
        return $validated; // Or return $this->data directly if no filtering needed
    }


    /**
     * Adds an error message for a field.
     * @param string $field The field name.
     * @param string $message The error message.
     */
    protected function addError(string $field, string $message): void {
        // Check for custom message first
        $customKey = $field . '.' . substr($message, 0, strpos($message, ' ')); // e.g., name.required
        if(isset($this->customMessages[$customKey])) {
            $this->errors[$field][] = $this->customMessages[$customKey];
        } elseif (isset($this->customMessages[$field])) { // General custom message for the field
             $this->errors[$field][] = $this->customMessages[$field];
        }
         else {
            $this->errors[$field][] = $message;
        }
    }

    // --- Validation Rule Methods ---

    protected function validateRequired(string $field, $value, array $params): bool {
        if (is_null($value) || (is_string($value) && trim($value) === '') || (is_array($value) && empty($value))) {
            $this->addError($field, "The {$field} field is required.");
            return false;
        }
        return true;
    }

    protected function validateMin(string $field, $value, array $params): bool {
        $minLength = (int)($params[0] ?? 0);
        if (is_string($value) && mb_strlen(trim($value)) < $minLength) {
            $this->addError($field, "The {$field} field must be at least {$minLength} characters.");
            return false;
        }
        return true;
    }

    protected function validateMax(string $field, $value, array $params): bool {
        $maxLength = (int)($params[0] ?? 255);
        if (is_string($value) && mb_strlen(trim($value)) > $maxLength) {
            $this->addError($field, "The {$field} field may not be greater than {$maxLength} characters.");
            return false;
        }
        return true;
    }

    protected function validateEmail(string $field, $value, array $params): bool {
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, "The {$field} field must be a valid email address.");
            return false;
        }
        return true;
    }

    /**
     * Validates uniqueness in a database table.
     * Rule: unique:tableName,columnName[,exceptValue,exceptColumnIdName]
     * Example: 'email' => 'unique:users,email'
     * Example: 'email' => 'unique:users,email,10,id' (for update, excluding user with id 10)
     */
    protected function validateUnique(string $field, $value, array $params): bool {
        if (empty($value)) { // Ne pas valider l'unicité pour les valeurs vides
            return true;
        }

        $tableName = $params[0] ?? null;
        $columnName = $params[1] ?? $field;
        $exceptValue = $params[2] ?? null;
        $exceptColumnIdName = $params[3] ?? 'id';

        if (!$tableName) {
            error_log("Validation: 'unique' rule for field '{$field}' is missing table name parameter.");
            return false;
        }

            $exists = false;
            $specificHandlerUsed = false;

            if ($this->modelInstance) {
                if ($this->modelInstance instanceof \App\Models\Brand) {
                    if ($columnName === 'name') {
                        $exists = $this->modelInstance->nameExists((string)$value, $exceptValue ? (int)$exceptValue : null);
                        $specificHandlerUsed = true;
                    } elseif ($columnName === 'abbreviation') {
                        $exists = $this->modelInstance->abbreviationExists((string)$value, $exceptValue ? (int)$exceptValue : null);
                        $specificHandlerUsed = true;
                    }
                } elseif ($this->modelInstance instanceof \App\Models\Color) {
                    if ($columnName === 'name') {
                        $exists = $this->modelInstance->nameExists((string)$value, $exceptValue ? (int)$exceptValue : null);
                        $specificHandlerUsed = true;
                    } elseif ($columnName === 'hex_code') {
                        $exists = $this->modelInstance->hexCodeExists((string)$value, $exceptValue ? (int)$exceptValue : null);
                        $specificHandlerUsed = true;
                    }
                } elseif ($this->modelInstance instanceof \App\Models\Material) { // <-- NOUVEAU CAS
                    if ($columnName === 'name') {
                        $exists = $this->modelInstance->nameExists((string)$value, $exceptValue ? (int)$exceptValue : null);
                        $specificHandlerUsed = true;
                    }
                }
                // Ajoutez d'autres 'elseif' pour d'autres modèles ici...
            }
            
            // Si aucun handler spécifique n'a été utilisé (ou pas de modelInstance), utiliser le fallback générique.
            if (!$specificHandlerUsed) {
                 $db = Database::getInstance();
                 $sql = "SELECT COUNT(*) as count FROM `{$tableName}` WHERE `{$columnName}` = :value";
                 $queryParams = [':value' => $value];
                 if ($exceptValue !== null) {
                     $sql .= " AND `{$exceptColumnIdName}` != :exceptValue";
                     $queryParams[':exceptValue'] = $exceptValue;
                 }
                 $stmt = $db->query($sql, $queryParams);
                 $result = $stmt ? $stmt->fetch() : null;
                 $exists = $result && $result['count'] > 0;
            }

            if ($exists) {
                $this->addError($field, "The {$field} '{$value}' has already been taken.");
                return false;
            }
            return true;
    }

    // Add more validation methods as needed (numeric, alpha, date, etc.)
}

/* Explication de la classe Validation :

__construct(array $data, ?object $modelInstance = null): Prend les données à valider (ex: $_POST) et optionnellement une instance de modèle (utile pour les règles unique).

setRules(array $rules, array $customMessages = []): Définit les règles. Le format des règles est une chaîne (ex: 'required|min:3').

validate(): Parcourt les règles, appelle les méthodes validateRuleName() correspondantes.

passes(), fails(), getErrors(), getError(): Méthodes utilitaires pour vérifier le résultat.

addError(): Ajoute un message d'erreur. Prend en compte les messages personnalisés.

validateRequired(), validateMin(), validateMax(), validateEmail(), validateUnique(): Exemples de méthodes de validation. Vous en ajouterez d'autres au besoin.

La méthode validateUnique est un peu plus complexe. Elle tente d'utiliser des méthodes spécifiques du modèle (comme nameExists dans Brand) si le modèle est du type attendu. Sinon, elle a un fallback générique qui exécute une requête SQL directe. C'est une partie à affiner. */