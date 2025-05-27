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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($place['nom'] ?? 'Lieu') ?> - Maroc Authentique</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/place.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .place-hero-pro {
            background: linear-gradient(rgba(30,30,30,0.5),rgba(30,30,30,0.5)), url('<?= htmlspecialchars($place['photo']) ?>') center/cover no-repeat;
            height: 350px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0 0 30px 30px;
            box-shadow: 0 8px 32px rgba(44,62,80,0.12);
        }
        .place-hero-pro .hero-content {
            color: #fff;
            text-align: center;
            width: 100%;
        }
        .place-hero-pro h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2.8rem;
            margin-bottom: 10px;
            letter-spacing: 1px;
        }
        .place-hero-pro p {
            font-size: 1.2rem;
            opacity: 0.9;
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
            box-shadow: 0 2px 10px rgba(44,62,80,0.07);
            margin-bottom: 32px;
            padding: 32px 28px;
        }
        .section-block h3 {
            font-size: 1.3rem;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--primary-color);
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
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container header-container">
            <a href="index.php" class="logo">
                <img src="https://i.postimg.cc/g07GgLp5/VMaroc-logo-trf.png" alt="Maroc Authentique" class="logo-img"style="height:70px;">
            </a>
            <ul class="nav-menu">
                <li><a href="index.php">Accueil</a></li>
                <li><a href="destinations.php">Destinations</a></li>
                <li><a href="experiences.php">Expériences</a></li>
            </ul>
            <div class="auth-buttons">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                        <a href="pages/admin-panel.php" class="btn-outline">Panel Admin</a>
                        <a href="logout.php" class="btn-primary">Déconnexion</a>
                    <?php else: ?>
                        <a href="profile.php" class="btn-outline">Mon Profil</a>
                        <a href="logout.php" class="btn-primary">Déconnexion</a>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="login.php" class="btn-outline">Connexion</a>
                    <a href="register.php" class="btn-primary">Inscription</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main style="margin-top: 40px; background: #f8f6f3; min-height: 100vh;">
        <div class="container" style="max-width: 1200px;">
            <div style="padding-top: 78px; margin-bottom: 38px;">
                <h1 style="font-family: 'Playfair Display', serif; font-size: 2.5rem; font-weight:700; margin-bottom: 0.5rem; color: #222; letter-spacing: -1px;"><?= htmlspecialchars($place['nom']) ?></h1>
                <p style="font-size: 1.1rem; color: #444; margin-bottom: 1.5rem; max-width: 800px;"> <?= htmlspecialchars($place['description']) ?> </p>
            </div>
            <div class="place-content" style="display: grid; grid-template-columns: 2fr 1fr; gap: 2.5rem; align-items: flex-start; background: #fff; border-radius: 18px; box-shadow: 0 4px 24px rgba(44,62,80,0.10); padding: 36px 32px 32px 32px; margin-bottom: 38px;">
                <div>
                    <?php
                    // Préparer les 4 images pour le slider
                    $gallery = [];
                    if (!empty($place['gallery'])) {
                        $gallery = array_filter(array_map('trim', explode(',', $place['gallery'])));
                    }
                    $gallery = array_filter($gallery, function($img) use ($place) {
                        return $img !== $place['photo'];
                    });
                    $sliderImages = array_merge([$place['photo']], array_slice($gallery, 0, 3));
                    while (count($sliderImages) < 4) {
                        $sliderImages[] = $place['photo'];
                    }
                    ?>
                    <div class="main-image" style="margin-bottom: 1.2rem; position:relative; overflow:hidden;">
                        <div id="slider" style="position:relative; width:100%; height:100%;">
                            <?php foreach ($sliderImages as $idx => $img): ?>
                                <img src="<?= htmlspecialchars($img) ?>" alt="Photo <?= $idx+1 ?>" class="slider-photo" style="width:100%; height:450px; object-fit:cover; border-radius:8px; position:absolute; left:0; top:0; opacity:<?= $idx === 0 ? '1' : '0' ?>; transition:opacity 0.5s;">
                            <?php endforeach; ?>
                            <button id="sliderPrev" type="button" aria-label="Précédent" style="position:absolute;left:16px;top:50%;transform:translateY(-50%);background:rgba(45,41,38,0.7);border:none;border-radius:50%;width:38px;height:38px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.2rem;cursor:pointer;outline:none;z-index:2;"><i class="fas fa-chevron-left"></i></button>
                            <button id="sliderNext" type="button" aria-label="Suivant" style="position:absolute;right:16px;top:50%;transform:translateY(-50%);background:rgba(45,41,38,0.7);border:none;border-radius:50%;width:38px;height:38px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.2rem;cursor:pointer;outline:none;z-index:2;"><i class="fas fa-chevron-right"></i></button>
                        </div>
                    </div>
                    <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const photos = document.querySelectorAll('.slider-photo');
                        const prev = document.getElementById('sliderPrev');
                        const next = document.getElementById('sliderNext');
                        let idx = 0;
                        function show(idxToShow) {
                            photos.forEach((img, i) => {
                                img.style.opacity = (i === idxToShow) ? '1' : '0';
                            });
                        }
                        prev.addEventListener('click', function() {
                            idx = (idx - 1 + photos.length) % photos.length;
                            show(idx);
                        });
                        next.addEventListener('click', function() {
                            idx = (idx + 1) % photos.length;
                            show(idx);
                        });
                    });
                    </script>
                </div>
                <div class="place-info">
                    <span class="place-title" style="font-size:1.35rem;font-weight:bold;color:#222;display:block;"> <?= htmlspecialchars($place['nom']) ?> </span>
                    <div class="place-meta" style="display:flex;align-items:center;gap:14px;margin-top:4px;margin-bottom:10px;">
                        <span class="stars">
                            <?php
                            $moyenne = null;
                            if (count($reviews) > 0) {
                                $sum = 0;
                                foreach ($reviews as $r) $sum += $r['rating'];
                                $moyenne = $sum / count($reviews);
                            }
                            $fullStars = floor($moyenne);
                            $halfStar = ($moyenne - $fullStars) >= 0.5 ? 1 : 0;
                            $emptyStars = 5 - $fullStars - $halfStar;
                            for ($i = 0; $i < $fullStars; $i++) echo '<i class="fas fa-star" style="color:#FFD700;"></i>';
                            if ($halfStar) echo '<i class="fas fa-star-half-alt" style="color:#FFD700;"></i>';
                            for ($i = 0; $i < $emptyStars; $i++) echo '<i class="far fa-star" style="color:#FFD700;"></i>';
                            ?>
                        </span>
                        <span class="star-note-value" style="margin-left:6px;font-weight:500;color:#444;font-size:1.08rem;">
                            <?= $moyenne ? round($moyenne,1) : '-' ?>
                        </span>
                        <span class="place-address" style="display:flex;align-items:center;gap:5px;color:#6c757d;font-size:1.05em;margin-left:10px;">
                            <i class="fa fa-map-marker-alt" style="color:#b48a3c;font-size:1.1em;"></i>
                            <?= htmlspecialchars($place['adresse']) ?>
                        </span>
                    </div>
                    <?php if (!empty($place['url_activite'])): ?>
                        <a href="<?= htmlspecialchars($place['url_activite']) ?>" class="btn-primary" style="width:100%;margin:18px 0 8px 0;" target="_blank" rel="noopener">Voir les activités</a>
                    <?php else: ?>
                        <button class="btn-primary" style="width:100%;margin:18px 0 8px 0;opacity:0.6;cursor:not-allowed;" disabled>Aucune activité en ligne</button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="services" style="margin-top:2.5rem;">
                <h2 style="font-size:1.3rem;font-family:'Playfair Display',serif;margin-bottom:1.2rem;">À propos de <?= htmlspecialchars($place['nom']) ?></h2>
                <div style="border-left: 3px solid #7bb86f; padding-left: 16px; color: #4a4a4a; margin-bottom: 1.2rem;">
                    <?= nl2br(htmlspecialchars($place['description'])) ?>
                </div>
            </div>
            <?php if (!empty($equipements[0])): ?>
            <div class="services">
                <h2>Équipements & Services</h2>
                <div class="services-grid">
                    <?php foreach ($equipements as $eq): ?>
                        <div class="service-item"><i class="fas fa-check-circle"></i> <span><?= htmlspecialchars($eq) ?></span></div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            <!-- Activités à faire (exemple) -->
            <?php if (!empty($place['activites'])): ?>
            <div class="services">
                <h2>Activités à faire</h2>
                <div class="services-grid">
                    <?php foreach (explode(',', $place['activites']) as $act): ?>
                        <div class="service-item"><i class="fas fa-running"></i> <span><?= htmlspecialchars(trim($act)) ?></span></div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            <div class="services">
                <h2>Localisation</h2>
                <div style="margin-bottom:1rem;">
                    <?php if (!empty($place['latitude']) && !empty($place['longitude'])): ?>
                        <iframe width="100%" height="260" frameborder="0" style="border:0" allowfullscreen
                            src="https://www.google.com/maps?q=<?= $place['latitude'] ?>,<?= $place['longitude'] ?>&hl=fr&z=16&output=embed">
                        </iframe>
                    <?php else: ?>
                        <img src="https://maps.googleapis.com/maps/api/staticmap?center=<?= urlencode($place['adresse']) ?>&zoom=15&size=600x260&markers=color:red|<?= urlencode($place['adresse']) ?>&key=VOTRE_API_KEY" alt="Carte" style="width:100%;height:260px;object-fit:cover;">
                        <p style="margin-top:10px;"><i class="fas fa-map-pin"></i> <?= htmlspecialchars($place['adresse']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="services">
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
                    document.addEventListener('DOMContentLoaded', function() {
                        const stars = document.querySelectorAll('.rating-select label');
                        stars.forEach((star, index) => {
                            star.addEventListener('mouseover', function() {
                                // Highlight stars on hover
                                for (let i = 0; i <= index; i++) {
                                    stars[i].querySelector('i').style.color = '#f1c40f';
                                }
                                for (let i = index + 1; i < stars.length; i++) {
                                    stars[i].querySelector('i').style.color = '#ccc';
                                }
                            });
                            
                            star.addEventListener('click', function() {
                                // Set the selected rating
                                star.querySelector('input').checked = true;
                                // Keep the stars highlighted
                                for (let i = 0; i <= index; i++) {
                                    stars[i].querySelector('i').style.color = '#f1c40f';
                                }
                                for (let i = index + 1; i < stars.length; i++) {
                                    stars[i].querySelector('i').style.color = '#ccc';
                                }
                            });
                        });
                        
                        // Reset stars when mouse leaves the rating area
                        const ratingSelect = document.querySelector('.rating-select');
                        ratingSelect.addEventListener('mouseleave', function() {
                            stars.forEach((star, index) => {
                                const input = star.querySelector('input');
                                if (input.checked) {
                                    // Keep selected stars highlighted
                                    for (let i = 0; i <= index; i++) {
                                        stars[i].querySelector('i').style.color = '#f1c40f';
                                    }
                                    for (let i = index + 1; i < stars.length; i++) {
                                        stars[i].querySelector('i').style.color = '#ccc';
                                    }
                                } else {
                                    // Reset to initial state based on user's review
                                    const userRating = <?= $userHasReview ? $userReview['rating'] : 0 ?>;
                                    if (index < userRating) {
                                        star.querySelector('i').style.color = '#f1c40f';
                                    } else {
                                        star.querySelector('i').style.color = '#ccc';
                                    }
                                }
                            });
                        });
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
        </div>
    </main>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <img src="https://i.postimg.cc/g07GgLp5/VMaroc-logo-trf.png" alt="VMaroc Logo" class="logo-img" style="height:60px;">
                    <p>Découvrez les merveilles du Maroc avec VMaroc, votre guide de voyage personnalisé.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                <div class="footer-col">
                    <h3>Liens Rapides</h3>
                    <ul>
                        <li><a href="index.php">Accueil</a></li>
                        <li><a href="destinations.php">Destinations</a></li>
                        <li><a href="experiences.php">Expériences</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h3>Contact</h3>
                    <p>contact@marocauthentique.com</p>
                    <p>+212 522 123 456</p>
                </div>
            </div>
            <div class="copyright">
                <p>© 2025 Maroc Authentique. Tous droits réservés.</p>
            </div>
        </div>
    </footer>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const stars = document.querySelectorAll('.star');
            const ratingInput = document.getElementById('rating-value');
            const form = document.getElementById('star-rating-form');
            let selectedRating = 0;

            stars.forEach(star => {
                // Survol des étoiles
                star.addEventListener('mouseover', function() {
                    const rating = this.getAttribute('data-rating');
                    updateStars(rating);
                });

                // Sortie du survol
                star.addEventListener('mouseout', function() {
                    updateStars(selectedRating);
                });

                // Clic sur une étoile
                star.addEventListener('click', function() {
                    selectedRating = this.getAttribute('data-rating');
                    ratingInput.value = selectedRating;
                    updateStars(selectedRating);
                    form.submit();
                });
            });

            function updateStars(rating) {
                stars.forEach(star => {
                    const starRating = star.getAttribute('data-rating');
                    star.style.color = starRating <= rating ? '#f1c40f' : '#ccc';
                });
            }
        });
    </script>
</body>
</html> 