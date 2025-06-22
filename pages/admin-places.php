<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

try {
$pdo = new PDO('mysql:host=localhost;dbname=vmaroc;charset=utf8', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Erreur de connexion : " . $e->getMessage();
    die();
}

// Récupération des villes avec vérification
try {
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'villes'");
    $stmt->execute();
    if ($stmt->rowCount() == 0) {
        echo "La table 'villes' n'existe pas";
    } else {
        $stmt = $pdo->query("SELECT id, nom FROM villes ORDER BY nom");
        $villes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<!-- Nombre de villes : " . count($villes) . " -->";
        if (count($villes) == 0) {
            echo "<!-- Aucune ville trouvée dans la base de données -->";
        }
    }
} catch (PDOException $e) {
    echo "Erreur lors de la récupération des villes : " . $e->getMessage();
    $villes = [];
}

// Récupération des catégories avec vérification
try {
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'lieux'");
    $stmt->execute();
    if ($stmt->rowCount() == 0) {
        echo "La table 'lieux' n'existe pas";
    } else {
        $stmt = $pdo->query("SELECT DISTINCT categorie FROM lieux WHERE categorie IS NOT NULL AND categorie != '' ORDER BY categorie");
        $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
        if (empty($categories)) {
            $categories = [
                'hotels', 'restaurants', 'monuments', 'plages', 
                'parcs', 'shopping', 'cinemas', 'musees', 
                'sport', 'culture', 'vie_nocturne'
            ];
        }
    }
} catch (PDOException $e) {
    echo "Erreur lors de la récupération des catégories : " . $e->getMessage();
    $categories = [
        'hotels', 'restaurants', 'monuments', 'plages', 
        'parcs', 'shopping', 'cinemas', 'musees', 
        'sport', 'culture', 'vie_nocturne'
    ];
}

// Récupération des filtres actuels
$ville_filter = isset($_GET['ville']) ? $_GET['ville'] : '';
$categorie_filter = isset($_GET['categorie']) ? $_GET['categorie'] : '';

// Récupérer les statistiques
try {
    $stats = [
        'lieux' => $pdo->query("SELECT COUNT(*) FROM lieux")->fetchColumn(),
        'avis' => $pdo->query("SELECT COUNT(*) FROM avis WHERE id_lieu IS NOT NULL")->fetchColumn(),
        'images' => $pdo->query("SELECT COUNT(*) FROM lieux WHERE hero_images IS NOT NULL")->fetchColumn()
    ];
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des statistiques : " . $e->getMessage());
    $stats = ['lieux' => 0, 'avis' => 0, 'images' => 0];
}

// Récupérer les villes pour le formulaire
try {
    $cities = $pdo->query("SELECT id, nom FROM villes ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des villes : " . $e->getMessage());
    $cities = [];
}

// Initialisation des variables de filtrage
$ville_filter = isset($_GET['ville_id']) ? intval($_GET['ville_id']) : 0;
$categorie_filter = isset($_GET['categorie']) ? trim($_GET['categorie']) : '';

// Récupérer les filtres
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$ville_filter = isset($_GET['ville_id']) ? intval($_GET['ville_id']) : 0;
$categorie_filter = isset($_GET['categorie']) ? trim($_GET['categorie']) : '';

// Construction de la requête SQL de base
$sql = "SELECT l.*, v.nom as ville_nom 
        FROM lieux l 
        LEFT JOIN villes v ON l.id_ville = v.id 
        WHERE 1=1";
$params = [];

// Ajouter les conditions de filtrage
if (!empty($_GET['search'])) {
    $search = '%' . trim($_GET['search']) . '%';
    $sql .= " AND (l.nom LIKE :search OR l.description LIKE :search)";
    $params['search'] = $search;
}

if (!empty($_GET['ville_id'])) {
    $sql .= " AND l.id_ville = :ville_id";
    $params['ville_id'] = intval($_GET['ville_id']);
}

if (!empty($_GET['categorie'])) {
    $sql .= " AND l.categorie = :categorie";
    $params['categorie'] = trim($_GET['categorie']);
}

$sql .= " ORDER BY l.id ASC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $places = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total_items = count($places);
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des lieux : " . $e->getMessage());
    $places = [];
    $total_items = 0;
}

// Vérifier la connexion à la base de données
try {
    $test = $pdo->query("SELECT COUNT(*) FROM lieux")->fetchColumn();
    error_log("Nombre total de lieux dans la base : " . $test);
} catch (PDOException $e) {
    error_log("Erreur de connexion à la base de données : " . $e->getMessage());
}

// Initialiser les autres variables pour éviter les warnings
$items_per_page = null;
$offset = null;
$filtered_city_name = '';

// --- REMOVED: Database Connection Test Echos ---
// if ($pdo) {
//     echo '<div style="color: green; font-weight: bold;">Database connection successful!</div>';
// } else {
//     echo '<div style="color: red; font-weight: bold;">Database connection failed!</div>';
//     // Depending on the error, the script might stop here anyway
// }
// --- End REMOVED Database Connection Test Echos ---

// Ajout d'un lieu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nom']) && !isset($_POST['edit_id'])) {
    try {
        // Validation des données
        $nom = trim($_POST['nom']);
        $ville_id = isset($_POST['id_ville']) ? intval($_POST['id_ville']) : 0;
        $categorie = isset($_POST['categorie']) ? trim($_POST['categorie']) : '';
        $url = isset($_POST['url_activite']) ? trim($_POST['url_activite']) : '';
        $adresse = isset($_POST['adresse']) ? trim($_POST['adresse']) : '';
        $budget = isset($_POST['budget']) ? trim($_POST['budget']) : '';
        $latitude = isset($_POST['latitude']) ? trim($_POST['latitude']) : null;
        $longitude = isset($_POST['longitude']) ? trim($_POST['longitude']) : null;
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
        $equipements = isset($_POST['equipements']) ? trim($_POST['equipements']) : '';
        $boutiques_services = isset($_POST['boutiques_services']) ? trim($_POST['boutiques_services']) : '';

        // Validation
        $errors = [];
        if (empty($nom)) $errors[] = "Le nom est requis";
        if (empty($ville_id)) $errors[] = "La ville est requise";
        if (empty($categorie)) $errors[] = "La catégorie est requise";
        if (empty($adresse)) $errors[] = "L'adresse est requise";
        if (empty($budget)) $errors[] = "Le budget est requis";

        if (empty($errors)) {
            // Gestion des images
            $hero_images = [];
            if (isset($_FILES['hero_images_upload']) && is_array($_FILES['hero_images_upload']['name'])) {
                $uploadDir = '../uploads/lieux/hero/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                foreach ($_FILES['hero_images_upload']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['hero_images_upload']['error'][$key] === UPLOAD_ERR_OK) {
                        $filename = uniqid('hero_') . '_' . basename($_FILES['hero_images_upload']['name'][$key]);
                        $uploadFile = $uploadDir . $filename;
                        
                        if (move_uploaded_file($tmp_name, $uploadFile)) {
                            $hero_images[] = 'uploads/lieux/hero/' . $filename;
                        }
                    }
                }
            }
            
            $hero_images_string = !empty($hero_images) ? implode(',', $hero_images) : '';

            // Insertion dans la base de données
            $stmt = $pdo->prepare("
                INSERT INTO lieux (
                    nom, id_ville, categorie, url_activite, adresse, 
                    budget, latitude, longitude, description, 
                    equipements, boutiques_services, hero_images
                ) VALUES (
                    ?, ?, ?, ?, ?, 
                    ?, ?, ?, ?, 
                    ?, ?, ?
                )
            ");
            
            $stmt->execute([
                $nom, $ville_id, $categorie, $url, $adresse,
                $budget, $latitude, $longitude, $description,
                $equipements, $boutiques_services, $hero_images_string
            ]);

            $_SESSION['success'] = "Le lieu a été ajouté avec succès !";
            header("Location: admin-places.php");
            exit;
        } else {
            $_SESSION['error'] = implode("<br>", $errors);
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur lors de l'ajout du lieu : " . $e->getMessage();
    }
}

