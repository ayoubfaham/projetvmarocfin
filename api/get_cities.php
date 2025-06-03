<?php
header('Content-Type: application/json');

// Activer l'affichage des erreurs pour le débogage
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Inclure le fichier de configuration de la base de données
require_once '../config/database.php';

try {
    // Récupérer toutes les villes de la base de données
    $stmt = $pdo->prepare("SELECT * FROM villes ORDER BY nom ASC");
    $stmt->execute();
    $cities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Retourner les résultats au format JSON
    echo json_encode($cities);
} catch (PDOException $e) {
    // En cas d'erreur, retourner un message d'erreur
    http_response_code(500);
    echo json_encode(['error' => 'Erreur de base de données: ' . $e->getMessage()]);
}
