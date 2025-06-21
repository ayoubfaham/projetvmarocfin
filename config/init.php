<?php
require_once 'database.php';

try {
    // Création de la table villes si elle n'existe pas
    $pdo->exec("CREATE TABLE IF NOT EXISTS villes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(100) NOT NULL,
        description TEXT,
        hero_images TEXT,
        best_time VARCHAR(100),
        language VARCHAR(50),
        currency VARCHAR(50)
    )");

    // Création de la table lieux si elle n'existe pas
    $pdo->exec("CREATE TABLE IF NOT EXISTS lieux (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(100) NOT NULL,
        description TEXT,
        id_ville INT,
        hero_images TEXT,
        url_activite VARCHAR(255),
        categorie VARCHAR(50),
        adresse TEXT,
        equipements TEXT,
        boutiques_services TEXT,
        latitude VARCHAR(50),
        longitude VARCHAR(50),
        budget VARCHAR(50),
        rating DECIMAL(3,2) DEFAULT 0,
        FOREIGN KEY (id_ville) REFERENCES villes(id) ON DELETE CASCADE
    )");

    // Création de la table recommandations si elle n'existe pas
    $pdo->exec("CREATE TABLE IF NOT EXISTS recommandations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ville_id INT,
        titre VARCHAR(200) NOT NULL,
        description TEXT,
        categorie VARCHAR(50) NOT NULL,
        prix_min DECIMAL(10,2),
        prix_max DECIMAL(10,2),
        duree_min INT,
        duree_max INT,
        image_url VARCHAR(255),
        FOREIGN KEY (ville_id) REFERENCES villes(id) ON DELETE CASCADE
    )");

    // Création de la table utilisateurs si elle n'existe pas
    $pdo->exec("CREATE TABLE IF NOT EXISTS utilisateurs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(100) NOT NULL,
        email VARCHAR(150) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('user', 'admin') DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Création de la table avis si elle n'existe pas
    $pdo->exec("CREATE TABLE IF NOT EXISTS avis (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_utilisateur INT NOT NULL,
        id_lieu INT NOT NULL,
        rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
        commentaire TEXT,
        date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id) ON DELETE CASCADE,
        FOREIGN KEY (id_lieu) REFERENCES lieux(id) ON DELETE CASCADE,
        UNIQUE KEY unique_user_lieu (id_utilisateur, id_lieu)
    )");

    echo "Base de données initialisée avec succès !";
} catch (PDOException $e) {
    die("Erreur d'initialisation de la base de données : " . $e->getMessage());
} 