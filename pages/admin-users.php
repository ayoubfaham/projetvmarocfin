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
            $role = $_POST['role'] ?? 'user';
            $stmt = $pdo->prepare("INSERT INTO utilisateurs (username, email, password, tel, preferences, role) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$username, $email, $password, $tel, $preferences, $role])) {
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
            $role = $_POST['role'] ?? 'user';
            $sql = "UPDATE utilisateurs SET username = ?, email = ?, tel = ?, preferences = ?, role = ?";
            $params = [$username, $email, $tel, $preferences, $role];
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
            $stmt = $pdo->prepare("DELETE FROM utilisateurs WHERE id = ?");
            if ($stmt->execute([$id])) {
                $success = "Utilisateur supprimé avec succès";
            } else {
                $error = "Erreur lors de la suppression de l'utilisateur";
            }
        }
    }
}

// Récupération des utilisateurs
$stmt = $pdo->query("SELECT * FROM utilisateurs ORDER BY id DESC");
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Admin - Gestion des utilisateurs</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/montserrat-font.css">
    <link rel="stylesheet" href="../css/main.css">
    <style>
        :root {
            --primary-color: #bfa14a;
            --secondary-color: #1A5F7A;
            --white: #fff;
            --light-color: #f6f7fb;
            --border-color: #e9cba7;
            --shadow-md: 0 4px 12px rgba(0,0,0,0.08);
        }
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
        .admin-message {
            padding: 16px 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            font-weight: 500;
            position: relative;
            padding-left: 55px;
            animation: slideInDown 0.5s ease-out forwards;
            border-left: 5px solid;
            display: flex;
            align-items: center;
        }
        .admin-message::before {
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            left: 20px;
            font-size: 1.2rem;
        }
        .admin-message-success {
            background-color: #e7f7ef;
            color: #1d6d4e;
            border-color: #28a745;
        }
        .admin-message-success::before {
            content: '\f058';
            color: #28a745;
        }
        .admin-message-error {
            background-color: #fef0f0;
            color: #b02a37;
            border-color: #dc3545;
        }
        .admin-message-error::before {
            content: '\f057';
            color: #dc3545;
        }
        .admin-message-warning {
            background-color: #fff8e6;
            color: #997404;
            border-color: #ffc107;
        }
        .admin-message-warning::before {
            content: '\f071';
            color: #ffc107;
        }
        .admin-message-info {
            background-color: #e6f3ff;
            color: #0a58ca;
            border-color: #0d6efd;
        }
        .admin-message-info::before {
            content: '\f05a';
            color: #0d6efd;
        }
        @keyframes slideInDown {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
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
                <h2>Gestion des Utilisateurs <span id="userCount" style="font-size:1rem;font-weight:400;color:var(--secondary-color);">(<?php echo count($users); ?>)</span></h2>
                <input type="text" id="searchUserInput" placeholder="Rechercher un utilisateur..." style="padding:10px 16px;border:1px solid var(--border-color);border-radius:6px;min-width:220px;">
            </div>
            <?php if (isset($success)): ?>
                <div class="admin-message admin-message-success">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="admin-message admin-message-error">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            <div class="form" style="max-width:900px;margin:0 auto 40px auto;">
                <h3 style="text-align:center;">
                    Ajouter / Modifier un Utilisateur
                </h3>
                <form action="" method="POST" class="admin-form-flex">
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
                    <div class="form-group">
                        <label for="role">Rôle</label>
                        <select id="role" name="role" required>
                            <option value="user">Utilisateur</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="form-group" style="display: flex; align-items: flex-end;">
                        <button type="submit" class="btn-solid">Ajouter</button>
                    </div>
                </form>
            </div>
            <div class="section-title">
                <h3>Liste des utilisateurs</h3>
            </div>
            <div style="overflow-x:auto;">
                <table class="admin-table" id="usersTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Utilisateur</th>
                            <th>Email</th>
                            <th>Téléphone</th>
                            <th>Préférences</th>
                            <th>Rôle</th>
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
                            <td data-label="Rôle"><?php echo htmlspecialchars($user['role']); ?></td>
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
        </div>
    </main>
    <script>
        function editUser(user) {
            document.getElementById('username').value = user.username;
            document.getElementById('email').value = user.email;
            document.getElementById('password').value = '';
            document.getElementById('password').required = false;
            document.getElementById('tel').value = user.tel || '';
            document.getElementById('preferences').value = user.preferences || '';
            document.getElementById('role').value = user.role || 'user';
            const form = document.querySelector('.admin-form-flex');
            form.querySelector('input[name="action"]').value = 'edit';
            const oldId = form.querySelector('input[name="id"]');
            if (oldId) oldId.remove();
            form.insertAdjacentHTML('beforeend', `<input type="hidden" name="id" value="${user.id}">`);
            form.querySelector('button[type="submit"]').textContent = 'Modifier';
            document.querySelector('.form').scrollIntoView({ behavior: 'smooth' });
        }
    </script>
</body>
</html> 