-- Database: dressing_manager_db
-- CREATE DATABASE IF NOT EXISTS dressing_manager_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE dressing_manager_db;

-- Table for application users (even if management is later, needed for logging)
CREATE TABLE `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'editor', 'viewer') NOT NULL DEFAULT 'viewer', -- Example roles
  `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_login_at` TIMESTAMP NULL DEFAULT NULL
  -- Fields for 2FA will be added later
  -- otp_secret VARCHAR(255) NULL,
  -- otp_enabled BOOLEAN NOT NULL DEFAULT FALSE,
  -- otp_backup_codes TEXT NULL -- Store as encrypted JSON or hashed if sensitive
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert a default system/admin user for initial logging or development
-- IMPORTANT: Change password in a real environment!
INSERT INTO `users` (`username`, `email`, `password_hash`, `role`, `is_active`) VALUES
('admin', 'admin@dressing.com', 'dc1e7c03e162397b355b6f1c895dfdf3790d98c10b920c55e91272b8eecada2a', 'admin', TRUE), -- Replace with a real hash
('system_logger', 'system@internal.log', 'N/A', 'admin', FALSE); -- A non-login user for system-generated logs if needed

-- Table for logging user actions
CREATE TABLE `action_logs` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NULL, -- Nullable if action can be system-generated or pre-auth
  `action_type` VARCHAR(100) NOT NULL COMMENT 'e.g., ARTICLE_CREATE, ARTICLE_UPDATE, USER_LOGIN',
  `entity_type` VARCHAR(50) NULL COMMENT 'e.g., Article, User',
  `entity_id` INT UNSIGNED NULL COMMENT 'ID of the affected entity',
  `description` TEXT NULL COMMENT 'Human-readable description of the action',
  `details` JSON NULL COMMENT 'Additional details about the action, like changed fields (old/new values)',
  `ip_address` VARCHAR(45) NULL,
  `user_agent` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: categories_types
-- Stores the type of article (robe, pantalon) and its broader category (vêtement, chaussure)
CREATE TABLE `categories_types` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL COMMENT 'e.g., Robe, Pantalon, Bague',
  `category` ENUM('vêtement', 'chaussures', 'bijou', 'accessoire') NOT NULL COMMENT 'Broad category',
  `code` VARCHAR(5) NOT NULL UNIQUE COMMENT 'e.g., VRO, VPA, BBA - Used for article_ref generation',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: brands
CREATE TABLE `brands` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `abbreviation` VARCHAR(20) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: colors
CREATE TABLE `colors` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL UNIQUE COMMENT 'e.g., Noir, Rouge cardinal',
  `hex_code` VARCHAR(7) NULL COMMENT 'e.g., #000000, #B82010',
  `base_color_category` VARCHAR(30) NULL COMMENT 'e.g., noir, rouge - for grouping',
  `image_filename` VARCHAR(255) NULL COMMENT 'e.g., 000000-Aile_de_corbeau.png - store only filename, path is config',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: materials
CREATE TABLE `materials` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: suppliers
CREATE TABLE `suppliers` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `contact_person` VARCHAR(100) NULL,
  `email` VARCHAR(100) NULL,
  `phone` VARCHAR(30) NULL,
  `address` TEXT NULL,
  `notes` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: item_users (people who wear/use items, not app users)
CREATE TABLE `item_users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `abbreviation` VARCHAR(20) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: storage_locations
CREATE TABLE `storage_locations` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `room` VARCHAR(100) NOT NULL,
  `area` VARCHAR(100) NULL COMMENT 'e.g., Closet A, Dresser B',
  `shelf_or_rack` VARCHAR(100) NULL COMMENT 'e.g., Rayonnage A, Tiroir 3',
  `level_or_section` VARCHAR(100) NULL COMMENT 'e.g., Étage 2, Compartiment gauche',
  `specific_spot_or_box` VARCHAR(100) NULL COMMENT 'e.g., Boîte à chapeaux, Cintre 5',
  `full_location_path` VARCHAR(500) AS (CONCAT_WS(' > ', `room`, `area`, `shelf_or_rack`, `level_or_section`, `specific_spot_or_box`)) STORED, -- Generated column
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: statuses (for articles)
CREATE TABLE `statuses` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL UNIQUE COMMENT 'e.g., Disponible et rangé, En cours d''utilisation',
  `availability_type` ENUM('in_stock', 'out_of_stock', 'limbo') NOT NULL COMMENT 'Derived from in_out: in=in_stock, out=out_of_stock/limbo',
  `description` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: event_types
CREATE TABLE `event_types` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE COMMENT 'e.g., bal, cocktail, déjeuner',
  `typical_day_moments` VARCHAR(255) NULL COMMENT 'e.g., soir, nuit / matin, midi, après-midi - comma separated',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: articles
