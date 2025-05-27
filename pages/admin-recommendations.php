<?php
session_start();
require_once '../config/database.php';
require_once '../config/auth.php';

// Vérifier si l'utilisateur est administrateur
if (!isAdmin()) {
    header('Location: ../index.php');
    exit();
}

// Gérer les actions (ajout, modification, suppression)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $titre = $_POST['titre'] ?? '';
                $description = $_POST['description'] ?? '';
                $categorie = $_POST['categorie'] ?? '';
                $prix_min = $_POST['prix_min'] ?? 0;
                $prix_max = $_POST['prix_max'] ?? 0;
                $duree_min = $_POST['duree_min'] ?? 0;
                $duree_max = $_POST['duree_max'] ?? 0;
                $ville_id = $_POST['ville_id'] ?? 0;
                $image_url = $_POST['image_url'] ?? '';

                $stmt = $pdo->prepare("INSERT INTO recommandations (ville_id, titre, description, categorie, prix_min, prix_max, duree_min, duree_max, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$ville_id, $titre, $description, $categorie, $prix_min, $prix_max, $duree_min, $duree_max, $image_url]);
                $success = "Recommandation ajoutée avec succès !";
                break;

            case 'edit':
                $id = $_POST['id'] ?? 0;
                $titre = $_POST['titre'] ?? '';
                $description = $_POST['description'] ?? '';
                $categorie = $_POST['categorie'] ?? '';
                $prix_min = $_POST['prix_min'] ?? 0;
                $prix_max = $_POST['prix_max'] ?? 0;
                $duree_min = $_POST['duree_min'] ?? 0;
                $duree_max = $_POST['duree_max'] ?? 0;
                $ville_id = $_POST['ville_id'] ?? 0;
                $image_url = $_POST['image_url'] ?? '';

                $stmt = $pdo->prepare("UPDATE recommandations SET ville_id = ?, titre = ?, description = ?, categorie = ?, prix_min = ?, prix_max = ?, duree_min = ?, duree_max = ?, image_url = ? WHERE id = ?");
                $stmt->execute([$ville_id, $titre, $description, $categorie, $prix_min, $prix_max, $duree_min, $duree_max, $image_url, $id]);
                $success = "Recommandation mise à jour avec succès !";
                break;

            case 'delete':
                $id = $_POST['id'] ?? 0;
                $stmt = $pdo->prepare("DELETE FROM recommandations WHERE id = ?");
                $stmt->execute([$id]);
                $success = "Recommandation supprimée avec succès !";
                break;
        }
    }
}

