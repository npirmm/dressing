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
// Validation et ImageUploader seront utilisés dans store/update

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

    // store(), update(), delete() viendront ensuite
}