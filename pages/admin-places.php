<?php
session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: admin-login.php');
    exit();
}

$pdo = new PDO('mysql:host=localhost;dbname=vmaroc;charset=utf8', 'root', '');

// Ajout d'un lieu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nom'])) {
    $nom = $_POST['nom'];
    $desc = $_POST['description'];
    $id_ville = intval($_POST['id_ville']);
    $categorie = $_POST['categorie'] ?? '';
    $url_activite = $_POST['url_activite'] ?? '';
    // Gestion des 4 photos
    $photo = $_POST['photo'] ?? '';
    $photo2 = $_POST['photo2'] ?? '';
    $photo3 = $_POST['photo3'] ?? '';
    $photo4 = $_POST['photo4'] ?? '';
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    // Upload photo 1
    if (isset($_FILES['photo_upload']) && $_FILES['photo_upload']['error'] === 0) {
        $filename = $_FILES['photo_upload']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $new_filename = uniqid() . '.' . $ext;
            $upload_path = '../uploads/lieux/' . $new_filename;
            if (!is_dir('../uploads/lieux')) {
                mkdir('../uploads/lieux', 0777, true);
            }
            if (move_uploaded_file($_FILES['photo_upload']['tmp_name'], $upload_path)) {
                $photo = 'uploads/lieux/' . $new_filename;
            }
        }
    }
    // Upload photo 2
    if (isset($_FILES['photo2_upload']) && $_FILES['photo2_upload']['error'] === 0) {
        $filename = $_FILES['photo2_upload']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $new_filename = uniqid() . '.' . $ext;
            $upload_path = '../uploads/lieux/' . $new_filename;
            if (!is_dir('../uploads/lieux')) {
                mkdir('../uploads/lieux', 0777, true);
            }
            if (move_uploaded_file($_FILES['photo2_upload']['tmp_name'], $upload_path)) {
                $photo2 = 'uploads/lieux/' . $new_filename;
            }
        }
    }
    // Upload photo 3
    if (isset($_FILES['photo3_upload']) && $_FILES['photo3_upload']['error'] === 0) {
        $filename = $_FILES['photo3_upload']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $new_filename = uniqid() . '.' . $ext;
            $upload_path = '../uploads/lieux/' . $new_filename;
            if (!is_dir('../uploads/lieux')) {
                mkdir('../uploads/lieux', 0777, true);
            }
            if (move_uploaded_file($_FILES['photo3_upload']['tmp_name'], $upload_path)) {
                $photo3 = 'uploads/lieux/' . $new_filename;
            }
        }
    }
    // Upload photo 4
    if (isset($_FILES['photo4_upload']) && $_FILES['photo4_upload']['error'] === 0) {
        $filename = $_FILES['photo4_upload']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $new_filename = uniqid() . '.' . $ext;
            $upload_path = '../uploads/lieux/' . $new_filename;
            if (!is_dir('../uploads/lieux')) {
                mkdir('../uploads/lieux', 0777, true);
            }
            if (move_uploaded_file($_FILES['photo4_upload']['tmp_name'], $upload_path)) {
                $photo4 = 'uploads/lieux/' . $new_filename;
            }
        }
    }
    try {
        $pdo->beginTransaction();
        
        // Insu00e9rer le lieu avec la catu00e9gorie
        $stmt = $pdo->prepare("INSERT INTO lieux (nom, photo, photo2, photo3, photo4, description, id_ville, url_activite, categorie) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nom, $photo, $photo2, $photo3, $photo4, $desc, $id_ville, $url_activite, $categorie]);
        $lieu_id = $pdo->lastInsertId();
        
        // Ru00e9cupu00e9rer le nom de la ville
        $stmt = $pdo->prepare("SELECT nom FROM villes WHERE id = ?");
        $stmt->execute([$id_ville]);
        $ville_nom = $stmt->fetchColumn();
        
        // Cru00e9er une recommandation liu00e9e au lieu si une catu00e9gorie est su00e9lectionnu00e9e
        if (!empty($categorie)) {
            // Du00e9finir les prix et duru00e9es par du00e9faut selon la catu00e9gorie
            $prix_durees = [
                'hotels' => ['prix_min' => 800, 'prix_max' => 2000, 'duree_min' => 1, 'duree_max' => 3],
                'restaurants' => ['prix_min' => 300, 'prix_max' => 1000, 'duree_min' => 1, 'duree_max' => 1],
                'parcs' => ['prix_min' => 100, 'prix_max' => 300, 'duree_min' => 1, 'duree_max' => 1],
                'plages' => ['prix_min' => 0, 'prix_max' => 200, 'duree_min' => 1, 'duree_max' => 1],
                'cinemas' => ['prix_min' => 100, 'prix_max' => 200, 'duree_min' => 1, 'duree_max' => 1],
                'theatres' => ['prix_min' => 200, 'prix_max' => 500, 'duree_min' => 1, 'duree_max' => 1],
                'monuments' => ['prix_min' => 100, 'prix_max' => 300, 'duree_min' => 1, 'duree_max' => 1],
                'musees' => ['prix_min' => 100, 'prix_max' => 300, 'duree_min' => 1, 'duree_max' => 2],
                'shopping' => ['prix_min' => 400, 'prix_max' => 1500, 'duree_min' => 1, 'duree_max' => 2],
                'vie_nocturne' => ['prix_min' => 300, 'prix_max' => 1000, 'duree_min' => 1, 'duree_max' => 1]
            ];
            
            // Titre et description par défaut selon la catégorie
            $titres_descriptions = [
                'hotels' => [
                    'titre' => 'Séjour à ' . $nom,
                    'description' => 'Profitez d\'un séjour confortable à ' . $nom . ', un hôtel de qualité à ' . $ville_nom . '.'
                ],
                'restaurants' => [
                    'titre' => 'Dégustation à ' . $nom,
                    'description' => 'Savourez une expérience culinaire unique à ' . $nom . ', un restaurant réputé de ' . $ville_nom . '.'
                ],
                'parcs' => [
                    'titre' => 'Promenade à ' . $nom,
                    'description' => 'Profitez d\'une promenade relaxante au ' . $nom . ', un parc magnifique de ' . $ville_nom . '.'
                ],
                'plages' => [
                    'titre' => 'Détente à ' . $nom,
                    'description' => 'Relaxez-vous sur la plage de ' . $nom . ', un lieu de détente idéal à ' . $ville_nom . '.'
                ],
                'cinemas' => [
                    'titre' => 'Séance au cinéma ' . $nom,
                    'description' => 'Profitez d\'une séance de cinéma au ' . $nom . ', une salle moderne à ' . $ville_nom . '.'
                ],
                'theatres' => [
                    'titre' => 'Spectacle au théâtre ' . $nom,
                    'description' => 'Assistez à un spectacle captivant au théâtre ' . $nom . ' de ' . $ville_nom . '.'
                ],
                'monuments' => [
                    'titre' => 'Visite de ' . $nom,
                    'description' => 'Découvrez ' . $nom . ', un monument historique incontournable de ' . $ville_nom . '.'
                ],
                'musees' => [
                    'titre' => 'Visite du musée ' . $nom,
                    'description' => 'Explorez les collections fascinantes du musée ' . $nom . ' à ' . $ville_nom . '.'
                ],
                'shopping' => [
                    'titre' => 'Shopping à ' . $nom,
                    'description' => 'Faites du shopping à ' . $nom . ', une destination incontournable pour les achats à ' . $ville_nom . '.'
                ],
                'vie_nocturne' => [
                    'titre' => 'Soirée à ' . $nom,
                    'description' => 'Profitez de la vie nocturne à ' . $nom . ', un lieu branché de ' . $ville_nom . '.'
                ]
            ];
            
            // Utiliser les valeurs par du00e9faut pour la catu00e9gorie su00e9lectionnu00e9e
            $prix_min = $prix_durees[$categorie]['prix_min'];
            $prix_max = $prix_durees[$categorie]['prix_max'];
            $duree_min = $prix_durees[$categorie]['duree_min'];
            $duree_max = $prix_durees[$categorie]['duree_max'];
            $titre = $titres_descriptions[$categorie]['titre'];
            $description = $titres_descriptions[$categorie]['description'];
            
            // Insu00e9rer la recommandation
            $stmt = $pdo->prepare("INSERT INTO recommandations (ville_id, titre, description, categorie, prix_min, prix_max, duree_min, duree_max, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$id_ville, $titre, $description, $categorie, $prix_min, $prix_max, $duree_min, $duree_max, $photo]);
        }
        
        $pdo->commit();
        // Rediriger vers la page des lieux filtrée par la ville sélectionnée
        header('Location: admin-places.php?ville_id=' . $id_ville);
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Erreur : " . $e->getMessage();
    }
}

// Modification d'un lieu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $edit_id = intval($_POST['edit_id']);
    $nom = $_POST['nom'];
    $desc = $_POST['description'];
    $id_ville = intval($_POST['id_ville']);
    $categorie = $_POST['categorie'] ?? '';
    $url_activite = $_POST['url_activite'] ?? '';
    $photo = $_POST['photo'] ?? '';
    $photo2 = $_POST['photo2'] ?? '';
    $photo3 = $_POST['photo3'] ?? '';
    $photo4 = $_POST['photo4'] ?? '';
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];

    // Upload photo 1
    if (isset($_FILES['photo_upload']) && $_FILES['photo_upload']['error'] === 0) {
        $filename = $_FILES['photo_upload']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $new_filename = uniqid() . '.' . $ext;
            $upload_path = '../uploads/lieux/' . $new_filename;
            if (!is_dir('../uploads/lieux')) {
                mkdir('../uploads/lieux', 0777, true);
            }
            if (move_uploaded_file($_FILES['photo_upload']['tmp_name'], $upload_path)) {
                $photo = 'uploads/lieux/' . $new_filename;
            }
        }
    }
    // Upload photo 2
    if (isset($_FILES['photo2_upload']) && $_FILES['photo2_upload']['error'] === 0) {
        $filename = $_FILES['photo2_upload']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $new_filename = uniqid() . '.' . $ext;
            $upload_path = '../uploads/lieux/' . $new_filename;
            if (!is_dir('../uploads/lieux')) {
                mkdir('../uploads/lieux', 0777, true);
            }
            if (move_uploaded_file($_FILES['photo2_upload']['tmp_name'], $upload_path)) {
                $photo2 = 'uploads/lieux/' . $new_filename;
            }
        }
    }
    // Upload photo 3
    if (isset($_FILES['photo3_upload']) && $_FILES['photo3_upload']['error'] === 0) {
        $filename = $_FILES['photo3_upload']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $new_filename = uniqid() . '.' . $ext;
            $upload_path = '../uploads/lieux/' . $new_filename;
            if (!is_dir('../uploads/lieux')) {
                mkdir('../uploads/lieux', 0777, true);
            }
            if (move_uploaded_file($_FILES['photo3_upload']['tmp_name'], $upload_path)) {
                $photo3 = 'uploads/lieux/' . $new_filename;
            }
        }
    }
    // Upload photo 4
    if (isset($_FILES['photo4_upload']) && $_FILES['photo4_upload']['error'] === 0) {
        $filename = $_FILES['photo4_upload']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $new_filename = uniqid() . '.' . $ext;
            $upload_path = '../uploads/lieux/' . $new_filename;
            if (!is_dir('../uploads/lieux')) {
                mkdir('../uploads/lieux', 0777, true);
            }
            if (move_uploaded_file($_FILES['photo4_upload']['tmp_name'], $upload_path)) {
                $photo4 = 'uploads/lieux/' . $new_filename;
            }
        }
    }

    try {
        $pdo->beginTransaction();
        
        // Mettre à jour le lieu avec la catégorie
        $stmt = $pdo->prepare("UPDATE lieux SET nom = ?, photo = ?, photo2 = ?, photo3 = ?, photo4 = ?, description = ?, id_ville = ?, url_activite = ?, categorie = ? WHERE id = ?");
        $stmt->execute([$nom, $photo, $photo2, $photo3, $photo4, $desc, $id_ville, $url_activite, $categorie, $edit_id]);
        
        // Récupérer le nom de la ville
        $stmt = $pdo->prepare("SELECT nom FROM villes WHERE id = ?");
        $stmt->execute([$id_ville]);
        $ville_nom = $stmt->fetchColumn();
        
        // Vérifier si une recommandation existe déjà pour ce lieu
        $stmt = $pdo->prepare("SELECT id FROM recommandations WHERE ville_id = ? AND titre LIKE ?");
        $stmt->execute([$id_ville, 'Visite de ' . $nom . '%']);
        $recommandation_id = $stmt->fetchColumn();
        
        if (!empty($categorie)) {
            // Définir les prix et durées par défaut selon la catégorie
            $prix_durees = [
                'hotels' => ['prix_min' => 800, 'prix_max' => 2000, 'duree_min' => 1, 'duree_max' => 3],
                'restaurants' => ['prix_min' => 300, 'prix_max' => 1000, 'duree_min' => 1, 'duree_max' => 1],
                'parcs' => ['prix_min' => 100, 'prix_max' => 300, 'duree_min' => 1, 'duree_max' => 1],
                'plages' => ['prix_min' => 0, 'prix_max' => 200, 'duree_min' => 1, 'duree_max' => 1],
                'cinemas' => ['prix_min' => 100, 'prix_max' => 200, 'duree_min' => 1, 'duree_max' => 1],
                'theatres' => ['prix_min' => 200, 'prix_max' => 500, 'duree_min' => 1, 'duree_max' => 1],
                'monuments' => ['prix_min' => 100, 'prix_max' => 300, 'duree_min' => 1, 'duree_max' => 1],
                'musees' => ['prix_min' => 100, 'prix_max' => 300, 'duree_min' => 1, 'duree_max' => 2],
                'shopping' => ['prix_min' => 400, 'prix_max' => 1500, 'duree_min' => 1, 'duree_max' => 2],
                'vie_nocturne' => ['prix_min' => 300, 'prix_max' => 1000, 'duree_min' => 1, 'duree_max' => 1]
            ];
            
            // Titre et description par défaut selon la catégorie
            $titres_descriptions = [
                'hotels' => [
                    'titre' => 'Séjour à ' . $nom,
                    'description' => 'Profitez d\'un séjour confortable à ' . $nom . ', un hôtel de qualité à ' . $ville_nom . '.'
                ],
                'restaurants' => [
                    'titre' => 'Dégustation à ' . $nom,
                    'description' => 'Savourez une expérience culinaire unique à ' . $nom . ', un restaurant réputé de ' . $ville_nom . '.'
                ],
                'parcs' => [
                    'titre' => 'Promenade à ' . $nom,
                    'description' => 'Profitez d\'une promenade relaxante au ' . $nom . ', un parc magnifique de ' . $ville_nom . '.'
                ],
                'plages' => [
                    'titre' => 'Détente à ' . $nom,
                    'description' => 'Relaxez-vous sur la plage de ' . $nom . ', un lieu de détente idéal à ' . $ville_nom . '.'
                ],
                'cinemas' => [
                    'titre' => 'Séance au cinéma ' . $nom,
                    'description' => 'Profitez d\'une séance de cinéma au ' . $nom . ', une salle moderne à ' . $ville_nom . '.'
                ],
                'theatres' => [
                    'titre' => 'Spectacle au théâtre ' . $nom,
                    'description' => 'Assistez à un spectacle captivant au théâtre ' . $nom . ' de ' . $ville_nom . '.'
                ],
                'monuments' => [
                    'titre' => 'Visite de ' . $nom,
                    'description' => 'Découvrez ' . $nom . ', un monument historique incontournable de ' . $ville_nom . '.'
                ],
                'musees' => [
                    'titre' => 'Visite du musée ' . $nom,
                    'description' => 'Explorez les collections fascinantes du musée ' . $nom . ' à ' . $ville_nom . '.'
                ],
                'shopping' => [
                    'titre' => 'Shopping à ' . $nom,
                    'description' => 'Faites du shopping à ' . $nom . ', une destination incontournable pour les achats à ' . $ville_nom . '.'
                ],
                'vie_nocturne' => [
                    'titre' => 'Soirée à ' . $nom,
                    'description' => 'Profitez de la vie nocturne à ' . $nom . ', un lieu branché de ' . $ville_nom . '.'
                ]
            ];
            
            // Utiliser les valeurs par défaut pour la catégorie sélectionnée
            $prix_min = $prix_durees[$categorie]['prix_min'];
            $prix_max = $prix_durees[$categorie]['prix_max'];
            $duree_min = $prix_durees[$categorie]['duree_min'];
            $duree_max = $prix_durees[$categorie]['duree_max'];
            $titre = $titres_descriptions[$categorie]['titre'];
            $description = $titres_descriptions[$categorie]['description'];
            
            if ($recommandation_id) {
                // Mettre à jour la recommandation existante
                $stmt = $pdo->prepare("UPDATE recommandations SET titre = ?, description = ?, categorie = ?, prix_min = ?, prix_max = ?, duree_min = ?, duree_max = ?, image_url = ? WHERE id = ?");
                $stmt->execute([$titre, $description, $categorie, $prix_min, $prix_max, $duree_min, $duree_max, $photo, $recommandation_id]);
            } else {
                // Créer une nouvelle recommandation
                $stmt = $pdo->prepare("INSERT INTO recommandations (ville_id, titre, description, categorie, prix_min, prix_max, duree_min, duree_max, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$id_ville, $titre, $description, $categorie, $prix_min, $prix_max, $duree_min, $duree_max, $photo]);
            }
        }
        
        $pdo->commit();
        // Rediriger vers la page des lieux filtrée par la ville sélectionnée
        header('Location: admin-places.php?ville_id=' . $id_ville);
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Erreur : " . $e->getMessage();
    }
}

