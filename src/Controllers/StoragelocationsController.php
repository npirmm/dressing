<?php
// src/Controllers/StorageLocationsController.php

namespace App\Controllers;

use App\Models\StorageLocation;
use App\Utils\Helper;
use App\Utils\Validation;

class StorageLocationsController extends BaseController {
    private StorageLocation $storageLocationModel;

    public function __construct() {
        $this->storageLocationModel = new StorageLocation();
    }

    public function index(): void {
        $sortBy = $_GET['sort'] ?? 'room'; // Défaut de tri
        $sortOrder = $_GET['order'] ?? 'asc';
        $storageLocations = $this->storageLocationModel->getAll($sortBy, $sortOrder);
        $this->renderView('storage_locations/index', [
            'pageTitle' => 'Manage Storage Locations',
            'storageLocations' => $storageLocations
        ]);
    }

    public function form(?int $id = null): void {
        $storageLocation = null;
        $pageTitle = 'Create New Storage Location';
        $formAction = APP_URL . '/storagelocations/store'; // URL en minuscules

        if ($id !== null) {
            $storageLocation = $this->storageLocationModel->findById($id);
            if (!$storageLocation) {
                Helper::redirect('storagelocations', ['danger' => "Location with ID {$id} not found."]);
                return;
            }
            $pageTitle = 'Edit Storage Location'; // On pourrait afficher le full_path ici
            $formAction = APP_URL . '/storagelocations/update/' . $id;
        }

		$distinctRooms = $this->storageLocationModel->getDistinctValuesForField('room');
		$distinctAreas = $this->storageLocationModel->getDistinctValuesForField('area');
		$distinctShelves = $this->storageLocationModel->getDistinctValuesForField('shelf_or_rack');
		$distinctLevels = $this->storageLocationModel->getDistinctValuesForField('level_or_section');

		$this->renderView('storage_locations/form', [
			'pageTitle' => $pageTitle,
			'storageLocation' => $storageLocation,
			'formAction' => $formAction,
			'csrfToken' => $_SESSION[CSRF_TOKEN_NAME],
			'distinctRooms' => $distinctRooms, // Passer les données à la vue
			'distinctAreas' => $distinctAreas,
			'distinctShelves' => $distinctShelves,
			'distinctLevels' => $distinctLevels,
		]);
    }
    
    public function create(): void { $this->form(null); }
    public function edit(int $id): void { $this->form($id); }

