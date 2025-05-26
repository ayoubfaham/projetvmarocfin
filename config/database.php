<?php
try {
    $host = 'localhost';
    $username = 'root';
    $password = '';
    
    // Connexion initiale sans base de données
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Création de la base de données si elle n'existe pas
    $pdo->exec("CREATE DATABASE IF NOT EXISTS vmaroc");
    
    // Connexion à la base de données vmaroc
    $pdo = new PDO("mysql:host=$host;dbname=vmaroc;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

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