// Suppression d'un lieu
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $pdo->prepare("DELETE FROM lieux WHERE id = ?")->execute([$id]);
    header('Location: admin-places.php');
    exit();
}

// Préparation de l'édition
if (isset($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM lieux WHERE id = ?");
    $stmt->execute([$editId]);
    $editPlace = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($editPlace) {
        $editMode = true;
    }
}

// Liste des lieux et villes
$ville_filter = isset($_GET['ville_id']) ? intval($_GET['ville_id']) : 0;

if ($ville_filter > 0) {
    // Filtrer les lieux par ville si un ID de ville est spécifié
    $stmt = $pdo->prepare("SELECT lieux.*, villes.nom AS ville_nom FROM lieux JOIN villes ON lieux.id_ville = villes.id WHERE lieux.id_ville = ?");
    $stmt->execute([$ville_filter]);
    $places = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer le nom de la ville filtrée pour l'afficher
    $stmt = $pdo->prepare("SELECT nom FROM villes WHERE id = ?");
    $stmt->execute([$ville_filter]);
    $filtered_city_name = $stmt->fetchColumn();
} else {
    // Afficher tous les lieux si aucun filtre n'est spécifié
    $places = $pdo->query("SELECT lieux.*, villes.nom AS ville_nom FROM lieux JOIN villes ON lieux.id_ville = villes.id")->fetchAll(PDO::FETCH_ASSOC);
}

$cities = $pdo->query("SELECT * FROM villes")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Admin - Gestion des lieux</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/main.css">
    <style>
        body, .admin-table, .admin-table td, .admin-table th, .form-group, .form-group input, .form-group select, .form-group textarea {
            font-size: 0.91rem;
        }
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: var(--white);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow-md);
            min-width: 700px;
        }
        .admin-table th,
        .admin-table td {
            padding: 16px 12px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        .admin-table th {
            background: var(--primary-color);
            color: var(--white);
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .admin-table tr:nth-child(even) {
            background: #f8f8f8;
        }
        .admin-table tr:hover {
            background: #f1f1f1;
        }
        .place-thumb {
            width: 48px;
            height: 48px;
            object-fit: cover;
            border-radius: 8px;
            background: #eee;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .btn-delete {
            background: #dc3545;
            color: #fff;
            border: none;
            padding: 8px 18px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.2s;
        }
        .btn-delete:hover {
            background: #b52a37;
        }
        @media (max-width: 900px) {
            .admin-table, .admin-table thead, .admin-table tbody, .admin-table th, .admin-table td, .admin-table tr {
                display: block;
            }
            .admin-table thead tr {
                display: none;
            }
            .admin-table tr {
                margin-bottom: 18px;
                border-radius: 8px;
                box-shadow: var(--shadow-md);
                background: #fff;
            }
            .admin-table td {
                padding: 12px 16px;
                border: none;
                position: relative;
            }
            .admin-table td:before {
                content: attr(data-label);
                font-weight: 600;
                color: var(--primary-color);
                display: block;
                margin-bottom: 6px;
                text-transform: uppercase;
                font-size: 0.9em;
            }
        }
        .admin-filters-bar {
            display: flex;
            gap: 18px;
            align-items: center;
            justify-content: center;
            margin: 0 0 32px 0;
            flex-wrap: wrap;
        }
        .admin-filters-bar select {
            padding: 10px 18px;
            border: 1.5px solid var(--primary-color, #b48a3c);
            border-radius: 8px;
            background: #faf9f7;
            color: #222;
            font-size: 1.05rem;
            box-shadow: 0 2px 8px rgba(180,138,60,0.07);
            transition: border 0.2s, box-shadow 0.2s;
            outline: none;
            min-width: 180px;
        }
        .admin-filters-bar select:focus {
            border-color: var(--accent-color, #d4af37);
            box-shadow: 0 4px 16px rgba(180,138,60,0.13);
            background: #fff;
        }
        .search-bar-admin {
            max-width: 400px;
            margin: 0;
            position: relative;
        }
        .search-bar-admin input[type="text"] {
            width: 100%;
            padding: 12px 16px;
            border: 1.5px solid var(--primary-color, #b48a3c);
            border-radius: 8px;
            font-size: 1.08rem;
            background: #faf9f7;
            color: #222;
            box-shadow: 0 2px 8px rgba(180,138,60,0.07);
            transition: border 0.2s, box-shadow 0.2s;
            outline: none;
        }
        .search-bar-admin input[type="text"]:focus {
            border-color: var(--accent-color, #d4af37);
            box-shadow: 0 4px 16px rgba(180,138,60,0.13);
            background: #fff;
        }
        #suggestionsLieu {
            position: absolute;
            z-index: 10;
            width: 100%;
            background: #fff;
            border: 1.5px solid var(--primary-color, #b48a3c);
            border-top: none;
            border-radius: 0 0 8px 8px;
            box-shadow: 0 8px 24px rgba(180,138,60,0.10);
            display: none;
            max-height: 220px;
            overflow-y: auto;
        }
        #suggestionsLieu div {
            padding: 12px 16px;
            cursor: pointer;
            font-size: 1.05rem;
            color: #222;
            transition: background 0.15s;
        }
        #suggestionsLieu div:hover {
            background: #f7f3e7;
            color: var(--primary-color, #b48a3c);
        }
    </style>
</head>
<body>
    <!-- Header/Navbar moderne -->
    <header>
        <div class="container header-container">
            <a href="../index.php" class="logo">
                <img src="https://i.postimg.cc/g07GgLp5/VMaroc-logo-trf.png" alt="Maroc Authentique" class="logo-img" style="height:70px;">
            </a>
            <ul class="nav-menu">
                <li><a href="../index.php">Accueil</a></li>
                <li><a href="../destinations.php">Destinations</a></li>
                <li><a href="../recommandations.php">Recommandations</a></li>
            </ul>
            <div class="auth-buttons">
                <a href="admin-panel.php" class="btn-outline" style="margin-right:10px;">Panel Admin</a>
                <a href="../logout.php" class="btn-primary">Déconnexion</a>
            </div>
        </div>
    </header>

    <main style="margin-top:100px;">
    <div class="container">
            <div class="section-title">
                <h2>Gestion des lieux</h2>
                <?php if (isset($ville_filter) && $ville_filter > 0 && isset($filtered_city_name)): ?>
                <div style="text-align: center; margin-top: 10px;">
                    <p style="color: var(--primary-color); font-weight: 500;">Lieux filtrés pour la ville de <strong><?= htmlspecialchars($filtered_city_name) ?></strong></p>
                    <a href="admin-places.php" class="btn-outline" style="display: inline-block; margin-top: 10px;">Voir tous les lieux</a>
                </div>
                <?php endif; ?>
            </div>
            <section class="section">
                <div class="form" style="max-width:600px;margin:0 auto 40px auto;">
                    <h3 style="text-align:center;">
                        <?= isset($editMode) && $editMode ? 'Modifier le lieu' : 'Ajouter un lieu' ?>
                    </h3>
        <form method="post" enctype="multipart/form-data">
                        <?php if (isset($editMode) && $editMode): ?>
                            <input type="hidden" name="edit_id" value="<?= htmlspecialchars($editPlace['id']) ?>">
                        <?php endif; ?>
                        <div class="form-group">
                            <input type="text" name="nom" class="form-control" placeholder="Nom du lieu" required value="<?= isset($editPlace) ? htmlspecialchars($editPlace['nom']) : '' ?>">
                        </div>
                        <div class="form-group">
                            <input type="text" name="photo" class="form-control" placeholder="URL ou nom du fichier photo 1" value="<?= isset($editPlace) ? htmlspecialchars($editPlace['photo']) : '' ?>">
                        </div>
                        <div class="form-group">
                            <label for="photo_upload">Ou uploader une image 1 :</label>
                            <input type="file" name="photo_upload" id="photo_upload" accept="image/*">
                        </div>
                        <div class="form-group">
                            <input type="text" name="photo2" class="form-control" placeholder="URL ou nom du fichier photo 2" value="<?= isset($editPlace) ? htmlspecialchars($editPlace['photo2'] ?? '') : '' ?>">
                        </div>
                        <div class="form-group">
                            <label for="photo2_upload">Ou uploader une image 2 :</label>
                            <input type="file" name="photo2_upload" id="photo2_upload" accept="image/*">
                        </div>
                        <div class="form-group">
                            <input type="text" name="photo3" class="form-control" placeholder="URL ou nom du fichier photo 3" value="<?= isset($editPlace) ? htmlspecialchars($editPlace['photo3'] ?? '') : '' ?>">
                        </div>
                        <div class="form-group">
                            <label for="photo3_upload">Ou uploader une image 3 :</label>
                            <input type="file" name="photo3_upload" id="photo3_upload" accept="image/*">
                        </div>
                        <div class="form-group">
                            <input type="text" name="photo4" class="form-control" placeholder="URL ou nom du fichier photo 4" value="<?= isset($editPlace) ? htmlspecialchars($editPlace['photo4'] ?? '') : '' ?>">
                        </div>
                        <div class="form-group">
                            <label for="photo4_upload">Ou uploader une image 4 :</label>
                            <input type="file" name="photo4_upload" id="photo4_upload" accept="image/*">
                        </div>
                        <div class="form-group">
                            <input type="text" name="description" class="form-control" placeholder="Description" required value="<?= isset($editPlace) ? htmlspecialchars($editPlace['description']) : '' ?>">
                        </div>
                        <div class="form-group">
                            <input type="text" name="url_activite" class="form-control" placeholder="URL de l'activité (ex: https://www.hotel.com)" value="<?= isset($editPlace) ? htmlspecialchars($editPlace['url_activite'] ?? '') : '' ?>">
                        </div>
                        <div class="form-group">
                            <select name="id_ville" class="form-control" required>
                                <option value="">Ville</option>
                                <?php foreach ($cities as $city): ?>
                                    <option value="<?= $city['id'] ?>" <?= (isset($editPlace) && $editPlace['id_ville'] == $city['id']) || (!isset($editPlace) && isset($ville_filter) && $ville_filter == $city['id']) ? 'selected' : '' ?>><?= htmlspecialchars($city['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <select name="categorie" class="form-control" required>
                                <option value="">Catu00e9gorie</option>
                                <option value="hotels" <?= (isset($editPlace) && $editPlace['categorie'] == 'hotels') ? 'selected' : '' ?>>Hôtels</option>
                                <option value="restaurants" <?= (isset($editPlace) && $editPlace['categorie'] == 'restaurants') ? 'selected' : '' ?>>Restaurants</option>
                                <option value="parcs" <?= (isset($editPlace) && $editPlace['categorie'] == 'parcs') ? 'selected' : '' ?>>Parcs</option>
                                <option value="plages" <?= (isset($editPlace) && $editPlace['categorie'] == 'plages') ? 'selected' : '' ?>>Plages</option>
                                <option value="cinemas" <?= (isset($editPlace) && $editPlace['categorie'] == 'cinemas') ? 'selected' : '' ?>>Cinémas</option>
                                <option value="theatres" <?= (isset($editPlace) && $editPlace['categorie'] == 'theatres') ? 'selected' : '' ?>>Théâtres</option>
                                <option value="monuments" <?= (isset($editPlace) && $editPlace['categorie'] == 'monuments') ? 'selected' : '' ?>>Monuments</option>
                                <option value="musees" <?= (isset($editPlace) && $editPlace['categorie'] == 'musees') ? 'selected' : '' ?>>Musées</option>
                                <option value="shopping" <?= (isset($editPlace) && $editPlace['categorie'] == 'shopping') ? 'selected' : '' ?>>Shopping</option>
                                <option value="vie_nocturne" <?= (isset($editPlace) && $editPlace['categorie'] == 'vie_nocturne') ? 'selected' : '' ?>>Vie nocturne</option>
                            </select>
                        </div>
                        <button type="submit" class="btn-solid" style="width:100%;">
                            <?= isset($editMode) && $editMode ? 'Enregistrer' : 'Ajouter' ?>
                        </button>
                        <?php if (isset($editMode) && $editMode): ?>
                            <a href="admin-places.php" class="btn-outline" style="min-width:100px;">Annuler</a>
                        <?php endif; ?>
        </form>
                </div>

                <div class="section-title">
                    <h3>Liste des lieux</h3>
                </div>
                <!-- Filtres combinés -->
                <div class="admin-filters-bar">
                    <select id="filterVille" class="form-control">
                        <option value="">Toutes les villes</option>
                        <?php foreach ($cities as $city): ?>
                            <option value="<?= htmlspecialchars($city['nom']) ?>"><?= htmlspecialchars($city['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select id="filterCategorie" class="form-control">
                        <option value="">Toutes les catégories</option>
                        <?php
                        $categories = [];
                        foreach ($places as $place) {
                            if (!empty($place['categorie']) && !in_array($place['categorie'], $categories)) {
                                $categories[] = $place['categorie'];
                            }
                        }
                        foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="search-bar-admin">
                        <input type="text" id="searchLieu" class="form-control" placeholder="Rechercher un lieu..." autocomplete="off">
                        <div id="suggestionsLieu"></div>
                    </div>
                </div>
                <div style="overflow-x:auto;">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nom</th>
                                <th>Photo</th>
                                <th>Description</th>
                                <th>Ville</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
            <?php foreach ($places as $place): ?>
                <tr data-ville="<?= htmlspecialchars($place['ville_nom']) ?>" data-categorie="<?= htmlspecialchars($place['categorie'] ?? '') ?>">
                                    <td data-label="ID"><?= $place['id'] ?></td>
                                    <td data-label="Nom"><?= htmlspecialchars($place['nom']) ?></td>
                                    <td data-label="Photo">
                                        <?php if ($place['photo']): ?>
                                            <?php if (strpos($place['photo'], 'uploads/') === 0): ?>
                                                <img src="../<?= htmlspecialchars($place['photo']) ?>" alt="<?= htmlspecialchars($place['nom']) ?>" class="place-thumb">
                                            <?php else: ?>
                                                <img src="<?= htmlspecialchars($place['photo']) ?>" alt="<?= htmlspecialchars($place['nom']) ?>" class="place-thumb">
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <div class="place-thumb"><i class="fas fa-image" style="color:#bbb;font-size:1.5em;"></i></div>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Description"><?= htmlspecialchars($place['description']) ?></td>
                                    <td data-label="Ville"><?= htmlspecialchars($place['ville_nom']) ?></td>
                                    <td data-label="Action">
                                        <div class="admin-actions">
                                            <a href="?edit=<?= $place['id'] ?>" class="btn-outline" style="padding:4px 12px;font-size:0.9rem;">Modifier</a>
                                            <a href="?delete=<?= $place['id'] ?>" class="btn-delete" style="padding:4px 12px;font-size:0.9rem;" onclick="return confirm('Supprimer ce lieu ?')">Supprimer</a>
                                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
                        </tbody>
        </table>
    </div>
            </section>
        </div>
    </main>
    <script>
    // Récupérer tous les noms de lieux côté JS
    const lieux = [
        <?php foreach ($places as $place): ?>
            "<?= addslashes($place['nom']) ?>",
        <?php endforeach; ?>
    ];
    const tableRows = document.querySelectorAll('.admin-table tbody tr');
    const searchInput = document.getElementById('searchLieu');
    const suggestionsBox = document.getElementById('suggestionsLieu');

    searchInput.addEventListener('input', function() {
        const val = this.value.toLowerCase();
        suggestionsBox.innerHTML = '';
        if (val.length === 0) {
            suggestionsBox.style.display = 'none';
            tableRows.forEach(row => row.style.display = '');
            return;
        }
        // Suggestions
        const suggestions = lieux.filter(nom => nom.toLowerCase().includes(val));
        if (suggestions.length > 0) {
            suggestionsBox.style.display = 'block';
            suggestions.forEach(sugg => {
                const div = document.createElement('div');
                div.textContent = sugg;
                div.style.padding = '8px 12px';
                div.style.cursor = 'pointer';
                div.addEventListener('mousedown', function(e) {
                    e.preventDefault();
                    searchInput.value = sugg;
                    suggestionsBox.style.display = 'none';
                    // Filtrer la table
                    tableRows.forEach(row => {
                        const nomCell = row.querySelector('td[data-label="Nom"]');
                        if (nomCell && nomCell.textContent.trim() === sugg) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                });
                suggestionsBox.appendChild(div);
            });
        } else {
            suggestionsBox.style.display = 'none';
        }
        // Filtrage live (affiche toutes les lignes qui contiennent la lettre)
        tableRows.forEach(row => {
            const nomCell = row.querySelector('td[data-label="Nom"]');
            if (nomCell && nomCell.textContent.toLowerCase().includes(val)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
    // Cacher suggestions si clic ailleurs
    window.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !suggestionsBox.contains(e.target)) {
            suggestionsBox.style.display = 'none';
        }
    });

    // Filtres combinés ville + catégorie
    const filterCategorie = document.getElementById('filterCategorie');
    function applyFilters() {
        const ville = filterVille.value;
        const categorie = filterCategorie.value;
        tableRows.forEach(row => {
            const rowVille = row.getAttribute('data-ville');
            const rowCat = row.getAttribute('data-categorie');
            const villeOk = !ville || rowVille === ville;
            const catOk = !categorie || rowCat === categorie;
            row.style.display = (villeOk && catOk) ? '' : 'none';
        });
        // Réinitialise la recherche
        searchInput.value = '';
        suggestionsBox.style.display = 'none';
    }
    filterVille.addEventListener('change', applyFilters);
    filterCategorie.addEventListener('change', applyFilters);
    </script>
</body>
</html> 