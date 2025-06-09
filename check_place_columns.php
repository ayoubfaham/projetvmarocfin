<?php
// Connexion à la base de données
require_once 'config.php';

try {
    // Vérifier si la table lieux existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'lieux'");
    if ($stmt->rowCount() == 0) {
        die("La table 'lieux' n'existe pas dans la base de données.");
    }
    
    // Récupérer l'ID du lieu actuel depuis l'URL
    $lieu_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    // Si un ID est spécifié, afficher les détails de ce lieu
    if ($lieu_id > 0) {
        $stmt = $pdo->prepare("SELECT * FROM lieux WHERE id = ?");
        $stmt->execute([$lieu_id]);
        $lieu = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($lieu) {
            echo "<h2>Détails du lieu #" . htmlspecialchars($lieu['id']) . "</h2>";
            echo "<h3>" . htmlspecialchars($lieu['nom'] ?? 'Sans nom') . "</h3>";
            
            // Afficher toutes les colonnes qui contiennent 'url' ou 'lien' dans leur nom
            echo "<h3>Colonnes avec des URLs ou liens :</h3>";
            echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse;'>";
            echo "<tr><th>Colonne</th><th>Valeur</th></tr>";
            
            $trouve = false;
            foreach ($lieu as $colonne => $valeur) {
                if (stripos($colonne, 'url') !== false || stripos($colonne, 'lien') !== false) {
                    $trouve = true;
                    echo "<tr>";
                    echo "<td><strong>" . htmlspecialchars($colonne) . "</strong></td>";
                    echo "<td>" . (!empty($valeur) ? htmlspecialchars($valeur) : '<em>vide</em>') . "</td>";
                    echo "</tr>";
                }
            }
            
            if (!$trouve) {
                echo "<tr><td colspan='2'>Aucune colonne contenant 'url' ou 'lien' n'a été trouvée.</td></tr>";
            }
            
            echo "</table>";
            
            // Afficher toutes les colonnes pour référence
            echo "<h3>Toutes les colonnes :</h3>";
            echo "<pre>";
            print_r($lieu);
            echo "</pre>";
        } else {
            echo "<p>Aucun lieu trouvé avec l'ID $lieu_id</p>";
        }
    }
    
    // Afficher la structure de la table pour référence
    echo "<h2>Structure de la table 'lieux'</h2>";
    $stmt = $pdo->query("DESCRIBE lieux");
    echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse;'>";
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
    
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>

<h3>Instructions :</h3>
<ol>
    <li>Ouvrez la page d'un lieu spécifique (par exemple : place.php?id=1)</li>
    <li>Copiez l'ID du lieu depuis l'URL</li>
    <li>Ajoutez l'ID à l'URL de cette page : check_place_columns.php?id=VOTRE_ID</li>
    <li>Vous verrez toutes les colonnes contenant 'url' ou 'lien' dans leur nom</li>
</ol>
