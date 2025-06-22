<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

try {
    $pdo = new PDO('mysql:host=localhost;dbname=vmaroc;charset=utf8', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Erreur de connexion : " . $e->getMessage();
    die();
}

// Récupérer les statistiques
try {
    $stats = [
        'total_users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
        'admin_users' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn(),
        'regular_users' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn()
    ];
} catch (PDOException $e) {
    $stats = ['total_users' => 0, 'admin_users' => 0, 'regular_users' => 0];
}

// Traitement de l'ajout d'utilisateur
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $username = trim($_POST['nom']);
    $email = trim($_POST['email']);
    $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
    $role = trim($_POST['role']);

    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $email, $password, $role]);
        $_SESSION['success'] = "Utilisateur ajouté avec succès !";
        header("Location: admin-users.php");
        exit;
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur lors de l'ajout de l'utilisateur : " . $e->getMessage();
    }
}

// Traitement de la suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $user_id = $_POST['user_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $_SESSION['success'] = "Utilisateur supprimé avec succès !";
        header("Location: admin-users.php");
        exit;
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur lors de la suppression : " . $e->getMessage();
    }
}

// Traitement de la modification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    $user_id = $_POST['user_id'];
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $role = trim($_POST['edit_role']);
    
    try {
        if (!empty($_POST['new_password'])) {
            $password = password_hash(trim($_POST['new_password']), PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, password = ?, role = ? WHERE id = ?");
            $stmt->execute([$username, $email, $password, $role, $user_id]);
            } else {
            $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?");
            $stmt->execute([$username, $email, $role, $user_id]);
        }
        $_SESSION['success'] = "Utilisateur modifié avec succès !";
        header("Location: admin-users.php");
        exit;
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur lors de la modification : " . $e->getMessage();
    }
}

