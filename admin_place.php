<?php
session_start();
require_once 'config/database.php';

// Vérifier si l'utilisateur est connecté et est un administrateur
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: login.php');
    exit;
}

$error_message = '';
$success_message = '';

// Récupération de l'ID du lieu depuis l'URL
$placeId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Récupération des informations du lieu
$place = null;
if ($placeId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM lieux WHERE id = ?");
    $stmt->execute([$placeId]);
    $place = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$place) {
        $error_message = "Ce lieu n'existe pas.";
    }
}

// Traitement du formulaire de mise à jour
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_place'])) {
    $equipements = isset($_POST['equipements']) ? trim($_POST['equipements']) : '';
    $boutiques_services = isset($_POST['boutiques_services']) ? trim($_POST['boutiques_services']) : '';
    $url_activites = isset($_POST['url_activites']) ? trim($_POST['url_activites']) : '';
    
    try {
        // Vérifier si la colonne url_activites existe, sinon l'ajouter
        $checkColumn = $pdo->query("SHOW COLUMNS FROM lieux LIKE 'url_activites'");
        if ($checkColumn->rowCount() == 0) {
            $pdo->exec("ALTER TABLE lieux ADD COLUMN url_activites VARCHAR(500) DEFAULT ''");
        }
        
        $stmt = $pdo->prepare("UPDATE lieux SET equipements = ?, boutiques_services = ?, url_activites = ? WHERE id = ?");
        $stmt->execute([$equipements, $boutiques_services, $url_activites, $placeId]);
        
        $success_message = "Les informations du lieu ont été mises à jour avec succès.";
        
        // Mettre à jour les données locales
        $place['equipements'] = $equipements;
        $place['boutiques_services'] = $boutiques_services;
        $place['url_activites'] = $url_activites;
    } catch (PDOException $e) {
        $error_message = "Erreur lors de la mise à jour : " . $e->getMessage();
    }
}

$stmt = $pdo->query("SELECT lieux.*, villes.nom AS ville_nom FROM lieux LEFT JOIN villes ON lieux.id_ville = villes.id");
$places = $stmt->fetchAll(PDO::FETCH_ASSOC);

$unique_places = [];
foreach ($places as $place) {
    $key = strtolower(trim($place['nom'])) . '_' . $place['id_ville'];
    if (!isset($unique_places[$key])) {
        $unique_places[$key] = $place;
    }
}

$stmt = $pdo->query("SELECT id, nom FROM villes ORDER BY nom");
$cities = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration du lieu - VMaroc</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/header.css">
    <style>
        .admin-container {
            max-width: 900px;
            margin: 120px auto 50px;
            padding: 30px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .admin-header {
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .admin-title {
            font-size: 1.8rem;
            color: var(--primary-color);
            font-family: 'Playfair Display', serif;
        }
        .form-group {
            margin-bottom: 25px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-family: 'Poppins', sans-serif;
            font-size: 0.95rem;
            min-height: 120px;
        }
        .help-text {
            font-size: 0.85rem;
            color: #666;
            margin-top: 5px;
        }
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
        }
        .btn-primary:hover {
            background-color: #a38e5a;
        }
        .btn-secondary {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            margin-right: 10px;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include 'includes/header.php'; ?>
    
    <div class="admin-container">
        <div class="admin-header">
            <h1 class="admin-title">Administration du lieu</h1>
            <a href="place.php?id=<?= $placeId ?>" class="btn-secondary"><i class="fas fa-arrow-left"></i> Retour au lieu</a>
        </div>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($place): ?>
            <h2><?= htmlspecialchars($place['nom']) ?></h2>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="equipements">Équipements & Services</label>
                    <textarea id="equipements" name="equipements" rows="5"><?= htmlspecialchars($place['equipements'] ?? '') ?></textarea>
                    <p class="help-text">Séparez les équipements par des virgules. Exemple: WiFi gratuit, Climatisation, Parking, Piscine</p>
                </div>
                
                <div class="form-group">
                    <label for="boutiques_services">Boutiques et services à proximité</label>
                    <textarea id="boutiques_services" name="boutiques_services" rows="4"><?= htmlspecialchars($place['boutiques_services'] ?? '') ?></textarea>
                    <p class="help-text">Liste les boutiques et services à proximité, séparés par des virgules</p>
                </div>
                
                <div class="form-group">
                    <label for="url_activites">URL des activités</label>
                    <input type="url" id="url_activites" name="url_activites" class="form-control" 
                           value="<?= htmlspecialchars($place['url_activites'] ?? '') ?>" 
                           placeholder="https://exemple.com/reservation"
                           style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                    <p class="help-text">Lien vers la page de réservation ou d'activités du lieu</p>
                </div>
                
                <button type="submit" name="update_place" class="btn-primary">Mettre à jour</button>
            </form>
        <?php else: ?>
            <p>Veuillez sélectionner un lieu valide à modifier.</p>
        <?php endif; ?>
    </div>
    
    <script>
        // Script pour formater automatiquement les virgules
        document.addEventListener('DOMContentLoaded', function() {
            const textareas = document.querySelectorAll('textarea');
            
            textareas.forEach(textarea => {
                textarea.addEventListener('blur', function() {
                    // Remplacer les virgules multiples par une seule
                    let value = this.value.replace(/,\s*,+/g, ',');
                    // Ajouter un espace après chaque virgule si ce n'est pas déjà le cas
                    value = value.replace(/,([^\s])/g, ', $1');
                    // Supprimer les espaces en début et fin
                    value = value.trim();
                    // Supprimer la virgule à la fin si elle existe
                    value = value.replace(/,\s*$/, '');
                    
                    this.value = value;
                });
            });
        });
    </script>
</body>
</html>
