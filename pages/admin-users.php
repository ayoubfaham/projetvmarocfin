<?php
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
            $username = $_POST['username'];
            $email = $_POST['email'];
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $tel = $_POST['tel'] ?? null;
            $preferences = $_POST['preferences'] ?? null;
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, tel, preferences) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$username, $email, $password, $tel, $preferences])) {
                $success = "Utilisateur ajouté avec succès";
            } else {
                $error = "Erreur lors de l'ajout de l'utilisateur";
            }
        } elseif ($_POST['action'] === 'edit') {
            $id = $_POST['id'];
            $username = $_POST['username'];
            $email = $_POST['email'];
            $tel = $_POST['tel'] ?? null;
            $preferences = $_POST['preferences'] ?? null;
            $sql = "UPDATE users SET username = ?, email = ?, tel = ?, preferences = ?";
            $params = [$username, $email, $tel, $preferences];
            if (!empty($_POST['password'])) {
                $sql .= ", password = ?";
                $params[] = password_hash($_POST['password'], PASSWORD_DEFAULT);
            }
            $sql .= " WHERE id = ?";
            $params[] = $id;
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute($params)) {
                $success = "Utilisateur modifié avec succès";
            } else {
                $error = "Erreur lors de la modification de l'utilisateur";
            }
        } elseif ($_POST['action'] === 'delete') {
            $id = $_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            if ($stmt->execute([$id])) {
                $success = "Utilisateur supprimé avec succès";
            } else {
                $error = "Erreur lors de la suppression de l'utilisateur";
            }
        }
    }
}

// Récupération des utilisateurs
$stmt = $pdo->query("SELECT * FROM users ORDER BY id DESC");
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Utilisateurs - Admin</title>
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
        .user-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: #eee;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: var(--primary-color);
            font-size: 1.1em;
            margin-right: 10px;
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
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            transition: var(--transition);
        }
        .form-group input:focus {
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
                <a href="../logout.php" class="btn-primary">Déconnexion</a>
            </div>
        </div>
    </header>

    <main style="margin-top:100px;">
    <div class="container">
            <div class="section-title">
                <h2>Gestion des Utilisateurs</h2>
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
                <h3>Ajouter un Utilisateur</h3>
                <form action="" method="POST" class="form-grid">
                    <input type="hidden" name="action" value="add">
                    <div class="form-group">
                        <label for="username">Nom d'utilisateur</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Mot de passe</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label for="tel">Téléphone</label>
                        <input type="text" id="tel" name="tel">
                    </div>
                    <div class="form-group">
                        <label for="preferences">Préférences</label>
                        <input type="text" id="preferences" name="preferences">
                    </div>
                    <div class="form-group" style="display: flex; align-items: flex-end;">
                        <button type="submit" class="btn-solid">Ajouter</button>
                    </div>
                </form>
            </div>

            <!-- Liste des utilisateurs -->
            <?php if (empty($users)): ?>
                <div class="empty-state">
                    <i class="fas fa-users"></i>
                    <h3>Aucun utilisateur trouvé</h3>
                    <p>Commencez par ajouter un utilisateur en utilisant le formulaire ci-dessus.</p>
                </div>
            <?php else: ?>
                <div class="section-title">
                    <h3>Liste des utilisateurs</h3>
                </div>
                <div style="overflow-x:auto;">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Utilisateur</th>
                                <th>Email</th>
                                <th>Téléphone</th>
                                <th>Préférences</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                                    <td data-label="ID"><?php echo $user['id']; ?></td>
                                    <td data-label="Utilisateur">
                                        <span class="user-avatar">
                                            <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                        </span>
                                        <?php echo htmlspecialchars($user['username']); ?>
                                    </td>
                                    <td data-label="Email"><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td data-label="Téléphone"><?php echo htmlspecialchars($user['tel']); ?></td>
                                    <td data-label="Préférences"><?php echo htmlspecialchars($user['preferences']); ?></td>
                                    <td data-label="Actions" class="action-buttons">
                                        <button class="btn-edit" onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)"><i class="fas fa-edit"></i> Modifier</button>
                                        <form action="" method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" class="btn-delete" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')">
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

    <!-- Footer moderne -->
    <footer>
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <h3>Maroc Authentique</h3>
                    <p>Découvrez les merveilles du Maroc avec Maroc Authentique, votre guide de voyage personnalisé pour explorer les plus belles destinations marocaines.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                <div class="footer-col">
                    <h3>Liens Rapides</h3>
                    <ul>
                        <li><a href="../index.php">Accueil</a></li>
                        <li><a href="../destinations.php">Destinations</a></li>
                        <li><a href="../experiences.php">Expériences</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h3>Villes Populaires</h3>
                    <ul>
                        <li><a href="../city.php?name=Marrakech">Marrakech</a></li>
                        <li><a href="../city.php?name=Fès">Fès</a></li>
                        <li><a href="../city.php?name=Chefchaouen">Chefchaouen</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h3>Contact</h3>
                    <p>contact@marocauthentique.com</p>
                    <p>+212 522 123 456</p>
                </div>
            </div>
            <div class="copyright">
                <p>© 2025 Maroc Authentique. Tous droits réservés.</p>
                <p style="margin-top: 10px;">
                    <a href="#" style="color: #BBBBBB; text-decoration: none;">Politique de confidentialité</a> | 
                    <a href="#" style="color: #BBBBBB; text-decoration: none;">Conditions d'utilisation</a>
                </p>
            </div>
        </div>
    </footer>

    <script>
        function editUser(user) {
            document.getElementById('username').value = user.username;
            document.getElementById('email').value = user.email;
            document.getElementById('password').value = '';
            document.getElementById('password').required = false;
            document.getElementById('tel').value = user.tel || '';
            document.getElementById('preferences').value = user.preferences || '';
            const form = document.querySelector('.add-form form');
            form.querySelector('input[name="action"]').value = 'edit';
            const oldId = form.querySelector('input[name="id"]');
            if (oldId) oldId.remove();
            form.insertAdjacentHTML('beforeend', `<input type="hidden" name="id" value="${user.id}">`);
            form.querySelector('button[type="submit"]').textContent = 'Modifier';
            document.querySelector('.add-form').scrollIntoView({ behavior: 'smooth' });
        }
    </script>
</body>
</html> 