<?php
// src/Controllers/MaterialsController.php

namespace App\Controllers;

use App\Models\Material; // Changer Brand en Material
use App\Utils\Helper;
use App\Utils\Validation;

class MaterialsController extends BaseController { // Changer BrandsController en MaterialsController
    private Material $materialModel; // Changer Brand en Material

    public function __construct() {
        // parent::__construct(); // Supprimer si BaseController n'a pas de constructeur
        $this->materialModel = new Material(); // Changer Brand en Material
    }

    public function index(): void {
        // Récupérer les paramètres de tri depuis $_GET
        $sortBy = $_GET['sort'] ?? 'name'; // Colonne par défaut
        $sortOrder = $_GET['order'] ?? 'asc'; // Ordre par défaut

        $materials = $this->materialModel->getAll($sortBy, $sortOrder);
        
        $this->renderView('materials/index', [
            'pageTitle' => 'Manage Materials',
            'materials' => $materials,
            // Pas besoin de passer sortBy et sortOrder ici car la vue les lit déjà depuis $_GET
        ]);
    }

    public function form(?int $id = null): void {
        $material = null;
        $pageTitle = 'Create New Material';
        $formAction = APP_URL . '/materials/store'; // Changer brands en materials

        if ($id !== null) {
            $material = $this->materialModel->findById($id);
            if (!$material) {
                Helper::redirect('materials', ['danger' => "Material with ID {$id} not found."]);
                return;
            }
            $pageTitle = 'Edit Material: ' . Helper::e($material['name']);
            $formAction = APP_URL . '/materials/update/' . $id;
        }

        $this->renderView('materials/form', [ // Changer brands/ en materials/
            'pageTitle' => $pageTitle,
            'material' => $material, // Changer brand en material
            'formAction' => $formAction,
            'csrfToken' => $_SESSION[CSRF_TOKEN_NAME]
        ]);
    }
    
    public function create(): void { $this->form(null); }
    public function edit(int $id): void { $this->form($id); }

    public function store(): void {
        $this->verifyCsrf();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Passer $this->materialModel au validateur
            $validator = new Validation($_POST, $this->materialModel); 
            $validator->setRules([
                'name' => 'required|min:2|max:100|unique:materials,name', // table 'materials'
            ]);

            if ($validator->validate()) {
                $dataToCreate = [
                    'name' => trim($_POST['name']),
                ];
                $materialId = $this->materialModel->create($dataToCreate);

                if ($materialId) {
                    Helper::redirect('materials', ['success' => 'Material created successfully!']);
                } else {
                    $_SESSION['form_data'] = $_POST;
                    Helper::redirect('materials/create', ['danger' => 'Failed to create material (database error).']);
                }
            } else {
                $_SESSION['form_data'] = $_POST;
                $_SESSION['form_errors'] = $validator->getErrors();
                Helper::redirect('materials/create');
            }
        } else {
            Helper::redirect('materials/create');
        }
    }

    public function update(int $id): void {
        $this->verifyCsrf();
        $material = $this->materialModel->findById($id);
        if (!$material) {
            Helper::redirect('materials', ['danger' => "Material with ID {$id} not found."]);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $validator = new Validation($_POST, $this->materialModel);
            $validator->setRules([
                'name' => 'required|min:2|max:100|unique:materials,name,'.$id.',id', // table 'materials'
            ]);

            if ($validator->validate()) {
                $dataToUpdate = [
                    'name' => trim($_POST['name']),
                ];
                
                if ($dataToUpdate['name'] === $material['name']) {
                    Helper::redirect('materials/edit/' . $id, ['info' => 'No changes were made to the material.']);
                    return;
                }

                if ($this->materialModel->update($id, $dataToUpdate)) {
                    Helper::redirect('materials', ['success' => 'Material updated successfully!']);
                } else {
                    $_SESSION['form_data'] = $_POST;
                    Helper::redirect('materials/edit/' . $id, ['danger' => 'Failed to update material (database error).']);
                }
            } else {
                $_SESSION['form_data'] = $_POST;
                $_SESSION['form_errors'] = $validator->getErrors();
                Helper::redirect('materials/edit/' . $id);
            }
        } else {
            // Pour la requête GET vers la page d'édition (pré-remplissage du formulaire)
            $this->form($id); // Appel direct de la méthode form qui gère le rendu
        }
    }

    public function delete(int $id): void {
        $this->verifyCsrf();
        $material = $this->materialModel->findById($id);
        if (!$material) {
             Helper::redirect('materials', ['danger' => "Material with ID {$id} not found."]);
             return;
        }
        if ($this->materialModel->delete($id)) {
            Helper::redirect('materials', ['success' => 'Material "' . Helper::e($material['name']) . '" deleted successfully!']);
        } else {
            Helper::redirect('materials', ['danger' => 'Failed to delete material. It might be in use.']);
        }
    }

    public function show(int $id): void {
        $material = $this->materialModel->findById($id);
        if (!$material) {
            Helper::redirect('materials', ['danger' => "Material with ID {$id} not found."]);
            return;
        }
        $this->renderView('materials/show', [ // Changer brands/ en materials/
            'pageTitle' => 'Material Details: ' . Helper::e($material['name']), // Changer Brand en Material
            'material' => $material // Changer brand en material
        ]);
    }
}