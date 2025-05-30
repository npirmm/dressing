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

    /**
     * Validates a field against a regular expression.
     * Rule: regex:/your_pattern/modifiers
     * Example: 'zip_code' => 'regex:/^[0-9]{5}$/'
     * @param string $field The field name.
     * @param mixed $value The value of the field.
     * @param array $params Array containing the regex pattern as the first element.
     * @return bool True if validation passes, false otherwise.
     */
    protected function validateRegex(string $field, $value, array $params): bool {
        if (empty($value)) { // Don't validate empty values with regex unless 'required' is also used
            return true;
        }
        if (empty($params[0])) {
            error_log("Validation: Regex pattern not provided for field '{$field}'.");
            return false; // Or throw an exception
        }
        $pattern = $params[0];
        // Check if the pattern is correctly delimited (e.g. /pattern/i)
        // Basic check, could be more robust
        if ( ($pattern[0] !== $pattern[strlen($pattern)-1] && !preg_match('/^[a-zA-Z0-9]/', substr($pattern, -1))) || 
             ($pattern[0] === $pattern[strlen($pattern)-1] && strlen($pattern) < 2)
           ) {
            // Simple delimiter check, assuming common delimiters like / # ~ |
            // If not properly delimited, wrap it with / /
            // This is a simple fix; ideally, the pattern should always be passed correctly delimited.
             if (strpos($pattern, '/') === false && strpos($pattern, '#') === false && strpos($pattern, '~') === false) {
                 $pattern = '/' . $pattern . '/';
             }
        }


        if (!preg_match($pattern, (string)$value)) {
            $this->addError($field, "The {$field} format is invalid.");
            return false;
        }
        return true;
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
                } elseif ($this->modelInstance instanceof \App\Models\CategoryType) { // <-- AJOUTER
                    if ($columnName === 'name') {
                        $exists = $this->modelInstance->nameExists((string)$value, $exceptValue ? (int)$exceptValue : null);
                        $specificHandlerUsed = true;
                    } elseif ($columnName === 'code') {
                        $exists = $this->modelInstance->codeExists(strtoupper((string)$value), $exceptValue ? (int)$exceptValue : null); // Valider le code en majuscules
                        $specificHandlerUsed = true;
                    }
                } elseif ($this->modelInstance instanceof \App\Models\EventType) { // <-- AJOUTER
                    if ($columnName === 'name') {
                        $exists = $this->modelInstance->nameExists((string)$value, $exceptValue ? (int)$exceptValue : null);
                        $specificHandlerUsed = true;
                    }
                } elseif ($this->modelInstance instanceof \App\Models\ItemUser) { // <-- AJOUTER CE BLOC
                    if ($columnName === 'name') {
                        $exists = $this->modelInstance->nameExists((string)$value, $exceptValue ? (int)$exceptValue : null);
                        $specificHandlerUsed = true;
                    } elseif ($columnName === 'abbreviation') {
                        $exists = $this->modelInstance->abbreviationExists((string)$value, $exceptValue ? (int)$exceptValue : null);
                        $specificHandlerUsed = true;
                    }
                } elseif ($this->modelInstance instanceof \App\Models\Supplier) { // <-- AJOUTER CE BLOC
                    if ($columnName === 'name') {
                        $exists = $this->modelInstance->nameExists((string)$value, $exceptValue ? (int)$exceptValue : null);
                        $specificHandlerUsed = true;
                    } elseif ($columnName === 'email') {
                        $exists = $this->modelInstance->emailExists((string)$value, $exceptValue ? (int)$exceptValue : null);
                        $specificHandlerUsed = true;
                    }
                } elseif ($this->modelInstance instanceof \App\Models\StorageLocation) { // <-- AJOUTER
                    // La règle 'unique' ici serait sur 'full_location_path' qui n'est pas un champ direct du formulaire.
                    // On pourrait avoir une règle custom ex: 'full_path_unique' et une méthode validateFullPathUnique.
                    // Pour l'instant, on peut laisser le fallback SQL générique si on ajoute une contrainte unique sur full_location_path en BDD.
                    // Ou, si on utilise la méthode fullPathExists:
                    if ($columnName === 'full_location_path_check') { // Nom de "colonne" fictif pour la règle
                        // Les données des champs individuels sont dans $this->data
                        $locationData = [
                            'room' => $this->data['room'] ?? '',
                            'area' => $this->data['area'] ?? '',
                            'shelf_or_rack' => $this->data['shelf_or_rack'] ?? '',
                            'level_or_section' => $this->data['level_or_section'] ?? '',
                            'specific_spot_or_box' => $this->data['specific_spot_or_box'] ?? '',
                        ];
                        $exists = $this->modelInstance->fullPathExists($locationData, $exceptValue ? (int)$exceptValue : null);
                        $specificHandlerUsed = true;
                    }
                } elseif ($this->modelInstance instanceof \App\Models\Status) { // <-- AJOUTER CE BLOC
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

    protected function validateAlphaNumDash(string $field, $value, array $params): bool {
        if (!empty($value) && !preg_match('/^[a-zA-Z0-9_-]+$/', (string)$value)) {
            // Le message d'erreur est déjà géré par addError si un message custom est fourni.
            // Sinon, un message générique peut être défini ici, ou dans les messages custom par défaut.
            $this->addError($field, "The {$field} field may only contain letters, numbers, dashes, and underscores.");
            return false;
        }
        return true;
    }
	
	protected function validateIn(string $field, $value, array $params): bool {
        if (!empty($value) && !in_array((string)$value, $params)) {
            $this->addError($field, "The selected {$field} is invalid. Allowed values are: " . implode(', ', $params));
            return false;
        }
        return true;
    }

    protected function validateArray(string $field, $value, array $params): bool {
        // Si le champ n'est pas présent dans les données (ex: aucune checkbox cochée),
        // et qu'il n'est pas 'required', alors c'est valide.
        // Si 'required' est utilisé, il doit y avoir au moins un élément si c'est un tableau.
        // Cette règle vérifie juste que si $value est défini, c'est un tableau.
        if (isset($this->data[$field]) && !is_array($this->data[$field])) {
            $this->addError($field, "The {$field} field must be a collection of items.");
            return false;
        }
        return true;
    }


    protected function validateNumericOrEmpty(string $field, $value, array $params): bool {
        if (empty($value) && $value !== '0' && $value !== 0) { // Permettre '0'
            return true;
        }
        if (!is_numeric($value)) {
            $this->addError($field, "The {$field} field must be a number.");
            return false;
        }
        return true;
    }

    protected function validateMinNumeric(string $field, $value, array $params): bool {
        if (empty($value) && $value !== '0' && $value !== 0) return true; // Ne pas valider si vide
        $min = (float)($params[0] ?? 0);
        if (!is_numeric($value) || (float)$value < $min) {
            $this->addError($field, "The {$field} must be at least {$min}.");
            return false;
        }
        return true;
    }

    protected function validateMaxNumeric(string $field, $value, array $params): bool {
        if (empty($value) && $value !== '0' && $value !== 0) return true;
        $max = (float)($params[0] ?? PHP_INT_MAX);
        if (!is_numeric($value) || (float)$value > $max) {
            $this->addError($field, "The {$field} may not be greater than {$max}.");
            return false;
        }
        return true;
    }

    protected function validateDateOrEmpty(string $field, $value, array $params): bool {
        if (empty($value)) {
            return true;
        }
        // Tente de parser la date, accepte YYYY-MM-DD
        $d = \DateTime::createFromFormat('Y-m-d', $value);
        if ($d && $d->format('Y-m-d') === $value) {
            return true;
        }
        $this->addError($field, "The {$field} is not a valid date (YYYY-MM-DD).");
        return false;
    }

    protected function validateDecimalOrEmpty(string $field, $value, array $params): bool {
        if (empty($value) && (string)$value !== '0' && (string)$value !== '0.0' && (string)$value !== '0.00') { // Permettre 0, 0.0, 0.00
            return true;
        }
        $decimals = isset($params[0]) ? (int)$params[0] : 2; // Nombre de décimales par défaut
        // Regex pour un nombre décimal (positif ou négatif, point comme séparateur)
        // Permet un nombre optionnel de décimales jusqu'à $decimals
        $pattern = '/^-?\d+(\.\d{1,' . $decimals . '})?$/';
        if (!preg_match($pattern, (string)$value)) {
             $this->addError($field, "The {$field} must be a valid decimal number with up to {$decimals} decimal places.");
            return false;
        }
        return true;
    }

    protected function validateTimeOrEmpty(string $field, $value, array $params): bool {
        if (empty($value)) {
            return true;
        }
        // Accepte HH:MM ou HH:MM:SS
        if (!preg_match('/^([01]\d|2[0-3]):([0-5]\d)(:([0-5]\d))?$/', (string)$value)) {
            $this->addError($field, "The {$field} format is invalid (HH:MM or HH:MM:SS).");
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