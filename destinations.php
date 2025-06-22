<?php
session_start();
require_once 'config/database.php';
require_once 'includes/city_image_helper.php';

// Pagination settings
$perPage = 6; // Number of destinations per page
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $perPage;

// Récupération du nombre total de destinations
$totalStmt = $pdo->query("SELECT COUNT(*) as total FROM villes");
$totalDestinations = $totalStmt->fetch()['total'];
$totalPages = ceil($totalDestinations / $perPage);

// Récupération des destinations paginées
$stmt = $pdo->prepare("
    SELECT 
        v.*,
        NULL as site_web,  -- Champ pour compatibilité
        NULL as website,   -- Champ pour compatibilité
        NULL as url        -- Champ pour compatibilité
    FROM villes v 
    ORDER BY v.nom 
    LIMIT :limit OFFSET :offset
");
$stmt->bindParam(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$villes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Destinations - VMaroc</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/montserrat-font.css">
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/header.css">
    <style>
        body, .destination-content, .destination-content p, .place-meta, .star-note-value, .place-address {
            font-family: 'Montserrat', sans-serif;
            font-size: 0.97rem;
        }
        .destination-content h3 {
            font-family: 'Montserrat', sans-serif;
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
    <style>
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
         header .nav-menu li a,
         header .auth-buttons a {
            color: #fff !important; /* White text */
            transition: color 0.2s, border-bottom 0.2s;
         }

         header .nav-menu li a:hover,
         header .nav-menu li a.active {
             color: #bfa14a !important; /* Gold color on hover/active */
             border-bottom-color: #f3e9d1; /* Light gold border */
         }

        header .btn-outline {
            border-color: #bfa14a !important; /* Gold border */
            color: #fff !important; /* White text */
        }
        header .btn-outline:hover {
             background: #bfa14a !important; /* Gold background on hover */
             color: #fff !important; /* White text on hover */
        }

        header .btn-primary {
             background: #bfa14a !important; /* Gold background */
             color: #fff !important; /* White text */
        }
        header .btn-primary:hover {
             background: #8B7355 !important; /* Darker gold on hover */
        }

        /* Adjust logo size if needed */
        header .logo-img {
            height: 70px; /* Ensure consistent logo size */
        }

    </style>
    <style>
        /* Styles for the hero slider */
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

    </style>
    <style>
        /* Styles for the two-column layout */
        

        /* Styles for pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin: 32px 0 48px 0; /* Adjusted margin */
            flex-wrap: wrap;
        }
        .pagination-btn {
            background: #fff;
            color: #bfa14a;
            border: 1.5px solid #e9cba7;
            border-radius: 18px;
            padding: 8px 16px;
            font-size: 0.95rem;
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
            outline: none;
            text-decoration: none;
            box-shadow: 0 1px 6px rgba(0,0,0,0.08);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 38px;
            box-sizing: border-box;
        }
        .pagination-btn.active,
        .pagination-btn:focus {
            background: #bfa14a;
            color: #fff;
            border-color: #bfa14a;
            box-shadow: 0 3px 10px rgba(191,161,74,0.3);
        }
        .pagination-btn:hover:not(.active) {
            background: #fcf8f2;
            color: #8B7355;
            border-color: #bfa14a;
            box-shadow: 0 2px 8px rgba(191,161,74,0.2);
        }
        .pagination-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            background: #f8f8f8;
            color: #aaa;
            border-color: #eee;
            box-shadow: none;
        }
    </style>
    <style>
    footer {
        background: #2D2926;
        color: #f5f5f5;
        padding: 60px 0 30px 0;
        font-family: 'Montserrat', sans-serif;
        margin-top: 0px;
    }
    .footer-grid {
        display: flex;
        justify-content: center;
        align-items: flex-start;
        gap: 80px;
        max-width: 1200px;
        margin: 0 auto;
        padding-bottom: 40px;
        flex-wrap: wrap;
    }
    .footer-col {
        flex: 1 1 260px;
        min-width: 220px;
        text-align: left;
    }
    .footer-col h3 {
        color: #fff;
        font-size: 1.45rem;
        font-weight: 800;
        margin-bottom: 18px;
        letter-spacing: -1px;
    }
    .footer-col ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .footer-col ul li {
        margin-bottom: 10px;
    }
    .footer-col ul li a {
        color: #f5f5f5;
        text-decoration: none;
        font-size: 1.08rem;
        font-weight: 500;
        transition: color 0.2s;
    }
    .footer-col ul li a:hover {
        color: #e9cba7;
    }
    .footer-col p {
        color: #e0e0e0;
        font-size: 1.08rem;
        margin: 0 0 10px 0;
    }
    .footer-col img.logo-img {
        height: 60px;
        margin-bottom: 10px;
    }
    @media (max-width: 900px) {
        .footer-grid { flex-direction: column; gap: 32px; align-items: flex-start; }
        .footer-col { min-width: 0; }
    }
    .copyright {
        border-top: 1px solid #444;
        margin-top: 18px;
        padding: 18px 0 0 0;
        text-align: center;
        color: #e0e0e0;
        font-size: 1.01rem;
    }
    .copyright a {
        color: #e9cba7;
        text-decoration: none;
        margin: 0 8px;
        font-weight: 500;
    }
    .copyright a:hover {
        text-decoration: underline;
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
                <li><a href="recommandations.php">Recommandations</a></li>
            </ul>
            
            <div class="nav-buttons">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                        <a href="pages/admin-panel.php" class="nav-btn btn-outline">Pannel Admin</a>
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

    <!-- HERO FULL WIDTH WITH INTEGRATED HEADER AND DYNAMIC SLIDER -->
    <section class="hero" style="height: 75vh; min-height: 900px; /* Reduced height to move hero down */ position: relative; display: flex; align-items: center; justify-content: center; overflow: hidden;">

        <!-- Hero Slider -->
        <div class="hero-slider">
            <!-- Slides will be generated by JavaScript/PHP -->
        </div>
        
        <div class="hero-overlay"></div>

        <!-- Navigation Arrows -->
        <div class="hero-arrow left-arrow"><i class="fas fa-chevron-left"></i></div>
        <div class="hero-arrow right-arrow"><i class="fas fa-chevron-right"></i></div>

    </section>

    <main style="margin-top: -10px; /* Reduced space above main content */">
        <!-- Destinations Section -->
        <section class="section">
            <div class="container">
                <div class="section-title">
                    <h2 style="font-family: 'Montserrat', sans-serif; font-weight: 800;">Toutes nos destinations</h2>
                    <p style="font-family: 'Montserrat', sans-serif;">Explorez les merveilles du Maroc, ville par ville</p>
                </div>

                <div class="search-bar">
                    <input type="text" id="searchInput" placeholder="Rechercher une ville..." style="font-family: 'Montserrat', sans-serif;">
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
                                <img src="<?= htmlspecialchars(getCityImageUrl($ville['nom'], $ville['photo'])) ?>"
                                    alt="<?= htmlspecialchars($ville['nom']) ?>">
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
                                
                                <!-- Barre du site officiel -->
                                <?php if (!empty($ville['site_web']) || !empty($ville['website']) || !empty($ville['url'])): ?>
                                <?php 
                                    $site_web = !empty($ville['site_web']) ? $ville['site_web'] : 
                                             (!empty($ville['website']) ? $ville['website'] : $ville['url']);
                                    if (!preg_match("~^https?://~i", $site_web)) {
                                        $site_web = 'https://' . $site_web;
                                    }
                                ?>
                                <div class="official-website-bar" style="background-color: #f8f9fa; border-radius: 6px; padding: 8px 12px; margin: 12px 0; display: flex; align-items: center; gap: 8px; font-size: 0.9rem;">
                                    <i class="fas fa-external-link-alt" style="color: #b48a3c;"></i>
                                    <span style="flex-grow: 1; color: #555;">Site officiel :</span>
                                    <a href="<?= htmlspecialchars($site_web) ?>" target="_blank" rel="noopener noreferrer" style="color: #b48a3c; text-decoration: none; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 180px; display: inline-block;">
                                        Visiter le site
                                    </a>
                                </div>
                                <?php endif; ?>
                                
                                <a href="city.php?id=<?= $ville['id'] ?>" class="btn-primary" style="font-family: 'Montserrat', sans-serif; font-weight: 600;">Découvrir</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                 <!-- Pagination -->
                 <?php if ($totalPages > 1): ?>
                 <div class="pagination" style="margin-top: 40px;">
                     <a class="pagination-btn" href="?page=<?= max(1, $page-1) ?>" <?= $page <= 1 ? 'disabled' : '' ?>>Précédent</a>
                     <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                         <a class="pagination-btn<?= $i === $page ? ' active' : '' ?>" href="?page=<?= $i ?>"> <?= $i ?> </a>
                     <?php endfor; ?>
                     <a class="pagination-btn" href="?page=<?= min($totalPages, $page+1) ?>" <?= $page >= $totalPages ? 'disabled' : '' ?>>Suivant</a>
                 </div>
                 <?php endif; ?>

            </div>
        </section>
    </main>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

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
    <script>
        // Code for the hero background image slider with arrows
        // Define your hero images here
        const heroImages = [
            'images/destinations.png',
            'images/destination1.png',
            'images/destination2.png',
            'images/destination3.png',
            'images/destination4.png',
            'images/destination5.png'
        ];

        const heroSlider = document.querySelector('.hero-slider');
        const leftArrow = document.querySelector('.hero-arrow.left-arrow');
        const rightArrow = document.querySelector('.hero-arrow.right-arrow');
        let currentHeroSlideIndex = 0;
        let autoSlideInterval;

        // Function to create and add slides to the DOM
        function createHeroSlides() {
            if (!heroSlider || heroImages.length === 0) return;
            heroImages.forEach((imgSrc, index) => {
                const slide = document.createElement('div');
                slide.classList.add('hero-slide');
                slide.style.backgroundImage = `url('${imgSrc}')`;
                if (index === 0) {
                    slide.classList.add('active'); // Set the first slide as active
                }
                heroSlider.appendChild(slide);
            });
        }

        // Function to show a specific hero slide
        function showHeroSlide(indexToShow) {
            const slides = heroSlider.querySelectorAll('.hero-slide');
            if (slides.length === 0) return;

            // Optional: Add fade-out class to current slide before changing opacity (for smoother transition if needed)
            // const currentActiveSlide = heroSlider.querySelector('.hero-slide.active');
            // if (currentActiveSlide) currentActiveSlide.classList.add('fade-out');

            slides.forEach((slide, i) => {
                slide.classList.remove('active');
                // slide.classList.remove('fade-out'); // Remove fade-out class
                if (i === indexToShow) {
                    slide.classList.add('active');
                }
            });
             currentHeroSlideIndex = indexToShow;
        }

        // Function to go to the next hero slide
        function nextHeroSlide() {
            const slides = heroSlider.querySelectorAll('.hero-slide');
            if (slides.length === 0) return;
            const nextIndex = (currentHeroSlideIndex + 1) % slides.length;
            showHeroSlide(nextIndex);
        }

        // Function to go to the previous hero slide
        function prevHeroSlide() {
            const slides = heroSlider.querySelectorAll('.hero-slide');
            if (slides.length === 0) return;
            const prevIndex = (currentHeroSlideIndex - 1 + slides.length) % slides.length;
            showHeroSlide(prevIndex);
        }

        // Start auto slide rotation
        function startAutoSlide() {
             stopAutoSlide(); // Clear any existing interval
             autoSlideInterval = setInterval(nextHeroSlide, 7000); // Change slide every 7 seconds
        }

        // Stop auto slide rotation
        function stopAutoSlide() {
            clearInterval(autoSlideInterval);
            autoSlideInterval = null;
        }

        // Initialize the hero slider
        createHeroSlides(); // Create the slides first
        if (heroImages.length > 1) { // Only start auto slide if there's more than one image
            startAutoSlide(); // Start auto rotation
        } else { // If only one image, hide arrows
            if(leftArrow) leftArrow.style.display = 'none';
            if(rightArrow) rightArrow.style.display = 'none';
        }

        // Add event listeners for arrows (only if there's more than one image)
        if (heroImages.length > 1) {
            if (leftArrow) {
                leftArrow.addEventListener('click', () => {
                    stopAutoSlide();
                    prevHeroSlide();
                    // Optional: restart auto slide after a delay if user stops interacting
                    // clearTimeout(autoSlideTimer);
                    // autoSlideTimer = setTimeout(startAutoSlide, 15000);
                });
            }

            if (rightArrow) {
                rightArrow.addEventListener('click', () => {
                    stopAutoSlide();
                    nextHeroSlide();
                     // Optional: restart auto slide after a delay if user stops interacting
                    // clearTimeout(autoSlideTimer);
                    // autoSlideTimer = setTimeout(startAutoSlide, 15000);
                });
            }
        }

         // Optional: Stop auto rotation when user leaves the page
         window.addEventListener('beforeunload', stopAutoSlide);

    </script>
</body>
</html> 