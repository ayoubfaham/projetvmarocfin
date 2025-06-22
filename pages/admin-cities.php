<?php
// --- FORCE ERROR REPORTING --- //
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// --- END FORCE ERROR REPORTING --- //

session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=vmaroc", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Fonctions pour calculer les statistiques
function getVillesCount($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) FROM villes");
    return $stmt->fetchColumn();
}

function getRecommandationsCount($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) FROM recommandations");
    return $stmt->fetchColumn();
}

function getImagesCount($pdo) {
    $stmt = $pdo->query("SELECT hero_images FROM villes");
    $total = 0;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (!empty($row['hero_images'])) {
            $images = array_filter(explode(',', $row['hero_images']));
            $total += count($images);
        }
    }
    return $total;
}

// Récupération des statistiques
$stats = [
    'villes' => getVillesCount($pdo),
    'recommandations' => getRecommandationsCount($pdo),
    'images' => getImagesCount($pdo)
];

// Initialisation des variables pour l'édition
$editMode = false;
$editCity = [
    'id' => '',
    'nom' => '',
    'photo' => '',
    'description' => '',
    'hero_images' => ''
];

// Traitement des formulaires
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nom = trim($_POST['nom']);
        $desc = trim($_POST['description']);
        
    if (!empty($nom) && !empty($desc)) {
        try {
            $pdo->beginTransaction();
            
            // Gestion des images hero
            $uploaded_hero_images = [];
        if (isset($_FILES['hero_images_upload']) && is_array($_FILES['hero_images_upload']['name'])) {
                $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                $uploadDir = '../uploads/cities/hero/';
                
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                foreach ($_FILES['hero_images_upload']['tmp_name'] as $key => $tmp_name) {
                    if ($_FILES['hero_images_upload']['error'][$key] === UPLOAD_ERR_OK) {
                        $filename = $_FILES['hero_images_upload']['name'][$key];
                        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                        if (in_array($ext, $allowed)) {
                            $newFilename = uniqid('city_hero_', true) . '.' . $ext;
                            $targetPath = $uploadDir . $newFilename;
                            
                            if (move_uploaded_file($tmp_name, $targetPath)) {
                                $uploaded_hero_images[] = 'uploads/cities/hero/' . $newFilename;
                            }
                            }
                    }
                }
            }
            
            if (isset($_POST['edit_id']) && $_POST['edit_id'] !== '') {
                // Mode édition
                $id = intval($_POST['edit_id']);
                
                // Récupérer les images existantes
            $stmt = $pdo->prepare("SELECT hero_images FROM villes WHERE id = ?");
            $stmt->execute([$id]);
                $existing_images = $stmt->fetchColumn();
                
                // Combiner les anciennes et nouvelles images
                $hero_images = [];
                if (!empty($existing_images)) {
                    $hero_images = array_merge($hero_images, explode(',', $existing_images));
        }
                if (!empty($uploaded_hero_images)) {
                    $hero_images = array_merge($hero_images, $uploaded_hero_images);
                }
                $hero_images_string = implode(',', array_filter($hero_images));
                
                $stmt = $pdo->prepare("UPDATE villes SET nom = ?, description = ?, hero_images = ? WHERE id = ?");
                $stmt->execute([$nom, $desc, $hero_images_string, $id]);
                $_SESSION['admin_message'] = "Ville modifiée avec succès !";
                            } else {
                // Mode ajout
        $hero_images_string = implode(',', $uploaded_hero_images);
                $stmt = $pdo->prepare("INSERT INTO villes (nom, description, hero_images) VALUES (?, ?, ?)");
                $stmt->execute([$nom, $desc, $hero_images_string]);
                $_SESSION['admin_message'] = "Ville ajoutée avec succès !";
                }
                
                $pdo->commit();
                $_SESSION['admin_message_type'] = 'success';
                header('Location: admin-cities.php');
                exit();
            } catch (Exception $e) {
                $pdo->rollBack();
            $_SESSION['admin_message'] = "Erreur : " . $e->getMessage();
                $_SESSION['admin_message_type'] = 'error';
                header('Location: admin-cities.php');
                exit();
            }
        } else {
        $_SESSION['admin_message'] = "Veuillez remplir tous les champs obligatoires.";
            $_SESSION['admin_message_type'] = 'error';
            header('Location: admin-cities.php');
            exit();
    }
}

