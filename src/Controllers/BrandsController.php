<?php
// src/Controllers/BrandsController.php

namespace App\Controllers;

use App\Models\Brand;
use App\Utils\Helper;
use App\Utils\Validation;

class BrandsController extends BaseController {
    private Brand $brandModel;

    public function __construct() {
    //    parent::__construct();
        $this->brandModel = new Brand();
        // Basic auth check placeholder - to be expanded later
        // if (!Auth::check()) { // Assuming Auth::check() exists
        //     Helper::redirect('login');
        // }
    }

    /**
     * Displays a list of all brands.
     * Route: /brands or /brands/index
     */
    public function index(): void {
        $sortBy = $_GET['sort'] ?? 'name';
        $sortOrder = $_GET['order'] ?? 'asc';
        $brands = $this->brandModel->getAll($sortBy, $sortOrder);
        $this->renderView('brands/index', [
            'pageTitle' => 'Manage Brands',
            'brands' => $brands
        ]);
    }

    /**
     * Shows the form to create a new brand or edit an existing one.
     * Route: /brands/create (for new)
     * Route: /brands/edit/{id} (for editing) - Your router needs to support this
     * For this simple router, we'll use /brands/edit/ID
     */
    public function form(?int $id = null): void { // Or separate create() and edit($id) methods
        $brand = null;
        $pageTitle = 'Create New Brand';
        $formAction = APP_URL . '/brands/store';

        if ($id !== null) {
            $brand = $this->brandModel->findById($id);
            if (!$brand) {
                // Set a flash message: Brand not found
                Helper::redirect('brands', ['danger' => "Brand with ID {$id} not found."]);
                return;
            }
            $pageTitle = 'Edit Brand: ' . Helper::e($brand['name']);
            $formAction = APP_URL . '/brands/update/' . $id;
        }

        $this->renderView('brands/form', [
            'pageTitle' => $pageTitle,
            'brand' => $brand, // null if creating, array if editing
            'formAction' => $formAction,
            'csrfToken' => $_SESSION[CSRF_TOKEN_NAME] // Pass CSRF token to the view
        ]);
    }
    
    // Specific method for create form, mapped by router to /brands/create
    public function create(): void {
        $this->form(null);
    }

    // Specific method for edit form, mapped by router to /brands/edit/ID
    public function edit(int $id): void {
        $this->form($id);
    }