// Modification d'un lieu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $edit_id = intval($_POST['edit_id']);
    $nom = trim($_POST['nom']);
    $desc = trim($_POST['description']);
    $id_ville = intval($_POST['id_ville']);
    $categorie = trim($_POST['categorie']);
    $url_activite = trim($_POST['url_activite']);
    $adresse = trim($_POST['adresse']);
    $equipements = trim($_POST['equipements']);
    $boutiques_services = trim($_POST['boutiques_services']);
    $budget = trim($_POST['budget']);
    
    // Gérer les champs numériques/décimaux
    $latitude = !empty($_POST['latitude']) ? (float)$_POST['latitude'] : null;
    $longitude = !empty($_POST['longitude']) ? (float)$_POST['longitude'] : null;

    try {
        // Validation des champs obligatoires
        $errors = [];
        if (empty($nom)) $errors[] = "Le nom est requis";
        if (empty($id_ville)) $errors[] = "La ville est requise";
        if (empty($categorie)) $errors[] = "La catégorie est requise";
        if (empty($adresse)) $errors[] = "L'adresse est requise";
        
        if (!empty($errors)) {
            throw new Exception("Erreurs de validation : " . implode(", ", $errors));
        }

        $pdo->beginTransaction();

        // Mise à jour des informations de base
        $stmt = $pdo->prepare("
            UPDATE lieux 
            SET nom = ?, 
                description = ?, 
                id_ville = ?, 
                categorie = ?, 
                url_activite = ?, 
                adresse = ?, 
                equipements = ?, 
                boutiques_services = ?, 
                latitude = ?, 
                longitude = ?, 
                budget = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $nom,
            $desc,
            $id_ville,
            $categorie,
            $url_activite,
            $adresse,
            $equipements,
            $boutiques_services,
            $latitude,
            $longitude,
            $budget,
            $edit_id
        ]);

        // Gestion des nouvelles images si des fichiers ont été uploadés
        if (isset($_FILES['hero_images_upload']) && is_array($_FILES['hero_images_upload']['name'])) {
            $uploadDir = '../uploads/lieux/hero/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            // Récupérer les images existantes
            $stmt = $pdo->prepare("SELECT hero_images FROM lieux WHERE id = ?");
            $stmt->execute([$edit_id]);
            $existing_images = $stmt->fetchColumn();
            $hero_images = !empty($existing_images) ? explode(',', $existing_images) : [];

            foreach ($_FILES['hero_images_upload']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['hero_images_upload']['error'][$key] === UPLOAD_ERR_OK) {
                    $filename = uniqid('hero_') . '_' . basename($_FILES['hero_images_upload']['name'][$key]);
                    $uploadFile = $uploadDir . $filename;
                    
                    if (move_uploaded_file($tmp_name, $uploadFile)) {
                        $hero_images[] = 'uploads/lieux/hero/' . $filename;
                    }
                }
            }

            // Mettre à jour la base de données avec toutes les images
            if (!empty($hero_images)) {
                $hero_images_string = implode(',', array_unique($hero_images));
                $stmt = $pdo->prepare("UPDATE lieux SET hero_images = ? WHERE id = ?");
                $stmt->execute([$hero_images_string, $edit_id]);
            }
        }

        $pdo->commit();

        $_SESSION['success'] = "Les modifications ont été enregistrées avec succès !";
        $_SESSION['success_message'] = "Le lieu a été modifié avec succès !";
        header("Location: admin-places.php");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Erreur lors de la mise à jour : " . $e->getMessage();
        header("Location: admin-places.php?edit=" . $edit_id);
        exit;
    }
}

// Suppression d'un lieu
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $pdo->prepare("DELETE FROM lieux WHERE id = ?")->execute([$id]);
    header('Location: admin-places.php');
    exit();
}