// Suppression d'une ville
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    try {
        // Supprimer les fichiers d'images associés
        $stmt = $pdo->prepare("SELECT hero_images FROM villes WHERE id = ?");
        $stmt->execute([$id]);
        $images = $stmt->fetchColumn();
        
        if (!empty($images)) {
            $image_paths = explode(',', $images);
            foreach ($image_paths as $path) {
                if (!empty($path)) {
                    $full_path = '../' . trim($path);
                    if (file_exists($full_path)) {
                        unlink($full_path);
                    }
                }
            }
        }

        $pdo->prepare("DELETE FROM villes WHERE id = ?")->execute([$id]);
        $_SESSION['admin_message'] = "Ville supprimée avec succès !";
        $_SESSION['admin_message_type'] = 'success';
    } catch (Exception $e) {
        $_SESSION['admin_message'] = "Erreur lors de la suppression : " . $e->getMessage();
        $_SESSION['admin_message_type'] = 'error';
    }
    header('Location: admin-cities.php');
    exit();
}

// Suppression d'une image hero
if (isset($_GET['delete_hero_image']) && isset($_GET['city_id']) && isset($_GET['image_path'])) {
    $city_id = intval($_GET['city_id']);
    $image_path = urldecode($_GET['image_path']);
    
    try {
        // Récupérer les images actuelles
    $stmt = $pdo->prepare("SELECT hero_images FROM villes WHERE id = ?");
    $stmt->execute([$city_id]);
        $current_images = $stmt->fetchColumn();
    
        if ($current_images) {
            $images = array_filter(explode(',', $current_images));
            $images = array_filter($images, function($img) use ($image_path) {
            return trim($img) !== trim($image_path);
        });
        
            // Mettre à jour la base de données
            $new_images = implode(',', $images);
        $stmt = $pdo->prepare("UPDATE villes SET hero_images = ? WHERE id = ?");
            $stmt->execute([$new_images, $city_id]);
        
            // Supprimer le fichier
        $file_path = '../' . $image_path;
        if (file_exists($file_path)) {
                unlink($file_path);
        }
        
            $_SESSION['admin_message'] = "Image supprimée avec succès.";
        $_SESSION['admin_message_type'] = 'success';
        }
    } catch (Exception $e) {
        $_SESSION['admin_message'] = "Erreur lors de la suppression de l'image : " . $e->getMessage();
        $_SESSION['admin_message_type'] = 'error';
    }
    
    header('Location: admin-cities.php?edit=' . $city_id);
    exit();
}