CREATE TABLE `articles` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL COMMENT 'Courte description / nom principal de l''article',
  `article_ref` VARCHAR(20) NULL UNIQUE COMMENT 'e.g., VRO00001 - Generated: category_type.code + sequence',
  `description` TEXT NULL COMMENT 'Description longue',
  `season` ENUM('printemps', 'été', 'automne', 'hiver', 'toutes saisons', 'entre-saisons') NULL,
  `category_type_id` INT UNSIGNED NOT NULL,
  `brand_id` INT UNSIGNED NULL,
  `condition` ENUM('neuf', 'excellent', 'bon état', 'médiocre', 'à réparer/retoucher') NOT NULL DEFAULT 'bon état',
  `primary_color_id` INT UNSIGNED NULL, -- Main color
  `secondary_color_id` INT UNSIGNED NULL, -- Optional secondary color
  `material_id` INT UNSIGNED NULL, -- Main material
  `size` VARCHAR(50) NULL COMMENT 'e.g., L, 44, Taille Unique',
  `weight_grams` INT UNSIGNED NULL,
  `current_storage_location_id` INT UNSIGNED NULL,
  `current_status_id` INT UNSIGNED NOT NULL,
  `purchase_date` DATE NULL,
  `purchase_price` DECIMAL(10,2) NULL,
  `supplier_id` INT UNSIGNED NULL, -- Supplier from whom it was purchased
  `estimated_value` DECIMAL(10,2) NULL,
  `rating` TINYINT UNSIGNED NULL COMMENT '0-5 stars',
  `notes` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_worn_at` TIMESTAMP NULL,
  `times_worn` INT UNSIGNED NOT NULL DEFAULT 0,

  FOREIGN KEY (`category_type_id`) REFERENCES `categories_types`(`id`),
  FOREIGN KEY (`brand_id`) REFERENCES `brands`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`primary_color_id`) REFERENCES `colors`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`secondary_color_id`) REFERENCES `colors`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`material_id`) REFERENCES `materials`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`current_storage_location_id`) REFERENCES `storage_locations`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`current_status_id`) REFERENCES `statuses`(`id`),
  FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: article_images
-- Stores multiple images for a single article
CREATE TABLE `article_images` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `article_id` INT UNSIGNED NOT NULL,
  `image_path` VARCHAR(255) NOT NULL COMMENT 'Path relative to a base image directory, e.g., VRO00001-01.jpg',
  `caption` VARCHAR(255) NULL,
  `is_primary` BOOLEAN NOT NULL DEFAULT FALSE,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
  `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`article_id`) REFERENCES `articles`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: associated_articles (Many-to-Many relationship for articles)
