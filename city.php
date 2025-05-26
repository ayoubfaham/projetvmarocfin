<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'config/database.php';

// Récupération de l'ID de la ville depuis l'URL
$cityId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$city = $pdo->prepare("SELECT * FROM villes WHERE id = ?");
$city->execute([$cityId]);
$city = $city->fetch(PDO::FETCH_ASSOC);

// Catégories disponibles
$categories = [
    'Hôtels', 'Restaurants', 'Parcs', 'Plages', 'Cinémas',
    'Théâtres', 'Monuments', 'Musées', 'Shopping', 'Vie nocturne'
];

// Pagination
$perPage = 5;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$selectedCategory = isset($_GET['categorie']) ? $_GET['categorie'] : 'all';

// Construction de la requête
$params = [$cityId];
$sql = "SELECT * FROM lieux WHERE id_ville = ?";
if ($selectedCategory !== 'all' && in_array($selectedCategory, $categories)) {
    $sql .= " AND categorie = ?";
    $params[] = $selectedCategory;
}
$sqlCount = str_replace('SELECT *', 'SELECT COUNT(*) as total', $sql);

// Nombre total de lieux (pour pagination)
$stmtCount = $pdo->prepare($sqlCount);
$stmtCount->execute($params);
$totalPlaces = $stmtCount->fetch()['total'];
$totalPages = max(1, ceil($totalPlaces / $perPage));

// Récupération des lieux paginés
$limit = (int)$perPage;
$offset = (int)($perPage * ($page - 1));
$sql .= " ORDER BY categorie, nom LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$places = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les catégories présentes dans la ville
$catStmt = $pdo->prepare("SELECT DISTINCT categorie FROM lieux WHERE id_ville = ?");
$catStmt->execute([$cityId]);
$cityCategories = $catStmt->fetchAll(PDO::FETCH_COLUMN);

