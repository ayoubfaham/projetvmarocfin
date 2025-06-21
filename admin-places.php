<?php
session_start();
error_reporting(0);
ini_set('display_errors', 0);

try {
    $pdo = new PDO('mysql:host=localhost;dbname=vmaroc;charset=utf8', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<!-- Debug: Connexion à la base de données réussie -->";
    
    // Test simple pour vérifier l'accès aux données
    $test = $pdo->query("SELECT COUNT(*) FROM lieux")->fetchColumn();
    echo "<!-- Debug: Nombre total de lieux dans la base = " . $test . " -->";
    
    // Vérification des villes
    $villes = $pdo->query("SELECT id, nom FROM villes ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
    echo "<!-- Debug: Nombre de villes = " . count($villes) . " -->";
    echo "<!-- Debug: Première ville = " . json_encode(reset($villes)) . " -->";
    
    // Vérification des catégories
    $categories = $pdo->query("SELECT DISTINCT categorie FROM lieux WHERE categorie IS NOT NULL AND categorie != '' ORDER BY categorie")->fetchAll(PDO::FETCH_COLUMN);
    echo "<!-- Debug: Catégories disponibles = " . implode(", ", $categories) . " -->";
    
    // Affichage des 5 premiers lieux pour debug
    $test_lieux = $pdo->query("SELECT * FROM lieux LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    echo "<!-- Debug: 5 premiers lieux = " . json_encode($test_lieux) . " -->";
    
    // Récupération des filtres
    $search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
    $ville_filter = isset($_GET['ville_id']) ? intval($_GET['ville_id']) : 0;
    $categorie_filter = isset($_GET['categorie']) ? trim($_GET['categorie']) : '';

    // Construction de la requête SQL avec filtres
    $sql = "SELECT l.*, v.nom as ville_nom 
            FROM lieux l 
            LEFT JOIN villes v ON l.id_ville = v.id";
    $where_conditions = [];
    $params = [];

    if (!empty($search_query)) {
        $where_conditions[] = "(l.nom LIKE :search OR l.description LIKE :search OR v.nom LIKE :search)";
        $params['search'] = "%$search_query%";
    }

    if ($ville_filter > 0) {
        $where_conditions[] = "l.id_ville = :ville_id";
        $params['ville_id'] = $ville_filter;
    }

    if (!empty($categorie_filter)) {
        $where_conditions[] = "LOWER(l.categorie) = LOWER(:categorie)";
        $params['categorie'] = $categorie_filter;
    }

    if (!empty($where_conditions)) {
        $sql .= " WHERE " . implode(" AND ", $where_conditions);
    }

    $sql .= " ORDER BY l.id DESC";

    try {
        echo "<!-- Debug: Requête SQL = " . $sql . " -->";
        echo "<!-- Debug: Paramètres = " . json_encode($params) . " -->";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $places = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total_items = count($places);
        
        echo "<!-- Debug: Nombre total de lieux trouvés = " . $total_items . " -->";
        if ($total_items > 0) {
            echo "<!-- Debug: Premier lieu = " . json_encode($places[0]) . " -->";
        }
        
        // Vérification des filtres actifs
        echo "<!-- Debug: Filtres actifs - ";
        echo "Recherche: " . $search_query . ", ";
        echo "Ville: " . $ville_filter . ", ";
        echo "Catégorie: " . $categorie_filter . " -->";
        
    } catch (PDOException $e) {
        echo "<!-- Erreur SQL : " . $e->getMessage() . " -->";
        $places = [];
        $total_items = 0;
    }

    // Vérification de la variable $places avant l'affichage
    echo "<!-- Debug: places est-il un tableau ? " . (is_array($places) ? 'Oui' : 'Non') . " -->";
    echo "<!-- Debug: Contenu de places = " . json_encode(array_slice($places, 0, 2)) . " -->";

} catch (PDOException $e) {
    echo "<!-- Debug: ERREUR de connexion = " . $e->getMessage() . " -->";
    die("Erreur de connexion : " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Lieux</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2D796D;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        h1 {
            color: var(--primary-color);
            margin-bottom: 20px;
        }

        .filters-form {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .search-box {
            margin-bottom: 15px;
        }

        .form-control {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .filters {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .btn-submit {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
        }

        .table-responsive {
            overflow-x: auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .table th {
            background: #f8f9fa;
            font-weight: 600;
            color: var(--primary-color);
        }

        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
        }

        .badge-hotels { background: #e3f2fd; color: #1976d2; }
        .badge-restaurants { background: #fff3e0; color: #f57c00; }
        .badge-monuments { background: #e8f5e9; color: #388e3c; }
        .badge-economique { background: #e8f5e9; color: #388e3c; }
        .badge-moyen { background: #fff3e0; color: #f57c00; }
        .badge-luxe { background: #fce4ec; color: #c2185b; }

        .btn-edit,
        .btn-delete {
            padding: 4px 8px;
            border-radius: 4px;
            color: white;
            text-decoration: none;
            margin-right: 5px;
            font-size: 14px;
        }

        .btn-edit { background: var(--primary-color); }
        .btn-delete { background: #dc3545; }

        .thumbnail {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
        }

        .image-count {
            font-size: 12px;
            color: #666;
            margin-left: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>
            <i class="fas fa-map-marker-alt"></i>
            Liste des lieux (<?php echo $total_items; ?> au total)
        </h1>
        
        <form method="GET" action="" class="filters-form" novalidate>
            <div class="search-box">
                <input type="text" name="search" placeholder="Rechercher un lieu..." 
                       value="<?php echo htmlspecialchars($search_query); ?>" class="form-control">
            </div>
            <div class="filters">
                <select name="ville_id" class="form-control">
                    <option value="">Toutes les villes</option>
                    <?php foreach ($villes as $ville): ?>
                        <option value="<?php echo $ville['id']; ?>" 
                            <?php echo ($ville_filter == $ville['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($ville['nom']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="categorie" class="form-control">
                    <option value="">Toutes les catégories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>" 
                            <?php echo ($categorie_filter === $cat) ? 'selected' : ''; ?>>
                            <?php echo ucfirst(htmlspecialchars($cat)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn-submit">
                    <i class="fas fa-filter"></i> Appliquer les filtres
                </button>
            </div>
        </form>

        <div class="table-responsive">
            <?php
            echo "<!-- Debug: Avant l'affichage du tableau -->";
            echo "<!-- Debug: places est défini ? " . (isset($places) ? "Oui" : "Non") . " -->";
            echo "<!-- Debug: places est un tableau ? " . (is_array($places) ? "Oui" : "Non") . " -->";
            echo "<!-- Debug: Nombre d'éléments dans places : " . (isset($places) ? count($places) : "Non défini") . " -->";
            ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Actions</th>
                        <th>ID</th>
                        <th>Ville</th>
                        <th>Nom</th>
                        <th>Catégorie</th>
                        <th>Images</th>
                        <th>Description</th>
                        <th>Équipements</th>
                        <th>Coordonnées</th>
                        <th>URL</th>
                        <th>Budget</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    echo "<!-- Debug: Début de la boucle d'affichage -->";
                    if (empty($places)):
                        echo "<!-- Debug: \$places est vide -->";
                    ?>
                        <tr>
                            <td colspan="11" style="text-align: center;">
                                Aucun lieu trouvé
                                <?php echo "<!-- Debug: Filtres actifs - Recherche: '$search_query', Ville: '$ville_filter', Catégorie: '$categorie_filter' -->"; ?>
                            </td>
                        </tr>
                    <?php
                    else:
                        echo "<!-- Debug: Nombre de lieux à afficher : " . count($places) . " -->";
                        foreach ($places as $index => $place):
                            echo "<!-- Debug: Affichage du lieu #$index - ID: " . $place['id'] . " -->";
                    ?>
                        <tr>
                            <td>
                                <a href="?edit=<?php echo $place['id']; ?>" class="btn-edit" title="Modifier">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="?delete=<?php echo $place['id']; ?>" class="btn-delete" 
                                   onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce lieu ?')" title="Supprimer">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars($place['id']); ?></td>
                            <td><?php echo htmlspecialchars($place['ville_nom']); ?></td>
                            <td><?php echo htmlspecialchars($place['nom']); ?></td>
                            <td>
                                <?php if (!empty($place['categorie'])): ?>
                                    <span class="badge badge-<?php echo strtolower($place['categorie']); ?>">
                                        <?php echo ucfirst(htmlspecialchars($place['categorie'])); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                if (!empty($place['hero_images'])):
                                    $images = explode(',', $place['hero_images']);
                                    $firstImage = trim($images[0]);
                                    echo "<!-- Debug: Images pour le lieu #" . $place['id'] . " - Première image: $firstImage -->"; 
                                ?>
                                    <img src="../<?php echo htmlspecialchars($firstImage); ?>" 
                                         alt="<?php echo htmlspecialchars($place['nom']); ?>"
                                         class="thumbnail">
                                    <span class="image-count">
                                        <?php echo count($images); ?> image(s)
                                    </span>
                                <?php else: ?>
                                    <span class="no-image">Aucune image</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo !empty($place['description']) ? htmlspecialchars(substr($place['description'], 0, 100)) . '...' : ''; ?></td>
                            <td><?php echo !empty($place['equipements']) ? htmlspecialchars(substr($place['equipements'], 0, 100)) . '...' : ''; ?></td>
                            <td>
                                <?php if (!empty($place['latitude']) && !empty($place['longitude'])): ?>
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?php echo number_format($place['latitude'], 6) . ', ' . number_format($place['longitude'], 6); ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($place['url_activite'])): ?>
                                    <a href="<?php echo htmlspecialchars($place['url_activite']); ?>" 
                                       target="_blank" title="<?php echo htmlspecialchars($place['url_activite']); ?>">
                                        <i class="fas fa-link"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($place['budget'])): ?>
                                    <span class="badge badge-<?php echo strtolower($place['budget']); ?>">
                                        <?php echo ucfirst(htmlspecialchars($place['budget'])); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php
                        endforeach;
                        echo "<!-- Debug: Fin de la boucle d'affichage -->";
                    endif;
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html> 