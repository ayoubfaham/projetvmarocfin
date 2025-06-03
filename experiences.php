<?php
session_start();
require_once 'config/database.php';

// Récupération des recommandations uniques
$recommandations = [
    [
        'title' => 'Séjour dans le désert',
        'description' => 'Vivez une expérience inoubliable dans le désert du Sahara. Nuit sous les étoiles, promenade en dromadaire et découverte de la culture berbère.',
        'image' => 'https://images.unsplash.com/photo-1518546305927-5a555bb7020d?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80',
        'location' => 'Merzouga'
    ],
    // ... autres recommandations ...
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expériences - VMaroc</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/header.css">
    <style>
    body, .recommandation-content, .recommandation-content p, .recommandation-location {
      font-size: 0.91rem;
    }
    .recommandation-content h3 {
      font-size: 1.18rem;
      font-weight: bold;
      margin-bottom: 6px;
      color: #222;
    }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include 'includes/header.php'; ?>

    <main style="margin-top: 100px;">
        <div class="container">
            <h1 class="section-title">Expériences Uniques au Maroc</h1>
            <p class="section-subtitle">Découvrez des expériences authentiques et inoubliables</p>
            
            <div class="experiences-grid">
                <?php foreach ($recommandations as $exp): ?>
                <div class="experience-card">
                    <div class="experience-image">
                        <img src="<?= htmlspecialchars($exp['image']) ?>" alt="<?= htmlspecialchars($exp['title']) ?>">
                    </div>
                    <div class="experience-content">
                        <h3><?= htmlspecialchars($exp['title']) ?></h3>
                        <p><?= htmlspecialchars($exp['description']) ?></p>
                        <div class="experience-location">
                            <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($exp['location']) ?>
                        </div>
                        <a href="#" class="btn-primary">En savoir plus</a>
                    </div>
                </div>
                <?php endforeach; ?>
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
                    <p>contact@vmaroc.com</p>
                    <p>+212 522 123 456</p>
                </div>
            </div>
            <div class="copyright">
                <p>© 2025 VMaroc. Tous droits réservés.</p>
            </div>
        </div>
    </footer>
</body>
</html>