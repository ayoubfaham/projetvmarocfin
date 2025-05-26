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
    $stmt = $pdo->prepare('UPDATE avis SET rating = ? WHERE id = ?');
    $stmt->execute([$rating, $id]);
    $success_message = 'Note modifiée avec succès.';
}

// Récupération de tous les avis
$reviews = $pdo->query('
    SELECT r.*, u.nom as user_nom, l.nom as lieu_nom
    FROM avis r
    JOIN utilisateurs u ON r.id_utilisateur = u.id
    JOIN lieux l ON r.id_lieu = l.id
    ORDER BY r.date_creation DESC
')->fetchAll(PDO::FETCH_ASSOC);
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
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"> <?= $success_message ?> </div>
        <?php endif; ?>
        <table class="table table-bordered table-hover">
            <thead class="table-light">
                <tr>
                    <th>#</th>
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
                    <td><?= $review['id'] ?></td>
                    <td><?= htmlspecialchars($review['user_nom']) ?></td>
                    <td><?= htmlspecialchars($review['lieu_nom']) ?></td>
                    <td>
                        <form method="post" class="d-inline-flex align-items-center" style="gap:4px;">
                            <input type="hidden" name="review_id" value="<?= $review['id'] ?>">
                            <input type="hidden" name="edit_review" value="1">
                            <select name="rating" class="form-select form-select-sm" style="width:70px;display:inline-block;">
                                <?php for($i=1;$i<=5;$i++): ?>
                                    <option value="<?= $i ?>" <?= $i == $review['rating'] ? 'selected' : '' ?>><?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                            <button type="submit" class="btn btn-sm btn-outline-primary" title="Modifier la note"><i class="fa fa-check"></i></button>
                        </form>
                        <?php for($i=1;$i<=5;$i++): ?>
                            <i class="fa fa-star star" style="color:<?= $i <= $review['rating'] ? '#f1c40f' : '#ccc' ?>;"></i>
                        <?php endfor; ?>
                    </td>
                    <td><?= date('d/m/Y', strtotime($review['date_creation'])) ?></td>
                    <td class="actions">
                        <a href="?delete=<?= $review['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Supprimer cet avis ?');"><i class="fa fa-trash"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <a href="admin-panel.php" class="btn btn-secondary mt-3"><i class="fa fa-arrow-left"></i> Retour au panel admin</a>
    </div>
</body>
</html> 