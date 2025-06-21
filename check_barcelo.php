<?php
require_once 'config/database.php';

$stmt = $pdo->prepare("SELECT id, nom, hero_images FROM lieux WHERE nom LIKE ? OR nom LIKE ?");
$stmt->execute(['%Barceló%', '%Casablanca%']);

while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: " . $row['id'] . "\n";
    echo "Nom: " . $row['nom'] . "\n";
    echo "Images: " . $row['hero_images'] . "\n\n";
} 