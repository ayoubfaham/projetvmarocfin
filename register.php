<?php
session_start();
require_once 'config/database.php';

// Redirection si déjà connecté
if (isset($_SESSION['user_id'])) {
    header('Location: profile.php');
    exit;
}

// Traitement du formulaire d'inscription
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = $_POST['nom'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $telephone = $_POST['telephone'] ?? '';
    $preferences = $_POST['preferences'] ?? '';

    $errors = [];

    // Validation des champs
    if (empty($nom)) {
        $errors[] = "Le nom est requis.";
    }

    if (empty($email)) {
        $errors[] = "L'email est requis.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'email n'est pas valide.";
    } else {
        // Vérification si l'email existe déjà
        $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = "Cet email est déjà utilisé.";
        }
    }

    if (empty($password)) {
        $errors[] = "Le mot de passe est requis.";
    } elseif (strlen($password) < 6) {
        $errors[] = "Le mot de passe doit contenir au moins 6 caractères.";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Les mots de passe ne correspondent pas.";
    }

    // Si pas d'erreurs, création du compte
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("
            INSERT INTO utilisateurs (nom, email, password, telephone, preferences) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        if ($stmt->execute([$nom, $email, $hashed_password, $telephone, $preferences])) {
            // Connexion automatique
            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['user_nom'] = $nom;
            header('Location: profile.php');
            exit;
        } else {
            $errors[] = "Une erreur est survenue lors de la création du compte.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Maroc Authentique</title>
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
                <a href="login.php" class="btn-outline">Connexion</a>
                <a href="register.php" class="btn-primary active">Inscription</a>
            </div>
        </div>
    </header>

    <main style="margin-top: 100px;">
        <div class="container">
            <div class="auth-container">
                <div class="auth-card">
                    <div class="auth-header">
                        <h2>Inscription</h2>
                        <p>Créez votre compte pour commencer l'aventure</p>
                    </div>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-error">
                            <ul>
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="auth-form">
                        <div class="form-group">
                            <label for="nom">Nom complet</label>
                            <input type="text" id="nom" name="nom" value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="telephone">Téléphone</label>
                            <input type="tel" id="telephone" name="telephone" value="<?= htmlspecialchars($_POST['telephone'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label for="password">Mot de passe</label>
                            <input type="password" id="password" name="password" required>
                            <small>Le mot de passe doit contenir au moins 6 caractères</small>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirmer le mot de passe</label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                        </div>

                        <div class="form-group">
                            <label for="preferences">Préférences de voyage</label>
                            <textarea id="preferences" name="preferences" rows="3"><?= htmlspecialchars($_POST['preferences'] ?? '') ?></textarea>
                            <small>Décrivez vos centres d'intérêt et préférences de voyage</small>
                        </div>

                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="terms" required>
                                J'accepte les conditions d'utilisation et la politique de confidentialité
                            </label>
                        </div>

                        <button type="submit" class="btn-primary btn-block">Créer mon compte</button>
                    </form>

                    <div class="auth-footer">
                        <p>Vous avez déjà un compte ? <a href="login.php">Connectez-vous</a></p>
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