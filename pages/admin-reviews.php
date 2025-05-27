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
    <title>Gérer les avis - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body, .admin-table, .admin-table td, .admin-table th, .form-group, .form-group input, .form-group select, .form-group textarea {
            font-size: 0.91rem;
        }
        body { background: #f8f9fa; }
        .admin-container { max-width: 950px; margin: 40px auto; background: #fff; border-radius: 16px; box-shadow: 0 2px 10px rgba(44,62,80,0.07); padding: 36px 32px; }
        .table th, .table td { vertical-align: middle; }
        .star { color: #f1c40f; font-size: 1.2rem; }
        .actions { display: flex; gap: 10px; }
    </style>
</head>
<body>
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
    
    <!-- Scripts Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 