    public function store(): void {
        $this->verifyCsrf();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $validator = new Validation($_POST, $this->storageLocationModel); // Passer le modèle
            $rules = [
                'room' => 'required|max:100',
                'area' => 'max:100',
                'shelf_or_rack' => 'max:100',
                'level_or_section' => 'max:100',
                'specific_spot_or_box' => 'max:100',
                // Optionnel: Règle custom pour fullPathExists si implémentée
                // 'room' => 'required|max:100|full_path_unique:storage_locations,dummy_col_name', // dummy_col_name car la règle est sur plusieurs champs
            ];
            // Exemple si vous aviez une règle custom 'full_path_unique'
            // $validator->setRules($rules, ['room.full_path_unique' => 'This exact location path already exists.']);
            $validator->setRules($rules);


            if ($validator->validate()) {
                $dataToCreate = [
                    'room' => trim($_POST['room']),
                    'area' => trim($_POST['area'] ?? '') ?: null,
                    'shelf_or_rack' => trim($_POST['shelf_or_rack'] ?? '') ?: null,
                    'level_or_section' => trim($_POST['level_or_section'] ?? '') ?: null,
                    'specific_spot_or_box' => trim($_POST['specific_spot_or_box'] ?? '') ?: null,
                ];
                
            if ($this->storageLocationModel->fullPathExists($dataToCreate)) {
                $_SESSION['form_data'] = $_POST;
                // Ajouter une erreur spécifique pour le chemin complet
                // Vous pouvez choisir un champ "général" pour cette erreur ou un champ principal comme 'room'
                $_SESSION['form_errors']['room'] = ['This exact storage location path already exists.'];
                Helper::redirect('storagelocations/create');
                return; // Stopper l'exécution
            }

                $id = $this->storageLocationModel->create($dataToCreate);
                if ($id) {
                    Helper::redirect('storagelocations', ['success' => 'Storage Location created.']);
                } else {
                    $_SESSION['form_data'] = $_POST;
                    Helper::redirect('storagelocations/create', ['danger' => 'Database error.']);
                }
            } else {
                $_SESSION['form_data'] = $_POST;
                $_SESSION['form_errors'] = $validator->getErrors();
                Helper::redirect('storagelocations/create');
            }
        } else { Helper::redirect('storagelocations/create'); }
    }

    public function update(int $id): void {
        $this->verifyCsrf();
        $location = $this->storageLocationModel->findById($id);
        if (!$location) { Helper::redirect('storagelocations', ['danger' => "ID not found."]); return; }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $validator = new Validation($_POST, $this->storageLocationModel);
             $rules = [ // Mêmes règles que pour store
                'room' => 'required|max:100',
                'area' => 'max:100',
                'shelf_or_rack' => 'max:100',
                'level_or_section' => 'max:100',
                'specific_spot_or_box' => 'max:100',
                 // 'room' => 'required|max:100|full_path_unique:storage_locations,dummy_col_name,'.$id.',id',
            ];
            $validator->setRules($rules);

            if ($validator->validate()) {
                $dataToUpdate = [
                    'room' => trim($_POST['room']),
                    'area' => trim($_POST['area'] ?? '') ?: null,
                    'shelf_or_rack' => trim($_POST['shelf_or_rack'] ?? '') ?: null,
                    'level_or_section' => trim($_POST['level_or_section'] ?? '') ?: null,
                    'specific_spot_or_box' => trim($_POST['specific_spot_or_box'] ?? '') ?: null,
                ];

            if ($this->storageLocationModel->fullPathExists($dataToUpdate, $id)) {
                $_SESSION['form_data'] = $_POST;
                $_SESSION['form_errors']['room'] = ['This exact storage location path already exists for another entry.'];
                Helper::redirect('storagelocations/edit/' . $id);
                return; // Stopper l'exécution
            }
                
                $noChanges = ($dataToUpdate['room'] === $location['room'] &&
                              $dataToUpdate['area'] === $location['area'] &&
                              $dataToUpdate['shelf_or_rack'] === $location['shelf_or_rack'] &&
                              $dataToUpdate['level_or_section'] === $location['level_or_section'] &&
                              $dataToUpdate['specific_spot_or_box'] === $location['specific_spot_or_box']);

                if ($noChanges) {
                    Helper::redirect('storagelocations/edit/' . $id, ['info' => 'No changes made.']);
                    return;
                }

                if ($this->storageLocationModel->update($id, $dataToUpdate)) {
                    Helper::redirect('storagelocations', ['success' => 'Storage Location updated.']);
                } else {
                    $_SESSION['form_data'] = $_POST;
                    Helper::redirect('storagelocations/edit/' . $id, ['danger' => 'Database error.']);
                }
            } else {
                $_SESSION['form_data'] = $_POST;
                $_SESSION['form_errors'] = $validator->getErrors();
                Helper::redirect('storagelocations/edit/' . $id);
            }
        } else { $this->form($id); }
    }

    public function delete(int $id): void {
        $this->verifyCsrf();
        $item = $this->storageLocationModel->findById($id);
        if (!$item) { Helper::redirect('storagelocations', ['danger' => "ID not found."]); return; }
        if ($this->storageLocationModel->delete($id)) {
            Helper::redirect('storagelocations', ['success' => 'Storage Location deleted.']);
        } else {
            Helper::redirect('storagelocations', ['danger' => 'Failed to delete. Location might be in use.']);
        }
    }

    public function show(int $id): void {
        $storageLocation = $this->storageLocationModel->findById($id);
        if (!$storageLocation) { Helper::redirect('storagelocations', ['danger' => "ID not found."]); return; }
        $this->renderView('storage_locations/show', [
            'pageTitle' => 'Location Details: ' . Helper::e($storageLocation['full_location_path']),
            'storageLocation' => $storageLocation
        ]);
    }
}