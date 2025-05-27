<?php
session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: admin-login.php');
    exit();
}

$pdo = new PDO('mysql:host=localhost;dbname=vmaroc;charset=utf8', 'root', '');

// Feedback messages
$success = $error = '';

// Initialisation des variables pour l'édition
$editMode = false;
$editCity = [
    'id' => '',
    'nom' => '',
    'photo' => '',
    'description' => ''
];

// Ajout d'une ville
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['edit_id']) && $_POST['edit_id'] !== '') {
        // Modification
        $id = intval($_POST['edit_id']);
        $nom = trim($_POST['nom']);
        $photo = trim($_POST['photo']);
        $desc = trim($_POST['description']);
        if ($nom && $desc) {
            $stmt = $pdo->prepare("UPDATE villes SET nom = ?, photo = ?, description = ? WHERE id = ?");
            $stmt->execute([$nom, $photo, $desc, $id]);
            $success = "Ville modifiée avec succès !";
        } else {
            $error = "Veuillez remplir tous les champs obligatoires.";
            $editMode = true;
            $editCity = ['id' => $id, 'nom' => $nom, 'photo' => $photo, 'description' => $desc];
        }
    } elseif (isset($_POST['nom'])) {
        // Ajout
        $nom = trim($_POST['nom']);
        $photo = trim($_POST['photo']);
        $desc = trim($_POST['description']);
        $categories = isset($_POST['categories']) ? $_POST['categories'] : [];
        
        if ($nom && $desc) {
            try {
                $pdo->beginTransaction();
                
                // Insérer la ville
                $stmt = $pdo->prepare("INSERT INTO villes (nom, photo, description) VALUES (?, ?, ?)");
                $stmt->execute([$nom, $photo, $desc]);
                $ville_id = $pdo->lastInsertId();
                
                // Ajouter des recommandations par défaut pour chaque catégorie sélectionnée
                if (!empty($categories)) {
                    $recommandationsDefaut = [
                        'culture' => [
                            'titre' => 'Visite culturelle de ' . $nom,
                            'description' => 'Découvrez les sites culturels et historiques de ' . $nom,
                            'prix_min' => 500,
                            'prix_max' => 1200,
                            'duree_min' => 1,
                            'duree_max' => 2,
                            'image_url' => $photo
                        ],
                        'nature' => [
                            'titre' => 'Exploration nature à ' . $nom,
                            'description' => 'Randonnée et découverte des paysages naturels de ' . $nom,
                            'prix_min' => 600,
                            'prix_max' => 1500,
                            'duree_min' => 1,
                            'duree_max' => 2,
                            'image_url' => $photo
                        ],
                        'gastronomie' => [
                            'titre' => 'Découverte gastronomique de ' . $nom,
                            'description' => 'Dégustez les spécialités culinaires locales de ' . $nom,
                            'prix_min' => 700,
                            'prix_max' => 1400,
                            'duree_min' => 1,
                            'duree_max' => 1,
                            'image_url' => $photo
                        ],
                        'shopping' => [
                            'titre' => 'Shopping à ' . $nom,
                            'description' => 'Découvrez les marchés et boutiques d\'artisanat de ' . $nom,
                            'prix_min' => 400,
                            'prix_max' => 1000,
                            'duree_min' => 1,
                            'duree_max' => 1,
                            'image_url' => $photo
                        ],
                        'plage' => [
                            'titre' => 'Détente et relaxation à ' . $nom,
                            'description' => 'Profitez des espaces de détente et de bien-être de ' . $nom,
                            'prix_min' => 800,
                            'prix_max' => 1800,
                            'duree_min' => 1,
                            'duree_max' => 1,
                            'image_url' => $photo
                        ],
                        'famille' => [
                            'titre' => 'Activités familiales à ' . $nom,
                            'description' => 'Découvrez les activités adaptées aux familles à ' . $nom,
                            'prix_min' => 600,
                            'prix_max' => 1500,
                            'duree_min' => 1,
                            'duree_max' => 2,
                            'image_url' => $photo
                        ]
                    ];
                    
                    $stmt = $pdo->prepare("INSERT INTO recommandations (ville_id, titre, description, categorie, prix_min, prix_max, duree_min, duree_max, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    
                    foreach ($categories as $categorie) {
                        if (isset($recommandationsDefaut[$categorie])) {
                            $rec = $recommandationsDefaut[$categorie];
                            $stmt->execute([
                                $ville_id,
                                $rec['titre'],
                                $rec['description'],
                                $categorie,
                                $rec['prix_min'],
                                $rec['prix_max'],
                                $rec['duree_min'],
                                $rec['duree_max'],
                                $rec['image_url']
                            ]);
                        }
                    }
                }
                
                $pdo->commit();
                $success = "Ville ajoutée avec succès avec " . count($categories) . " recommandations !";
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Erreur lors de l'ajout : " . $e->getMessage();
            }
        } else {
            $error = "Veuillez remplir tous les champs obligatoires.";
        }
    }
}

// Suppression d'une ville
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $pdo->prepare("DELETE FROM villes WHERE id = ?")->execute([$id]);
    $success = "Ville supprimée avec succès !";
}