// Récupération des utilisateurs
try {
    $stmt = $pdo->query("SELECT * FROM users ORDER BY id DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $users = [];
    $_SESSION['error'] = "Erreur lors de la récupération des utilisateurs : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Admin - Gestion des Utilisateurs</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2D796D;
            --light-green: #E6F0EE;
            --text-dark: #2D3748;
            --text-gray: #718096;
            --border-radius: 8px;
            --box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Montserrat', sans-serif;
        }

        body {
            background-color: #f5f6fa;
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
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem;
        }

        .logo {
            height: 45px;
            width: auto;
        }

        .nav-center {
            display: flex;
            gap: 2.5rem;
        }

        .nav-link {
            color: var(--text-dark);
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem;
            transition: color 0.3s ease;
        }

        .nav-link:hover {
            color: var(--primary-color);
        }

        .page-header {
            background: var(--primary-color);
            color: white;
            padding: 4rem 2rem;
            text-align: center;
        }

        .page-header h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .stats-container {
            padding: 2rem;
            margin-top: 3rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            display: flex;
            align-items: center;
            gap: 1.5rem;
            box-shadow: var(--box-shadow);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            color: var(--primary-color);
            font-size: 2rem;
            width: 40px;
        }

        .stat-content h3 {
            font-size: 2.5rem;
            font-weight: 600;
            margin: 0;
        }

        .add-user-section {
            background: white;
            border-radius: var(--border-radius);
            margin: 2rem;
            box-shadow: var(--box-shadow);
        }

        .section-header {
            background: var(--primary-color);
            color: white;
            padding: 1.5rem 2rem;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .form-container {
            padding: 2rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-dark);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e0e0e0;
            border-radius: var(--border-radius);
            font-size: 0.9rem;
            transition: all 0.3s ease;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
        }

        select.form-control {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%232D796D' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 16px;
            padding-right: 2.5rem;
            cursor: pointer;
        }

        select.form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(45, 121, 109, 0.1);
            outline: none;
        }

        select.form-control option {
            padding: 0.75rem;
            font-size: 0.9rem;
            background-color: white;
            color: var(--text-dark);
        }

        select.form-control option:hover {
            background-color: var(--light-green);
        }

        .btn-submit {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: var(--border-radius);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-submit:hover {
            background: #236b5f;
            transform: translateY(-2px);
        }

        .users-table {
            background: white;
            border-radius: var(--border-radius);
            margin: 2rem;
            box-shadow: var(--box-shadow);
        }

        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .table th {
            background: #f8f9fa;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
        }

        .table td {
            padding: 1rem;
            border-top: 1px solid #e9ecef;
        }

        .table tr:hover {
            background: var(--light-green);
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .btn-action {
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .btn-edit {
            background: var(--primary-color);
            color: white;
        }

        .btn-delete {
            background: #dc3545;
            color: white;
        }

        .role-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .role-admin {
            background: #fce7f3;
            color: #be185d;
        }

        .role-user {
            background: #e0f2fe;
            color: #0369a1;
        }

        @media (max-width: 768px) {
            .stats-grid,
            .form-grid {
                grid-template-columns: 1fr;
            }

            .table {
                display: block;
                overflow-x: auto;
            }
        }

        /* Styles pour la modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .modal-content {
            position: relative;
            background-color: #fff;
            margin: 10% auto;
            padding: 20px;
            width: 50%;
            border-radius: var(--border-radius);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .close {
            position: absolute;
            right: 20px;
            top: 10px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: var(--primary-color);
        }

        /* Style pour les messages de confirmation */
        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: var(--border-radius);
            opacity: 1;
            transition: opacity 0.5s ease-in-out;
        }

        .alert.fade-out {
            opacity: 0;
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

        .menu-wrapper {
            position: relative;
        }

        .dropdown-menu {
            display: none;
            position: fixed;
            top: 80px; /* Hauteur du header */
            left: 0;
            height: calc(100vh - 80px); /* Hauteur totale moins la hauteur du header */
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
            top: 80px; /* Commence sous le header */
            left: 0;
            width: 100%;
            height: calc(100vh - 80px); /* Hauteur totale moins la hauteur du header */
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

        .dropdown-item.active i {
            color: inherit;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 3rem;
        }

        /* Style spécifique pour le sélecteur de rôle */
        #role, #edit_role {
            background-color: white;
            font-weight: 500;
        }

        #role option[value="admin"], #edit_role option[value="admin"] {
            color: #be185d;
            font-weight: 500;
        }

        #role option[value="user"], #edit_role option[value="user"] {
            color: #0369a1;
            font-weight: 500;
        }

        /* Hover effect pour le select */
        select.form-control:hover {
            border-color: var(--primary-color);
            background-color: var(--light-green);
        }
    </style>
</head>
<body>
    <div class="menu-overlay" id="menuOverlay"></div>
    <header class="header">
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
                        <a href="admin-users.php" class="dropdown-item active">
                            <i class="fas fa-users"></i>
                            <span>Gérer les utilisateurs</span>
                        </a>
                        <a href="admin-reviews.php" class="dropdown-item">
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
    </header>

    <main>
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                ?>
                </div>
            <?php endif; ?>

        <div class="page-header">
            <h1>Gestion des Utilisateurs</h1>
            <p>Gérez efficacement les utilisateurs et leurs rôles sur la plateforme</p>
        </div>

        <div class="stats-container">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['total_users']; ?></h3>
                        <p>Utilisateurs Total</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['admin_users']; ?></h3>
                        <p>Administrateurs</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['regular_users']; ?></h3>
                        <p>Utilisateurs Standard</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="add-user-section">
            <div class="section-header">
                <i class="fas fa-user-plus"></i>
                <h2>Ajouter un utilisateur</h2>
                </div>
            <div class="form-container">
                <form method="POST" class="user-form">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="nom">Nom</label>
                            <input type="text" id="nom" name="nom" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Mot de passe</label>
                            <input type="password" id="password" name="password" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="role">Rôle</label>
                            <select id="role" name="role" class="form-control" required>
                                <option value="" disabled selected>Sélectionnez un rôle</option>
                                <option value="user">Utilisateur</option>
                                <option value="admin">Administrateur</option>
                            </select>
                        </div>
                    </div>
                    <div style="margin-top: 1.5rem;">
                        <button type="submit" name="add_user" class="btn-submit">
                            <i class="fas fa-plus"></i> Ajouter l'utilisateur
                        </button>
                    </div>
                </form>
            </div>
            </div>

        <div class="users-table">
            <div class="section-header">
                <i class="fas fa-list"></i>
                    <h2>Liste des utilisateurs</h2>
                </div>
            <div style="padding: 1rem;">
                <table class="table">
                        <thead>
                            <tr>
                                <th>Actions</th>
                                <th>ID</th>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Rôle</th>
                            <th>Date d'inscription</th>
                            </tr>
                        </thead>
                        <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn-action btn-edit" onclick="editUser(<?php echo $user['id']; ?>, '<?php echo addslashes($user['username']); ?>', '<?php echo addslashes($user['email']); ?>', '<?php echo $user['role']; ?>')">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?');">
                                        <input type="hidden" name="delete_user" value="1">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" class="btn-action btn-delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                                        </td>
                            <td><?php echo isset($user['id']) ? $user['id'] : ''; ?></td>
                            <td><?php echo isset($user['username']) ? htmlspecialchars($user['username']) : ''; ?></td>
                            <td><?php echo isset($user['email']) ? htmlspecialchars($user['email']) : ''; ?></td>
                            <td>
                                <span class="role-badge <?php echo isset($user['role']) && $user['role'] === 'admin' ? 'role-admin' : 'role-user'; ?>">
                                    <?php echo isset($user['role']) ? ucfirst($user['role']) : 'user'; ?>
                                </span>
                            </td>
                            <td><?php echo isset($user['created_at']) ? $user['created_at'] : ''; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                        </tbody>
                    </table>
            </div>
        </div>

        <!-- Modal d'édition -->
        <div id="editModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Modifier l'utilisateur</h2>
                <form method="POST" class="user-form">
                    <input type="hidden" name="edit_user" value="1">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="username">Nom</label>
                            <input type="text" id="edit_username" name="username" class="form-control" required>
            </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="edit_email" name="email" class="form-control" required>
            </div>
                        <div class="form-group">
                            <label for="new_password">Nouveau mot de passe (laisser vide pour ne pas changer)</label>
                            <input type="password" id="new_password" name="new_password" class="form-control">
                </div>
                        <div class="form-group">
                            <label for="edit_role">Rôle</label>
                            <select id="edit_role" name="edit_role" class="form-control" required>
                                <option value="" disabled>Sélectionnez un rôle</option>
                                <option value="user">Utilisateur</option>
                                <option value="admin">Administrateur</option>
                            </select>
            </div>
        </div>
                    <div style="margin-top: 1.5rem;">
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-save"></i> Enregistrer les modifications
                        </button>
                </div>
                </form>
            </div>
        </div>
    </main>

<script>
        // Fonction pour ouvrir la modal d'édition
        function editUser(id, username, email, role) {
            document.getElementById('edit_user_id').value = id;
            document.getElementById('edit_username').value = username;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_role').value = role;
            document.getElementById('editModal').style.display = 'block';
        }

        // Auto-dismiss success messages after 3 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const successAlert = document.querySelector('.alert-success');
            if (successAlert) {
                setTimeout(function() {
                    successAlert.classList.add('fade-out');
                    setTimeout(function() {
                        successAlert.remove();
                    }, 500);
                }, 3000);
            }
        });

        // Fermer la modal quand on clique sur le X
        document.querySelector('.close').onclick = function() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Fermer la modal quand on clique en dehors
        window.onclick = function(event) {
            if (event.target == document.getElementById('editModal')) {
                document.getElementById('editModal').style.display = 'none';
            }
        }

        function toggleMenu() {
            const menu = document.getElementById('adminMenu');
            const overlay = document.getElementById('menuOverlay');
            menu.classList.toggle('active');
            overlay.classList.toggle('active');
            document.body.style.overflow = menu.classList.contains('active') ? 'hidden' : '';
        }

        // Fermer le menu si on clique sur l'overlay
        document.getElementById('menuOverlay').addEventListener('click', function() {
            toggleMenu();
        });

        // Empêcher la propagation des clics dans le menu
        document.getElementById('adminMenu').addEventListener('click', function(event) {
            event.stopPropagation();
    });
</script>
</body>
</html> 