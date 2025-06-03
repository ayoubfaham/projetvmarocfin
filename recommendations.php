<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'config/database.php';

// Fonction pour obtenir l'icône correspondant à chaque catégorie
function getInterestIcon($interest) {
    switch ($interest) {
        case 'Tous les lieux':
            return 'fas fa-globe';
        case 'Hôtels':
            return 'fas fa-hotel';
        case 'Restaurants':
            return 'fas fa-utensils';
        case 'Parcs':
            return 'fas fa-tree';
        case 'Plages':
            return 'fas fa-umbrella-beach';
        case 'Cinémas':
            return 'fas fa-film';
        case 'Théâtres':
            return 'fas fa-theater-masks';
        case 'Monuments':
            return 'fas fa-monument';
        case 'Musées':
            return 'fas fa-landmark';
        case 'Shopping':
            return 'fas fa-shopping-bag';
        case 'Vie nocturne':
            return 'fas fa-cocktail';
        default:
            return 'fas fa-map-marker-alt';
    }
}

// Récupérer les villes depuis la base
try {
    $stmt = $pdo->query("SELECT nom FROM villes ORDER BY nom");
    $cities = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $cities = [];
}

// Centres d'intérêt
$interests = [
    'Tous les lieux', 'Hôtels', 'Restaurants', 'Parcs', 'Plages', 'Cinémas',
    'Théâtres', 'Monuments', 'Musées', 'Shopping', 'Vie nocturne'
];

// Budgets
$budgets = ['Économique', 'Moyen', 'Luxe'];

// Traitement du formulaire
$errors = [];
$selectedCity = $_POST['city'] ?? '';
$selectedInterests = $_POST['interests'] ?? [];
$arrivee = $_POST['arrivee'] ?? '';
$depart = $_POST['depart'] ?? '';
$budget = $_POST['budget'] ?? '';

// Débogage - Afficher les valeurs reçues
error_log('Ville: ' . $selectedCity);
error_log('Intérêts: ' . print_r($selectedInterests, true));
error_log('Budget: ' . $budget);

