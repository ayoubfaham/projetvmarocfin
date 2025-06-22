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
    require_once('../includes/db_connect.php');
    
    $query = isset($_GET['q']) ? trim($_GET['q']) : '';
    
    if (strlen($query) >= 1) {
        $sql = "
            SELECT DISTINCT
                l.id,
                l.nom,
                l.description,
                l.categorie,
                l.budget,
                v.nom as ville_nom,
                GROUP_CONCAT(DISTINCT i.chemin) as images
            FROM lieux l
            LEFT JOIN villes v ON l.id_ville = v.id
            LEFT JOIN images i ON l.id = i.id_lieu
            WHERE 
                LOWER(l.nom) LIKE LOWER(:search)
                OR LOWER(l.description) LIKE LOWER(:search)
                OR LOWER(v.nom) LIKE LOWER(:search)
            GROUP BY l.id
            ORDER BY
                CASE 
                    WHEN LOWER(l.nom) = LOWER(:exact) THEN 1
                    WHEN LOWER(l.nom) LIKE LOWER(:start) THEN 2
                    WHEN LOWER(v.nom) LIKE LOWER(:start) THEN 3
                    ELSE 4
                END,
                l.nom ASC
            LIMIT 8";

        $stmt = $pdo->prepare($sql);
        
        $searchParam = "%{$query}%";
        $startParam = "{$query}%";
        
        $stmt->execute([
            'search' => $searchParam,
            'exact' => $query,
            'start' => $startParam
        ]);

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $suggestions = array_map(function($item) use ($query) {
            // Nettoyer et préparer les données
            $nom = htmlspecialchars($item['nom']);
            $ville = htmlspecialchars($item['ville_nom'] ?? 'Non spécifiée');
            $categorie = htmlspecialchars($item['categorie']);
            $description = htmlspecialchars($item['description']);
            
            // Créer un extrait de la description
            $description = $description ? substr($description, 0, 100) . '...' : '';
            
            // Mettre en surbrillance la partie recherchée dans le nom
            $pattern = '/(' . preg_quote($query, '/') . ')/i';
            $nom_highlight = preg_replace($pattern, '<strong>$1</strong>', $nom);
            
            // Préparer les images
            $images = $item['images'] ? explode(',', $item['images']) : [];
            $firstImage = !empty($images) ? trim($images[0]) : '';
            
            return [
                'id' => $item['id'],
                'nom' => $nom,
                'nom_highlight' => $nom_highlight,
                'ville' => $ville,
                'categorie' => $categorie,
                'description' => $description,
                'budget' => $item['budget'],
                'image' => $firstImage
            ];
        }, $results);
        
        echo json_encode($suggestions);
    } else {
        echo json_encode([]);
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erreur de base de données']);
} 