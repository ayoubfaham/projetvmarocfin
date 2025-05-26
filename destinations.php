<?php
session_start();
require_once 'config/database.php';

// Récupération de toutes les villes
$stmt = $pdo->query("SELECT * FROM villes ORDER BY nom");
$villes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Destinations - Maroc Authentique</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/main.css">
    <style>
        body, .destination-content, .destination-content p, .place-meta, .star-note-value, .place-address {
            font-size: 0.97rem;
        }
        .destination-content h3 {
            font-size: 1.25rem;
            font-weight: bold;
            margin-bottom: 6px;
            color: #222;
        }
        .place-meta {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.08rem;
            color: #222;
            margin-bottom: 8px;
        }
        .stars {
            display: flex;
            align-items: center;
            gap: 2px;
        }
        .stars i {
            color: #FFD700;
            font-size: 1.1em;
        }
        .star-note-value {
            margin-left: 6px;
            font-weight: 500;
            color: #444;
            font-size: 1.08rem;
        }
        .place-address {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #6c757d;
            font-size: 1.05em;
            margin-left: 10px;
        }
        .place-address i {
            color: #b48a3c;
            font-size: 1.1em;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container header-container">
            <a href="index.php" class="logo">
                <img src="https://i.postimg.cc/g07GgLp5/VMaroc-logo-trf.png" alt="Maroc Authentique" class="logo-img" style="height:70px;">
            </a>
            <ul class="nav-menu">
                <li><a href="index.php">Accueil</a></li>
                <li><a href="destinations.php" class="active">Destinations</a></li>
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

    <main style="margin-top: 100px;">
        <!-- Hero Section -->
        <section class="hero" style="background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('https://images.unsplash.com/photo-1548013146-72479768bada?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80') no-repeat center center/cover;">
            <div class="hero-content">
                <h1>Découvrez le Maroc</h1>
                <p>Explorez nos destinations les plus populaires</p>
            </div>
        </section>

        <!-- Destinations Section -->
        <section class="section">
            <div class="container">
                <div class="section-title">
                    <h2>Toutes nos destinations</h2>
                    <p>Explorez les merveilles du Maroc, ville par ville</p>
                </div>

                <div class="search-bar">
                    <input type="text" id="searchInput" placeholder="Rechercher une ville...">
                    <i class="fas fa-search"></i>
                </div>

                <div class="destinations-grid">
                    <?php foreach ($villes as $ville): ?>
                        <?php
                        // Calcul de la note moyenne pour la ville
                        $stmt = $pdo->prepare("SELECT AVG(a.rating) as moyenne FROM avis a JOIN lieux l ON a.id_lieu = l.id WHERE l.id_ville = ?");
                        $stmt->execute([$ville['id']]);
                        $moyenne = $stmt->fetchColumn();
                        $fullStars = floor($moyenne);
                        $halfStar = ($moyenne - $fullStars) >= 0.5 ? 1 : 0;
                        $emptyStars = 5 - $fullStars - $halfStar;
                        ?>
                        <div class="destination-card" data-city="<?= strtolower($ville['nom']) ?>">
                            <div class="destination-image">
                                <img src="<?= htmlspecialchars($ville['photo']) ?>" alt="<?= htmlspecialchars($ville['nom']) ?>">
                            </div>
                            <div class="destination-content">
                                <h3><?= htmlspecialchars($ville['nom']) ?></h3>
                                <div class="place-meta">
                                    <span class="stars">
                                        <?php for ($i = 0; $i < $fullStars; $i++) echo '<i class="fas fa-star"></i>'; ?>
                                        <?php if ($halfStar) echo '<i class="fas fa-star-half-alt"></i>'; ?>
                                        <?php for ($i = 0; $i < $emptyStars; $i++) echo '<i class="far fa-star"></i>'; ?>
                                    </span>
                                    <span class="star-note-value">
                                        <?= $moyenne ? round($moyenne,1) : '-' ?>
                                    </span>
                                    <span class="place-address">
                                        <i class="fa fa-map-marker-alt"></i>
                                        <?= htmlspecialchars($ville['adresse'] ?? $ville['nom']) ?>
                                    </span>
                                </div>
                                <p><?= htmlspecialchars($ville['description']) ?></p>
                                <a href="city.php?id=<?= $ville['id'] ?>" class="btn-primary">Découvrir</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <img src="https://i.postimg.cc/g07GgLp5/VMaroc-logo-trf.png" alt="VMaroc Logo" class="logo-img" style="height:90px;">
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
        // Fonction de recherche
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const cards = document.querySelectorAll('.destination-card');
            
            cards.forEach(card => {
                const cityName = card.dataset.city;
                if (cityName.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html> 