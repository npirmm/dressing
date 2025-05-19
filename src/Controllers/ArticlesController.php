<?php
// src/Controllers/ArticlesController.php

namespace App\Controllers;

use App\Models\Article;
use App\Models\Brand; // Nécessaire pour la liste des marques
use App\Models\CategoryType; // Nécessaire pour la liste des types/catégories
use App\Models\Color; // Nécessaire pour la liste des couleurs
use App\Models\Material; // Nécessaire pour la liste des matières
use App\Models\Status; // Nécessaire pour la liste des statuts
use App\Models\StorageLocation; // Nécessaire pour la liste des lieux de stockage
use App\Models\Supplier; // Nécessaire pour la liste des fournisseurs
use App\Models\EventType;      // Pour la liste des types d'événements
use App\Models\ItemUser;       // Pour la liste des utilisateurs d'items
use App\Models\EventLog;
use App\Utils\Helper;
use App\Utils\Validation; 
use App\Utils\ImageUploader; 



class ArticlesController extends BaseController {
    private Article $articleModel;
    private string $articleImageUploadPath;

    // Modèles pour les listes déroulantes
    private Brand $brandModel;
    private CategoryType $categoryTypeModel;
    private Color $colorModel;
    private Material $materialModel;
    private Status $statusModel;
    private StorageLocation $storageLocationModel;
    private Supplier $supplierModel;
    private EventType $eventTypeModel; 
    private ItemUser $itemUserModel;   

    public function __construct() {
        $this->articleModel = new Article();
        if (!defined('ARTICLE_IMAGE_PATH')) {
            define('ARTICLE_IMAGE_PATH', 'articles_fallback/');
            error_log("Config constant ARTICLE_IMAGE_PATH not defined.");
        }
        $this->articleImageUploadPath = ARTICLE_IMAGE_PATH;

        // Instancier les modèles nécessaires pour les formulaires
        $this->brandModel = new Brand();
        $this->categoryTypeModel = new CategoryType();
        $this->colorModel = new Color();
        $this->materialModel = new Material();
        $this->statusModel = new Status(); // Assurez-vous que Status.php existe et est correct
        $this->storageLocationModel = new StorageLocation();
        $this->supplierModel = new Supplier();
        $this->eventTypeModel = new EventType();
        $this->itemUserModel = new ItemUser();
    }

    public function index(): void {
        $sortBy = $_GET['sort'] ?? 'a.name'; // Qualifier avec l'alias de table 'a.'
        $sortOrder = $_GET['order'] ?? 'asc';
        // Les filtres viendront plus tard
        $articles = $this->articleModel->getAll($sortBy, $sortOrder);

        $this->renderView('articles/index', [
            'pageTitle' => 'Manage Articles',
            'articles' => $articles,
            'articleImagePath' => APP_URL . '/assets/media/' . $this->articleImageUploadPath
        ]);
    }

    public function show(int $id): void {
        $article = $this->articleModel->findById($id);
        if (!$article) {
            Helper::redirect('articles', ['danger' => "Article with ID {$id} not found."]);
            return;
        }

        // Charger les noms des articles associés pour l'affichage
        if (!empty($article['associated_article_ids'])) {
            $associatedArticlesDetails = [];
            foreach ($article['associated_article_ids'] as $assocId) {
                $assocArticle = $this->articleModel->findById((int)$assocId); // Récupère le nom, ref
                if ($assocArticle) {
                    $associatedArticlesDetails[] = [
                        'id' => $assocArticle['id'],
                        'name' => $assocArticle['name'],
                        'article_ref' => $assocArticle['article_ref']
                    ];
                }
            }
            $article['associated_articles_details'] = $associatedArticlesDetails;
        }


        $this->renderView('articles/show', [
            'pageTitle' => 'Article Details: ' . Helper::e($article['name']),
            'article' => $article,
            'articleImagePath' => APP_URL . '/assets/media/' . $this->articleImageUploadPath
        ]);
    }