    /**
     * Stores a new brand in the database.
     * Route: POST /brands/store (or simply /brands if using HTTP verbs in router)
     */
    public function store(): void {
        $this->verifyCsrf();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $validator = new Validation($_POST, $this->brandModel); // Passez le modèle pour les règles 'unique'
            $validator->setRules([
                'name' => 'required|min:3|max:100|unique:brands,name', // unique:table,column
                'abbreviation' => 'max:20|unique:brands,abbreviation' // unique si non vide
            ], [
                'name.required' => 'The brand name is absolutely required!', // Custom message example
                'name.unique' => 'This brand name is already taken, sorry.'
            ]);

            if ($validator->validate()) {
                // $validatedData = $validator->validatedData(); // Get validated data (not strictly needed if using $_POST directly)
                // Ajustement pour abbreviation, pour passer null si vide
                $dataToCreate = [
                    'name' => $_POST['name'], // ou $validatedData['name']
                    'abbreviation' => !empty(trim($_POST['abbreviation'])) ? trim($_POST['abbreviation']) : null
                ];

                $brandId = $this->brandModel->create($dataToCreate);

                if ($brandId) {
                    Helper::redirect('brands', ['success' => 'Brand created successfully!']);
                } else {
                    // Erreur générale de la BDD, improbable si la validation est passée
                    $_SESSION['form_data'] = $_POST;
                    Helper::redirect('brands/create', ['danger' => 'Failed to create brand due to a database error.']);
                }
            } else {
                // Validation failed
                $_SESSION['form_data'] = $_POST; // Pour réafficher les données
                $_SESSION['form_errors'] = $validator->getErrors(); // Stocker les erreurs
                Helper::redirect('brands/create'); // Rediriger vers le formulaire
            }
        } else {
            Helper::redirect('brands/create');
        }
    }

    public function update(int $id): void {
        $this->verifyCsrf();

        $brand = $this->brandModel->findById($id);
        if (!$brand) {
            Helper::redirect('brands', ['danger' => "Brand with ID {$id} not found for update."]);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $validator = new Validation($_POST, $this->brandModel);
            $validator->setRules([
                // Pour unique en update, on exclut l'ID actuel: unique:table,column,exceptValue,idColumnName
                'name' => 'required|min:3|max:100|unique:brands,name,'.$id.',id',
                'abbreviation' => 'max:20|unique:brands,abbreviation,'.$id.',id'
            ]);

            if ($validator->validate()) {
                $dataToUpdate = [
                    'name' => $_POST['name'],
                    'abbreviation' => !empty(trim($_POST['abbreviation'])) ? trim($_POST['abbreviation']) : null
                ];

                // Vérifier si des changements ont réellement été faits
                if ($dataToUpdate['name'] === $brand['name'] && $dataToUpdate['abbreviation'] === $brand['abbreviation']) {
                    Helper::redirect('brands/edit/' . $id, ['info' => 'No changes were made to the brand.']);
                    return;
                }

                if ($this->brandModel->update($id, $dataToUpdate)) {
                    Helper::redirect('brands', ['success' => 'Brand updated successfully!']);
                } else {
                    $_SESSION['form_data'] = $_POST;
                    Helper::redirect('brands/edit/' . $id, ['danger' => 'Failed to update brand due to a database error.']);
                }
            } else {
                // Validation failed
                $_SESSION['form_data'] = $_POST;
                $_SESSION['form_errors'] = $validator->getErrors();
                Helper::redirect('brands/edit/' . $id);
            }
        } else {
            // GET request pour la page d'édition, on passe les données existantes
            // Cette partie n'est pas directement pour la soumission POST, mais pour l'affichage initial du form
            // Assurez-vous que le form.php charge bien les données $brand passées par la méthode edit()
             $this->renderView('brands/form', [
                'pageTitle' => 'Edit Brand: ' . Helper::e($brand['name']),
                'brand' => $brand,
                'formAction' => APP_URL . '/brands/update/' . $id,
                'csrfToken' => $_SESSION[CSRF_TOKEN_NAME]
            ]);
        }
    }

    /**
     * Deletes a brand.
     * Route: POST /brands/delete/{id} (use POST for actions with side-effects)
     * Or GET /brands/delete/{id} with a confirmation step
     */
    public function delete(int $id): void {
        // For GET requests, show a confirmation page. For POST, perform delete.
        // Simplified: directly delete but ensure CSRF for POST (if form method is POST)
        // A proper delete would be a POST request from a form.
        // If your router makes this accessible via GET, add a confirmation step.
        // For now, assuming it's called from a form with method POST (which needs CSRF)
        // If you are linking directly like <a href="/brands/delete/1">, it's a GET.
        // That GET should show a confirmation form that POSTs to this or another delete_confirm action.

        // Let's assume for now it's a POST request for deletion
        $this->verifyCsrf(); // If it's a POST request

        $brand = $this->brandModel->findById($id);
        if (!$brand) {
            Helper::redirect('brands', ['danger' => "Brand with ID {$id} not found for deletion."]);
            return;
        }
        
        if ($this->brandModel->delete($id)) {
            Helper::redirect('brands', ['success' => 'Brand "' . Helper::e($brand['name']) . '" deleted successfully!']);
        } else {
            // This might happen if, for example, FK constraints prevent deletion
            // and the model doesn't explicitly check for them.
            Helper::redirect('brands', ['danger' => 'Failed to delete brand "' . Helper::e($brand['name']) . '". It might be in use.']);
        }
    }

    /**
     * Shows details for a single brand. (Optional, if needed beyond the list)
     * Route: /brands/show/{id} or just /brands/{id}
     */
    public function show(int $id): void {
        $brand = $this->brandModel->findById($id);

	// DÉBOGAGE
    // var_dump($id);
    // var_dump($brand);
    // die('Debug in show method'); // Décommentez pour arrêter l'exécution ici et voir les var_dumps
	
	
        if (!$brand) {
            Helper::redirect('brands', ['danger' => "Brand with ID {$id} not found."]);
            return;
        }

        $this->renderView('brands/show', [
            'pageTitle' => 'Brand Details: ' . Helper::e($brand['name']),
            'brand' => $brand
        ]);
    }
}