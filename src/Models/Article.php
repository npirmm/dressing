<?php
// src/Models/Article.php

namespace App\Models;

use App\Core\Database;
use App\Utils\Helper; // Pour le logging plus tard
use PDO;

class Article {
    private Database $dbInstance;
    private string $tableName = 'articles';
    private string $pivotSuitableEventTypesTable = 'article_suitable_event_types';
	
    public function __construct() {
        $this->dbInstance = Database::getInstance();
    }

    public function findById(int $id): array|false {
        // Pour l'affichage détaillé, on veut récupérer les noms des entités liées
        $sql = "SELECT 
                    a.*, 
                    ct.name as category_type_name, ct.category as category_group, ct.code as category_type_code,
                    b.name as brand_name,
                    pc.name as primary_color_name, pc.hex_code as primary_color_hex,
                    sc.name as secondary_color_name, sc.hex_code as secondary_color_hex,
                    m.name as material_name,
                    sl.full_location_path as storage_location_full_path,
                    st.name as status_name,
                    sup.name as supplier_name
                FROM {$this->tableName} a
                LEFT JOIN categories_types ct ON a.category_type_id = ct.id
                LEFT JOIN brands b ON a.brand_id = b.id
                LEFT JOIN colors pc ON a.primary_color_id = pc.id
                LEFT JOIN colors sc ON a.secondary_color_id = sc.id
                LEFT JOIN materials m ON a.material_id = m.id
                LEFT JOIN storage_locations sl ON a.current_storage_location_id = sl.id
                LEFT JOIN statuses st ON a.current_status_id = st.id
                LEFT JOIN suppliers sup ON a.supplier_id = sup.id
                WHERE a.id = :id";
        
        $stmt = $this->dbInstance->query($sql, [':id' => $id]);
        $article = $stmt ? $stmt->fetch() : false;

        if ($article) {
            $article['images'] = $this->getArticleImages($id);
            $article['associated_article_ids'] = $this->getAssociatedArticleIds($id);
            $article['suitable_event_type_ids'] = $this->getSuitableEventTypeIds($id); // NOUVEAU
        }
        return $article;
    }

