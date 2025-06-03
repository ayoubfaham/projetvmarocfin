<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: admin-login.php');
    exit();
}

require_once '../config/database.php';

// Traitement de l'ajout/modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $user_id = $_POST['user_id'];
            $lieu_id = $_POST['place_id'];
            $rating = intval($_POST['rating']);
            if ($rating < 1 || $rating > 5) {
                $error = "La note doit être comprise entre 1 et 5.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO avis (id_utilisateur, id_lieu, rating) VALUES (?, ?, ?)");
                if ($stmt->execute([$user_id, $lieu_id, $rating])) {
                    $success = "Avis ajouté avec succès";
                } else {
                    $error = "Erreur lors de l'ajout de l'avis";
                }
            }
        } elseif ($_POST['action'] === 'edit') {
            $id = $_POST['id'];
            $rating = intval($_POST['rating']);
            if ($rating < 1 || $rating > 5) {
                $error = "La note doit être comprise entre 1 et 5.";
            } else {
                $stmt = $pdo->prepare("UPDATE avis SET rating = ? WHERE id = ?");
                if ($stmt->execute([$rating, $id])) {
                    $success = "Avis modifié avec succès";
                } else {
                    $error = "Erreur lors de la modification de l'avis";
                }
            }
        } elseif ($_POST['action'] === 'delete') {
            $id = $_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM avis WHERE id = ?");
            if ($stmt->execute([$id])) {
                $success = "Avis supprimé avec succès";
            } else {
                $error = "Erreur lors de la suppression de l'avis";
            }
        }
    }
}

