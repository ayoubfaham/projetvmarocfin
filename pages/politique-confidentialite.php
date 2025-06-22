<?php
session_start();
require_once '../config/database.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Politique de Confidentialité - VMaroc</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2D2926;
            --accent: #e9cba7;
            --text-dark: #2D2926;
            --text-light: #666666;
            --background: #F8F8F8;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            line-height: 1.6;
            color: var(--text-dark);
            background: var(--background);
            padding-top: 80px;
        }

        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            padding: 0;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            z-index: 1000;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border-bottom: 1px solid rgba(233, 203, 167, 0.2);
        }

        .header.scrolled {
            padding: 5px 0;
            background: rgba(255, 255, 255, 0.98);
            box-shadow: 0 5px 30px rgba(0, 0, 0, 0.1);
        }

        .header-container {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: center;
            max-width: 1440px;
            margin: 0 auto;
            padding: 15px 3vw;
            gap: 40px;
        }

        .logo {
            height: 55px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            filter: brightness(1.02);
            justify-self: start;
        }

        .logo:hover {
            transform: scale(1.05) rotate(-2deg);
            filter: brightness(1.1);
        }

        .nav-links {
            display: flex;
            gap: 40px;
            list-style: none;
            margin: 0;
            padding: 0;
            justify-self: center;
        }

        .nav-link {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            font-size: 1.05rem;
            position: relative;
            padding: 8px 0;
            transition: all 0.3s ease;
            letter-spacing: 0.3px;
        }

        .nav-link::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background: linear-gradient(to right, #e9cba7, #bfa14a);
            transform: scaleX(0);
            transform-origin: right;
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .nav-link:hover {
            color: #bfa14a;
        }

        .nav-link:hover::before {
            transform: scaleX(1);
            transform-origin: left;
        }

        .nav-buttons {
            display: flex;
            align-items: center;
            gap: 20px;
            justify-self: end;
        }

        .nav-btn {
            padding: 10px 24px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 0.95rem;
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            letter-spacing: 0.3px;
        }

        .btn-outline {
            background: transparent;
            color: #2D2926;
            border: 2px solid #e9cba7;
        }

        .btn-outline:hover {
            background: #e9cba7;
            color: #2D2926;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(233, 203, 167, 0.3);
        }

        .btn-solid {
            background: linear-gradient(135deg, #e9cba7, #bfa14a);
            color: #2D2926;
            border: none;
            box-shadow: 0 4px 15px rgba(233, 203, 167, 0.3);
        }

        .btn-solid:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(233, 203, 167, 0.4);
            filter: brightness(1.05);
        }

        .nav-btn i {
            margin-right: 8px;
            font-size: 1rem;
        }

        /* Ajout d'un effet de ripple sur les boutons */
        .nav-btn::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            background: radial-gradient(circle, rgba(255,255,255,0.8) 0%, rgba(255,255,255,0) 100%);
            opacity: 0;
            transform: scale(2);
            transition: opacity 0.5s ease, transform 0.5s ease;
        }

        .nav-btn:active::after {
            opacity: 0.3;
            transform: scale(0);
            transition: 0s;
        }

        @media (max-width: 1024px) {
            .logo-section {
                gap: 30px;
            }

            .nav-links {
                gap: 25px;
            }

            .nav-link {
                font-size: 1rem;
            }
        }

        @media (max-width: 768px) {
            .header {
                padding: 10px 0;
            }

            .header-container {
                padding: 5px 20px;
            }

            .logo {
                height: 45px;
            }

            .nav-links {
                display: none;
            }

            .nav-buttons {
                gap: 10px;
            }

            .nav-btn {
                padding: 8px 16px;
                font-size: 0.9rem;
            }
        }

        /* Animation pour le header au scroll */
        @keyframes headerSlideDown {
            from {
                transform: translateY(-100%);
            }
            to {
                transform: translateY(0);
            }
        }

        .header.scrolled {
            animation: headerSlideDown 0.5s ease forwards;
        }

        /* Styles du corps et du footer restaurés */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .privacy-header {
            text-align: center;
            margin: 40px 0 60px;
            position: relative;
        }

        .privacy-header::after {
            content: '';
            position: absolute;
            bottom: -20px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: var(--accent);
        }

        .privacy-header h1 {
            font-size: 2.8rem;
            color: var(--primary);
            margin-bottom: 20px;
            font-weight: 800;
        }

        .privacy-header p {
            color: var(--text-light);
            font-size: 1.2rem;
            font-weight: 500;
        }

        .privacy-content {
            background: white;
            padding: 60px;
            border-radius: 30px;
            box-shadow: 0 10px 40px rgba(233, 203, 167, 0.15);
            margin-bottom: 60px;
            border: 1px solid rgba(233, 203, 167, 0.2);
        }

        .section {
            margin-bottom: 50px;
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 0.6s forwards;
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .section:nth-child(1) { animation-delay: 0.1s; }
        .section:nth-child(2) { animation-delay: 0.2s; }
        .section:nth-child(3) { animation-delay: 0.3s; }
        .section:nth-child(4) { animation-delay: 0.4s; }
        .section:nth-child(5) { animation-delay: 0.5s; }
        .section:nth-child(6) { animation-delay: 0.6s; }
        .section:nth-child(7) { animation-delay: 0.7s; }

        h2 {
            color: var(--primary);
            font-size: 1.8rem;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--accent);
            font-weight: 700;
        }

        p {
            margin-bottom: 20px;
            color: var(--text-light);
            font-size: 1.1rem;
            line-height: 1.8;
        }

        ul {
            margin: 0 0 20px 20px;
            color: var(--text-light);
        }

        li {
            margin-bottom: 12px;
            font-size: 1.1rem;
            position: relative;
            padding-left: 25px;
        }

        li::before {
            content: '→';
            position: absolute;
            left: 0;
            color: var(--accent);
        }

        .contact-info {
            background: linear-gradient(135deg, #f8f4ef, #fff);
            padding: 40px;
            border-radius: 20px;
            margin-top: 40px;
            border: 1px solid var(--accent);
        }

        .contact-info h2 {
            border-bottom: none;
            color: var(--primary);
            margin-bottom: 20px;
            padding-bottom: 0;
        }

        .contact-info ul {
            list-style: none;
            margin-left: 0;
        }

        .contact-info li {
            display: flex;
            align-items: center;
            gap: 15px;
            padding-left: 0;
        }

        .contact-info li::before {
            content: none;
        }

        .contact-info i {
            color: var(--accent);
            font-size: 1.2rem;
            width: 24px;
        }

        .date-update {
            text-align: right;
            font-style: italic;
            color: var(--text-light);
            margin-top: 40px;
            font-size: 0.95rem;
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }

            .privacy-content {
                padding: 30px;
            }

            .privacy-header h1 {
                font-size: 2rem;
            }

            h2 {
                font-size: 1.5rem;
            }
        }

        /* Footer Styles */
        footer {
            background: linear-gradient(135deg, #2D2926 0%, #1a1715 100%);
            color: #f5f5f5;
            padding: 80px 0 40px;
            position: relative;
            overflow: hidden;
            margin-top: 80px;
        }

        footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(to right, #e9cba7, #bfa14a);
        }

        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 50px;
            margin-bottom: 60px;
        }

        .footer-col {
            position: relative;
            opacity: 0;
            transform: translateY(20px);
            animation: footerFadeIn 0.6s forwards;
        }

        @keyframes footerFadeIn {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .footer-col:nth-child(1) { animation-delay: 0.1s; }
        .footer-col:nth-child(2) { animation-delay: 0.2s; }
        .footer-col:nth-child(3) { animation-delay: 0.3s; }
        .footer-col:nth-child(4) { animation-delay: 0.4s; }

        .footer-logo {
            display: block;
            margin-bottom: 25px;
            transition: transform 0.3s ease;
        }

        .footer-logo:hover {
            transform: scale(1.05);
        }

        .footer-logo img {
            height: 60px;
            width: auto;
            filter: brightness(0) invert(1);
        }

        .footer-col h3 {
            color: #e9cba7;
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 25px;
            position: relative;
            padding-bottom: 15px;
        }

        .footer-col h3::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 40px;
            height: 3px;
            background: #e9cba7;
            transition: width 0.3s ease;
        }

        .footer-col:hover h3::after {
            width: 60px;
        }

        .footer-col p {
            color: #e0e0e0;
            font-size: 1rem;
            line-height: 1.8;
            margin-bottom: 20px;
        }

        .footer-col ul {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .footer-col ul li {
            margin-bottom: 12px;
            padding-left: 0;
        }

        .footer-col ul li::before {
            content: none;
        }

        .footer-col ul li a {
            color: #f5f5f5;
            text-decoration: none;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-block;
            position: relative;
            padding-left: 20px;
        }

        .footer-col ul li a::before {
            content: '→';
            position: absolute;
            left: 0;
            opacity: 0;
            transform: translateX(-10px);
            transition: all 0.3s ease;
            color: #e9cba7;
        }

        .footer-col ul li a:hover {
            color: #e9cba7;
            padding-left: 25px;
        }

        .footer-col ul li a:hover::before {
            opacity: 1;
            transform: translateX(0);
        }

        .social-links {
            display: flex;
            gap: 15px;
            margin-top: 25px;
        }

        .social-links a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: rgba(233, 203, 167, 0.1);
            border-radius: 50%;
            color: #e9cba7;
            font-size: 1.2rem;
            transition: all 0.3s ease;
            border: 1px solid rgba(233, 203, 167, 0.2);
        }

        .social-links a:hover {
            background: #e9cba7;
            color: #2D2926;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(233, 203, 167, 0.3);
        }

        .footer-col i {
            width: 20px;
            margin-right: 10px;
            color: #e9cba7;
        }

        .copyright {
            text-align: center;
            padding-top: 40px;
            margin-top: 40px;
            border-top: 1px solid rgba(233, 203, 167, 0.1);
            position: relative;
        }

        .copyright::before {
            content: '';
            position: absolute;
            top: -1px;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 2px;
            background: linear-gradient(to right, transparent, #e9cba7, transparent);
        }

        .copyright p {
            color: #e0e0e0;
            font-size: 0.95rem;
            margin: 10px 0;
        }

        .copyright a {
            color: #e9cba7;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            margin: 0 10px;
            position: relative;
        }

        .copyright a::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 1px;
            background: #e9cba7;
            transition: width 0.3s ease;
        }

        .copyright a:hover {
            color: #fff;
        }

        .copyright a:hover::after {
            width: 100%;
        }

        @media (max-width: 768px) {
            footer {
                padding: 60px 0 30px;
            }

            .footer-grid {
                grid-template-columns: 1fr;
                gap: 40px;
            }

            .footer-col {
                text-align: center;
            }

            .footer-col h3::after {
                left: 50%;
                transform: translateX(-50%);
            }

            .social-links {
                justify-content: center;
            }

            .footer-col ul li a {
                padding-left: 0;
            }

            .footer-col ul li a:hover {
                padding-left: 5px;
            }
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const header = document.querySelector('.header');
            let lastScroll = 0;

            window.addEventListener('scroll', () => {
                const currentScroll = window.pageYOffset;

                if (currentScroll > lastScroll && currentScroll > 100) {
                    header.classList.add('scrolled');
                } else {
                    header.classList.remove('scrolled');
                }

                lastScroll = currentScroll;
            });
        });
    </script>