// Préparation de l'édition
if (isset($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM villes WHERE id = ?");
    $stmt->execute([$editId]);
    $editCity = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($editCity) {
        $editMode = true;
    }
}

// Liste des villes
$cities = $pdo->query("SELECT * FROM villes")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Admin - Gestion des villes</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/main.css">
    <style>
        body, .admin-table, .admin-table td, .admin-table th, .form-group, .form-group input, .form-group select, .form-group textarea {
            font-size: 0.91rem;
        }
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            background: var(--white);
            border-radius: 8px;
            box-shadow: var(--shadow-md);
            margin-bottom: 2rem;
        }
        .admin-table th, .admin-table td {
            padding: 14px 10px;
            text-align: left;
        }
        .admin-table th {
            background: var(--light-color);
            color: var(--primary-color);
            font-weight: 600;
        }
        .admin-table tr:not(:last-child) {
            border-bottom: 1px solid var(--border-color);
        }
        .admin-table img {
            height: 40px;
            border-radius: 4px;
        }
        .admin-actions {
            display: flex;
            gap: 8px;
        }
        .alert-success {
            background: #e6f9ed;
            color: #1a7f37;
            border: 1px solid #b7ebc6;
            padding: 12px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
            text-align: center;
        }
        .alert-error {
            background: #fff0f0;
            color: #c0392b;
            border: 1px solid #f5c6cb;
            padding: 12px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
            text-align: center;
        }
        .admin-form-flex {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            align-items: flex-end;
        }
        .admin-form-flex .form-group {
            flex: 1 1 180px;
            margin-bottom: 0;
        }
        @media (max-width: 900px) {
            .admin-form-flex {
                flex-direction: column;
                align-items: stretch;
            }
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
                <a href="admin-panel.php" class="btn-outline">Panel Admin</a>
                <a href="logout.php" class="btn-outline">Déconnexion</a>
            </div>
        </div>
    </header>

    <main style="margin-top:100px;">
    <div class="container">
            <div class="section-title" style="display:flex;align-items:center;justify-content:space-between;gap:20px;flex-wrap:wrap;">
                <h2>Gestion des villes <span id="cityCount" style="font-size:1rem;font-weight:400;color:var(--secondary-color);">(<?= count($cities) ?>)</span></h2>
                <input type="text" id="searchCityInput" placeholder="Rechercher une ville..." style="padding:10px 16px;border:1px solid var(--border-color);border-radius:6px;min-width:220px;">
            </div>
            <section class="section">
                <?php if ($success): ?><div class="alert-success"><?= $success ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert-error"><?= $error ?></div><?php endif; ?>
                <div class="form" style="max-width:900px;margin:0 auto 40px auto;">
                    <h3 style="text-align:center;">
                        <?= $editMode ? 'Modifier la ville' : 'Ajouter une ville' ?>
                    </h3>
                    <form method="post" class="admin-form-flex">
                        <?php if ($editMode): ?>
                            <input type="hidden" name="edit_id" value="<?= htmlspecialchars($editCity['id']) ?>">
                        <?php endif; ?>
                        <div class="form-group">
                            <input type="text" name="nom" class="form-control" placeholder="Nom de la ville *" required value="<?= htmlspecialchars($editCity['nom']) ?>">
                        </div>
                        <div class="form-group">
                            <input type="text" name="photo" class="form-control" placeholder="URL ou nom du fichier photo (ex: marrakech.jpg)" value="<?= htmlspecialchars($editCity['photo']) ?>">
                        </div>
                        <div class="form-group">
                            <input type="text" name="description" class="form-control" placeholder="Description *" required value="<?= htmlspecialchars($editCity['description']) ?>">
                        </div>
                        <div style="display:flex;gap:8px;align-items:center;">
                            <button type="submit" class="btn-solid" style="min-width:120px;">
                                <?= $editMode ? 'Enregistrer' : 'Ajouter' ?>
                            </button>
                            <?php if ($editMode): ?>
                                <a href="admin-cities.php" class="btn-outline" style="min-width:100px;">Annuler</a>
                            <?php endif; ?>
                        </div>
        </form>
                </div>

                <div class="section-title">
                    <h3>Liste des villes</h3>
                </div>
                <div style="overflow-x:auto;">
                    <table class="admin-table" id="citiesTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nom</th>
                                <th>Photo</th>
                                <th>Description</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($cities)): ?>
                                <tr><td colspan="5" style="text-align:center;color:var(--secondary-color);">Aucune ville enregistrée.</td></tr>
                            <?php endif; ?>
            <?php foreach ($cities as $city): ?>
                <tr>
                    <td><?= $city['id'] ?></td>
                    <td><?= htmlspecialchars($city['nom']) ?></td>
                                    <td>
                                        <?php if ($city['photo']): ?>
                                            <img src="<?= htmlspecialchars($city['photo']) ?>" alt="<?= htmlspecialchars($city['nom']) ?>">
                                        <?php else: ?>
                                            <span style="color:var(--secondary-color);font-size:0.9em;">Aucune</span>
                                        <?php endif; ?>
                                    </td>
                    <td><?= htmlspecialchars($city['description']) ?></td>
                    <td>
                                        <div class="admin-actions">
                                            <a href="?edit=<?= $city['id'] ?>" class="btn-outline" style="padding:4px 12px;font-size:0.9rem;">Modifier</a>
                                            <a href="?delete=<?= $city['id'] ?>" class="btn-outline" style="padding:4px 12px;font-size:0.9rem;" onclick="return confirm('Supprimer cette ville ?')">Supprimer</a>
                                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
                        </tbody>
        </table>
    </div>
            </section>
        </div>
    </main>
</body>
</html> 