<?php
require_once 'config/database.php';

// Récupérer les informations du lieu
$stmt = $pdo->prepare("SELECT id, nom, hero_images FROM lieux WHERE nom = ?");
$stmt->execute(['Barceló Anfa Casablanca']);
$lieu = $stmt->fetch(PDO::FETCH_ASSOC);

if ($lieu) {
    // Récupérer uniquement les images qui existent physiquement
    $directory = __DIR__ . '/images/Barceló Anfa Casablanca/';
    $files = scandir($directory);
    $validImages = [];
    
    // Filtrer pour ne garder que les images jpg du Barceló
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && $file !== '.DS_Store' && strpos($file, 'Barceló Anfa Casablanca') !== false && strpos($file, '.jpg') !== false) {
            $validImages[] = $file;
        }
    }
    
    if (!empty($validImages)) {
        // Trier les images par numéro
        sort($validImages);
        
        // Mettre à jour la base de données avec uniquement les images valides
        $newImages = implode(',', $validImages);
        $stmt = $pdo->prepare("UPDATE lieux SET hero_images = ? WHERE id = ?");
        $stmt->execute([$newImages, $lieu['id']]);
        
        echo "✅ Mise à jour réussie !\n\n";
        echo "Images conservées :\n";
        foreach ($validImages as $image) {
            echo "- " . $image . "\n";
        }
    } else {
        echo "❌ Aucune image valide trouvée dans le dossier.";
    }
} else {
    echo "❌ Lieu non trouvé dans la base de données.";
} 