// Récupération des avis avec les informations des utilisateurs et des lieux
$stmt = $pdo->query("
    SELECT a.*, u.nom as username, l.nom as place_name 
    FROM avis a 
    JOIN utilisateurs u ON a.id_utilisateur = u.id 
    JOIN lieux l ON a.id_lieu = l.id 
    ORDER BY a.id DESC
");
$reviews = $stmt->fetchAll();

// Récupération des utilisateurs pour le formulaire
$users = $pdo->query("SELECT id, nom FROM utilisateurs")->fetchAll();

// Récupération des lieux pour le formulaire
$places = $pdo->query("SELECT id, nom FROM lieux")->fetchAll();

// Récupérer tous les avis
$stmt = $pdo->query("SELECT a.*, u.nom as utilisateur, l.nom as lieu FROM avis a
    JOIN utilisateurs u ON a.id_utilisateur = u.id
    JOIN lieux l ON a.id_lieu = l.id
    ORDER BY a.date_creation DESC");
$all_reviews = $stmt->fetchAll();

if (isset($_POST['delete_review_id'])) {
    $id = (int)$_POST['delete_review_id'];
    $stmt = $pdo->prepare("DELETE FROM avis WHERE id = ?");
    $stmt->execute([$id]);
    // Optionnel : message de succès
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Avis - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/main.css">
    <style>
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: var(--white);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow-md);
            min-width: 700px;
        }
        .admin-table th,
        .admin-table td {
            padding: 16px 12px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
            font-size: 1rem;
        }
        .admin-table th {
            background: var(--primary-color);
            color: var(--white);
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .admin-table tr:nth-child(even) {
            background: #f8f8f8;
        }
        .admin-table tr:hover {
            background: #f1f1f1;
        }
        .rating {
            color: #ffc107;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        .btn-edit {
            background: var(--accent-color);
            color: #fff;
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            font-size: 0.95rem;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.2s;
        }
        .btn-edit:hover {
            background: #b47b2a;
        }
        .btn-delete {
            background: #dc3545;
            color: #fff;
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            font-size: 0.95rem;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.2s;
        }
        .btn-delete:hover {
            background: #b52a37;
        }
        @media (max-width: 900px) {
            .admin-table, .admin-table thead, .admin-table tbody, .admin-table th, .admin-table td, .admin-table tr {
                display: block;
            }
            .admin-table thead tr {
                display: none;
            }
            .admin-table tr {
                margin-bottom: 18px;
                border-radius: 8px;
                box-shadow: var(--shadow-md);
                background: #fff;
            }
            .admin-table td {
                padding: 12px 16px;
                border: none;
                position: relative;
            }
            .admin-table td:before {
                content: attr(data-label);
                font-weight: 600;
                color: var(--primary-color);
                display: block;
                margin-bottom: 6px;
                text-transform: uppercase;
                font-size: 0.9em;
            }
        }
        .add-form {
            background: var(--white);
            padding: 25px;
            border-radius: 12px;
            box-shadow: var(--shadow-md);
            margin-bottom: 30px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--primary-color);
            font-weight: 500;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            transition: var(--transition);
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: var(--accent-color);
            outline: none;
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            background: var(--white);
            border-radius: 12px;
            box-shadow: var(--shadow-md);
        }
        .empty-state i {
            font-size: 3rem;
            color: var(--accent-color);
            margin-bottom: 20px;
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
                <a href="logout.php" class="btn-primary">Déconnexion</a>
            </div>
        </div>
    </header>

    <main style="margin-top:100px;">
    <div class="container">
            <div class="section-title">
                <h2>Gestion des Avis</h2>
            </div>

            <?php if (isset($success)): ?>
                <div class="alert alert-success">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <?php echo $error; ?>
                </div>
        <?php endif; ?>

            <!-- Formulaire d'ajout -->
            <div class="add-form">
                <h3>Ajouter un Avis</h3>
                <form action="" method="POST" class="form-grid">
                    <input type="hidden" name="action" value="add">
                    <div class="form-group">
                        <label for="user_id">Utilisateur</label>
                        <select id="user_id" name="user_id" required>
                            <option value="">Sélectionner un utilisateur</option>
                <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['nom']); ?></option>
                <?php endforeach; ?>
            </select>
                    </div>
                    <div class="form-group">
                        <label for="place_id">Lieu</label>
                        <select id="place_id" name="place_id" required>
                            <option value="">Sélectionner un lieu</option>
                            <?php foreach ($places as $place): ?>
                                <option value="<?php echo $place['id']; ?>"><?php echo htmlspecialchars($place['nom']); ?></option>
                <?php endforeach; ?>
            </select>
                    </div>
                    <div class="form-group">
                        <label for="rating">Note</label>
                        <select id="rating" name="rating" required>
                            <option value="">Sélectionner une note</option>
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?> étoile<?php echo $i > 1 ? 's' : ''; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group" style="display: flex; align-items: flex-end;">
                        <button type="submit" class="btn-solid">Ajouter</button>
                    </div>
        </form>
    </div>

            <!-- Liste des avis -->
            <?php if (empty($reviews)): ?>
                <div class="empty-state">
                    <i class="fas fa-star"></i>
                    <h3>Aucun avis trouvé</h3>
                    <p>Commencez par ajouter un avis en utilisant le formulaire ci-dessus.</p>
                </div>
            <?php else: ?>
                <div class="section-title">
                    <h3>Liste des avis</h3>
                </div>
                <div style="overflow-x:auto;">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Utilisateur</th>
                                <th>Lieu</th>
                                <th>Note</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reviews as $review): ?>
                                <tr>
                                    <td data-label="ID"><?php echo $review['id']; ?></td>
                                    <td data-label="Utilisateur"><?php echo htmlspecialchars($review['username']); ?></td>
                                    <td data-label="Lieu"><?php echo htmlspecialchars($review['place_name']); ?></td>
                                    <td data-label="Note" class="rating">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star<?php echo $i <= $review['rating'] ? '' : '-o'; ?>"></i>
                                        <?php endfor; ?>
                                    </td>
                                    <td data-label="Date"><?php echo htmlspecialchars($review['date_creation'] ?? $review['date_avis'] ?? ''); ?></td>
                                    <td data-label="Actions" class="action-buttons">
                                        <button class="btn-edit" onclick="editReview(<?php echo htmlspecialchars(json_encode($review)); ?>)"><i class="fas fa-edit"></i> Modifier</button>
                                        <form action="" method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $review['id']; ?>">
                                            <button type="submit" class="btn-delete" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet avis ?')">
                                                <i class="fas fa-trash"></i> Supprimer
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html> 