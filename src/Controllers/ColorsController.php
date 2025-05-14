<?php
// src/Controllers/ColorsController.php

namespace App\Controllers;

use App\Models\Color;
use App\Utils\Helper;
use App\Utils\Validation;
use App\Utils\ImageUploader; // Importer ImageUploader

class ColorsController extends BaseController {
    private Color $colorModel;
    private string $imageUploadPath; // From config constant

    public function __construct() {
        //parent::__construct();
        $this->colorModel = new Color();
        if (!defined('COLOR_IMAGE_PATH')) {
            // Fallback or error if not defined, though it should be
            define('COLOR_IMAGE_PATH', 'colors_fallback/');
            error_log("Config constant COLOR_IMAGE_PATH not defined.");
        }
        $this->imageUploadPath = COLOR_IMAGE_PATH; // e.g., 'colors/'
    }

    public function index(): void {
        $colors = $this->colorModel->getAll();
        $this->renderView('colors/index', [
            'pageTitle' => 'Manage Colors',
            'colors' => $colors,
            'imagePath' => APP_URL . '/assets/media/' . $this->imageUploadPath // For display
        ]);
    }

    public function form(?int $id = null): void {
        $color = null;
        $pageTitle = 'Create New Color';
        $formAction = APP_URL . '/colors/store';

        if ($id !== null) {
            $color = $this->colorModel->findById($id);
            if (!$color) {
                Helper::redirect('colors', ['danger' => "Color with ID {$id} not found."]);
                return;
            }
            $pageTitle = 'Edit Color: ' . Helper::e($color['name']);
            $formAction = APP_URL . '/colors/update/' . $id;
        }

        $this->renderView('colors/form', [
            'pageTitle' => $pageTitle,
            'color' => $color,
            'formAction' => $formAction,
            'imagePath' => APP_URL . '/assets/media/' . $this->imageUploadPath, // For displaying current image
            'csrfToken' => $_SESSION[CSRF_TOKEN_NAME]
        ]);
    }
    
    public function create(): void { $this->form(null); }
    public function edit(int $id): void { $this->form($id); }


    // --- Méthode utilitaire pour générer le nom de fichier ---
    private function generateImageFilename(string $hexCode, string $colorName): string {
        // 1. Préparer le code HEX: enlever le #, mettre en majuscules
        $hexPart = strtoupper(str_replace('#', '', trim($hexCode)));
        if (empty($hexPart)) {
            $hexPart = 'NOHEX'; // Fallback si le hex est vide, bien que vous ayez dit qu'il sera toujours fourni
        }

        // 2. Préparer le nom de la couleur: minuscules, remplacer espaces et caractères spéciaux par underscore
        $namePart = strtolower(trim($colorName));
        $namePart = preg_replace('/\s+/', '_', $namePart); // Remplace les espaces par _
        $namePart = preg_replace('/[^a-z0-9_]/', '', $namePart); // Garde seulement alphanumériques et underscore
        $namePart = trim($namePart, '_'); // Enlève les underscores en début/fin
        if (empty($namePart)) {
            $namePart = 'color'; // Fallback
        }
        
        return "HEX{$hexPart}_{$namePart}"; // L'extension sera ajoutée par ImageUploader
    }


