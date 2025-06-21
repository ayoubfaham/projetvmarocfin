<?php
header('Content-Type: application/json');
session_start();

// Vérification de la session admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Accès non autorisé']);
    exit;
}

try {
    $pdo = new PDO('mysql:host=localhost;dbname=vmaroc;charset=utf8', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Récupération des filtres GET
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $ville_id = isset($_GET['ville_id']) ? intval($_GET['ville_id']) : 0;
    $categorie = isset($_GET['categorie']) ? trim($_GET['categorie']) : '';

    $sql = "SELECT l.*, v.nom as ville_nom FROM lieux l LEFT JOIN villes v ON l.id_ville = v.id WHERE 1=1";
    $params = [];

    if (!empty($search)) {
        // Diviser les termes de recherche
        $searchTerms = explode(' ', $search);
        $searchClauses = [];
        
        foreach ($searchTerms as $index => $term) {
            $key = "search$index";
            $searchClauses[] = "(
                LOWER(l.nom) LIKE LOWER(:$key) 
                OR LOWER(l.description) LIKE LOWER(:$key)
                OR LOWER(v.nom) LIKE LOWER(:$key)
                OR LOWER(l.categorie) LIKE LOWER(:$key)
            )";
            $params[$key] = "%$term%";
        }
        
        if (!empty($searchClauses)) {
            $sql .= " AND (" . implode(' AND ', $searchClauses) . ")";
        }
    }

    if ($ville_id > 0) {
        $sql .= " AND l.id_ville = :ville_id";
        $params['ville_id'] = $ville_id;
    }

    if (!empty($categorie)) {
        $sql .= " AND LOWER(l.categorie) = LOWER(:categorie)";
        $params['categorie'] = $categorie;
    }

    // Tri des résultats
    if (!empty($search)) {
        $sql .= " ORDER BY 
            CASE 
                WHEN LOWER(l.nom) LIKE LOWER(:exact_match) THEN 1
                WHEN LOWER(l.nom) LIKE LOWER(:start_match) THEN 2
                ELSE 3
            END,
            l.nom ASC";
        $params['exact_match'] = $search;
        $params['start_match'] = $search . "%";
    } else {
        $sql .= " ORDER BY l.nom ASC";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $places = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($places);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
} 