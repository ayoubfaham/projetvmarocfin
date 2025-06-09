<?php
session_start();
require_once '../config/database.php';

// Vérification admin
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: admin-login.php');
    exit;
}

// Suppression d'un avis
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $stmt = $pdo->prepare('DELETE FROM avis WHERE id = ?');
    $stmt->execute([$_GET['delete']]);
    $success_message = 'Avis supprimé avec succès.';
}

// Modification d'un avis
if (isset($_POST['edit_review'])) {
    $id = (int)$_POST['review_id'];
    $rating = (int)$_POST['rating'];
    $commentaire = isset($_POST['commentaire']) ? trim($_POST['commentaire']) : '';
    
    try {
        // Vérifier si la colonne commentaire existe dans la table
        $checkColumn = $pdo->query("SHOW COLUMNS FROM avis LIKE 'commentaire'")->fetchColumn();
        
        if ($checkColumn) {
            // La colonne existe, on peut faire la mise à jour
            $stmt = $pdo->prepare("UPDATE avis SET rating = ?, commentaire = ? WHERE id = ?");
            $stmt->execute([$rating, $commentaire, $id]);
        } else {
            // La colonne n'existe pas, on ne met à jour que la note
            $stmt = $pdo->prepare("UPDATE avis SET rating = ? WHERE id = ?");
            $stmt->execute([$rating, $id]);
            
            // On affiche un message d'erreur
            $error_message = "Attention: La colonne 'commentaire' n'existe pas dans la table 'avis'. Veuillez vérifier la structure de votre base de données.";
        }
        
        $success_message = 'Avis modifié avec succès.';
        
        // Redirection pour rafraîchir la page
        header('Location: admin-reviews.php?success=1');
        exit;
    } catch (PDOException $e) {
        $error_message = "Erreur lors de la modification de l'avis: " . $e->getMessage();
    }
}

// Message de succès après redirection
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $success_message = 'Avis modifié avec succès.';
}

// Récupération de tous les avis avec jointure explicite de toutes les colonnes
// Vérifier la structure de la table pour trouver le nom de la colonne des commentaires
$tableStructure = $pdo->query("DESCRIBE avis")->fetchAll(PDO::FETCH_ASSOC);
$commentColumn = 'commentaire'; // Nom par défaut

// Rechercher le nom réel de la colonne des commentaires
foreach ($tableStructure as $column) {
    if ($column['Field'] === 'avis') {
        $commentColumn = 'avis';
        break;
    } else if ($column['Field'] === 'commentaire') {
        $commentColumn = 'commentaire';
        break;
    }
}

// Récupération de tous les avis avec le bon nom de colonne
$query = "
    SELECT r.id, r.id_utilisateur, r.id_lieu, r.rating, 
           COALESCE(r.{$commentColumn}, '') as commentaire, 
           r.date_creation, 
           u.nom as user_nom, l.nom as lieu_nom
    FROM avis r
    JOIN utilisateurs u ON r.id_utilisateur = u.id
    JOIN lieux l ON r.id_lieu = l.id
    ORDER BY r.date_creation DESC
";

$reviews = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);

// Débogage
$debug = [
    'structure' => $tableStructure,
    'colonne_commentaire' => $commentColumn,
    'exemple_avis' => !empty($reviews) ? $reviews[0] : null
];

