<?php
// src/Controllers/StatusesController.php

namespace App\Controllers;

use App\Models\Status; // Importe la classe Status depuis le bon namespace
use App\Utils\Helper;
use App\Utils\Validation;

class StatusesController extends BaseController {
    private Status $statusModel;

    public function __construct() {
        // parent::__construct(); // Supprimer si BaseController n'a pas de constructeur
        $this->statusModel = new Status();
    }

    public function index(): void {
        $sortBy = $_GET['sort'] ?? 'name';
        $sortOrder = $_GET['order'] ?? 'asc';
        $statuses = $this->statusModel->getAll($sortBy, $sortOrder);
        $this->renderView('statuses/index', [
            'pageTitle' => 'Manage Statuses',
            'statuses' => $statuses
        ]);
    }

    public function form(?int $id = null): void {
        $status = null;
        $pageTitle = 'Create New Status';
        $formAction = APP_URL . '/statuses/store';

        if ($id !== null) {
            $status = $this->statusModel->findById($id);
            if (!$status) {
                Helper::redirect('statuses', ['danger' => "Status with ID {$id} not found."]);
                return;
            }
            $pageTitle = 'Edit Status: ' . Helper::e($status['name']);
            $formAction = APP_URL . '/statuses/update/' . $id;
        }

        // Débogage pour vérifier si la méthode statique est accessible
        // var_dump('In StatusesController->form()');
        // var_dump(method_exists('App\Models\Status', 'getAvailableAvailabilityTypes'));
        // var_dump(is_callable(['App\Models\Status', 'getAvailableAvailabilityTypes']));
        // die();

        $this->renderView('statuses/form', [
            'pageTitle' => $pageTitle,
            'status' => $status,
            'availabilityTypes' => Status::getAvailableAvailabilityTypes(), // L'appel problématique (ligne 45 environ)
            'formAction' => $formAction,
            'csrfToken' => $_SESSION[CSRF_TOKEN_NAME]
        ]);
    }
    
    public function create(): void { 
        $this->form(null); 
    }

    public function edit(int $id): void { 
        $this->form($id); 
    }

    public function store(): void {
        $this->verifyCsrf();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $validator = new Validation($_POST, $this->statusModel);
            $rules = [
                'name' => 'required|max:50|unique:statuses,name',
                'availability_type' => 'required|in:'.implode(',',Status::getAvailableAvailabilityTypes()), // Appel statique ici aussi
                'description' => 'max:255'
            ];
            $validator->setRules($rules);

            if ($validator->validate()) {
                $dataToCreate = [
                    'name' => trim($_POST['name']),
                    'availability_type' => $_POST['availability_type'],
                    'description' => trim($_POST['description'] ?? '') ?: null,
                ];
                $id = $this->statusModel->create($dataToCreate);
                if ($id) {
                    Helper::redirect('statuses', ['success' => 'Status created successfully!']);
                } else {
                    $_SESSION['form_data'] = $_POST;
                    Helper::redirect('statuses/create', ['danger' => 'Database error creating status.']);
                }
            } else {
                $_SESSION['form_data'] = $_POST;
                $_SESSION['form_errors'] = $validator->getErrors();
                Helper::redirect('statuses/create');
            }
        } else { Helper::redirect('statuses/create'); }
    }

    public function update(int $id): void {
        $this->verifyCsrf();
        $status = $this->statusModel->findById($id);
        if (!$status) { Helper::redirect('statuses', ['danger' => "Status with ID {$id} not found."]); return; }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $validator = new Validation($_POST, $this->statusModel);
            $rules = [
                'name' => 'required|max:50|unique:statuses,name,'.$id.',id',
                'availability_type' => 'required|in:'.implode(',',Status::getAvailableAvailabilityTypes()), // Appel statique ici aussi
                'description' => 'max:255'
            ];
            $validator->setRules($rules);

            if ($validator->validate()) {
                $dataToUpdate = [
                    'name' => trim($_POST['name']),
                    'availability_type' => $_POST['availability_type'],
                    'description' => trim($_POST['description'] ?? '') ?: null,
                ];
                if ($dataToUpdate['name'] === $status['name'] && 
                    $dataToUpdate['availability_type'] === $status['availability_type'] &&
                    ($dataToUpdate['description'] ?? null) === ($status['description'] ?? null)) { // Comparaison plus sûre pour les nulls
                    Helper::redirect('statuses/edit/' . $id, ['info' => 'No changes made to the status.']);
                    return;
                }
                if ($this->statusModel->update($id, $dataToUpdate)) {
                    Helper::redirect('statuses', ['success' => 'Status updated successfully!']);
                } else {
                    $_SESSION['form_data'] = $_POST;
                    Helper::redirect('statuses/edit/' . $id, ['danger' => 'Database error updating status.']);
                }
            } else {
                $_SESSION['form_data'] = $_POST;
                $_SESSION['form_errors'] = $validator->getErrors();
                Helper::redirect('statuses/edit/' . $id);
            }
        } else { $this->form($id); }
    }

    public function delete(int $id): void {
        $this->verifyCsrf();
        $item = $this->statusModel->findById($id);
        if (!$item) { Helper::redirect('statuses', ['danger' => "Status with ID {$id} not found."]); return; }
        
        // Vous pourriez vouloir ajouter une vérification ici pour voir si le statut est utilisé par des articles
        // avant de permettre la suppression, si la contrainte FK est 'ON DELETE RESTRICT'.
        // if ($this->statusModel->isUsedByArticles($id)) {
        //     Helper::redirect('statuses', ['danger' => 'Cannot delete status: It is currently in use by one or more articles.']);
        //     return;
        // }

        if ($this->statusModel->delete($id)) {
            Helper::redirect('statuses', ['success' => 'Status deleted successfully.']);
        } else {
            Helper::redirect('statuses', ['danger' => 'Failed to delete status. It might be in use or a database error occurred.']);
        }
    }

    public function show(int $id): void {
        $status = $this->statusModel->findById($id);
        if (!$status) { Helper::redirect('statuses', ['danger' => "Status with ID {$id} not found."]); return; }
        $this->renderView('statuses/show', [
            'pageTitle' => 'Status Details: ' . Helper::e($status['name']),
            'status' => $status
        ]);
    }
}