// Préparation de l'édition
if (isset($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM villes WHERE id = ?");
    $stmt->execute([$editId]);
    $editCity = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($editCity) {
        $editMode = true;
    }
}

// Liste des villes
$cities = $pdo->query("SELECT id, nom, photo, description, hero_images FROM villes")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Admin - Gestion des Villes</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2D796D;
            --primary-light: #E6F0EE;
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
            line-height: 1.6;
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

        .page-header {
            background: var(--primary-color);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
            text-align: center;
        }

        .page-title {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .page-description {
            font-size: 1.1rem;
            opacity: 0.9;
            max-width: 800px;
            margin: 0 auto;
        }

        .search-box {
            max-width: 600px;
            margin: 2rem auto 0;
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 1rem 3rem;
            border: none;
            border-radius: var(--border-radius);
            font-size: 1rem;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            backdrop-filter: blur(5px);
        }

        .search-input::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.7);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 2rem;
            border-radius: var(--border-radius);
            text-align: center;
            box-shadow: var(--shadow);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card i {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .stat-card h3 {
            font-size: 2.5rem;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .stat-card p {
            color: #666;
            font-size: 1.1rem;
        }

        .notification {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .notification.success {
            background: #d1fae5;
            color: #065f46;
        }

        .notification.error {
            background: #fee2e2;
            color: #dc2626;
        }

        footer {
            background: white;
            padding: 1rem 0;
            text-align: center;
            box-shadow: 0 -2px 4px rgba(0,0,0,0.1);
            margin-top: 2rem;
        }

        @media (max-width: 1200px) {
            .stats-grid {
            grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .header-container {
                padding: 0 1rem;
            }

            .nav-center {
                display: none;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .page-header {
                padding: 2rem 0;
            }

            .page-title {
                font-size: 2rem;
            }
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .section-header {
            background: var(--primary-color);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .section-header i {
            font-size: 1.2rem;
        }

        .section-header h2 {
            font-size: 1.2rem;
            font-weight: 500;
            margin: 0;
        }

        .form-container {
            background: white;
            padding: 2rem;
            border-radius: 0 0 var(--border-radius) var(--border-radius);
            margin-bottom: 2rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #666;
            font-size: 0.9rem;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px var(--primary-light);
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
            border: 2px dashed #ddd;
            padding: 2rem;
            text-align: center;
            border-radius: var(--border-radius);
            background: #f8f9fa;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .upload-container:hover {
            border-color: var(--primary-color);
            background: var(--primary-light);
        }

        .upload-container i {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .upload-container p {
            color: #666;
            margin: 0;
        }

        .image-preview {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 1rem;
        }

        .preview-item {
            position: relative;
            width: 100px;
            height: 100px;
        }

        .preview-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: var(--border-radius);
        }

        .remove-image {
            position: absolute;
            top: -8px;
            right: -8px;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #dc3545;
            color: white;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: var(--shadow);
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: var(--border-radius);
            overflow: hidden;
        }

        .table th,
        .table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .table th {
            background: #f8f9fa;
            font-weight: 500;
            color: #666;
            font-size: 0.9rem;
        }

        .table td {
            font-size: 0.9rem;
            color: #333;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .btn-edit,
        .btn-delete {
            width: 32px;
            height: 32px;
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-edit {
            background: var(--primary-color);
        }

        .btn-delete {
            background: #dc3545;
        }

        .btn-edit:hover {
            background: #236b5f;
        }

        .btn-delete:hover {
            background: #c82333;
        }

        .place-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .place-thumbnail {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: var(--border-radius);
        }

        .category-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            background: var(--primary-light);
            color: var(--primary-color);
            font-size: 0.8rem;
            font-weight: 500;
        }

        .description-cell {
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .btn-submit {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: var(--border-radius);
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: background-color 0.3s ease;
        }

        .btn-submit:hover {
            background: #236b5f;
        }

        .btn-cancel {
            background: #f8f9fa;
            color: #666;
            border: 1px solid #ddd;
            padding: 0.75rem 1.5rem;
            border-radius: var(--border-radius);
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .btn-cancel:hover {
            background: #e9ecef;
            color: #333;
        }

        @media (max-width: 1200px) {
            .form-row {
                grid-template-columns: repeat(2, 1fr);
        }
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            .container {
                padding: 1rem;
        }

            .table th,
            .table td {
                padding: 0.75rem;
            }
        }

        .city-images {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            align-items: center;
        }

        .city-images .place-thumbnail {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: var(--border-radius);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s ease;
        }

        .city-images .place-thumbnail:hover {
            transform: scale(1.1);
        }

        .more-images {
            background: rgba(0, 0, 0, 0.5);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: var(--border-radius);
            font-size: 0.8rem;
        }

        .text-muted {
            color: #6c757d;
            font-style: italic;
        }

        /* Style de la modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .modal.active {
            display: block;
        }

        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 2rem;
            border-radius: 8px;
            width: 90%;
            max-width: 600px;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .modal-title {
            font-size: 2rem;
            font-weight: 500;
            margin: 0;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            opacity: 0.5;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            font-size: 1.1rem;
            color: #666;
            margin-bottom: 0.5rem;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
        }

        #currentHeroImage {
            margin-bottom: 1rem;
        }

        #currentHeroImage img {
            max-width: 200px;
            height: auto;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .btn-choose-photo {
            background: #f5f5f5;
            border: none;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            cursor: pointer;
            color: #666;
            font-size: 1rem;
        }

        .btn-choose-photo:hover {
            background: #e9e9e9;
        }

        .btn-submit {
            width: 100%;
            padding: 1rem;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            margin-top: 1rem;
        }

        .btn-submit:hover {
            background: #236b5f;
        }
    </style>
</head>
<body>
    <div class="menu-overlay" id="menuOverlay"></div>
    <div class="header">
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
                        <a href="admin-cities.php" class="dropdown-item active">
                            <i class="fas fa-building"></i>
                            <span>Gérer les villes</span>
                        </a>
                        <a href="admin-places.php" class="dropdown-item">
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
    </div>

    <div class="page-header">
        <div class="container">
            <h1 class="page-title">Gestion des Villes</h1>
            <p class="page-description">Gérez facilement les villes et leurs informations depuis cette interface intuitive.</p>
            <div class="search-box">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="searchCityInput" placeholder="Rechercher une ville..." class="search-input">
            </div>
        </div>
    </div>

    <div class="main-content">
        <div class="container">
            <?php if (isset($_SESSION['admin_message'])): ?>
                <div class="notification <?= $_SESSION['admin_message_type'] === 'success' ? 'success' : 'error' ?>">
                    <?= $_SESSION['admin_message'] ?>
                </div>
                <?php unset($_SESSION['admin_message']); unset($_SESSION['admin_message_type']); ?>
            <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                    <i class="fas fa-city"></i>
                    <h3><?php echo $stats['villes']; ?></h3>
                    <p>Villes</p>
            </div>
            <div class="stat-card">
                    <i class="fas fa-star"></i>
                    <h3><?php echo $stats['recommandations']; ?></h3>
                    <p>Recommandations</p>
                </div>
            <div class="stat-card">
                    <i class="fas fa-images"></i>
                    <h3><?php echo $stats['images']; ?></h3>
                    <p>Images</p>
                    </div>
                </div>

            <div class="add-section">
                <div class="section-header">
                    <i class="fas fa-plus"></i>
                    <h2><?php echo $editMode ? 'Modifier la ville' : 'Ajouter une ville'; ?></h2>
                </div>
                <div class="form-container">
                    <form method="post" class="city-form" enctype="multipart/form-data">
                        <?php if ($editMode): ?>
                            <input type="hidden" name="edit_id" value="<?php echo htmlspecialchars($editCity['id']); ?>">
                        <?php endif; ?>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="nom">Nom de la ville</label>
                                <input type="text" id="nom" name="nom" class="form-control" value="<?php echo htmlspecialchars($editCity['nom']); ?>" required>
                </div>
        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" class="form-control" rows="4" required><?php echo htmlspecialchars($editCity['description']); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>Images de la ville</label>
                            <div class="upload-container" id="fileUploadArea">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <p>Glissez et déposez vos images ici, ou<br>cliquez pour sélectionner</p>
                                <input type="file" name="hero_images_upload[]" id="hero_images" multiple accept="image/*" style="display: none;">
                            </div>
                            <div id="imagePreview" class="image-preview">
                                <?php if ($editMode && !empty($editCity['hero_images'])): ?>
                                    <?php foreach (explode(',', $editCity['hero_images']) as $image): ?>
                                        <?php if (!empty(trim($image))): ?>
                                        <div class="preview-item">
                                            <img src="../<?php echo htmlspecialchars(trim($image)); ?>" alt="Preview">
                                            <button type="button" class="remove-image" onclick="removeImage(this, '<?php echo htmlspecialchars(trim($image)); ?>')">
                                                <i class="fas fa-times"></i>
                </button>
                                        </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn-submit">
                                <i class="fas fa-save"></i>
                                <?php echo $editMode ? 'Enregistrer les modifications' : 'Ajouter la ville'; ?>
                            </button>
                            <?php if ($editMode): ?>
                                <a href="admin-cities.php" class="btn-cancel">
                                    <i class="fas fa-times"></i>
                                    Annuler
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <div class="list-section">
                <div class="section-header">
                    <i class="fas fa-list"></i>
                    <h2>Liste des villes (<?php echo count($cities); ?> au total)</h2>
            </div>
            <div class="table-responsive">
                    <table class="table">
                    <thead>
                        <tr>
                                <th>Actions</th>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Description</th>
                                <th>Images</th>
                        </tr>
                    </thead>
                    <tbody>
                            <?php foreach ($cities as $city): ?>
                                <tr>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="?edit=<?php echo $city['id']; ?>" class="btn-edit" title="Modifier">
                                    <i class="fas fa-edit"></i>
                                </a>
                                            <a href="?delete=<?php echo $city['id']; ?>" class="btn-delete" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette ville ?')" title="Supprimer">
                                    <i class="fas fa-trash"></i>
                                </a>
                                        </div>
                                    </td>
                                    <td><?php echo $city['id']; ?></td>
                                    <td><?php echo htmlspecialchars($city['nom']); ?></td>
                                    <td class="description-cell"><?php echo htmlspecialchars($city['description']); ?></td>
                                    <td>
                                        <div class="city-images">
                                            <?php
                                            if (!empty($city['hero_images'])) {
                                                $hero_images = array_filter(explode(',', $city['hero_images']));
                                                foreach ($hero_images as $index => $image):
                                                    if ($index < 3 && !empty(trim($image))): // Afficher maximum 3 miniatures
                                            ?>
                                                    <img src="../<?php echo htmlspecialchars(trim($image)); ?>" alt="Image <?php echo $index + 1; ?>" class="place-thumbnail">
                                            <?php
                                                    endif;
                                                endforeach;
                                                if (count($hero_images) > 3) {
                                                    echo '<span class="more-images">+' . (count($hero_images) - 3) . '</span>';
                                                }
                                            } else {
                                                echo '<span class="text-muted">Aucune image</span>';
                                            }
                                            ?>
                                        </div>
                            </td>
                                </tr>
                            <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>
            </div>
        </div>

    <footer>
        <div class="container">
            <p>© 2025 VMaroc. Tous droits réservés.</p>
    </div>
    </footer>

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

    // Auto-hide notifications after 3 seconds
    document.addEventListener('DOMContentLoaded', function() {
        const notifications = document.querySelectorAll('.notification');
        notifications.forEach(notification => {
            setTimeout(() => {
                notification.style.transition = 'opacity 0.5s ease';
                notification.style.opacity = '0';
                setTimeout(() => {
                    notification.remove();
                }, 500);
            }, 3000);
        });
    });
    </script>

    <script>
    // JavaScript pour la gestion des images et le glisser-déposer
    const fileUploadArea = document.getElementById('fileUploadArea');
    const fileInput = document.getElementById('hero_images');
    const imagePreview = document.getElementById('imagePreview');

    // Empêcher le comportement par défaut de glisser-déposer
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        fileUploadArea.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    // Mettre en évidence la zone de dépôt lors du survol
    ['dragenter', 'dragover'].forEach(eventName => {
        fileUploadArea.addEventListener(eventName, () => fileUploadArea.classList.add('dragover'), false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        fileUploadArea.addEventListener(eventName, () => fileUploadArea.classList.remove('dragover'), false);
    });

    // Gérer les fichiers déposés
    fileUploadArea.addEventListener('drop', handleDrop, false);

    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        fileInput.files = files;
        updateImagePreview(files);
    }

    // Gérer le clic sur la zone de dépôt
    fileUploadArea.addEventListener('click', () => {
        fileInput.click();
    });

    // Gérer la sélection de fichiers via le dialogue
    fileInput.addEventListener('change', function() {
        updateImagePreview(this.files);
    });

    // Mettre à jour l'aperçu des images
    function updateImagePreview(files) {
        if (!files || files.length === 0) return;
        
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            if (!file.type.match('image.*')) continue;
            
            const reader = new FileReader();
            reader.onload = function(e) {
                const div = document.createElement('div');
                div.className = 'preview-item new-upload';
                div.innerHTML = `
                    <img src="${e.target.result}" alt="Image Preview">
                    <button type="button" class="preview-remove"><i class="fas fa-times"></i></button>
                `;
                imagePreview.appendChild(div);
                
                // Ajouter un gestionnaire d'événements pour le bouton de suppression
                div.querySelector('.preview-remove').addEventListener('click', function() {
                    div.remove();
                });
            };
            reader.readAsDataURL(file);
        }
    }

    // Recherche de villes
    document.getElementById('searchCityInput').addEventListener('keyup', function() {
        const searchValue = this.value.toLowerCase();
        const rows = document.querySelectorAll('#citiesTable tbody tr');
        
        rows.forEach(row => {
            const cityName = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
            const cityDesc = row.querySelector('td:nth-child(5)').textContent.toLowerCase();
            
            if (cityName.includes(searchValue) || cityDesc.includes(searchValue)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
    </script>

    <!-- Modal de modification -->
    <div class="modal" id="editModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Modifier la ville</h3>
                <button type="button" class="modal-close" onclick="closeModal()">&times;</button>
        </div>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="edit_id" value="">

                <div class="form-group">
                    <label for="nom">Nom</label>
                    <input type="text" id="nom" name="nom" class="form-control" required>
                    </div>

                <div class="form-group">
                    <label for="photo">Photo</label>
                    <div class="photo-section">
                        <div class="current-photo" id="currentPhoto">
                            <!-- L'image actuelle sera affichée ici -->
                        </div>
                        <div class="photo-upload">
                            <input type="file" id="photoInput" name="photo_file" accept="image/*">
                            <label for="photoInput">Choisir une nouvelle photo</label>
                            <button type="button" class="btn-delete-photo" style="display: none;">Supprimer</button>
                    </div>
                        <div class="photo-preview" id="photoPreview">
                            <!-- La prévisualisation de la nouvelle photo sera affichée ici -->
                        </div>
                    </div>
                    <input type="hidden" name="photo" id="photoHidden">
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" class="form-control" rows="4" required></textarea>
                </div>

                <button type="submit" class="btn-submit">Enregistrer les modifications</button>
            </form>
        </div>
    </div>

    <script>
    function closeModal() {
        document.getElementById('editModal').classList.remove('active');
        window.location.href = 'admin-cities.php';
    }

    // Gestion de l'upload de l'image hero
    document.getElementById('heroImageInput').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const formData = new FormData();
            formData.append('photo', file);
            
            fetch('upload_city_photo.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const currentHeroImage = document.getElementById('currentHeroImage');
                    currentHeroImage.innerHTML = `<img src="../${data.url}" alt="Hero Image">`;
                    document.getElementById('heroImagePath').value = data.url;
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors de l\'upload de l\'image');
            });
        }
    });

    // Mise à jour de la modal avec les données de la ville
    document.querySelectorAll('.btn-edit').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const cityId = this.getAttribute('href').split('=')[1];
            console.log('Édition de la ville ID:', cityId);
            
            fetch(`get_city.php?id=${cityId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Erreur réseau');
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Données reçues:', data);
                    
                    // Remplir les champs de la modal
                    document.querySelector('#editModal input[name="edit_id"]').value = data.id;
                    document.querySelector('#editModal input[name="nom"]').value = data.nom;
                    document.querySelector('#editModal textarea[name="description"]').value = data.description;
                    
                    // Afficher l'image hero existante
                    const currentHeroImage = document.getElementById('currentHeroImage');
                    if (data.hero_images) {
                        const firstImage = data.hero_images.split(',')[0];
                        if (firstImage && firstImage.trim()) {
                            console.log('Hero image trouvée:', firstImage);
                            currentHeroImage.innerHTML = `<img src="../${firstImage.trim()}" alt="Hero Image">`;
                            document.getElementById('heroImagePath').value = firstImage.trim();
                        } else {
                            console.log('Aucune hero image valide trouvée');
                            currentHeroImage.innerHTML = '<p>Aucune photo</p>';
                        }
                    } else {
                        console.log('Aucune hero image trouvée');
                        currentHeroImage.innerHTML = '<p>Aucune photo</p>';
                    }
                    
                    // Afficher la modal
                    document.getElementById('editModal').classList.add('active');
                })
                .catch(error => {
                    console.error('Erreur lors de la récupération des données:', error);
                    alert('Erreur lors de la récupération des données de la ville');
                });
        });
    });

    // Fermer la modal quand on clique en dehors
    document.getElementById('editModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });
    </script>
</body>
</html>