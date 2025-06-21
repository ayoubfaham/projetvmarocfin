<?php
require_once 'config/database.php';

try {
    // Supprimer les tables existantes dans le bon ordre (à cause des contraintes de clé étrangère)
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("DROP TABLE IF EXISTS avis");
    $pdo->exec("DROP TABLE IF EXISTS recommandations");
    $pdo->exec("DROP TABLE IF EXISTS lieux");
    $pdo->exec("DROP TABLE IF EXISTS utilisateurs");
    $pdo->exec("DROP TABLE IF EXISTS villes");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    // Recréer les tables avec la bonne structure
    require_once 'config/init.php';

    // Insérer un utilisateur admin par défaut
    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $pdo->exec("INSERT INTO utilisateurs (nom, email, password, role) VALUES ('Admin', 'admin@vmaroc.com', '$adminPassword', 'admin')");

    // Insérer quelques villes de test
    $cities = [
        [
            'nom' => 'Marrakech',
            'description' => 'La ville rouge, célèbre pour ses souks, ses jardins et la place Jemaa el-Fna.',
            'hero_images' => 'uploads/villes/marrakech1.jpg,uploads/villes/marrakech2.jpg',
            'best_time' => 'Mars à Mai, Septembre à Novembre',
            'language' => 'Arabe, Français',
            'currency' => 'Dirham marocain (MAD)'
        ],
        [
            'nom' => 'Fès',
            'description' => 'La capitale spirituelle du Maroc, avec la plus grande médina piétonne au monde.',
            'hero_images' => 'uploads/villes/fes1.jpg,uploads/villes/fes2.jpg',
            'best_time' => 'Avril à Juin, Septembre à Novembre',
            'language' => 'Arabe, Français',
            'currency' => 'Dirham marocain (MAD)'
        ]
    ];

    $stmt = $pdo->prepare("INSERT INTO villes (nom, description, hero_images, best_time, language, currency) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($cities as $city) {
        $stmt->execute([
            $city['nom'],
            $city['description'],
            $city['hero_images'],
            $city['best_time'],
            $city['language'],
            $city['currency']
        ]);
    }

    echo "Base de données réinitialisée avec succès ! <br>";
    echo "Vous pouvez maintenant vous connecter avec : <br>";
    echo "Email : admin@vmaroc.com <br>";
    echo "Mot de passe : admin123 <br>";
    echo "<a href='pages/admin-login.php'>Aller à la page de connexion</a>";
} catch (PDOException $e) {
    die("Erreur lors de la réinitialisation de la base de données : " . $e->getMessage());
} 