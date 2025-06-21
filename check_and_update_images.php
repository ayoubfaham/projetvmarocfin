<?php
require_once 'config/database.php';

// Récupérer les informations du lieu
$stmt = $pdo->prepare("SELECT id, nom, hero_images FROM lieux WHERE nom = ?");
$stmt->execute(['Barceló Anfa Casablanca']);
$lieu = $stmt->fetch(PDO::FETCH_ASSOC);

if ($lieu) {
    echo "Images actuelles dans la base de données : " . $lieu['hero_images'] . "\n\n";
    
    // Lister les images physiquement présentes dans le dossier
    $directory = __DIR__ . '/images/Barceló Anfa Casablanca/';
    $existingFiles = scandir($directory);
    $validFiles = array_filter($existingFiles, function($file) {
        return !in_array($file, ['.', '..', '.DS_Store']) && strpos($file, 'Barceló Anfa Casablanca') !== false;
    });
    
    echo "Images trouvées dans le dossier :\n";
    foreach ($validFiles as $file) {
        echo "- " . $file . "\n";
    }
    echo "\n";
    
    // Mettre à jour la base de données avec les images valides
    $newImages = implode(',', array_values($validFiles));
    $stmt = $pdo->prepare("UPDATE lieux SET hero_images = ? WHERE id = ?");
    $stmt->execute([$newImages, $lieu['id']]);
    
    echo "Base de données mise à jour avec succès.\n";
    echo "Nouvelles images : " . $newImages . "\n";
} else {
    echo "Lieu non trouvé dans la base de données.\n";
} 