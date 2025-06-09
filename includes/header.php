<?php
// Ce fichier contient le header commun u00e0 toutes les pages du site
// Il doit u00eatre inclus au du00e9but de chaque page apru00e8s la balise <body>

// Assurez-vous que la session est du00e9marru00e9e
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>

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
                <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1): ?>
                    <a href="admin/index.php" class="nav-btn btn-outline">Panneau Admin</a>
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
