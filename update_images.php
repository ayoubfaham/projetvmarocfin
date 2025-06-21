<?php
require_once 'config/database.php';

// Récupérer les images actuelles
$stmt = $pdo->prepare("SELECT id, hero_images FROM lieux WHERE nom LIKE ?");
$stmt->execute(['%Barceló%']);
$lieu = $stmt->fetch(PDO::FETCH_ASSOC);

if ($lieu) {
    $images = explode(',', $lieu['hero_images']);
    // Supprimer la première image
    array_shift($images);
    $newImages = implode(',', $images);
    
    // Mettre à jour la base de données
    $stmt = $pdo->prepare("UPDATE lieux SET hero_images = ? WHERE id = ?");
    $stmt->execute([$newImages, $lieu['id']]);
    
    echo "Images mises à jour avec succès.\n";
    echo "Nouvelles images : " . $newImages . "\n";
} else {
    echo "Lieu non trouvé.\n";
} 