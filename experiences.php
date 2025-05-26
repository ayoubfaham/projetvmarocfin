<?php
session_start();
require_once 'config/database.php';

// Récupération des expériences uniques
$experiences = [
    [
        'title' => 'Séjour dans le désert',
        'description' => 'Vivez une expérience inoubliable dans le désert du Sahara. Nuit sous les étoiles, promenade en dromadaire et découverte de la culture berbère.',
        'image' => 'https://images.unsplash.com/photo-1518546305927-5a555bb7020d?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80',
        'location' => 'Merzouga'
    ],
    [
        'title' => 'Cuisine traditionnelle',
        'description' => 'Apprenez les secrets de la cuisine marocaine avec nos chefs locaux. Préparation du couscous, tajines et pâtisseries traditionnelles.',
        'image' => 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80',
        'location' => 'Fès'
    ],
    [
        'title' => 'Randonnée dans l\'Atlas',
        'description' => 'Explorez les sentiers de l\'Atlas avec nos guides expérimentés. Découvrez des paysages à couper le souffle et rencontrez les communautés locales.',
        'image' => 'https://images.unsplash.com/photo-1518546305927-5a555bb7020d?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80',
        'location' => 'Toubkal'
    ],
    [
        'title' => 'Spa traditionnel',
        'description' => 'Détendez-vous dans un hammam traditionnel. Profitez des soins ancestraux et des massages aux huiles essentielles.',
        'image' => 'https://images.unsplash.com/photo-1518546305927-5a555bb7020d?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80',
        'location' => 'Marrakech'
    ],
    [
        'title' => 'Surf sur la côte atlantique',
        'description' => 'Apprenez à surfer sur les vagues de l\'Atlantique. Cours adaptés à tous les niveaux dans un cadre idyllique.',
        'image' => 'https://images.unsplash.com/photo-1518546305927-5a555bb7020d?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80',
        'location' => 'Taghazout'
    ],
    [
        'title' => 'Festival de musique',
        'description' => 'Participez aux festivals de musique traditionnelle et moderne. Une immersion dans la culture musicale marocaine.',
        'image' => 'https://images.unsplash.com/photo-1518546305927-5a555bb7020d?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80',
        'location' => 'Essaouira'
    ]
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recommendations - Maroc Authentique</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/main.css">
    <style>
    body, .experience-content, .experience-content p, .experience-location {
      font-size: 0.91rem;
    }
    .experience-content h3 {
      font-size: 1.18rem;
      font-weight: bold;
      margin-bottom: 6px;
      color: #222;
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
                <li><a href="destinations.php">Destinations</a></li>
                <li><a href="experiences.php" class="active">Recommendations</a></li>
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
        <section class="hero" style="background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('https://images.unsplash.com/photo-1464983953574-0892a716854b?auto=format&fit=crop&w=1500&q=80') no-repeat center center/cover;">
            <div class="hero-content">
                <h1>Nos Recommendations</h1>
                <p>Découvrez nos suggestions incontournables pour un voyage réussi au Maroc</p>
            </div>
        </section>

        <!-- Recommendations Section -->
        <section class="section">
            <div class="container">
                <div class="section-title">
                    <h2>Suggestions du moment</h2>
                    <p>Nos coups de cœur, lieux à ne pas manquer et conseils pour profiter pleinement de votre séjour</p>
                </div>
                <div class="experiences-grid">
                    <!-- Exemples de recommandations variées -->
                    <div class="experience-card">
                        <div class="experience-image">
                            <img src="https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=1500&q=80" alt="Chefchaouen">
                            <div class="experience-location"><i class="fas fa-map-marker-alt"></i> Chefchaouen</div>
                        </div>
                        <div class="experience-content">
                            <h3>Flâner dans la ville bleue</h3>
                            <p>Perdez-vous dans les ruelles bleues de Chefchaouen, l'une des villes les plus photogéniques du Maroc.</p>
                        </div>
                    </div>
                    <div class="experience-card">
                        <div class="experience-image">
                            <img src="https://images.unsplash.com/photo-1464983953574-0892a716854b?auto=format&fit=crop&w=1500&q=80" alt="Sahara">
                            <div class="experience-location"><i class="fas fa-map-marker-alt"></i> Sahara</div>
                        </div>
                        <div class="experience-content">
                            <h3>Nuit sous les étoiles dans le désert</h3>
                            <p>Vivez une nuit magique en bivouac à Merzouga, au cœur des dunes dorées du Sahara.</p>
                        </div>
                    </div>
                    <div class="experience-card">
                        <div class="experience-image">
                            <img src="https://images.unsplash.com/photo-1502082553048-f009c37129b9?auto=format&fit=crop&w=1500&q=80" alt="Essaouira">
                            <div class="experience-location"><i class="fas fa-map-marker-alt"></i> Essaouira</div>
                        </div>
                        <div class="experience-content">
                            <h3>Déguster du poisson frais à Essaouira</h3>
                            <p>Profitez du port d'Essaouira pour savourer un poisson grillé face à l'océan Atlantique.</p>
                        </div>
                    </div>
                    <div class="experience-card">
                        <div class="experience-image">
                            <img src="https://images.unsplash.com/photo-1518546305927-5a555bb7020d?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80" alt="Atlas">
                            <div class="experience-location"><i class="fas fa-map-marker-alt"></i> Haut Atlas</div>
                        </div>
                        <div class="experience-content">
                            <h3>Randonnée dans l'Atlas</h3>
                            <p>Explorez les montagnes de l'Atlas, entre villages berbères et paysages à couper le souffle.</p>
                        </div>
                    </div>
                    <div class="experience-card">
                        <div class="experience-image">
                            <img src="https://images.unsplash.com/photo-1555939594-58d7cb561ad1?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80" alt="Fès">
                            <div class="experience-location"><i class="fas fa-map-marker-alt"></i> Fès</div>
                        </div>
                        <div class="experience-content">
                            <h3>Visiter les médersas de Fès</h3>
                            <p>Découvrez l'histoire et l'architecture des anciennes écoles coraniques de la médina de Fès.</p>
                        </div>
                    </div>
                    <div class="experience-card">
                        <div class="experience-image">
                            <img src="https://images.unsplash.com/photo-1518546305927-5a555bb7020d?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80" alt="Marrakech">
                            <div class="experience-location"><i class="fas fa-map-marker-alt"></i> Marrakech</div>
                        </div>
                        <div class="experience-content">
                            <h3>Se détendre dans un hammam traditionnel</h3>
                            <p>Offrez-vous un moment de bien-être dans un hammam ou un spa marocain à Marrakech.</p>
                        </div>
                    </div>
                    <div class="experience-card">
                        <div class="experience-image">
                            <img src="https://images.unsplash.com/photo-1518546305927-5a555bb7020d?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80" alt="Taghazout">
                            <div class="experience-location"><i class="fas fa-map-marker-alt"></i> Taghazout</div>
                        </div>
                        <div class="experience-content">
                            <h3>Apprendre à surfer à Taghazout</h3>
                            <p>Initiez-vous au surf sur les plus belles vagues de la côte Atlantique.</p>
                        </div>
                    </div>
                    <div class="experience-card">
                        <div class="experience-image">
                            <img src="https://images.unsplash.com/photo-1464983953574-0892a716854b?auto=format&fit=crop&w=1500&q=80" alt="Conseil">
                            <div class="experience-location"><i class="fas fa-lightbulb"></i> Conseil</div>
                        </div>
                        <div class="experience-content">
                            <h3>Conseil pratique</h3>
                            <p>Privilégiez le printemps ou l'automne pour visiter le Maroc et profiter d'un climat idéal.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Newsletter Section -->
        <section class="section newsletter-section">
            <div class="container">
                <div class="newsletter-content">
                    <h2>Restez informé</h2>
                    <p>Inscrivez-vous à notre newsletter pour recevoir nos dernières offres et expériences</p>
                    <form class="newsletter-form">
                        <input type="email" placeholder="Votre adresse email" required>
                        <button type="submit" class="btn-primary">S'inscrire</button>
                    </form>
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
</body>
</html> 