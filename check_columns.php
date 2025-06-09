<?php
require_once 'config/database.php';

try {
    // Vérifier la structure de la table lieux
    $stmt = $pdo->query("SHOW COLUMNS FROM lieux");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h2>Colonnes disponibles dans la table 'lieux':</h2>";
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
    
    // Vérifier si url_activités existe
    if (in_array('url_activités', $columns)) {
        echo "<p style='color: green;'>La colonne 'url_activités' existe dans la table 'lieux'.</p>";
        
        // Afficher un exemple de données
        $stmt = $pdo->query("SELECT id, nom, url_activités FROM lieux WHERE url_activités IS NOT NULL AND url_activités != '' LIMIT 5");
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Exemples de données avec url_activités :</h3>";
        echo "<pre>";
        print_r($results);
        echo "</pre>";
    } else {
        echo "<p style='color: red;'>La colonne 'url_activités' n'existe PAS dans la table 'lieux'.</p>";
        
        // Afficher les colonnes qui pourraient être pertinentes
        $possible_columns = [];
        $possible_matches = ['url', 'site', 'web', 'lien', 'activite', 'activity'];
        
        foreach ($columns as $col) {
            foreach ($possible_matches as $match) {
                if (stripos($col, $match) !== false) {
                    $possible_columns[] = $col;
                    break;
                }
            }
        }
        
        if (!empty($possible_columns)) {
            echo "<p>Colonnes potentielles contenant des URLs :</p>";
            echo "<pre>";
            print_r($possible_columns);
            echo "</pre>";
        }
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Erreur lors de la vérification de la base de données: " . $e->getMessage() . "</p>";
}

// Vérifier les données d'un lieu spécifique (remplacez l'ID par un ID valide)
$placeId = isset($_GET['id']) ? (int)$_GET['id'] : 1;
try {
    $stmt = $pdo->prepare("SELECT * FROM lieux WHERE id = ?");
    $stmt->execute([$placeId]);
    $place = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h2>Données du lieu ID: $placeId</h2>";
    echo "<pre>";
    print_r($place);
    echo "</pre>";
} catch (PDOException $e) {
    echo "<p>Impossible de récupérer les données du lieu: " . $e->getMessage() . "</p>";
}
?>

<h2>Instructions</h2>
<ol>
    <li>Si la colonne n'existe pas, vous devrez l'ajouter à la table 'lieux' avec cette commande SQL :
        <pre>ALTER TABLE lieux ADD COLUMN `url_activités` VARCHAR(255) DEFAULT NULL COMMENT 'URL de réservation en ligne' AFTER `boutiques_services`;</pre>
    </li>
    <li>Si la colonne existe sous un autre nom, mettez à jour le code PHP pour utiliser le bon nom de colonne.</li>
</ol>
