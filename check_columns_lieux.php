<?php
// Connexion à la base de données
require_once 'config.php';

try {
    // Vérifier si un ID de lieu est passé en paramètre
    $place_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($place_id > 0) {
        // Récupérer les informations du lieu spécifique
        $stmt = $pdo->prepare("SELECT * FROM lieux WHERE id = ?");
        $stmt->execute([$place_id]);
        $place = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($place) {
            echo "<h2>Détails du lieu #$place_id</h2>";
            echo "<h3>Colonnes et valeurs :</h3>";
            echo "<pre>";
            print_r($place);
            echo "</pre>";
        } else {
            echo "Aucun lieu trouvé avec l'ID $place_id";
        }
    }
    
    // Afficher la structure de la table lieux
    echo "<h3>Structure de la table 'lieux' :</h3>";
    $stmt = $pdo->query("SHOW COLUMNS FROM lieux");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<p>Colonnes disponibles : " . implode(", ", $columns) . "</p>";
    
    // Vérifier les noms de colonnes potentiels pour le site web
    $possible_columns = ['site_web', 'website', 'url', 'lien_web', 'lien_site'];
    $found_columns = array_intersect($possible_columns, $columns);
    
    echo "<h3>Colonnes de site web détectées :</h3>";
    if (!empty($found_columns)) {
        echo "<ul>";
        foreach ($found_columns as $col) {
            echo "<li>$col</li>";
        }
        echo "</ul>";
    } else {
        echo "Aucune colonne de site web trouvée parmi : " . implode(", ", $possible_columns);
    }
    
    // Afficher les 5 premiers lieux avec leurs URLs de site web
    echo "<h3>Exemples de données :</h3>";
    $stmt = $pdo->query("SELECT id, nom, site_web, website, url, lien_web, lien_site FROM lieux LIMIT 5");
    $examples = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse; width: 100%;'>";
    // En-têtes
    echo "<tr><th>ID</th><th>Nom</th><th>site_web</th><th>website</th><th>url</th><th>lien_web</th><th>lien_site</th></tr>";
    
    // Données
    foreach ($examples as $row) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['nom']) . "</td>";
        echo "<td>" . htmlspecialchars($row['site_web'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($row['website'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($row['url'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($row['lien_web'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($row['lien_site'] ?? '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>

<h3>Instructions :</h3>
<ol>
    <li>Pour voir les détails d'un lieu spécifique, ajoutez <code>?id=1</code> à l'URL (remplacez 1 par l'ID du lieu)</li>
    <li>Vérifiez les colonnes disponibles dans la table 'lieux'</li>
    <li>Vérifiez les exemples de données pour voir où sont stockées les URLs des sites web</li>
</ol>
