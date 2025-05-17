<?php
// src/Controllers/EventTypesController.php

namespace App\Controllers;

use App\Models\EventType;
use App\Models\DayMoment; // Pour charger les moments disponibles
use App\Utils\Helper;
use App\Utils\Validation;

class EventTypesController extends BaseController {
    private EventType $eventTypeModel;
    private DayMoment $dayMomentModel;

    public function __construct() {
        $this->eventTypeModel = new EventType();
        $this->dayMomentModel = new DayMoment(); // Instancier le modèle DayMoment
    }

    public function index(): void {
        $sortBy = $_GET['sort'] ?? 'name';
        $sortOrder = $_GET['order'] ?? 'asc';
        // La méthode getAll du modèle EventType récupère maintenant les noms des moments concaténés
        $eventTypes = $this->eventTypeModel->getAll($sortBy, $sortOrder);
        $this->renderView('event_types/index', [
            'pageTitle' => 'Manage Event Types',
            'eventTypes' => $eventTypes
        ]);
    }

    public function form(?int $id = null): void {
        $eventType = null;
        $pageTitle = 'Create New Event Type';
        $formAction = APP_URL . '/eventtypes/store';
        $selectedDayMomentIds = [];

        if ($id !== null) {
            $eventType = $this->eventTypeModel->findById($id);
            if (!$eventType) {
                Helper::redirect('eventtypes', ['danger' => "Event Type with ID {$id} not found."]);
                return;
            }
            $pageTitle = 'Edit Event Type: ' . Helper::e($eventType['name']);
            $formAction = APP_URL . '/eventtypes/update/' . $id;
            $selectedDayMomentIds = $eventType['selected_day_moment_ids'] ?? [];
        }

        $allDayMoments = $this->dayMomentModel->getAllOrdered(); // Récupérer tous les moments pour les checkboxes

        $this->renderView('event_types/form', [
            'pageTitle' => $pageTitle,
            'eventType' => $eventType,
            'allDayMoments' => $allDayMoments,
            'selectedDayMomentIds' => $selectedDayMomentIds,
            'formAction' => $formAction,
            'csrfToken' => $_SESSION[CSRF_TOKEN_NAME]
        ]);
    }
    
    public function create(): void { $this->form(null); }
    public function edit(int $id): void { $this->form($id); }

    public function store(): void {
        $this->verifyCsrf();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $validator = new Validation($_POST, $this->eventTypeModel);
            $validator->setRules([
                'name' => 'required|max:100|unique:event_types,name',
                'description' => 'max:65535', // TEXT max length
                'day_moment_ids' => 'array' // S'assurer que c'est un tableau (peut être vide)
            ]);
            // La règle 'array' pour day_moment_ids doit être ajoutée à Validation.php
            // protected function validateArray(string $field, $value, array $params): bool {
            //     if (!is_null($value) && !is_array($value)) { // Permet null si non requis
            //         $this->addError($field, "The {$field} must be an array.");
            //         return false;
            //     }
            //     return true;
            // }

            if ($validator->validate()) {
                $dataToCreate = [
                    'name' => trim($_POST['name']),
                    'description' => trim($_POST['description'] ?? '') ?: null,
                ];
                $dayMomentIds = $_POST['day_moment_ids'] ?? [];
                
                $id = $this->eventTypeModel->create($dataToCreate, $dayMomentIds);
                if ($id) {
                    Helper::redirect('eventtypes', ['success' => 'Event Type created successfully!']);
                } else {
                    $_SESSION['form_data'] = $_POST; // Inclut les day_moment_ids
                    Helper::redirect('eventtypes/create', ['danger' => 'Database error creating event type.']);
                }
            } else {
                $_SESSION['form_data'] = $_POST;
                $_SESSION['form_errors'] = $validator->getErrors();
                Helper::redirect('eventtypes/create');
            }
        } else { Helper::redirect('eventtypes/create'); }
    }

    public function update(int $id): void {
        $this->verifyCsrf();
        $eventType = $this->eventTypeModel->findById($id);
        if (!$eventType) { Helper::redirect('eventtypes', ['danger' => "ID not found."]); return; }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $validator = new Validation($_POST, $this->eventTypeModel);
            $validator->setRules([
                'name' => 'required|max:100|unique:event_types,name,'.$id.',id',
                'description' => 'max:65535',
                'day_moment_ids' => 'array'
            ]);

            if ($validator->validate()) {
                $dataToUpdate = [
                    'name' => trim($_POST['name']),
                    'description' => trim($_POST['description'] ?? '') ?: null,
                ];
                $dayMomentIds = $_POST['day_moment_ids'] ?? [];

                // Vérifier si des changements ont été faits (un peu plus complexe avec la relation many-to-many)
                $currentSelectedIds = $eventType['selected_day_moment_ids'] ?? [];
                sort($currentSelectedIds); // Trier pour comparaison
                sort($dayMomentIds);      // Trier pour comparaison
                
                $noChanges = ($dataToUpdate['name'] === $eventType['name'] &&
                              $dataToUpdate['description'] === $eventType['description'] &&
                              $currentSelectedIds === $dayMomentIds);

                if ($noChanges) {
                    Helper::redirect('eventtypes/edit/' . $id, ['info' => 'No changes made.']);
                    return;
                }

                if ($this->eventTypeModel->update($id, $dataToUpdate, $dayMomentIds)) {
                    Helper::redirect('eventtypes', ['success' => 'Event Type updated successfully!']);
                } else {
                    $_SESSION['form_data'] = $_POST;
                    Helper::redirect('eventtypes/edit/' . $id, ['danger' => 'Database error updating.']);
                }
            } else {
                $_SESSION['form_data'] = $_POST;
                $_SESSION['form_errors'] = $validator->getErrors();
                Helper::redirect('eventtypes/edit/' . $id);
            }
        } else { $this->form($id); }
    }

    public function delete(int $id): void {
        $this->verifyCsrf();
        $item = $this->eventTypeModel->findById($id);
        if (!$item) { Helper::redirect('eventtypes', ['danger' => "ID not found."]); return; }
        if ($this->eventTypeModel->delete($id)) {
            Helper::redirect('eventtypes', ['success' => 'Event Type deleted.']);
        } else {
            Helper::redirect('eventtypes', ['danger' => 'Failed to delete.']);
        }
    }

    public function show(int $id): void {
        $eventType = $this->eventTypeModel->findById($id);
        if (!$eventType) { Helper::redirect('eventtypes', ['danger' => "ID not found."]); return; }
        
        // Récupérer les noms des moments pour l'affichage
        $dayMomentModel = new DayMoment(); // Peut être mis dans le constructeur
        $selectedMoments = $dayMomentModel->findByIds($eventType['selected_day_moment_ids'] ?? []);
        $eventType['day_moments_names_list'] = array_column($selectedMoments, 'name');

        $this->renderView('event_types/show', [
            'pageTitle' => 'Details: ' . Helper::e($eventType['name']),
            'eventType' => $eventType
        ]);
    }
}