    public function form(?int $id = null): void {
        $article = null;
        $pageTitle = 'Create New Article';
        $formAction = APP_URL . '/articles/store';
        $articleImages = []; // Pour les images existantes en mode édition
        $selectedAssociatedArticleIds = []; // Pour les articles associés en mode édition

        if ($id !== null) {
            $article = $this->articleModel->findById($id); // findById récupère déjà les images et IDs associés
            if (!$article) {
                Helper::redirect('articles', ['danger' => "Article with ID {$id} not found."]);
                return;
            }
            $pageTitle = 'Edit Article: ' . Helper::e($article['name']);
            $formAction = APP_URL . '/articles/update/' . $id;
            $articleImages = $article['images'] ?? []; // Récupéré par findById
            $selectedAssociatedArticleIds = $article['associated_article_ids'] ?? []; // Récupéré par findById
        }

        // Charger les données pour les listes déroulantes
        $brands = $this->brandModel->getAll('name', 'ASC');
        $categoryTypes = $this->categoryTypeModel->getAll('name', 'ASC');
        $colors = $this->colorModel->getAll('name', 'ASC');
        $materials = $this->materialModel->getAll('name', 'ASC');
        // Assurez-vous d'avoir un modèle Status.php et une méthode getAll()
        $statuses = $this->statusModel->getAll('id', 'ASC'); // Nouveau tri par ID
        $storageLocations = $this->storageLocationModel->getAll('full_location_path', 'ASC'); // Trier par full_path
        $suppliers = $this->supplierModel->getAll('name', 'ASC');
        
        // Pour la sélection des articles associés (tous les articles sauf celui en cours d'édition)
        $allArticlesForAssociation = $this->articleModel->getAll('a.name', 'ASC');
        if ($id !== null) {
            $allArticlesForAssociation = array_filter($allArticlesForAssociation, fn($a) => $a['id'] != $id);
        }

        // Options pour les enums (directement dans le contrôleur ou via une méthode statique du modèle Article)
        $seasonOptions = ['Printemps', 'Été', 'Automne', 'Hiver', 'Toutes saisons', 'Entre-saisons'];
        $conditionOptions = ['neuf', 'excellent', 'bon état', 'médiocre', 'à réparer/retoucher'];


        $this->renderView('articles/form', [
            'pageTitle' => $pageTitle,
            'article' => $article,
            'formAction' => $formAction,
            'csrfToken' => $_SESSION[CSRF_TOKEN_NAME],
            'articleImagePath' => APP_URL . '/assets/media/' . $this->articleImageUploadPath, // Pour afficher les images actuelles
            'articleImages' => $articleImages, // Images existantes
            
            // Données pour les selects
            'brands' => $brands,
            'categoryTypes' => $categoryTypes,
            'colors' => $colors,
            'materials' => $materials,
            'statuses' => $statuses,
            'storageLocations' => $storageLocations,
            'suppliers' => $suppliers,
            'allArticlesForAssociation' => $allArticlesForAssociation,
            'selectedAssociatedArticleIds' => $selectedAssociatedArticleIds,

            // Options pour enums
            'seasonOptions' => $seasonOptions,
            'conditionOptions' => $conditionOptions,
        ]);
				
				
    }
    
    public function create(): void { $this->form(null); }
    public function edit(int $id): void { $this->form($id); }


    public function store(): void {
        $this->verifyCsrf();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Helper::redirect('articles/create');
            return;
        }

        $validator = new Validation($_POST, null);
        $rules = [ // Les règles ne doivent plus contenir 'current_status_id' ici
            'name' => 'required|max:150',
            'category_type_id' => 'required|numeric',
            'condition' => 'required|in:neuf,excellent,bon état,médiocre,à réparer/retoucher',
            
            // Champs optionnels mais avec validation si fournis
            'brand_id' => 'numeric_or_empty',
            'primary_color_id' => 'numeric_or_empty',
            'secondary_color_id' => 'numeric_or_empty',
            'material_id' => 'numeric_or_empty',
            // 'current_storage_location_id' n'est plus dans le form initial
            'supplier_id' => 'numeric_or_empty',
            'season' => 'in:Printemps,Été,Automne,Hiver,Toutes saisons,Entre-saisons,',
            'size' => 'max:50',
            'weight_grams' => 'numeric_or_empty|min_numeric:0',
            'purchase_date' => 'date_or_empty',
            'purchase_price' => 'decimal_or_empty:2',
            'estimated_value' => 'decimal_or_empty:2',
            'rating' => 'numeric_or_empty|min_numeric:0|max_numeric:5',
            // 'description', 'notes' sont des TEXT, pas de validation de longueur ici
            // 'article_images' (fichiers) et 'associated_article_ids' (tableau) sont gérés séparément
        ];
        // Vous devrez ajouter les règles 'numeric_or_empty', 'min_numeric', 'max_numeric', 'date_or_empty', 'decimal_or_empty'
        // à votre classe Validation.php si elles n'existent pas.
        // Exemple pour numeric_or_empty:
        // protected function validateNumericOrEmpty(string $field, $value, array $params): bool {
        //     if (empty($value)) return true;
        //     if (!is_numeric($value)) {
        //         $this->addError($field, "The {$field} field must be a number.");
        //         return false;
        //     }
        //     return true;
        // }