$recommendations = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Rendre les validations moins strictes pour faciliter les tests
    if (!$selectedCity) $errors['city'] = 'Veuillez sélectionner une ville';
    // Commentons ces validations pour permettre des recherches plus flexibles
    // if (empty($selectedInterests)) $errors['interests'] = 'Veuillez sélectionner au moins un centre d\'intérêt';
    // if (!$arrivee) $errors['arrivee'] = 'Veuillez indiquer la date d\'arrivée';
    // if (!$depart) $errors['depart'] = 'Veuillez indiquer la date de départ';
    // if (!$budget) $errors['budget'] = 'Veuillez choisir un budget';
    // Si pas d'erreurs, on cherche les recommandations
    if (!empty($_POST['submitted']) && empty($errors)) {
        // Calculer la durée du séjour en jours
        $duree = null;
        if (!empty($arrivee) && !empty($depart)) {
            $dateArrivee = new DateTime($arrivee);
            $dateDepart = new DateTime($depart);
            $interval = $dateArrivee->diff($dateDepart);
            $duree = $interval->days;
        }
        
        // Vérifier si des filtres sont appliqués
        $noFilters = empty($selectedCity) && empty($selectedInterests) && empty($budget) && empty($duree);
        
        if ($noFilters) {
            // Aucun filtre sélectionné : afficher tous les lieux
            $sql = "SELECT * FROM lieux ORDER BY id DESC LIMIT 10";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $recommendations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // Récupérer l'ID de la ville sélectionnée
            $cityId = null;
            if (!empty($selectedCity)) {
                $stmt = $pdo->prepare("SELECT id FROM villes WHERE nom = ?");
                $stmt->execute([$selectedCity]);
                $cityId = $stmt->fetchColumn();
            }
            
            // Construire la requête SQL avec tous les filtres
            // Ajout de la jointure avec la table villes pour avoir plus d'informations
            $sql = "SELECT l.*, v.nom as ville_nom FROM lieux l 
                   LEFT JOIN villes v ON l.id_ville = v.id 
                   WHERE 1=1";
            $params = [];
            
            // Filtre par ville
            if ($cityId) {
                $sql .= " AND l.id_ville = :id_ville";
                $params['id_ville'] = $cityId;
            }
            
            // Filtre par centres d'intérêt - Approche plus flexible
            if (!empty($selectedInterests)) {
                // Débogage
                error_log('Intérêts sélectionnés: ' . implode(', ', $selectedInterests));
                
                // Vérifier si 'Plage' est sélectionné et ajouter des alternatives possibles
                $expandedInterests = [];
                foreach ($selectedInterests as $interest) {
                    $expandedInterests[] = $interest;
                    
                    // Ajouter des variations courantes
                    if (strtolower($interest) === 'plage') {
                        $expandedInterests[] = 'Plages';
                        $expandedInterests[] = 'Bord de mer';
                        $expandedInterests[] = 'Littoral';
                    }
                    if (strtolower($interest) === 'culture') {
                        $expandedInterests[] = 'Culturel';
                        $expandedInterests[] = 'Musée';
                    }
                    // Ajouter d'autres variations si nécessaire
                }
                
                $likeClauses = [];
                foreach ($expandedInterests as $idx => $interest) {
                    // Recherche beaucoup plus souple pour les catégories
                    $likeClauses[] = "LOWER(l.categorie) LIKE LOWER(:interest$idx)";
                    $params["interest$idx"] = "%$interest%";
                }
                $sql .= " AND (" . implode(" OR ", $likeClauses) . ")";
            }
            
            // Filtre par budget - Approche beaucoup plus flexible
            if ($budget) {
                // Débogage
                error_log('Budget sélectionné: ' . $budget);
                
                // Créer des variations possibles du budget
                $budgetVariations = [$budget];
                
                // Ajouter des variations courantes
                if (strtolower($budget) === 'économique') {
                    $budgetVariations[] = 'economique'; // sans accent
                    $budgetVariations[] = 'bas';
                    $budgetVariations[] = 'petit budget';
                } elseif (strtolower($budget) === 'moyen') {
                    $budgetVariations[] = 'standard';
                    $budgetVariations[] = 'modere';
                    $budgetVariations[] = 'modéré';
                } elseif (strtolower($budget) === 'luxe') {
                    $budgetVariations[] = 'premium';
                    $budgetVariations[] = 'haut de gamme';
                    $budgetVariations[] = 'vip';
                }
                
                // Créer une clause OR pour toutes les variations possibles
                $budgetClauses = [];
                foreach ($budgetVariations as $idx => $budgetVar) {
                    $budgetClauses[] = "LOWER(l.budget) LIKE LOWER(:budget$idx)";
                    $params["budget$idx"] = "%$budgetVar%";
                }
                $sql .= " AND (" . implode(" OR ", $budgetClauses) . ")";
            }
            
            // Note: Nous ne filtrons pas par durée pour l'instant car nous ne connaissons pas
            // la structure exacte de la table lieux concernant la durée recommandée
            // Mais nous gardons la durée calculée pour l'afficher dans les résultats
            
            // Limiter et ordonner les résultats
            $sql .= " ORDER BY l.id DESC LIMIT 10";
            
            // Débogage: Enregistrer la requête SQL et les paramètres
            error_log('SQL Query: ' . $sql);
            error_log('Params: ' . print_r($params, true));
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $recommendations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Débogage: Nombre de résultats trouvés
            error_log('Nombre de résultats: ' . count($recommendations));
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Recommandations Personnalisées - VMaroc</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/header.css">
    <style>
        body {
            background: #f8f6f3;
            font-family: 'Montserrat', Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .main-title {
            text-align: center;
            font-size: 2.5rem;
            font-weight: 700;
            margin-top: 100px;
            margin-bottom: 10px;
            font-family: 'Montserrat', sans-serif;
            color: #2D2926;
        }
        .subtitle {
            text-align: center;
            color: #6b7280;
            margin-bottom: 40px;
        }
        .recommend-box {
            background: rgba(255, 255, 255, 0.4);
            padding: 30px 8vw;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.15);
            max-width: 800px;
            margin: 0 auto 40px auto;
            backdrop-filter: blur(8px);
            border: none;
        }
        .section-title {
            font-size: 1.15rem;
            font-weight: 600;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #2D2926;
            text-shadow: 0 1px 1px rgba(0,0,0,0.2);
            background-color: transparent;
            padding: 8px 0;
            border-radius: 0;
        }
        .city-list, .interest-list, .budget-list {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            margin-bottom: 10px;
        }
        /* Styles communs pour tous les boutons de sélection */
        
        .city-btn, .interest-btn, .budget-btn {
            background: rgba(241, 245, 249, 0.5);
            border: none;
            border-radius: 12px;
            padding: 10px 18px;
            font-size: 0.9rem;
            color: #2D2926;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(5px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.08);
        }
        .city-btn:hover, .interest-btn:hover, .budget-btn:hover {
            background: #e2e8f0;
            transform: translateY(-2px);
        }
        .city-btn.selected, .interest-btn.selected, .budget-btn.selected {
            background: #b48a3c;
            color: white;
            box-shadow: 0 3px 8px rgba(180, 138, 60, 0.4);
            transform: translateY(-1px);
        }
        .interest-btn i {
            margin-right: 8px;
        }
        .city-btn:focus, .interest-btn:focus, .budget-btn:focus {
            outline: 2px solid #0e637a;
        }
        .error {
            color: #e11d48;
            font-size: 0.98rem;
            margin-bottom: 10px;
        }
        .dates-row {
            display: flex;
            gap: 18px;
            margin-bottom: 10px;
        }
        .dates-row input[type="date"] {
            flex: 1;
            padding: 12px;
            border-radius: 10px;
            border: 1.5px solid #e5e7eb;
            font-size: 1rem;
        }
        .submit-btn {
            width: 100%;
            background: #0e637a;
            color: #fff;
            border: none;
            border-radius: 12px;
            padding: 16px 0;
            font-size: 1.1rem;
            font-weight: 600;
            margin-top: 18px;
            cursor: pointer;
            opacity: 0.7;
            transition: opacity 0.2s;
        }
        .submit-btn.enabled {
            opacity: 1;
            cursor: pointer;
        }
        /* Mise en page adaptative pour le mode résultats */
        #page-content {
            transition: all 0.5s ease-in-out;
        }
        
        #page-content.results-mode {
            display: flex;
            flex-direction: row;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        #page-content.results-mode .recommend-box {
            width: 35%;
            margin: 0;
            padding: 20px;
        }
        
        #page-content.results-mode .recommendations-container {
            width: 60%;
            margin: 0;
        }
        
        @media (max-width: 992px) {
            #page-content.results-mode {
                flex-direction: column;
            }
            
            #page-content.results-mode .recommend-box,
            #page-content.results-mode .recommendations-container {
                width: 100%;
                max-width: 800px;
                margin: 0 auto 20px auto;
            }
        }
        
        @media (max-width: 768px) {
            .recommend-box { padding: 20px 6vw; }
            .main-title { font-size: 1.5rem; }
        }
    </style>
