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
            INSERT INTO utilisateurs (nom, email, password, telephone) 
            VALUES (?, ?, ?, ?)
        ");
        
        if ($stmt->execute([$nom, $email, $hashed_password, $telephone])) {
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
    <title>Inscription - VMaroc</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/montserrat-font.css">
    <link rel="stylesheet" href="css/header.css">
    <style>
        :root {
            --primary-color: #D70026;
            --secondary-color: #1A5F7A;
            --text-color: #333;
            --light-gray: #f5f5f5;
            --medium-gray: #e0e0e0;
            --dark-gray: #888;
            --white: #fff;
            --shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
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
            display: flex;
            flex-direction: column;
            background-color: var(--light-gray);
        }
        
        /* Masquer le header original pour cette page */
        header {
            display: none;
        }
        
        /* Page d'inscription avec image d'arrière-plan */
        .register-page {
            min-height: 100vh;
            width: 100%;
            background: url('images/login.png') center/cover no-repeat;
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
        
        /* Formulaire d'inscription */
        .register-content {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100%;
            max-width: 550px;
            background-color: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(5px);
            border-radius: 15px;
            box-shadow: var(--shadow);
            padding: 30px;
            margin: 20px;
        }
        
        .register-title {
            text-align: center;
            margin-bottom: 25px;
        }
        
        .register-title h2 {
            font-family: 'Playfair Display', serif;
            font-size: 28px;
            color: #000000;
            margin-bottom: 10px;
        }
        
        .register-title p {
            color: var(--dark-gray);
            font-size: 14px;
        }
        
        .register-form {
            width: 100%;
        }
        
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            font-size: 14px;
            color: var(--text-color);
        }
        
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 12px 15px 12px 40px;
            border: 1px solid rgba(255, 255, 255, 0.6);
            background-color: rgba(255, 255, 255, 0.8);
            border-radius: 30px;
            font-size: 14px;
            transition: var(--transition);
            height: 45px;
        }
        
        .form-group textarea {
            padding-left: 15px;
            border-radius: 15px;
            resize: vertical;
            min-height: 80px;
        }
        
        .form-group input:focus, .form-group textarea:focus {
            outline: none;
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.3);
        }
        
        .form-group i {
            position: absolute;
            left: 15px;
            top: 43px;
            color: var(--dark-gray);
            font-size: 16px;
            z-index: 2;
        }
        
        .form-group small {
            display: block;
            margin-top: 5px;
            font-size: 12px;
            color: var(--dark-gray);
        }
        
        .checkbox-label {
            display: flex;
            align-items: center;
            font-size: 13px;
            color: var(--text-color);
            cursor: pointer;
        }
        
        .checkbox-label input {
            margin-right: 10px;
            width: auto;
        }
        
        .btn-register {
            background-color: var(--secondary-color);
            color: var(--white);
            border: none;
            border-radius: 30px;
            padding: 12px 30px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: var(--transition);
            margin-top: 10px;
        }
        
        .btn-register:hover {
            background-color: #12465a;
            transform: translateY(-2px);
        }
        
        .register-footer {
            margin-top: 20px;
            text-align: center;
            font-size: 14px;
            color: var(--text-color);
        }
        
        .register-footer a {
            color: var(--secondary-color);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
        }
        
        .register-footer a:hover {
            color: var(--primary-color);
        }
        
        .alert {
            width: 100%;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-error {
            background-color: rgba(255, 0, 0, 0.2);
            border-left: 4px solid var(--primary-color);
            color: white;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }
        
        .alert ul {
            margin-left: 20px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .register-content {
                max-width: 90%;
                padding: 20px;
            }
            
            .register-title h2 {
                font-size: 24px;
            }
            
            .form-group input, .form-group textarea {
                padding: 10px 10px 10px 35px;
            }
            
            .form-group i {
                top: 36px;
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
    <div class="register-page">
        <!-- Logo VMaroc en haut avec lien vers la page d'accueil -->
        <a href="index.php">
            <img src="https://i.postimg.cc/g07GgLp5/VMaroc-logo-trf.png" alt="VMaroc Logo" class="vmaroc-logo">
        </a>
        
        <div class="register-content">
            <div class="register-title">
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

                    <form method="POST" class="register-form">
                        <div class="form-group">
                            <label for="nom">Nom complet</label>
                            <i class="fas fa-user"></i>
                            <input type="text" id="nom" name="nom" value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="email">Email</label>
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="telephone">Téléphone</label>
                            <i class="fas fa-phone"></i>
                            <input type="tel" id="telephone" name="telephone" value="<?= htmlspecialchars($_POST['telephone'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label for="password">Mot de passe</label>
                            <i class="fas fa-lock"></i>
                            <input type="password" id="password" name="password" required>
                            <small>Le mot de passe doit contenir au moins 6 caractères</small>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirmer le mot de passe</label>
                            <i class="fas fa-lock"></i>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                        </div>



                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="terms" required>
                                J'accepte les conditions d'utilisation et la politique de confidentialité
                            </label>
                        </div>

                        <button type="submit" class="btn-register">Créer mon compte</button>
                    </form>

                    <div class="register-footer">
                        <p>Vous avez déjà un compte ? <a href="login.php">Connectez-vous</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
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