<?php
// Désactiver l'affichage des erreurs en production
ini_set('display_errors', 0);
error_reporting(0);

session_start();
require_once 'config/database.php';

// Récupérer tous les lieux
$stmt = $pdo->query("SELECT * FROM lieux ORDER BY nom");
$lieux = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupération de l'ID du lieu depuis l'URL
$placeId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
// Vérifier si la colonne url_activités existe
$columnExists = false;
try {
    $checkColumn = $pdo->query("SHOW COLUMNS FROM lieux LIKE 'url_activités'");
    $columnExists = $checkColumn->rowCount() > 0;
} catch (PDOException $e) {
    // Erreur silencieuse en production
}

// Construire la requête en fonction de l'existence de la colonne
$query = "SELECT l.*, v.nom as ville_nom, v.id as ville_id" . ($columnExists ? ", l.url_activités" : "") . " 
          FROM lieux l 
          JOIN villes v ON l.id_ville = v.id 
          WHERE l.id = ?";

// Récupération des informations du lieu (avec nouveaux champs)
$place = $pdo->prepare($query);
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

// Initialiser le tableau des images du slider
$sliderImages = [];

// Si des images hero sont définies, les traiter
if (!empty($place['hero_images'])) {
    $heroImages = array_filter(array_map('trim', explode(',', $place['hero_images'])));

    // Chemin de base pour les images
    $baseImagePath = 'images/';
    $baseImagePath2 = '../images/';

    // Fonction pour vérifier si un fichier existe en tenant compte de la casse
    function fileExistsCaseInsensitive($file) {
        if (file_exists($file)) {
            return true;
        }
        $dir = dirname($file);
        $filename = basename($file);
        if (!is_dir($dir)) {
            return false;
        }
        $files = scandir($dir);
        $lowerFilename = strtolower($filename);
        foreach ($files as $f) {
            if (strtolower($f) === $lowerFilename) {
                return true;
            }
        }
        return false;
    }

    // Parcourir chaque image et vérifier son existence
    $updatedHeroImages = [];
    foreach ($heroImages as $image) {
        $image = trim($image);
        if (empty($image)) continue;

        // Essayer plusieurs chemins possibles
        $finalPath = '';
        $possiblePaths = [
            $image,
            $baseImagePath . $image,
            $baseImagePath2 . $image,
            'images/places/' . $image,
            '../images/places/' . $image,
            'images/lieux/' . $image,
            '../images/lieux/' . $image,
            'images/' . basename($image),
            '../images/' . basename($image),
            'images/Barceló Anfa Casablanca/' . basename($image),
            '../images/Barceló Anfa Casablanca/' . basename($image)
        ];

        foreach ($possiblePaths as $testPath) {
            $physicalPath = $_SERVER['DOCUMENT_ROOT'] . '/project10/' . $testPath;
            if (fileExistsCaseInsensitive($physicalPath)) {
                $finalPath = '/project10/' . $testPath;
                $updatedHeroImages[] = $testPath;
                break;
            }
        }

        if (!empty($finalPath)) {
            $sliderImages[] = $finalPath;
        }
    }

    // Mettre à jour la base de données si des images ont été supprimées ou modifiées
    if (count($updatedHeroImages) !== count($heroImages)) {
        $newHeroImages = implode(',', array_unique($updatedHeroImages));
        $stmt = $pdo->prepare("UPDATE lieux SET hero_images = ? WHERE id = ?");
        $stmt->execute([$newHeroImages, $placeId]);
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
            transition: opacity 0.3s ease-in-out;
            z-index: 1;
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
            background: rgba(0, 0, 0, 0.3); /* Ajusté pour une meilleure visibilité */
            z-index: 2;
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
            padding: 10px 20px;
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
            height: 600px; /* Hauteur fixe pour assurer la visibilité */
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            margin-bottom: 40px;
            overflow: hidden;
            background-color: #f0f0f0; /* Couleur de fond par défaut */
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
            transition: opacity 0.3s ease-in-out;
            z-index: 1;
        }

        .hero-overlay {
             position: absolute;
             top: 0;
             left: 0;
             width: 100%;
             height: 100%;
             background: rgba(0, 0, 0, 0.3); /* Ajusté pour une meilleure visibilité */
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
        .hero-slider-simple {
            position: relative;
            width: 100%;
            height: 420px;
            overflow: hidden;
            border-radius: 0 0 30px 30px;
            background: #eee;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .hero-slide-simple {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            opacity: 0;
            transition: opacity 0.7s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .hero-slide-simple.active {
            opacity: 1;
            z-index: 2;
        }
        .hero-slide-simple img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 0 0 30px 30px;
        }
        .hero-arrow-simple {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(34,34,34,0.5);
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            font-size: 1.2rem;
            cursor: pointer;
            z-index: 3;
            transition: background 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .hero-arrow-simple.prev { left: 18px; }
        .hero-arrow-simple.next { right: 18px; }
        .hero-arrow-simple:hover { background: #bfa14a; color: #fff; }
        .hero-dots-simple {
            position: absolute;
            bottom: 18px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 10px;
            z-index: 4;
        }
        .hero-dot-simple {
            width: 12px; height: 12px;
            border-radius: 50%;
            background: #fff;
            opacity: 0.6;
            cursor: pointer;
            border: 2px solid #bfa14a;
            transition: background 0.2s, opacity 0.2s;
        }
        .hero-dot-simple.active {
            background: #bfa14a;
            opacity: 1;
        }
        @media (max-width: 900px) {
            .hero-slider-simple, .hero-slide-simple img { height: 260px; }
        }
        @media (max-width: 600px) {
            .hero-slider-simple, .hero-slide-simple img { height: 180px; }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="hero-header">
        <div class="header-container">
            <a href="index.php" class="logo">
                <img src="https://i.postimg.cc/g07GgLp5/VMaroc-logo-trf.png" alt="VMaroc Logo" class="logo-img">
            </a>
            
            <ul class="nav-menu">
                <li><a href="index.php">Accueil</a></li>
                <li><a href="destinations.php">Destinations</a></li>
                <li><a href="recommendations.php">Recommandations</a></li>
            </ul>
            
            <div class="nav-buttons">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                        <a href="pages/admin-panel.php" class="nav-btn btn-outline">Panneau Admin</a>
                    <?php else: ?>
                        <a href="profile.php" class="nav-btn btn-outline">Mon Profil</a>
                    <?php endif; ?>
                    <a href="logout.php" class="nav-btn btn-solid">Déconnexion</a>
                <?php else: ?>
                    <a href="login.php" class="nav-btn btn-outline">Connexion</a>
                    <a href="register.php" class="nav-btn btn-solid"><i class="fas fa-user-plus" style="margin-right: 6px;"></i>Inscription</a>
                <?php endif; ?>
            </div>
        </div>
    </header>
    
    <!-- HERO FULL WIDTH WITH DYNAMIC BACKGROUND SLIDER AND CONTENT -->
    <?php if ($place): ?>
    <section class="hero" style="min-height:60vh;position:relative;overflow:hidden;">
        <div class="hero-background" id="heroBackground"></div>
        <button class="hero-arrow left-arrow" id="prevHero" aria-label="Image précédente" style="position:absolute;left:24px;top:50%;transform:translateY(-50%);z-index:10;background:rgba(0,0,0,0.3);border:none;border-radius:50%;color:#fff;font-size:2.2rem;width:48px;height:48px;display:flex;align-items:center;justify-content:center;cursor:pointer;"><i class="fas fa-chevron-left"></i></button>
        <button class="hero-arrow right-arrow" id="nextHero" aria-label="Image suivante" style="position:absolute;right:24px;top:50%;transform:translateY(-50%);z-index:10;background:rgba(0,0,0,0.3);border:none;border-radius:50%;color:#fff;font-size:2.2rem;width:48px;height:48px;display:flex;align-items:center;justify-content:center;cursor:pointer;"><i class="fas fa-chevron-right"></i></button>
        <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1): ?>
        <button class="delete-image-btn" id="deleteImageBtn" style="position:absolute;top:20px;right:20px;z-index:10;background:rgba(220,53,69,0.8);color:#fff;border:none;border-radius:50%;width:40px;height:40px;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:1.2rem;"><i class="fas fa-trash"></i></button>
        <?php endif; ?>
        <div class="hero-overlay"></div>
        <div class="hero-content premium">
            <h1><?= htmlspecialchars($place['nom'] ?? 'Lieu') ?></h1>
            <div class="hero-sub"><?= htmlspecialchars($place['description'] ?? '') ?></div>
        </div>
    </section>
    <script>
    const heroImages = <?= json_encode($sliderImages) ?>;
    let currentHeroIdx = 0;
    const heroBg = document.getElementById('heroBackground');
    const prevBtn = document.getElementById('prevHero');
    const nextBtn = document.getElementById('nextHero');
    const deleteBtn = document.getElementById('deleteImageBtn');
    let heroInterval;

    function showHeroImg(idx) {
        if (!heroBg || heroImages.length === 0) return;
        
        // Gérer l'index circulaire
        currentHeroIdx = (idx + heroImages.length) % heroImages.length;
        
        const imagePath = heroImages[currentHeroIdx].startsWith('/project10/') 
            ? heroImages[currentHeroIdx] 
            : '/project10/' + heroImages[currentHeroIdx];
            
        heroBg.style.opacity = '0';
        setTimeout(() => {
            heroBg.style.backgroundImage = `url('${imagePath}')`;
            heroBg.style.backgroundSize = 'cover';
            heroBg.style.backgroundPosition = 'center';
            heroBg.style.backgroundRepeat = 'no-repeat';
            heroBg.style.opacity = '1';
        }, 300);
    }

    function nextHeroImg() {
        showHeroImg(currentHeroIdx + 1);
    }

    function prevHeroImg() {
        showHeroImg(currentHeroIdx - 1);
    }

    function startHeroAuto() {
        stopHeroAuto();
        if (heroImages.length > 1) {
            heroInterval = setInterval(nextHeroImg, 6000);
        }
    }

    function stopHeroAuto() {
        if (heroInterval) {
            clearInterval(heroInterval);
            heroInterval = null;
        }
    }

    // Initialisation
    if (heroBg && heroImages.length > 0) {
        // Ajouter la transition CSS
        heroBg.style.transition = 'opacity 0.3s ease-in-out';
        
        // Afficher la première image
        showHeroImg(0);
        
        // Démarrer le défilement automatique si il y a plus d'une image
        if (heroImages.length > 1) {
            startHeroAuto();
            
            // Gestionnaires d'événements pour les boutons
            if (prevBtn) {
                prevBtn.addEventListener('click', () => {
                    prevHeroImg();
                    startHeroAuto();
                });
            }
            if (nextBtn) {
                nextBtn.addEventListener('click', () => {
                    nextHeroImg();
                    startHeroAuto();
                });
            }
            
            // Arrêter/reprendre le défilement au survol
            heroBg.addEventListener('mouseenter', stopHeroAuto);
            heroBg.addEventListener('mouseleave', startHeroAuto);
        } else {
            // Cacher les boutons s'il n'y a qu'une seule image
            if (prevBtn) prevBtn.style.display = 'none';
            if (nextBtn) nextBtn.style.display = 'none';
        }
    }

    <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1): ?>
    // Fonction de suppression d'image pour les administrateurs
    if (deleteBtn) {
        deleteBtn.addEventListener('click', function() {
            if (confirm('Êtes-vous sûr de vouloir supprimer cette image ?')) {
                const currentImage = heroImages[currentHeroIdx];
                
                // Créer les données du formulaire
                const formData = new FormData();
                formData.append('place_id', <?= $placeId ?>);
                formData.append('image', currentImage.replace('/project10/', ''));

                // Envoyer la requête
                fetch('delete_image.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Recharger la page pour mettre à jour le slider
                        window.location.reload();
                    } else {
                        alert('Erreur lors de la suppression de l\'image : ' + (data.message || 'Erreur inconnue'));
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Une erreur est survenue lors de la suppression de l\'image');
                });
            }
        });
    }
    <?php endif; ?>
    </script>
    <?php endif; ?>

    
    <main style="margin-top: 0px; background: #f8f6f3; min-height: 100vh;">
        <div class="container" style="max-width: 1200px;">
            
            <!-- New Two-Column Layout Container -->
            <div class="place-two-column-layout">

                <!-- Left Column: Introduction -->
                <div class="left-column">

                    <!-- Added place introduction section with official website -->
                    <div style="display: flex; flex-direction: column; gap: 30px; margin-top: 30px;">
                        <!-- Section Réservation -->
                        <div class="section-block" style="background-color: #fff; border: 1px solid #e0d5b8; border-radius: 8px; padding: 30px; margin-bottom: 0;">
                            <h3 style="font-size: 1.4rem; margin-bottom: 18px; color: #2D2926; font-family: 'Playfair Display', serif;">Activités</h3>
                            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px;">
                                <!-- Bouton de réservation d'hôtel -->
                                <a href="https://www.barcelo.com/fr-ma/barcelo-anfa-casablanca/" 
                                   target="_blank" 
                                   rel="noopener noreferrer"
                                   style="background-color: #f9f5ed;
                                          color: #b48a3c !important; 
                                          padding: 12px 15px; 
                                          border-radius: 6px; 
                                          font-weight: 600; 
                                          text-decoration: none; 
                                          display: flex; 
                                          align-items: center; 
                                          gap: 10px;
                                          transition: all 0.3s ease;
                                          border: 1px solid #e0d5b8;
                                          font-size: 0.95rem;
                                          text-align: left;">
                                    <i class="fas fa-hotel" style="color: #b48a3c; font-size: 1.1rem;"></i>
                                    <div>
                                        <div style="font-weight: 700;">Activités</div>
                                        <div style="font-size: 0.85rem; color: #666;">Réservez ici</div>
                                    </div>
                                </a>
                            </div>
                        </div>
                        <?php if (!empty($place['description'])): ?>
                        <div class="section-block" style="padding: 30px; margin-bottom: 0;">
                            <h3 style="font-size: 1.4rem; margin-bottom: 18px; color: #2D2926; font-family: 'Playfair Display', serif;">À propos de <?= htmlspecialchars($place['nom'] ?? 'ce lieu') ?></h3>
                            <p style="font-size: 1.05rem; line-height: 1.6; color: #444;"><?= nl2br(htmlspecialchars($place['description'])) ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <?php
                        // Récupérer l'URL du site web du lieu (vérifier plusieurs noms de colonnes possibles)
                        $site_web = '';
                        $possible_columns = ['site_web', 'website', 'url', 'lien_web', 'lien_site'];
                        foreach ($possible_columns as $col) {
                            if (!empty($place[$col])) {
                                $site_web = trim($place[$col]);
                                break;
                            }
                        }
                        
                        // Ajouter https:// si nécessaire
                        if (!empty($site_web) && !preg_match('~^https?://~i', $site_web)) {
                            $site_web = 'https://' . $site_web;
                        }
                        
                        if (!empty($site_web)): 
                        ?>
                        <div class="section-block" style="background-color: #fff; border: 1px solid #e0d5b8; border-radius: 8px; padding: 20px; text-align: left; margin-bottom: 0;">
                            <div style="display: flex; align-items: center; gap: 15px;">
                                <div style="background-color: #f9f5ed; width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <i class="fas fa-globe" style="color: #b48a3c; font-size: 1.2rem;"></i>
                                </div>
                                <div>
                                    <div style="font-weight: 600; color: #2D2926; margin-bottom: 3px;">Site Officiel</div>
                                    <div style="font-size: 0.9rem; color: #666; margin-bottom: 8px; word-break: break-all;">
                                        <?= htmlspecialchars(parse_url($site_web, PHP_URL_HOST) ?: $site_web) ?>
                                    </div>
                                    <a href="<?= htmlspecialchars($site_web) ?>" 
                                       target="_blank" 
                                       rel="noopener noreferrer" 
                                       style="color: #b48a3c !important; 
                                              padding: 8px 20px; 
                                              border-radius: 4px; 
                                              font-weight: 600; 
                                              text-decoration: none; 
                                              display: inline-flex; 
                                              align-items: center; 
                                              gap: 8px;
                                              border: 1px solid #b48a3c;
                                              transition: all 0.3s ease;
                                              font-size: 0.95rem;">
                                        <i class="fas fa-external-link-alt"></i>
                                        <span>Visiter le site</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

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

                    <!-- Site web de l'établissement -->
                    <?php 
                    // Vérifier si une URL de site web est disponible
                    $website_url = $place['site_web'] ?? $place['website'] ?? $place['url'] ?? '';
                    $website_url = trim($website_url);
                    
                    if (!empty($website_url)): 
                        // S'assurer que l'URL commence par http:// ou https://
                        if (!preg_match('~^https?://~i', $website_url)) {
                            $website_url = 'https://' . $website_url;
                        }
                        
                        // Déterminer l'icône en fonction du type de lieu
                        $type_lieu = strtolower($place['type'] ?? '');
                        $icone = 'fa-globe'; // Icône par défaut
                        $couleur = '#3498db'; // Couleur bleue par défaut
                        
                        if (strpos($type_lieu, 'hôtel') !== false || strpos($type_lieu, 'hotel') !== false) {
                            $icone = 'fa-hotel';
                            $couleur = '#e74c3c'; // Rouge
                        } elseif (strpos($type_lieu, 'restaurant') !== false || strpos($type_lieu, 'café') !== false) {
                            $icone = 'fa-utensils';
                            $couleur = '#e67e22'; // Orange
                        } elseif (strpos($type_lieu, 'cinéma') !== false || strpos($type_lieu, 'cinema') !== false) {
                            $icone = 'fa-film';
                            $couleur = '#9b59b6'; // Violet
                        } elseif (strpos($type_lieu, 'théâtre') !== false || strpos($type_lieu, 'theatre') !== false) {
                            $icone = 'fa-theater-masks';
                            $couleur = '#2ecc71'; // Vert
                        } elseif (strpos($type_lieu, 'musée') !== false || strpos($type_lieu, 'musee') !== false) {
                            $icone = 'fa-landmark';
                            $couleur = '#f1c40f'; // Jaune
                        }
                    ?>
                    <div class="section-block" style="padding: 20px; margin-bottom: 20px; background-color: #f8f9fa; border-radius: 8px; text-align: center;">
                        <h3 style="font-size: 1.2rem; margin-bottom: 15px; color: #2c3e50; display: flex; align-items: center; justify-content: center; gap: 8px;">
                            <i class="fas <?= $icone ?>" style="color: <?= $couleur ?>;"></i>
                            Site web de l'établissement
                        </h3>
                        <a href="<?= htmlspecialchars($website_url) ?>" 
                           target="_blank" 
                           rel="noopener noreferrer"
                           class="btn-outline" 
                           style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 10px 20px; font-size: 1rem; border-radius: 25px; background-color: #f5f5f5; border: 1px solid #ddd; color: #333; text-decoration: none; transition: all 0.3s ease;"
                           onmouseover="this.style.backgroundColor='#e9e9e9'; this.style.borderColor='#b48a3c';"
                           onmouseout="this.style.backgroundColor='#f5f5f5'; this.style.borderColor='#ddd';">
                            <i class="fas fa-external-link-alt"></i>
                            Visiter le site web
                        </a>
                    </div>
                    <?php endif; ?>

                    <?php 
                    // Récupération de l'URL d'activité
                    $url_activite = '';
                    
                    // Vérifier les différentes variations possibles du nom de la colonne
                    $possible_columns = ['url_activités', 'url_activites', 'site_web', 'website', 'lien_activite'];
                    
                    foreach ($possible_columns as $col) {
                        if (!empty($place[$col])) {
                            $url_activite = trim($place[$col]);
                            break;
                        }
                    }
                    
                    // Ajouter https:// si ce n'est pas déjà le cas
                    if (!empty($url_activite) && !preg_match('~^https?://~i', $url_activite)) {
                        $url_activite = 'https://' . $url_activite;
                    }
                    ?>

                    <?php if (!empty($url_activite)): ?>
                    <div class="section-block" style="padding: 30px; margin-bottom: 30px;">
                        <h3 style="font-size: 1.3rem; margin-bottom: 18px; display: flex; align-items: center; gap: 10px; color: var(--primary-color);">
                            <i class="fas fa-ticket-alt" style="color: #b48a3c;"></i>Réserver en ligne
                        </h3>
                        <div style="text-align: center;">
                            <a href="<?= htmlspecialchars($url_activite) ?>" 
                               target="_blank" 
                               rel="noopener noreferrer"
                               class="btn-outline" 
                               style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; font-size: 1rem; border-radius: 25px; background-color: #f5f5f5; border: 1px solid #ddd; color: #333; text-decoration: none; transition: all 0.3s ease;"
                               onmouseover="this.style.backgroundColor='#e9e9e9'; this.style.borderColor='#b48a3c';"
                               onmouseout="this.style.backgroundColor='#f5f5f5'; this.style.borderColor='#ddd';">
                                <i class="fas fa-external-link-alt"></i>
                                Réserver maintenant
                            </a>
                        </div>
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
    <?php include 'includes/footer.php'; ?>

    <!-- Font Awesome -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</body>
</html> 