// Suppression d'une image hero individuelle
if ((isset($_GET['delete_hero_image']) && $_GET['delete_hero_image'] == 1) && isset($_GET['place_id']) && isset($_GET['image_path'])) {
    $place_id = intval($_GET['place_id']);
    $image_path = urldecode($_GET['image_path']);
    
    try {
        // Récupérer les images hero actuelles
        $stmt = $pdo->prepare("SELECT hero_images FROM lieux WHERE id = ?");
        $stmt->execute([$place_id]);
        $hero_images_string = $stmt->fetchColumn();
        
        if ($hero_images_string) {
            // Convertir la chaîne en tableau et nettoyer les espaces
            $hero_images = array_map('trim', explode(',', $hero_images_string));
            
            // Retirer l'image spécifiée du tableau
            $hero_images = array_filter($hero_images, function($img) use ($image_path) {
                return trim($img) !== trim($image_path);
            });
            
            // Réindexer le tableau pour éviter les clés non séquentielles
            $hero_images = array_values($hero_images);
            
            // Mettre à jour la base de données avec la nouvelle liste d'images
            $new_hero_images = implode(',', $hero_images);
            
            $update_stmt = $pdo->prepare("UPDATE lieux SET hero_images = ? WHERE id = ?");
            $result = $update_stmt->execute([$new_hero_images, $place_id]);
            
            // Supprimer physiquement le fichier (optionnel)
            $file_path = '../' . $image_path;
            if (file_exists($file_path)) {
                @unlink($file_path);
            }
            
            $_SESSION['admin_message'] = "L'image a été supprimée avec succès.";
            $_SESSION['admin_message_type'] = 'success';
        }
    } catch (Exception $e) {
        // En cas d'erreur, enregistrer l'erreur et afficher un message
        $_SESSION['admin_message'] = "Erreur lors de la suppression de l'image: " . $e->getMessage();
        $_SESSION['admin_message_type'] = 'error';
    }
    
    // Rediriger vers la page d'édition
    header('Location: admin-places.php?edit=' . $place_id);
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
$categorie_filter = isset($_GET['categorie']) ? trim($_GET['categorie']) : '';

if ($ville_filter > 0 && $categorie_filter !== '') {
    // Filtrer par ville ET catégorie
    try {
        $stmt = $pdo->prepare("SELECT lieux.*, villes.nom AS ville_nom FROM lieux LEFT JOIN villes ON lieux.id_ville = villes.id WHERE lieux.id_ville = ? AND LOWER(TRIM(lieux.categorie)) = LOWER(TRIM(?))");
        $stmt->execute([$ville_filter, $categorie_filter]);
        $places = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = $pdo->prepare("SELECT nom FROM villes WHERE id = ?");
        $stmt->execute([$ville_filter]);
        $filtered_city_name = $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Erreur lors du filtrage par ville et catégorie : " . $e->getMessage());
        $places = [];
    }
} elseif ($ville_filter > 0) {
    // Filtrer par ville uniquement
    try {
        $stmt = $pdo->prepare("SELECT lieux.*, villes.nom AS ville_nom FROM lieux LEFT JOIN villes ON lieux.id_ville = villes.id WHERE lieux.id_ville = ?");
        $stmt->execute([$ville_filter]);
        $places = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = $pdo->prepare("SELECT nom FROM villes WHERE id = ?");
        $stmt->execute([$ville_filter]);
        $filtered_city_name = $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Erreur lors du filtrage par ville : " . $e->getMessage());
        $places = [];
    }
} elseif ($categorie_filter !== '') {
    // Filtrer par catégorie uniquement
    try {
        $stmt = $pdo->prepare("SELECT lieux.*, villes.nom AS ville_nom FROM lieux LEFT JOIN villes ON lieux.id_ville = villes.id WHERE LOWER(TRIM(lieux.categorie)) = LOWER(TRIM(?))");
        $stmt->execute([$categorie_filter]);
        $places = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur lors du filtrage par catégorie : " . $e->getMessage());
        $places = [];
    }
} else {
    // Afficher tous les lieux si aucun filtre n'est spécifié
    try {
        $stmt = $pdo->query("SELECT lieux.*, villes.nom AS ville_nom FROM lieux LEFT JOIN villes ON lieux.id_ville = villes.id");
        $places = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Debug: Afficher le nombre total de lieux
        error_log("Nombre total de lieux chargés : " . count($places));
        
        // Debug: Afficher le nombre de lieux par catégorie
        $categories = [];
        foreach ($places as $place) {
            $categorie = $place['categorie'] ?? 'Non défini';
            if (!isset($categories[$categorie])) {
                $categories[$categorie] = 0;
            }
            $categories[$categorie]++;
        }
        foreach ($categories as $categorie => $count) {
            error_log("Catégorie '$categorie': $count lieux");
        }
    } catch (PDOException $e) {
        error_log("Erreur lors du chargement des lieux : " . $e->getMessage());
        $places = [];
    }
}

// --- Display admin messages ---
if (isset($_SESSION['admin_message'])) {
    $message = $_SESSION['admin_message'];
    $message_type = $_SESSION['admin_message_type'] ?? 'info'; // default to info
    unset($_SESSION['admin_message']);
    unset($_SESSION['admin_message_type']); // Clear the message after displaying
}
// --- End Display admin messages ---
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Admin - Gestion des Lieux</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
    <script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
    <style>
        .leaflet-control-geocoder-form input {
            border: 1px solid #ccc;
            border-radius: 5px;
            padding: 8px;
            font-family: 'Montserrat', sans-serif;
            font-size: 14px;
            width: 250px;
        }
        .leaflet-control-geocoder-icon {
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%232D796D"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>');
            background-size: 20px 20px;
        }
        :root {
            --primary-color: #2D796D;
            --light-green: #E6F0EE;
            --text-dark: #333;
            --border-radius: 8px;
            --shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Montserrat', sans-serif;
        }

        body {
            background-color: #f5f5f5;
            color: var(--text-dark);
            padding-top: 80px;
        }

        .header {
            background: white;
            height: 80px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }

        .header-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem;
            height: 80px;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 3rem;
        }

        .logo {
            height: 45px;
            width: auto;
        }

        .menu-wrapper {
            position: relative;
        }

        .menu-toggle {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: var(--primary-color);
            font-weight: 500;
            text-decoration: none;
            font-size: 1.1rem;
            padding: 0.5rem;
            background: none;
            border: none;
            cursor: pointer;
        }

        .menu-toggle i {
            font-size: 1.5rem;
        }

        .dropdown-menu {
            display: none;
            position: fixed;
            top: 80px;
            left: 0;
            height: calc(100vh - 80px);
            width: 300px;
            background: white;
            z-index: 1000;
            padding: 2rem;
            transform: translateX(-100%);
            transition: transform 0.3s ease;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }

        .dropdown-menu.active {
            display: block;
            transform: translateX(0);
        }

        .menu-overlay {
            display: none;
            position: fixed;
            top: 80px;
            left: 0;
            width: 100%;
            height: calc(100vh - 80px);
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .menu-overlay.active {
            display: block;
            opacity: 1;
        }

        .dropdown-header {
            padding-bottom: 1.5rem;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid #E5E7EB;
        }

        .dropdown-header h2 {
            color: var(--primary-color);
            font-size: 1.75rem;
            margin: 0;
            font-weight: 600;
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.75rem 0;
            color: #64748B;
            text-decoration: none;
            transition: all 0.3s ease;
            margin-bottom: 0.5rem;
        }

        .dropdown-item:hover {
            color: var(--primary-color);
        }

        .dropdown-item.active {
            color: var(--primary-color);
        }

        .dropdown-item i {
            font-size: 1.25rem;
            width: 24px;
            text-align: center;
        }

        .dropdown-item span {
            font-size: 1.1rem;
            font-weight: 500;
        }

        .dropdown-item.active i {
            color: inherit;
        }

        .nav-center {
            display: flex;
            gap: 2.5rem;
        }

        .nav-link {
            color: var(--text-dark);
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem;
            transition: color 0.3s ease;
        }

        .nav-link:hover {
            color: var(--primary-color);
        }

        /* Navigation Styles */
        .nav-item {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 1.1rem;
            font-weight: 500;
            transition: all 0.2s ease;
            border-radius: 8px;
            padding: 1rem;
        }

        .nav-item:hover {
            background: var(--light-green);
            transform: translateX(5px);
        }

        .nav-item.active {
            background: var(--primary-color);
            color: white;
        }

        /* Menu Button Styles */
        .menu-button {
            color: var(--primary-color);
            font-weight: 600;
        }

        .menu-button:hover {
            color: #236b5f;
        }

        /* Action Buttons */
        .btn-action {
            background: var(--primary-color);
            color: white;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .btn-action:hover {
            background: #236b5f;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(45, 121, 109, 0.2);
        }

        /* Form Controls */
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(45, 121, 109, 0.1);
        }

        /* Upload Container */
        .upload-container:hover {
            border-color: var(--primary-color);
            background: var(--light-green);
        }

        .upload-icon {
            color: var(--primary-color);
        }

        /* Search Section */
        .search-section {
            position: relative;
            width: 100%;
            max-width: 600px;
            margin: 2rem auto;
            z-index: 100;
        }

        .search-input {
            width: 100%;
            height: 48px;
            padding: 0 1rem 0 3rem;
            border: 2px solid #E2E8F0;
            border-radius: 12px;
            font-size: 1rem;
            background: white;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(45, 121, 109, 0.1);
        }

        .search-results {
            position: absolute;
            top: calc(100% + 8px);
            left: 0;
            right: 0;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }

        .search-result-item:hover {
            background: var(--light-green);
        }

        /* Hero Section Styles */
        .page-header {
            background: var(--primary-color);
            color: white;
            padding: 4rem 2rem;
            margin: 0;
            text-align: center;
            border-radius: 0;
        }

        .page-header h1 {
            font-size: 2.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
            display: block;
            text-align: center;
        }

        .page-header p {
            font-size: 1.1rem;
            opacity: 0.9;
            max-width: 800px;
            margin: 0 auto 2rem;
        }

        .search-container {
            max-width: 600px;
            margin: 0 auto;
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: none;
            border-radius: var(--border-radius);
            font-size: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }

        /* Stats Section */
        .stats-container {
            padding: 2rem;
            margin-top: 3rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            display: flex;
            align-items: center;
            gap: 1.5rem;
            box-shadow: var(--shadow);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            color: var(--primary-color);
            font-size: 2rem;
            width: 40px;
        }

        .stat-content h3 {
            font-size: 2.5rem;
            font-weight: 600;
            color: var(--text-dark);
            margin: 0;
        }

        .stat-content p {
            color: #666;
            margin: 0.5rem 0 0 0;
            font-size: 1rem;
            font-weight: 500;
        }

        /* Add Place Form */
        .add-place-section {
            background: white;
            border-radius: var(--border-radius);
            margin: 0 2rem;
            box-shadow: var(--shadow);
        }

        .section-header {
            background: var(--primary-color);
            color: white;
            padding: 1rem 2rem;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .section-header h2 {
            font-size: 1.2rem;
            font-weight: 500;
            margin: 0;
        }

        .form-container {
            padding: 2rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
            font-weight: 500;
            font-size: 0.9rem;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e0e0e0;
            border-radius: var(--border-radius);
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(45, 121, 109, 0.1);
        }

        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%23666' viewBox='0 0 16 16'%3E%3Cpath d='M8 11.5l-5-5h10l-5 5z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            padding-right: 2.5rem;
        }

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }

        .upload-container {
            border: 2px dashed #e0e0e0;
            border-radius: var(--border-radius);
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .upload-container:hover {
            border-color: var(--primary-color);
            background: rgba(45, 121, 109, 0.05);
        }

        .upload-icon {
            color: var(--primary-color);
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        .upload-text {
            color: #666;
            margin: 0;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .form-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .page-header,
            .stats-container,
            .add-place-section {
                margin: 1rem;
            }
        }

        /* Header Styles */
        .header {
            background: white;
            height: 80px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }

        .header-container {
            max-width: 1400px;
            margin: 0 auto;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem;
        }

        .logo-menu-container {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .logo {
            height: 45px;
            width: auto;
            object-fit: contain;
        }

        .menu-button {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border: none;
            background: none;
            color: var(--primary-color);
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            padding: 0.5rem;
        }

        .menu-button i {
            font-size: 1.2rem;
        }

        .nav-center {
            display: flex;
            gap: 2.5rem;
        }

        .nav-link {
            color: var(--text-dark);
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem;
            transition: color 0.3s ease;
        }

        .nav-link:hover,
        .nav-link.active {
            color: var(--primary-color);
        }

        .admin-actions {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .admin-button {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: opacity 0.3s ease;
        }

        .admin-button:hover {
            opacity: 0.8;
        }

        /* Table Styles */
        .table-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.1);
            margin: 2rem;
            overflow: hidden;
        }

        .table-header {
            background: var(--primary-color);
            color: white;
            padding: 1.5rem 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .table-header h2 {
            font-size: 1.2rem;
            font-weight: 600;
            margin: 0;
        }

        .table-responsive {
            overflow-x: auto;
            padding: 1rem;
        }

        /* Table Base Styles */
        .table {
            width: 100%;
            border-collapse: separate; /* Important pour les border-radius */
            border-spacing: 0;
        }

        .table th {
            background: #f8f9fa;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #2c3e50;
            border-bottom: 2px solid #e9ecef;
            white-space: nowrap;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .table th:first-child {
            border-top-left-radius: 8px;
        }

        .table th:last-child {
            border-top-right-radius: 8px;
        }

        .table th,
        .table td {
            padding: 1rem;
            border-bottom: 1px solid #e9ecef;
            vertical-align: middle;
        }

        .table th, .table td {
            border-left: 1px solid #e9ecef;
        }

        .table th:first-child, .table td:first-child {
            border-left: none;
        }

        .table tbody tr:hover {
            background-color: #f8f9fa;
        }

        /* Column Specific Styles */
        .id-col {
            width: 60px;
            text-align: center;
        }

        .name-col {
            min-width: 180px;
            max-width: 250px;
        }

        .category-col {
            width: 120px;
        }

        .desc-col {
            min-width: 200px;
            max-width: 300px;
        }

        .address-col {
            min-width: 150px;
            max-width: 250px;
        }

        .coord-col {
            width: 150px;
            text-align: center;
            font-family: monospace;
        }

        .images-col {
            min-width: 200px;
            padding: 10px;
        }

        .equip-col {
            min-width: 200px;
            max-width: 300px;
        }

        .budget-col {
            width: 100px;
            text-align: center;
        }

        .url-col {
            width: 60px;
            text-align: center;
        }

        /* Pour gérer le texte long */
        .table td {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 250px;
            padding: 12px;
            vertical-align: middle;
        }

        /* Pour les colonnes avec texte multiligne */
        .desc-col, .equip-col {
            white-space: normal;
            line-height: 1.4;
        }

        /* Style pour la galerie d'images */
        .images-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(60px, 1fr));
            gap: 8px;
            max-width: 200px;
            align-items: center;
            justify-content: start;
        }

        .image-item {
            position: relative;
            width: 60px;
            height: 60px;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .image-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
            z-index: 1;
        }

        .image-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .image-item:hover img {
            transform: scale(1.1);
        }

        .image-item::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.2);
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        .image-item:hover::after {
            opacity: 1;
        }

        .no-images {
            color: #94a3b8;
            font-style: italic;
            font-size: 0.85rem;
            padding: 10px;
            background: #f1f5f9;
            border-radius: 6px;
            text-align: center;
            display: block;
        }

        /* Place Info Styles */
        .place-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .place-image-container {
            position: relative;
        }

        .place-thumbnail {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            object-fit: cover;
        }

        .image-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--primary-color);
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .place-name {
            font-weight: 500;
        }

        /* Category Badge Styles */
        .category-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            text-transform: capitalize;
            background: #e9ecef;
        }

        .category-badge.hotels { background: #e3f2fd; color: #1976d2; }
        .category-badge.restaurants { background: #fce4ec; color: #c2185b; }
        .category-badge.monuments { background: #f3e5f5; color: #7b1fa2; }
        .category-badge.plages { background: #e0f7fa; color: #0097a7; }
        .category-badge.parcs { background: #e8f5e9; color: #388e3c; }

        /* Description Tooltip */
        .description-tooltip {
            position: relative;
            cursor: pointer;
        }

        .description-tooltip:hover::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 100%;
            left: 0;
            background: #2c3e50;
            color: white;
            padding: 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            max-width: 300px;
            z-index: 1000;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }

        /* Equipment and Services Lists */
        .equipment-list, .services-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .equipment-item, .service-item {
            background: #f0f2f5;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            white-space: nowrap;
        }

        /* Rating Stars */
        .rating-stars {
            color: #ffd700;
        }

        /* URL Link */
        .url-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary-color);
            text-decoration: none;
            padding: 4px 8px;
            border-radius: 4px;
            transition: background-color 0.2s;
        }

        .url-link:hover {
            background-color: rgba(45, 121, 109, 0.1);
        }

        /* Budget Amount */
        .budget-amount {
            font-weight: 500;
            color: #2c3e50;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .btn-action {
            width: 35px;
            height: 35px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            padding: 0;
        }

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .btn-edit {
            background-color: var(--primary-color);
        }

        .btn-edit:hover {
            background-color: #236b5f;
        }

        .btn-delete {
            background-color: #dc3545;
        }

        .btn-delete:hover {
            background-color: #c82333;
        }

        .btn-action i {
            font-size: 1rem;
        }

        /* Ensure the header stays fixed */
        .table .actions-col {
            width: 1%;
            white-space: nowrap;
            text-align: center;
            border-right: none !important; /* Pour s'assurer qu'aucune bordure ne s'applique */
        }

        /* Add shadow effect on scroll */
        .table-responsive {
            position: relative;
        }

        /* Suppression de l'ombre verticale */
        /*.table-responsive::after {
            content: '';
            position: absolute;
            top: 0;
            left: 160px;
            bottom: 0;
            width: 6px;
            pointer-events: none;
            background: linear-gradient(to right, rgba(0,0,0,0.1), transparent);
        }*/

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .actions-col {
                width: 120px !important;
                min-width: 120px !important;
            }

            .btn-action {
                padding: 0.4rem;
            }

            .action-text {
                display: none;
            }
        }

        /* Styles pour le message "Aucune donnée" */
        .no-data {
            text-align: center;
            padding: 2rem;
            color: #666;
        }

        .no-data i {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: #999;
        }

        /* Styles de pagination */
        .pagination {
            padding: 1.5rem;
            background: white;
            border-top: 1px solid #eee;
        }

        .pagination-container {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
        }

        .pagination-numbers {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .pagination-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 32px;
            height: 32px;
            padding: 0 0.5rem;
            border-radius: 6px;
            border: 1px solid #e0e0e0;
            color: #333;
            text-decoration: none;
            transition: all 0.2s;
        }

        .pagination-link:hover {
            background: #f5f5f5;
            border-color: #ccc;
        }

        .pagination-link.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .pagination-ellipsis {
            color: #666;
            padding: 0 0.5rem;
        }

        .pagination-info {
            text-align: center;
            margin-top: 1rem;
            color: #666;
            font-size: 0.9rem;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .pagination-numbers {
                display: none;
            }
            
            .pagination-info {
                font-size: 0.8rem;
            }
        }

        /* Filters Section */
        .filters-section {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            margin: 2rem;
            padding: 1.5rem;
        }

        .filters-container {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex: 1;
            min-width: 250px;
        }

        .filter-icon {
            width: 48px;
            height: 48px;
            background: var(--primary-color);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            box-shadow: 0 4px 12px rgba(45, 121, 109, 0.2);
        }

        .filter-select {
            width: 100%;
            height: 48px;
            padding: 0 1rem;
            padding-right: 3rem;
            border: 2px solid #E2E8F0;
            border-radius: 12px;
            font-size: 1rem;
            color: var(--text-dark);
            background: white;
            cursor: pointer;
            appearance: none;
            transition: all 0.3s ease;
        }

        .filter-select:hover,
        .filter-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(45, 121, 109, 0.1);
        }

        /* Search Bar */
        .search-section {
            position: relative;
            width: 100%;
            max-width: 600px;
            margin: 2rem auto;
            z-index: 100;
        }

        .search-container {
            position: relative;
            width: 100%;
        }

        .search-input {
            width: 100%;
            height: 48px;
            padding: 0 1rem 0 3rem;
            border: 2px solid #E2E8F0;
            border-radius: 12px;
            font-size: 1rem;
            background: white;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(45, 121, 109, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-gray);
            font-size: 1.1rem;
        }

        .search-spinner {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-color);
            display: none;
        }

        .search-results {
            position: absolute;
            top: calc(100% + 8px);
            left: 0;
            right: 0;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.1);
            max-height: 400px;
            overflow-y: auto;
            display: none;
            z-index: 1000;
        }

        .search-result-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            gap: 1rem;
            border-bottom: 1px solid #E2E8F0;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .search-result-item:hover {
            background: var(--light-green);
        }

        .search-result-image {
            width: 48px;
            height: 48px;
            border-radius: 8px;
            object-fit: cover;
        }

        .search-result-info {
            flex: 1;
        }

        .search-result-name {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.25rem;
        }

        .search-result-details {
            font-size: 0.9rem;
            color: var(--text-gray);
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .filters-container {
                gap: 1rem;
            }

            .filter-group {
                min-width: calc(50% - 1rem);
            }
        }

        @media (max-width: 768px) {
            .filters-section {
                margin: 1rem;
                padding: 1rem;
            }
            
            .search-input {
                height: 40px;
                font-size: 0.9rem;
            }
        }

        /* Sidebar Styles */
        .admin-sidebar {
            position: fixed;
            left: -300px;
            top: 0;
            bottom: 0;
            width: 300px;
            background: white;
            display: flex;
            flex-direction: column;
            z-index: 1000;
            padding-top: 80px;
            box-shadow: 4px 0 24px rgba(0,0,0,0.1);
            transition: left 0.3s ease;
        }

        .admin-sidebar.active {
            left: 0;
        }

        .sidebar-header {
            padding: 2rem;
        }

        .sidebar-header h2 {
            color: #2D3748;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 2rem;
        }

        .sidebar-nav {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            padding: 0 1.5rem;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.5rem;
            color: #2D3748;
            text-decoration: none;
            font-size: 1.1rem;
            font-weight: 500;
            transition: all 0.2s ease;
            border-radius: 12px;
        }

        .nav-item:hover {
            background: #E6F0EE;
            color: #2D796D;
            transform: translateX(5px);
        }

        .nav-item.active {
            background: #2D796D;
            color: white;
        }

        .nav-icon {
            color: #2D796D;
            font-size: 1.25rem;
            transition: all 0.2s ease;
        }

        .nav-item:hover .nav-icon {
            transform: scale(1.1);
        }

        .nav-item.active .nav-icon {
            color: white;
        }

        /* Overlay for mobile */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .sidebar-overlay.active {
            display: block;
            opacity: 1;
        }

        /* Button Styles */
        .btn-submit {
            background: #2D796D;
            color: white;
            border: none;
            padding: 1rem 2rem;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(45, 121, 109, 0.2);
        }

        .btn-submit:hover {
            background: #236b5f;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(45, 121, 109, 0.25);
        }

        .btn-submit:active {
            transform: translateY(1px);
            box-shadow: 0 2px 8px rgba(45, 121, 109, 0.2);
        }

        .btn-submit i {
            font-size: 1.2rem;
        }

        /* Loading state */
        .btn-submit.loading {
            position: relative;
            color: transparent;
            pointer-events: none;
        }

        .btn-submit.loading::after {
            content: '';
            position: absolute;
            left: 50%;
            top: 50%;
            width: 20px;
            height: 20px;
            border: 2px solid white;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 0.8s linear infinite;
            transform: translate(-50%, -50%);
        }

        @keyframes spin {
            to {
                transform: translate(-50%, -50%) rotate(360deg);
            }
        }

        /* Success animation */
        .btn-submit.success {
            background: #059669;
        }

        .btn-submit.success i {
            animation: scale 0.3s ease;
        }

        @keyframes scale {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.2); }
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 2rem;
            padding: 1.5rem;
            background: #F8FAFC;
            border-radius: 12px;
        }

        /* Cancel Button */
        .btn-cancel {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: #2D796D;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(45, 121, 109, 0.2);
        }
        
        .btn-cancel:hover {
            background: #1F5C54;
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(45, 121, 109, 0.3);
        }
        
        .btn-cancel i {
            font-size: 0.9em;
        }

        /* Filter Action Buttons */
        .filter-actions {
            display: flex;
            gap: 1rem;
            margin-left: auto;
        }

        .btn-filter {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.875rem 1.5rem;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-apply {
            background: #2D796D;
            color: white;
            border: none;
            box-shadow: 0 4px 12px rgba(45, 121, 109, 0.2);
        }

        .btn-apply:hover {
            background: #236b5f;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(45, 121, 109, 0.25);
        }

        .btn-reset {
            background: white;
            color: #64748B;
            border: 2px solid #E2E8F0;
        }

        .btn-reset:hover {
            background: #F8FAFC;
            color: #2D796D;
            border-color: #2D796D;
            transform: translateY(-2px);
        }

        .btn-filter i {
            font-size: 1.1rem;
        }

        .btn-apply i {
            color: white;
        }

        .btn-reset i {
            color: inherit;
        }

        @media (max-width: 768px) {
            .filter-actions {
                width: 100%;
                margin-top: 1rem;
            }

            .btn-filter {
                flex: 1;
                justify-content: center;
            }
        }

        /* Filter Select Styles */
        .filter-group {
            position: relative;
            display: flex;
            align-items: center;
            gap: 1rem;
            min-width: 300px;
        }

        .filter-icon-wrapper {
            width: 48px;
            height: 48px;
            background: #2D796D;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(45, 121, 109, 0.15);
        }

        .filter-icon-wrapper i {
            color: white;
            font-size: 1.2rem;
        }

        .select-wrapper {
            position: relative;
            flex: 1;
        }

        .filter-select {
                width: 100%;
            height: 48px;
            padding: 0 1rem;
            padding-right: 3rem;
            background: white;
            border: 2px solid #E2E8F0;
            border-radius: 12px;
            font-size: 1rem;
            color: #2D3748;
            cursor: pointer;
            appearance: none;
            transition: all 0.3s ease;
        }

        .filter-select:hover {
            border-color: #2D796D;
            box-shadow: 0 2px 8px rgba(45, 121, 109, 0.1);
        }

        .filter-select:focus {
            outline: none;
            border-color: #2D796D;
            box-shadow: 0 0 0 4px rgba(45, 121, 109, 0.1);
        }

        .select-wrapper::after {
            content: '\f107';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #64748B;
            pointer-events: none;
            transition: all 0.3s ease;
        }

        .select-wrapper:hover::after {
            color: #2D796D;
        }

        .filter-select option {
            padding: 0.5rem;
            font-size: 1rem;
            color: #2D3748;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .filter-group {
                min-width: 100%;
            }

            .filter-icon-wrapper {
                width: 40px;
                height: 40px;
            }

            .filter-select {
                height: 40px;
                font-size: 0.95rem;
            }
        }

        .image-preview img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
        }
        .preview-item {
            position: relative;
        }
        .remove-image {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #ff4444;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 12px;
        }

        #map {
            height: 400px;
            width: 100%;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .coordinates-group {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        .coordinates-group .form-group {
                flex: 1;
            }
        .map-container {
            margin-bottom: 20px;
        }
        .map-help-text {
            margin-top: 10px;
            color: #666;
            font-size: 0.9rem;
        }

        /* Modal d'image amélioré */
        .image-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.85);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(5px);
        }

        .modal-content {
            position: relative;
            max-width: 90vw;
            max-height: 90vh;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        .modal-content img {
            display: block;
            max-width: 100%;
            max-height: 90vh;
            object-fit: contain;
        }

        .close-modal {
            position: absolute;
            top: 20px;
            right: 20px;
            color: white;
            font-size: 32px;
            cursor: pointer;
            width: 40px;
            height: 40px;
            background: rgba(0, 0, 0, 0.5);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.2s ease;
        }

        .close-modal:hover {
            background: rgba(0, 0, 0, 0.7);
        }

        .filter-actions {
            display: flex;
            gap: 10px;
            margin-left: auto;
        }

        .btn-filter {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-apply {
            background: #2D796D;
            color: white;
            border: none;
        }

        .btn-apply:hover {
            background: #236b5f;
            transform: translateY(-2px);
        }

        .btn-reset {
            background: white;
            color: #64748B;
            border: 2px solid #E2E8F0;
        }

        .btn-reset:hover {
            border-color: #2D796D;
            color: #2D796D;
            transform: translateY(-2px);
        }

        .btn-filter i {
            font-size: 16px;
        }

        .search-box {
            position: relative;
            flex: 1;
        }

        .search-input {
            width: 100%;
            padding: 12px 40px 12px 40px;
            border: 2px solid #E2E8F0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .search-box .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748B;
        }

        .search-box .clear-search {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748B;
            cursor: pointer;
            padding: 5px;
            display: none;
        }

        .search-box .clear-search:hover {
            color: #4A5568;
        }

        .search-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(45, 121, 109, 0.1);
        }

        /* Styles pour les images dans le formulaire de modification */
        .image-preview-container {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 10px;
        }

        .preview-image-item {
            position: relative;
            width: 150px;
            height: 150px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .preview-image-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .existing-images {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
        }

        .existing-image-item {
            position: relative;
            width: 150px;
            height: 150px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .existing-image-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .delete-image {
            position: absolute;
            top: 5px;
            right: 5px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 50%;
            width: 25px;
            height: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: #dc3545;
            transition: all 0.2s ease;
        }

        .delete-image:hover {
            background: #dc3545;
            color: white;
        }

        #imagePreview {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 10px;
        }

        .upload-container {
            border: 2px dashed #E2E8F0;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin-top: 10px;
            background: #F8FAFC;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .upload-container:hover {
            background: #E6F0EE;
            border-color: var(--primary-color);
        }

        .upload-container i {
            font-size: 2rem;
            color: #64748B;
            margin-bottom: 10px;
        }

        .browse-text {
            color: var(--primary-color);
            text-decoration: underline;
            cursor: pointer;
        }

        #fileInput {
            display: none;
        }

        /* Style pour la notification */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 8px;
            background: #2D796D;
            color: white;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            opacity: 0;
            transform: translateY(-20px);
            transition: all 0.3s ease;
        }

        .notification.show {
            opacity: 1;
            transform: translateY(0);
        }

        .notification i {
            font-size: 18px;
        }

        .notification.success {
            background: #2D796D;
        }

        .notification.error {
            background: #dc3545;
        }

        /* Style pour le message de succès */
        .success-message {
            background-color: #f0faf8;
            border: 1px solid #2D796D;
            border-radius: 8px;
            padding: 15px 20px;
            margin: 20px 0;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: fadeInOut 3s forwards;
        }

        .success-message i {
            color: #2D796D;
            font-size: 20px;
        }

        .success-message span {
            color: #2D796D;
            font-weight: 500;
        }

        @keyframes fadeInOut {
            0% { opacity: 0; transform: translateY(-10px); }
            10% { opacity: 1; transform: translateY(0); }
            90% { opacity: 1; transform: translateY(0); }
            100% { opacity: 0; transform: translateY(-10px); }
        }
    </style>

    <script>
        // Fonction pour faire disparaître les messages après 3 secondes
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.classList.add('fade-out');
                    setTimeout(() => {
                        alert.remove();
                    }, 500);
                }, 3000);
            });
        });

        function showNotification(message, type = 'success') {
            // Supprimer toute notification existante
            const existingNotification = document.querySelector('.notification');
            if (existingNotification) {
                existingNotification.remove();
            }

            // Créer la nouvelle notification
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.innerHTML = `
                <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
                <span>${message}</span>
            `;

            // Ajouter la notification au document
            document.body.appendChild(notification);

            // Afficher la notification avec une animation
            setTimeout(() => {
                notification.classList.add('show');
            }, 100);

            // Supprimer la notification après 3 secondes
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }, 3000);
        }

        <?php if (isset($_SESSION['success_message'])): ?>
            document.addEventListener('DOMContentLoaded', function() {
                showNotification("<?php echo addslashes($_SESSION['success_message']); ?>");
                <?php unset($_SESSION['success_message']); ?>
            });
        <?php endif; ?>
    </script>
