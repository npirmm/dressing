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
// ItemUser sera utile pour la gestion de l'historique/événements, pas forcément dans ce formulaire initial
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
        $statuses = (new Status())->getAll('id', 'ASC'); // Instanciation directe si pas déjà dans $this
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

        // 1. Validation des données de base
        // Le modèle Article sera utilisé pour des vérifications 'exists' si besoin, mais pas pour 'unique' sur les champs directs d'article pour l'instant
        $validator = new Validation($_POST, null); // Pas de modèle pour unique sur 'name' d'article pour l'instant
        
        // Définir les règles de validation
        // Note: Les ID des entités liées (brand_id, color_id, etc.) doivent être validés comme 'numeric'
        // et potentiellement avec une règle 'exists:tableName,idColumn' si vous l'implémentez dans Validation.php
        $rules = [
            'name' => 'required|max:150',
            'category_type_id' => 'required|numeric', // Doit exister dans categories_types
            'current_status_id' => 'required|numeric', // Doit exister dans statuses
            'condition' => 'required|in:neuf,excellent,bon état,médiocre,à réparer/retoucher',
            
            // Champs optionnels mais avec validation si fournis
            'brand_id' => 'numeric_or_empty', // Règle custom à ajouter si besoin, ou juste numeric
            'primary_color_id' => 'numeric_or_empty',
            'secondary_color_id' => 'numeric_or_empty',
            'material_id' => 'numeric_or_empty',
            'current_storage_location_id' => 'numeric_or_empty',
            'supplier_id' => 'numeric_or_empty',
            'season' => 'in:Printemps,Été,Automne,Hiver,Toutes saisons,Entre-saisons,', // Laisser la virgule pour 'empty'
            'size' => 'max:50',
            'weight_grams' => 'numeric_or_empty|min_numeric:0', // Règle custom à ajouter
            'purchase_date' => 'date_or_empty', // Règle custom à ajouter
            'purchase_price' => 'decimal_or_empty:2', // Règle custom (decimal avec 2 décimales)
            'estimated_value' => 'decimal_or_empty:2',
            'rating' => 'numeric_or_empty|min_numeric:0|max_numeric:5', // Règle custom
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

        // 2. Préparer les données pour la création de l'article principal
        $postedSeason = trim($_POST['season'] ?? ''); // Récupérer la valeur postée
        $articleData = [
            'name' => trim($_POST['name']),
            'description' => trim($_POST['description'] ?? '') ?: null,
            'season' => !empty($postedSeason) ? $postedSeason : null, // Si vide, stocker NULL
            'category_type_id' => (int)$_POST['category_type_id'],
            'brand_id' => !empty($_POST['brand_id']) ? (int)$_POST['brand_id'] : null,
            'condition' => $_POST['condition'],
            'primary_color_id' => !empty($_POST['primary_color_id']) ? (int)$_POST['primary_color_id'] : null,
            'secondary_color_id' => !empty($_POST['secondary_color_id']) ? (int)$_POST['secondary_color_id'] : null,
            'material_id' => !empty($_POST['material_id']) ? (int)$_POST['material_id'] : null,
            'size' => trim($_POST['size'] ?? '') ?: null,
            'weight_grams' => !empty($_POST['weight_grams']) ? (int)$_POST['weight_grams'] : null,
            'current_storage_location_id' => !empty($_POST['current_storage_location_id']) ? (int)$_POST['current_storage_location_id'] : null,
            'current_status_id' => (int)$_POST['current_status_id'],
            'purchase_date' => !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : null,
            'purchase_price' => !empty($_POST['purchase_price']) ? (float)$_POST['purchase_price'] : null,
            'supplier_id' => !empty($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : null,
            'estimated_value' => !empty($_POST['estimated_value']) ? (float)$_POST['estimated_value'] : null,
            'rating' => !empty($_POST['rating']) ? (int)$_POST['rating'] : null,
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

        Helper::redirect('articles/show/' . $articleId, ['success' => 'Article created successfully!']);
    }

    // La méthode update() sera très similaire mais avec des vérifications supplémentaires
}