-- Which articles are often worn together
CREATE TABLE `associated_articles` (
  `article_id_1` INT UNSIGNED NOT NULL,
  `article_id_2` INT UNSIGNED NOT NULL,
  `notes` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`article_id_1`, `article_id_2`),
  FOREIGN KEY (`article_id_1`) REFERENCES `articles`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`article_id_2`) REFERENCES `articles`(`id`) ON DELETE CASCADE,
  CONSTRAINT `check_different_articles` CHECK (`article_id_1` <> `article_id_2`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: history_log (tracks status changes, usage events for articles) OLD VERSION => remplacée par event_log (voir plus bas)
/* CREATE TABLE `history_log` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `article_id` INT UNSIGNED NOT NULL,
  `log_timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `status_id` INT UNSIGNED NOT NULL COMMENT 'Status the article transitioned to',
  `event_type_id` INT UNSIGNED NULL COMMENT 'Type of event if this log entry relates to usage',
  `event_name` VARCHAR(150) NULL COMMENT 'Specific name of the event, e.g., Gala de charité 2024',
  `event_date` DATE NULL,
  `item_user_id` INT UNSIGNED NULL COMMENT 'Person who used/wore the article',
  `related_supplier_id` INT UNSIGNED NULL COMMENT 'e.g., Dry cleaner, Repair shop',
  `cost_associated` DECIMAL(10,2) NULL COMMENT 'e.g., Cleaning cost, repair cost, rental income',
  `currency` CHAR(3) DEFAULT 'EUR',
  `notes` TEXT NULL,
  `created_by_user_id` INT UNSIGNED NULL, -- App user who logged this entry

  FOREIGN KEY (`article_id`) REFERENCES `articles`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`status_id`) REFERENCES `statuses`(`id`),
  FOREIGN KEY (`event_type_id`) REFERENCES `event_types`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`item_user_id`) REFERENCES `item_users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`related_supplier_id`) REFERENCES `suppliers`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by_user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci; */

-- Table: event_log_images (Stores multiple images for a single history_log event entry)
CREATE TABLE `event_log_images` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `history_log_id` BIGINT UNSIGNED NOT NULL,
  `image_path` VARCHAR(255) NOT NULL COMMENT 'Path relative to a base image directory, e.g., event_2024_gala_01.jpg',
  `caption` VARCHAR(255) NULL,
  `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`history_log_id`) REFERENCES `history_log`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Pre-populate some data (examples)

INSERT INTO `categories_types` (`name`, `category`, `code`) VALUES
('Robe', 'vêtement', 'VRO'),
('Pantalon', 'vêtement', 'VPA'),
('Chemisier', 'vêtement', 'VCH'),
('Escarpins', 'chaussures', 'CES'),
('Collier', 'bijou', 'BCO'),
('Ceinture', 'accessoire', 'ACE'); -- Changed code for ceinture to avoid conflict if 'B' is for Bijoux

INSERT INTO `statuses` (`name`, `availability_type`, `description`) VALUES
('Disponible et rangé', 'in_stock', 'Article est disponible et à sa place de rangement.'),
('En cours d''utilisation', 'out_of_stock', 'Article est actuellement porté ou utilisé.'),
('Au nettoyage', 'out_of_stock', 'Article est chez le pressing ou en cours de nettoyage.'),
('En réparation', 'out_of_stock', 'Article est en cours de réparation.'),
('Acheté (Nouveau)', 'in_stock', 'Article vient d''être acquis, prêt à être catalogué.'),
('Vendu', 'out_of_stock', 'Article a été vendu.'),
('Prêté', 'out_of_stock', 'Article a été prêté à quelqu''un.'),
('Égaré', 'limbo', 'Article est introuvable.'),
('Déclassé', 'limbo', 'Article n''est plus utilisé, sort de la collection active.'),
('Archivé', 'limbo', 'Article est stocké à long terme, hors collection active.');

INSERT INTO `event_types` (`name`, `typical_day_moments`) VALUES
('Bal', 'soir, nuit'),
('Cocktail', 'soir'),
('Déjeuner d''affaires', 'midi'),
('Dîner de gala', 'soir'),
('Mariage', 'après-midi, soir'),
('Voyage', 'matin, midi, après-midi, soir, nuit');

-- You would add more pre-population for colors, brands, materials, etc. as needed.
-- For example:
INSERT INTO `brands` (`name`, `abbreviation`) VALUES ('Chanel', 'CHA'), ('Generic', 'GEN');
INSERT INTO `colors` (`name`, `hex_code`, `base_color_category`) VALUES ('Noir', '#000000', 'Noir'), ('Blanc', '#FFFFFF', 'Blanc');
INSERT INTO `materials` (`name`) VALUES ('Coton'), ('Soie'), ('Cuir');
INSERT INTO `item_users` (`name`, `abbreviation`) VALUES ('Moi-même', 'MM'), ('Partenaire', 'PART');
INSERT INTO `storage_locations` (`room`, `area`, `shelf_or_rack`) VALUES ('Chambre Principale', 'Dressing Gauche', 'Étagère Haut');

-- Notes sur le schéma :
-- article_ref: Sera généré par PHP (ex: VRO de categories_types + un numéro séquentiel).
-- images dans articles: Devenu une table article_images (relation un-à-plusieurs).
-- articles_associés: Devenu une table de liaison associated_articles (relation plusieurs-à-plusieurs).
-- type_evenement dans articles: Supprimé, car un article n'a pas un type d'événement. Il est utilisé lors d'événements, ce qui est tracé dans history_log.
-- couleur, matière, marque, stockage, état dans articles: Devenus des clés étrangères vers leurs tables respectives.
-- history_log: Remplace votre table historique. event_images est gérée par event_log_images.
-- full_location_path dans storage_locations: Colonne générée pour faciliter l'affichage du chemin complet.
-- action_logs: Pour tracer toutes les actions importantes. user_id fait référence à la table users.


ALTER TABLE `event_types`
ADD COLUMN `description` TEXT NULL DEFAULT NULL COMMENT 'Detailed description of the event type' AFTER `name`;

-- 2. Supprimer l'ancienne colonne 'typical_day_moments' si elle existe et si vous êtes sûr
-- ATTENTION: Sauvegardez les données de cette colonne si vous voulez les migrer manuellement.
-- ALTER TABLE `event_types` DROP COLUMN `typical_day_moments`; -- Exécutez si vous êtes prêt

-- 3. Créer la table 'day_moments'
CREATE TABLE `day_moments` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL UNIQUE,
  `sort_order` TINYINT UNSIGNED DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Insérer les moments de la journée prédéfinis
INSERT INTO `day_moments` (`name`, `sort_order`) VALUES
('matin', 10),
('midi', 20),
('après-midi', 30),
('soir', 40),
('nuit', 50);

-- 5. Créer la table de liaison 'event_type_day_moment'
CREATE TABLE `event_type_day_moment` (
  `event_type_id` INT UNSIGNED NOT NULL,
  `day_moment_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`event_type_id`, `day_moment_id`),
  FOREIGN KEY (`event_type_id`) REFERENCES `event_types`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`day_moment_id`) REFERENCES `day_moments`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `item_users` 
