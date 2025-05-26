<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

// Tableau centralisé des villes populaires
$popular = ['casablanca', 'marrakech', 'tanger'];

try {
    $cities = $pdo->query("SELECT * FROM villes")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Maroc Authentique | Découvrez les trésors du Maroc</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Découvrez les plus belles destinations du Maroc et planifiez votre voyage idéal">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/main.css">
    <style>
        :root {
            --primary-color: #2D2926;
            --secondary-color: #555555;
            --accent-color: #8B7355;
            --light-color: #F5F5F5;
            --text-color: #333333;
            --border-color: #E0E0E0;
            --footer-bg: #2D2926;
            --white: #FFFFFF;
            --transition: all 0.3s ease;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.1);
            --shadow-md: 0 4px 6px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 25px rgba(0,0,0,0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body, .destination-item, .destination-item p, .destination-meta, .destination-address, .destination-rating {
            font-size: 0.91rem;
        }

        .hero p, .section-header p {
            font-size: 1rem;
        }

        body {
            font-family: 'Poppins', sans-serif;
            color: var(--text-color);
            line-height: 1.6;
            overflow-x: hidden;
            background-color: var(--white);
        }

        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header Style */
        header {
            background-color: var(--white);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
        }
        
        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 5%;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .logo-container {
            display: flex;
            align-items: center;
            text-decoration: none;
        }
        
        .logo-img {
            height: 40px;
            width: auto;
        }
        
        .logo-text {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-left: 10px;
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
        }
        
        .btn-outline {
            border: 1px solid var(--accent-color);
            color: var(--accent-color);
            background: transparent;
        }
        
        .btn-solid {
            background: var(--accent-color);
            color: var(--white);
            border: 1px solid var(--accent-color);
        }

        /* Hero Section */
        .hero {
            position: relative;
            height: 70vh;
            min-height: 350px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            text-align: center;
            width: 100%;
        }

        .hero h1 {
            font-size: 3rem;
            margin-bottom: 20px;
            font-family: 'Playfair Display', serif;
            line-height: 1.2;
        }

        .hero p {
            font-size: 1.1rem;
            margin-bottom: 30px;
            opacity: 0.9;
        }

        /* Destinations Section - Style simplifié comme sur l'image */
        .destinations-section {
            padding: 80px 0;
        }

        .section-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .section-header h2 {
            font-size: 2rem;
            margin-bottom: 15px;
            color: var(--primary-color);
        }

        .section-header p {
            color: var(--secondary-color);
            max-width: 700px;
            margin: 0 auto 30px;
        }

        .search-container {
            max-width: 500px;
            margin: 0 auto 40px;
        }

        #searchInput {
            width: 100%;
            padding: 12px 20px;
            font-size: 1rem;
            border: 1px solid var(--border-color);
            border-radius: 4px;
        }

        .destination-list {
            max-width: 800px;
            margin: 0 auto;
        }

        .destination-item {
            margin-bottom: 30px;
            padding-bottom: 30px;
            border-bottom: 1px solid var(--border-color);
        }

        .destination-item:last-child {
            border-bottom: none;
        }

        .destination-item h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: var(--primary-color);
        }

        .destination-item p {
            color: var(--secondary-color);
            margin-bottom: 15px;
            line-height: 1.6;
        }

        .explore-link {
            display: inline-flex;
            align-items: center;
            color: var(--accent-color);
            font-weight: 500;
            text-decoration: none;
        }

        .explore-link i {
            margin-left: 5px;
        }

        .view-all {
            text-align: center;
            margin-top: 40px;
        }

        .view-all a {
            color: var(--accent-color);
            text-decoration: none;
            font-weight: 500;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2rem;
            }
            
            .section-header h2 {
                font-size: 1.8rem;
            }
            
            .destination-item h3 {
                font-size: 1.3rem;
            }
        }

        @media (max-width: 480px) {
            .logo-text {
                display: none;
            }
            
            .hero h1 {
                font-size: 1.8rem;
            }
            
            .section-header h2 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>

<!-- Header -->
<header>
    <div class="container header-container">
        <a href="index.php" class="logo">
            <img src="https://i.postimg.cc/g07GgLp5/VMaroc-logo-trf.png" alt="Maroc Authentique" class="logo-img" style="height:78px;">
        </a>
        <ul class="nav-menu">
            <li><a href="index.php" class="active">Accueil</a></li>
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

<main style="margin-top: 100px;">
    <!-- Hero Section avec slider -->
    <section class="hero" style="position: relative; height: 70vh; min-height: 350px; display: flex; align-items: center; justify-content: center; overflow: hidden;">
        <div id="heroSlider" style="position: absolute; inset: 0; width: 100%; height: 100%; display: flex; transition: transform 0.7s cubic-bezier(.4,0,.2,1); will-change: transform;">
            <img src="https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=1500&q=80" class="hero-slide" style="object-fit: cover; width: 100%; height: 100%; min-width: 100%;">
            <img src="https://images.unsplash.com/photo-1464983953574-0892a716854b?auto=format&fit=crop&w=1500&q=80" class="hero-slide" style="object-fit: cover; width: 100%; height: 100%; min-width: 100%;">
            <img src="https://images.unsplash.com/photo-1502082553048-f009c37129b9?auto=format&fit=crop&w=1500&q=80" class="hero-slide" style="object-fit: cover; width: 100%; height: 100%; min-width: 100%;">
            <!-- Ajoute d'autres images si tu veux -->
        </div>
        <div style="background: rgba(0,0,0,0.5); position: absolute; inset: 0; z-index: 1;"></div>
        <button id="heroPrev" aria-label="Image précédente" style="position:absolute;left:24px;top:50%;transform:translateY(-50%);z-index:2;background:rgba(45,41,38,0.7);border:none;border-radius:50%;width:44px;height:44px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.5rem;cursor:pointer;outline:none;"><i class="fas fa-chevron-left"></i></button>
        <button id="heroNext" aria-label="Image suivante" style="position:absolute;right:24px;top:50%;transform:translateY(-50%);z-index:2;background:rgba(45,41,38,0.7);border:none;border-radius:50%;width:44px;height:44px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.5rem;cursor:pointer;outline:none;"><i class="fas fa-chevron-right"></i></button>
        <div class="hero-content" style="position: relative; z-index: 2; text-align: center; width: 100%;">
            <h1>Découvrez le Maroc Authentique</h1>
            <p>Planifiez votre voyage idéal et explorez les trésors cachés du royaume</p>
        </div>
    </section>

    <!-- Destinations Section -->
    <section class="section">
        <div class="container">
            <div class="section-title">
                <h2>Destinations Populaires</h2>
                <p>Explorez les villes les plus emblématiques du Maroc et découvrez leur richesse culturelle, historique et gastronomique.</p>
            </div>

            <div class="search-bar">
                <input type="text" id="searchInput" placeholder="Rechercher une ville...">
                <i class="fas fa-search"></i>
            </div>

            <div class="destinations-grid">
                <?php
                $displayed = 0;
                foreach ($cities as $city):
                    if (in_array(strtolower($city['nom']), $popular)):
                        $displayed++;
                ?>
                    <div class="destination-card" data-city="<?= strtolower($city['nom']) ?>">
                        <div class="destination-image">
                            <img src="<?= htmlspecialchars($city['photo']) ?>" alt="<?= htmlspecialchars($city['nom']) ?>">
                        </div>
                        <div class="destination-content">
                            <h3><?= htmlspecialchars($city['nom']) ?></h3>
                            <p><?= htmlspecialchars($city['description']) ?></p>
                            <a href="city.php?id=<?= $city['id'] ?>" class="btn-primary">Découvrir</a>
                        </div>
                    </div>
                <?php
                    endif;
                endforeach;
                if ($displayed === 0):
                ?>
                    <p style="text-align:center;color:var(--secondary-color);">Aucune ville populaire trouvée.</p>
                <?php endif; ?>
            </div>

            <div class="view-all" style="text-align:center; margin-top:40px;">
            </div>
        </div>
    </section>

    <!-- Pourquoi Choisir VMaroc ? -->
    <section class="section" style="background: var(--light-color); padding: 60px 0 40px 0;">
            <div class="container">
            <div class="section-title" style="margin-bottom:40px;">
                <h2>Pourquoi Choisir VMaroc ?</h2>
                <p style="color:var(--secondary-color); max-width:700px; margin:0 auto;">VMaroc vous aide à créer l'expérience de voyage parfaite au Maroc avec des outils adaptés à vos envies et vos besoins.</p>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 32px;">
                <div style="background: #f7f8fa; border-radius: 18px; padding: 36px 28px; text-align: left; box-shadow: var(--shadow-sm);">
                    <div style="background: var(--light-color); width: 56px; height: 56px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 18px;">
                        <i class="fas fa-map-marker-alt" style="font-size: 1.7rem; color: var(--accent-color);"></i>
                    </div>
                    <h3 style="color: var(--primary-color); font-size: 1.25rem; margin-bottom: 10px; font-weight:600;">Explorez en Détail</h3>
                    <p style="color: var(--secondary-color);">Découvrez chaque ville avec des informations détaillées sur les attractions, hôtels, restaurants et plus encore.</p>
                </div>
                <div style="background: #f7f8fa; border-radius: 18px; padding: 36px 28px; text-align: left; box-shadow: var(--shadow-sm);">
                    <div style="background: var(--light-color); width: 56px; height: 56px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 18px;">
                        <i class="fas fa-heart" style="font-size: 1.7rem; color: var(--accent-color);"></i>
                    </div>
                    <h3 style="color: var(--primary-color); font-size: 1.25rem; margin-bottom: 10px; font-weight:600;">Recommandations Personnalisées</h3>
                    <p style="color: var(--secondary-color);">Obtenez des suggestions adaptées à vos intérêts, votre budget et la durée de votre séjour.</p>
                </div>
                <div style="background: #f7f8fa; border-radius: 18px; padding: 36px 28px; text-align: left; box-shadow: var(--shadow-sm);">
                    <div style="background: var(--light-color); width: 56px; height: 56px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 18px;">
                        <i class="fas fa-compass" style="font-size: 1.7rem; color: var(--accent-color);"></i>
                    </div>
                    <h3 style="color: var(--primary-color); font-size: 1.25rem; margin-bottom: 10px; font-weight:600;">Interface Intuitive</h3>
                    <p style="color: var(--secondary-color);">Naviguez facilement dans notre application avec une interface claire et des filtres pratiques pour trouver ce que vous cherchez.</p>
                </div>
            </div>
            </div>
        </section>

    <!-- Prêt à Découvrir le Maroc ? -->
    <section class="section" style="background: var(--light-color); padding: 0 0 60px 0;">
            <div class="container">
            <div class="discover-slider" style="position: relative; border-radius: 24px; box-shadow: var(--shadow-lg); overflow: hidden; min-height: 220px;">
                <div class="slider-images" style="position: absolute; inset: 0; width: 100%; height: 100%;">
                    <img src="https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=1500&q=80" class="slider-img active" style="object-fit: cover; width: 100%; height: 100%; position: absolute; left: 0; top: 0; transition: opacity 1s;">
                    <img src="https://images.unsplash.com/photo-1464983953574-0892a716854b?auto=format&fit=crop&w=1500&q=80" class="slider-img" style="object-fit: cover; width: 100%; height: 100%; position: absolute; left: 0; top: 0; opacity: 0; transition: opacity 1s;">
                    <img src="https://images.unsplash.com/photo-1502082553048-f009c37129b9?auto=format&fit=crop&w=1500&q=80" class="slider-img" style="object-fit: cover; width: 100%; height: 100%; position: absolute; left: 0; top: 0; opacity: 0; transition: opacity 1s;">
                    <!-- Ajoute d'autres images si tu veux -->
                    <div style="background: rgba(255,255,255,0.82); position: absolute; inset: 0;"></div>
                </div>
                <div style="position: relative; z-index: 2; display: flex; align-items: center; justify-content: space-between; height: 100%; padding: 48px 48px 48px 60px;">
                    <div style="max-width: 60%;">
                        <h2 style="color: var(--primary-color); font-size: 2.2rem; font-weight: 700; margin-bottom: 16px;">Prêt à Découvrir le Maroc ?</h2>
                        <p style="color: var(--secondary-color); font-size: 1.15rem;">Commencez votre voyage dès maintenant et explorez les merveilles du Maroc à votre propre rythme.</p>
                    </div>
                    <div>
                        <a href="destinations.php" style="display: inline-flex; align-items: center; gap: 10px; background: var(--primary-color); color: var(--white); font-weight: 600; font-size: 1.1rem; border-radius: 32px; padding: 18px 38px; text-decoration: none; box-shadow: var(--shadow-md); transition: background 0.2s;">
                            <i class="fas fa-map-marker-alt" style="font-size: 1.2em;"></i> Explorer les villes
                        </a>
                    </div>
                </div>
                </div>
            </div>
        </section>
    </main>

<!-- Footer -->
<footer>
        <div class="container">
        <div class="footer-grid">
            <div class="footer-col">
                <div style="display: flex; flex-direction: column; align-items: flex-start; gap: 18px;">
                    <img src="https://i.postimg.cc/g07GgLp5/VMaroc-logo-trf.png" alt="VMaroc Logo" class="logo-img" style="height:60px;">
                    <p>Découvrez les merveilles du Maroc avec VMaroc, votre guide de voyage personnalisé.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                    </div>
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
                <h3>Villes Populaires</h3>
                    <ul>
                    <?php
                    foreach ($popular as $pop) {
                        foreach ($cities as $city) {
                            if (strtolower($city['nom']) === $pop) {
                                echo '<li><a href="city.php?id=' . $city['id'] . '">' . htmlspecialchars($city['nom']) . '</a></li>';
                                break;
                            }
                        }
                    }
                    ?>
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
    // Fonction de recherche dynamique
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

    let sliderIndex = 0;
    const sliderImgs = document.querySelectorAll('.slider-img');
    setInterval(() => {
        sliderImgs.forEach((img, i) => {
            img.style.opacity = (i === sliderIndex) ? '1' : '0';
            img.classList.toggle('active', i === sliderIndex);
        });
        sliderIndex = (sliderIndex + 1) % sliderImgs.length;
    }, 3500); // Change d'image toutes les 3,5 secondes

    (function() {
        const slider = document.getElementById('heroSlider');
        const slides = slider.querySelectorAll('.hero-slide');
        const prevBtn = document.getElementById('heroPrev');
        const nextBtn = document.getElementById('heroNext');
        let current = 0;
        let autoSlide;

        function goToSlide(idx) {
            current = (idx + slides.length) % slides.length;
            slider.style.transform = `translateX(-${current * 100}%)`;
        }

        function nextSlide() {
            goToSlide(current + 1);
        }
        function prevSlide() {
            goToSlide(current - 1);
        }

        nextBtn.addEventListener('click', () => {
            nextSlide();
            resetAuto();
        });
        prevBtn.addEventListener('click', () => {
            prevSlide();
            resetAuto();
        });

        function resetAuto() {
            clearInterval(autoSlide);
            autoSlide = setInterval(nextSlide, 3500);
        }

        autoSlide = setInterval(nextSlide, 3500);
    })();
</script>
</body>
</html> 