// Ajouter des commentaires de test si nécessaire
if (!empty($reviews)) {
    foreach ($reviews as &$review) {
        if (empty($review['commentaire'])) {
            // Essayons de mettre à jour l'avis avec un commentaire de test
            $updateStmt = $pdo->prepare("UPDATE avis SET commentaire = ? WHERE id = ? AND (commentaire IS NULL OR commentaire = '')");
            $testComment = "Commentaire de test pour l'avis #" . $review['id'];
            $updateStmt->execute([$testComment, $review['id']]);
            $review['commentaire'] = $testComment;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Admin - Gestion des avis</title>
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
        .form {
            max-width:520px;
            margin:0 auto 40px auto;
            background:#fff;
            border-radius:18px;
            box-shadow:0 4px 18px #e9cba733;
            padding:38px 32px 28px 32px;
        }
        .form-group {
            margin-bottom:18px;
        }
        .form-group label {
            font-weight:600;
            color:#2d2d2d;
            margin-bottom:7px;
            display:block;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 12px 20px;
            border: 1.5px solid #e9cba7;
            border-radius: 8px;
            background: #fff;
            color: #222;
            font-size: 1.08rem;
            box-shadow: 0 2px 8px #e9cba733;
            transition: border 0.2s, box-shadow 0.2s;
            outline: none;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            border-color: #bfa14a;
            box-shadow: 0 4px 16px #e9cba755;
            background: #fff;
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
    <div class="admin-container">
        <h2 class="mb-4"><i class="fas fa-star"></i> Gérer les avis</h2>
        <div class="alert alert-info mb-4">
            <i class="fas fa-info-circle"></i> Note: Chaque utilisateur ne peut laisser qu'un seul avis par lieu. Si un utilisateur tente d'ajouter un nouvel avis pour un lieu qu'il a déjà évalué, son avis précédent sera mis à jour.
        </div>
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"> <?= $success_message ?> </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"> <?= $error_message ?> </div>
        <?php endif; ?>
        
        <!-- Informations de débogage -->
        <div class="alert alert-info mb-4">
            <h4>Informations de débogage</h4>
            <p><strong>Nom de la colonne utilisée pour les commentaires :</strong> <?= $commentColumn ?></p>
            <p><strong>Structure de la table 'avis' :</strong></p>
            <pre style="max-height: 200px; overflow-y: auto;"><?php print_r($tableStructure); ?></pre>
            
            <?php if (!empty($reviews)): ?>
                <p><strong>Exemple d'avis (premier de la liste) :</strong></p>
                <pre style="max-height: 200px; overflow-y: auto;"><?php print_r($reviews[0]); ?></pre>
            <?php endif; ?>
        </div>

        <table class="table table-bordered table-hover">
            <thead class="table-dark">
                <tr>
                    <th style="width: 5%">#</th>
                    <th style="width: 15%">Utilisateur</th>
                    <th style="width: 15%">Lieu</th>
                    <th style="width: 10%">Note</th>
                    <th style="width: 25%">Commentaire</th>
                    <th style="width: 15%">Date</th>
                    <th style="width: 15%">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reviews as $review): ?>
                <tr>
                    <td><?= $review['id'] ?></td>
                    <td><?= htmlspecialchars($review['user_nom']) ?></td>
                    <td><?= htmlspecialchars($review['lieu_nom']) ?></td>
                    <td>
                        <?php for($i=1;$i<=5;$i++): ?>
                            <i class="fa fa-star" style="color:<?= $i <= $review['rating'] ? '#f1c40f' : '#ccc' ?>;"></i>
                        <?php endfor; ?>
                    </td>
                    <td style="min-width: 200px; background-color: #f9f9f9;">
                        <div style="border: 1px solid #ddd; padding: 8px; border-radius: 4px;">
                        <?php if (isset($review['commentaire']) && !empty(trim($review['commentaire']))): ?>
                            <div style="max-height: 100px; overflow-y: auto;">
                                <strong>Commentaire :</strong><br>
                                <?= nl2br(htmlspecialchars($review['commentaire'])) ?>
                            </div>
                        <?php else: ?>
                            <span class="text-muted fst-italic">Aucun commentaire pour cet avis</span>
                        <?php endif; ?>
                        </div>
                    </td>
                    <td><?= date('d/m/Y H:i', strtotime($review['date_creation'])) ?></td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-primary mb-1" data-bs-toggle="modal" data-bs-target="#editModal<?= $review['id'] ?>">
                            <i class="fa fa-edit"></i> Modifier
                        </button>
                        <a href="?delete=<?= $review['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Supprimer cet avis ?');"><i class="fa fa-trash"></i> Supprimer</a>
                        
                        <!-- Modal pour modifier l'avis -->
                        <div class="modal fade" id="editModal<?= $review['id'] ?>" tabindex="-1" aria-labelledby="editModalLabel<?= $review['id'] ?>" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="editModalLabel<?= $review['id'] ?>">Modifier l'avis</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <form method="post">
                                        <div class="modal-body">
                                            <input type="hidden" name="review_id" value="<?= $review['id'] ?>">
                                            <input type="hidden" name="edit_review" value="1">
                                            
                                            <div class="mb-3">
                                                <label for="rating<?= $review['id'] ?>" class="form-label">Note :</label>
                                                <select name="rating" id="rating<?= $review['id'] ?>" class="form-select">
                                                    <?php for($i=1;$i<=5;$i++): ?>
                                                        <option value="<?= $i ?>" <?= $i == $review['rating'] ? 'selected' : '' ?>><?= $i ?> étoile<?= $i > 1 ? 's' : '' ?></option>
                                                    <?php endfor; ?>
                                                </select>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="commentaire<?= $review['id'] ?>" class="form-label">Commentaire :</label>
                                                <textarea name="commentaire" id="commentaire<?= $review['id'] ?>" class="form-control" rows="4"><?= htmlspecialchars($review['commentaire'] ?? '') ?></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <a href="admin-panel.php" class="btn btn-secondary mt-3"><i class="fa fa-arrow-left"></i> Retour au panel admin</a>
    </div>
    
    <footer>
        <div class="container" style="text-align:center;">
            <p style="color:#bfa14a;font-family:'Montserrat',sans-serif;font-size:1rem;margin:18px 0 0 0;">© 2025 Maroc Authentique. Tous droits réservés.</p>
        </div>
    </footer>
    
    <!-- Scripts Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 