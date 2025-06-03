<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'config/database.php';

// Récupération de l'ID du lieu depuis l'URL
$placeId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Récupération des informations du lieu (avec nouveaux champs)
$place = $pdo->prepare("
    SELECT l.*, v.nom as ville_nom, v.id as ville_id
    FROM lieux l 
    JOIN villes v ON l.id_ville = v.id 
    WHERE l.id = ?
");
$place->execute([$placeId]);
$place = $place->fetch(PDO::FETCH_ASSOC);

// Récupération des avis
$reviews = $pdo->prepare("
    SELECT r.*, u.nom as user_nom 
    FROM avis r 
    JOIN utilisateurs u ON r.id_utilisateur = u.id 
    WHERE r.id_lieu = ? 
    ORDER BY r.date_creation DESC
");
$reviews->execute([$placeId]);
$reviews = $reviews->fetchAll(PDO::FETCH_ASSOC);

// Traitement des équipements et services
$equipements = isset($place['equipements']) ? array_map('trim', explode(',', $place['equipements'])) : [];
$boutiques = isset($place['boutiques_services']) ? array_map('trim', explode(',', $place['boutiques_services'])) : [];

// Vérifier si l'utilisateur a déjà laissé un avis pour ce lieu
$userHasReview = false;
$userReview = null;

if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM avis WHERE id_utilisateur = ? AND id_lieu = ?");
    $stmt->execute([$_SESSION['user_id'], $placeId]);
    $userReview = $stmt->fetch(PDO::FETCH_ASSOC);
    $userHasReview = ($userReview !== false);
}

// Traitement de l'ajout ou de la modification d'un avis
if (isset($_POST['add_review']) && isset($_SESSION['user_id'])) {
    $rating = (int)$_POST['rating'];
    $commentaire = trim($_POST['commentaire'] ?? '');
    $id_utilisateur = $_SESSION['user_id'];
    $id_lieu = $place['id'];

    if ($rating >= 1 && $rating <= 5) {
        try {
            if ($userHasReview) {
                // Mise à jour de l'avis existant
                $stmt = $pdo->prepare("UPDATE avis SET rating = ?, commentaire = ? WHERE id_utilisateur = ? AND id_lieu = ?");
                $stmt->execute([$rating, $commentaire, $id_utilisateur, $id_lieu]);
                $success_message = "Votre avis a bien été mis à jour !";
            } else {
                // Insertion d'un nouvel avis
                $stmt = $pdo->prepare("INSERT INTO avis (id_utilisateur, id_lieu, rating, commentaire, date_creation) VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$id_utilisateur, $id_lieu, $rating, $commentaire]);
                $success_message = "Votre avis a bien été enregistré !";
            }
            
            // Mise à jour de la note moyenne du lieu
            $stmt = $pdo->prepare("SELECT AVG(rating) as moyenne FROM avis WHERE id_lieu = ?");
            $stmt->execute([$id_lieu]);
            $moyenne = $stmt->fetchColumn();

            $stmt = $pdo->prepare("UPDATE lieux SET rating = ? WHERE id = ?");
            $stmt->execute([$moyenne, $id_lieu]);
            
            // Rafraichir la page pour voir les changements
            header("Location: place.php?id=" . $placeId . "&success=1");
            exit;
        } catch (PDOException $e) {
            $error_message = "Une erreur est survenue : " . $e->getMessage();
        }
    } else {
        $error_message = "Veuillez sélectionner une note entre 1 et 5.";
    }
}

// Message de succès après redirection
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $success_message = "Votre avis a bien été enregistré !";
}

// Préparer les images pour le slider
$sliderImages = [];

// Débogage des images hero
error_log("[DEBUG PLACE.PHP] ID du lieu: " . $placeId);
error_log("[DEBUG PLACE.PHP] Valeur de hero_images: " . ($place['hero_images'] ?? 'non définie'));

// Utiliser les images hero si disponibles
if (!empty($place['hero_images'])) {
    // Nettoyer et filtrer les chemins d'images
    $heroImages = array_filter(array_map('trim', explode(',', $place['hero_images'])));
    error_log("[DEBUG PLACE.PHP] Images hero après explode: " . print_r($heroImages, true));
    
    // Tableau pour stocker les chemins d'images valides
    $validHeroImages = [];
    
    // Ajouter le chemin complet pour chaque image hero et vérifier qu'elle existe
    foreach ($heroImages as $img) {
        $originalPath = $img;
        $finalPath = $img;
        
        // Vérifier si l'image commence déjà par http:// ou https:// (URL complète)
        if (!preg_match('/^(http|https):\/\//i', $finalPath)) {
            // Vérifier si l'image commence déjà par un slash
            if (substr($finalPath, 0, 1) !== '/') {
                $finalPath = '/' . $finalPath;
            }
        }
        
        // Vérifier si le fichier existe physiquement
        // Essayer d'abord avec le chemin complet depuis la racine du site
        $physicalPath = $_SERVER['DOCUMENT_ROOT'] . $finalPath;
        $fileExists = file_exists($physicalPath);
        
        // Si le fichier n'existe pas, essayer avec le chemin relatif depuis le répertoire du projet
        if (!$fileExists) {
            $projectPath = dirname(__FILE__);
            $physicalPathProject = $projectPath . '/' . ltrim($finalPath, '/');
            $fileExists = file_exists($physicalPathProject);
            
            if ($fileExists) {
                // Si on trouve le fichier avec ce chemin, mettre à jour le chemin physique
                $physicalPath = $physicalPathProject;
            }
        }
        
        error_log("[DEBUG PLACE.PHP] Vérification de l'image: " . $finalPath);
        error_log("[DEBUG PLACE.PHP] Chemin physique: " . $physicalPath);
        error_log("[DEBUG PLACE.PHP] Fichier existe: " . ($fileExists ? 'Oui' : 'Non'));
        
        // Ajouter l'image au tableau uniquement si elle existe ou si c'est une URL externe
        if ($fileExists || preg_match('/^(http|https):\/\//i', $finalPath)) {
            $validHeroImages[] = $finalPath;
            error_log("[DEBUG PLACE.PHP] Image valide ajoutée: " . $finalPath);
        } else {
            error_log("[DEBUG PLACE.PHP] Image ignorée car introuvable: " . $finalPath);
            
            // Essayer avec plusieurs chemins alternatifs
            $altPaths = [
                // Chemin sans le premier slash
                substr($finalPath, 1),
                // Chemin relatif au répertoire du projet
                'project 10/' . ltrim($finalPath, '/'),
                // Chemin direct depuis le répertoire du projet
                ltrim($finalPath, '/')
            ];
            
            foreach ($altPaths as $altPath) {
                // Essayer avec le chemin depuis la racine du document
                $altPhysicalPath1 = $_SERVER['DOCUMENT_ROOT'] . '/' . $altPath;
                // Essayer avec le chemin depuis le répertoire du projet
                $altPhysicalPath2 = dirname(__FILE__) . '/' . $altPath;
                
                error_log("[DEBUG PLACE.PHP] Essai avec chemin alternatif: " . $altPath);
                error_log("[DEBUG PLACE.PHP] Chemin physique alternatif 1: " . $altPhysicalPath1);
                error_log("[DEBUG PLACE.PHP] Chemin physique alternatif 2: " . $altPhysicalPath2);
                
                if (file_exists($altPhysicalPath1)) {
                    $validHeroImages[] = '/' . $altPath;
                    error_log("[DEBUG PLACE.PHP] Image trouvée avec chemin alternatif 1: " . $altPath);
                    break;
                } else if (file_exists($altPhysicalPath2)) {
                    $validHeroImages[] = '/' . $altPath;
                    error_log("[DEBUG PLACE.PHP] Image trouvée avec chemin alternatif 2: " . $altPath);
                    break;
                }
            }
        }
    }
    
    // Utiliser uniquement les images valides
    if (!empty($validHeroImages)) {
        $sliderImages = array_merge($sliderImages, $validHeroImages);
        error_log("[DEBUG PLACE.PHP] SliderImages après fusion: " . print_r($sliderImages, true));
    } else {
        error_log("[DEBUG PLACE.PHP] Aucune image hero valide trouvée");
    }
} 
// Fallback aux anciennes images si hero_images est vide
else if (!empty($place['photo'])) {
    $sliderImages[] = $place['photo'];
}
if (empty($sliderImages) && !empty($place['gallery'])) {
    $gallery = array_filter(array_map('trim', explode(',', $place['gallery'])));
    $sliderImages = array_merge($sliderImages, $gallery);
}
// Fallback to a default image if no images are specified
if (empty($sliderImages)) {
    $sliderImages[] = 'images/default_place_hero.jpg'; // <-- REMPLACEZ ceci par le chemin de votre image de hero par défaut pour les lieux sans images spécifiques
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($place['nom']) ?> - VMaroc</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/montserrat-font.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/place.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Header Style pour place.php */
        header.hero-header {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            z-index: 10;
            background: transparent;
            box-shadow: none;
        }
        
        .header-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 1440px;
            margin: 0 auto;
            padding: 0 3vw;
            height: 84px;
            background: transparent;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .logo-img {
            height: 72px !important;
            width: auto;
            display: block;
        }
        
        .nav-menu {
            display: flex;
            gap: 38px;
            list-style: none;
            margin: 0 0 0 48px;
            padding: 0;
            flex: 1;
            justify-content: center;
        }
        
        .nav-menu li a {
            color: #fff;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.05rem;
            position: relative;
            padding-bottom: 5px;
            text-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        
        .nav-menu li a:after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: 0;
            left: 0;
            background-color: #e9cba7;
            transition: width 0.3s;
        }
        
        .nav-menu li a:hover:after {
            width: 100%;
        }
        
        .nav-buttons {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .nav-btn {
            padding: 8px 20px;
            border-radius: 4px;
            font-weight: 500;
            font-size: 0.95rem;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .btn-outline {
            background: rgba(34, 34, 34, 0.7);
            color: #fff;
            border: none;
        }
        
        .btn-solid {
            background: #e9cba7;
            color: #222;
            border: none;
            font-weight: 600;
        }
        
        .btn-outline:hover {
            background: rgba(34, 34, 34, 0.9);
        }
        
        .btn-solid:hover {
            background: #d9b897;
            transform: translateY(-2px);
        }
        
        .place-hero-pro {
            position: relative;
            height: 500px; /* Augmentation de la hauteur pour plus d'espace */
            margin-top: 0; /* Pas besoin de marge négative */
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0 0 30px 30px;
            box-shadow: 0 8px 32px rgba(44,62,80,0.12);
            overflow: hidden; /* Important to contain arrow positioning */
        }
        .hero-background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            transition: background-image 1.5s ease-in-out;
            z-index: 1; /* Below content and arrows */
        }
        .place-hero-pro .hero-content {
            position: relative;
            z-index: 2;
            padding-top: 84px; /* Décaler le contenu vers le bas pour éviter le chevauchement avec le header */
            color: #fff;
            text-align: center;
            width: 100%;
        }
        .place-hero-pro h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2.8rem;
            margin-bottom: 10px;
            letter-spacing: 1px;
            text-shadow: 0 4px 24px rgba(0,0,0,0.28), 0 1.5px 6px rgba(0,0,0,0.18);
        }
        .place-hero-pro p {
            font-size: 1.2rem;
            opacity: 0.9;
            text-shadow: 0 4px 24px rgba(0,0,0,0.28), 0 1.5px 6px rgba(0,0,0,0.18);
        }
        .hero-arrow {
            position: absolute;
            top: 60%; /* Ajusté pour tenir compte du header et du nouveau padding */
            transform: translateY(-50%);
            background: rgba(0, 0, 0, 0.5);
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            font-size: 1.2rem;
            cursor: pointer;
            z-index: 3;
            transition: background 0.2s;
        }
        .hero-arrow:hover {
            background: rgba(0, 0, 0, 0.7);
        }
        .hero-arrow.prev {
            left: 20px;
        }
        .hero-arrow.next {
            right: 20px;
        }
        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(rgba(30,30,30,0.5),rgba(30,30,30,0.5));
            z-index: 1;
        }
        .place-details-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 40px;
            margin-top: 40px;
        }
        .place-main {
            flex:2;
        }
        .place-infos {
            flex:1;
            background: #f8f9fa;
            border-radius: 14px;
            padding: 28px 24px;
            box-shadow: 0 2px 10px rgba(44,62,80,0.07);
            min-width: 260px;
            max-width: 340px;
        }
        .place-infos div {
            margin-bottom: 22px;
        }
        .place-infos strong {
            margin-left: 8px;
        }
        .btn-back {
            display: inline-block;
            margin: 30px 0 0 0;
            background: var(--secondary-color);
            color: #fff;
            border-radius: 30px;
            padding: 10px 28px;
            font-weight: 500;
            text-decoration: none;
            transition: background 0.2s;
        }
        .btn-back:hover {
            background: #c0392b;
        }
        /* Sections supplémentaires */
        .section-block {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(44,62,80,0.08);
            padding: 30px;
            margin-bottom: 30px;
        }
        .section-block h2,
        .section-block h3 {
            font-family: 'Playfair Display', serif;
            color: #2D2926;
            margin-bottom: 20px;
        }
        .section-block h2 {
            font-size: 1.6rem;
        }
        .section-block h3 {
            font-size: 1.4rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .equip-list, .boutique-list {
            display: flex;
            flex-wrap: wrap;
            gap: 18px 30px;
            list-style: none;
            margin: 0;
            padding: 0;
        }
        .equip-list li, .boutique-list li {
            font-size: 1.05rem;
            color: #444;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .map-block {
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(44,62,80,0.07);
            margin-bottom: 32px;
        }
        /* Avis */
        .reviews-list {
            margin-top: 30px;
        }
        .review-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(44,62,80,0.07);
            margin-bottom: 22px;
            padding: 22px 20px 16px 20px;
        }
        .review-header {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .profile-avatar {
            width: 45px;
            height: 45px;
            background: var(--primary-color);
            color: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            font-weight: 600;
        }
        .review-header strong {
            font-size: 1.1rem;
        }
        .review-header .review-date {
            font-size: 0.9rem;
            color: #888;
        }
        .review-header .review-rating {
            margin-left: auto;
        }
        .review-header .review-rating i {
            color: #f1c40f;
        }
        .review-content {
            margin-top: 10px;
            color: #444;
        }
        @media (max-width: 900px) {
            .place-details-grid { flex-direction: column; gap: 20px; }
            .place-infos { max-width: 100%; }
        }
        .hero {
            min-height: 60vh;
            height: auto !important;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            margin-bottom: 40px;
            overflow: hidden;
        }

        .hero-background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            transition: background-image 1.5s ease-in-out;
            z-index: 1;
        }

        .hero-overlay {
             position: absolute;
             top: 0;
             left: 0;
             width: 100%;
             height: 100%;
             background: rgba(0, 0, 0, 0); /* Reduced opacity to make image brighter */
             z-index: 2;
        }

        .hero-content {
            text-align: center;
            width: 100%;
            color: #fff;
            z-index: 3;
        }

        .hero-content h1 {
             font-size: 3.5rem;
             margin-bottom: 15px;
             letter-spacing: 1px;
             text-shadow: 0 4px 24px rgba(0,0,0,0.28), 0 1.5px 6px rgba(0,0,0,0.18);
        }

        .hero-content p {
            font-size: 1.3rem;
            opacity: 0.9;
            text-shadow: 0 4px 24px rgba(0,0,0,0.28), 0 1.5px 6px rgba(0,0,0,0.18);
        }

        /* Styles for header when integrated in hero */
        header {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 100;
            background: transparent !important;
            box-shadow: none !important;
        }

        /* Ensure header container content is visible */
        header .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
        }

        /* Adjust colors for header elements on dark background */
         header .nav-menu li a, header .auth-buttons a {
            color: #fff !important;
            transition: color 0.2s, border-bottom 0.2s;
         }

         header .nav-menu li a:hover, header .nav-menu li a.active {
             color: #bfa14a !important;
             border-bottom-color: #f3e9d1;
         }

        header .btn-outline {
            border-color: #bfa14a !important;
            color: #fff !important;
        }
        header .btn-outline:hover {
             background: #bfa14a !important;
             color: #fff !important;
        }

        header .btn-primary {
             background: #bfa14a !important;
             color: #fff !important;
        }
        header .btn-primary:hover {
             background: #8B7355 !important;
        }

        /* Styles for the slider */
        .hero-slider {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1; /* Below overlay and content */
        }

        .hero-slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            opacity: 0; /* Start hidden */
            transition: opacity 1.5s ease-in-out; /* Fade transition */
        }

        .hero-slide.active {
            opacity: 1; /* Currently visible slide */
        }

        /* Styles for navigation arrows */
         .hero-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            color: #fff;
            font-size: 2.5rem;
            cursor: pointer;
            z-index: 4; /* Above content and overlay */
            padding: 20px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.4);
            transition: color 0.2s, text-shadow 0.2s;
         }

         .hero-arrow:hover {
            color: #bfa14a; /* Gold color on hover */
            text-shadow: 0 2px 15px rgba(0,0,0,0.6);
         }

         .left-arrow {
            left: 20px;
         }

         .right-arrow {
            right: 20px;
         }

        @media (max-width: 768px) {
            .hero-arrow {
                font-size: 1.8rem;
                padding: 10px;
            }
            .left-arrow {
                left: 10px;
            }
            .right-arrow {
                right: 10px;
            }
        }

        /* Styles for the two-column layout */
        .place-two-column-layout {
            display: grid;
            grid-template-columns: 2fr 1fr; /* Left column wider than right */
            gap: 40px; /* Space between columns */
            align-items: start; /* Align items to the top */
            margin-top: 40px; /* Space above the layout */
        }

        .place-two-column-layout .left-column {
            /* Styles for the left column (introduction and reviews) */
        }

        .place-two-column-layout .left-column .section-block:not(:first-child) {
            margin-top: 30px; /* Add space between section blocks in the left column */
        }

        /* Specific styles for the Reviews section block */
        .place-two-column-layout .left-column .section-block h2 {
            font-size: 1.4rem; /* Adjust title size for reviews */
            margin-bottom: 20px; /* Space below title */
            color: #2D2926; /* Darker title color */
            font-family: 'Playfair Display', serif;
        }

        /* Styles for the review form */
        .review-form {
            background-color: #f8f9fa; /* Light background */
            padding: 25px; /* Increased padding */
            border-radius: 12px; /* Slightly larger border-radius */
            margin-bottom: 25px; /* Space below form */
            box-shadow: 0 2px 10px rgba(0,0,0,0.05); /* Subtle shadow */
        }

        .review-form h3 {
            font-size: 1.2rem; /* Title for the form */
            margin-bottom: 15px;
            color: #2D2926;
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
        }

        .review-form label {
            display: block;
            margin-bottom: 8px; /* Space below label */
            font-weight: 500;
            color: #555;
            font-size: 1rem;
        }

        .review-form textarea {
            width: 100%;
            padding: 12px; /* Increased padding */
            border: 1px solid #ddd;
            border-radius: 8px; /* Rounded corners */
            box-sizing: border-box; /* Include padding in width */
            font-size: 1rem;
            line-height: 1.5;
            resize: vertical; /* Allow vertical resizing */
        }

        .review-form .btn-primary {
            padding: 10px 24px; /* Adjusted padding */
            font-size: 1rem;
            margin-top: 15px;
        }

        /* Styles for the review list */
        .reviews-list {
            margin-top: 20px; /* Space above the list */
            display: flex;
            flex-direction: column;
            gap: 20px; /* Space between review cards */
        }

        /* Styles for individual review cards */
        .review-card {
            background-color: white;
            border-radius: 10px; /* Rounded corners */
            padding: 20px; /* Padding inside the card */
            box-shadow: 0 2px 8px rgba(0,0,0,0.05); /* Subtle shadow */
        }

        .review-card .review-header {
            display: flex;
            align-items: center;
            justify-content: space-between; /* Space out user/date and rating */
            margin-bottom: 10px;
        }

        .review-card .review-header > div strong {
             font-weight: 600;
             color: #333;
             display: block;
             font-size: 1.05rem;
        }

        .review-card .review-header > div span {
            color: #888;
            font-size: 0.9rem;
        }

        .review-card .review-header .fa-star, .review-card .review-header .fa-star-half-alt {
            color: #f1c40f; /* Star color */
            font-size: 1rem; /* Adjust star size */
        }

        .review-card .review-content {
            color: #444;
            line-height: 1.6; /* Improved line height */
            font-size: 1rem;
        }

        .login-prompt {
             background-color: #f8f9fa;
             padding: 20px;
             border-radius: 10px;
             margin-bottom: 25px;
             text-align: center;
             box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .login-prompt p {
            margin-bottom: 15px;
            font-size: 1rem;
            color: #555;
        }

        .login-prompt .btn-outline {
             padding: 10px 24px;
             font-size: 1rem;
        }

        /* Styles for the right column sections */
        .place-two-column-layout .right-column .section-block h3 {
            font-size: 1.4rem; /* Consistent title size with left column */
            margin-bottom: 20px; /* Space below title */
            color: #2D2926; /* Darker title color */
            font-family: 'Playfair Display', serif;
        }

        .place-two-column-layout .right-column .services-grid {
             display: grid;
             grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); /* Ensure responsiveness */
             gap: 15px; /* Space between items */
        }

         .place-two-column-layout .right-column .service-item {
             background-color: #f0f0f0; /* Light background for items */
             border-radius: 8px; /* Rounded corners for items */
             padding: 12px 15px; /* Padding inside items */
             display: flex;
             align-items: center;
             gap: 10px; /* Space between icon and text */
             font-size: 1rem;
             color: #444;
             box-shadow: 0 1px 5px rgba(0,0,0,0.03); /* Very subtle shadow for items */
         }

         .place-two-column-layout .right-column .service-item i {
             color: #bfa14a; /* Gold color for icons */
             font-size: 1.1rem; /* Slightly larger icons */
         }

         /* Styles for the Location section map/address */
         .place-two-column-layout .right-column .section-block iframe,
         .place-two-column-layout .right-column .section-block p.map-fallback {
             border-radius: 8px; /* Rounded corners for map/fallback */
             overflow: hidden; /* Ensures border-radius is applied */
             margin-bottom: 0; /* Remove default paragraph margin */
         }

         .place-two-column-layout .right-column .section-block p.map-fallback i {
             color: #b48a3c; /* Pin icon color */
             margin-right: 8px; /* Space between pin and address */
         }

        .place-two-column-layout .right-column {
             /* Styles for the right column (info and services) */
        }

        /* Responsive styles: stack columns on smaller screens */
        @media (max-width: 992px) { /* Adjust breakpoint as needed */
            .place-two-column-layout {
                grid-template-columns: 1fr; /* Stack columns */
                gap: 30px; /* Adjust gap for stacked layout */
            }

             /* On stacked layout, add space between section blocks in the right column */
             .place-two-column-layout .right-column .section-block:not(:first-child) {
                  margin-top: 30px;
             }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include 'includes/header.php'; ?>
    
    <!-- HERO FULL WIDTH WITH DYNAMIC BACKGROUND SLIDER AND CONTENT -->
    <?php if ($place): ?>
    <section class="place-hero-pro">
        <div class="hero-background"></div>
        <div class="hero-overlay"></div>
        <button class="hero-arrow prev" onclick="prevSlide()"><i class="fas fa-chevron-left"></i></button>
        <button class="hero-arrow next" onclick="nextSlide()"><i class="fas fa-chevron-right"></i></button>
        <div class="hero-content">
            <h1><?= htmlspecialchars($place['nom'] ?? 'Lieu') ?></h1>
            <p><?= htmlspecialchars($place['description'] ?? '') ?></p>
        </div>
    </section>
    <?php endif; ?>

    <main style="margin-top: 0px; background: #f8f6f3; min-height: 100vh;">
        <div class="container" style="max-width: 1200px;">
            
            <!-- New Two-Column Layout Container -->
            <div class="place-two-column-layout">

                <!-- Left Column: Introduction -->
                <div class="left-column">
                    <!-- Added place introduction section -->
                    <?php if (!empty($place['description'])): ?>
                    <div class="section-block" style="padding: 30px; margin-bottom: 30px; margin-top: 30px;">
                        <h3 style="font-size: 1.4rem; margin-bottom: 18px; color: #2D2926; font-family: 'Playfair Display', serif;">À propos de <?= htmlspecialchars($place['nom'] ?? 'ce lieu') ?></h3>
                        <p style="font-size: 1.05rem; line-height: 1.6; color: #444;"><?= nl2br(htmlspecialchars($place['description'])) ?></p>
                    </div>
                    <?php endif; ?>

                    <!-- Avis Section (moved here) -->
                     <div class="section-block" style="padding: 30px; margin-bottom: 30px;">
                         <h2>Avis des visiteurs</h2>
                         
                         <?php if (isset($success_message)): ?>
                             <div class="alert alert-success" style="background-color: #d4edda; color: #155724; padding: 12px; border-radius: 5px; margin-bottom: 20px;">
                                 <?= htmlspecialchars($success_message) ?>
                             </div>
                         <?php endif; ?>
                         
                         <?php if (isset($error_message)): ?>
                             <div class="alert alert-danger" style="background-color: #f8d7da; color: #721c24; padding: 12px; border-radius: 5px; margin-bottom: 20px;">
                                 <?= htmlspecialchars($error_message) ?>
                             </div>
                         <?php endif; ?>
                         
                         <!-- Formulaire d'ajout d'avis -->
                         <?php if (isset($_SESSION['user_id'])): ?>
                             <div class="review-form" style="background-color: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 30px;">
                                 <h3 style="font-size: 1.2rem; margin-bottom: 15px;"><?= $userHasReview ? 'Modifier votre avis' : 'Ajouter votre avis' ?></h3>
                                 <form method="post" action="place.php?id=<?= $placeId ?>">
                                     <input type="hidden" name="add_review" value="1">
                                     <div style="margin-bottom: 15px;">
                                         <label for="rating" style="display: block; margin-bottom: 5px; font-weight: 500;">Votre note :</label>
                                         <div class="rating-select" style="display: flex; gap: 10px;">
                                             <?php for($i=1; $i<=5; $i++): ?>
                                                 <label style="cursor: pointer;">
                                                     <input type="radio" name="rating" value="<?= $i ?>" <?= ($userHasReview && $userReview['rating'] == $i) ? 'checked' : '' ?> style="display: none;">
                                                     <i class="fa fa-star" style="font-size: 24px; color: <?= ($userHasReview && $userReview['rating'] >= $i) ? '#f1c40f' : '#ccc' ?>;"></i>
                                                 </label>
                                             <?php endfor; ?>
                                         </div>
                                     </div>
                                     <div style="margin-bottom: 15px;">
                                         <label for="commentaire" style="display: block; margin-bottom: 5px; font-weight: 500;">Votre commentaire :</label>
                                         <textarea name="commentaire" id="commentaire" rows="4" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;"><?= $userHasReview ? htmlspecialchars($userReview['commentaire']) : '' ?></textarea>
                                     </div>
                                     <button type="submit" class="btn-primary" style="padding: 10px 20px;">
                                         <?= $userHasReview ? 'Modifier mon avis' : 'Publier mon avis' ?>
                                     </button>
                                 </form>
                             </div>
                             
                             <script>
                             // Existing review star logic (keep this)
                             document.addEventListener('DOMContentLoaded', function() {
                                 const stars = document.querySelectorAll('.rating-select label');
                                 const userRating = <?= $userHasReview ? $userReview['rating'] : 0 ?>;

                                 stars.forEach((star, index) => {
                                      star.addEventListener('mouseover', function() {
                                         for (let i = 0; i <= index; i++) {
                                             stars[i].querySelector('i').style.color = '#f1c40f';
                                         }
                                         for (let i = index + 1; i < stars.length; i++) {
                                             stars[i].querySelector('i').style.color = '#ccc';
                                         }
                                     });
                                     
                                     star.addEventListener('click', function() {
                                         star.querySelector('input').checked = true;
                                         for (let i = 0; i <= index; i++) {
                                             stars[i].querySelector('i').style.color = '#f1c40f';
                                         }
                                         for (let i = index + 1; i < stars.length; i++) {
                                             stars[i].querySelector('i').style.color = '#ccc';
                                         }
                                     });
                                 });
                                 
                                 const ratingSelect = document.querySelector('.rating-select');
                                 if (ratingSelect) {
                                     ratingSelect.addEventListener('mouseleave', function() {
                                         stars.forEach((star, index) => {
                                             const input = star.querySelector('input');
                                             if (input.checked) {
                                                 for (let i = 0; i <= index; i++) {
                                                     stars[i].querySelector('i').style.color = '#f1c40f';
                                                 }
                                                 for (let i = index + 1; i < stars.length; i++) {
                                                     stars[i].querySelector('i').style.color = '#ccc';
                                                 }
                                             } else {
                                                 if (index < userRating) {
                                                     star.querySelector('i').style.color = '#f1c40f';
                                                 } else {
                                                     star.querySelector('i').style.color = '#ccc';
                                                 }
                                             }
                                         });
                                     });
                                 }
                             });
                             </script>
                         <?php else: ?>
                             <div class="login-prompt" style="background-color: #f8f9fa; padding: 15px; border-radius: 10px; margin-bottom: 20px; text-align: center;">
                                 <p style="margin-bottom: 10px;">Connectez-vous pour laisser votre avis sur ce lieu.</p>
                                 <a href="login.php?redirect=place.php?id=<?= $placeId ?>" class="btn-outline" style="display: inline-block; padding: 8px 20px;">Se connecter</a>
                             </div>
                         <?php endif; ?>
                         
                         <!-- Liste des avis -->
                         <h3 style="font-size: 1.2rem; margin: 20px 0 15px 0;"><?= count($reviews) ?> avis</h3>
                         
                         <?php if (!empty($reviews)): ?>
                             <div class="reviews-list" style="display: flex; flex-direction: column; gap: 20px;">
                                 <?php foreach ($reviews as $review): ?>
                                     <div class="review-card" style="background-color: white; border-radius: 10px; padding: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                                         <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
                                             <div>
                                                 <span style="font-weight: 600; color: #333; display: block;"><?= htmlspecialchars($review['user_nom']) ?></span>
                                                 <span style="color: #888; font-size: 0.9rem;"><?= date('j F Y', strtotime($review['date_creation'])) ?></span>
                                             </div>
                                             <div>
                                                 <?php for($i=1; $i<=5; $i++): ?>
                                                     <i class="fa fa-star" style="color: <?= $i <= $review['rating'] ? '#f1c40f' : '#ccc' ?>;"></i>
                                                 <?php endfor; ?>
                                             </div>
                                         </div>
                                         <?php if (!empty($review['commentaire'])): ?>
                                             <div style="color: #444; line-height: 1.5;">
                                                 <?= nl2br(htmlspecialchars($review['commentaire'])) ?>
                                             </div>
                                         <?php endif; ?>
                                     </div>
                                 <?php endforeach; ?>
                             </div>
                         <?php else: ?>
                             <p style="color: #888;">Aucun avis pour ce lieu. Soyez le premier à donner votre note !</p>
                         <?php endif; ?>
                     </div>

                    <!-- Any other main content for the left column could go here -->
                </div>

                <!-- Right Column: Info and Services -->
                <div class="right-column" style="margin-top: 30px;">
                    <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1): ?>
                    <div style="text-align: right; margin-bottom: 15px;">
                        <a href="admin_place.php?id=<?= $placeId ?>" class="btn-outline" style="display: inline-flex; align-items: center; gap: 8px; font-size: 0.9rem; padding: 8px 15px;">
                            <i class="fas fa-edit"></i> Modifier les équipements et services
                        </a>
                    </div>
                    <?php endif; ?>

                    <!-- Equipements & Services -->
                    <div class="section-block" style="padding: 30px; margin-bottom: 30px;">
                        <h3 style="font-size: 1.3rem; margin-bottom: 18px; display: flex; align-items: center; gap: 10px; color: var(--primary-color);"><i class="fas fa-concierge-bell" style="color: #bfa14a;"></i> Équipements & Services</h3>
                        <div class="services-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px;">
                            <?php if (!empty($equipements[0])): ?>
                                <?php foreach ($equipements as $eq): ?>
                                    <div class="service-item" style="display: flex; align-items: center; gap: 8px; font-size: 1rem; color: #444;"><i class="fas fa-check-circle" style="color: #bfa14a;"></i> <span><?= htmlspecialchars($eq) ?></span></div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="service-item" style="display: flex; align-items: center; gap: 8px; font-size: 1rem; color: #777; grid-column: 1/-1;">
                                    <i class="fas fa-info-circle" style="color: #999;"></i> 
                                    <span>Les équipements et services de ce lieu seront bientôt disponibles.</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Boutiques & Services Commerciaux -->
                    <div class="section-block" style="padding: 30px; margin-bottom: 30px;">
                        <h3 style="font-size: 1.3rem; margin-bottom: 18px; display: flex; align-items: center; gap: 10px; color: var(--primary-color);"><i class="fas fa-shopping-bag" style="color: #bfa14a;"></i> Boutiques & Services Commerciaux</h3>
                        <div class="services-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px;">
                            <?php if (!empty($boutiques[0])): ?>
                                <?php foreach ($boutiques as $boutique): ?>
                                    <div class="service-item" style="display: flex; align-items: center; gap: 8px; font-size: 1rem; color: #444;"><i class="fas fa-store" style="color: #bfa14a;"></i> <span><?= htmlspecialchars($boutique) ?></span></div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="service-item" style="display: flex; align-items: center; gap: 8px; font-size: 1rem; color: #777; grid-column: 1/-1;">
                                    <i class="fas fa-info-circle" style="color: #999;"></i> 
                                    <span>Les boutiques et services commerciaux de ce lieu seront bientôt disponibles.</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                     <!-- Activités à faire -->
                    <?php if (!empty($place['activites'])): ?>
                    <div class="section-block" style="padding: 30px; margin-bottom: 30px;">
                         <h3 style="font-size: 1.3rem; margin-bottom: 18px; display: flex; align-items: center; gap: 10px; color: var(--primary-color);">Activités à faire</h3>
                         <div class="services-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px;">
                             <?php foreach (explode(',', $place['activites']) as $act): ?>
                                 <div class="service-item" style="display: flex; align-items: center; gap: 8px; font-size: 1rem; color: #444;"><i class="fas fa-running" style="color: #bfa14a;"></i> <span><?= htmlspecialchars(trim($act)) ?></span></div>
                             <?php endforeach; ?>
                         </div>
                    </div>
                    <?php endif; ?>

                    <!-- Localisation -->
                     <div class="section-block" style="padding: 30px; margin-bottom: 30px;">
                         <h3 style="font-size: 1.3rem; margin-bottom: 18px; display: flex; align-items: center; gap: 10px; color: var(--primary-color);">
                             <i class="fas fa-map-marker-alt" style="color:#b48a3c;"></i>Localisation
                         </h3>
                         <div style="margin-bottom:1rem;">
                             <?php
                             // Afficher la carte pour tous les lieux, en utilisant soit les coordonnées existantes, soit l'adresse
                             $mapQuery = '';
                             
                             if (!empty($place['latitude']) && !empty($place['longitude'])) {
                                 // Utiliser les coordonnées existantes
                                 $mapQuery = $place['latitude'] . ',' . $place['longitude'];
                             } elseif (!empty($place['adresse'])) {
                                 // Utiliser l'adresse pour la carte
                                 $address = $place['adresse'];
                                 if (!empty($place['ville_nom'])) {
                                     $address .= ', ' . $place['ville_nom'];
                                 }
                                 $address .= ', Maroc';
                                 $mapQuery = urlencode($address);
                             } else {
                                 // Fallback sur le nom du lieu et la ville
                                 $mapQuery = urlencode($place['nom'] . ', ' . $place['ville_nom'] . ', Maroc');
                             }
                             ?>
                             
                             <!-- Afficher la carte Google Maps pour tous les lieux -->
                             <iframe width="100%" height="260" frameborder="0" style="border:0; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.1);" allowfullscreen
                                 src="https://www.google.com/maps?q=<?= $mapQuery ?>&hl=fr&z=15&output=embed">
                             </iframe>
                             
                             <!-- Afficher l'adresse textuelle en complément -->
                             <p style="margin-top:15px; color: #444; font-weight: 500;">
                                 <i class="fas fa-map-pin" style="color:#b48a3c; margin-right: 8px;"></i> 
                                 <?php
                                 if (!empty($place['adresse'])) {
                                     echo htmlspecialchars($place['adresse']);
                                     if (!empty($place['ville_nom'])) {
                                         echo ', ' . htmlspecialchars($place['ville_nom']);
                                     }
                                 } else {
                                     echo htmlspecialchars($place['nom'] . ', ' . $place['ville_nom']);
                                 }
                                 ?>
                             </p>
                         </div>
                     </div>

                    <!-- Any other info sections for the right column could go here -->

                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <img src="https://i.postimg.cc/g07GgLp5/VMaroc-logo-trf.png" alt="VMaroc Logo" class="logo-img" style="height:60px;">
                    <p>Découvrez les merveilles du Maroc avec VMaroc, votre guide de voyage personnalisé.</p>
                    <!-- Social links supprimés -->
                </div>
                <div class="footer-col">
                    <h3>Liens Rapides</h3>
                    <ul>
                        <li><a href="index.php">Accueil</a></li>
                        <li><a href="destinations.php">Destinations</a></li>
                        <li><a href="recommendations.php">Recommendations</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h3>Contact</h3>
                    <p>contact@marocauthentique.com</p>
                    <p>+212 522 123 456</p>
                </div>
            </div>
            <div class="copyright">
                <p style="font-family: 'Montserrat', sans-serif;">© 2025 Maroc Authentique. Tous droits réservés.</p>
                <p style="font-family: 'Montserrat', sans-serif; margin-top: 10px;">
                    <a href="politique-confidentialite.php" style="color: #8B7355; text-decoration: none; font-family: 'Montserrat', sans-serif;">Politique de confidentialité</a> | 
                    <a href="conditions-utilisation.php" style="color: #8B7355; text-decoration: none; font-family: 'Montserrat', sans-serif;">Conditions d'utilisation</a>
                </p>
            </div>
        </div>
    </footer>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Rotation des images du hero
        const sliderImages = <?php echo json_encode($sliderImages ?? []); ?>; // Use dynamic images from PHP
        let currentImageIndex = 0;
        let autoSlideInterval;
        const heroBackground = document.querySelector('.hero-background');

        // Précharger toutes les images pour éviter les problèmes d'affichage
        function preloadImages() {
            if (!sliderImages || sliderImages.length === 0) return;
            
            console.log('Préchargement de ' + sliderImages.length + ' images');
            sliderImages.forEach((src, index) => {
                // S'assurer que le chemin de l'image est correct
                let imagePath = src;
                if (!imagePath.match(/^(http|https):\/\//i) && !imagePath.startsWith('/')) {
                    imagePath = '/' + imagePath;
                }
                
                const img = new Image();
                img.src = imagePath;
                console.log('Préchargement de l\'image ' + index + ': ' + imagePath);
            });
        }
        
        // Fonction pour changer l'image sur l'élément background
        function changeHeroImage(index) {
            if (!heroBackground) {
                console.log('Impossible de changer l\'image: pas de background');
                return;
            }
            
            // Si aucune image n'est disponible, utiliser une image par défaut
            if (sliderImages.length === 0) {
                console.log('Aucune image disponible, utilisation de l\'image par défaut');
                heroBackground.style.backgroundImage = "url('/images/default_place_hero.jpg')";
                return;
            }
            
            currentImageIndex = index;
            
            // S'assurer que le chemin de l'image est correct
            let imagePath = sliderImages[currentImageIndex];
            
            // Vérifier si le chemin commence par http:// ou https://
            if (!imagePath.match(/^(http|https):\/\//i) && !imagePath.startsWith('/')) {
                imagePath = '/' + imagePath;
            }
            
            console.log('Tentative d\'affichage de l\'image hero ' + currentImageIndex + '/' + sliderImages.length + ':', imagePath);
            
            // Créer une nouvelle image pour vérifier qu'elle se charge correctement
            const img = new Image();
            img.onload = function() {
                heroBackground.style.backgroundImage = `url('${imagePath}')`;
                console.log('Image chargée avec succès:', imagePath);
            };
            img.onerror = function() {
                console.error('Erreur de chargement de l\'image:', imagePath);
                
                // Essayer avec un chemin alternatif (sans le premier slash)
                if (imagePath.startsWith('/')) {
                    const altPath = imagePath.substring(1);
                    console.log('Tentative avec chemin alternatif:', altPath);
                    
                    const altImg = new Image();
                    altImg.onload = function() {
                        heroBackground.style.backgroundImage = `url('${altPath}')`;
                        console.log('Image chargée avec succès (chemin alternatif):', altPath);
                    };
                    altImg.onerror = function() {
                        console.error('Erreur de chargement de l\'image (chemin alternatif):', altPath);
                        // Essayer de passer à l'image suivante si celle-ci ne se charge pas
                        if (sliderImages.length > 1) {
                            nextSlide();
                        } else {
                            // Si c'est la seule image et qu'elle ne se charge pas, utiliser l'image par défaut
                            heroBackground.style.backgroundImage = "url('/images/default_place_hero.jpg')";
                        }
                    };
                    altImg.src = altPath;
                } else {
                    // Si l'image ne commence pas par un slash, essayer de passer à l'image suivante
                    if (sliderImages.length > 1) {
                        nextSlide();
                    } else {
                        // Si c'est la seule image et qu'elle ne se charge pas, utiliser l'image par défaut
                        heroBackground.style.backgroundImage = "url('/images/default_place_hero.jpg')";
                    }
                }
            };
            img.src = imagePath;
        }

        // Fonction pour passer à l'image suivante
        function nextSlide() {
            const nextIndex = (currentImageIndex + 1) % sliderImages.length;
            changeHeroImage(nextIndex);
        }

        // Fonction pour passer à l'image précédente
        function prevSlide() {
            const prevIndex = (currentImageIndex - 1 + sliderImages.length) % sliderImages.length;
            changeHeroImage(prevIndex);
        }

        // Démarre la rotation automatique
        function startAutoSlide() {
            stopAutoSlide();
            autoSlideInterval = setInterval(nextSlide, 7000);
        }

        // Arrête la rotation automatique
        function stopAutoSlide() {
            clearInterval(autoSlideInterval);
            autoSlideInterval = null;
        }

        // Initialisation
        if (heroBackground && sliderImages.length > 0) {
            console.log('Initialisation du slider avec ' + sliderImages.length + ' images');
            
            // Précharger toutes les images avant de démarrer le slider
            preloadImages();
            
            // Afficher la première image
            changeHeroImage(0);
            
            // Démarrer la rotation automatique après un délai
            setTimeout(() => {
                startAutoSlide();
            }, 3000);
        } else {
            console.warn('Pas d\'images disponibles pour le slider ou élément hero-background non trouvé');
            console.log('sliderImages:', sliderImages);
            console.log('heroBackground:', heroBackground);
        }

        // Arrêter la rotation quand l'utilisateur quitte la page
        window.addEventListener('beforeunload', function() {
            stopAutoSlide();
        });
    });
    </script>
</body>
</html> 