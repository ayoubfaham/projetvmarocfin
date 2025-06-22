<?php
session_start();
require_once '../config/database.php';
require_once('../includes/admin-header.php');

// Vérification admin
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: admin-login.php');
    exit;
}

// Récupérer les utilisateurs pour le formulaire d'ajout d'avis
$users = $pdo->query('SELECT id, nom FROM utilisateurs ORDER BY nom')->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les lieux pour le formulaire d'ajout d'avis
$places = $pdo->query('SELECT id, nom FROM lieux ORDER BY nom')->fetchAll(PDO::FETCH_ASSOC);

// Suppression d'un avis
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    // Récupérer l'id_lieu avant suppression
    $stmt = $pdo->prepare('SELECT id_lieu FROM avis WHERE id = ?');
    $stmt->execute([$_GET['delete']]);
    $id_lieu = $stmt->fetchColumn();
    $stmt = $pdo->prepare('DELETE FROM avis WHERE id = ?');
    $stmt->execute([$_GET['delete']]);
    if ($id_lieu) { updateLieuRating($pdo, $id_lieu); }
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
        
        // Récupérer l'id_lieu pour mettre à jour le rating
        $stmt = $pdo->prepare('SELECT id_lieu FROM avis WHERE id = ?');
        $stmt->execute([$id]);
        $id_lieu = $stmt->fetchColumn();
        if ($id_lieu) { updateLieuRating($pdo, $id_lieu); }
        
        $success_message = 'Avis modifié avec succès.';
        
        // Redirection pour rafraîchir la page
        header('Location: admin-reviews.php?success=1');
        exit;
    } catch (PDOException $e) {
        $error_message = "Erreur lors de la modification de l'avis: " . $e->getMessage();
    }
}

