<?php
require_once 'config/database.php';

try {
    // Vérifier la structure de la table villes
    $stmt = $pdo->query("SHOW COLUMNS FROM villes");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h2>Colonnes de la table 'villes' :</h2>";
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
    
    // Vérifier si certaines colonnes existent
    $checkColumns = ['site_web', 'website', 'url'];
    echo "<h2>Vérification des colonnes spécifiques :</h2>";
    foreach ($checkColumns as $col) {
        $exists = in_array($col, $columns) ? 'Oui' : 'Non';
        echo "$col : $exists<br>";
    }
    
    // Afficher un exemple de données
    $example = $pdo->query("SELECT * FROM villes LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    echo "<h2>Exemple de données :</h2>";
    echo "<pre>";
    print_r($example);
    echo "</pre>";
    
} catch (PDOException $e) {
    die("Erreur : " . $e->getMessage());
}
?>