// Récupérer la moyenne des ratings pour la ville
$stmt = $pdo->prepare("
    SELECT AVG(a.rating) as moyenne
    FROM avis a
    JOIN lieux l ON a.id_lieu = l.id
    WHERE l.id_ville = ?
");
$stmt->execute([$cityId]);
$moyenne = $stmt->fetchColumn();

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($city['nom'] ?? 'Ville'); ?> - Maroc Authentique</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/main.css">
    <style>
    body, .place-info, .place-info p, .place-address, .place-rating, .places-count {
        font-size: 0.91rem;
    }
    .place-title {
        font-size: 1.08rem;
        font-weight: bold;
        color: var(--primary-color);
    }
    .city-main-grid {
        display: flex;
        gap: 32px;
        align-items: flex-start;
        margin-top: 40px;
    }
    .city-filters {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 2px 12px rgba(44,62,80,0.07);
        padding: 18px 12px 14px 12px;
        min-width: 0;
        width: 180px;
    }
    .city-filters h3 {
        font-size: 1.05rem;
        margin-bottom: 10px;
    }
    .filter-btns {
        display: flex;
        flex-wrap: wrap;
        gap: 7px;
    }
    .filter-btn {
        background: #f3f4f6;
        border: none;
        border-radius: 18px;
        padding: 6px 14px;
        font-size: 0.98rem;
        color: var(--primary-color);
        cursor: pointer;
        transition: background 0.2s;
        text-decoration: none;
        font-family: 'Poppins', sans-serif;
        font-weight: 500;
    }
    .filter-btn.active, .filter-btn:hover {
        background: var(--primary-color);
        color: #fff;
    }
    .city-places {
        flex: 1;
        margin-top: 18px;
    }
    .places-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 18px;
    }
    .places-header h2 {
        font-size: 1.5rem;
        font-family: 'Playfair Display', serif;
        color: var(--primary-color);
        margin: 0;
    }
    .places-count {
        color: var(--secondary-color);
        font-size: 1.05em;
    }
    .places-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(270px, 1fr));
        gap: 24px;
        margin-bottom: 60px;
    }
    .place-card {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 2px 12px rgba(44,62,80,0.07);
        overflow: hidden;
        display: flex;
        flex-direction: column;
        min-height: 320px;
    }
    .place-img img {
        width: 100%;
        height: 160px;
        object-fit: cover;
        display: block;
    }
    .place-info {
        padding: 18px 16px 14px 16px;
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
    }
    .place-title-rating {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 6px;
    }
    .place-rating {
        color: #FFD700;
        font-size: 0.95em;
        display: flex;
        align-items: center;
        gap: 2px;
    }
    .place-rating i {
        font-size: 13px !important;
    }
    .place-rating .rating-note {
        margin-left: 2px;
        font-size: 0.95em;
        color: var(--secondary-color);
        font-weight: 500;
    }
    .place-address {
        color: var(--secondary-color);
        font-size: 0.98em;
        margin-top: 2px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    @media (max-width: 900px) {
        .city-main-grid { flex-direction: column; gap: 24px; }
        .city-filters { min-width: 0; width: 100%; max-width: 100%; }
    }
    .pagination {
        display: flex;
        justify-content: center;
        gap: 8px;
        margin: 32px 0 0 0;
        margin-bottom: 48px;
    }
    .pagination-btn {
        background: #fff;
        color: var(--primary-color);
        border: 1.5px solid var(--border-color);
        border-radius: 18px;
        padding: 7px 18px;
        font-size: 1rem;
        font-family: 'Poppins', sans-serif;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.15s;
        outline: none;
        text-decoration: none;
        box-shadow: 0 2px 8px rgba(44,62,80,0.06);
        display: inline-block;
    }
    .pagination-btn.active, .pagination-btn:focus {
        background: var(--primary-color);
        color: #fff;
        border-color: var(--primary-color);
    }
    .pagination-btn:hover:not(.active) {
        background: #f3f4f6;
        color: var(--primary-color);
        border-color: var(--primary-color);
    }
    .pagination-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    .hero {
        min-height: 60vh;
        height: auto !important;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        background-size: cover !important;
        background-position: center !important;
        background-repeat: no-repeat !important;
        margin-bottom: 40px;
    }
    .hero-content {
        text-align: center;
        width: 100%;
        color: #fff;
        z-index: 2;
    }
    .hero-content h1, .hero-content p {
        text-shadow: 0 4px 24px rgba(0,0,0,0.28), 0 1.5px 6px rgba(0,0,0,0.18);
    }
    </style>
</head>
<body>
    <!-- Header/Navbar moderne -->
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
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                        <a href="pages/admin-panel.php" class="btn-outline">Panel Admin</a>
                        <a href="logout.php" class="btn-primary">Déconnexion</a>
                    <?php else: ?>
                        <a href="profile.php" class="btn-outline">Mon Profil</a>
                        <a href="logout.php" class="btn-primary">Déconnexion</a>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="login.php" class="btn-outline">Connexion</a>
                    <a href="register.php" class="btn-primary">Inscription</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main style="margin-top:100px;">
                <div class="container">
            <?php if ($city): ?>
                <section class="hero" style="background-image: url('<?= htmlspecialchars($city['photo']) ?>');">
                    <div class="hero-content">
                        <h1 style="font-family: 'Playfair Display', serif; font-size: 2.5rem; display: flex; align-items: center; justify-content: center; gap: 18px; flex-wrap: wrap;">
                            <?= htmlspecialchars($city['nom']) ?>
                            <?php if ($moyenne): ?>
                                <span style="display: flex; align-items: center; gap: 4px; font-size: 1.2rem; font-family: 'Poppins', sans-serif; font-weight: 500;">
                                    <?php
                                    $fullStars = floor($moyenne);
                                    $halfStar = ($moyenne - $fullStars) >= 0.5 ? 1 : 0;
                                    $emptyStars = 5 - $fullStars - $halfStar;
                                    for ($i = 0; $i < $fullStars; $i++) echo '<i class="fas fa-star" style="color:#FFD700;font-size:12px;"></i>';
                                    if ($halfStar) echo '<i class="fas fa-star-half-alt" style="color:#FFD700;font-size:12px;"></i>';
                                    for ($i = 0; $i < $emptyStars; $i++) echo '<i class="far fa-star" style="color:#FFD700;font-size:12px;"></i>';
                                    ?>
                                    <span style="margin-left:6px; color:var(--white); font-size:1.1rem; font-weight:600;">(<?= round($moyenne,2) ?> / 5)</span>
                                </span>
                            <?php endif; ?>
                        </h1>
                        <p style="font-size: 1.1rem; opacity: 0.97;"> <?= htmlspecialchars($city['description']) ?> </p>
                </div>
                </section>
                <div class="city-main-grid">
                    <aside class="city-filters">
                        <h3>Filtrer par catégorie</h3>
                        <div class="filter-btns">
                            <a href="?id=<?= $cityId ?>&categorie=all&page=1" class="filter-btn<?= $selectedCategory === 'all' ? ' active' : '' ?>">Tous les lieux</a>
                            <?php foreach ($categories as $cat): if (!in_array($cat, $cityCategories)) continue; ?>
                                <a href="?id=<?= $cityId ?>&categorie=<?= urlencode($cat) ?>&page=1" class="filter-btn<?= $selectedCategory === $cat ? ' active' : '' ?>"><?= htmlspecialchars($cat) ?></a>
                                <?php endforeach; ?>
                            </div>
                    </aside>
                    <section class="city-places">
                        <div class="places-header">
                            <h2>Tous les lieux</h2>
                            <span class="places-count"><?= $totalPlaces ?> résultat<?= $totalPlaces > 1 ? 's' : '' ?></span>
                        </div>
                            <div class="places-grid">
                            <?php if (empty($places)): ?>
                                <p style="text-align:center;color:#888;grid-column:1/-1;">Aucun lieu trouvé pour cette catégorie.</p>
                            <?php else: ?>
                                <?php foreach ($places as $place): ?>
                                    <?php
                                    // Calcul du rating moyen pour ce lieu
                                    $stmt = $pdo->prepare("SELECT AVG(rating) FROM avis WHERE id_lieu = ?");
                                    $stmt->execute([$place['id']]);
                                    $placeRating = $stmt->fetchColumn();
                                    ?>
                                    <div class="place-card">
                                        <div class="place-img"><img src="<?= htmlspecialchars($place['photo']) ?>" alt="<?= htmlspecialchars($place['nom']) ?>"></div>
                                        <div class="place-info">
                                            <div class="place-title-rating">
                                                <span class="place-title"><?= htmlspecialchars($place['nom']) ?></span>
                                                <?php if ($placeRating): ?>
                                                    <span class="place-rating">
                                                        <?php
                                                        $fullStars = floor($placeRating);
                                                        $halfStar = ($placeRating - $fullStars) >= 0.5 ? 1 : 0;
                                                        $emptyStars = 5 - $fullStars - $halfStar;
                                                        for ($i = 0; $i < $fullStars; $i++) echo '<i class="fas fa-star"></i>';
                                                        if ($halfStar) echo '<i class="fas fa-star-half-alt"></i>';
                                                        for ($i = 0; $i < $emptyStars; $i++) echo '<i class="far fa-star"></i>';
                                                        ?>
                                                        <span class="rating-note"><?= round($placeRating,1) ?></span>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if (!empty($place['adresse'])): ?>
                                                <div class="place-address"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($place['adresse']) ?></div>
                                            <?php endif; ?>
                                            <a href="place.php?id=<?= $place['id'] ?>" class="btn-primary" style="margin-top:12px;align-self:flex-start;">Découvrir</a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                        <div class="pagination">
                            <a class="pagination-btn" href="?id=<?= $cityId ?>&categorie=<?= urlencode($selectedCategory) ?>&page=<?= max(1, $page-1) ?>" <?= $page <= 1 ? 'disabled' : '' ?>>&laquo; Précédent</a>
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <a class="pagination-btn<?= $i === $page ? ' active' : '' ?>" href="?id=<?= $cityId ?>&categorie=<?= urlencode($selectedCategory) ?>&page=<?= $i ?>"> <?= $i ?> </a>
                            <?php endfor; ?>
                            <a class="pagination-btn" href="?id=<?= $cityId ?>&categorie=<?= urlencode($selectedCategory) ?>&page=<?= min($totalPages, $page+1) ?>" <?= $page >= $totalPages ? 'disabled' : '' ?>>Suivant &raquo;</a>
                        </div>
                        <?php endif; ?>
                    </section>
                </div>
            <?php else: ?>
                <div class="section-title"><h2>Ville non trouvée.</h2></div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer moderne -->
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
                    <h3>Villes Populaires</h3>
                    <ul>
                        <?php
                        $popular = ['casablanca', 'marrakech', 'tanger'];
                        $stmt = $pdo->query("SELECT id, nom FROM villes");
                        $cities = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($popular as $pop) {
                            foreach ($cities as $city) {
                                if (strtolower($city['nom']) === $pop) {
                                    echo '<li><a href="city.php?id=' . $city['id'] . '">' . htmlspecialchars($city['nom']) . '</a></li>';
                                    break;
                                }
                            }
                        }
                        ?>
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
                <p style="margin-top: 10px;">
                    <a href="#" style="color: #BBBBBB; text-decoration: none;">Politique de confidentialité</a> | 
                    <a href="#" style="color: #BBBBBB; text-decoration: none;">Conditions d'utilisation</a>
                </p>
            </div>
        </div>
    </footer>
</body>
</html> 