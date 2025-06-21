<?php
session_start();
require_once 'config/database.php';

// Tableau des centres d'intu00e9ru00eat
$interests = [
    'culture' => 'Culture et Histoire',
    'nature' => 'Nature et Randonnu00e9es',
    'gastronomie' => 'Gastronomie',
    'shopping' => 'Shopping',
    'plage' => 'Plage et Du00e9tente',
    'famille' => 'Famille'
];

// Ru00e9cupu00e9rer la liste des villes pour le formulaire
try {
    $stmt = $pdo->query("SELECT id, nom FROM villes ORDER BY nom");
    $cities = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $cities = [];
    error_log('Erreur SQL: ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recommandations - VMaroc</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/montserrat-font.css">
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/header.css">
    <style>
        body {
            font-family: 'Montserrat', Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2e4057;
            text-align: center;
        }
        .form-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        select, input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button {
            background-color: #b48a3c;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }
        button:hover {
            background-color: #9b7633;
        }
        .nav-menu {
            display: flex;
            list-style: none;
            margin: 0 0 20px 0;
            padding: 0;
            justify-content: center;
        }
        .nav-menu li {
            margin: 0 10px;
        }
        .nav-menu li a {
            text-decoration: none;
            color: #333;
            font-weight: bold;
        }
        .nav-menu li a:hover {
            color: #b48a3c;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include 'includes/header.php'; ?>
    
    <div class="container" style="margin-top: 100px;">
        <h1>Recommandations de Voyage</h1>
        
        <div class="form-container">
            <form method="POST" action="">
                <div class="form-group">
                    <label for="city">Ville</label>
                    <select name="city" id="city">
                        <option value="">Su00e9lectionnez une ville</option>
                        <?php foreach ($cities as $city): ?>
                            <option value="<?= htmlspecialchars($city['nom']) ?>"><?= htmlspecialchars($city['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="interest">Centre d'intu00e9ru00eat</label>
                    <select name="interest" id="interest">
                        <option value="">Su00e9lectionnez un centre d'intu00e9ru00eat</option>
                        <?php foreach ($interests as $key => $value): ?>
                            <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($value) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button type="submit">Obtenir des recommandations</button>
            </form>
        </div>
        
        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['city']) && !empty($_POST['city'])): ?>
            <div style="margin-top: 30px; padding: 20px; background-color: #f9f9f9; border-radius: 5px;">
                <h2>Vos recommandations pour <?= htmlspecialchars($_POST['city']) ?></h2>
                <p>Les recommandations personnalisu00e9es s'afficheront ici.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
