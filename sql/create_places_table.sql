-- Table structure for table `places`
CREATE TABLE IF NOT EXISTS `places` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ville_id` int(11) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `description` text,
  `categorie` varchar(50) NOT NULL,
  `adresse` varchar(255),
  `latitude` decimal(10,8),
  `longitude` decimal(11,8),
  `photo_url` varchar(255),
  `horaires` text,
  `prix_min` decimal(10,2),
  `prix_max` decimal(10,2),
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ville_id` (`ville_id`),
  CONSTRAINT `fk_place_ville` FOREIGN KEY (`ville_id`) REFERENCES `villes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci; 