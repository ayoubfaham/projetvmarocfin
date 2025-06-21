<?php
header('Content-Type: application/json');

try {
    if (!isset($_GET['id'])) {
        throw new Exception('ID non spécifié');
    }

    $pdo = new PDO('mysql:host=localhost;dbname=vmaroc;charset=utf8', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $id = intval($_GET['id']);
    
    // Récupérer les informations du lieu avant la suppression
    $stmt = $pdo->prepare("SELECT hero_images FROM lieux WHERE id = ?");
    $stmt->execute([$id]);
    $place = $stmt->fetch(PDO::FETCH_ASSOC);

    // Supprimer le lieu
    $stmt = $pdo->prepare("DELETE FROM lieux WHERE id = ?");
    $stmt->execute([$id]);

    // Si la suppression a réussi et qu'il y avait des images
    if ($place && !empty($place['hero_images'])) {
        $images = explode(',', $place['hero_images']);
        foreach ($images as $image) {
            $filepath = '../' . trim($image);
            if (file_exists($filepath)) {
                @unlink($filepath);
            }
        }
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?> 