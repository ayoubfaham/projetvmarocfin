<?php
session_start();
require_once '../config/database.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conditions d'Utilisation - VMaroc</title>
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

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .terms-header {
            text-align: center;
            margin: 40px 0 60px;
            position: relative;
        }

        .terms-header::after {
            content: '';
            position: absolute;
            bottom: -20px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: var(--accent);
        }

        .terms-header h1 {
            font-size: 2.8rem;
            color: var(--primary);
            margin-bottom: 20px;
            font-weight: 800;
        }

        .terms-header p {
            color: var(--text-light);
            font-size: 1.2rem;
            font-weight: 500;
        }

        .terms-content {
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

        .highlight {
            background: linear-gradient(135deg, #f8f4ef, #fff);
            padding: 40px;
            border-radius: 20px;
            margin: 40px 0;
            border: 1px solid var(--accent);
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

            .terms-content {
                padding: 30px;
            }

            .terms-header h1 {
                font-size: 2rem;
            }

            h2 {
                font-size: 1.5rem;
            }
        }
    </style>
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
        <div class="terms-header">
            <h1>Conditions d'Utilisation</h1>
            <p>Veuillez lire attentivement les conditions d'utilisation de VMaroc</p>
        </div>

        <div class="terms-content">
            <div class="section">
                <div class="highlight">
                    <p>Dernière mise à jour : <?php echo date('d/m/Y'); ?></p>
                    <p>Bienvenue sur VMaroc. En utilisant notre site, vous acceptez les présentes conditions d'utilisation. Veuillez les lire attentivement.</p>
                </div>
            </div>

            <div class="section">
                <h2>1. Acceptation des Conditions</h2>
                <p>En accédant et en utilisant le site VMaroc, vous acceptez d'être lié par ces conditions d'utilisation, toutes les lois et réglementations applicables.</p>
            </div>

            <div class="section">
                <h2>2. Utilisation du Site</h2>
                <ul>
                    <li>Le contenu du site est fourni à titre informatif uniquement.</li>
                    <li>Vous vous engagez à ne pas utiliser le site à des fins illégales ou interdites.</li>
                    <li>Vous êtes responsable de la confidentialité de vos identifiants de connexion.</li>
                </ul>
            </div>

            <div class="section">
                <h2>3. Propriété Intellectuelle</h2>
                <p>Tout le contenu présent sur VMaroc (textes, images, logos, etc.) est protégé par les droits d'auteur et autres droits de propriété intellectuelle.</p>
            </div>

            <div class="section">
                <h2>4. Contenu Utilisateur</h2>
                <ul>
                    <li>En publiant du contenu sur VMaroc, vous nous accordez une licence non exclusive pour utiliser ce contenu.</li>
                    <li>Vous êtes responsable du contenu que vous publiez.</li>
                    <li>Nous nous réservons le droit de supprimer tout contenu inapproprié.</li>
                </ul>
            </div>

            <div class="section">
                <h2>5. Limitation de Responsabilité</h2>
                <p>VMaroc ne peut garantir l'exactitude, l'exhaustivité ou la pertinence des informations fournies sur le site.</p>
            </div>

            <div class="section">
                <h2>6. Modifications</h2>
                <p>Nous nous réservons le droit de modifier ces conditions d'utilisation à tout moment. Les modifications entrent en vigueur dès leur publication sur le site.</p>
            </div>

            <div class="contact-info">
                <h2>Contact</h2>
                <p>Pour toute question concernant ces conditions d'utilisation, contactez-nous :</p>
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
    <link rel="stylesheet" href="../css/footer.css">

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
                <p>© <?php echo date('Y'); ?> VMaroc. Tous droits réservés.</p>
                <p>
                    <a href="politique-confidentialite.php">Politique de confidentialité</a> |
                    <a href="conditions-utilisation.php">Conditions d'utilisation</a>
                </p>
            </div>
        </div>
    </footer>

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
</body>
</html> 