</head>
<body>
    <header class="header">
        <div class="header-container">
            <a href="../index.php">
                <img src="https://i.postimg.cc/g07GgLp5/VMaroc-logo-trf.png" alt="VMaroc" class="logo">
            </a>
            <nav class="nav-links">
                <a href="../index.php" class="nav-link">Accueil</a>
                <a href="../destinations.php" class="nav-link">Destinations</a>
                <a href="../recommandations.php" class="nav-link">Recommandations</a>
            </nav>
            <div class="nav-buttons">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                        <a href="admin-panel.php" class="nav-btn btn-outline"><i class="fas fa-cog"></i>Panel Admin</a>
                    <?php endif; ?>
                    <a href="../logout.php" class="nav-btn btn-solid"><i class="fas fa-sign-out-alt"></i>Déconnexion</a>
                <?php else: ?>
                    <a href="../login.php" class="nav-btn btn-outline"><i class="fas fa-sign-in-alt"></i>Connexion</a>
                    <a href="../register.php" class="nav-btn btn-solid"><i class="fas fa-user-plus"></i>Inscription</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="privacy-header">
            <h1>Politique de Confidentialité</h1>
            <p>VMaroc s'engage à protéger votre vie privée et vos données personnelles</p>
        </div>

        <div class="privacy-content">
            <div class="section">
                <h2>1. Introduction</h2>
                <p>Chez VMaroc, nous accordons une grande importance à la protection de vos données personnelles. Cette politique de confidentialité explique comment nous collectons, utilisons et protégeons vos informations lorsque vous utilisez notre site web et nos services.</p>
            </div>

            <div class="section">
                <h2>2. Collecte des Données</h2>
                <p>Nous collectons les informations suivantes :</p>
                <ul>
                    <li>Informations d'identification (nom, prénom, email)</li>
                    <li>Données de connexion et d'utilisation du site</li>
                    <li>Préférences de voyage et historique des réservations</li>
                    <li>Commentaires et avis sur les destinations</li>
                </ul>
            </div>

            <div class="section">
                <h2>3. Utilisation des Données</h2>
                <p>Vos données sont utilisées pour :</p>
                <ul>
                    <li>Personnaliser votre expérience utilisateur</li>
                    <li>Gérer vos réservations et demandes</li>
                    <li>Améliorer nos services et communications</li>
                    <li>Assurer la sécurité de votre compte</li>
                </ul>
            </div>

            <div class="section">
                <h2>4. Protection des Données</h2>
                <p>Nous mettons en œuvre des mesures de sécurité techniques et organisationnelles pour protéger vos données contre :</p>
                <ul>
                    <li>L'accès non autorisé</li>
                    <li>La modification ou la divulgation</li>
                    <li>La destruction accidentelle ou illégale</li>
                </ul>
            </div>

            <div class="section">
                <h2>5. Vos Droits</h2>
                <p>Conformément à la réglementation, vous disposez des droits suivants :</p>
                <ul>
                    <li>Droit d'accès à vos données</li>
                    <li>Droit de rectification</li>
                    <li>Droit à l'effacement</li>
                    <li>Droit à la limitation du traitement</li>
                    <li>Droit à la portabilité des données</li>
                </ul>
            </div>

            <div class="section">
                <h2>6. Cookies</h2>
                <p>Notre site utilise des cookies pour améliorer votre expérience de navigation. Vous pouvez contrôler l'utilisation des cookies dans les paramètres de votre navigateur.</p>
            </div>

            <div class="section">
                <h2>7. Modifications</h2>
                <p>Nous nous réservons le droit de modifier cette politique de confidentialité à tout moment. Les modifications seront publiées sur cette page avec la date de mise à jour.</p>
            </div>

            <div class="contact-info">
                <h2>Contact</h2>
                <p>Pour toute question concernant notre politique de confidentialité, contactez-nous :</p>
                <ul>
                    <li><i class="fas fa-envelope"></i> contact@vmaroc.com</li>
                    <li><i class="fas fa-phone"></i> +212 522 123 456</li>
                    <li><i class="fas fa-map-marker-alt"></i> Avenue Mohammed V, Casablanca, Maroc</li>
                </ul>
            </div>

            <div class="date-update">
                <p>Dernière mise à jour : <?php echo date('d/m/Y'); ?></p>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <a href="../index.php" class="footer-logo">
                        <img src="https://i.postimg.cc/g07GgLp5/VMaroc-logo-trf.png" alt="VMaroc Logo">
                    </a>
                    <p>Découvrez les merveilles du Maroc avec VMaroc, votre guide de voyage personnalisé pour une expérience authentique et inoubliable.</p>
                    <div class="social-links">
                        <a href="#" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" title="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" title="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" title="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                <div class="footer-col">
                    <h3>Navigation</h3>
                    <ul>
                        <li><a href="../index.php">Accueil</a></li>
                        <li><a href="../destinations.php">Destinations</a></li>
                        <li><a href="../recommandations.php">Recommandations</a></li>
                        <li><a href="../about.php">À propos</a></li>
                        <li><a href="../contact.php">Contact</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h3>Destinations Populaires</h3>
                    <ul>
                        <?php
                        try {
                            $stmt = $pdo->prepare("SELECT id, nom FROM villes WHERE LOWER(nom) IN ('marrakech', 'casablanca', 'fès', 'chefchaouen')");
                            $stmt->execute();
                            while ($ville = $stmt->fetch()) {
                                echo '<li><a href="../city.php?id=' . $ville['id'] . '">' . $ville['nom'] . '</a></li>';
                            }
                        } catch (PDOException $e) {
                            error_log("Erreur lors de la récupération des villes: " . $e->getMessage());
                        }
                        ?>
                        <li><a href="../destinations.php">Toutes les destinations</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h3>Contact</h3>
                    <p><i class="fas fa-envelope"></i> contact@vmaroc.com</p>
                    <p><i class="fas fa-phone"></i> +212 522 123 456</p>
                    <p><i class="fas fa-map-marker-alt"></i> Avenue Mohammed V, Casablanca, Maroc</p>
                </div>
            </div>
            <div class="copyright">
                <p>© 2025 VMaroc. Tous droits réservés.</p>
                <p>
                    <a href="politique-confidentialite.php">Politique de confidentialité</a> |
                    <a href="conditions-utilisation.php">Conditions d'utilisation</a>
                </p>
            </div>
        </div>
    </footer>
</body>
</html> 