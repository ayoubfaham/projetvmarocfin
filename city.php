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

// --- Début: Logique pour obtenir les images du hero dynamiquement depuis la base de données ---
$cityHeroImages = [];
// Assurez-vous que votre table `villes` a une colonne nommée `hero_images` contenant des chemins séparés par des virgules.
if ($city && !empty($city['hero_images'])) {
    $cityHeroImages = array_map('trim', explode(',', $city['hero_images']));
}

// Fallback to a default image if no hero images are specified in the database for this city
if (empty($cityHeroImages)) {
    $cityHeroImages[] = 'images/default_city_hero.jpg'; // <-- REMPLACEZ ceci par le chemin de votre image de hero par défaut pour les villes sans images spécifiques
}
// --- Fin: Logique pour obtenir les images du hero dynamiquement ---

// Note: L'ancien tableau $cityHeroImagesMap n'est plus nécessaire pour le slider dynamique.

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

if ($selectedCategory === 'all') {
    // Si 'Tous les lieux' est sélectionné, on prend 6 lieux aléatoires parmi TOUTES les catégories
    // (le "à partir des autres catégories" implique de ne pas filtrer par 'all', ce qui est déjà le cas)
    $sql = "SELECT * FROM lieux WHERE id_ville = ? ORDER BY RAND() LIMIT 6";
    $sqlCount = "SELECT COUNT(*) as total FROM lieux WHERE id_ville = ?"; // Compte total pour la pagination (même si on en affiche que 6)
    // La pagination ne sera pas pertinente ici, mais on garde le count pour ne pas casser le reste du code

    // Pour le décompte affiché, on peut montrer le total réel ou juste "6 lieux aléatoires"
    // Gardons le total réel pour l'instant, le "6 lieux aléatoires" sera clair visuellement.

} else if (in_array($selectedCategory, $categories)) {
    // Si une catégorie spécifique est sélectionnée, on filtre par cette catégorie avec pagination
    $sql = "SELECT * FROM lieux WHERE id_ville = ? AND categorie = ?";
    $params[] = $selectedCategory;
    $sqlCount = "SELECT COUNT(*) as total FROM lieux WHERE id_ville = ? AND categorie = ?";
    $paramsCount = $params; // Utilise les mêmes paramètres pour le count

    // Pagination pour la catégorie spécifique
    $limit = (int)$perPage;
    $offset = (int)($perPage * ($page - 1));
    $sql .= " ORDER BY nom LIMIT $limit OFFSET $offset"; // On ordonne par nom ou autre pour la pagination

} else {
    // Cas par défaut ou catégorie invalide, afficher tous les lieux paginés (comportement par défaut si 'all' n'était pas spécial)
    // Pour garder la logique spéciale de 'all', ce bloc ne devrait pas être atteint si $selectedCategory est 'all'
    // mais on peut le laisser pour une gestion d'erreur ou pour afficher tout paginé si $selectedCategory est autre chose qu'une cat valide
    $sql = "SELECT * FROM lieux WHERE id_ville = ?";
     $sqlCount = str_replace('SELECT *', 'SELECT COUNT(*) as total', $sql);
     $paramsCount = $params;

     $limit = (int)$perPage;
     $offset = (int)($perPage * ($page - 1));
     $sql .= " ORDER BY categorie, nom LIMIT $limit OFFSET $offset";
}

// Exécution de la requête COUNT (déplacée ici pour être après la construction de $sqlCount et $paramsCount)
// Si selectedCategory est 'all', $paramsCount n'est pas défini, utilisons $params pour le count total
$stmtCount = $pdo->prepare($sqlCount);
$stmtCount->execute($selectedCategory === 'all' ? $params : $paramsCount); // Utilise $params pour 'all', $paramsCount pour catégories
$totalPlaces = $stmtCount->fetch()['total'];
$totalPages = max(1, ceil($totalPlaces / $perPage)); // Calcule le totalPages basé sur le total réel pour les catégories spécifiques

// Si selectedCategory est 'all', on force 1 page car on affiche toujours 6 résultats
if ($selectedCategory === 'all') {
    $totalPages = 1; // Pas de pagination pour 6 résultats aléatoires
}


// Récupération des lieux paginés ou aléatoires
$stmt = $pdo->prepare($sql);
// Exécute la requête avec les paramètres corrects ($params)
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

