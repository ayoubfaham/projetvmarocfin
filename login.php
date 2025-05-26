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
    <title>Connexion - Maroc Authentique</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/main.css">
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container header-container">
            <a href="index.php" class="logo">
                <img src="https://i.postimg.cc/g07GgLp5/VMaroc-logo-trf.png" alt="Maroc Authentique" class="logo-img" style="height:48px;">
            </a>
            <ul class="nav-menu">
                <li><a href="index.php">Accueil</a></li>
                <li><a href="destinations.php">Destinations</a></li>
                <li><a href="experiences.php">Expériences</a></li>
            </ul>
            <div class="auth-buttons">
                <a href="login.php" class="btn-outline active">Connexion</a>
                <a href="register.php" class="btn-primary">Inscription</a>
            </div>
        </div>
    </header>

    <main style="margin-top: 100px;">
        <div class="container">
            <div class="auth-container">
                <div class="auth-card">
                    <div class="auth-header">
                        <h2>Connexion</h2>
                        <p>Connectez-vous pour accéder à votre compte</p>
                    </div>

                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-error">
                            <?= htmlspecialchars($error_message) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="auth-form">
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" required>
                        </div>

                        <div class="form-group">
                            <label for="password">Mot de passe</label>
                            <input type="password" id="password" name="password" required>
                        </div>

                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="remember">
                                Se souvenir de moi
                            </label>
                        </div>

                        <button type="submit" class="btn-primary btn-block">Se connecter</button>
                    </form>

                    <div class="auth-footer">
                        <p>Vous n'avez pas de compte ? <a href="register.php">Inscrivez-vous</a></p>
                        <a href="#" class="forgot-password">Mot de passe oublié ?</a>
                    </div>
                </div>
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
</body>
</html> 