</head>
<?php
// Tableau des images d'arrière-plan disponibles
$backgroundImages = [
    'images/recommandation.png',
    'images/recommandation1.png',
    'images/recommandation2.png',
    'images/recommandation3.png',
    'images/recommandation4.png',
    'images/recommandation5.png',
    'images/recommandation6.png',
    'images/recommandation7.png'
];

// Convertir le tableau PHP en JSON pour JavaScript
$backgroundImagesJson = json_encode($backgroundImages);
?>
<body>
    <!-- Conteneurs pour les images d'arrière-plan avec transition -->
    <div id="backgroundContainer" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -2;">
        <div id="background1" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-size: cover; background-position: center center; transition: opacity 1s ease-in-out; opacity: 1;"></div>
        <div id="background2" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-size: cover; background-position: center center; transition: opacity 1s ease-in-out; opacity: 0;"></div>
    </div>
    
    <!-- Overlay semi-transparent pour améliorer la lisibilité -->
    <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: -1;"></div>
    
    <script>
    // Récupérer le tableau d'images depuis PHP
    const backgroundImages = <?= $backgroundImagesJson ?>;
    let currentIndex = 0;
    let nextIndex = 1;
    const bg1 = document.getElementById('background1');
    const bg2 = document.getElementById('background2');
    
    // Initialiser les deux premiers arrière-plans
    bg1.style.backgroundImage = `url('${backgroundImages[0]}')`;
    bg2.style.backgroundImage = `url('${backgroundImages[1]}')`;
    
    // Fonction pour changer l'arrière-plan
    function changeBackground() {
        // Inverser l'opacité des deux éléments d'arrière-plan
        if (bg1.style.opacity === '1') {
            bg1.style.opacity = '0';
            bg2.style.opacity = '1';
        } else {
            bg1.style.opacity = '1';
            bg2.style.opacity = '0';
        }
        
        // Préparer la prochaine image
        nextIndex = (nextIndex + 1) % backgroundImages.length;
        setTimeout(() => {
            // Mettre à jour l'image de l'arrière-plan invisible
            if (bg1.style.opacity === '0') {
                bg1.style.backgroundImage = `url('${backgroundImages[nextIndex]}')`;
            } else {
                bg2.style.backgroundImage = `url('${backgroundImages[nextIndex]}')`;
            }
        }, 1000); // Attendre que la transition soit terminée
    }
    
    // Changer l'arrière-plan toutes les 2 secondes
    setInterval(changeBackground, 2000);
    </script>
    <?php include 'includes/header.php'; ?>

    <h1 class="main-title" style="color: white; text-shadow: 2px 2px 4px rgba(0,0,0,0.5);">Recommandations Personnalisées</h1>
    <div class="subtitle" style="color: #f0f0f0; text-shadow: 1px 1px 3px rgba(0,0,0,0.5);">Partagez vos préférences pour découvrir des lieux qui correspondent à vos intérêts</div>
    
    <div id="page-content" class="<?= (!empty($_POST['submitted']) && empty($errors)) ? 'results-mode' : '' ?>">

    <form class="recommend-box" method="POST" id="recommendForm" autocomplete="off">
        <input type="hidden" name="submitted" value="1">
        <div class="section-title"><i class="fas fa-map-marker-alt"></i> Choisissez une ville</div>
        <div class="city-list">
                        <?php foreach ($cities as $city): ?>
                <button type="button" class="city-btn<?= ($selectedCity === $city) ? ' selected' : '' ?>" data-value="<?= htmlspecialchars($city) ?>"> <?= htmlspecialchars($city) ?> </button>
                        <?php endforeach; ?>
                </div>
        <?php if (!empty($_POST['submitted']) && !empty($errors['city'])): ?><div class="error"> <?= $errors['city'] ?> </div><?php endif; ?>
        <input type="hidden" name="city" id="cityInput" value="<?= htmlspecialchars($selectedCity) ?>">

        <div class="section-title" style="margin-top:28px;"><i class="fas fa-heart"></i> Centres d'intérêt</div>
        <div class="interest-list">
            <?php foreach ($interests as $interest): ?>
                <button type="button" class="interest-btn<?= (in_array($interest, $selectedInterests)) ? ' selected' : '' ?>" data-value="<?= htmlspecialchars($interest) ?>">
                    <i class="<?= getInterestIcon($interest) ?>" style="margin-right: 8px;"></i>
                    <?= htmlspecialchars($interest) ?>
                </button>
            <?php endforeach; ?>
        </div>
        <?php if (!empty($_POST['submitted']) && !empty($errors['interests'])): ?><div class="error"> <?= $errors['interests'] ?> </div><?php endif; ?>
        <div id="interestsHiddenInputs">
            <?php foreach ($selectedInterests as $interest): ?>
                <input type="hidden" name="interests[]" value="<?= htmlspecialchars($interest) ?>">
            <?php endforeach; ?>
        </div>

        <div class="section-title" style="margin-top:28px;"><i class="fas fa-calendar-alt"></i> Durée du séjour</div>
        <div class="dates-row">
            <input type="date" name="arrivee" id="arrivee" value="<?= htmlspecialchars($arrivee) ?>" placeholder="jj/mm/aaaa">
            <input type="date" name="depart" id="depart" value="<?= htmlspecialchars($depart) ?>" placeholder="jj/mm/aaaa">
                </div>
        <?php if (!empty($_POST['submitted']) && !empty($errors['arrivee'])): ?><div class="error"> <?= $errors['arrivee'] ?> </div><?php endif; ?>
        <?php if (!empty($_POST['submitted']) && !empty($errors['depart'])): ?><div class="error"> <?= $errors['depart'] ?> </div><?php endif; ?>

        <div class="section-title" style="margin-top:28px;"><i class="fas fa-dollar-sign"></i> Budget estimé</div>
        <div class="budget-list">
            <?php foreach ($budgets as $b): ?>
                <button type="button" class="budget-btn<?= ($budget === $b) ? ' selected' : '' ?>" data-value="<?= htmlspecialchars($b) ?>"> <?= htmlspecialchars($b) ?> </button>
            <?php endforeach; ?>
        </div>
        <?php if (!empty($_POST['submitted']) && !empty($errors['budget'])): ?><div class="error"> <?= $errors['budget'] ?> </div><?php endif; ?>
        <input type="hidden" name="budget" id="budgetInput" value="<?= htmlspecialchars($budget) ?>">

        <div style="display:flex;gap:15px;margin-top:10px;">
            <button type="submit" class="submit-btn" id="submitBtn" disabled style="flex:1">Obtenir des recommandations</button>
            <a href="recommendations.php" id="resetBtn" style="padding:12px 20px;background:#f1f5f9;color:#64748b;border-radius:12px;text-decoration:none;font-weight:500;display:flex;align-items:center;justify-content:center;transition:all 0.2s;" onmouseover="this.style.background='#e2e8f0';this.style.color='#475569'" onmouseout="this.style.background='#f1f5f9';this.style.color='#64748b'">
                <i class="fas fa-redo-alt" style="margin-right:8px;"></i> Réinitialiser
            </a>
        </div>
    </form>
    <?php if (!empty($_POST['submitted']) && empty($errors)): ?>
        <div class="recommendations-container" style="max-width:800px;margin:0 auto 40px auto;background-color:rgba(255, 255, 255, 0.4);padding:30px;border-radius:15px;box-shadow:0 8px 32px rgba(0,0,0,0.15);backdrop-filter:blur(8px);border:none;">
            <h2 style="font-size:1.6rem;margin-bottom:20px;font-family:'Montserrat',sans-serif;color:#2D2926;text-shadow:0 1px 2px rgba(0,0,0,0.2);font-weight:600;">Suggestions pour <?= htmlspecialchars($selectedCity) ?></h2>
            
            <!-- Affichage des critères de recherche -->
            <div style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:25px;padding-bottom:15px;border-bottom:1px solid rgba(241, 241, 241, 0.4);">
                <?php if (!empty($selectedCity)): ?>
                    <span style="display:inline-block;background:rgba(224, 231, 239, 0.6);color:#0e637a;padding:5px 12px;border-radius:20px;font-size:0.9em;backdrop-filter:blur(3px);">
                        <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($selectedCity) ?>
                    </span>
                <?php endif; ?>
                
                <?php if (!empty($selectedInterests)): ?>
                    <?php foreach($selectedInterests as $interest): ?>
                        <span style="display:inline-block;background:rgba(240, 230, 210, 0.6);color:#b48a3c;padding:5px 12px;border-radius:20px;font-size:0.9em;backdrop-filter:blur(3px);">
                            <i class="fas fa-tag"></i> <?= htmlspecialchars($interest) ?>
                        </span>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <?php if (!empty($budget)): ?>
                    <span style="display:inline-block;background:rgba(230, 247, 230, 0.6);color:#2e7d32;padding:5px 12px;border-radius:20px;font-size:0.9em;backdrop-filter:blur(3px);">
                        <i class="fas fa-wallet"></i> <?= htmlspecialchars($budget) ?>
                    </span>
                <?php endif; ?>
                
                <?php if (!empty($duree)): ?>
                    <span style="display:inline-block;background:rgba(245, 230, 255, 0.6);color:#7b1fa2;padding:5px 12px;border-radius:20px;font-size:0.9em;backdrop-filter:blur(3px);">
                        <i class="fas fa-calendar-alt"></i> <?= htmlspecialchars($duree) ?> jour<?= $duree > 1 ? 's' : '' ?>
                    </span>
                <?php endif; ?>
            </div>
            <?php if (empty($recommendations)): ?>
                <div style="color:#e11d48;text-align:center;padding:20px;background:#fff5f7;border-radius:10px;">
                    <i class="fas fa-exclamation-circle" style="font-size:24px;margin-bottom:10px;"></i>
                    <p style="margin:0;">Aucun lieu trouvé correspondant à vos critères.</p>
                    <p style="margin-top:5px;font-size:0.9em;">Essayez de modifier vos filtres pour obtenir plus de résultats.</p>
                    <?php if (isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'Firefox') !== false): ?>
                    <!-- Débogage: Afficher les détails de la requête pour les développeurs -->
                    <div style="margin-top:15px;text-align:left;font-size:0.8em;color:#666;background:#f8f8f8;padding:10px;border-radius:5px;">
                        <strong>Débogage:</strong><br>
                        <pre><?= htmlspecialchars($sql) ?></pre>
                        <strong>Paramètres:</strong><br>
                        <pre><?= htmlspecialchars(print_r($params, true)) ?></pre>
                    </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <?php foreach ($recommendations as $lieu): ?>
                    <div style="background:rgba(249, 248, 246, 0.6);border-radius:15px;padding:22px 25px;margin-bottom:25px;display:flex;gap:22px;align-items:flex-start;transition:all 0.3s;box-shadow:0 2px 8px rgba(0,0,0,0.03);backdrop-filter:blur(5px);" onmouseover="this.style.transform='translateY(-5px)';this.style.boxShadow='0 6px 16px rgba(0,0,0,0.1)'" onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='0 2px 8px rgba(0,0,0,0.03)'">
                        <?php if (!empty($lieu['photo'])): ?>
                            <img src="<?= htmlspecialchars($lieu['photo']) ?>" alt="<?= htmlspecialchars($lieu['nom']) ?>" style="width:120px;height:120px;max-width:120px;border-radius:10px;margin-bottom:0;object-fit:cover;">
                        <?php endif; ?>
                        <div style="flex:1;">
                            <div style="font-weight:600;font-size:1.1rem;display:flex;align-items:center;gap:8px;">
                                <?= htmlspecialchars($lieu['nom']) ?>
                                <?php if (!empty($lieu['categorie'])): ?>
                                    <span style="display:inline-block;background:#e0e7ef;color:#0e637a;padding:2px 10px;border-radius:12px;font-size:0.92em;">
                                        <?= htmlspecialchars($lieu['categorie']) ?>
                                    </span>
                                <?php endif; ?>
                                <?php if (!empty($lieu['budget'])): ?>
                                    <span style="display:inline-block;background:#f7e7c1;color:#b48a3c;padding:2px 10px;border-radius:12px;font-size:0.92em;">
                                        <?= htmlspecialchars($lieu['budget']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div style="color:#6b7280;margin:6px 0 8px 0;">
                                <?= htmlspecialchars($lieu['description']) ?>
                            </div>
                            <div style="font-size:0.98rem;">
                                <span style="color:#0e637a;"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($selectedCity) ?></span>
                            </div>
                            <div style="margin-top:15px;">
                                <a href="place.php?id=<?= urlencode($lieu['id']) ?>" style="display:inline-block;padding:10px 20px;background:#b48a3c;color:#fff;border-radius:8px;text-decoration:none;font-weight:500;transition:all 0.3s;" onmouseover="this.style.background='#9b7633';this.style.transform='translateY(-2px)'" onmouseout="this.style.background='#b48a3c';this.style.transform='translateY(0)'">Voir la fiche du lieu <i class="fas fa-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    </div> <!-- Fermeture de la div page-content -->
    
    <script>
    // Activer le mode résultats si le formulaire a été soumis avec succès
    <?php if (!empty($_POST['submitted']) && empty($errors)): ?>
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('page-content').classList.add('results-mode');
    });
    <?php endif; ?>
    
    // Sélection ville
    document.querySelectorAll('.city-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.city-btn').forEach(b => b.classList.remove('selected'));
            this.classList.add('selected');
            document.getElementById('cityInput').value = this.dataset.value;
            checkForm();
        });
    });
    // Sélection intérêts
    // Réinitialiser les intérêts sélectionnés lors du chargement de la page
    let selectedInterests = [];
    
    // Pré-sélectionner les intérêts déjà choisis (s'il y en a)
    <?php if (!empty($selectedInterests)): ?>
    selectedInterests = <?= json_encode($selectedInterests) ?>;
    // Marquer les boutons correspondants comme sélectionnés
    selectedInterests.forEach(interest => {
        const btn = document.querySelector(`.interest-btn[data-value="${interest}"]`);
        if (btn) btn.classList.add('selected');
    });
    <?php endif; ?>
    
    // Fonction pour mettre à jour les champs cachés des intérêts
    function updateInterestsInputs() {
        const container = document.getElementById('interestsHiddenInputs');
        container.innerHTML = '';
        
        selectedInterests.forEach(interest => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'interests[]';
            input.value = interest;
            container.appendChild(input);
        });
    }
    
    // Gestionnaire d'événements pour les boutons d'intérêt
    document.querySelectorAll('.interest-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const val = this.dataset.value;
            if (this.classList.contains('selected')) {
                this.classList.remove('selected');
                selectedInterests = selectedInterests.filter(i => i !== val);
            } else {
                this.classList.add('selected');
                selectedInterests.push(val);
            }
            updateInterestsInputs();
            checkForm();
        });
    });
    // Sélection budget
    document.querySelectorAll('.budget-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.budget-btn').forEach(b => b.classList.remove('selected'));
            this.classList.add('selected');
            document.getElementById('budgetInput').value = this.dataset.value;
            checkForm();
        });
    });
    // Validation JS
    function checkForm() {
        const cityValue = document.getElementById('cityInput').value;
        const submitBtn = document.getElementById('submitBtn');
        
        // Activer le bouton si au moins la ville est sélectionnée
        submitBtn.disabled = !cityValue;
    }
    document.getElementById('arrivee').addEventListener('input', checkForm);
    document.getElementById('depart').addEventListener('input', checkForm);
    
    // Bouton de réinitialisation
    document.getElementById('resetBtn').addEventListener('click', function(e) {
        // Si on est en mode résultats, on peut empêcher le comportement par défaut
        // et réinitialiser le formulaire sans recharger la page
        if (document.getElementById('page-content').classList.contains('results-mode')) {
            e.preventDefault();
            
            // Réinitialiser les sélections
            document.querySelectorAll('.city-btn').forEach(b => b.classList.remove('selected'));
            document.querySelectorAll('.interest-btn').forEach(b => b.classList.remove('selected'));
            document.querySelectorAll('.budget-btn').forEach(b => b.classList.remove('selected'));
            
            // Réinitialiser les champs cachés
            document.getElementById('cityInput').value = '';
            document.getElementById('budgetInput').value = '';
            selectedInterests = [];
            updateInterestsInputs();
            
            // Réinitialiser les dates
            document.getElementById('arrivee').value = '';
            document.getElementById('depart').value = '';
            
            // Désactiver le bouton de soumission
            document.getElementById('submitBtn').disabled = true;
            
            // Revenir à la mise en page initiale
            document.getElementById('page-content').classList.remove('results-mode');
        }
    });
    
    // Initial check
    checkForm();
    // Initialiser les champs cachés des intérêts au chargement de la page
    updateInterestsInputs();
    </script>
    
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
