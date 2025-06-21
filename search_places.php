<?php
header('Content-Type: application/json');

// Vérifier si l'utilisateur est connecté en tant qu'admin
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

// Vérifier si un terme de recherche est fourni
if (!isset($_GET['term'])) {
    echo json_encode([]);
    exit;
}

$searchTerm = trim($_GET['term']);

if (empty($searchTerm)) {
    echo json_encode([]);
    exit;
}

try {
    $pdo = new PDO('mysql:host=localhost;dbname=vmaroc;charset=utf8', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Préparer la requête de recherche
    $query = "SELECT l.*, v.nom as ville_nom, 
              SUBSTRING_INDEX(l.hero_images, ',', 1) as image
              FROM lieux l 
              LEFT JOIN villes v ON l.id_ville = v.id 
              WHERE l.nom LIKE :term 
              OR l.description LIKE :term 
              OR v.nom LIKE :term 
              OR l.categorie LIKE :term 
              LIMIT 10";
    
    $stmt = $pdo->prepare($query);
    $searchPattern = "%{$searchTerm}%";
    $stmt->execute(['term' => $searchPattern]);
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Nettoyer et formater les résultats
    foreach ($results as &$result) {
        $result['nom'] = htmlspecialchars($result['nom']);
        $result['ville_nom'] = htmlspecialchars($result['ville_nom']);
        $result['categorie'] = htmlspecialchars($result['categorie']);
        // Nettoyer le chemin de l'image
        if (!empty($result['image'])) {
            $result['image'] = trim($result['image']);
        }
    }
    
    echo json_encode($results);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur de base de données']);
    error_log($e->getMessage());
} 