<?php
session_start();
require_once 'config/database.php';

// Vérifier si l'utilisateur est admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    die("Accès non autorisé");
}

if (isset($_POST['place_id']) && isset($_POST['image'])) {
    $placeId = (int)$_POST['place_id'];
    $imageToDelete = trim($_POST['image']);

    // Récupérer les images actuelles
    $stmt = $pdo->prepare("SELECT hero_images FROM lieux WHERE id = ?");
    $stmt->execute([$placeId]);
    $place = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($place) {
        $currentImages = array_filter(array_map('trim', explode(',', $place['hero_images'])));
        
        // Retirer l'image à supprimer
        $updatedImages = array_filter($currentImages, function($img) use ($imageToDelete) {
            return trim($img) !== trim($imageToDelete);
        });

        // Mettre à jour la base de données
        $newHeroImages = implode(',', $updatedImages);
        $stmt = $pdo->prepare("UPDATE lieux SET hero_images = ? WHERE id = ?");
        $stmt->execute([$newHeroImages, $placeId]);

        // Tenter de supprimer le fichier physique
        $possiblePaths = [
            $_SERVER['DOCUMENT_ROOT'] . '/project10/' . $imageToDelete,
            $_SERVER['DOCUMENT_ROOT'] . '/project10/images/' . basename($imageToDelete),
            $_SERVER['DOCUMENT_ROOT'] . '/project10/images/Barceló Anfa Casablanca/' . basename($imageToDelete)
        ];

        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                unlink($path);
                break;
            }
        }

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lieu non trouvé']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Paramètres manquants']);
} 