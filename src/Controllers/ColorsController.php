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
    private function generateImageFilename(?string $hexCode, string $colorName): string {
        $hexPart = 'NOHEX'; // Défaut si pas de code HEX
        if (!empty($hexCode)) {
            // Enlever le # et mettre en minuscules pour le nom de fichier
            $hexPart = strtolower(str_replace('#', '', trim($hexCode)));
        }

        $namePart = strtolower(trim($colorName));
        $namePart = preg_replace('/\s+/', '_', $namePart);
        $namePart = preg_replace('/[^a-z0-9_]/', '', $namePart);
        $namePart = trim($namePart, '_');
        if (empty($namePart)) {
            $namePart = 'color';
        }
        
        // Ajout du timestamp pour l'unicité
        return "HEX{$hexPart}_{$namePart}_" . time();
    }


    public function store(): void {
        $this->verifyCsrf();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $inputHexCode = trim($_POST['hex_code'] ?? '');
            $inputName = trim($_POST['name'] ?? '');

            $validator = new Validation($_POST, $this->colorModel);
            $rules = [
                'name' => 'required|max:50|unique:colors,name',
                // Regex: # optionnel, suivi de 6 chiffres hexa. La validation unique sera ajoutée si hex_code n'est pas vide.
                'hex_code' => 'regex:/^#?[a-fA-F0-9]{6}$/', 
                'base_color_category' => 'max:30',
            ];

            // Unicité du hex_code seulement s'il est fourni
            if (!empty($inputHexCode)) {
                $rules['hex_code'] .= '|unique:colors,hex_code';
            }

            $validator->setRules($rules, [
                'name.required' => 'The color name is required.',
                'name.unique' => 'This color name is already taken.',
                'hex_code.regex' => 'The Hex code must be 6 hexadecimal characters, optionally starting with # (e.g., #RRGGBB or RRGGBB).',
                'hex_code.unique' => 'This Hex code is already in use by another color.'
            ]);

            $newImageFilename = null;
            $uploadSuccess = true;
            $uploader = new ImageUploader($this->imageUploadPath);

            if (isset($_FILES['image_filename']) && $_FILES['image_filename']['error'] !== UPLOAD_ERR_NO_FILE) {
                // Le nom de la couleur est requis pour nommer l'image, donc on vérifie $inputName.
                // $inputHexCode est optionnel pour le nommage de l'image (utilisera NOHEX).
                if (!empty($inputName)) {
                    // On passe $inputHexCode (qui peut être vide) à generateImageFilename
                    $desiredFilenameWithoutExtension = $this->generateImageFilename($inputHexCode, $inputName);
                    
                    if ($uploader->upload($_FILES['image_filename'], $desiredFilenameWithoutExtension)) {
                        $newImageFilename = $uploader->getUploadedFileName();
                    } else {
                        $uploadSuccess = false;
                        foreach ($uploader->getErrors() as $error) {
                            $_SESSION['form_errors']['image_filename'][] = $error;
                        }
                    }
                } else {
                    $uploadSuccess = false;
                    if (empty($_SESSION['form_errors']['image_filename'])) {
                        $_SESSION['form_errors']['image_filename'][] = 'Color Name is required to name the image file.';
                    }
                     // Si $inputName est vide, la validation du champ 'name' (required) devrait aussi échouer.
                }
            }

            if ($validator->validate() && $uploadSuccess) {
                $finalHexCode = null;
                if (!empty($inputHexCode)) {
                    // S'assurer que le # est présent pour le stockage en BDD si un hex est fourni
                    $finalHexCode = (strpos($inputHexCode, '#') === 0 ? $inputHexCode : '#' . $inputHexCode);
                }

                $dataToCreate = [
                    'name' => $inputName,
                    'hex_code' => $finalHexCode,
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
                
                if ($newImageFilename && $uploadSuccess) {
                    $uploader->deleteFile($newImageFilename);
                }
                Helper::redirect('colors/create');
            }
        } else {
            Helper::redirect('colors/create');
        }
    }


    public function update(int $id): void {
        $this->verifyCsrf();
        $color = $this->colorModel->findById($id);
        if (!$color) {
            Helper::redirect('colors', ['danger' => "Color with ID {$id} not found."]);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $inputHexCode = trim($_POST['hex_code'] ?? '');
            $inputName = trim($_POST['name'] ?? '');

            $validator = new Validation($_POST, $this->colorModel);
            $rules = [
                'name' => 'required|max:50|unique:colors,name,'.$id.',id',
                'hex_code' => 'regex:/^#?[a-fA-F0-9]{6}$/', // 6 chiffres hexa, # optionnel
                'base_color_category' => 'max:30',
            ];
            if (!empty($inputHexCode)) {
                 $rules['hex_code'] .= '|unique:colors,hex_code,'.$id.',id';
            }
            $validator->setRules($rules, [ /* ... messages custom ... */
                'hex_code.regex' => 'If provided, Hex code must be 6 hexadecimal characters, optionally starting with #.',
             ]);


            $newImageFilename = $color['image_filename'];
            $oldImageToDeleteOnSuccess = null;
            $uploadSuccess = true;
            $uploader = new ImageUploader($this->imageUploadPath);

            if (isset($_FILES['image_filename']) && $_FILES['image_filename']['error'] !== UPLOAD_ERR_NO_FILE) {
                if (!empty($inputName)) { // Nom requis pour le nommage de l'image
                    $desiredFilenameWithoutExtension = $this->generateImageFilename($inputHexCode, $inputName);
                    
                    if ($uploader->upload($_FILES['image_filename'], $desiredFilenameWithoutExtension)) {
                        $uploadedFile = $uploader->getUploadedFileName();
                        if ($uploadedFile !== $color['image_filename']) {
                            $newImageFilename = $uploadedFile;
                            if (!empty($color['image_filename'])) {
                                $oldImageToDeleteOnSuccess = $color['image_filename'];
                            }
                        }
                    } else {
                        $uploadSuccess = false;
                        foreach ($uploader->getErrors() as $error) {
                            $_SESSION['form_errors']['image_filename'][] = $error;
                        }
                    }
                } else {
                    $uploadSuccess = false;
                    if (empty($_SESSION['form_errors']['image_filename'])) {
                        $_SESSION['form_errors']['image_filename'][] = 'Color Name is required to name the new image file.';
                    }
                }
            } elseif (isset($_POST['remove_image']) && $_POST['remove_image'] == '1' && !empty($color['image_filename'])) {
                $oldImageToDeleteOnSuccess = $color['image_filename'];
                $newImageFilename = null;
            }


            if ($validator->validate() && $uploadSuccess) {
                $finalHexCode = null;
                if (!empty($inputHexCode)) {
                    $finalHexCode = (strpos($inputHexCode, '#') === 0 ? $inputHexCode : '#' . $inputHexCode);
                }

                $dataToUpdate = [
                    'name' => $inputName,
                    'hex_code' => $finalHexCode,
                    'base_color_category' => !empty(trim($_POST['base_color_category'])) ? trim($_POST['base_color_category']) : null,
                    'image_filename' => $newImageFilename
                ];
                
                // Vérifier s'il y a réellement des changements
                $noChanges = ($dataToUpdate['name'] === $color['name'] &&
                             $dataToUpdate['hex_code'] === $color['hex_code'] && // $color['hex_code'] est déjà normalisé ou NULL
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
                    if ($newImageFilename !== $color['image_filename'] && $newImageFilename !== null) {
                        $uploader->deleteFile($newImageFilename);
                    }
                    Helper::redirect('colors/edit/' . $id, ['danger' => 'Failed to update color (database error).']);
                }
            } else {
                $_SESSION['form_data'] = $_POST;
                if (empty($_SESSION['form_errors'])) $_SESSION['form_errors'] = $validator->getErrors();
                else $_SESSION['form_errors'] = array_merge_recursive($_SESSION['form_errors'] ?? [], $validator->getErrors());
                
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