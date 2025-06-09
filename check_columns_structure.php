<?php
// Connexion à la base de données
require_once 'config.php';

try {
    // Vérifier si la table lieux existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'lieux'");
    if ($stmt->rowCount() == 0) {
        die("La table 'lieux' n'existe pas dans la base de données.");
    }
    
    // Afficher la structure de la table lieux
    echo "<h2>Structure de la table 'lieux'</h2>";
    $stmt = $pdo->query("DESCRIBE lieux");
    echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Champ</th><th>Type</th><th>Null</th><th>Clé</th><th>Par défaut</th><th>Extra</th></tr>";
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Vérifier si des colonnes contiennent 'url' ou 'lien' dans leur nom
    echo "<h2>Colonnes contenant 'url' ou 'lien'</h2>";
    $stmt = $pdo->query("SHOW COLUMNS FROM lieux WHERE Field LIKE '%url%' OR Field LIKE '%lien%'");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($columns) > 0) {
        echo "<p>Colonnes trouvées : " . implode(", ", $columns) . "</p>";
        
        // Afficher un exemple de données pour ces colonnes
        echo "<h3>Exemple de données :</h3>";
        $stmt = $pdo->query("SELECT id, nom, " . implode(", ", $columns) . " FROM lieux LIMIT 5");
        $examples = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse; width: 100%;'>";
        // En-têtes
        echo "<tr><th>ID</th><th>Nom</th>";
        foreach ($columns as $col) {
            echo "<th>" . htmlspecialchars($col) . "</th>";
        }
        echo "</tr>";
        
        // Données
        foreach ($examples as $row) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['nom']) . "</td>";
            
            foreach ($columns as $col) {
                echo "<td>" . (!empty($row[$col]) ? htmlspecialchars($row[$col]) : '<em>vide</em>') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Aucune colonne contenant 'url' ou 'lien' n'a été trouvée dans la table 'lieux'.</p>";
    }
    
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>

<h3>Instructions :</h3>
<ol>
    <li>Vérifiez la structure de la table 'lieux' ci-dessus</li>
    <li>Identifiez la colonne qui contient l'URL des activités</li>
    <li>Notez le nom exact de cette colonne</li>
</ol>