// Récupérer les villes et les recommandations
try {
    $stmt = $pdo->query("SELECT * FROM villes ORDER BY nom");
    $villes = $stmt->fetchAll();

    $stmt = $pdo->query("SELECT r.*, v.nom as ville_nom FROM recommandations r JOIN villes v ON r.ville_id = v.id ORDER BY r.id DESC");
    $recommendations = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Erreur lors de la récupération des données : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Recommandations - Admin VMaroc</title>
    <link rel="stylesheet" href="../css/main.css">
    <style>
        .admin-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
        }
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        .admin-header h2 {
            color: var(--primary-color);
        }
        .add-button {
            background-color: var(--accent-color);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: var(--transition);
        }
        .add-button:hover {
            background-color: #6B5B45;
        }
        .recommendation-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .recommendation-table th, .recommendation-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        .recommendation-table th {
            background-color: var(--light-color);
            font-weight: 600;
        }
        .recommendation-actions {
            display: flex;
            gap: 10px;
        }
        .action-button {
            padding: 5px 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            transition: var(--transition);
        }
        .edit-button {
            background-color: #4CAF50;
            color: white;
        }
        .delete-button {
            background-color: #f44336;
            color: white;
        }
        .edit-button:hover {
            background-color: #45a049;
        }
        .delete-button:hover {
            background-color: #da190b;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            width: 90%;
            max-width: 600px;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
        }
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <!-- Header/Navbar -->
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
        <div class="admin-container">
            <?php if (isset($success)): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="admin-header">
                <h2>Gestion des Recommandations</h2>
                <button class="add-button" onclick="openModal('add')">Ajouter une recommandation</button>
            </div>

            <table class="recommendation-table">
                <thead>
                    <tr>
                        <th>Titre</th>
                        <th>Ville</th>
                        <th>Catégorie</th>
                        <th>Prix</th>
                        <th>Durée</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recommendations as $recommendation): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($recommendation['titre']); ?></td>
                            <td><?php echo htmlspecialchars($recommendation['ville_nom']); ?></td>
                            <td><?php echo htmlspecialchars($recommendation['categorie']); ?></td>
                            <td><?php echo htmlspecialchars($recommendation['prix_min'] . ' - ' . $recommendation['prix_max'] . ' MAD'); ?></td>
                            <td><?php echo htmlspecialchars($recommendation['duree_min'] . ' - ' . $recommendation['duree_max'] . ' jours'); ?></td>
                            <td>
                                <div class="recommendation-actions">
                                    <button class="action-button edit-button" onclick="openModal('edit', <?php echo json_encode($recommendation); ?>)">Modifier</button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette recommandation ?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $recommendation['id']; ?>">
                                        <button type="submit" class="action-button delete-button">Supprimer</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Modal pour ajouter/modifier une recommandation -->
    <div id="recommendationModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Ajouter une recommandation</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <form id="recommendationForm" method="POST" onsubmit="return validateForm()">
                <input type="hidden" name="action" id="formAction">
                <input type="hidden" name="id" id="formId">
                
                <div class="form-group">
                    <label for="titre">Titre</label>
                    <input type="text" name="titre" id="titre" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea name="description" id="description" rows="3" required></textarea>
                </div>

                <div class="form-group">
                    <label for="categorie">Catu00e9gorie</label>
                    <select name="categorie" id="categorie" required>
                        <option value="">Choisir une catu00e9gorie</option>
                        <option value="hotels">Hôtels</option>
                        <option value="restaurants">Restaurants</option>
                        <option value="parcs">Parcs</option>
                        <option value="plages">Plages</option>
                        <option value="cinemas">Cinémas</option>
                        <option value="theatres">Théâtres</option>
                        <option value="monuments">Monuments</option>
                        <option value="musees">Musées</option>
                        <option value="shopping">Shopping</option>
                        <option value="vie_nocturne">Vie nocturne</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="ville_id">Ville</label>
                    <select name="ville_id" id="ville_id" required>
                        <option value="">Choisir une ville</option>
                        <?php foreach ($villes as $ville): ?>
                            <option value="<?php echo $ville['id']; ?>"><?php echo htmlspecialchars($ville['nom']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="prix_min">Prix minimum (MAD)</label>
                    <input type="number" name="prix_min" id="prix_min" step="100" required>
                </div>

                <div class="form-group">
                    <label for="prix_max">Prix maximum (MAD)</label>
                    <input type="number" name="prix_max" id="prix_max" step="100" required>
                </div>

                <div class="form-group">
                    <label for="duree_min">Durée minimum (jours)</label>
                    <input type="number" name="duree_min" id="duree_min" min="1" required>
                </div>

                <div class="form-group">
                    <label for="duree_max">Durée maximum (jours)</label>
                    <input type="number" name="duree_max" id="duree_max" min="1" required>
                </div>

                <div class="form-group">
                    <label for="image_url">URL de l'image</label>
                    <input type="url" name="image_url" id="image_url">
                </div>

                <div class="modal-footer">
                    <button type="button" class="action-button" style="background-color: #6c757d; color: white;" onclick="closeModal()">Annuler</button>
                    <button type="submit" class="action-button" id="submitButton" style="background-color: var(--accent-color); color: white;">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(action, data = null) {
            document.getElementById('modalTitle').textContent = action === 'add' ? 'Ajouter une recommandation' : 'Modifier la recommandation';
            document.getElementById('formAction').value = action;
            document.getElementById('submitButton').textContent = action === 'add' ? 'Ajouter' : 'Modifier';

            if (action === 'edit' && data) {
                document.getElementById('formId').value = data.id;
                document.getElementById('titre').value = data.titre;
                document.getElementById('description').value = data.description;
                document.getElementById('categorie').value = data.categorie;
                document.getElementById('ville_id').value = data.ville_id;
                document.getElementById('prix_min').value = data.prix_min;
                document.getElementById('prix_max').value = data.prix_max;
                document.getElementById('duree_min').value = data.duree_min;
                document.getElementById('duree_max').value = data.duree_max;
                document.getElementById('image_url').value = data.image_url;
            } else {
                // Réinitialiser le formulaire
                document.getElementById('recommendationForm').reset();
            }

            document.getElementById('recommendationModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('recommendationModal').style.display = 'none';
        }

        function validateForm() {
            const prixMin = parseInt(document.getElementById('prix_min').value);
            const prixMax = parseInt(document.getElementById('prix_max').value);
            const dureeMin = parseInt(document.getElementById('duree_min').value);
            const dureeMax = parseInt(document.getElementById('duree_max').value);

            if (prixMin > prixMax) {
                alert('Le prix minimum ne peut pas être supérieur au prix maximum');
                return false;
            }

            if (dureeMin > dureeMax) {
                alert('La durée minimum ne peut pas être supérieure à la durée maximum');
                return false;
            }

            return true;
        }

        // Fermer le modal si on clique en dehors
        window.onclick = function(event) {
            const modal = document.getElementById('recommendationModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>
