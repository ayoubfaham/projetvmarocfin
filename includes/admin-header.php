<?php
// Assurez-vous que la session est démarrée
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Vérification de l'authentification admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VMaroc - Administration</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/admin-style.css">
    <style>
        :root {
            --primary: #2a665a;
            --primary-light: #3d8f80;
            --dark: #2d3436;
            --gray: #636e72;
            --light: #f5f6fa;
            --danger: #ff7675;
            --success: #00b894;
            --background: #f0f4f3;
        }

        .header {
            background: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            z-index: 1000;
        }

        .left-section {
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .logo {
            display: flex;
            align-items: center;
        }

        .logo img {
            height: 45px;
            object-fit: contain;
        }

        .admin-nav {
            display: flex;
            gap: 1.5rem;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .nav-link {
            color: var(--dark);
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .nav-link:hover, .nav-link.active {
            color: var(--primary);
            background: var(--background);
        }

        .main-nav {
            display: flex;
            gap: 30px;
            align-items: center;
            list-style: none;
            margin: 0;
            padding: 0;
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
        }

        .dropdown-menu {
            position: relative;
        }

        .dropdown-toggle {
            background: var(--background);
            border: none;
            color: var(--primary);
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .dropdown-toggle:hover {
            background: var(--primary);
            color: white;
        }

        .dropdown-content {
            position: absolute;
            right: 0;
            top: 120%;
            background: white;
            min-width: 250px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            padding: 0.75rem;
            display: none;
            z-index: 1000;
        }

        .dropdown-menu:hover .dropdown-content {
            display: block;
        }

        .dropdown-content a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            color: var(--dark);
            text-decoration: none;
            font-weight: 500;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .dropdown-content a:hover {
            background: var(--background);
            color: var(--primary);
        }

        .dropdown-content i {
            font-size: 1.1rem;
            color: var(--primary);
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            padding: 5px;
            cursor: pointer;
            color: var(--dark);
            transition: all 0.3s ease;
        }

        .mobile-menu-btn:hover {
            color: var(--primary);
        }

        @media (max-width: 768px) {
            .mobile-menu-btn {
                display: block;
            }

            .main-nav {
                display: none;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="left-section">
            <a href="../index.php" class="logo">
                <img src="https://i.postimg.cc/g07GgLp5/VMaroc-logo-trf.png" alt="VMaroc">
            </a>
            <ul class="admin-nav">
                <li><a href="admin-panel.php" class="nav-link active">Panel Admin</a></li>
                <li><a href="../logout.php" class="nav-link">Déconnexion</a></li>
            </ul>
        </div>
        <div class="right-section">
            <div class="dropdown-menu">
                <button class="dropdown-toggle">
                    <i class="fas fa-ellipsis-v"></i>
                </button>
                <div class="dropdown-content">
                    <a href="admin-cities.php"><i class="fas fa-city"></i> Gérer les villes</a>
                    <a href="admin-places.php"><i class="fas fa-map-marker-alt"></i> Gérer les lieux</a>
                    <a href="admin-users.php"><i class="fas fa-users"></i> Gérer les utilisateurs</a>
                    <a href="admin-reviews.php"><i class="fas fa-star"></i> Gérer les avis</a>
                </div>
            </div>
        </div>
        <nav>
            <ul class="main-nav">
                <li><a href="../index.php" class="nav-link">Accueil</a></li>
                <li><a href="../destinations.php" class="nav-link">Destinations</a></li>
                <li><a href="../recommandations.php" class="nav-link">Recommandations</a></li>
            </ul>
        </nav>
        <button class="mobile-menu-btn">
            <i class="fas fa-bars"></i>
        </button>
    </header>
</body>
</html> 