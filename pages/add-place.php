<?php
session_start();
require_once('../includes/config.php');

// Récupération des villes pour le select
try {
    $stmt = $pdo->query("SELECT id, nom FROM villes ORDER BY nom");
    $villes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Erreur lors de la récupération des villes : " . $e->getMessage();
}

// Récupération des catégories depuis la base de données
try {
    $stmt = $pdo->query("SELECT DISTINCT categorie FROM lieux ORDER BY categorie");
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Si aucune catégorie n'existe encore dans la base, utiliser une liste par défaut
    if (empty($categories)) {
        $categories = [
            'hotels', 'restaurants', 'monuments', 'plages', 
            'parcs', 'shopping', 'cinemas', 'musees', 
            'sport', 'culture', 'vie_nocturne'
        ];
    }
} catch (PDOException $e) {
    echo "Erreur lors de la récupération des catégories : " . $e->getMessage();
    // Utiliser la liste par défaut en cas d'erreur
    $categories = [
        'hotels', 'restaurants', 'monuments', 'plages', 
        'parcs', 'shopping', 'cinemas', 'musees', 
        'sport', 'culture', 'vie_nocturne'
    ];
}

// Traitement du formulaire d'ajout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validation des données
        $nom = trim($_POST['nom'] ?? '');
        $ville_id = $_POST['id_ville'] ?? '';
        $categorie = $_POST['categorie'] ?? '';
        $url = trim($_POST['url'] ?? '');
        $adresse = trim($_POST['adresse'] ?? '');
        $budget = trim($_POST['budget'] ?? '');
        $latitude = trim($_POST['latitude'] ?? '');
        $longitude = trim($_POST['longitude'] ?? '');
        $description = trim($_POST['description'] ?? '');

        // Validation basique
        $errors = [];
        if (empty($nom)) $errors[] = "Le nom est requis";
        if (empty($ville_id)) $errors[] = "La ville est requise";
        if (empty($categorie)) $errors[] = "La catégorie est requise";
        if (empty($adresse)) $errors[] = "L'adresse est requise";
        if (!empty($latitude) && !is_numeric($latitude)) $errors[] = "La latitude doit être un nombre";
        if (!empty($longitude) && !is_numeric($longitude)) $errors[] = "La longitude doit être un nombre";

        // Si pas d'erreurs, on insère dans la base
        if (empty($errors)) {
            $stmt = $pdo->prepare("
                INSERT INTO lieux (nom, id_ville, categorie, url, adresse, budget, latitude, longitude, description) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $nom, $ville_id, $categorie, $url, $adresse, 
                $budget, $latitude, $longitude, $description
            ]);

            $_SESSION['success_message'] = "Le lieu a été ajouté avec succès !";
            header("Location: admin-places.php");
            exit;
        }
    } catch (PDOException $e) {
        $errors[] = "Erreur lors de l'ajout du lieu : " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Ajouter un lieu</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f6fa;
            padding: 20px;
        }

        .add-place-section {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 30px;
            max-width: 1200px;
            margin: 20px auto;
        }

        .section-title {
            color: #2D796D;
            font-size: 24px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 15px;
            border-bottom: 2px solid #E6F0EE;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2D3748;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #CBD5E0;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.3s ease;
            background-color: #fff;
        }

        .form-control:focus {
            outline: none;
            border-color: #2D796D;
            box-shadow: 0 0 0 3px rgba(45, 121, 109, 0.1);
        }

        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%232D796D' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14L2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 35px;
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        .btn-submit {
            background: #2D796D;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-submit:hover {
            background: #246355;
            transform: translateY(-1px);
        }

        .error-message {
            color: #e53e3e;
            background: #fff5f5;
            border: 1px solid #fc8181;
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 20px;
        }

        .success-message {
            color: #2f855a;
            background: #f0fff4;
            border: 1px solid #68d391;
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 20px;
        }

        .form-footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #E6F0EE;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn-cancel {
            color: #2D3748;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .btn-cancel:hover {
            background: #EDF2F7;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-footer {
                flex-direction: column-reverse;
                gap: 15px;
            }

            .btn-submit, .btn-cancel {
                width: 100%;
                text-align: center;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="add-place-section">
        <h2 class="section-title">
            <i class="fas fa-plus-circle"></i>
            Ajouter un nouveau lieu
        </h2>

        <?php if (!empty($errors)): ?>
            <div class="error-message">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="success-message">
                <?php 
                echo htmlspecialchars($_SESSION['success_message']);
                unset($_SESSION['success_message']);
                ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-grid">
                <div class="form-group">
                    <label for="nom">Nom du lieu</label>
                    <input type="text" id="nom" name="nom" class="form-control" required
                           value="<?php echo htmlspecialchars($_POST['nom'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="ville">Ville</label>
                    <select id="ville" name="id_ville" class="form-control" required>
                        <option value="">Sélectionner une ville</option>
                        <?php foreach ($villes as $ville): ?>
                            <option value="<?php echo $ville['id']; ?>"
                                    <?php echo (isset($_POST['id_ville']) && $_POST['id_ville'] == $ville['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($ville['nom']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="categorie">Catégorie</label>
                    <select id="categorie" name="categorie" class="form-control" required>
                        <option value="">Sélectionner une catégorie</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat; ?>"
                                    <?php echo (isset($_POST['categorie']) && $_POST['categorie'] == $cat) ? 'selected' : ''; ?>>
                                <?php echo ucfirst(str_replace('_', ' ', $cat)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="url">URL Activité/Site Web</label>
                    <input type="url" id="url" name="url" class="form-control"
                           value="<?php echo htmlspecialchars($_POST['url'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="adresse">Adresse</label>
                    <input type="text" id="adresse" name="adresse" class="form-control" required
                           value="<?php echo htmlspecialchars($_POST['adresse'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="budget">Budget</label>
                    <input type="text" id="budget" name="budget" class="form-control"
                           value="<?php echo htmlspecialchars($_POST['budget'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="latitude">Latitude</label>
                    <input type="number" step="any" id="latitude" name="latitude" class="form-control"
                           value="<?php echo htmlspecialchars($_POST['latitude'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="longitude">Longitude</label>
                    <input type="number" step="any" id="longitude" name="longitude" class="form-control"
                           value="<?php echo htmlspecialchars($_POST['longitude'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" class="form-control"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
            </div>

            <div class="form-footer">
                <a href="admin-places.php" class="btn-cancel">
                    <i class="fas fa-arrow-left"></i>
                    Retour
                </a>
                <button type="submit" class="btn-submit">
                    <i class="fas fa-plus"></i>
                    Ajouter le lieu
                </button>
            </div>
        </form>
    </div>
</body>
</html> 