<?php
session_start();
require_once 'config/database.php';

// Redirection si déjà connecté
if (isset($_SESSION['user_id'])) {
    header('Location: profile.php');
    exit;
}

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_nom'] = $user['nom'];
        $_SESSION['user_role'] = $user['role'] ?? 'user';

        if ($user['role'] === 'admin') {
            header('Location: pages/admin-panel.php');
        } else {
            header('Location: profile.php');
        }
        exit;
    } else {
        $error_message = "Email ou mot de passe incorrect.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - VMaroc</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/montserrat-font.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/header.css">
    <style>
        :root {
            --primary-color: #e30613;
            --secondary-color: #004aad;
            --text-color: #333;
            --white: #fff;
            --error: #dc3545;
            --shadow-sm: 0 2px 5px rgba(0,0,0,0.1);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.15);
            --transition: all 0.3s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            color: var(--text-color);
            line-height: 1.6;
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        /* Masquer le header original pour cette page */
        header {
            display: none;
        }
        
        /* Page de connexion avec image d'arrière-plan */
        .login-page {
            min-height: 100vh;
            width: 100%;
            background: url('images/login1.png') center/cover no-repeat;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        
        /* Logo VMaroc en haut */
        .vmaroc-logo {
            width: 100px;
            height: auto;
            position: absolute;
            top: 30px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 3;
        }
        
        /* Formulaire de connexion */
        .login-content {
            position: absolute;
            bottom: 20%;
            left: 50%;
            transform: translateX(-50%);
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100%;
            max-width: 450px;
            padding: 25px;
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.6);
            background-color: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(5px);
        }
        /* Formulaire de connexion */
        .login-form {
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
        }
        
        .input-group {
            position: relative;
            width: 100%;
        }
        
        .input-group input {
            width: 100%;
            padding: 12px 40px 12px 40px;
            border: 1px solid rgba(255, 255, 255, 0.6);
            border-radius: 50px;
            font-size: 1rem;
            background-color: var(--white);
            box-shadow: var(--shadow-sm);
        }
        
        .input-group input:focus {
            outline: none;
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.3);
        }
        
        .input-group .icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            z-index: 2;
        }
        
        .toggle-pw {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #999;
            font-size: 1rem;
            padding: 5px;
            z-index: 2;
        }
        
        .login-form button[type="submit"] {
            background-color: var(--secondary-color);
            color: var(--white);
            border: none;
            padding: 12px 20px;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            width: 100%;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            margin-top: 0.5rem;
        }
        
        .login-form button[type="submit"]:hover {
            background: #0056c8;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        .login-form button[type="submit"]:active {
            transform: translateY(0);
        }
        
        /* Liens en bas */
        .login-links {
            display: flex;
            justify-content: space-between;
            width: 100%;
            margin-top: 1rem;
            font-size: 0.85rem;
        }
        
        .login-links a {
            color: white;
            text-decoration: none;
            background-color: rgba(255,255,255,0.2);
            padding: 8px 15px;
            border-radius: 20px;
            backdrop-filter: blur(5px);
        }
        
        .login-links a:hover {
            background-color: rgba(255,255,255,0.3);
        }
        
        .error-message {
            color: var(--error);
            background: rgba(220, 53, 69, 0.1);
            border-left: 4px solid var(--error);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .error-message i {
            font-size: 1.2rem;
        }
        /* Media Queries */
        @media (max-width: 768px) {
            .maroc-emblem {
                width: 80px;
                top: 60px;
            }
            
            .vmaroc-logo {
                width: 90%;
                top: 35%;
            }
            
            .login-content {
                bottom: 10%;
                max-width: 90%;
            }
        }
        
        @media (max-width: 480px) {
            .maroc-emblem {
                width: 60px;
                top: 40px;
            }
            
            .login-links {
                flex-direction: column;
                gap: 10px;
                align-items: center;
            }
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

    <div class="login-page">
        <!-- Logo VMaroc en haut avec lien vers la page d'accueil -->
        <a href="index.php">
            <img src="https://i.postimg.cc/g07GgLp5/VMaroc-logo-trf.png" alt="VMaroc Logo" class="vmaroc-logo">
        </a>
        
        <div class="login-content">
            
            <?php if (isset($error_message)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="login-form" autocomplete="off">
                <div class="input-group">
                    <span class="icon"><i class="fas fa-user"></i></span>
                    <input type="text" name="email" placeholder="Nom d'utilisateur" required>
                </div>
                
                <div class="input-group">
                    <span class="icon"><i class="fas fa-lock"></i></span>
                    <input type="password" name="password" placeholder="Mot de passe" id="login-password" required>
                    <button type="button" class="toggle-pw" tabindex="0" aria-label="Afficher le mot de passe" onclick="togglePassword('login-password', this)">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                
                <button type="submit">Commencer</button>
            </form>
            
            <div class="login-links">
                <a href="register.php">Créer un compte</a>
                <a href="#">Besoin d'aide ?</a>
            </div>
        </div>
    </div>
    <script>
        function togglePassword(id, el) {
            const passwordInput = document.getElementById(id);
            const icon = el.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
                el.setAttribute('aria-label', 'Masquer le mot de passe');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
                el.setAttribute('aria-label', 'Afficher le mot de passe');
            }
        }
    </script>
    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <img src="https://i.postimg.cc/g07GgLp5/VMaroc-logo-trf.png" alt="VMaroc Logo" class="logo-img">
                    <p>Découvrez les merveilles du Maroc avec VMaroc, votre guide de voyage personnalisé.</p>
                </div>
                <div class="footer-col">
                    <h3>Liens Rapides</h3>
                    <ul>
                        <li><a href="index.php">Accueil</a></li>
                        <li><a href="destinations.php">Destinations</a></li>
                        <li><a href="recommendations.php">Recommandations</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h3>Contact</h3>
                    <p>contact@marocauthentique.com</p>
                    <p>+212 522 123 456</p>
                </div>
            </div>
            <hr>
            <div class="copyright">
                <p>© 2025 Maroc Authentique. Tous droits réservés.</p>
                <p>
                    <a href="politique-confidentialite.php">Politique de confidentialité</a> |
                    <a href="conditions-utilisation.php">Conditions d'utilisation</a>
                </p>
            </div>
        </div>
    </footer>
</body>
</html>