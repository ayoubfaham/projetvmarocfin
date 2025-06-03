<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/main.css">
    <style>
        .admin-panel-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 32px;
            margin-top: 40px;
        }
        .admin-card {
            background: var(--white);
            border-radius: 12px;
            box-shadow: var(--shadow-md);
            padding: 32px 24px;
            text-align: center;
            transition: var(--transition);
            border: 1px solid var(--border-color);
        }
        .admin-card:hover {
            box-shadow: var(--shadow-lg);
            border-color: var(--accent-color);
            transform: translateY(-5px);
        }
        .admin-card i {
            font-size: 2.5rem;
            color: var(--accent-color);
            margin-bottom: 18px;
        }
        .admin-card h3 {
            font-size: 1.2rem;
            margin-bottom: 12px;
            color: var(--primary-color);
        }
        .admin-card p {
            color: var(--secondary-color);
            font-size: 0.95rem;
            margin-bottom: 18px;
        }
        .admin-card a {
            display: inline-block;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <!-- Header/Navbar moderne -->
    <header>
        <div class="container header-container">
            <a href="../index.php" class="logo">
                <img src="https://i.postimg.cc/g07GgLp5/VMaroc-logo-trf.png" alt="Maroc Authentique" class="logo-img" style="height:70px;">
            </a>
            <ul class="nav-menu">
                <li><a href="../index.php">Accueil</a></li>
                <li><a href="../destinations.php">Destinations</a></li>
                <li><a href="../recommandations.php">Recommandations</a></li>
            </ul>
            <div class="auth-buttons">
                <a href="admin-panel.php" class="btn-outline" style="margin-right:10px;">Panel Admin</a>
                <a href="../logout.php" class="btn-primary">Déconnexion</a>
            </div>
        </div>
    </header>

    <main style="margin-top:100px;">
    <div class="container">
            <div class="section-title">
                <h2>Tableau de bord Admin</h2>
            </div>
            <div class="admin-panel-cards">
                <div class="admin-card">
                    <i class="fas fa-city"></i>
                    <h3>Gestion des villes</h3>
                    <p>Ajouter, modifier ou supprimer les villes du Maroc.</p>
                    <a href="admin-cities.php" class="btn-solid">Gérer les villes</a>
                </div>
                <div class="admin-card">
                    <i class="fas fa-map-marker-alt"></i>
                    <h3>Gestion des lieux</h3>
                    <p>Gérer les lieux à visiter dans chaque ville.</p>
                    <a href="admin-places.php" class="btn-solid">Gérer les lieux</a>
                </div>
                <div class="admin-card">
                    <i class="fas fa-users"></i>
                    <h3>Gestion des utilisateurs</h3>
                    <p>Voir, ajouter ou supprimer les utilisateurs du site.</p>
                    <a href="admin-users.php" class="btn-solid">Gérer les utilisateurs</a>
                </div>
                <div class="admin-card">
                    <i class="fas fa-star"></i>
                    <h3>Gestion des avis</h3>
                    <p>Consulter et modérer les avis des utilisateurs.</p>
                    <a href="add-review.php" class="btn-solid">Gérer les avis</a>
                </div>
            </div>
    </div>
    </main>
</body>
</html> 