    public function getSuitableEventTypeIds(int $articleId): array {
        $sql = "SELECT event_type_id FROM {$this->pivotSuitableEventTypesTable} WHERE article_id = :article_id";
        $stmt = $this->dbInstance->query($sql, [':article_id' => $articleId]);
        return $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];
    }

    public function getArticleImages(int $articleId): array {
        $sql = "SELECT * FROM article_images WHERE article_id = :article_id ORDER BY is_primary DESC, sort_order ASC, id ASC";
        $stmt = $this->dbInstance->query($sql, [':article_id' => $articleId]);
        return $stmt ? $stmt->fetchAll() : [];
    }

    public function getAssociatedArticleIds(int $articleId): array {
        $sql = "SELECT 
                    CASE
                        WHEN article_id_1 = :article_id_case THEN article_id_2
                        ELSE article_id_1
                    END as associated_id
                FROM associated_articles
                WHERE article_id_1 = :article_id_where1 OR article_id_2 = :article_id_where2";
        
        $params = [
            ':article_id_case' => $articleId,
            ':article_id_where1' => $articleId,
            ':article_id_where2' => $articleId
        ];
        
        $stmt = $this->dbInstance->query($sql, $params);
        return $stmt ? $stmt->fetchAll(\PDO::FETCH_COLUMN) : []; // Assurez-vous d'avoir \PDO ici
    }

    /**
     * Fetches all articles with basic related data for listing.
     */
    public function getAll(string $sortBy = 'a.name', string $sortOrder = 'ASC', array $filters = []): array {
        // Liste blanche pour le tri
        $allowedSortColumns = [
            'a.id', 'a.name', 'a.article_ref', 'ct.name', 'b.name', 'a.size', 
            'st.name', 'sl.full_location_path', 'a.created_at', 'a.last_worn_at', 'a.times_worn'
        ];
        // Le point dans 'a.name' est important pour la clause ORDER BY quand on a des jointures
        // et que le nom de colonne est ambigu.
        $sortByQualified = $sortBy; // Par défaut, on assume qu'il est déjà qualifié (ex: 'a.name')
        if (!in_array(strtolower($sortBy), $allowedSortColumns)) {
             // Essayer de qualifier si non qualifié et sûr
            if (in_array('a.'.strtolower($sortBy), $allowedSortColumns)) {
                $sortByQualified = 'a.'.strtolower($sortBy);
            } elseif (in_array('ct.'.strtolower($sortBy), $allowedSortColumns)) {
                $sortByQualified = 'ct.'.strtolower($sortBy);
            } elseif (in_array('b.'.strtolower($sortBy), $allowedSortColumns)) {
                 $sortByQualified = 'b.'.strtolower($sortBy);
            } // etc. ou forcer une valeur par défaut plus simple.
            else {
                $sortByQualified = 'a.name'; // Colonne par défaut sûre
            }
        }

        $sortOrderSanitized = strtoupper($sortOrder);
        if ($sortOrderSanitized !== 'ASC' && $sortOrderSanitized !== 'DESC') {
            $sortOrderSanitized = 'ASC';
        }

        $sql = "SELECT 
                    a.id, a.name, a.article_ref, a.size, a.condition,
                    ct.name as category_type_name,
                    b.name as brand_name,
                    pc.hex_code as primary_color_hex,
                    st.name as status_name,
                    (SELECT image_path FROM article_images WHERE article_id = a.id AND is_primary = TRUE LIMIT 1) as primary_image_path
                FROM {$this->tableName} a
                LEFT JOIN categories_types ct ON a.category_type_id = ct.id
                LEFT JOIN brands b ON a.brand_id = b.id
                LEFT JOIN colors pc ON a.primary_color_id = pc.id
                LEFT JOIN statuses st ON a.current_status_id = st.id
                -- WHERE et filtres (à venir)
                ORDER BY {$sortByQualified} {$sortOrderSanitized}";
                // Pas de GROUP BY ici pour la liste simple, sauf si on ajoute des agrégats

        // Gestion des filtres (sera ajoutée plus tard)
        $params = [];
        // if (!empty($filters['name_like'])) {
        //    $sqlWhere[] = "a.name LIKE :name_like";
        //    $params[':name_like'] = '%' . $filters['name_like'] . '%';
        // }
        // ... if ($sqlWhere) $sql .= " WHERE " . implode(' AND ', $sqlWhere); ...

        $stmt = $this->dbInstance->query($sql, $params);
        return $stmt ? $stmt->fetchAll() : [];
    }

    // Méthodes create, update, delete, gestion des images, articles associés, article_ref viendront plus tard.
    // Placeholder pour la génération de référence, à développer
    public function getNextArticleRef(string $categoryTypeCode): string {
        // 1. Trouver le dernier numéro pour ce code de catégorie
        $sql = "SELECT MAX(SUBSTRING(article_ref, " . (strlen($categoryTypeCode) + 1) . ")) AS max_num
                FROM {$this->tableName} 
                WHERE article_ref LIKE :code_prefix";
        $stmt = $this->dbInstance->query($sql, [':code_prefix' => $categoryTypeCode . '%']);
        $result = $stmt ? $stmt->fetch() : null;
        
        $nextNum = $result && $result['max_num'] !== null ? (int)$result['max_num'] + 1 : 1;
        
        // 2. Formater avec des zéros en tête (ex: 00001)
        return $categoryTypeCode . str_pad((string)$nextNum, 5, '0', STR_PAD_LEFT);
    }

    public function create(array $data): int|false {
        $sql = "INSERT INTO {$this->tableName} (
                    name, article_ref, description, season, category_type_id, brand_id, `condition`, 
                    primary_color_id, secondary_color_id, material_id, size, weight_grams, 
                    current_storage_location_id, current_status_id, purchase_date, purchase_price, 
                    supplier_id, estimated_value, rating, notes, 
                    created_at, updated_at
                ) VALUES (
                    :name, :article_ref, :description, :season, :category_type_id, :brand_id, :condition, 
                    :primary_color_id, :secondary_color_id, :material_id, :size, :weight_grams, 
                    :current_storage_location_id, :current_status_id, :purchase_date, :purchase_price, 
                    :supplier_id, :estimated_value, :rating, :notes, 
                    NOW(), NOW()
                )";
        
        // Note: `condition` est un mot-clé SQL, donc il est entouré de backticks dans la requête
        // mais pas dans les clés de $data car ce sont des clés de tableau PHP.
        $params = [
            ':name' => $data['name'],
            ':article_ref' => $data['article_ref'],
            ':description' => $data['description'],
            ':season' => $data['season'],
            ':category_type_id' => $data['category_type_id'],
            ':brand_id' => $data['brand_id'],
            ':condition' => $data['condition'],
            ':primary_color_id' => $data['primary_color_id'],
            ':secondary_color_id' => $data['secondary_color_id'],
            ':material_id' => $data['material_id'],
            ':size' => $data['size'],
            ':weight_grams' => $data['weight_grams'],
            ':current_storage_location_id' => $data['current_storage_location_id'],
            ':current_status_id' => $data['current_status_id'],
            ':purchase_date' => $data['purchase_date'],
            ':purchase_price' => $data['purchase_price'],
            ':supplier_id' => $data['supplier_id'],
            ':estimated_value' => $data['estimated_value'],
            ':rating' => $data['rating'],
            ':notes' => $data['notes']
        ];

        $stmt = $this->dbInstance->query($sql, $params);
        if ($stmt) {
            $id = (int)$this->dbInstance->lastInsertId();
            Helper::logAction(strtoupper($this->tableName).'_CREATE', ucfirst($this->tableName), $id, "Article '{$data['name']}' ({$data['article_ref']}) created.");
            return $id;
        }
        return false;
    }

    // Nouvelle méthode pour synchroniser les types d'événements adaptés
    public function syncSuitableEventTypes(int $articleId, array $eventTypeIds): void {
        // 1. Supprimer les anciennes associations
        $this->dbInstance->query("DELETE FROM {$this->pivotSuitableEventTypesTable} WHERE article_id = :article_id", [':article_id' => $articleId]);

        // 2. Ajouter les nouvelles associations
        if (!empty($eventTypeIds)) {
            $sqlInsert = "INSERT INTO {$this->pivotSuitableEventTypesTable} (article_id, event_type_id) VALUES (:article_id, :event_type_id)";
            foreach ($eventTypeIds as $eventTypeId) {
                try {
                    $this->dbInstance->query($sqlInsert, [
                        ':article_id' => $articleId,
                        ':event_type_id' => (int)$eventTypeId
                    ]);
                } catch (\PDOException $e) {
                    if ($e->getCode() != 23000) throw $e; // Ignorer les erreurs de clé dupliquée (ne devrait pas arriver avec la suppression)
                }
            }
        }
    }

    public function addImage(int $articleId, string $imagePath, ?string $caption = null, bool $isPrimary = false, int $sortOrder = 0): bool {
        // Si cette image est marquée comme primaire, s'assurer qu'aucune autre n'est primaire pour cet article
        if ($isPrimary) {
            $this->dbInstance->query("UPDATE article_images SET is_primary = FALSE WHERE article_id = :article_id", [':article_id' => $articleId]);
        }

        $sql = "INSERT INTO article_images (article_id, image_path, caption, is_primary, sort_order) 
                VALUES (:article_id, :image_path, :caption, :is_primary, :sort_order)";
        $stmt = $this->dbInstance->query($sql, [
            ':article_id' => $articleId,
            ':image_path' => $imagePath,
            ':caption' => $caption,
            ':is_primary' => (int)$isPrimary, // Convertir booléen en int pour la BDD
            ':sort_order' => $sortOrder
        ]);
        return $stmt !== false;
    }

    public function syncAssociatedArticles(int $articleId, array $associatedIds): void {
        $pdo = $this->dbInstance->getConnection(); // Pour les transactions si besoin, ou juste pour exec multiple
        
        // 1. Supprimer les anciennes associations pour cet article
        // Attention: si la relation est symétrique, il faut supprimer dans les deux sens ou avoir une convention
        // Pour A-B, on stocke une seule ligne. (article_id_1 < article_id_2 est une convention)
        // Ici, on supprime toutes les lignes où $articleId apparaît.
        $this->dbInstance->query("DELETE FROM associated_articles WHERE article_id_1 = :id OR article_id_2 = :id", [':id' => $articleId]);

        // 2. Ajouter les nouvelles associations
        if (!empty($associatedIds)) {
            $sqlInsert = "INSERT INTO associated_articles (article_id_1, article_id_2) VALUES (:id1, :id2)";
            foreach ($associatedIds as $assocId) {
                $assocId = (int)$assocId;
                if ($articleId === $assocId) continue; // Ne pas s'associer à soi-même

                // Pour éviter les doublons (A-B et B-A) et les erreurs de clé dupliquée,
                // on stocke toujours avec le plus petit ID en premier.
                $id1 = min($articleId, $assocId);
                $id2 = max($articleId, $assocId);
                
                // Vérifier si l'association existe déjà (pas strictement nécessaire si la PK empêche)
                // $checkSql = "SELECT COUNT(*) FROM associated_articles WHERE article_id_1 = :id1 AND article_id_2 = :id2";
                // $stmtCheck = $this->dbInstance->query($checkSql, [':id1' => $id1, ':id2' => $id2]);
                // if ($stmtCheck && $stmtCheck->fetchColumn() == 0) {
                   try {
                       $this->dbInstance->query($sqlInsert, [':id1' => $id1, ':id2' => $id2]);
                   } catch (\PDOException $e) {
                       // Ignorer les erreurs de clé dupliquée si on essaie d'insérer une paire qui existe déjà
                       // (ce qui ne devrait pas arriver avec la suppression préalable, sauf en cas de concurrence)
                       if ($e->getCode() != 23000) { // 23000 est le code SQLSTATE pour violation de contrainte d'intégrité
                           throw $e; // Relancer les autres erreurs
                       }
                   }
                // }
            }
        }
    }
    /**
     * Enregistre un événement pour un article et met à jour le statut/localisation de l'article.
     * Gère cela dans une transaction.
     * @param int $articleId L'ID de l'article.
     * @param array $eventData Données pour la table event_log.
     * @param array $articleUpdateData Données pour mettre à jour l'article principal.
     * @param array $eventImageFiles Tableau de fichiers d'images pour l'événement (depuis $_FILES).
     * @param int|null $groupedEventId ID optionnel d'un événement groupé à lier.
     * @return bool True en cas de succès, false sinon.
     */
    public function recordArticleEvent(int $articleId, array $eventData, array $articleUpdateData, array $eventImageFiles = [], ?int $groupedEventId = null): bool {
        $pdo = $this->dbInstance->getConnection();
        // L'uploader d'images pour les événements (chemin différent des images d'articles)
        $eventImageUploader = null;
        if (!empty($eventImageFiles) && defined('EVENT_IMAGE_PATH')) { // EVENT_IMAGE_PATH de config/app.php
            $eventImageUploader = new \App\Utils\ImageUploader(EVENT_IMAGE_PATH);
        }

        try {
            $pdo->beginTransaction();

            // 1. Créer l'entrée dans event_log
            $eventLogModel = new EventLog(); // Instancier ici
            $eventData['article_id'] = $articleId; // S'assurer que article_id est bien là
            // created_by_app_user_id sera géré par EventLogModel si non fourni explicitement

            $eventLogId = $eventLogModel->create($eventData);
			
			if ($eventLogId && isset($eventData['event_type_id']) && !empty($eventData['event_type_id'])) {
				$this->ensureSuitableEventTypeExists($articleId, (int)$eventData['event_type_id']);
			}
			
            if (!$eventLogId) {
                $pdo->rollBack();
                error_log("Failed to insert into event_log for article_id: {$articleId}");
                return false;
            }

            // 2. Gérer l'upload des images d'événement et les lier à event_log
            if ($eventImageUploader && !empty($eventImageFiles['name'][0])) { // Vérifier si des fichiers ont été soumis
                $fileCount = count($eventImageFiles['name']);
                for ($i = 0; $i < $fileCount; $i++) {
                    if ($eventImageFiles['error'][$i] === UPLOAD_ERR_OK) {
                        $currentFile = [
                            'name' => $eventImageFiles['name'][$i],
                            'type' => $eventImageFiles['type'][$i],
                            'tmp_name' => $eventImageFiles['tmp_name'][$i],
                            'error' => $eventImageFiles['error'][$i],
                            'size' => $eventImageFiles['size'][$i]
                        ];
                        // Nom de fichier unique pour l'image d'événement
                        $eventImageName = 'event_' . $eventLogId . '_' . time() . '_' . ($i + 1);
                        if ($eventImageUploader->upload($currentFile, $eventImageName)) {
                            $eventLogModel->addImage($eventLogId, $eventImageUploader->getUploadedFileName(), $_POST['event_image_captions'][$i] ?? null);
                        } else {
                            // Gérer l'échec d'upload d'une image d'événement (log, mais continuer la transaction ?)
                            error_log("Failed to upload event image: " . $eventImageFiles['name'][$i] . " Errors: " . implode(', ', $eventImageUploader->getErrors()));
                        }
                    }
                }
            }

            // 3. Lier à un événement groupé si un ID est fourni
            if ($groupedEventId !== null && $eventLogId) {
                if (!$eventLogModel->linkEventToGroup($eventLogId, $groupedEventId)) {
                     error_log("Failed to link event_log {$eventLogId} to grouped_event {$groupedEventId}");
                     // Décider si c'est une erreur bloquante pour la transaction
                }
            }

            // 4. Mettre à jour l'article principal (statut, localisation, etc.)
            $updateClauses = [];
            $articleParams = [':id' => $articleId]; // :id est pour la clause WHERE

            if (isset($articleUpdateData['current_status_id'])) {
                $updateClauses[] = "current_status_id = :update_current_status_id"; // Utiliser des noms de placeholder uniques
                $articleParams[':update_current_status_id'] = $articleUpdateData['current_status_id'];
            }
            if (array_key_exists('current_storage_location_id', $articleUpdateData)) {
                $updateClauses[] = "current_storage_location_id = :update_current_storage_location_id";
                $articleParams[':update_current_storage_location_id'] = $articleUpdateData['current_storage_location_id']; // Peut être NULL
            }
            if (isset($articleUpdateData['last_worn_at'])) {
                 $updateClauses[] = "last_worn_at = " . ($articleUpdateData['last_worn_at'] === 'NOW()' ? "NOW()" : ":update_last_worn_at");
                 if ($articleUpdateData['last_worn_at'] !== 'NOW()') { // Bind seulement si ce n'est pas NOW()
                     $articleParams[':update_last_worn_at'] = $articleUpdateData['last_worn_at'];
                 }
            }
            if (isset($articleUpdateData['increment_times_worn']) && $articleUpdateData['increment_times_worn']) {
                 $updateClauses[] = "times_worn = COALESCE(times_worn, 0) + 1";
            }

            if (!empty($updateClauses)) {
                $articleSql = "UPDATE {$this->tableName} SET " . implode(', ', $updateClauses) . ", updated_at = NOW() WHERE id = :id";
                $stmtArticle = $this->dbInstance->query($articleSql, $articleParams);
                if (!$stmtArticle) {
                    $pdo->rollBack();
                    error_log("Failed to update article (ID: {$articleId}) after logging event. Params: ".json_encode($articleParams)." Error: " . json_encode($this->dbInstance->getConnection()->errorInfo()));
                    return false;
                }
            }
            
            $pdo->commit();
            Helper::logAction('ARTICLE_EVENT_RECORDED', 'Article', $articleId, 'Event (ID: '.$eventLogId.') recorded for article.');
            return true;

        } catch (\Exception $e) {
            $pdo->rollBack();
            error_log("Error in recordArticleEvent for article_id {$articleId}: " . $e->getMessage());
            return false;
        }
    }

    public function getAllPaginated(
        string $sortBy, 
        string $sortOrder, 
        array $filters = [], 
        int $currentPage = 1, 
        int $itemsPerPage = 15
	 ): array {
		$allowedSortColumns = [
			'a.id', 'a.name', 'a.article_ref', 'a.size', 'a.condition', // Champs de la table 'articles' (alias 'a')
			'ct.name', // Nom du CategoryType (alias 'ct')
			'b.name',  // Nom du Brand (alias 'b')
			'st.name', // Nom du Status (alias 'st')
			'a.created_at', 'a.updated_at', 'a.last_worn_at', 'a.times_worn',
			'pc.base_color_category' // NOUVEAU : pour trier par la catégorie de couleur de base de la couleur primaire
			// Note: 'sl.full_location_path' n'est pas dans la requête SELECT de getAllPaginated, donc on ne peut pas trier dessus ici.
		];
		
		$sortByQualified = 'a.updated_at'; // Défaut sûr
		$sortOrderSanitized = (strtoupper($sortOrder) === 'DESC') ? 'DESC' : 'ASC';

		// Logique de qualification améliorée
		$potentialQualifiedSortBy = strtolower($sortBy);
		if (strpos($potentialQualifiedSortBy, '.') === false) { // Si pas d'alias fourni
			if (in_array('a.' . $potentialQualifiedSortBy, $allowedSortColumns)) {
				$sortByQualified = 'a.' . $potentialQualifiedSortBy;
			} elseif (in_array('ct.' . $potentialQualifiedSortBy, $allowedSortColumns)) {
				$sortByQualified = 'ct.' . $potentialQualifiedSortBy;
			} elseif (in_array('b.' . $potentialQualifiedSortBy, $allowedSortColumns)) {
				$sortByQualified = 'b.' . $potentialQualifiedSortBy;
			} elseif (in_array('st.' . $potentialQualifiedSortBy, $allowedSortColumns)) {
				$sortByQualified = 'st.' . $potentialQualifiedSortBy;
			}
			// Ajoutez d'autres alias si nécessaire
		} elseif (in_array($potentialQualifiedSortBy, $allowedSortColumns)) { // Si alias déjà fourni et valide
			$sortByQualified = $potentialQualifiedSortBy;
		}
		// Si rien ne correspond, $sortByQualified reste à sa valeur par défaut 'a.updated_at'

		$selectFields = "a.id, a.name, a.article_ref, a.size, a.condition,
						 ct.name as category_type_name, /* ct.name */
						 b.name as brand_name, /* b.name */
						 pc.hex_code as primary_color_hex,
						 pc.base_color_category as primary_base_color_category, /* NOUVEAU ou s'assurer qu'il est là */
						 st.name as status_name, /* st.name */
						 a.created_at, a.updated_at, a.last_worn_at, a.times_worn, /* Ajout des champs pour tri */
						 (SELECT image_path FROM article_images WHERE article_id = a.id AND is_primary = TRUE LIMIT 1) as primary_image_path";
        
        $fromAndJoins = "FROM {$this->tableName} a
						 LEFT JOIN categories_types ct ON a.category_type_id = ct.id
						 LEFT JOIN brands b ON a.brand_id = b.id
						 LEFT JOIN colors pc ON a.primary_color_id = pc.id " . //{/* Jointure pour la couleur primaire */}
						 "LEFT JOIN statuses st ON a.current_status_id = st.id";

        $whereClauses = [];
        $params = [];

        if (!empty($filters['name_ref_desc'])) {
            $searchTerm = '%' . $filters['name_ref_desc'] . '%';
            $whereClauses[] = "(a.name LIKE :search_name OR a.article_ref LIKE :search_ref OR a.description LIKE :search_desc)";
            $params[':search_name'] = $searchTerm;
            $params[':search_ref'] = $searchTerm;
            $params[':search_desc'] = $searchTerm;
        }
        if (!empty($filters['category_type_id'])) {
            $whereClauses[] = "a.category_type_id = :category_type_id";
            $params[':category_type_id'] = (int)$filters['category_type_id'];
        }
        if (!empty($filters['brand_id'])) {
            $whereClauses[] = "a.brand_id = :brand_id";
            $params[':brand_id'] = (int)$filters['brand_id'];
        }
        if (!empty($filters['status_id'])) {
            $whereClauses[] = "a.current_status_id = :status_id";
            $params[':status_id'] = (int)$filters['status_id'];
        }
        if (!empty($filters['season'])) {
            $whereClauses[] = "a.season = :season";
            $params[':season'] = $filters['season'];
        }
        if (!empty($filters['condition'])) {
            $whereClauses[] = "a.`condition` = :condition"; // Backticks pour 'condition'
            $params[':condition'] = $filters['condition'];
        }

		// filtre pour base_color_category
		if (!empty($filters['base_color_category'])) {
			$whereClauses[] = "pc.base_color_category LIKE :base_color_category";
			$params[':base_color_category'] = '%' . $filters['base_color_category'] . '%';
		}
	
        // Ajoutez d'autres filtres ici (couleur, matière, etc.)

        $whereSql = "";
        if (!empty($whereClauses)) {
            $whereSql = " WHERE " . implode(" AND ", $whereClauses);
        }

        // Compter le total d'éléments pour la pagination (AVANT LIMIT)
        $totalSql = "SELECT COUNT(a.id) as total {$fromAndJoins} {$whereSql}";
        $totalStmt = $this->dbInstance->query($totalSql, $params);
        $totalItems = $totalStmt ? (int)$totalStmt->fetch()['total'] : 0;

        // Calculer l'offset pour la pagination
        $offset = ($currentPage - 1) * $itemsPerPage;

        // Requête principale avec filtres, tri et pagination
		$sql = "SELECT {$selectFields} {$fromAndJoins} {$whereSql} 
				ORDER BY {$sortByQualified} {$sortOrderSanitized} 
				LIMIT {$itemsPerPage} OFFSET {$offset}";
        
        $stmt = $this->dbInstance->query($sql, $params);
        $data = $stmt ? $stmt->fetchAll() : [];

        return [
            'data' => $data,
            'total' => $totalItems,
            'currentPage' => $currentPage,
            'itemsPerPage' => $itemsPerPage
        ];
    }
 
	public function ensureSuitableEventTypeExists(int $articleId, int $eventTypeIdToAdd): void {
		$sqlCheck = "SELECT COUNT(*) FROM {$this->pivotSuitableEventTypesTable} 
					 WHERE article_id = :article_id AND event_type_id = :event_type_id";
		$stmtCheck = $this->dbInstance->query($sqlCheck, [':article_id' => $articleId, ':event_type_id' => $eventTypeIdToAdd]);
		
		if ($stmtCheck && $stmtCheck->fetchColumn() == 0) {
			// L'association n'existe pas, on l'ajoute
			$sqlInsert = "INSERT INTO {$this->pivotSuitableEventTypesTable} (article_id, event_type_id) 
						  VALUES (:article_id, :event_type_id)";
			try {
				$this->dbInstance->query($sqlInsert, [':article_id' => $articleId, ':event_type_id' => $eventTypeIdToAdd]);
			} catch (\PDOException $e) {
				// Gérer les erreurs, par ex. si event_type_id n'est pas valide (ne devrait pas arriver si bien validé avant)
				error_log("Failed to auto-add suitable event type: " . $e->getMessage());
			}
		}
	}

    // update() et delete() viendront après
}