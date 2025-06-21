<?php
require_once 'config/database.php';

// Récupérer les informations du lieu
$stmt = $pdo->prepare("SELECT id, nom, hero_images FROM lieux WHERE nom = ?");
$stmt->execute(['Barceló Anfa Casablanca']);
$lieu = $stmt->fetch(PDO::FETCH_ASSOC);

if ($lieu) {
    // Récupérer les images actuelles
    $currentImages = explode(',', $lieu['hero_images']);
    
    // Filtrer pour retirer l'image de la façade
    $updatedImages = array_filter($currentImages, function($image) {
        return !strpos(strtolower($image), 'facade') && 
               !strpos(strtolower($image), 'front') && 
               !strpos($image, 'Barceló Anfa Casablanca1');  // Supprime aussi la première image si c'est celle de la façade
    });
    
    // Mettre à jour la base de données
    $newImages = implode(',', array_values($updatedImages));
    $stmt = $pdo->prepare("UPDATE lieux SET hero_images = ? WHERE id = ?");
    $stmt->execute([$newImages, $lieu['id']]);
    
    echo "✅ Image de la façade supprimée avec succès !\n\n";
    echo "Images restantes :\n";
    foreach ($updatedImages as $image) {
        echo "- " . $image . "\n";
    }
} else {
    echo "❌ Lieu non trouvé dans la base de données.";
} 