MODIFY COLUMN `abbreviation` VARCHAR(20) NOT NULL,
ADD CONSTRAINT `unique_item_user_abbreviation` UNIQUE (`abbreviation`);

-- Contraintes supplémentaires pour SUPPLIERS
ALTER TABLE `suppliers` ADD CONSTRAINT `unique_supplier_name` UNIQUE (`name`);
ALTER TABLE `suppliers` ADD CONSTRAINT `unique_supplier_email_if_not_null` UNIQUE (`email`);

-- 1. Table principale: event_log
CREATE TABLE `event_log` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `article_id` INT UNSIGNED NOT NULL,
  `log_date` DATE NOT NULL COMMENT 'Date de début du statut/événement (sans heure)',
  `log_time` TIME NULL DEFAULT NULL COMMENT 'Heure de début du statut/événement (optionnelle)',
  
  `status_id` INT UNSIGNED NOT NULL COMMENT 'Nouveau statut de l''article après cet événement (FK vers statuses.id)',
  
  -- Champs spécifiques à certains statuts/événements (tous NULLables)
  `event_type_id` INT UNSIGNED NULL COMMENT 'Type d''événement (FK vers event_types.id)',
  `event_name` VARCHAR(150) NULL COMMENT 'Nom spécifique de l''événement',
  `description` TEXT NULL COMMENT 'Description plus longue de l''événement',
  `item_user_id` INT UNSIGNED NULL COMMENT 'Personne qui a utilisé l''article (FK vers item_users.id)',
  `related_supplier_id` INT UNSIGNED NULL COMMENT 'Fournisseur lié (FK vers suppliers.id)',
  `cost_associated` DECIMAL(10,2) NULL COMMENT 'Coût/prix associé',
  `currency` CHAR(3) DEFAULT 'EUR',

  `created_by_app_user_id` INT UNSIGNED NULL COMMENT 'Utilisateur de l''application qui a enregistré cet événement (FK vers users.id)',
  `created_at_log_entry` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Timestamp de création de cette entrée de log',

  FOREIGN KEY (`article_id`) REFERENCES `articles`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`status_id`) REFERENCES `statuses`(`id`),
  FOREIGN KEY (`event_type_id`) REFERENCES `event_types`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`item_user_id`) REFERENCES `item_users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`related_supplier_id`) REFERENCES `suppliers`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by_app_user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Table: event_log_images (Pour les images multiples par entrée de event_log)
CREATE TABLE `event_log_images` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `event_log_id` BIGINT UNSIGNED NOT NULL, -- Changé de history_log_id
  `image_path` VARCHAR(255) NOT NULL COMMENT 'Path relative to a base image directory',
  `caption` VARCHAR(255) NULL,
  `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`event_log_id`) REFERENCES `event_log`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Table: grouped_event_articles (Pour lier plusieurs articles à un "concept" d'événement)
CREATE TABLE `grouped_event_articles` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `group_event_name` VARCHAR(150) NULL COMMENT 'Nom commun du groupe d''événement',
    `group_event_date` DATE NOT NULL COMMENT 'Date de ce groupe d''événement',
    `group_event_time` TIME NULL DEFAULT NULL COMMENT 'Heure de ce groupe d''événement (optionnelle)',
    `notes` TEXT NULL,
    `created_at_group` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Table de liaison: event_log_grouped_event_link
CREATE TABLE `event_log_grouped_event_link` (
    `event_log_id` BIGINT UNSIGNED NOT NULL, -- Changé de history_log_id
    `grouped_event_id` BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (`event_log_id`, `grouped_event_id`),
    FOREIGN KEY (`event_log_id`) REFERENCES `event_log`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`grouped_event_id`) REFERENCES `grouped_event_articles`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ajout d'un index sur article_id dans event_log pour des recherches plus rapides par article
ALTER TABLE `event_log` ADD INDEX `idx_event_log_article_id` (`article_id`);
ALTER TABLE `event_log` ADD INDEX `idx_event_log_date` (`log_date`);

CREATE TABLE `article_suitable_event_types` (
  `article_id` INT UNSIGNED NOT NULL,
  `event_type_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`article_id`, `event_type_id`),
  FOREIGN KEY (`article_id`) REFERENCES `articles`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`event_type_id`) REFERENCES `event_types`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;