    public function store(): void {
        $this->verifyCsrf();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $inputHexCode = trim($_POST['hex_code'] ?? '');
            $inputName = trim($_POST['name'] ?? '');

            $validator = new Validation($_POST, $this->colorModel);
            // ... (définition des rules, inchangée) ...
            $validator->setRules([
                'name' => 'required|max:50|unique:colors,name',
                'hex_code' => 'required|regex:/^#?([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/|unique:colors,hex_code',
                'base_color_category' => 'max:30',
            ], [ /* ... messages custom ... */ ]);

            $newImageFilename = null;
            $uploadSuccess = true;
            $uploader = new ImageUploader($this->imageUploadPath);

            if (isset($_FILES['image_filename']) && $_FILES['image_filename']['error'] !== UPLOAD_ERR_NO_FILE) {
                if (!empty($inputHexCode) && !empty($inputName) && preg_match('/^#?([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/', $inputHexCode) ) {
                    
                    $baseDesiredFilename = $this->generateImageFilename($inputHexCode, $inputName);
                    $fileExtension = strtolower(pathinfo($_FILES['image_filename']['name'], PATHINFO_EXTENSION));
                    
                    $finalFilenameWithoutExtension = $baseDesiredFilename;
                    $counter = 1;
                    // Boucle pour trouver un nom de fichier unique
                    while (file_exists($uploader->getTargetDir() . $finalFilenameWithoutExtension . '.' . $fileExtension)) {
                        $finalFilenameWithoutExtension = $baseDesiredFilename . '_' . $counter;
                        $counter++;
                    }
                    // Le nom de fichier final (sans extension) est $finalFilenameWithoutExtension

                    if ($uploader->upload($_FILES['image_filename'], $finalFilenameWithoutExtension)) { // Passe le nom sans extension
                        $newImageFilename = $uploader->getUploadedFileName(); // Récupère le nom complet avec extension
                    } else {
                        $uploadSuccess = false;
                        foreach ($uploader->getErrors() as $error) {
                            $_SESSION['form_errors']['image_filename'][] = $error;
                        }
                    }
                } else {
                    $uploadSuccess = false;
                    if (empty($_SESSION['form_errors']['image_filename'])) {
                        $_SESSION['form_errors']['image_filename'][] = 'Hex code and Name are required to name the image file correctly.';
                    }
                }
            }

            if ($validator->validate() && $uploadSuccess) {
                $dataToCreate = [
                    'name' => $inputName,
                    'hex_code' => (strpos($inputHexCode, '#') === 0 ? $inputHexCode : '#' . $inputHexCode), // Assurer le #
                    'base_color_category' => !empty(trim($_POST['base_color_category'])) ? trim($_POST['base_color_category']) : null,
                    'image_filename' => $newImageFilename
                ];

                $colorId = $this->colorModel->create($dataToCreate);

                if ($colorId) {
                    Helper::redirect('colors', ['success' => 'Color created successfully!']);
                } else {
                    $_SESSION['form_data'] = $_POST;
                    if ($newImageFilename) $uploader->deleteFile($newImageFilename);
                    Helper::redirect('colors/create', ['danger' => 'Failed to create color (database error).']);
                }
            } else {
                $_SESSION['form_data'] = $_POST;
                if (empty($_SESSION['form_errors'])) $_SESSION['form_errors'] = $validator->getErrors();
                else $_SESSION['form_errors'] = array_merge_recursive($_SESSION['form_errors'] ?? [], $validator->getErrors());
                
                if ($newImageFilename && $uploadSuccess) { // Si upload a réussi mais validation a échoué
                    $uploader->deleteFile($newImageFilename);
                } // Si upload a échoué ($uploadSuccess est false), $newImageFilename est null ou l'image n'a pas été déplacée.
                Helper::redirect('colors/create');
            }
        } else {
            Helper::redirect('colors/create');
        }
    }


    public function update(int $id): void {
        $this->verifyCsrf();
        $color = $this->colorModel->findById($id);
        // ... (vérification si couleur existe) ...

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $inputHexCode = trim($_POST['hex_code'] ?? '');
            $inputName = trim($_POST['name'] ?? '');

            $validator = new Validation($_POST, $this->colorModel);
            // ... (définition des rules, inchangée) ...
            $validator->setRules([ /* ... */ ]);


            $newImageFilename = $color['image_filename'];
            $oldImageToDeleteOnSuccess = null;
            $uploadSuccess = true;
            $uploader = new ImageUploader($this->imageUploadPath);

            if (isset($_FILES['image_filename']) && $_FILES['image_filename']['error'] !== UPLOAD_ERR_NO_FILE) {
                if (!empty($inputHexCode) && !empty($inputName) && preg_match('/^#?([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/', $inputHexCode) ) {
                    
                    $baseDesiredFilename = $this->generateImageFilename($inputHexCode, $inputName);
                    $fileExtension = strtolower(pathinfo($_FILES['image_filename']['name'], PATHINFO_EXTENSION));
                    
                    $finalFilenameWithoutExtension = $baseDesiredFilename;
                    $counter = 1;
                    // Boucle pour trouver un nom de fichier unique, en s'assurant de ne pas entrer en conflit
                    // avec l'image existante de cette couleur si elle n'est pas renommée.
                    // (Cette logique est plus complexe si le nom généré est le même que l'ancien)
                    // Simplification: si on upload une nouvelle image, on génère un nom potentiellement nouveau.
                    // Si le nom généré + extension existe et n'est pas l'image actuelle, on suffixe.

                    $currentFullImageName = $color['image_filename'];
                    
                    while (file_exists($uploader->getTargetDir() . $finalFilenameWithoutExtension . '.' . $fileExtension) &&
                           ($finalFilenameWithoutExtension . '.' . $fileExtension) !== $currentFullImageName) {
                        $finalFilenameWithoutExtension = $baseDesiredFilename . '_' . $counter;
                        $counter++;
                    }

                    if ($uploader->upload($_FILES['image_filename'], $finalFilenameWithoutExtension)) {
                        $uploadedFile = $uploader->getUploadedFileName();
                        // Si le nom du fichier uploadé (après potentielle suffixation) est différent de l'ancien nom stocké
                        if ($uploadedFile !== $color['image_filename']) {
                            $newImageFilename = $uploadedFile;
                            if (!empty($color['image_filename'])) {
                                $oldImageToDeleteOnSuccess = $color['image_filename'];
                            }
                        }
                        // Si le nom est le même, $newImageFilename reste l'ancien, $oldImageToDeleteOnSuccess reste null.
                        // Cela couvre le cas où on ré-uploade la même image avec le même nom.
                    } else {
                        $uploadSuccess = false;
                        foreach ($uploader->getErrors() as $error) {
                            $_SESSION['form_errors']['image_filename'][] = $error;
                        }
                    }
                } else {
                    $uploadSuccess = false;
                    if (empty($_SESSION['form_errors']['image_filename'])) {
                        $_SESSION['form_errors']['image_filename'][] = 'Hex code and Name are required to name the new image file correctly.';
                    }
                }
            } elseif (isset($_POST['remove_image']) && $_POST['remove_image'] == '1' && !empty($color['image_filename'])) {
                $oldImageToDeleteOnSuccess = $color['image_filename'];
                $newImageFilename = null;
            }


            if ($validator->validate() && $uploadSuccess) {
                $dataToUpdate = [
                    'name' => $inputName,
                    'hex_code' => (strpos($inputHexCode, '#') === 0 ? $inputHexCode : '#' . $inputHexCode),
                    'base_color_category' => !empty(trim($_POST['base_color_category'])) ? trim($_POST['base_color_category']) : null,
                    'image_filename' => $newImageFilename
                ];
                
                $noChanges = ($dataToUpdate['name'] === $color['name'] &&
                             $dataToUpdate['hex_code'] === $color['hex_code'] &&
                             $dataToUpdate['base_color_category'] === $color['base_color_category'] &&
                             $dataToUpdate['image_filename'] === $color['image_filename']);

                if ($noChanges) {
                    Helper::redirect('colors/edit/' . $id, ['info' => 'No changes were made to the color.']);
                    return;
                }

                if ($this->colorModel->update($id, $dataToUpdate, $oldImageToDeleteOnSuccess)) {
                    Helper::redirect('colors', ['success' => 'Color updated successfully!']);
                } else {
                    $_SESSION['form_data'] = $_POST;
                    // Si la BDD échoue, et qu'on avait uploadé une nouvelle image qui n'est pas l'ancienne
                    if ($newImageFilename !== $color['image_filename'] && $newImageFilename !== null) {
                        $uploader->deleteFile($newImageFilename);
                    }
                    Helper::redirect('colors/edit/' . $id, ['danger' => 'Failed to update color (database error).']);
                }
            } else {
                $_SESSION['form_data'] = $_POST;
                if (empty($_SESSION['form_errors'])) $_SESSION['form_errors'] = $validator->getErrors();
                else $_SESSION['form_errors'] = array_merge_recursive($_SESSION['form_errors'] ?? [], $validator->getErrors());
                
                // Si la validation échoue, et qu'on avait uploadé une nouvelle image qui n'est pas l'ancienne
                if ($newImageFilename !== $color['image_filename'] && $newImageFilename !== null && $uploadSuccess) {
                     $uploader->deleteFile($newImageFilename);
                }
                Helper::redirect('colors/edit/' . $id);
            }
        } else {
             $this->renderView('colors/form', [
                'pageTitle' => 'Edit Color: ' . Helper::e($color['name']),
                'color' => $color,
                'formAction' => APP_URL . '/colors/update/' . $id,
                'imagePath' => APP_URL . '/assets/media/' . $this->imageUploadPath,
                'csrfToken' => $_SESSION[CSRF_TOKEN_NAME]
            ]);
        }
    }

    public function delete(int $id): void {
        $this->verifyCsrf(); // Important for destructive actions via POST
        $color = $this->colorModel->findById($id); // Get details for logging and image deletion
        if (!$color) {
             Helper::redirect('colors', ['danger' => "Color with ID {$id} not found for deletion."]);
             return;
        }
        if ($this->colorModel->delete($id)) {
            Helper::redirect('colors', ['success' => 'Color "' . Helper::e($color['name']) . '" deleted successfully!']);
        } else {
            Helper::redirect('colors', ['danger' => 'Failed to delete color. It might be in use or a database error occurred.']);
        }
    }

    public function show(int $id): void {
        $color = $this->colorModel->findById($id);
        if (!$color) {
            Helper::redirect('colors', ['danger' => "Color with ID {$id} not found."]);
            return;
        }
        $this->renderView('colors/show', [
            'pageTitle' => 'Color Details: ' . Helper::e($color['name']),
            'color' => $color,
            'imagePath' => APP_URL . '/assets/media/' . $this->imageUploadPath
        ]);
    }
}