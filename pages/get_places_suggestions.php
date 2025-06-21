<?php

// Diviser les mots de recherche
$searchTerms = explode(' ', $search);
$whereClauses = [];
$params = [];

foreach ($searchTerms as $index => $term) {
    $key = "search$index";
    $whereClauses[] = "(
        LOWER(l.nom) LIKE LOWER(:$key) 
        OR LOWER(l.description) LIKE LOWER(:$key)
        OR LOWER(l.categorie) LIKE LOWER(:$key)
        OR LOWER(v.nom) LIKE LOWER(:$key)
    )";
    $params[$key] = "%$term%";
}

$sql = "SELECT l.*, v.nom as ville_nom 
        FROM lieux l 
        LEFT JOIN villes v ON l.id_ville = v.id 
        WHERE " . implode(' AND ', $whereClauses) . "
        ORDER BY 
            CASE 
                WHEN LOWER(l.nom) LIKE LOWER(:exact_match) THEN 1
                WHEN LOWER(l.nom) LIKE LOWER(:start_match) THEN 2
                ELSE 3
            END,
            l.nom ASC
        LIMIT 10";

// Ajouter les paramètres de tri
$params['exact_match'] = $search;
$params['start_match'] = $search . "%";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($results); 