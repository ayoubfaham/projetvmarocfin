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
    <title>Mon Profil - Maroc Authentique</title>
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
                <a href="profile.php" class="btn-outline active">Mon Profil</a>
                <a href="logout.php" class="btn-primary">Déconnexion</a>
            </div>
        </div>
    </header>

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