</head>
<body>
    <div class="menu-overlay" id="menuOverlay"></div>
    <header class="header">
        <div class="header-container">
            <div class="header-left">
                <img src="https://i.postimg.cc/g07GgLp5/VMaroc-logo-trf.png" alt="VMaroc" class="logo">
                <div class="menu-wrapper">
                    <button class="menu-toggle" onclick="toggleMenu()">
                        <i class="fas fa-bars"></i>
                        <span>Menu</span>
                    </button>
                    <div class="dropdown-menu" id="adminMenu">
                        <div class="dropdown-header">
                            <h2>Administration</h2>
                        </div>
                        <a href="admin-cities.php" class="dropdown-item">
                            <i class="fas fa-building"></i>
                            <span>Gérer les villes</span>
                        </a>
                        <a href="admin-places.php" class="dropdown-item active">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>Gérer les lieux</span>
                        </a>
                        <a href="admin-users.php" class="dropdown-item">
                            <i class="fas fa-users"></i>
                            <span>Gérer les utilisateurs</span>
                        </a>
                        <a href="admin-reviews.php" class="dropdown-item">
                            <i class="fas fa-star"></i>
                            <span>Gérer les avis</span>
                        </a>
                    </div>
                </div>
            </div>
            <nav class="nav-center">
                <a href="../index.php" class="nav-link">Accueil</a>
                <a href="../destinations.php" class="nav-link">Destinations</a>
                <a href="../recommendations.php" class="nav-link">Recommandations</a>
            </nav>
            <div class="admin-actions">
                <a href="admin-panel.php" class="nav-link">Panel Admin</a>
                <a href="../logout.php" class="nav-link">Déconnexion</a>
            </div>
        </div>
    </header>

    <script>
        function toggleMenu() {
            const menu = document.getElementById('adminMenu');
            const overlay = document.getElementById('menuOverlay');
            menu.classList.toggle('active');
            overlay.classList.toggle('active');
            document.body.style.overflow = menu.classList.contains('active') ? 'hidden' : '';
        }

        // Fermer le menu si on clique sur l'overlay
        document.getElementById('menuOverlay').addEventListener('click', function() {
            toggleMenu();
        });

        // Empêcher la propagation des clics dans le menu
        document.getElementById('adminMenu').addEventListener('click', function(event) {
            event.stopPropagation();
        });
    </script>

    <main class="main-content">
        <div class="container">
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo htmlspecialchars($_SESSION['success_message']); ?></span>
                </div>
                <script>
                    // Supprimer le message après 3 secondes
                    setTimeout(() => {
                        const message = document.querySelector('.success-message');
                        if (message) {
                            message.style.display = 'none';
                        }
                    }, 3000);
                </script>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>
        </div>
    </main>

    <div class="page-header">
        <h1>Gestion des Lieux (<?php echo $stats['lieux']; ?>)</h1>
        <p>Gérez facilement les lieux et leurs informations depuis cette interface intuitive.</p>
        <div class="search-container">
            <i class="fas fa-search search-icon"></i>
            <input type="text" class="search-input" placeholder="Rechercher un lieu...">
    </div>
    </div>

    <div class="stats-container">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-map-marker-alt"></i>
        </div>
                <div class="stat-content">
                    <h3><?php echo $stats['lieux']; ?></h3>
                    <p>Lieux</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-star"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $stats['avis']; ?></h3>
                    <p>Avis</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-images"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $stats['images']; ?></h3>
                    <p>Images</p>
                </div>
            </div>
        </div>
        </div>
        
    <div class="add-place-section">
        <div class="section-header">
            <i class="fas fa-plus-circle"></i>
            <h2><?php echo (isset($editMode) && $editMode) ? 'Modifier le lieu' : 'Ajouter un nouveau lieu'; ?></h2>
        </div>
        
    <div class="form-container">
            <form class="place-form" method="POST" enctype="multipart/form-data">
            <?php if (isset($editMode) && $editMode): ?>
                <input type="hidden" name="edit_id" value="<?php echo $editPlace['id']; ?>">
            <?php endif; ?>
                <div class="form-grid">
                <div class="form-group">
                    <label for="nom">Nom du lieu</label>
                        <input type="text" id="nom" name="nom" class="form-control" value="<?php echo isset($editPlace) ? htmlspecialchars($editPlace['nom']) : ''; ?>" required>
                </div>
                <div class="form-group">
                        <label for="ville">Ville</label>
                        <select id="ville" name="id_ville" class="form-control" required>
                        <option value="">Sélectionner une ville</option>
                            <?php
                            try {
                                $stmt = $pdo->query("SELECT id, nom FROM villes ORDER BY nom");
                                while ($ville = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    $selected = (isset($editPlace) && $editPlace['id_ville'] == $ville['id']) ? ' selected' : '';
                                    echo '<option value="' . htmlspecialchars($ville['id']) . '"' . $selected . '>' 
                                        . htmlspecialchars($ville['nom']) . '</option>';
                                }
                            } catch (PDOException $e) {
                                echo '<option value="">Erreur de chargement des villes</option>';
                            }
                            ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="categorie">Catégorie</label>
                        <select id="categorie" name="categorie" class="form-control" required>
                            <?php if (isset($editPlace)): ?>
                            <option value="<?php echo htmlspecialchars($editPlace['categorie']); ?>" selected><?php echo ucfirst(str_replace('_', ' ', htmlspecialchars($editPlace['categorie']))); ?></option>
                            <?php endif; ?>
                        <option value="">Sélectionner une catégorie</option>
                        <?php
                        try {
                            $stmt = $pdo->query("SELECT DISTINCT categorie FROM lieux WHERE categorie IS NOT NULL AND categorie != '' ORDER BY categorie");
                            $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
                            if (empty($categories)) {
                        $categories = [
                                    'hotels', 'restaurants', 'monuments', 'plages', 
                                    'parcs', 'shopping', 'cinemas', 'musees', 
                                    'sport', 'culture', 'vie_nocturne'
                                ];
                            }
                            foreach ($categories as $categorie) {
                                $categorie_display = ucfirst(str_replace('_', ' ', $categorie));
                                echo '<option value="' . htmlspecialchars($categorie) . '">' 
                                    . htmlspecialchars($categorie_display) . '</option>';
                            }
                        } catch (PDOException $e) {
                            echo '<option value="">Erreur de chargement des catégories</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                        <label for="url">URL Activité/Site Web</label>
                        <input type="url" id="url" name="url_activite" class="form-control" value="<?php echo isset($editPlace) ? htmlspecialchars($editPlace['url_activite']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="adresse">Adresse</label>
                        <input type="text" id="adresse" name="adresse" class="form-control" value="<?php echo isset($editPlace) ? htmlspecialchars($editPlace['adresse']) : ''; ?>" required>
                </div>
                <div class="form-group">
                        <label for="budget">Budget</label>
                        <select id="budget" name="budget" class="form-control" required>
                            <option value="" <?php echo !isset($editPlace) ? 'selected' : ''; ?>>Sélectionner un budget</option>
                            <option value="economique" <?php echo (isset($editPlace) && $editPlace['budget'] == 'economique') ? 'selected' : ''; ?>>Économique</option>
                            <option value="moyen" <?php echo (isset($editPlace) && $editPlace['budget'] == 'moyen') ? 'selected' : ''; ?>>Moyen</option>
                            <option value="luxe" <?php echo (isset($editPlace) && $editPlace['budget'] == 'luxe') ? 'selected' : ''; ?>>Luxe</option>
                        </select>
                </div>
                <div class="form-group">
                    <label for="latitude">Latitude</label>
                        <input type="text" id="latitude" name="latitude" class="form-control" value="<?php echo isset($editPlace) ? htmlspecialchars($editPlace['latitude']) : ''; ?>" required readonly>
            </div>
                <div class="form-group">
                    <label for="longitude">Longitude</label>
                        <input type="text" id="longitude" name="longitude" class="form-control" value="<?php echo isset($editPlace) ? htmlspecialchars($editPlace['longitude']) : ''; ?>" required readonly>
                </div>
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" class="form-control" required><?php echo isset($editPlace) ? htmlspecialchars($editPlace['description']) : ''; ?></textarea>
                </div>
                <div class="form-group">
                    <label for="equipements">Équipements</label>
                    <textarea id="equipements" name="equipements" class="form-control"><?php echo isset($editPlace) ? htmlspecialchars($editPlace['equipements']) : ''; ?></textarea>
                </div>
                <div class="form-group">
                    <label for="services">Boutiques/Services</label>
                    <textarea id="services" name="boutiques_services" class="form-control"><?php echo isset($editPlace) ? htmlspecialchars($editPlace['boutiques_services']) : ''; ?></textarea>
            </div>
                <div class="form-group">
                <label>Images du lieu</label>
                <?php if (isset($editPlace) && !empty($editPlace['hero_images'])): ?>
                    <div class="existing-images">
                            <?php
                        $images = explode(',', $editPlace['hero_images']);
                        foreach ($images as $index => $image):
                            $image = trim($image);
                            if (!empty($image)):
                            ?>
                            <div class="existing-image-item">
                                <img src="/project10/<?php echo htmlspecialchars($image); ?>" alt="Image <?php echo $index + 1; ?>">
                                <span class="delete-image" onclick="deleteImage(<?php echo $index; ?>, '<?php echo htmlspecialchars($image); ?>', <?php echo $editPlace['id']; ?>)">
                                    <i class="fas fa-times"></i>
                                </span>
                                </div>
                        <?php
                            endif;
                        endforeach;
                        ?>
                    </div>
                <?php endif; ?>
                
                    <div class="upload-container" id="uploadContainer">
                            <i class="fas fa-cloud-upload-alt"></i>
                    <p>Glissez et déposez vos images ici ou <span class="browse-text">parcourir</span></p>
                    <input type="file" id="fileInput" name="hero_images_upload[]" multiple accept="image/*">
                        </div>
                <div id="imagePreview" class="image-preview-container"></div>
            </div>

                <div class="map-container">
                    <label>Sélectionner l'emplacement sur la carte</label>
                    <div id="map"></div>
                    <p class="map-help-text">
                        <i class="fas fa-info-circle"></i>
                        Cliquez sur la carte pour définir l'emplacement. Les coordonnées seront automatiquement remplies.
                    </p>
                </div>

                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        var map = L.map('map').setView([31.7917, -7.0926], 5);
                        
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '© OpenStreetMap contributors'
                        }).addTo(map);

                        var marker;

                        // Fonction pour mettre à jour le marqueur et les champs
                        function updateMarkerAndFields(lat, lng, address) {
                            if (marker) {
                                map.removeLayer(marker);
                            }
                            marker = L.marker([lat, lng]).addTo(map);
                            map.setView([lat, lng], 13);
                            
                            document.getElementById('latitude').value = lat.toFixed(6);
                            document.getElementById('longitude').value = lng.toFixed(6);
                            
                            if (address) {
                                document.getElementById('adresse').value = address;
                                marker.bindPopup(address).openPopup();
                            }
                        }

                        // Barre de recherche
                        L.Control.geocoder({
                            defaultMarkGeocode: false,
                            placeholder: 'Rechercher un lieu...'
                        })
                        .on('markgeocode', function(e) {
                            var center = e.geocode.center;
                            var name = e.geocode.name;
                            updateMarkerAndFields(center.lat, center.lng, name);
                        })
                        .addTo(map);

                        // Gérer le clic sur la carte
                        map.on('click', function(e) {
                            var lat = e.latlng.lat;
                            var lng = e.latlng.lng;
                            
                            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&accept-language=fr`)
                                .then(res => res.json())
                                .then(data => {
                                    var address = data.display_name || `Coordonnées : ${lat.toFixed(4)}, ${lng.toFixed(4)}`;
                                    updateMarkerAndFields(lat, lng, address);
                                })
                                .catch(() => {
                                    updateMarkerAndFields(lat, lng, `Coordonnées : ${lat.toFixed(4)}, ${lng.toFixed(4)}`);
                                });
                        });

                        // Si les coordonnées sont déjà définies (en mode édition)
                        var lat = document.getElementById('latitude').value;
                        var lng = document.getElementById('longitude').value;
                        var address = document.getElementById('adresse').value;
                        if (lat && lng) {
                            updateMarkerAndFields(parseFloat(lat), parseFloat(lng), address);
                        }
                    });
                </script>

            <div class="form-actions">
                    <button type="submit" class="btn-submit">
                        <i class="fas <?php echo isset($editMode) && $editMode ? 'fa-save' : 'fa-plus'; ?>"></i>
                        <?php echo isset($editMode) && $editMode ? 'Mettre à jour' : 'Ajouter'; ?>
                </button>
                <?php if (isset($editMode) && $editMode): ?>
                    <a href="admin-places.php" class="btn-cancel">
                        <i class="fas fa-times"></i>
                        Annuler
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="table-section">
        <div class="table-header">
            <i class="fas fa-list"></i>
            <h2>Liste des lieux (<?php echo $total_items; ?> au total)</h2>
            
        </div>
        <div class="filters-section">
            <form method="GET" class="search-filters">
                <div class="search-box">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" name="search" id="searchInput" class="search-input" placeholder="Rechercher un lieu..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <i class="fas fa-times clear-search"></i>
                </div>
                <div class="filters-group">
                    <div class="filter">
                        <i class="fas fa-city"></i>
                        <select id="villeFilter" name="ville_id" class="filter-select">
                            <option value="">Toutes les villes</option>
                            <?php foreach ($villes as $ville): ?>
                                <option value="<?php echo $ville['id']; ?>" <?php if(isset($_GET['ville_id']) && $_GET['ville_id'] == $ville['id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($ville['nom']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter">
                        <i class="fas fa-tag"></i>
                        <select id="categorieFilter" name="categorie" class="filter-select">
                            <option value="">Toutes les catégories</option>
                            <?php foreach ($categories as $categorie): ?>
                                <option value="<?php echo htmlspecialchars($categorie); ?>" <?php if(isset($_GET['categorie']) && $_GET['categorie'] == $categorie) echo 'selected'; ?>>
                                    <?php echo ucfirst(str_replace('_', ' ', htmlspecialchars($categorie))); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-actions">
                        <button type="submit" class="btn-filter btn-apply">
                            <i class="fas fa-filter"></i>
                            Appliquer
                        </button>
                        <button type="button" class="btn-filter btn-reset">
                            <i class="fas fa-undo"></i>
                            Réinitialiser
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <style>
            .filters-section {
                background: white;
                padding: 20px;
                margin-bottom: 20px;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }

            .search-filters {
                display: flex;
                flex-direction: column;
                gap: 20px;
            }

            .search-box {
                position: relative;
                flex: 1;
            }

            .search-input {
                width: 100%;
                padding: 12px 40px 12px 40px;
                border: 2px solid #E2E8F0;
                border-radius: 8px;
                font-size: 1rem;
                transition: all 0.3s ease;
            }

            .search-box .search-icon {
                position: absolute;
                left: 15px;
                top: 50%;
                transform: translateY(-50%);
                color: #64748B;
            }

            .search-box .clear-search {
                position: absolute;
                right: 15px;
                top: 50%;
                transform: translateY(-50%);
                color: #64748B;
                cursor: pointer;
                padding: 5px;
                display: none;
            }

            .search-box .clear-search:hover {
                color: #4A5568;
            }

            .search-input:focus {
                border-color: var(--primary-color);
                box-shadow: 0 0 0 3px rgba(45, 121, 109, 0.1);
            }

            .search-suggestions {
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: white;
                border: 1px solid #E2E8F0;
                border-radius: 8px;
                margin-top: 5px;
                max-height: 200px;
                overflow-y: auto;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                z-index: 1000;
                display: none;
            }

            .suggestion-item {
                padding: 10px 15px;
                cursor: pointer;
                border-bottom: 1px solid #eee;
                transition: background-color 0.2s;
            }

            .suggestion-item:last-child {
                border-bottom: none;
            }

            .suggestion-item:hover,
            .suggestion-item.active {
                background-color: #f8f9fa;
            }

            .suggestion-name {
                font-weight: 500;
                color: #2c3e50;
                margin-bottom: 4px;
            }

            .suggestion-name strong {
                color: #2980b9;
            }

            .suggestion-details {
                display: flex;
                gap: 15px;
                font-size: 0.85rem;
                color: #666;
                margin-bottom: 4px;
            }

            .suggestion-details i {
                margin-right: 5px;
                color: #7f8c8d;
            }

            .suggestion-description {
                font-size: 0.85rem;
                color: #666;
                margin-top: 4px;
                font-style: italic;
            }

            .filters-group {
                display: flex;
                gap: 15px;
                align-items: center;
                flex-wrap: wrap;
            }

            .filter {
                position: relative;
                flex: 1;
                min-width: 200px;
            }

            .filter i {
                position: absolute;
                left: 15px;
                top: 50%;
                transform: translateY(-50%);
                color: #64748B;
            }

            .filter-select {
                width: 100%;
                padding: 12px 40px;
                border: 2px solid #E2E8F0;
                border-radius: 8px;
                font-size: 1rem;
                appearance: none;
                background: white;
                cursor: pointer;
                transition: all 0.3s ease;
            }

            .filter-select:focus {
                border-color: var(--primary-color);
                box-shadow: 0 0 0 3px rgba(45, 121, 109, 0.1);
            }

            .btn-filter {
                padding: 12px 24px;
                background: var(--primary-color);
                color: white;
                border: none;
                border-radius: 8px;
                font-size: 1rem;
                font-weight: 500;
                display: flex;
                align-items: center;
                gap: 8px;
                cursor: pointer;
                transition: all 0.3s ease;
            }

            .btn-filter:hover {
                background: #236b5f;
                transform: translateY(-2px);
            }

            @media (max-width: 768px) {
                .filters-group {
                    flex-direction: column;
                }

                .filter {
                    width: 100%;
                }

                .btn-filter {
                    width: 100%;
                    justify-content: center;
                }
            }
        </style>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const searchInput = document.getElementById('searchInput');
                const suggestionsBox = document.getElementById('searchSuggestions');
                const clearSearch = document.querySelector('.clear-search');
                const filterForm = document.querySelector('.search-filters');
                let currentFocus = -1;

                // Gérer le clic sur le bouton Réinitialiser
                filterForm.addEventListener('submit', function(e) {
                    e.preventDefault(); // Empêcher le rechargement de la page
                    const formData = new FormData(this);
                    const params = new URLSearchParams(formData);
                    
                    // Mettre à jour l'URL sans recharger la page
                    const newUrl = window.location.pathname + '?' + params.toString();
                    window.history.pushState({ path: newUrl }, '', newUrl);
                    
                    // Récupérer et afficher les résultats filtrés
                    fetch('get_filtered_places.php?' + params.toString())
                        .then(response => response.json())
                        .then(data => {
                            updateTableContent(data);
                        })
                        .catch(error => {
                            console.error('Erreur:', error);
                        });
                });

                // Fonction pour mettre à jour le contenu du tableau
                function updateTableContent(places) {
                    const tableBody = document.querySelector('.table tbody');
                    const totalCounter = document.querySelector('.table-header h2');

                    if (!places || places.length === 0) {
                        tableBody.innerHTML = `
                            <tr>
                                <td colspan="11" class="text-center">
                                    <div class="no-results">
                                        <i class="fas fa-search"></i>
                                        <p>Aucun lieu trouvé</p>
                                    </div>
                                </td>
                            </tr>`;
                        totalCounter.textContent = 'Liste des lieux (0 au total)';
                        return;
                    }

                    tableBody.innerHTML = '';
                    places.forEach(place => {
                        const heroImages = place.hero_images ? place.hero_images.split(',') : [];
                        const row = `
                        <tr>
                            <td class="actions-col">
                                <div class="action-buttons">
                                    <a href="?edit=${place.id}" class="btn-action btn-edit" title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="javascript:void(0)" onclick="deletePlace(${place.id}, '${place.nom}')" class="btn-action btn-delete" title="Supprimer">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </div>
                            </td>
                            <td class="id-col">${place.id}</td>
                            <td class="id-col">${place.id_ville}</td>
                            <td class="name-col">
                                <div class="place-info">
                                    <span class="place-name">${place.nom}</span>
                                </div>
                            </td>
                            <td class="category-col">
                                <span class="category-badge ${place.categorie.toLowerCase()}">
                                    ${place.categorie}
                                </span>
                            </td>
                            <td class="images-col">
                                ${heroImages.length > 0 ? `
                                <div class="images-gallery">
                                    ${heroImages.slice(0, 3).map((image, index) => `
                                    <div class="image-item">
                                        <img src="/project10/${image.trim()}" 
                                             alt="Image ${index + 1}" 
                                             onclick="openImageModal('/project10/${image.trim()}')"
                                             title="Cliquez pour agrandir">
                                    </div>
                                    `).join('')}
                                </div>
                                ` : '<span class="no-images">Aucune image</span>'}
                            </td>
                            <td class="desc-col">${place.description || ''}</td>
                            <td class="equip-col">
                                ${place.equipements ? place.equipements + '<br><br>' : ''}
                                ${place.boutiques_services || ''}
                            </td>
                            <td class="coord-col">
                                ${place.latitude ? place.latitude + ', ' + place.longitude : ''}
                            </td>
                            <td class="url-col">
                                ${place.url_activite ? `
                                <a href="${place.url_activite}" target="_blank" class="url-link">
                                    <i class="fas fa-external-link-alt"></i>
                                </a>
                                ` : ''}
                            </td>
                            <td class="budget-col">${place.budget || ''}</td>
                        </tr>
                    `;
                    tableBody.insertAdjacentHTML('beforeend', row);
                });

                totalCounter.textContent = `Liste des lieux (${places.length} au total)`;
            }

            // Fonction pour supprimer un lieu
            window.deletePlace = function(id, nom) {
                if (confirm(`Êtes-vous sûr de vouloir supprimer ${nom} ?`)) {
                    fetch(`delete_place.php?id=${id}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Recharger les données du tableau
                                const formData = new FormData(filterForm);
                                const params = new URLSearchParams(formData);
                                fetch('get_filtered_places.php?' + params.toString())
                                    .then(response => response.json())
                                    .then(data => {
                                        updateTableContent(data);
                                    });
                            } else {
                                alert('Erreur lors de la suppression : ' + data.error);
                            }
                        })
                        .catch(error => {
                            console.error('Erreur:', error);
                            alert('Erreur lors de la suppression');
                        });
                }
            };

            // Le reste du code existant pour la recherche et les suggestions...
            // ... (garder tout le code précédent pour les suggestions)
        });
    </script>

    <style>
        /* Styles pour l'indicateur de chargement */
        .loading-indicator {
            text-align: center;
            padding: 2rem;
            color: #666;
        }

        .loading-indicator::after {
            content: '';
            display: inline-block;
            width: 1em;
            height: 1em;
            border: 2px solid #666;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-left: 0.5rem;
            vertical-align: middle;
        }

        .error-message {
            text-align: center;
            padding: 2rem;
            color: #dc3545;
            background: #fff5f5;
        }

        .error-message i {
            margin-right: 0.5rem;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>

    <?php if (empty($places)): ?>
        <div class="no-data">
            <i class="fas fa-info-circle"></i>
            <p>Aucun lieu n'a été trouvé.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th class="actions-col">Actions</th>
                        <th class="id-col">ID</th>
                        <th class="id-col">ID Ville</th>
                        <th class="name-col">Nom</th>
                        <th class="category-col">Catégorie</th>
                        <th class="images-col">Images</th>
                        <th class="desc-col">Description</th>
                        <th class="equip-col">Équipements</th>
                        <th class="coord-col">Coordonnées</th>
                        <th class="url-col">URL</th>
                        <th class="budget-col">Budget</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($places as $place): ?>
                        <tr>
                            <td class="actions-col">
                                <div class="action-buttons">
                                    <a href="?edit=<?php echo $place['id']; ?>" class="btn-action btn-edit" title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="javascript:void(0)" onclick="deletePlace(<?php echo $place['id']; ?>, '<?php echo htmlspecialchars($place['nom']); ?>')" class="btn-action btn-delete" title="Supprimer">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </div>
                            </td>
                            <td class="id-col"><?php echo $place['id']; ?></td>
                            <td class="id-col"><?php echo $place['id_ville']; ?></td>
                            <td class="name-col">
                                <div class="place-info">
                                    <span class="place-name"><?php echo htmlspecialchars($place['nom']); ?></span>
                                </div>
                            </td>
                            <td class="category-col">
                                <?php if (!empty($place['categorie'])): ?>
                                    <span class="category-badge <?php echo strtolower($place['categorie']); ?>">
                                        <?php echo htmlspecialchars($place['categorie']); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="images-col">
                                <?php 
                                $heroImages = !empty($place['hero_images']) ? explode(',', $place['hero_images']) : [];
                                if (!empty($heroImages)): 
                                ?>
                                    <div class="images-gallery">
                                        <?php foreach (array_slice($heroImages, 0, 3) as $index => $image): ?>
                                            <div class="image-item">
                                                <img src="/project10/<?php echo trim($image); ?>" 
                                                     alt="Image <?php echo $index + 1; ?>" 
                                                     onclick="openImageModal('/project10/<?php echo trim($image); ?>')"
                                                     title="Cliquez pour agrandir">
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="no-images">Aucune image</span>
                                <?php endif; ?>
                            </td>
                            <td class="desc-col"><?php echo htmlspecialchars($place['description'] ?? ''); ?></td>
                            <td class="equip-col">
                                <?php 
                                if (!empty($place['equipements'])) {
                                    echo htmlspecialchars($place['equipements']) . '<br><br>';
                                }
                                if (!empty($place['boutiques_services'])) {
                                    echo htmlspecialchars($place['boutiques_services']);
                                }
                                ?>
                            </td>
                            <td class="coord-col">
                                <?php 
                                if (!empty($place['latitude']) && !empty($place['longitude'])) {
                                    echo $place['latitude'] . ', ' . $place['longitude'];
                                }
                                ?>
                            </td>
                            <td class="url-col">
                                <?php if (!empty($place['url_activite'])): ?>
                                    <a href="<?php echo htmlspecialchars($place['url_activite']); ?>" target="_blank" class="url-link">
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                            <td class="budget-col"><?php echo htmlspecialchars($place['budget'] ?? ''); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    </div>
    </main>

    <!-- Modal pour afficher les images en grand -->
    <div id="imageModal" class="image-modal">
        <span class="close-modal" onclick="closeImageModal()">&times;</span>
        <div class="modal-content">
            <img id="modalImage" src="" alt="Image en grand">
        </div>
    </div>

    <script>
        // Fonction pour ouvrir la modal d'image
        function openImageModal(imageSrc) {
            const modal = document.getElementById('imageModal');
            const modalImg = document.getElementById('modalImage');
            modal.style.display = "flex";
            modalImg.src = imageSrc;
        }

        // Fonction pour fermer la modal d'image
        function closeImageModal() {
            document.getElementById('imageModal').style.display = "none";
        }

        // Fermer la modal si on clique en dehors de l'image
        document.getElementById('imageModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeImageModal();
            }
        });

        // Fonction pour supprimer une image
        function deleteImage(index, imagePath, placeId) {
            if (confirm('Êtes-vous sûr de vouloir supprimer cette image ?')) {
                window.location.href = `admin-places.php?delete_hero_image=1&place_id=${placeId}&image_path=${encodeURIComponent(imagePath)}`;
            }
        }

        // Prévisualisation des images
        document.getElementById('fileInput').addEventListener('change', function(e) {
            const preview = document.getElementById('imagePreview');
            preview.innerHTML = '';

            Array.from(e.target.files).forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const div = document.createElement('div');
                    div.className = 'preview-image-item';
                    div.innerHTML = `
                        <img src="${e.target.result}" alt="Preview ${index + 1}">
                    `;
                    preview.appendChild(div);
                }
                reader.readAsDataURL(file);
            });
        });

        // Gestion du drag & drop
        const uploadContainer = document.getElementById('uploadContainer');
        const fileInput = document.getElementById('fileInput');

        uploadContainer.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadContainer.style.backgroundColor = '#E6F0EE';
        });

        uploadContainer.addEventListener('dragleave', (e) => {
            e.preventDefault();
            uploadContainer.style.backgroundColor = '';
        });

        uploadContainer.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadContainer.style.backgroundColor = '';
            fileInput.files = e.dataTransfer.files;
            fileInput.dispatchEvent(new Event('change'));
        });

        document.querySelector('.browse-text').addEventListener('click', () => {
            fileInput.click();
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const suggestionsBox = document.getElementById('searchSuggestions');
            const clearSearch = document.querySelector('.clear-search');
            let currentFocus = -1;

            // Afficher le bouton de suppression quand il y a du texte
            searchInput.addEventListener('input', function() {
                clearSearch.style.display = this.value ? 'block' : 'none';
                if (this.value.length >= 2) {
                    fetchSuggestions(this.value);
                } else {
                    suggestionsBox.style.display = 'none';
                }
            });

            // Effacer la recherche
            clearSearch.addEventListener('click', function() {
                searchInput.value = '';
                this.style.display = 'none';
                suggestionsBox.style.display = 'none';
                // Recharger la page sans paramètres de recherche
                const url = new URL(window.location.href);
                url.searchParams.delete('search');
                window.location.href = url.toString();
            });

            // Fonction pour récupérer les suggestions
            function fetchSuggestions(query) {
                const params = new URLSearchParams();
                params.append('search', query);
                
                fetch(`get_places_suggestions.php?${params.toString()}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.length > 0) {
                            showSuggestions(data);
                        } else {
                            suggestionsBox.style.display = 'none';
                        }
                    })
                    .catch(error => {
                        console.error('Erreur:', error);
                        suggestionsBox.style.display = 'none';
                    });
            }

            // Fonction pour afficher les suggestions
            function showSuggestions(suggestions) {
                suggestionsBox.innerHTML = '';
                suggestions.forEach((place, index) => {
                    const div = document.createElement('div');
                    div.className = 'suggestion-item';
                    div.innerHTML = `
                        <div class="suggestion-name">${highlightMatch(place.nom, searchInput.value)}</div>
                        <div class="suggestion-details">
                            <span><i class="fas fa-map-marker-alt"></i> ${place.ville_nom || 'Ville non spécifiée'}</span>
                            <span><i class="fas fa-tag"></i> ${place.categorie || 'Non catégorisé'}</span>
                        </div>
                        ${place.description ? `<div class="suggestion-description">${truncateText(place.description, 100)}</div>` : ''}
                    `;
                    div.addEventListener('click', () => {
                        searchInput.value = place.nom;
                        suggestionsBox.style.display = 'none';
                        // Soumettre le formulaire
                        searchInput.closest('form').submit();
                    });
                    suggestionsBox.appendChild(div);
                });
                suggestionsBox.style.display = 'block';
            }

            // Fonction pour mettre en surbrillance les correspondances
            function highlightMatch(text, query) {
                if (!query) return text;
                const regex = new RegExp(`(${query})`, 'gi');
                return text.replace(regex, '<strong>$1</strong>');
            }

            // Fonction pour tronquer le texte
            function truncateText(text, maxLength) {
                if (text.length <= maxLength) return text;
                return text.substr(0, maxLength) + '...';
            }

            // Gérer la navigation au clavier dans les suggestions
            searchInput.addEventListener('keydown', function(e) {
                const items = suggestionsBox.getElementsByClassName('suggestion-item');
                
                if (e.key === 'ArrowDown') {
                    currentFocus++;
                    addActive(items);
                    e.preventDefault();
                }
                else if (e.key === 'ArrowUp') {
                    currentFocus--;
                    addActive(items);
                    e.preventDefault();
                }
                else if (e.key === 'Enter' && currentFocus > -1) {
                    if (items[currentFocus]) {
                        items[currentFocus].click();
                        e.preventDefault();
                    }
                }
            });

            // Ajouter/supprimer la classe active
            function addActive(items) {
                if (!items || !items.length) return;
                removeActive(items);
                if (currentFocus >= items.length) currentFocus = 0;
                if (currentFocus < 0) currentFocus = items.length - 1;
                items[currentFocus].classList.add('active');
            }

            function removeActive(items) {
                Array.from(items).forEach(item => {
                    item.classList.remove('active');
                });
            }

            // Fermer les suggestions si on clique en dehors
            document.addEventListener('click', function(e) {
                if (!searchInput.contains(e.target) && !suggestionsBox.contains(e.target)) {
                    suggestionsBox.style.display = 'none';
                }
            });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const filterForm = document.querySelector('.search-filters');
            const searchInput = document.getElementById('searchInput');
            const villeFilter = document.getElementById('villeFilter');
            const categorieFilter = document.getElementById('categorieFilter');
            const resetButton = document.querySelector('.btn-reset');
            const placesContainer = document.querySelector('.places-grid');
            
            // Fonction pour réinitialiser les filtres
            resetButton.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Réinitialiser les champs
                searchInput.value = '';
                document.getElementById('villeFilter').value = '';
                document.getElementById('categorieFilter').value = '';
                
                // Masquer le bouton de suppression de la recherche
                clearSearch.style.display = 'none';
                
                // Recharger la page sans paramètres
                window.location.href = window.location.pathname;
            });

            // Le reste du code JavaScript existant...
        });
    </script>
</body>
</html> 