        $validator->setRules($rules);

        if (!$validator->validate()) {
            $_SESSION['form_data'] = $_POST;
            $_SESSION['form_errors'] = $validator->getErrors();
            Helper::redirect('articles/create');
            return;
        }

		if (!defined('STATUS_ID_NEW_PURCHASE')) {
			error_log("ERREUR CRITIQUE: Constante STATUS_ID_NEW_PURCHASE non définie.");
			// Gérer erreur...
			Helper::redirect('articles/create', ['danger' => 'System configuration error for default status.']); return;
		}
		$defaultStatusId = STATUS_ID_NEW_PURCHASE;
		
		
        // 2. Préparer les données pour la création de l'article principal
        //$postedSeason = trim($_POST['season'] ?? ''); // Récupérer la valeur postée
        $articleData = [
            'name' => trim($_POST['name']),
            'description' => trim($_POST['description'] ?? '') ?: null,
            'season' => !empty(trim($_POST['season'] ?? '')) ? trim($_POST['season']) : null,
            'category_type_id' => (int)$_POST['category_type_id'],
            'brand_id' => !empty($_POST['brand_id']) ? (int)$_POST['brand_id'] : null,
            'condition' => $_POST['condition'],
            'primary_color_id' => !empty($_POST['primary_color_id']) ? (int)$_POST['primary_color_id'] : null,
            'secondary_color_id' => !empty($_POST['secondary_color_id']) ? (int)$_POST['secondary_color_id'] : null,
            'material_id' => !empty($_POST['material_id']) ? (int)$_POST['material_id'] : null,
            'size' => trim($_POST['size'] ?? '') ?: null,
            'weight_grams' => !empty($_POST['weight_grams']) ? (int)$_POST['weight_grams'] : null,
            
            'current_storage_location_id' => null, // Initialement null
            'current_status_id' => $defaultStatusId, // *** UTILISER L'ID DU STATUT PAR DÉFAUT ICI ***

            'purchase_date' => !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : null,
            'purchase_price' => !empty($_POST['purchase_price']) ? (float)$_POST['purchase_price'] : null,
            'supplier_id' => !empty($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : null,
            'estimated_value' => !empty($_POST['estimated_value']) ? (float)$_POST['estimated_value'] : null,
            'rating' => !empty($_POST['rating']) && is_numeric($_POST['rating']) ? (int)$_POST['rating'] : null, // Vérifier is_numeric
            'notes' => trim($_POST['notes'] ?? '') ?: null,
            // article_ref sera généré par le modèle
        ];
        
        // 3. Générer article_ref (besoin du code du category_type)
        $categoryType = $this->categoryTypeModel->findById((int)$_POST['category_type_id']);
        if (!$categoryType) {
            $_SESSION['form_data'] = $_POST;
            $_SESSION['form_errors']['category_type_id'] = ['Selected category/type is invalid.'];
            Helper::redirect('articles/create');
            return;
        }
        $articleData['article_ref'] = $this->articleModel->getNextArticleRef($categoryType['code']);



        // 4. Créer l'article principal en BDD
        $articleId = $this->articleModel->create($articleData);

        if (!$articleId) {
            $_SESSION['form_data'] = $_POST;
            // On pourrait essayer de récupérer l'erreur PDO ici pour un message plus précis
            // $dbError = $this->articleModel->getLastDbError(); // Si vous implémentez une telle méthode
            Helper::redirect('articles/create', ['danger' => 'Failed to create article (database error).']);
            return;
        }

        // 5. Gérer l'upload des images
        $uploadedImagePaths = [];
        if (isset($_FILES['article_images'])) {
            $imageUploader = new ImageUploader($this->articleImageUploadPath); // 'articles/'
            $files = $_FILES['article_images'];
            $fileCount = count($files['name']);

            for ($i = 0; $i < $fileCount; $i++) {
                if ($files['error'][$i] === UPLOAD_ERR_OK) {
                    // Construire un nom de fichier unique basé sur article_ref et un index/timestamp
                    // Ex: VRO00001_1.jpg, VRO00001_2.jpg
                    $imageFileNameWithoutExtension = str_replace(' ', '_', $articleData['article_ref']) . '_' . ($i + 1) . '_' . time();
                    
                    // Simuler la structure de fichier pour la méthode upload
                    $currentFile = [
                        'name' => $files['name'][$i],
                        'type' => $files['type'][$i],
                        'tmp_name' => $files['tmp_name'][$i],
                        'error' => $files['error'][$i],
                        'size' => $files['size'][$i]
                    ];

                    if ($imageUploader->upload($currentFile, $imageFileNameWithoutExtension)) {
                        $dbImagePath = $imageUploader->getUploadedFileName();
                        // Enregistrer l'image dans la table article_images
                        $isPrimary = ($i == 0 && empty($_POST['primary_image_id'])); // Première image uploadée est primaire par défaut si aucune sélectionnée
                        $this->articleModel->addImage($articleId, $dbImagePath, null, $isPrimary);
                        $uploadedImagePaths[] = $dbImagePath; // Pour rollback si besoin
                    } else {
                        // Gérer les erreurs d'upload pour cette image spécifique
                        // Peut-être accumuler les erreurs et les afficher
                        error_log("Failed to upload image: " . $files['name'][$i] . " Errors: " . implode(', ', $imageUploader->getErrors()));
                        // On continue avec les autres images
                    }
                } elseif ($files['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                     error_log("Upload error for file " . $files['name'][$i] . ": code " . $files['error'][$i]);
                }
            }
        }

        // 6. Gérer les articles associés
        $associatedArticleIds = $_POST['associated_article_ids'] ?? [];
        if (!empty($associatedArticleIds)) {
            $this->articleModel->syncAssociatedArticles($articleId, $associatedArticleIds);
        }

        //Helper::redirect('articles/show/' . $articleId, ['success' => 'Article created successfully!']);
		Helper::redirect('articles/log_event/' . $articleId, ['success' => 'Article created! Now, please log its initial status.']);
    }

    public function log_event(int $articleId): void { // C'est le formulaire pour créer une entrée event_log
        $article = $this->articleModel->findById($articleId);
        if (!$article) {
            Helper::redirect('articles', ['danger' => "Article with ID {$articleId} not found."]);
            return;
        }

        //$allStatuses = $this->statusModel->getAll('id', 'ASC'); // Nouveau tri par ID

		$allStatusesRaw = $this->statusModel->getAll('id', 'ASC');
		$availableStatusesForNewEvent = [];

		// ID ou Nom du statut "Acheté (Nouveau)" (Idéalement, utilisez un ID constant)
		// $statusNameNewPurchase = 'Acheté (Nouveau)'; 
		$statusIdNewPurchase = defined('STATUS_ID_NEW_PURCHASE') ? STATUS_ID_NEW_PURCHASE : null; // Si vous définissez une constante pour l'ID

		// Filtrer la liste des statuts pour le formulaire de log_event
		// Si le statut actuel est "Acheté (Nouveau)", on ne le propose pas comme nouveau statut.
		if ($article['current_status_id'] == STATUS_ID_NEW_PURCHASE) { // Comparer les IDs
			foreach ($allStatusesRaw as $status) {
				if ($status['id'] != STATUS_ID_NEW_PURCHASE) {
					$availableStatusesForNewEvent[] = $status;
				}
			}
		} else {
			// Si le statut actuel n'est pas "Acheté (Nouveau)", on peut potentiellement
			// re-sélectionner le même statut pour juste loguer un événement sans changer de statut,
			// ou vous pourriez aussi l'exclure si un changement est toujours attendu.
			// Pour l'instant, on les garde tous sauf "Acheté (Nouveau)" s'il n'est pas l'actuel.
			 foreach ($allStatusesRaw as $status) {
				// Optionnel: si vous ne voulez jamais revenir à "Acheté (Nouveau)" après l'avoir quitté
				// if ($status['name'] !== $statusNameNewPurchase) {
					$availableStatusesForNewEvent[] = $status;
				// }
			}
			// Ou plus simple pour ce cas : $availableStatusesForNewEvent = $allStatusesRaw;
		}
		// Si $availableStatusesForNewEvent est vide (cas peu probable), il faut le gérer.

        $allEventTypes = $this->eventTypeModel->getAll('name', 'ASC');
        $allItemUsers = $this->itemUserModel->getAll('name', 'ASC');
        $allSuppliers = $this->supplierModel->getAll('name', 'ASC');
        $allStorageLocations = $this->storageLocationModel->getAll('full_location_path', 'ASC');
        
        // Pour la création d'un "grouped event"
        // On pourrait lister les grouped_events récents pour permettre de lier à un existant
        // Pour l'instant, on se concentre sur la création d'un nouveau log.

        $this->renderView('articles/log_event_form', [
            'pageTitle' => 'Log New Event for: ' . Helper::e($article['name']),
            'article' => $article, // Contient le statut actuel via $article['status_name']
			'availableStatuses' => $availableStatusesForNewEvent, // Liste filtrée
            'allEventTypes' => $allEventTypes,
            'allItemUsers' => $allItemUsers,
            'allSuppliers' => $allSuppliers,
            'allStorageLocations' => $allStorageLocations,
            'formAction' => APP_URL . '/articles/store_event/' . $articleId,
            'csrfToken' => $_SESSION[CSRF_TOKEN_NAME]
        ]);
    }

    public function store_event(int $articleId): void {
        $this->verifyCsrf();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Helper::redirect('articles/log_event/' . $articleId);
            return;
        }

        $article = $this->articleModel->findById($articleId); // Recharger l'article pour infos à jour
        if (!$article) {
            Helper::redirect('articles', ['danger' => "Article not found."]);
            return;
        }

        // 1. Validation
        $validator = new Validation($_POST); // Pas de modèle spécifique pour la validation globale ici
        $rules = [
            'new_status_id' => 'required|numeric', // ID du nouveau statut
            'log_date' => 'required|date_or_empty', // date_or_empty vérifie le format si non vide
            'log_time' => 'time_or_empty', // Règle à ajouter : regex /^([01]\d|2[0-3]):([0-5]\d)(:([0-5]\d))?$/
            // Champs conditionnels (validés ici, mais leur nécessité est vérifiée plus bas)
            'event_type_id_event' => 'numeric_or_empty',
            'item_user_id_event' => 'numeric_or_empty',
            'related_supplier_id_event' => 'numeric_or_empty',
            'storage_location_id_event' => 'numeric_or_empty',
            'cost_associated_event' => 'decimal_or_empty:2',
        ];
        // Vous devrez ajouter la règle `time_or_empty` à Validation.php

        $newStatusId = (int)($_POST['new_status_id'] ?? 0);
        $selectedStatus = $this->statusModel->findById($newStatusId); // Récupérer le statut sélectionné

        // Logique pour rendre des champs requis conditionnellement
        $showEventType = false; $showItemUser = false;
        $showSupplierPrice = false; $showStorageLocation = false;

        if ($selectedStatus) {
            $statusName = strtolower($selectedStatus['name']);
            if (str_contains($statusName, 'utilisation') || str_contains($statusName, 'porté')) {
                $rules['event_type_id_event'] = 'required|numeric'; // Type d'événement requis pour utilisation
                $rules['item_user_id_event'] = 'required|numeric';  // Utilisateur requis pour utilisation
                $showEventType = true; $showItemUser = true;
            }
            if (str_contains($statusName, 'nettoyage') || str_contains($statusName, 'réparation') || str_contains($statusName, 'vendu')) {
                $rules['related_supplier_id_event'] = 'numeric_or_empty'; // Fournisseur optionnel mais possible
                $rules['cost_associated_event'] = 'decimal_or_empty:2'; // Prix possible
                $showSupplierPrice = true;
            }
            if (str_contains($statusName, 'disponible et rangé')) {
                $rules['storage_location_id_event'] = 'required|numeric';
                $showStorageLocation = true;
            }
        }
        $validator->setRules($rules, [
            'event_type_id_event.required' => 'Event type is required when status is "En cours d\'utilisation".',
            'item_user_id_event.required' => 'Item user is required when status is "En cours d\'utilisation".',
            'storage_location_id_event.required' => 'Storage location is required when status is "Disponible et rangé".'
        ]);

        if (!$validator->validate()) {
            $_SESSION['form_data_event'] = $_POST;
            $_SESSION['form_errors_event'] = $validator->getErrors();
            Helper::redirect('articles/log_event/' . $articleId);
            return;
        }

        // 2. Préparer les données pour event_log
        $eventData = [
            // article_id sera ajouté par le modèle recordArticleEvent
            'log_date' => $_POST['log_date'],
            'log_time' => !empty(trim($_POST['log_time'] ?? '')) ? trim($_POST['log_time']) : null,
            'status_id' => $newStatusId,
            'event_type_id' => ($showEventType && !empty($_POST['event_type_id_event'])) ? (int)$_POST['event_type_id_event'] : null,
            'event_name' => trim($_POST['event_name_event'] ?? '') ?: null,
            'description' => trim($_POST['description_event'] ?? '') ?: null,
            'item_user_id' => ($showItemUser && !empty($_POST['item_user_id_event'])) ? (int)$_POST['item_user_id_event'] : null,
            'related_supplier_id' => ($showSupplierPrice && !empty($_POST['related_supplier_id_event'])) ? (int)$_POST['related_supplier_id_event'] : null,
            'cost_associated' => ($showSupplierPrice && is_numeric($_POST['cost_associated_event'] ?? '')) ? (float)$_POST['cost_associated_event'] : null,
            'currency' => (!empty($_POST['currency_event']) && $showSupplierPrice) ? strtoupper(trim($_POST['currency_event'])) : 'EUR',
            // created_by_app_user_id à gérer avec l'authentification
        ];

        // 3. Préparer les données pour mettre à jour l'article principal
        $articleUpdateData = [
            'current_status_id' => $newStatusId,
            'current_storage_location_id' => ($showStorageLocation && !empty($_POST['storage_location_id_event'])) ? (int)$_POST['storage_location_id_event'] : $article['current_storage_location_id'], // Conserver l'ancien si pas rangé
        ];
         if ($articleUpdateData['current_storage_location_id'] === $article['current_storage_location_id'] && !$showStorageLocation) {
            // Si le statut n'est PAS "rangé" ET que la localisation n'a pas changé dans le form (car champ caché ou non rempli)
            // alors on veut explicitement mettre la localisation à NULL ou la conserver.
            // Si on veut la vider quand l'article n'est plus rangé :
            $articleUpdateData['current_storage_location_id'] = null;
        }


        // Gérer last_worn_at et times_worn si le statut est une "utilisation"
        if ($selectedStatus && (str_contains(strtolower($selectedStatus['name']), 'utilisation') || str_contains(strtolower($selectedStatus['name']), 'porté'))) {
            $articleUpdateData['last_worn_at'] = $_POST['log_date'] . (!empty($eventData['log_time']) ? ' ' . $eventData['log_time'] : ' 00:00:00'); // Utiliser la date de l'événement
            $articleUpdateData['increment_times_worn'] = true; // Le modèle gérera l'incrémentation
        }

        // 4. Gérer les images d'événement
        $eventImageFiles = $_FILES['event_images'] ?? []; // Doit être `name="event_images[]"` dans le form

        // 5. Gérer la création/liaison d'un événement groupé (simplifié pour l'instant)
        $groupedEventId = null;
        if (!empty($_POST['link_to_grouped_event_id'])) { // Si on lie à un existant
            $groupedEventId = (int)$_POST['link_to_grouped_event_id'];
        } elseif (!empty($_POST['create_new_grouped_event_name']) && $showEventType) { // Si on crée un nouveau
            $eventLogModel = new EventLog(); // Pour appeler createGroupedEvent
            $groupData = [
                'group_event_name' => trim($_POST['create_new_grouped_event_name']),
                'group_event_date' => $_POST['log_date'],
                'group_event_time' => $eventData['log_time'],
                'notes' => trim($_POST['grouped_event_notes'] ?? '') ?: null
            ];
            $groupedEventId = $eventLogModel->createGroupedEvent($groupData);
            if (!$groupedEventId) {
                 // Gérer l'erreur de création du groupe, mais continuer pour le log principal ?
                error_log("Failed to create grouped event.");
            }
        }


        if ($this->articleModel->recordArticleEvent($articleId, $eventData, $articleUpdateData, $eventImageFiles, $groupedEventId)) {
            Helper::redirect('articles/show/' . $articleId, ['success' => 'Article status updated and event logged.']);
        } else {
            $_SESSION['form_data_event'] = $_POST;
            // Les erreurs de validation devraient déjà être dans $_SESSION['form_errors_event']
            Helper::redirect('articles/log_event/' . $articleId, ['danger' => 'Failed to log event or update article.']);
        }
    }
}