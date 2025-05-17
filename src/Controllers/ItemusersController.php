<?php
// src/Controllers/ItemUsersController.php

namespace App\Controllers;

use App\Models\ItemUser;
use App\Utils\Helper;
use App\Utils\Validation;

class ItemUsersController extends BaseController {
    private ItemUser $itemUserModel;

    public function __construct() {
        $this->itemUserModel = new ItemUser();
    }

    public function index(): void {
        $sortBy = $_GET['sort'] ?? 'name';
        $sortOrder = $_GET['order'] ?? 'asc';
        $itemUsers = $this->itemUserModel->getAll($sortBy, $sortOrder);
        $this->renderView('item_users/index', [
            'pageTitle' => 'Manage Item Users',
            'itemUsers' => $itemUsers
        ]);
    }

    public function form(?int $id = null): void {
        $itemUser = null;
        $pageTitle = 'Create New Item User';
        $formAction = APP_URL . '/itemusers/store'; // URL en minuscules

        if ($id !== null) {
            $itemUser = $this->itemUserModel->findById($id);
            if (!$itemUser) {
                Helper::redirect('itemusers', ['danger' => "Item User with ID {$id} not found."]);
                return;
            }
            $pageTitle = 'Edit Item User: ' . Helper::e($itemUser['name']);
            $formAction = APP_URL . '/itemusers/update/' . $id;
        }

        $this->renderView('item_users/form', [
            'pageTitle' => $pageTitle,
            'itemUser' => $itemUser,
            'formAction' => $formAction,
            'csrfToken' => $_SESSION[CSRF_TOKEN_NAME]
        ]);
    }
    
    public function create(): void { $this->form(null); }
    public function edit(int $id): void { $this->form($id); }

    public function store(): void {
        $this->verifyCsrf();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $validator = new Validation($_POST, $this->itemUserModel);
            $rules = [
                'name' => 'required|max:100|unique:item_users,name',
                'abbreviation' => 'required|max:20|unique:item_users,abbreviation' // 'required' et 'unique'
            ];
            // Plus besoin d'ajouter 'unique' conditionnellement pour abbreviation
            $validator->setRules($rules, [
                'abbreviation.required' => 'The abbreviation is required.',
                'abbreviation.unique' => 'This abbreviation is already in use.'
                // Ajoutez d'autres messages custom si besoin
            ]);

            if ($validator->validate()) {
                $dataToCreate = [
                    'name' => trim($_POST['name']),
                    'abbreviation' => trim($_POST['abbreviation']), // On sait qu'il est fourni
                ];
                // ... (reste de la logique de store) ...
                $id = $this->itemUserModel->create($dataToCreate);
                if ($id) {
                    Helper::redirect('itemusers', ['success' => 'Item User created successfully!']);
                } else {
                    $_SESSION['form_data'] = $_POST;
                    Helper::redirect('itemusers/create', ['danger' => 'Database error creating item user.']);
                }
            } else {
                $_SESSION['form_data'] = $_POST;
                $_SESSION['form_errors'] = $validator->getErrors();
                Helper::redirect('itemusers/create');
            }
        } else { Helper::redirect('itemusers/create'); }
    }

    public function update(int $id): void {
        $this->verifyCsrf();
        $itemUser = $this->itemUserModel->findById($id);
        if (!$itemUser) { Helper::redirect('itemusers', ['danger' => "ID not found."]); return; }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $validator = new Validation($_POST, $this->itemUserModel);
            $rules = [
                'name' => 'required|max:100|unique:item_users,name,'.$id.',id',
                'abbreviation' => 'required|max:20|unique:item_users,abbreviation,'.$id.',id' // 'required' et 'unique' avec exclusion
            ];
            // Plus besoin d'ajouter 'unique' conditionnellement
            $validator->setRules($rules, [ /* ... messages custom ... */]);

            if ($validator->validate()) {
                $dataToUpdate = [
                    'name' => trim($_POST['name']),
                    'abbreviation' => trim($_POST['abbreviation']),
                ];
                // ... (reste de la logique d'update, y compris la vérification $noChanges) ...
                if ($dataToUpdate['name'] === $itemUser['name'] && $dataToUpdate['abbreviation'] === $itemUser['abbreviation']) {
                    Helper::redirect('itemusers/edit/' . $id, ['info' => 'No changes made.']);
                    return;
                }
                if ($this->itemUserModel->update($id, $dataToUpdate)) {
                    Helper::redirect('itemusers', ['success' => 'Item User updated successfully!']);
                } else {
                    $_SESSION['form_data'] = $_POST;
                    Helper::redirect('itemusers/edit/' . $id, ['danger' => 'Database error updating.']);
                }
            } else {
                $_SESSION['form_data'] = $_POST;
                $_SESSION['form_errors'] = $validator->getErrors();
                Helper::redirect('itemusers/edit/' . $id);
            }
        } else { $this->form($id); }
    }

    public function delete(int $id): void {
        $this->verifyCsrf();
        $item = $this->itemUserModel->findById($id);
        if (!$item) { Helper::redirect('itemusers', ['danger' => "ID not found."]); return; }
        if ($this->itemUserModel->delete($id)) {
            Helper::redirect('itemusers', ['success' => 'Item User deleted.']);
        } else {
            Helper::redirect('itemusers', ['danger' => 'Failed to delete. Item User might be in use.']);
        }
    }

    public function show(int $id): void {
        $itemUser = $this->itemUserModel->findById($id);
        if (!$itemUser) { Helper::redirect('itemusers', ['danger' => "ID not found."]); return; }
        $this->renderView('item_users/show', [
            'pageTitle' => 'Details: ' . Helper::e($itemUser['name']),
            'itemUser' => $itemUser
        ]);
    }
}