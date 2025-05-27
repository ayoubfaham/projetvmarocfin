<?php
require_once 'config/database.php';

// Vu00e9rifier la structure de la table avis
$tableInfo = $pdo->query("DESCRIBE avis")->fetchAll(PDO::FETCH_ASSOC);
echo "<h3>Structure de la table 'avis':</h3>";
echo "<pre>";
print_r($tableInfo);
echo "</pre>";

// Vu00e9rifier les donnu00e9es dans la table avis
$avisData = $pdo->query("SELECT * FROM avis LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
echo "<h3>Donnu00e9es dans la table 'avis' (5 premiers enregistrements):</h3>";
echo "<pre>";
print_r($avisData);
echo "</pre>";
?>
