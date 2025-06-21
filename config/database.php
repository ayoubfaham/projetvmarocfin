<?php
try {
    $host = 'localhost';
    $dbname = 'vmaroc';
    $username = 'root';
    $password = '';
    $charset = 'utf8';
    
    $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $pdo = new PDO($dsn, $username, $password, $options);

    // Vérifier et mettre à jour la structure de la table lieux si nécessaire
    $pdo->exec("ALTER TABLE lieux ADD COLUMN IF NOT EXISTS hero_images TEXT COMMENT 'Images du lieu séparées par des virgules'");
    $pdo->exec("ALTER TABLE lieux ADD COLUMN IF NOT EXISTS url_activite VARCHAR(255) COMMENT 'URL de réservation en ligne'");
    $pdo->exec("ALTER TABLE lieux ADD COLUMN IF NOT EXISTS equipements TEXT COMMENT 'Équipements disponibles'");
    $pdo->exec("ALTER TABLE lieux ADD COLUMN IF NOT EXISTS boutiques_services TEXT COMMENT 'Services disponibles'");
    $pdo->exec("ALTER TABLE lieux ADD COLUMN IF NOT EXISTS latitude VARCHAR(50) COMMENT 'Latitude du lieu'");
    $pdo->exec("ALTER TABLE lieux ADD COLUMN IF NOT EXISTS longitude VARCHAR(50) COMMENT 'Longitude du lieu'");
    $pdo->exec("ALTER TABLE lieux ADD COLUMN IF NOT EXISTS budget VARCHAR(50) COMMENT 'Budget estimé'");
    $pdo->exec("ALTER TABLE lieux ADD COLUMN IF NOT EXISTS rating DECIMAL(3,2) DEFAULT 0 COMMENT 'Note moyenne'");

    // Création de la table villes si elle n'existe pas
    $pdo->exec("CREATE TABLE IF NOT EXISTS villes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(100) NOT NULL,
        description TEXT,
        photo VARCHAR(255),
        best_time VARCHAR(100),
        language VARCHAR(50),
        currency VARCHAR(50)
    )");

    // Création de la table utilisateurs si elle n'existe pas
    $pdo->exec("CREATE TABLE IF NOT EXISTS utilisateurs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(100) NOT NULL,
        email VARCHAR(150) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        telephone VARCHAR(20),
        preferences TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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
        FOREIGN KEY (ville_id) REFERENCES villes(id)
    )");
    
    // Création de la table lieux si elle n'existe pas
    $pdo->exec("CREATE TABLE IF NOT EXISTS lieux (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(100) NOT NULL,
        photo VARCHAR(255),
        photo2 VARCHAR(255),
        photo3 VARCHAR(255),
        photo4 VARCHAR(255),
        description TEXT,
        id_ville INT,
        url_activite VARCHAR(255),
        categorie VARCHAR(50),
        FOREIGN KEY (id_ville) REFERENCES villes(id)
    )");

    // Création de la table avis si elle n'existe pas
    $pdo->exec("CREATE TABLE IF NOT EXISTS avis (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_utilisateur INT NOT NULL,
        id_lieu INT NOT NULL,
        rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
        commentaire TEXT,
        date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id),
        FOREIGN KEY (id_lieu) REFERENCES lieux(id),
        UNIQUE KEY unique_user_lieu (id_utilisateur, id_lieu)
    )");

    // Vérifier si la table est vide
    $count = $pdo->query("SELECT COUNT(*) FROM villes")->fetchColumn();
    
    // Si la table est vide, insérer des villes de test
    if ($count == 0) {
        $cities = [
            [
                'nom' => 'Marrakech',
                'description' => 'La ville rouge, célèbre pour ses souks, ses jardins et la place Jemaa el-Fna.',
                'photo' => 'https://images.unsplash.com/photo-1464983953574-0892a716854b?auto=format&fit=crop&w=800&q=80',
                'best_time' => 'Mars à Mai, Septembre à Novembre',
                'language' => 'Arabe, Français',
                'currency' => 'Dirham marocain (MAD)'
            ],
            [
                'nom' => 'Fès',
                'description' => 'La capitale spirituelle du Maroc, avec la plus grande médina piétonne au monde.',
                'photo' => 'https://images.unsplash.com/photo-1502082553048-f009c37129b9?auto=format&fit=crop&w=800&q=80',
                'best_time' => 'Avril à Juin, Septembre à Novembre',
                'language' => 'Arabe, Français',
                'currency' => 'Dirham marocain (MAD)'
            ],
            [
                'nom' => 'Chefchaouen',
                'description' => 'La perle bleue du Rif, célèbre pour ses ruelles colorées et son ambiance paisible.',
                'photo' => 'https://images.unsplash.com/photo-1500534314209-a25ddb2bd429?auto=format&fit=crop&w=800&q=80',
                'best_time' => 'Avril à Juin, Septembre à Octobre',
                'language' => 'Arabe, Espagnol',
                'currency' => 'Dirham marocain (MAD)'
            ]
        ];

        $stmt = $pdo->prepare("INSERT INTO villes (nom, description, photo, best_time, language, currency) VALUES (?, ?, ?, ?, ?, ?)");
        
        foreach ($cities as $city) {
            $stmt->execute([
                $city['nom'],
                $city['description'],
                $city['photo'],
                $city['best_time'],
                $city['language'],
                $city['currency']
            ]);
        }
    }

} catch(PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
} 