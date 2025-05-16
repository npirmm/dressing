<?php
// src/Controllers/CategoryTypesController.php

namespace App\Controllers;

use App\Models\CategoryType;
use App\Utils\Helper;
use App\Utils\Validation;

class CategoryTypesController extends BaseController {
    private CategoryType $categoryTypeModel;

    public function __construct() {
        $this->categoryTypeModel = new CategoryType();
    }

    public function index(): void {
        $sortBy = $_GET['sort'] ?? 'name';
        $sortOrder = $_GET['order'] ?? 'asc';
        $categoryTypes = $this->categoryTypeModel->getAll($sortBy, $sortOrder);
        $this->renderView('categories_types/index', [
            'pageTitle' => 'Manage Categories/Types',
            'categoryTypes' => $categoryTypes
        ]);
    }

    public function form(?int $id = null): void {
        $categoryType = null;
        $pageTitle = 'Create New Category/Type';
        $formAction = APP_URL . '/categorytypes/store'; // Notez: 'categorytypes' au pluriel pour l'URL

        if ($id !== null) {
            $categoryType = $this->categoryTypeModel->findById($id);
            if (!$categoryType) {
                Helper::redirect('categorytypes', ['danger' => "Category/Type with ID {$id} not found."]);
                return;
            }
            $pageTitle = 'Edit Category/Type: ' . Helper::e($categoryType['name']);
            $formAction = APP_URL . '/categorytypes/update/' . $id;
        }

        $this->renderView('categories_types/form', [
            'pageTitle' => $pageTitle,
            'categoryType' => $categoryType,
            'availableCategories' => CategoryType::getAvailableCategories(), // Pour le select
            'formAction' => $formAction,
            'csrfToken' => $_SESSION[CSRF_TOKEN_NAME]
        ]);
    }
    
    public function create(): void { $this->form(null); }
    public function edit(int $id): void { $this->form($id); }

    public function store(): void {
        $this->verifyCsrf();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $validator = new Validation($_POST, $this->categoryTypeModel);
            $validator->setRules([
                'name' => 'required|max:100|unique:categories_types,name',
                'category' => 'required|in:'.implode(',',CategoryType::getAvailableCategories()),
                'code' => 'required|max:5|alpha_num_dash|unique:categories_types,code' // alpha_num_dash à ajouter à Validation
            ], [
                'code.alpha_num_dash' => 'The code can only contain letters, numbers, and dashes/underscores.'
            ]);

            // Règle alpha_num_dash (si pas déjà dans Validation.php)
            // Vous devrez l'ajouter à Validation.php:
            // protected function validateAlphaNumDash(string $field, $value, array $params): bool {
            //     if (!empty($value) && !preg_match('/^[a-zA-Z0-9_-]+$/', (string)$value)) {
            //         $this->addError($field, "The {$field} may only contain letters, numbers, dashes, and underscores.");
            //         return false;
            //     }
            //     return true;
            // }


            if ($validator->validate()) {
                $dataToCreate = [
                    'name' => trim($_POST['name']),
                    'category' => $_POST['category'],
                    'code' => trim($_POST['code'])
                ];
                $id = $this->categoryTypeModel->create($dataToCreate);
                if ($id) {
                    Helper::redirect('categorytypes', ['success' => 'Category/Type created successfully!']);
                } else {
                    $_SESSION['form_data'] = $_POST;
                    Helper::redirect('categorytypes/create', ['danger' => 'Database error creating category/type.']);
                }
            } else {
                $_SESSION['form_data'] = $_POST;
                $_SESSION['form_errors'] = $validator->getErrors();
                Helper::redirect('categorytypes/create');
            }
        } else { Helper::redirect('categorytypes/create'); }
    }

    public function update(int $id): void {
        $this->verifyCsrf();
        $categoryType = $this->categoryTypeModel->findById($id);
        if (!$categoryType) { Helper::redirect('categorytypes', ['danger' => "ID not found."]); return; }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $validator = new Validation($_POST, $this->categoryTypeModel);
            $validator->setRules([
                'name' => 'required|max:100|unique:categories_types,name,'.$id.',id',
                'category' => 'required|in:'.implode(',',CategoryType::getAvailableCategories()),
                'code' => 'required|max:5|alpha_num_dash|unique:categories_types,code,'.$id.',id'
            ]);

            if ($validator->validate()) {
                $dataToUpdate = [
                    'name' => trim($_POST['name']),
                    'category' => $_POST['category'],
                    'code' => trim($_POST['code'])
                ];
                if ($dataToUpdate['name'] === $categoryType['name'] && 
                    $dataToUpdate['category'] === $categoryType['category'] &&
                    strtoupper($dataToUpdate['code']) === strtoupper($categoryType['code'])) {
                    Helper::redirect('categorytypes/edit/' . $id, ['info' => 'No changes made.']);
                    return;
                }
                if ($this->categoryTypeModel->update($id, $dataToUpdate)) {
                    Helper::redirect('categorytypes', ['success' => 'Category/Type updated successfully!']);
                } else {
                    $_SESSION['form_data'] = $_POST;
                    Helper::redirect('categorytypes/edit/' . $id, ['danger' => 'Database error updating.']);
                }
            } else {
                $_SESSION['form_data'] = $_POST;
                $_SESSION['form_errors'] = $validator->getErrors();
                Helper::redirect('categorytypes/edit/' . $id);
            }
        } else { $this->form($id); }
    }

    public function delete(int $id): void {
        $this->verifyCsrf();
        $item = $this->categoryTypeModel->findById($id);
        if (!$item) { Helper::redirect('categorytypes', ['danger' => "ID not found."]); return; }
        if ($this->categoryTypeModel->delete($id)) {
            Helper::redirect('categorytypes', ['success' => 'Category/Type deleted.']);
        } else {
            Helper::redirect('categorytypes', ['danger' => 'Failed to delete. Item might be in use.']);
        }
    }

    public function show(int $id): void {
        $categoryType = $this->categoryTypeModel->findById($id);
        if (!$categoryType) { Helper::redirect('categorytypes', ['danger' => "ID not found."]); return; }
        $this->renderView('categories_types/show', [
            'pageTitle' => 'Details: ' . Helper::e($categoryType['name']),
            'categoryType' => $categoryType
        ]);
    }
}