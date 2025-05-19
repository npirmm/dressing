<?php
// src/Models/Article.php

namespace App\Models;

use App\Core\Database;
use App\Utils\Helper; // Pour le logging plus tard
use PDO;

class Article {
    private Database $dbInstance;
    private string $tableName = 'articles';

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
            // Récupérer les images associées
            $article['images'] = $this->getArticleImages($id);
            // Récupérer les articles associés (juste les IDs pour l'instant, on affinera)
            $article['associated_article_ids'] = $this->getAssociatedArticleIds($id);
        }
        return $article;
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
    // update() et delete() viendront après
}