// Ajout d'un avis
if (isset($_POST['add_review'])) {
    $id_utilisateur = (int)$_POST['id_utilisateur'];
    $id_lieu = (int)$_POST['id_lieu'];
    $rating = (int)$_POST['rating'];
    $commentaire = isset($_POST['commentaire']) ? trim($_POST['commentaire']) : '';

    if ($id_utilisateur > 0 && $id_lieu > 0 && $rating >= 1 && $rating <= 5) {
        try {
            $stmt = $pdo->prepare("INSERT INTO avis (id_utilisateur, id_lieu, rating, commentaire) VALUES (?, ?, ?, ?)");
            $stmt->execute([$id_utilisateur, $id_lieu, $rating, $commentaire]);
            updateLieuRating($pdo, $id_lieu);
            $success_message = 'Avis ajouté avec succès.';
        } catch (PDOException $e) {
            $error_message = "Erreur lors de l'ajout de l'avis: " . $e->getMessage();
        }
    } else {
        $error_message = 'Veuillez remplir tous les champs obligatoires correctement.';
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

function updateLieuRating($pdo, $lieu_id) {
    $stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating FROM avis WHERE id_lieu = ?");
    $stmt->execute([$lieu_id]);
    $avg = $stmt->fetchColumn();
    $avg = $avg !== false ? round($avg, 2) : null;
    $stmt = $pdo->prepare("UPDATE lieux SET rating = ? WHERE id = ?");
    $stmt->execute([$avg, $lieu_id]);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Admin - Gestion des Avis</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2D796D;
            --primary-light: #E6F0EE;
            --text-dark: #333;
            --border-radius: 8px;
            --shadow: 0 2px 4px rgba(0,0,0,0.1);
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Montserrat', sans-serif;
        }

        body {
            background-color: #f5f5f5;
            color: var(--text-dark);
            line-height: 1.6;
            padding-top: 80px;
        }

        .header {
            background: white;
            height: 80px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }

        .header-container {
    max-width: 1400px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    padding: 0 2rem;
    height: 80px;
    position: relative;
    gap: 4rem; /* Espacement entre les sections */
}

.header-left {
    display: flex;
    align-items: center;
    gap: 2rem;
    min-width: 250px; /* Largeur fixe pour la section gauche */
}

        .logo {
            height: 45px;
            width: auto;
            margin-right: 1rem;
        }

        .nav-center {
    display: flex;
    align-items: center;
    gap: 3rem; /* Espacement entre les liens */
    flex-grow: 1; /* Prend l'espace disponible */
    justify-content: center; /* Centre les liens */
    padding: 0 2rem; /* Espacement interne */
}

        .nav-link {
            color: var(--text-dark);
            text-decoration: none;
            font-weight: 500;
            padding: 0.75rem 1.25rem;
            transition: color 0.3s ease;
            white-space: nowrap;
        }

        .admin-actions {
    display: flex;
    align-items: center;
    gap: 1rem;
    min-width: 250px; /* Largeur fixe pour la section droite */
    justify-content: flex-end; /* Aligne à droite */
}

.header-left::after {
    content: "";
    height: 30px;
    width: 1px;
    background-color: #e5e7eb;
    margin-left: 1rem;
}

.nav-center {
    position: static;
    transform: none;
}

        .admin-actions .nav-link {
            padding: 0.75rem 1.75rem;
            border-radius: var(--border-radius);
            font-weight: 500;
            transition: all 0.2s ease;
            white-space: nowrap;
            min-width: 180px;
            text-align: center;
        }

        .admin-actions .nav-link:first-child {
            background: var(--primary-light);
            color: var(--primary-color);
        }

        .admin-actions .nav-link:last-child {
            background: var(--primary-color);
            color: white;
        }

        .menu-toggle {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: var(--primary-color);
            font-weight: 500;
            text-decoration: none;
            font-size: 1.1rem;
            padding: 0.75rem;
            background: none;
            border: none;
            cursor: pointer;
        }

        .menu-toggle span {
            font-size: 1.1rem;
        }

        @media (max-width: 1200px) {
            .header-container {
                padding: 0 1.5rem;
            }

            .nav-center {
                margin: 0 auto;
            }

            .admin-actions {
                gap: 1.5rem;
                margin-left: auto;
            }

            .admin-actions .nav-link {
                min-width: 120px;
                padding: 0.75rem 1.25rem;
            }
        }

        @media (max-width: 768px) {
            .header-container {
                padding: 0 1rem;
            }

            .nav-center {
                display: none;
            }

            .admin-actions {
                gap: 1rem;
                margin-left: auto;
            }

            .admin-actions .nav-link {
                min-width: auto;
                padding: 0.75rem 1rem;
            }

            .menu-toggle span {
                display: none;
            }
        }

        .page-header {
            background: var(--primary-color);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
            text-align: center;
        }

        .page-title {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .page-description {
            font-size: 1.1rem;
            opacity: 0.9;
            max-width: 800px;
            margin: 0 auto;
        }

        .container {
            max-width: 1800px;
            margin: 0 auto;
            padding: 2rem 3rem;
        }

        .stats-grid {
            display: flex;
            gap: 3rem;
            margin-bottom: 3rem;
            padding: 0 1rem;
        }

        .stat-card {
            flex: 1;
            background: white;
            padding: 3rem;
            border-radius: var(--border-radius);
            text-align: center;
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 2rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }

        .stat-icon {
            background: var(--primary-light);
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .stat-card i {
            font-size: 2.5rem;
            color: var(--primary-color);
        }

        .stat-content {
            text-align: left;
            flex: 1;
        }

        .stat-content h3 {
            font-size: 3.5rem;
            margin: 0;
            color: var(--primary-color);
            font-weight: 600;
            line-height: 1.2;
        }

        .stat-content p {
            margin: 0.75rem 0 0;
            color: var(--gray-600);
            font-size: 1.25rem;
            font-weight: 500;
        }

        @media (max-width: 1600px) {
            .container {
                max-width: 1400px;
                padding: 2rem;
            }
        }

        @media (max-width: 1200px) {
            .container {
                padding: 1.5rem;
            }

            .stats-grid {
                gap: 2rem;
                padding: 0;
            }

            .stat-card {
                padding: 2rem;
            }

            .form-card, .list-card {
                margin: 0 0 2rem 0;
            }

            .table-container {
                padding: 1rem 2rem;
            }
        }

        .form-card, .list-card {
            margin: 0 1rem 3rem 1rem;
        }

        .form-content {
            padding: 3rem;
        }

        .form-row {
            gap: 3rem;
            margin-bottom: 3rem;
        }

        .table-container {
            padding: 1.5rem 3rem;
        }

        .table th, .table td {
            padding: 1.5rem 2rem;
        }

        .filters-wrapper {
            padding: 2rem 3rem;
            margin: 0;
        }

        .filters-container {
            gap: 3rem;
        }

        .form-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
            overflow: hidden;
        }

        .form-header {
            background: var(--primary-color);
            color: white;
            padding: 1.5rem 2rem;
            font-size: 1.25rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
        }

        .form-header h2 {
            color: white;
            margin: 0;
            font-size: 1.25rem;
        }

        .form-header i {
            color: white;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.75rem;
            color: var(--gray-600);
            font-weight: 500;
            font-size: 1rem;
        }

        .form-control {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 1px solid var(--gray-200);
            border-radius: var(--border-radius);
            font-size: 1rem;
            color: var(--gray-600);
            background-color: white;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px var(--primary-light);
        }

        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%236b7280' viewBox='0 0 16 16'%3E%3Cpath d='M8 11.5l-5-5h10l-5 5z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            padding-right: 2.5rem;
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 2rem;
        }

        .btn {
            padding: 0.875rem 1.5rem;
            border: none;
            border-radius: var(--border-radius);
            font-weight: 500;
            font-size: 1rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: #236b5f;
            transform: translateY(-1px);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .table th {
            background: var(--gray-50);
            padding: 1rem 1.5rem;
            font-weight: 600;
            color: var(--text-dark);
            text-align: left;
            border-bottom: 2px solid var(--gray-200);
        }

        .table td {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--gray-200);
            color: var(--text-dark);
        }

        .table tr:last-child td {
            border-bottom: none;
        }

        .table tbody tr:hover {
            background-color: var(--primary-light);
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .btn-edit,
        .btn-delete {
            width: 32px;
            height: 32px;
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-edit {
            background: var(--primary-color);
        }

        .btn-delete {
            background: #dc3545;
        }

        .btn-edit:hover,
        .btn-delete:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .rating {
            color: #ffc107;
            font-size: 1.1rem;
            letter-spacing: 1px;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--gray-500);
        }

        .empty-state i {
            font-size: 3rem;
            color: var(--gray-400);
            margin-bottom: 1rem;
        }

        .filters-container {
            display: flex;
            gap: 1.5rem;
        }

        .filter-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            background: rgba(255, 255, 255, 0.1);
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
        }

        .filter-icon {
            color: white;
            opacity: 0.8;
        }

        .filter-select {
            background: transparent;
            border: none;
            color: white;
            font-size: 0.9rem;
            padding-right: 1.5rem;
            cursor: pointer;
        }

        .filter-select option {
            background: white;
            color: var(--gray-600);
        }

        .notification {
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
            gap: 1rem;
            font-weight: 500;
        }

        .notification i {
            font-size: 1.25rem;
        }

        .notification.success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #059669;
        }

        .notification.error {
            background: #fee2e2;
            color: #dc2626;
            border-left: 4px solid #dc2626;
        }

        .text-primary {
            color: var(--primary-color);
            margin-right: 0.5rem;
        }

        .table td {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--gray-200);
            color: var(--gray-600);
            vertical-align: middle;
        }

        .table td i {
            font-size: 1rem;
            width: 20px;
            text-align: center;
        }

        .form-group label i {
            color: var(--primary-color);
            margin-right: 0.5rem;
            width: 16px;
            text-align: center;
        }

        /* Animation pour les boutons et les éléments interactifs */
        .btn, .btn-edit, .btn-delete, .form-control {
            transition: all 0.2s ease-in-out;
        }

        /* Style amélioré pour le survol des lignes du tableau */
        .table tbody tr {
            transition: all 0.2s ease-in-out;
        }

        .table tbody tr:hover {
            background-color: var(--primary-light);
            transform: translateX(5px);
        }

        /* Style amélioré pour les notifications */
        .notification {
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* Style pour les tooltips des boutons d'action */
        [title] {
            position: relative;
        }

        [title]:hover::after {
            content: attr(title);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            padding: 0.5rem;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            border-radius: 4px;
            font-size: 0.875rem;
            white-space: nowrap;
            z-index: 10;
            margin-bottom: 5px;
        }

        /* Style pour les labels des formulaires */
        .form-group label {
            display: flex;
            align-items: center;
            margin-bottom: 0.75rem;
            color: var(--gray-600);
            font-weight: 500;
        }

        /* Style pour les sélecteurs */
        select.form-control option {
            padding: 0.5rem;
        }

        select.form-control option:hover {
            background-color: var(--primary-light);
        }

        /* Style pour le textarea */
        textarea.form-control {
            font-family: inherit;
            line-height: 1.5;
            padding: 1rem;
        }

        /* Style pour les étoiles de notation */
        .rating {
            display: flex;
            align-items: center;
            gap: 2px;
        }

        .list-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            margin-top: 2rem;
        }

        .list-header {
            background: var(--primary-color);
            padding: 1.5rem 2rem;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
        }

        .list-header h2 {
            color: white;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.5rem;
            font-weight: 500;
        }

        .list-header i {
            color: white;
            font-size: 1.25rem;
        }

        .list-header span {
            color: white;
        }

        .filters-wrapper {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-top: 2rem;
            box-shadow: var(--shadow);
        }

        .filter-item {
            position: relative;
        }

        .filter-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .filter-label i {
            color: var(--primary-color);
        }

        .filter-select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: var(--border-radius);
            background: white;
            color: var(--text-dark);
            font-size: 0.95rem;
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%236b7280' viewBox='0 0 16 16'%3E%3Cpath d='M8 11.5l-5-5h10l-5 5z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            transition: all 0.2s ease;
        }

        .filter-select:hover {
            border-color: var(--primary-color);
        }

        .filter-select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px var(--primary-light);
        }

        .menu-wrapper {
            position: relative;
        }

        .menu-toggle {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: var(--primary-color);
            font-weight: 500;
            text-decoration: none;
            font-size: 1.1rem;
            padding: 0.5rem;
            background: none;
            border: none;
            cursor: pointer;
        }

        .menu-toggle i {
            font-size: 1.5rem;
        }

        .dropdown-menu {
            display: none;
            position: fixed;
            top: 80px;
            left: 0;
            height: calc(100vh - 80px);
            width: 300px;
            background: white;
            z-index: 1000;
            padding: 2rem;
            transform: translateX(-100%);
            transition: transform 0.3s ease;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }

        .dropdown-menu.active {
            display: block;
            transform: translateX(0);
        }

        .menu-overlay {
            display: none;
            position: fixed;
            top: 80px;
            left: 0;
            width: 100%;
            height: calc(100vh - 80px);
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .menu-overlay.active {
            display: block;
            opacity: 1;
        }

        .dropdown-header {
            padding-bottom: 1.5rem;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid #E5E7EB;
        }

        .dropdown-header h2 {
            color: var(--primary-color);
            font-size: 1.75rem;
            margin: 0;
            font-weight: 600;
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.75rem 0;
            color: #64748B;
            text-decoration: none;
            transition: all 0.3s ease;
            margin-bottom: 0.5rem;
        }

        .dropdown-item:hover {
            color: var(--primary-color);
        }

        .dropdown-item.active {
            color: var(--primary-color);
        }

        .dropdown-item i {
            font-size: 1.25rem;
            width: 24px;
            text-align: center;
        }

        .dropdown-item span {
            font-size: 1.1rem;
            font-weight: 500;
        }

        .admin-actions {
            display: flex;
            gap: 1rem;
        }

        .admin-actions .nav-link {
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .admin-actions .nav-link:last-child {
            background: var(--primary-color);
            color: white;
        }

        .admin-actions .nav-link:last-child:hover {
            background: #236b5f;
        }

        .admin-actions .nav-link:first-child {
            background: var(--primary-light);
            color: var(--primary-color);
        }

        .admin-actions .nav-link:first-child:hover {
            background: #d1e7e3;
        }
    </style>
</head>
<body>
    <div class="menu-overlay" id="menuOverlay"></div>
    <div class="header">
        <div class="header-container">
            <div class="header-left">
                <img src="https://i.postimg.cc/g07GgLp5/VMaroc-logo-trf.png" alt="VMaroc" class="logo">
                <div class="menu-wrapper">
                    <button class="menu-toggle" onclick="toggleMenu()">
                        <i class="fas fa-bars"></i>
                        <span>Menu</span>
                    </button>
                    <div class="dropdown-menu" id="adminMenu">
                        <div class="dropdown-header">
                            <h2>Administration</h2>
                        </div>
                        <a href="admin-cities.php" class="dropdown-item">
                            <i class="fas fa-building"></i>
                            <span>Gérer les villes</span>
                        </a>
                        <a href="admin-places.php" class="dropdown-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>Gérer les lieux</span>
                        </a>
                        <a href="admin-users.php" class="dropdown-item">
                            <i class="fas fa-users"></i>
                            <span>Gérer les utilisateurs</span>
                        </a>
                        <a href="admin-reviews.php" class="dropdown-item active">
                            <i class="fas fa-star"></i>
                            <span>Gérer les avis</span>
                        </a>
            </div>
        </div>
            </div>
            <nav class="nav-center">
                <a href="../index.php" class="nav-link">Accueil</a>
                <a href="../destinations.php" class="nav-link">Destinations</a>
                <a href="../recommendations.php" class="nav-link">Recommandations</a>
            </nav>
            <div class="admin-actions">
                <a href="admin-panel.php" class="nav-link">Panel Admin</a>
                <a href="../logout.php" class="nav-link">Déconnexion</a>
            </div>
        </div>
    </div>

    <div class="page-header">
        <div class="container">
            <h1 class="page-title">Gestion des Avis</h1>
            <p class="page-description">Gérez les avis des utilisateurs sur les différents lieux touristiques.</p>
        </div>
    </div>

    <div class="container">
        <?php if (isset($success_message)): ?>
            <div class="notification success">
                <i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="notification error">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                <i class="fas fa-star"></i>
                </div>
                <div class="stat-content">
                <h3><?= count($reviews) ?></h3>
                <p>Avis au total</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                <h3><?= count($users) ?></h3>
                <p>Utilisateurs</p>
                </div>
        </div>
            <div class="stat-card">
                <div class="stat-icon">
                <i class="fas fa-map-marker-alt"></i>
                </div>
                <div class="stat-content">
                <h3><?= count($places) ?></h3>
                <p>Lieux</p>
                </div>
            </div>
        </div>

        <div class="form-card">
            <div class="form-header">
                <i class="fas fa-plus-circle"></i>
                <h2>Ajouter un Avis</h2>
            </div>
            <div class="form-content">
                <form action="" method="POST" class="review-form">
                <input type="hidden" name="add_review" value="1">
                <div class="form-row">
                    <div class="form-group">
                            <label for="id_utilisateur">
                                <i class="fas fa-user"></i>
                                Utilisateur
                            </label>
                            <select id="id_utilisateur" name="id_utilisateur" class="form-control" required>
                            <option value="">Sélectionner un utilisateur</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?= htmlspecialchars($user['id']) ?>"><?= htmlspecialchars($user['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                            <label for="id_lieu">
                                <i class="fas fa-map-marker-alt"></i>
                                Lieu
                            </label>
                            <select id="id_lieu" name="id_lieu" class="form-control" required>
                            <option value="">Sélectionner un lieu</option>
                            <?php foreach ($places as $place): ?>
                                <option value="<?= htmlspecialchars($place['id']) ?>"><?= htmlspecialchars($place['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                            <label for="rating">
                                <i class="fas fa-star"></i>
                                Note
                            </label>
                            <select id="rating" name="rating" class="form-control" required>
                            <option value="">Sélectionner une note</option>
                                <?php for($i = 1; $i <= 5; $i++): ?>
                                    <option value="<?= $i ?>"><?= str_repeat('★', $i) . str_repeat('☆', 5-$i) ?></option>
                                <?php endfor; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                        <label for="commentaire">
                            <i class="fas fa-comment"></i>
                            Commentaire
                        </label>
                        <textarea id="commentaire" name="commentaire" class="form-control" rows="4" placeholder="Entrez votre commentaire ici..."></textarea>
                </div>
                <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus"></i>
                            Ajouter l'avis
                        </button>
                </div>
            </form>
        </div>
        </div>

        <div class="list-card">
            <div class="list-header">
                <h2>
                    <i class="fas fa-list"></i>
                    <span>Liste des Avis</span>
                </h2>
            </div>
            <div class="filters-wrapper">
                <div class="filters-container">
                    <div class="filter-item">
                        <label class="filter-label">
                            <i class="fas fa-user"></i>
                            Tous les utilisateurs
                        </label>
                        <select class="filter-select" id="filterUser">
                        <option value="">Tous les utilisateurs</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?= htmlspecialchars($user['nom']) ?>"><?= htmlspecialchars($user['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    </div>
                    <div class="filter-item">
                        <label class="filter-label">
                            <i class="fas fa-map-marker-alt"></i>
                            Tous les lieux
                        </label>
                        <select class="filter-select" id="filterPlace">
                        <option value="">Tous les lieux</option>
                        <?php foreach ($places as $place): ?>
                            <option value="<?= htmlspecialchars($place['nom']) ?>"><?= htmlspecialchars($place['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    </div>
                    <div class="filter-item">
                        <label class="filter-label">
                            <i class="fas fa-star"></i>
                            Toutes les notes
                        </label>
                        <select class="filter-select" id="filterRating">
                        <option value="">Toutes les notes</option>
                            <?php for($i = 1; $i <= 5; $i++): ?>
                                <option value="<?= $i ?>"><?= str_repeat('★', $i) ?></option>
                            <?php endfor; ?>
                    </select>
                    </div>
                </div>
            </div>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th width="100">Actions</th>
                            <th>Utilisateur</th>
                            <th>Lieu</th>
                            <th width="120">Note</th>
                            <th>Commentaire</th>
                            <th width="120">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reviews)): ?>
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <i class="fas fa-comments"></i>
                                    <p>Aucun avis trouvé</p>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($reviews as $review): ?>
                                <tr>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-edit" title="Modifier" onclick="editReview(<?= $review['id'] ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn-delete" title="Supprimer" onclick="confirmDelete(<?= $review['id'] ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                    <td class="user-cell">
                                        <div class="user-icon">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <?= htmlspecialchars($review['user_nom']) ?>
                                    </td>
                                    <td class="place-cell">
                                        <div class="place-icon">
                                            <i class="fas fa-map-marker-alt"></i>
                                        </div>
                                        <?= htmlspecialchars($review['lieu_nom']) ?>
                                    </td>
                                    <td class="rating-cell">
                                        <?= str_repeat('★', $review['rating']) ?>
                                    </td>
                                    <td><?= htmlspecialchars($review['commentaire']) ?></td>
                                    <td class="date-cell">
                                        <i class="far fa-calendar-alt"></i>
                                        <?= date('d/m/Y', strtotime($review['date_creation'])) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function toggleMenu() {
            const menu = document.getElementById('adminMenu');
            const overlay = document.getElementById('menuOverlay');
            menu.classList.toggle('active');
            overlay.classList.toggle('active');
        }

        // Fermer le menu quand on clique sur l'overlay
        document.getElementById('menuOverlay').addEventListener('click', function() {
            const menu = document.getElementById('adminMenu');
            menu.classList.remove('active');
            this.classList.remove('active');
        });

        // Fermer le menu quand on clique sur un lien du menu
        document.querySelectorAll('.dropdown-item').forEach(item => {
            item.addEventListener('click', function() {
                const menu = document.getElementById('adminMenu');
                const overlay = document.getElementById('menuOverlay');
                menu.classList.remove('active');
                overlay.classList.remove('active');
            });
        });

        // Filtrage des avis avec animation
        document.getElementById('filterUser').addEventListener('change', filterReviews);
        document.getElementById('filterPlace').addEventListener('change', filterReviews);
        document.getElementById('filterRating').addEventListener('change', filterReviews);

        function filterReviews() {
            const userFilter = document.getElementById('filterUser').value.toLowerCase();
            const placeFilter = document.getElementById('filterPlace').value.toLowerCase();
            const ratingFilter = document.getElementById('filterRating').value;

            const rows = document.querySelectorAll('.table tbody tr');
            let hasVisibleRows = false;

            rows.forEach(row => {
                if (row.querySelector('.empty-state')) return;

                const userName = row.querySelector('.user-cell').textContent.trim().toLowerCase();
                const placeName = row.querySelector('.place-cell').textContent.trim().toLowerCase();
                const rating = row.querySelector('.rating-cell').textContent.trim().length;

                const userMatch = !userFilter || userName.includes(userFilter);
                const placeMatch = !placeFilter || placeName.includes(placeFilter);
                const ratingMatch = !ratingFilter || rating === parseInt(ratingFilter);

                if (userMatch && placeMatch && ratingMatch) {
                    row.style.display = '';
                    hasVisibleRows = true;
                    row.style.opacity = '1';
                } else {
                    row.style.opacity = '0';
                    setTimeout(() => {
                        row.style.display = 'none';
                    }, 200);
                }
            });

            // Afficher l'état vide si aucun résultat
            const emptyState = document.querySelector('.empty-state')?.parentElement?.parentElement;
            if (emptyState) {
                emptyState.style.display = hasVisibleRows ? 'none' : '';
            }
        }

        function editReview(id) {
            // Implémenter la logique d'édition ici
            console.log('Édition de l\'avis:', id);
        }

        function confirmDelete(id) {
            if (confirm('Êtes-vous sûr de vouloir supprimer cet avis ?')) {
                window.location.href = `?delete=${id}`;
            }
        }
    </script>
</body>
</html> 