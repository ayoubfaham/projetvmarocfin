<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=vmaroc", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Initialiser les statistiques par défaut
    $stats = [
        'cities' => 0,
        'users' => 0,
        'reviews' => 0
    ];

    // Récupérer les statistiques si les tables existent
    try {
        $stats['cities'] = $pdo->query("SELECT COUNT(*) FROM cities")->fetchColumn();
    } catch (PDOException $e) {}

    try {
        $stats['users'] = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    } catch (PDOException $e) {}

    try {
        $stats['reviews'] = $pdo->query("SELECT COUNT(*) FROM reviews")->fetchColumn();
    } catch (PDOException $e) {}

    // Initialiser les activités récentes
    $recentActivities = [];

    // Récupérer les dernières activités si les tables existent
    try {
        $recentActivities = $pdo->query("
            SELECT 'city' as type, name as title, created_at 
            FROM cities 
            UNION ALL
            SELECT 'review' as type, comment as title, created_at 
            FROM reviews
            ORDER BY created_at DESC 
            LIMIT 5
        ")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {}

} catch(PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>VMaroc - Administration</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #1B4B66;
            --accent: #4CA771;
            --white: #ffffff;
            --text-dark: #1A1A1A;
            --text-light: #666666;
            --overlay: rgba(0, 0, 0, 0.4);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            color: var(--white);
            overflow-x: hidden;
            position: relative;
        }

        .background-slideshow {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
        }

        .background-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0;
            transition: opacity 1s ease-in-out;
        }

        .background-image.active {
            opacity: 1;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.2), rgba(0, 0, 0, 0.2));
            z-index: -1;
        }

        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            padding: 1.5rem 3rem;
            background: rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(8px);
            z-index: 100;
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .logo-section {
            display: flex;
            align-items: center;
        }

        .logo {
            height: 65px;
            filter: brightness(0) invert(1);
            transition: transform 0.3s ease;
        }

        .logo:hover {
            transform: scale(1.1);
        }

        .nav-links {
            display: flex;
            gap: 3rem;
            justify-content: center;
        }

        .nav-link {
            color: var(--white);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
            font-size: 1.1rem;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }

        .nav-link[href*="destinations"] {
            color: var(--accent);
            font-weight: 600;
            opacity: 1;
        }

        .nav-link[href*="destinations"]::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 100%;
            height: 2px;
            background: var(--accent);
        }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--accent);
            transition: width 0.3s ease;
        }

        .nav-link:hover {
            opacity: 1;
        }

        .nav-link:hover::after {
            width: 100%;
        }

        .nav-link.active {
            color: var(--accent);
            font-weight: 700;
            opacity: 1;
            font-size: 1.15rem;
        }

        .nav-link.active::after {
            width: 100%;
            background: var(--accent);
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 2.5rem;
            justify-content: flex-end;
        }

        .nav-link.logout {
            padding: 0.8rem 2rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            font-weight: 600;
            letter-spacing: 1px;
        }

        .nav-link.logout:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .search-bar {
            display: flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50px;
            padding: 0.5rem 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }

        .search-bar:hover {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.3);
        }

        .search-bar input {
            background: none;
            border: none;
            color: var(--white);
            outline: none;
            padding: 0.5rem;
            width: 200px;
            font-size: 0.9rem;
        }

        .search-bar input::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .user-actions {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .notification-bell {
            position: relative;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .notification-bell:hover {
            transform: scale(1.1);
        }

        .notification-dot {
            position: absolute;
            top: -5px;
            right: -5px;
            width: 8px;
            height: 8px;
            background: var(--accent);
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.5); opacity: 0.5; }
            100% { transform: scale(1); opacity: 1; }
        }

        .profile-section {
            display: flex;
            align-items: center;
            gap: 1rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 50px;
            transition: all 0.3s ease;
        }

        .profile-section:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .profile-image {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }

        .profile-section:hover .profile-image {
            border-color: var(--accent);
        }

        .dashboard {
            max-width: 100%;
            min-height: 100vh;
            padding: 2rem;
            display: flex;
            align-items: center;
            position: relative;
        }

        .left-section {
            flex: 1;
            padding: 2rem;
            position: relative;
        }

        .decorative-elements {
            position: absolute;
            font-size: 24px;
            color: var(--white);
            opacity: 0.3;
        }

        .arrows-top-left {
            top: 0;
            left: 0;
        }

        .dots-top-right {
            top: 0;
            right: 20%;
        }

        .arrows-bottom-right {
            bottom: 0;
            right: 20%;
        }

        .dots-bottom-left {
            bottom: 0;
            left: 0;
        }

        .main-title {
            font-size: 4.5rem;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 0;
            letter-spacing: -1px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }

        .subtitle {
            font-size: 4.5rem;
            color: #4CA771;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 2rem;
            letter-spacing: -1px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }

        .description {
            font-size: 1rem;
            line-height: 1.6;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 2rem;
            max-width: 500px;
            font-weight: 400;
        }

        .cards-container {
            flex: 1.5;
            position: relative;
            height: 700px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            padding: 40px 0;
        }

        .admin-card {
            position: absolute;
            width: 360px;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            opacity: 0;
            pointer-events: none;
            margin: 20px 0;
        }

        .admin-card.prev {
            transform: translateY(-100%) scale(0.85);
            opacity: 0.7;
            z-index: 1;
            pointer-events: auto;
            filter: brightness(0.9);
        }

        .admin-card.active {
            transform: translateY(0) scale(1);
            opacity: 1;
            z-index: 3;
            pointer-events: auto;
        }

        .admin-card.next {
            transform: translateY(100%) scale(0.85);
            opacity: 0.7;
            z-index: 1;
            pointer-events: auto;
            filter: brightness(0.9);
        }

        .card-image {
            width: 100%;
            height: 220px;
            object-fit: cover;
            transition: all 0.5s ease;
        }

        .card-content {
            padding: 1.5rem 2rem;
            background: white;
        }

        .card-title {
            font-size: 2.2rem;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 1.2rem;
            font-family: 'Plus Jakarta Sans', sans-serif;
            letter-spacing: -0.5px;
        }

        .card-description {
            font-size: 0.95rem;
            line-height: 1.5;
            color: var(--text-light);
            margin-bottom: 1.5rem;
        }

        .read-more {
            display: inline-flex;
            align-items: center;
            padding: 0.7rem 1.8rem;
            background: #1a1a1a;
            border-radius: 50px;
            color: white;
            text-decoration: none;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            gap: 0.6rem;
        }

        .read-more::after {
            content: '→';
            transition: transform 0.3s ease;
            font-size: 1.4rem;
            margin-top: -2px;
        }

        .read-more:hover {
            background: #000;
            transform: translateY(-2px);
        }

        .read-more:hover::after {
            transform: translateX(5px);
        }

        .nav-arrow {
            position: absolute;
            width: 50px;
            height: 50px;
            background: rgba(76, 167, 113, 0.95);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 10;
            left: 125px;
            transform: none;
            transition: all 0.3s ease;
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            font-size: 1.4rem;
            color: #fff;
        }

        .nav-arrow:hover {
            background: rgba(76, 167, 113, 1);
            transform: scale(1.1);
        }

        .nav-arrow.prev {
            top: 35%;
        }

        .nav-arrow.next {
            top: 55%;
        }

        .nav-arrow.prev::before {
            content: "\f077";
        }

        .nav-arrow.next::before {
            content: "\f078";
        }

        .admin-card:hover .card-image {
            transform: scale(1.05);
        }

        @media (max-width: 1400px) {
            .main-title, .subtitle {
                font-size: 4rem;
            }

            .admin-card {
                width: 340px;
            }

            .card-title {
                font-size: 2rem;
            }
        }

        @media (max-width: 1200px) {
            .dashboard {
                flex-direction: column;
                padding: 1.5rem;
            }

            .left-section {
                padding: 1.5rem;
                text-align: center;
                margin-bottom: 2rem;
            }

            .main-title, .subtitle {
                font-size: 3.5rem;
            }

            .description {
                margin: 0 auto 2rem;
            }

            .cards-container {
                width: 100%;
                height: 500px;
            }

            .admin-card {
                width: 320px;
            }

            .card-title {
                font-size: 1.8rem;
            }

            .nav-arrow {
                left: 125px;
            }
        }

        @media (max-width: 768px) {
            .header {
                padding: 1rem;
            }

            .logo-section {
                gap: 2rem;
            }

            .nav-links {
                gap: 1.5rem;
            }

            .nav-link {
                font-size: 1rem;
            }

            .logo {
                height: 45px;
            }

            .main-title, .subtitle {
                font-size: 3rem;
            }

            .admin-card {
                width: 300px;
            }

            .card-title {
                font-size: 1.6rem;
            }

            .nav-arrow {
                width: 40px;
                height: 40px;
                font-size: 1.1rem;
                left: 125px;
            }

            .nav-arrow.prev {
                top: 37.5%;
            }

            .nav-arrow.next {
                top: 52.5%;
            }
        }

        /* Nouveau style pour le footer */
        footer {
            background: rgba(27, 75, 102, 0.95);
            backdrop-filter: blur(10px);
            padding: 4rem 0 2rem;
            margin-top: 4rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 4rem;
            margin-bottom: 3rem;
        }

        .footer-col {
            color: var(--white);
        }

        .footer-col .logo-img {
            height: 60px;
            margin-bottom: 1.5rem;
            filter: brightness(0) invert(1);
            transition: transform 0.3s ease;
        }

        .footer-col .logo-img:hover {
            transform: scale(1.05);
        }

        .footer-col p {
            color: rgba(255, 255, 255, 0.8);
            line-height: 1.6;
            font-size: 0.95rem;
            margin-bottom: 1rem;
        }

        .footer-col h3 {
            color: var(--white);
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            position: relative;
        }

        .footer-col h3::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            width: 40px;
            height: 2px;
            background: var(--accent);
        }

        .footer-col ul {
            list-style: none;
            padding: 0;
        }

        .footer-col ul li {
            margin-bottom: 1rem;
        }

        .footer-col ul li a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-block;
            position: relative;
        }

        .footer-col ul li a::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 1px;
            background: var(--accent);
            transition: width 0.3s ease;
        }

        .footer-col ul li a:hover {
            color: var(--accent);
            transform: translateX(5px);
        }

        .footer-col ul li a:hover::after {
            width: 100%;
        }

        .footer-contact {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .footer-contact i {
            color: var(--accent);
            font-size: 1.2rem;
        }

        .footer-social {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .social-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .social-icon:hover {
            background: var(--accent);
            transform: translateY(-3px);
        }

        .copyright {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 2rem;
            text-align: center;
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.9rem;
        }

        .copyright a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .copyright a:hover {
            color: var(--accent);
        }

        @media (max-width: 768px) {
            .footer-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .footer-col {
                text-align: center;
            }

            .footer-col h3::after {
                left: 50%;
                transform: translateX(-50%);
            }

            .footer-social {
                justify-content: center;
            }

            .footer-contact {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="background-slideshow">
        <img src="../images/paneladmin.png" alt="Background 1" class="background-image active">
        <img src="../images/paneladmin1.png" alt="Background 2" class="background-image">
        <img src="../images/paneladmin2.png" alt="Background 3" class="background-image">
    </div>

    <script>
        // Ajout du code pour le diaporama
        document.addEventListener('DOMContentLoaded', function() {
            const images = document.querySelectorAll('.background-image');
            let currentImage = 0;

            function nextImage() {
                images[currentImage].classList.remove('active');
                currentImage = (currentImage + 1) % images.length;
                images[currentImage].classList.add('active');
            }

            // Change d'image toutes les 5 secondes
            setInterval(nextImage, 5000);
        });
    </script>

    <header class="header">
        <div class="logo-section">
            <a href="../index.php">
                <img src="https://i.postimg.cc/g07GgLp5/VMaroc-logo-trf.png" alt="VMaroc" class="logo">
            </a>
        </div>
        <nav class="nav-links">
            <a href="../index.php" class="nav-link">Accueil</a>
            <a href="../destinations.php" class="nav-link">Destinations</a>
            <a href="../recommandations.php" class="nav-link">Recommandations</a>
        </nav>
        <div class="header-actions">
            <a href="admin-panel.php" class="nav-link active">Panel Admin</a>
            <a href="logout.php" class="nav-link logout">Déconnexion</a>
        </div>
    </header>

    <div class="dashboard">
        <div class="left-section">
            <div class="decorative-elements arrows-top-left">›››››</div>
            <div class="decorative-elements dots-top-right">
                • • • • •<br>
                • • • • •
            </div>
            <h1 class="main-title">Pannel</h1>
            <div class="subtitle">Administrateur</div>
            
            <div class="decorative-elements dots-bottom-left">
                
                • • • • •<br>
                • • • • •
            </div>
            <div class="decorative-elements arrows-bottom-right">›››››</div>
        </div>

        <div class="cards-container">
                <div class="admin-card">
                <div class="card-content">
                    <h2 class="card-title">Gérer les villes</h2>
                    <a href="admin-cities.php" class="read-more">Gérer</a>
                </div>
            </div>

                <div class="admin-card">
                <div class="card-content">
                    <h2 class="card-title">Gérer les lieux</h2>
                    <a href="admin-places.php" class="read-more">Gérer</a>
                </div>
            </div>

                <div class="admin-card">
                <div class="card-content">
                    <h2 class="card-title">Gérer les utilisateurs</h2>
                    <a href="admin-users.php" class="read-more">Gérer</a>
                </div>
            </div>

                <div class="admin-card">
                <div class="card-content">
                    <h2 class="card-title">Gérer les avis</h2>
                    <a href="admin-reviews.php" class="read-more">Gérer</a>
                </div>
            </div>

            <div class="nav-arrow prev"></div>
            <div class="nav-arrow next"></div>
            </div>
    </div>

    <footer>
        <div class="footer-container">
            <div class="footer-grid">
                <div class="footer-col">
                    <img src="https://i.postimg.cc/g07GgLp5/VMaroc-logo-trf.png" alt="VMaroc Logo" class="logo-img">
                    <p>Découvrez les merveilles du Maroc avec VMaroc, votre guide de voyage personnalisé. Nous vous accompagnons dans la découverte des trésors cachés et des expériences authentiques du royaume.</p>
                    <div class="footer-social">
                        <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                <div class="footer-col">
                    <h3>Liens Rapides</h3>
                    <ul>
                        <li><a href="../index.php">Accueil</a></li>
                        <li><a href="../destinations.php">Destinations</a></li>
                        <li><a href="../recommandations.php">Recommandations</a></li>
                        <li><a href="../blog.php">Blog Voyage</a></li>
                        <li><a href="../contact.php">Contactez-nous</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h3>Contact</h3>
                    <div class="footer-contact">
                        <i class="fas fa-envelope"></i>
                        <p>contact@marocauthentique.com</p>
                    </div>
                    <div class="footer-contact">
                        <i class="fas fa-phone"></i>
                        <p>+212 522 123 456</p>
                    </div>
                    <div class="footer-contact">
                        <i class="fas fa-map-marker-alt"></i>
                        <p>123 Avenue Mohammed V, Casablanca, Maroc</p>
                    </div>
                </div>
            </div>
            <div class="copyright">
                <p>© 2025 Maroc Authentique. Tous droits réservés.</p>
                <p>
                    <a href="politique-confidentialite.php">Politique de confidentialité</a> |
                    <a href="conditions-utilisation.php">Conditions d'utilisation</a> |
                </p>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Slideshow functionality
            const slides = document.querySelectorAll('.bg-slide');
            let currentSlide = 0;

            function nextSlide() {
                slides[currentSlide].style.opacity = '0';
                currentSlide = (currentSlide + 1) % slides.length;
                slides[currentSlide].style.opacity = '1';
            }

            // Change slide every 5 seconds
            setInterval(nextSlide, 5000);

            // Rest of your existing JavaScript...
            const cards = Array.from(document.querySelectorAll('.admin-card'));
            const prevBtn = document.querySelector('.nav-arrow.prev');
            const nextBtn = document.querySelector('.nav-arrow.next');
            let currentIndex = 0;

            function updateCards() {
                cards.forEach((card, index) => {
                    card.classList.remove('prev', 'active', 'next');
                    
                    if (index === currentIndex) {
                        card.classList.add('active');
                    } else if (index === getPrevIndex()) {
                        card.classList.add('prev');
                    } else if (index === getNextIndex()) {
                        card.classList.add('next');
                    }
                });

                updateProgressDots();
            }

            function getPrevIndex() {
                return (currentIndex - 1 + cards.length) % cards.length;
            }

            function getNextIndex() {
                return (currentIndex + 1) % cards.length;
            }

            function slideNext() {
                currentIndex = getNextIndex();
                updateCards();
            }

            function slidePrev() {
                currentIndex = getPrevIndex();
                updateCards();
            }

            // Create progress dots
            const dotsContainer = document.createElement('div');
            dotsContainer.className = 'progress-dots';
            
            cards.forEach((_, index) => {
                const dot = document.createElement('div');
                dot.className = 'progress-dot';
                dot.addEventListener('click', () => {
                    currentIndex = index;
                    updateCards();
                });
                dotsContainer.appendChild(dot);
            });
            
            document.querySelector('.cards-container').appendChild(dotsContainer);

            function updateProgressDots() {
                const dots = dotsContainer.querySelectorAll('.progress-dot');
                dots.forEach((dot, index) => {
                    dot.classList.toggle('active', index === currentIndex);
                });
            }

            // Initialize
            updateCards();

            // Event Listeners
            prevBtn.addEventListener('click', slidePrev);
            nextBtn.addEventListener('click', slideNext);

            // Keyboard Navigation
            document.addEventListener('keydown', (e) => {
                if (e.key === 'ArrowLeft') slidePrev();
                if (e.key === 'ArrowRight') slideNext();
            });

            // Touch Support
            let touchStartX = 0;
            const container = document.querySelector('.cards-container');

            container.addEventListener('touchstart', (e) => {
                touchStartX = e.touches[0].clientX;
                stopAutoRotate();
            });

            container.addEventListener('touchend', (e) => {
                const touchEndX = e.changedTouches[0].clientX;
                const diff = touchStartX - touchEndX;

                if (Math.abs(diff) > 50) {
                    if (diff > 0) slideNext();
                    else slidePrev();
                }

                setTimeout(startAutoRotate, 3000);
            });

            // Auto-rotate
            let autoRotateInterval;
            
            const startAutoRotate = () => {
                autoRotateInterval = setInterval(slideNext, 5000);
            };

            const stopAutoRotate = () => {
                clearInterval(autoRotateInterval);
            };

            // Start auto-rotation
            startAutoRotate();

            // Stop on user interaction
            container.addEventListener('mouseenter', stopAutoRotate);
            container.addEventListener('mouseleave', startAutoRotate);

            // Card click handling
            cards.forEach(card => {
                card.addEventListener('click', () => {
                    const index = cards.indexOf(card);
                    if (index !== currentIndex) {
                        currentIndex = index;
                        updateCards();
                    }
                });
            });
        });
    </script>
</body>
</html> 