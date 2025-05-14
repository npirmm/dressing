<?php
// src/Controllers/BrandsController.php

namespace App\Controllers;

use App\Models\Brand;
use App\Utils\Helper;

class BrandsController extends BaseController {
    private Brand $brandModel;

    public function __construct() {
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
        $brands = $this->brandModel->getAll();
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
        $name = trim($_POST['name'] ?? '');
        $abbreviationInput = trim($_POST['abbreviation'] ?? '');
        // Conserver NULL si l'input est vide, sinon la chaîne.
        // Cela dépend si votre DB et votre logique abbreviationExists() attendent NULL ou '' pour "pas d'abréviation".
        // Si abbreviationExists() gère bien les chaînes vides, alors:
        $abbreviation = !empty($abbreviationInput) ? $abbreviationInput : null;


        if (empty($name)) {
            $_SESSION['form_data'] = $_POST;
            Helper::redirect('brands/create', ['danger' => 'Brand name cannot be empty.']);
            return;
        }

        $brandIdOrError = $this->brandModel->create($name, $abbreviation);

        if ($brandIdOrError === -1) { // Duplicate name
            $_SESSION['form_data'] = $_POST;
            Helper::redirect('brands/create', ['danger' => "The brand '{$name}' already exists."]);
        } elseif ($brandIdOrError === -2) { // Duplicate abbreviation
            $_SESSION['form_data'] = $_POST;
            Helper::redirect('brands/create', ['danger' => "The abbreviation '{$abbreviation}' is already in use."]);
        } elseif ($brandIdOrError && $brandIdOrError > 0) { // Success
            unset($_SESSION['form_data']);
            Helper::redirect('brands', ['success' => 'Brand created successfully!']);
        } else { // General failure
            $_SESSION['form_data'] = $_POST;
            Helper::redirect('brands/create', ['danger' => 'Failed to create brand. Please try again.']);
        }
    } else {
        Helper::redirect('brands/create'); // Or show error
        }
    }

    /**
     * Updates an existing brand in the database.
     * Route: POST /brands/update/{id}
     */
    public function update(int $id): void {
        $this->verifyCsrf(); // Check CSRF token

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim($_POST['name'] ?? '');
        $abbreviationInput = trim($_POST['abbreviation'] ?? '');
        $abbreviation = !empty($abbreviationInput) ? $abbreviationInput : null;
        
        $brand = $this->brandModel->findById($id);
        if (!$brand) {
            Helper::redirect('brands', ['danger' => "Brand with ID {$id} not found for update."]);
            return;
        }

        if (empty($name)) {
            $_SESSION['form_data'] = $_POST;
            Helper::redirect('brands/edit/' . $id, ['danger' => 'Brand name cannot be empty.']);
            return;
        }

        $success = $this->brandModel->update($id, $name, $abbreviation);

        if ($success) {
            unset($_SESSION['form_data']);
            Helper::redirect('brands', ['success' => 'Brand updated successfully!']);
        } else {
            $_SESSION['form_data'] = $_POST;
            $errorMessage = 'Failed to update brand. Please try again.'; // Default error

            if (isset($_SESSION['validation_error_type'])) {
                if ($_SESSION['validation_error_type'] === 'duplicate_name') {
                    $errorMessage = "Another brand with the name '{$name}' already exists.";
                } elseif ($_SESSION['validation_error_type'] === 'duplicate_abbreviation') {
                    $errorMessage = "The abbreviation '{$abbreviation}' is already in use by another brand.";
                }
                unset($_SESSION['validation_error_type']);
            } else {
                 $currentBrand = $this->brandModel->findById($id);
                if ($currentBrand && $currentBrand['name'] === $name && $currentBrand['abbreviation'] === ($abbreviation ?? $currentBrand['abbreviation']) ) { // Check if really no changes
                     Helper::redirect('brands/edit/' . $id, ['info' => 'No changes were made to the brand.']);
                     return; // Important to exit here
                }
            }
            Helper::redirect('brands/edit/' . $id, ['danger' => $errorMessage]);
        }
    } else {
        Helper::redirect('brands/edit/' . $id);
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