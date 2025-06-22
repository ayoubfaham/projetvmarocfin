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
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Montserrat', 'Poppins', Arial, sans-serif;
            background: #f6f7fb;
            color: #222;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 32px 16px 64px 16px;
        }
        .section-title h2, .section-title h3 {
            font-family: 'Montserrat', 'Poppins', Arial, sans-serif;
            font-weight: 800;
            color: #2d2d2d;
            letter-spacing: 1px;
        }
        .admin-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 20px;
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(44,44,44,0.06);
            border: 1.5px solid #e9cba7;
        }
        .admin-table thead th {
            background: #fff;
            color: #bfa14a;
            font-family: 'Montserrat', 'Poppins', Arial, sans-serif;
            font-weight: 700;
            font-size: 1.08rem;
            border-bottom: 2px solid #e9cba7;
            padding: 18px 14px;
            letter-spacing: 0.5px;
            text-align: left;
        }
        .admin-table th, .admin-table td {
            padding: 18px 14px;
            text-align: left;
            border-bottom: 1px solid #f3e9d1;
            font-size: 1.01rem;
        }
        .admin-table tr:nth-child(even) {
            background: #fafbfc;
        }
        .admin-table tr:hover {
            background: #f9f6f2;
        }
        .admin-table td {
            vertical-align: top;
        }
        .admin-table img {
            border-radius: 6px;
            box-shadow: 0 2px 8px #e9cba733;
        }
        .admin-actions {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .btn-solid, .btn-outline {
            font-family: 'Montserrat', 'Poppins', Arial, sans-serif;
            font-weight: 600;
            border-radius: 8px;
            padding: 10px 28px;
            font-size: 1.08rem;
            border: 1.5px solid #e9cba7;
            box-shadow: 0 2px 8px #e9cba733;
            transition: background 0.2s, color 0.2s, border 0.2s;
            cursor: pointer;
        }
        .btn-solid {
            background: #e9cba7;
            color: #222;
        }
        .btn-solid:hover {
            background: #bfa14a;
            color: #fff;
        }
        .btn-outline {
            background: #fff;
            color: #bfa14a;
        }
        .btn-outline:hover {
            background: #bfa14a;
            color: #fff;
        }
        .btn-delete {
            background: #dc3545;
            color: #fff;
            border: none;
            padding: 9px 22px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1rem;
            transition: background 0.2s;
        }
        .btn-delete:hover {
            background: #b52a37;
        }
        .form {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 4px 18px #e9cba733;
            padding: 32px 28px 24px 28px;
            margin-bottom: 40px;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }
        .form-group {
            margin-bottom: 22px;
        }
        .form-group label {
            font-weight: 500;
            color: #2d2d2d;
            margin-bottom: 7px;
            display: block;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 13px 16px;
            border: 1.5px solid #bfa14a;
            border-radius: 8px;
            background: #faf9f7;
            color: #222;
            font-size: 1.05rem;
            box-shadow: 0 2px 8px #e9cba733;
            transition: border 0.2s, box-shadow 0.2s;
            outline: none;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            border-color: #bfa14a;
            box-shadow: 0 4px 16px #e9cba755;
            background: #fff;
        }
        footer {
            background: #fff;
            color: #bfa14a;
            text-align: center;
            padding: 18px 0 0 0;
            font-size: 1rem;
            border-top: 1.5px solid #e9cba7;
            margin-top: 48px;
        }
        @media (max-width: 900px) {
            .container { max-width: 98vw; padding: 12px 2vw 32px 2vw; }
            .admin-table th, .admin-table td { font-size: 0.97rem; padding: 12px 7px; }
        }
        @media (max-width: 600px) {
            .admin-table, .admin-table thead, .admin-table tbody, .admin-table th, .admin-table td, .admin-table tr {
                display: block;
            }
            .admin-table thead tr { display: none; }
            .admin-table tr {
                margin-bottom: 18px;
                border-radius: 8px;
                box-shadow: 0 2px 8px #e9cba733;
                background: #fff;
            }
            .admin-table td {
                padding: 12px 8px;
                border: none;
                position: relative;
            }
            .admin-table td:before {
                content: attr(data-label);
                font-weight: 600;
                color: #bfa14a;
                display: block;
                margin-bottom: 6px;
                text-transform: uppercase;
                font-size: 0.9em;
            }
        }
    </style>
</head>
<body>
    <header style="background:#fff; box-shadow:0 2px 12px #e9cba733; padding:0;">
        <div class="container header-container" style="display:flex;align-items:center;justify-content:space-between;min-height:80px;width:100%;">
            <a href="../index.php" class="logo" style="flex:0 0 auto;display:flex;align-items:center;gap:12px;text-decoration:none;">
                <img src="https://i.postimg.cc/g07GgLp5/VMaroc-logo-trf.png" alt="Maroc Authentique" class="logo-img" style="height:54px;">
            </a>
            <ul class="nav-menu" style="flex:1 1 0;display:flex;justify-content:center;gap:36px;list-style:none;margin:0;padding:0;">
                <li><a href="../index.php" style="color:#222;font-family:'Montserrat',sans-serif;font-weight:600;font-size:1.08rem;text-decoration:none;transition:color .2s;">Accueil</a></li>
                <li><a href="../destinations.php" style="color:#222;font-family:'Montserrat',sans-serif;font-weight:600;font-size:1.08rem;text-decoration:none;transition:color .2s;">Destinations</a></li>
                <li><a href="../recommandations.php" style="color:#222;font-family:'Montserrat',sans-serif;font-weight:600;font-size:1.08rem;text-decoration:none;transition:color .2s;">Recommandations</a></li>
            </ul>
            <div class="auth-buttons" style="flex:0 0 auto;display:flex;align-items:center;gap:12px;">
                <a href="admin-panel.php" class="btn-outline">Panel Admin</a>
                <a href="../logout.php" class="btn-solid" style="background:#dc3545;color:#fff;border:none;">Déconnexion</a>
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
    <footer>
        <div class="container" style="text-align:center;">
            <p style="color:#bfa14a;font-family:'Montserrat',sans-serif;font-size:1rem;margin:18px 0 0 0;">© 2025 Maroc Authentique. Tous droits réservés.</p>
        </div>
    </footer>
</body>
</html> 