$categoryIcons = [
    'Hôtels' => 'fa-hotel',
    'Restaurants' => 'fa-utensils',
    'Parcs' => 'fa-tree',
    'Plages' => 'fa-umbrella-beach',
    'Cinémas' => 'fa-film',
    'Théâtres' => 'fa-masks-theater',
    'Monuments' => 'fa-landmark',
    'Musées' => 'fa-landmark-dome',
    'Shopping' => 'fa-shopping-bag',
    'Vie nocturne' => 'fa-martini-glass-citrus'
];

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($city['nom'] ?? 'Ville'); ?> - Maroc Authentique</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/montserrat-font.css">
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/header.css">
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
        gap: 90px;
        align-items: flex-start;
        margin-top: 40px;
        justify-content: flex-start;
    }
    .city-filters {
        width: 210px;
        min-width: 140px;
        background: #fff;
        border-radius: 18px;
        padding: 22px 10px 16px 10px;
        margin-right: 0;
        display: flex;
        flex-direction: column;
        align-items: stretch;
        border: 1px solid #f3e9d1;
        box-shadow: 0 2px 12px #e9cba722;
        margin-left: -60px;
    }
    .city-filters h3 {
        font-size: 1rem;
        margin-bottom: 14px;
        color: #2D2926;
        font-family: 'Montserrat', sans-serif;
        font-weight: 700;
        letter-spacing: 0.2px;
        text-align: left;
    }
    .filter-btns {
        display: flex;
        flex-direction: column;
        gap: 10px;
        width: 100%;
        z-index: 1;
        position: relative;
    }
    .filter-btns a.filter-btn {
        background: #f8f6f2;
        color: #bfa14a;
        border: none;
        border-radius: 12px;
        font-family: 'Montserrat', sans-serif;
        font-weight: 600;
        font-size: 0.93rem;
        padding: 7px 15px;
        text-align: left;
        text-decoration: none;
        transition: background 0.15s, color 0.15s;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 0.5em;
        outline: none;
        justify-content: flex-start;
        width: 100%;
    }
    .filter-btns a.filter-btn i {
        color: #bfa14a;
        font-size: 1em;
        margin-right: 10px;
        min-width: auto;
    }
    .filter-btns a.filter-btn.active,
    .filter-btns a.filter-btn:hover {
        background: #bfa14a;
        color: #fff;
    }
    .filter-btns a.filter-btn.active i,
    .filter-btns a.filter-btn:hover i {
        color: #fff;
    }
    .filter-btns a {
        color: #bfa14a;
        text-decoration: none;
        font-size: 0.93rem;
    }
    .city-places {
        flex: 1;
        margin-top: 0;
    }
    .places-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
    }
    .places-header h2 {
        font-size: 2rem;
        font-family: 'Playfair Display', serif;
        color: #2D2926;
        margin: 0;
        font-weight: 900;
    }
    .places-count {
        color: #8B7355;
        font-size: 1.13em;
        font-weight: 600;
    }
    .places-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 28px;
        margin-bottom: 60px;
    }
    .place-card {
        background: #fff;
        border-radius: 22px;
        box-shadow: 0 6px 32px #e9cba733;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        min-height: 340px;
        transition: box-shadow 0.2s, transform 0.2s;
        position: relative;
    }
    .place-card:hover {
        box-shadow: 0 16px 48px #bfa14a55;
        transform: translateY(-6px) scale(1.025);
    }
    .place-img img {
        width: 100%;
        height: 180px;
        object-fit: cover;
        display: block;
        border-radius: 22px 22px 0 0;
        transition: transform 0.35s cubic-bezier(.4,2,.6,1), filter 0.2s;
        filter: brightness(0.97) contrast(1.08);
    }
    .place-card:hover .place-img img {
        transform: scale(1.06);
        filter: brightness(1.03) contrast(1.13) saturate(1.1);
    }
    .place-info {
        padding: 22px 18px 18px 18px;
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
    }
    .place-title-rating {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 8px;
    }
    .place-title {
        font-size: 1.18rem;
        font-weight: 800;
        color: #2D2926;
        font-family: 'Montserrat', sans-serif;
    }
    .place-rating {
        color: #bfa14a;
        font-size: 1.08em;
        display: flex;
        align-items: center;
        gap: 2px;
    }
    .place-rating i {
        font-size: 15px !important;
    }
    .place-rating .rating-note {
        margin-left: 2px;
        font-size: 1.08em;
        color: #8B7355;
        font-weight: 600;
    }
    .place-address {
        color: #8B7355;
        font-size: 1.01em;
        margin-top: 2px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .btn-primary {
        background: #bfa14a;
        color: #fff;
        border: none;
        border-radius: 18px;
        padding: 10px 28px;
        font-size: 1.08rem;
        font-family: 'Montserrat', sans-serif;
        font-weight: 700;
        box-shadow: 0 2px 8px #e9cba733;
        margin-top: 18px;
        transition: background 0.2s, color 0.2s, box-shadow 0.2s, transform 0.2s;
        letter-spacing: 0.5px;
        text-decoration: none;
        display: inline-block;
        align-self: flex-start;
    }
    .btn-primary:hover {
        background: #8B7355;
        color: #fff;
        box-shadow: 0 6px 24px #bfa14a99;
        transform: translateY(-2px) scale(1.04);
    }
    @media (max-width: 1100px) {
        .city-main-grid { flex-direction: column; gap: 24px; }
        .city-filters { width: 100%; min-width: 0; margin-bottom: 24px; align-items: stretch; }
    }
    @media (max-width: 700px) {
        .city-main-grid { flex-direction: column; gap: 18px; }
        .city-filters { width: 100%; min-width: 0; margin-bottom: 18px; padding: 18px 4px 12px 4px; }
    }
    .pagination {
        display: flex;
        justify-content: center;
        gap: 8px;
        margin: 32px 0 48px 0;
        flex-wrap: wrap;
    }
    .pagination-btn {
        background: #fff;
        color: #bfa14a;
        border: 1.5px solid #e9cba7;
        border-radius: 18px;
        padding: 8px 16px;
        font-size: 0.95rem;
        font-family: 'Montserrat', sans-serif;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease-in-out;
        outline: none;
        text-decoration: none;
        box-shadow: 0 1px 6px rgba(0,0,0,0.08);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 38px;
        box-sizing: border-box;
    }
    .pagination-btn.active,
    .pagination-btn:focus {
        background: #bfa14a;
        color: #fff;
        border-color: #bfa14a;
        box-shadow: 0 3px 10px rgba(191,161,74,0.3);
    }
    .pagination-btn:hover:not(.active) {
        background: #fcf8f2;
        color: #8B7355;
        border-color: #bfa14a;
        box-shadow: 0 2px 8px rgba(191,161,74,0.2);
    }
    .pagination-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        background: #f8f8f8;
        color: #aaa;
        border-color: #eee;
        box-shadow: none;
    }
    .hero {
        min-height: 60vh;
        height: auto !important;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        margin-bottom: 40px;
        overflow: hidden; /* Important to contain arrow positioning */
    }
    .hero-background {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        transition: background-image 1.5s ease-in-out;
        z-index: 1; /* Below content and arrows */
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
    header {
        position: absolute;
        top: 0;
        left: 0;
        width: 100vw;
        z-index: 100;
        background: transparent !important;
        box-shadow: none !important;
        transition: transform 0.35s;
    }
    .nav-menu li a {
        font-size: 1.35rem !important;
        font-weight: 600;
        letter-spacing: 0.2px;
        color: #222;
        transition: color 0.2s, border-bottom 0.2s;
        border-bottom: 3px solid transparent;
        padding-bottom: 4px;
    }
    .nav-menu li a.active,
    .nav-menu li a:hover {
        color: #bfa14a !important;
        border-bottom: 3px solid #f3e9d1;
    }
    .auth-buttons {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    .auth-buttons a {
        font-size: 1.13rem !important;
        font-weight: 600;
        padding: 10px 26px;
        text-decoration: none;
        transition: all 0.2s ease-in-out;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-top: 0 !important;
        flex-shrink: 0;
    }
    .btn-outline {
        background: transparent;
        color: #222;
        border: 2px solid #bfa14a;
        border-radius: 18px;
    }
    .btn-outline:hover {
         background: #bfa14a;
         color: #fff !important;
         border-color: #bfa14a;
    }
    .btn-primary {
        background: #bfa14a;
        color: #fff;
        border: none;
        border-radius: 18px;
        box-shadow: 0 2px 8px #e9cba733;
    }
    .btn-primary:hover {
        background: #8B7355;
        box-shadow: 0 4px 16px #bfa14a55;
    }
    .logo-img {
        height: 80px !important;
        transition: height 0.2s ease-in-out;
    }
    .container {
        padding-left: 0;
    }
    </style>
</head>
<body>
    <!-- HERO FULL WIDTH AVEC HEADER -->
    <?php if ($city): ?>
    <section class="hero" style="height:120vh; min-height:800px; display:flex; align-items:center; justify-content:center; position:relative; width:100vw; max-width:100vw; margin-left:calc(-50vw + 50%); overflow: hidden;">
        <div class="hero-background"></div>
        
        
        <?php include 'includes/header.php'; ?>
        <div class="hero-overlay"></div>
        <div class="hero-content premium">
        </div>
    </section>
    <?php endif; ?>

    <main style="margin-top:100px;">
        <div class="container">
            <?php if ($city): ?>
            <div class="city-main-grid">
                <aside class="city-filters">
                    <h3>Filtrer par catégorie</h3>
                    <div class="filter-btns">
                        <a href="?id=<?= $cityId ?>&categorie=all&page=1" class="filter-btn<?= $selectedCategory === 'all' ? ' active' : '' ?>">
                            <i class="fas fa-globe"></i> Tous les lieux
                        </a>
                        <?php foreach ($categories as $cat): ?>
                            <a href="?id=<?= $cityId ?>&categorie=<?= urlencode($cat) ?>&page=1" class="filter-btn<?= $selectedCategory === $cat ? ' active' : '' ?>">
                                <i class="fas <?= $categoryIcons[$cat] ?>"></i> <?= htmlspecialchars($cat) ?>
                            </a>
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
                    <!-- Social links supprimés -->
                </div>
                <div class="footer-col">
                    <h3>Liens Rapides</h3>
                    <ul>
                        <li><a href="index.php">Accueil</a></li>
                        <li><a href="destinations.php">Destinations</a></li>
                        <li><a href="recommendations.php">Recommenadations</a></li>
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
                <p style="font-family: 'Montserrat', sans-serif;">© 2025 Maroc Authentique. Tous droits réservés.</p>
                <p style="font-family: 'Montserrat', sans-serif; margin-top: 10px;">
                    <a href="politique-confidentialite.php" style="color: #8B7355; text-decoration: none; font-family: 'Montserrat', sans-serif;">Politique de confidentialité</a> | 
                    <a href="conditions-utilisation.php" style="color: #8B7355; text-decoration: none; font-family: 'Montserrat', sans-serif;">Conditions d'utilisation</a>
                </p>
            </div>
        </div>
    </footer>

    <script>
    // Header hide/show on scroll
    let lastScroll = 0;
    const header = document.querySelector('header');
    const hero = document.querySelector('.hero');
    const heroBackground = document.querySelector('.hero-background'); // Get the new background element

    window.addEventListener('scroll', function() {
        const currentScroll = window.pageYOffset;
        const heroHeight = hero ? hero.offsetHeight : 0;
        if (currentScroll > heroHeight) {
            if (currentScroll > lastScroll) {
                // Scroll down, hide header
                header.style.transform = 'translateY(-120%)';
                header.style.transition = 'transform 0.35s';
            } else {
                // Scroll up, show header
                header.style.transform = 'translateY(0)';
                header.style.transition = 'transform 0.35s';
            }
        } else {
            // Toujours visible sur le hero
            header.style.transform = 'translateY(0)';
            header.style.transition = 'transform 0.35s';
        }
        lastScroll = currentScroll;
    });

    // Rotation des images du hero
    const heroImages = <?php echo json_encode($cityHeroImages ?? []); ?>; // Use dynamic images from PHP
    let currentImageIndex = 0;
    let autoSlideInterval;

    // Fonction pour changer l'image sur l'élément background
    function changeHeroImage(index) {
        if (heroImages.length === 0 || !heroBackground) return; // Check heroBackground instead of hero
        
        currentImageIndex = index;
        heroBackground.style.backgroundImage = `url('${heroImages[currentImageIndex]}')`;
        // Background properties are now in the .hero-background CSS class
        // heroBackground.style.backgroundSize = 'cover';
        // heroBackground.style.backgroundPosition = 'center';
        // heroBackground.style.backgroundRepeat = 'no-repeat';
    }

    // Fonction pour passer à l'image suivante (utilisée par l'intervalle automatique et la flèche droite)
    function nextSlide() {
        const nextIndex = (currentImageIndex + 1) % heroImages.length;
        changeHeroImage(nextIndex);
    }

    // Fonction pour passer à l'image précédente (utilisée par la flèche gauche)
    function prevSlide() {
        const prevIndex = (currentImageIndex - 1 + heroImages.length) % heroImages.length;
        changeHeroImage(prevIndex);
    }

    // Démarre la rotation automatique
    function startAutoSlide() {
        // S'assure qu'il n'y a pas déjà un intervalle en cours
        stopAutoSlide(); 
        autoSlideInterval = setInterval(nextSlide, 7000); // Change l'image toutes les 7 secondes
    }

    // Arrête la rotation automatique
    function stopAutoSlide() {
        clearInterval(autoSlideInterval);
        autoSlideInterval = null; // Réinitialise la variable
    }

    // Initialisation : Définir la première image et démarrer la rotation automatique après un délai
    if (heroBackground && heroImages.length > 0) { // Check heroBackground instead of hero
         // Définir la première image au chargement (sans attendre)
         changeHeroImage(0); 
         // Démarre la rotation automatique après 2 secondes
         // Utilisez une référence à nextSlide pour le premier changement après délai
         setTimeout(() => {
             nextSlide(); // Effectue le premier changement après 2s
             startAutoSlide(); // Démarre ensuite l'intervalle pour les changements suivants
         }, 2000);
    }

    // Gestion des clics sur les flèches
    

    // Optionnel : Arrêter la rotation quand l'utilisateur quitte la page pour économiser les ressources
    window.addEventListener('beforeunload', function() {
        stopAutoSlide();
    });

    </script>
</body>
</html> 