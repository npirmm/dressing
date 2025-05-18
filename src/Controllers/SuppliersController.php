<?php
// src/Controllers/SuppliersController.php

namespace App\Controllers;

use App\Models\Supplier;
use App\Utils\Helper;
use App\Utils\Validation;

class SuppliersController extends BaseController {
    private Supplier $supplierModel;

    public function __construct() {
        $this->supplierModel = new Supplier();
    }

    public function index(): void {
        $sortBy = $_GET['sort'] ?? 'name';
        $sortOrder = $_GET['order'] ?? 'asc';
        $suppliers = $this->supplierModel->getAll($sortBy, $sortOrder);
        $this->renderView('suppliers/index', [
            'pageTitle' => 'Manage Suppliers',
            'suppliers' => $suppliers
        ]);
    }

    public function form(?int $id = null): void {
        $supplier = null;
        $pageTitle = 'Create New Supplier';
        $formAction = APP_URL . '/suppliers/store';

        if ($id !== null) {
            $supplier = $this->supplierModel->findById($id);
            if (!$supplier) {
                Helper::redirect('suppliers', ['danger' => "Supplier with ID {$id} not found."]);
                return;
            }
            $pageTitle = 'Edit Supplier: ' . Helper::e($supplier['name']);
            $formAction = APP_URL . '/suppliers/update/' . $id;
        }

        $this->renderView('suppliers/form', [
            'pageTitle' => $pageTitle,
            'supplier' => $supplier,
            'formAction' => $formAction,
            'csrfToken' => $_SESSION[CSRF_TOKEN_NAME]
        ]);
    }
    
    public function create(): void { $this->form(null); }
    public function edit(int $id): void { $this->form($id); }

    public function store(): void {
        $this->verifyCsrf();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $validator = new Validation($_POST, $this->supplierModel);
            $rules = [
                'name' => 'required|max:150|unique:suppliers,name',
                'contact_person' => 'max:100',
                'email' => 'email|max:100', // La règle 'email' valide le format
                'phone' => 'max:30',
                // 'address' et 'notes' sont des TEXT, pas de max length ici (géré par BDD)
            ];
            // Ajouter la règle d'unicité pour l'email seulement s'il est fourni
            if (!empty(trim($_POST['email'] ?? ''))) {
                $rules['email'] .= '|unique:suppliers,email';
            }
            $validator->setRules($rules, [
                'email.email' => 'Please enter a valid email address.',
                'email.unique' => 'This email address is already in use.'
            ]);

            if ($validator->validate()) {
                $dataToCreate = [
                    'name' => trim($_POST['name']),
                    'contact_person' => trim($_POST['contact_person'] ?? '') ?: null,
                    'email' => !empty(trim($_POST['email'] ?? '')) ? trim($_POST['email']) : null,
                    'phone' => trim($_POST['phone'] ?? '') ?: null,
                    'address' => trim($_POST['address'] ?? '') ?: null,
                    'notes' => trim($_POST['notes'] ?? '') ?: null,
                ];
                $id = $this->supplierModel->create($dataToCreate);
                if ($id) {
                    Helper::redirect('suppliers', ['success' => 'Supplier created successfully!']);
                } else {
                    $_SESSION['form_data'] = $_POST;
                    Helper::redirect('suppliers/create', ['danger' => 'Database error creating supplier.']);
                }
            } else {
                $_SESSION['form_data'] = $_POST;
                $_SESSION['form_errors'] = $validator->getErrors();
                Helper::redirect('suppliers/create');
            }
        } else { Helper::redirect('suppliers/create'); }
    }

    public function update(int $id): void {
        $this->verifyCsrf();
        $supplier = $this->supplierModel->findById($id);
        if (!$supplier) { Helper::redirect('suppliers', ['danger' => "ID not found."]); return; }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $validator = new Validation($_POST, $this->supplierModel);
            $rules = [
                'name' => 'required|max:150|unique:suppliers,name,'.$id.',id',
                'contact_person' => 'max:100',
                'email' => 'email|max:100',
                'phone' => 'max:30',
            ];
            if (!empty(trim($_POST['email'] ?? ''))) {
                $rules['email'] .= '|unique:suppliers,email,'.$id.',id';
            }
            $validator->setRules($rules);

            if ($validator->validate()) {
                $dataToUpdate = [
                    'name' => trim($_POST['name']),
                    'contact_person' => trim($_POST['contact_person'] ?? '') ?: null,
                    'email' => !empty(trim($_POST['email'] ?? '')) ? trim($_POST['email']) : null,
                    'phone' => trim($_POST['phone'] ?? '') ?: null,
                    'address' => trim($_POST['address'] ?? '') ?: null,
                    'notes' => trim($_POST['notes'] ?? '') ?: null,
                ];
                
                $noChanges = ($dataToUpdate['name'] === $supplier['name'] &&
                              $dataToUpdate['contact_person'] === $supplier['contact_person'] &&
                              $dataToUpdate['email'] === $supplier['email'] &&
                              $dataToUpdate['phone'] === $supplier['phone'] &&
                              $dataToUpdate['address'] === $supplier['address'] &&
                              $dataToUpdate['notes'] === $supplier['notes']);

                if ($noChanges) {
                    Helper::redirect('suppliers/edit/' . $id, ['info' => 'No changes made.']);
                    return;
                }

                if ($this->supplierModel->update($id, $dataToUpdate)) {
                    Helper::redirect('suppliers', ['success' => 'Supplier updated successfully!']);
                } else {
                    $_SESSION['form_data'] = $_POST;
                    Helper::redirect('suppliers/edit/' . $id, ['danger' => 'Database error updating.']);
                }
            } else {
                $_SESSION['form_data'] = $_POST;
                $_SESSION['form_errors'] = $validator->getErrors();
                Helper::redirect('suppliers/edit/' . $id);
            }
        } else { $this->form($id); }
    }

    public function delete(int $id): void {
        $this->verifyCsrf();
        $item = $this->supplierModel->findById($id);
        if (!$item) { Helper::redirect('suppliers', ['danger' => "ID not found."]); return; }
        if ($this->supplierModel->delete($id)) {
            Helper::redirect('suppliers', ['success' => 'Supplier deleted.']);
        } else {
            // L'échec peut être dû à une contrainte FK si le fournisseur est utilisé dans `articles`
            Helper::redirect('suppliers', ['danger' => 'Failed to delete supplier. It might be in use by an article.']);
        }
    }

    public function show(int $id): void {
        $supplier = $this->supplierModel->findById($id);
        if (!$supplier) { Helper::redirect('suppliers', ['danger' => "ID not found."]); return; }
        $this->renderView('suppliers/show', [
            'pageTitle' => 'Details: ' . Helper::e($supplier['name']),
            'supplier' => $supplier
        ]);
    }
}