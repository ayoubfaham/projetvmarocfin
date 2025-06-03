<?php
session_start();
require_once 'config/database.php';

// Vérification de l'authentification
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Récupération des informations de l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Récupération des avis de l'utilisateur
$stmt = $pdo->prepare("
    SELECT r.*, l.nom as lieu_nom, v.nom as ville_nom 
    FROM avis r 
    JOIN lieux l ON r.id_lieu = l.id 
    JOIN villes v ON l.id_ville = v.id 
    WHERE r.id_utilisateur = ? 
    ORDER BY r.date_creation DESC
");
$stmt->execute([$_SESSION['user_id']]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Traitement de la mise à jour du profil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = $_POST['nom'] ?? '';
    $email = $_POST['email'] ?? '';
    $telephone = $_POST['telephone'] ?? '';
    $preferences = $_POST['preferences'] ?? '';

    $stmt = $pdo->prepare("
        UPDATE utilisateurs 
        SET nom = ?, email = ?, telephone = ?, preferences = ? 
        WHERE id = ?
    ");
    
    if ($stmt->execute([$nom, $email, $telephone, $preferences, $_SESSION['user_id']])) {
        $success_message = "Profil mis à jour avec succès !";
        // Mise à jour des données de session
        $_SESSION['user_nom'] = $nom;
    } else {
        $error_message = "Erreur lors de la mise à jour du profil.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil - VMaroc</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/montserrat-font.css">
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/header.css">
    <style>
        body {
            font-family: 'Montserrat', sans-serif;
            background-color: #f8f9fa;
        }
        
        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 20px;
        }
        
        .profile-section {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            padding: 25px;
            transition: transform 0.3s ease;
        }
        
        .profile-section:hover {
            transform: translateY(-5px);
        }
        
        .section-title h2 {
            font-family: 'Montserrat', sans-serif;
            font-weight: 800;
            color: #333;
            font-size: 1.8rem;
            margin-bottom: 20px;
            position: relative;
            padding-bottom: 10px;
        }
        
        .section-title h2:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 3px;
            background: #bfa14a;
        }
        
        .profile-card {
            padding: 20px 0;
        }
        
        .profile-header {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            background: #bfa14a;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            font-weight: 600;
            color: white;
            margin-bottom: 15px;
            box-shadow: 0 5px 15px rgba(191,161,74,0.3);
        }
        
        .profile-header h3 {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            color: #333;
            margin: 0;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            margin-bottom: 8px;
            color: #555;
        }
        
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #e1e1e1;
            border-radius: 8px;
            font-family: 'Montserrat', sans-serif;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus, .form-group textarea:focus {
            border-color: #bfa14a;
            box-shadow: 0 0 0 3px rgba(191,161,74,0.2);
            outline: none;
        }
        
        .btn-primary {
            background: #bfa14a;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-block;
            text-align: center;
            text-decoration: none;
            margin-top: 10px;
        }
        
        .btn-primary:hover {
            background: #a88c3d;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(191,161,74,0.3);
        }
        
        .reviews-list {
            display: grid;
            gap: 20px;
        }
        
        .review-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
            padding: 20px;
            transition: all 0.3s ease;
        }
        
        .review-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .reviewer-info h4 {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            margin: 0 0 5px 0;
            color: #333;
        }
        
        .review-location {
            font-family: 'Montserrat', sans-serif;
            font-size: 0.85rem;
            color: #777;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .review-rating {
            color: #bfa14a;
        }
        
        .review-rating .active {
            color: #bfa14a;
        }
        
        .review-content p {
            font-family: 'Montserrat', sans-serif;
            line-height: 1.6;
            color: #555;
            margin-bottom: 10px;
        }
        
        .review-date {
            font-family: 'Montserrat', sans-serif;
            font-size: 0.85rem;
            color: #999;
            text-align: right;
        }
        
        .no-reviews {
            font-family: 'Montserrat', sans-serif;
            color: #777;
            text-align: center;
            padding: 30px 0;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-family: 'Montserrat', sans-serif;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        @media (max-width: 768px) {
            .profile-grid {
                grid-template-columns: 1fr;
            }
            
            .profile-section {
                margin-bottom: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include 'includes/header.php'; ?>

    <main style="margin-top: 100px;">
        <div class="container">
            <!-- Messages de succès/erreur -->
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($success_message) ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>

            <div class="profile-grid">
                <!-- Informations du profil -->
                <section class="profile-section">
                    <div class="section-title">
                        <h2>Mon Profil</h2>
                    </div>
                    
                    <div class="profile-card">
                        <div class="profile-header">
                            <div class="profile-avatar">
                                <?= strtoupper(substr($user['nom'], 0, 1)) ?>
                            </div>
                            <h3><?= htmlspecialchars($user['nom']) ?></h3>
                        </div>
                        
                        <form method="POST" class="profile-form">
                            <div class="form-group">
                                <label for="nom">Nom complet</label>
                                <input type="text" id="nom" name="nom" value="<?= htmlspecialchars($user['nom']) ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="telephone">Téléphone</label>
                                <input type="tel" id="telephone" name="telephone" value="<?= htmlspecialchars($user['telephone']) ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="preferences">Préférences de voyage</label>
                                <textarea id="preferences" name="preferences" rows="3"><?= htmlspecialchars($user['preferences']) ?></textarea>
                            </div>
                            
                            <button type="submit" class="btn-primary">Mettre à jour</button>
                        </form>
                    </div>
                </section>

                <!-- Avis de l'utilisateur -->
                <section class="profile-section">
                    <div class="section-title">
                        <h2>Mes Avis</h2>
                    </div>
                    
                    <div class="reviews-list">
                        <?php if (!empty($reviews)): ?>
                            <?php foreach ($reviews as $review): ?>
                                <div class="review-card">
                                    <div class="review-header">
                                        <div class="reviewer-info">
                                            <h4><?= htmlspecialchars($review['lieu_nom']) ?></h4>
                                            <div class="review-location">
                                                <i class="fas fa-map-marker-alt"></i>
                                                <?= htmlspecialchars($review['ville_nom']) ?>
                                            </div>
                                        </div>
                                        <div class="review-rating">
                                            <?php for($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star <?= $i <= $review['note'] ? 'active' : '' ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    <div class="review-content">
                                        <p><?= nl2br(htmlspecialchars($review['commentaire'])) ?></p>
                                        <div class="review-date">
                                            <?= date('d/m/Y', strtotime($review['date_creation'])) ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="no-reviews">Vous n'avez pas encore laissé d'avis.</p>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <img src="https://i.postimg.cc/g07GgLp5/VMaroc-logo-trf.png" alt="VMaroc Logo" class="logo-img" style="height:90px;">
                    <p style="font-family: 'Montserrat', sans-serif;">Découvrez les merveilles du Maroc avec VMaroc, votre guide de voyage personnalisé.</p>
                </div>
                <div class="footer-col">
                    <h3 style="font-family: 'Montserrat', sans-serif; font-weight: 700;">Liens Rapides</h3>
                    <ul style="font-family: 'Montserrat', sans-serif;">
                        <li><a href="index.php" style="font-family: 'Montserrat', sans-serif;">Accueil</a></li>
                        <li><a href="destinations.php" style="font-family: 'Montserrat', sans-serif;">Destinations</a></li>
                        <li><a href="experiences.php" style="font-family: 'Montserrat', sans-serif;">Expériences</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h3 style="font-family: 'Montserrat', sans-serif; font-weight: 700;">Contact</h3>
                    <p style="font-family: 'Montserrat', sans-serif;">contact@marocauthentique.com</p>
                    <p style="font-family: 'Montserrat', sans-serif;">+212 522 123 456</p>
                </div>
            </div>
            <div class="copyright">
                <p style="font-family: 'Montserrat', sans-serif;">© 2025 Maroc Authentique. Tous droits réservés.</p>
                <p style="font-family: 'Montserrat', sans-serif; margin-top: 10px;">
                    <a href="politique-confidentialite.php" style="color: #8B7355; text-decoration: none; font-family: 'Montserrat', sans-serif;">Politique de confidentialité</a> | 
                    <a href="conditions-utilisation.php" style="color: #8B7355; text-decoration: none; font-family: 'Montserrat', sans-serif;">Conditions d'utilisation</a>
                </p>
            </div>
        </div>
    </footer>
</body>
</html> 