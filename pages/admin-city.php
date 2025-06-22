<?php
session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: admin-login.php');
    exit();
}

require_once '../config/database.php';

// Traitement de l'ajout/modification/suppression (similaire à admin-users.php)
// ... (Votre logique PHP actuelle pour les villes devrait aller ici)

// Gérer les messages de succès/erreur après redirection
if (isset($_GET['message'])) {
    if ($_GET['message'] == 'deleted') {
        $success = "Ville supprimée avec succès.";
    }
}

// Placeholder for database interaction - YOU NEED TO IMPLEMENT ACTUAL DB LOGIC
// Example: Fetch cities from DB, handle add/edit/delete operations
// For demonstration, using dummy data
$cities = [
    ['id' => 5, 'name' => 'Fès', 'description' => 'Capitale spirituelle du Maroc, connue pour sa médina médiévale et ses tanneries traditionnelles.', 'hero_images' => 'uploads/lieux/hero/fes1.jpg,uploads/lieux/hero/fes2.jpg', 'main_photo_url' => 'uploads/lieux/hero/fes_main.jpg'],
    ['id' => 6, 'name' => 'Casablanca', 'description' => 'La capitale économique, mélange d'architecture moderne et d'héritage colonial, célèbre pour sa mosquée Hassan II.', 'hero_images' => 'uploads/lieux/hero/casa1.jpg', 'main_photo_url' => 'uploads/lieux/hero/casa_main.jpg'],
    ['id' => 7, 'name' => 'Chefchaouen', 'description' => 'La perle bleue du Maroc, réputée pour ses ruelles peintes en bleu et son ambiance paisible.', 'hero_images' => 'uploads/lieux/hero/chefchaouen1.jpg,uploads/lieux/hero/chefchaouen2.jpg', 'main_photo_url' => 'uploads/lieux/hero/chefchaouen_main.jpg'],
    ['id' => 8, 'name' => 'Essaouira', 'description' => 'Ville côtière au charme authentique, connue pour sa médina, ses remparts et ses plages.', 'hero_images' => 'uploads/lieux/hero/essaouira1.jpg', 'main_photo_url' => 'uploads/lieux/hero/essaouira_main.jpg'],
];

// Gérer l'édition
$editCity = null;
if (isset($_GET['edit'])) {
    $editId = $_GET['edit'];
    // Dans un vrai scénario, vous récupéreriez la ville de la BDD
    foreach ($cities as $city) {
        if ($city['id'] == $editId) {
            $editCity = $city;
            break;
        }
    }
    $editMode = true;
} else {
    $editMode = false;
}

// Gérer la suppression (exemple simplifié)
if (isset($_GET['delete'])) {
    $deleteId = $_GET['delete'];
    // Logique de suppression de la BDD ici
    // $stmt = $pdo->prepare("DELETE FROM cities WHERE id = ?");
    // $stmt->execute([$deleteId]);
    $success = "Ville supprimée avec succès (exemple)";
    // Redirection pour éviter la re-soumission
    header('Location: admin-city.php?message=deleted');
    exit();
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Admin - Gestion des villes</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/admin-style.css">
</head>
<body>
    <header class="header">
        <div class="container header-container">
            <a href="../index.php" class="logo">
                <img src="https://i.postimg.cc/g07GgLp5/VMaroc-logo-trf.png" alt="Maroc Authentique" class="logo-img">
            </a>
            <ul class="nav-menu">
                <li><a href="../index.php" class="nav-link">Accueil</a></li>
                <li><a href="../destinations.php" class="nav-link">Destinations</a></li>
                <li><a href="../recommandations.php" class="nav-link">Recommandations</a></li>
            </ul>
            <div class="header-actions">
                <a href="admin-panel.php" class="btn btn-outline">Panel Admin</a>
                <a href="../logout.php" class="btn btn-primary">Déconnexion</a>
            </div>
        </div>
    </header>
    <main class="main-content">
    <div class="container">
        <?php if (isset($success)): ?>
            <div class="notification success">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="notification error">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        <div class="page-header">
            <div class="page-title">
                <h1>Gestion des villes <span class="city-count">(<?php echo count($cities); ?>)</span></h1>
            </div>
            <div class="search-box">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="searchCityInput" placeholder="Rechercher une ville..." class="search-input">
            </div>
        </div>
        
        <div class="form-card">
            <div class="form-header">
                <h2>Ajouter une ville</h2>
            </div>
            <form action="" method="POST" enctype="multipart/form-data" class="city-form">
                <input type="hidden" name="action" value="add">
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Nom de la ville *</label>
                        <input type="text" id="name" name="name" value="" required>
                    </div>
                    <div class="form-group">
                        <label for="main_photo_url">URL ou nom du fichier photo</label>
                        <input type="text" id="main_photo_url" name="main_photo_url" placeholder="ex: marrakech.jpg" value="">
                    </div>
                </div>
                <div class="form-group">
                    <label for="description">Description *</label>
                    <textarea id="description" name="description" rows="4" required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="hero_images_upload">Images pour le Hero Slider (plusieurs fichiers possibles):</label>
                    <div class="file-upload-area" id="heroImagesUploadArea">
                        <i class="fas fa-cloud-upload-alt upload-icon"></i>
                        <div class="upload-content">
                            <p>Glissez et déposez vos images ici, ou <span class="upload-link">cliquez pour sélectionner</span></p>
                        </div>
                        <input type="file" id="hero_images_upload" name="hero_images_upload[]" multiple accept="image/*" style="display: none;">
                    </div>
                    <div class="image-preview" id="heroImagesPreview">
                        <?php
                        if (isset($editCity) && !empty($editCity['hero_images'])) {
                            $hero_images = explode(',', $editCity['hero_images']);
                            foreach ($hero_images as $image_url) {
                                if (!empty($image_url)) {
                                    echo '<div class="preview-item">';
                                    echo '<img src="../' . htmlspecialchars($image_url) . '" alt="Hero Image">';
                                    echo '<button type="button" class="preview-remove" data-image="' . htmlspecialchars($image_url) . '"><i class="fas fa-times"></i></button>';
                                    echo '</div>';
                                }
                            }
                        }
                        ?>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Ajouter le lieu</button>
                </div>
            </form>
        </div>
        
        <div class="list-card">
            <div class="list-header">
                <h2>Liste des villes</h2>
                <div class="filter-bar">
                    <div class="filter-dropdown">
                        <select>
                            <option value="">Toutes les villes</option>
                            <!-- Populate with actual city names from DB -->
                        </select>
                    </div>
                </div>
            </div>
            <div style="overflow-x:auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ACTION</th>
                            <th>ID</th>
                            <th>NOM</th>
                            <th>VILLE</th>
                            <th>CATÉGORIE</th>
                            <th>DESCRIPTION</th>
                            <th>IMAGES</th>
                            <th>URL ACTIVITÉ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($cities)): ?>
                            <tr><td colspan="8" class="text-center">Aucune ville enregistrée.</td></tr>
                        <?php else: ?>
                            <?php foreach ($cities as $city): ?>
                                <tr>
                                    <td>
                                        <a href="?edit=<?= $city['id'] ?>" class="btn btn-outline btn-sm">Modifier</a>
                                        <a href="?delete=<?= $city['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Supprimer cette ville ?')">Supprimer</a>
                                    </td>
                                    <td><?= htmlspecialchars($city['id']) ?></td>
                                    <td><?= htmlspecialchars($city['name']) ?></td>
                                    <td>Fès</td> <!-- Placeholder, replace with actual city from DB if available -->
                                    <td>hotels</td> <!-- Placeholder, replace with actual category from DB if available -->
                                    <td class="col-description"><?= htmlspecialchars($city['description']) ?></td>
                                    <td>
                                        <?php
                                        if (!empty($city['hero_images'])) {
                                            $hero_images_array = explode(',', $city['hero_images']);
                                            foreach ($hero_images_array as $image_path) {
                                                if (!empty($image_path)) {
                                                    echo '<img src="../' . htmlspecialchars($image_path) . '" alt="Hero Image" class="city-image-thumbnail">';
                                                }
                                            }
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </td>
                                    <td>N/A</td> <!-- Placeholder for URL Activité -->
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <footer>
        <div class="container">
            <p>© 2025 Maroc Authentique. Tous droits réservés.</p>
        </div>
    </footer>
</main>
<script>
    // JavaScript for drag-and-drop image upload and preview
    const heroImagesUploadArea = document.getElementById('heroImagesUploadArea');
    const heroImagesInput = document.getElementById('hero_images_upload');
    const heroImagesPreview = document.getElementById('heroImagesPreview');

    // Prevent default drag behaviors
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        heroImagesUploadArea.addEventListener(eventName, preventDefaults, false);
        document.body.addEventListener(eventName, preventDefaults, false); // For preventing outside drops
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    // Highlight drop area when dragging over
    ['dragenter', 'dragover'].forEach(eventName => {
        heroImagesUploadArea.addEventListener(eventName, () => heroImagesUploadArea.classList.add('dragover'), false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        heroImagesUploadArea.addEventListener(eventName, () => heroImagesUploadArea.classList.remove('dragover'), false);
    });

    // Handle dropped files
    heroImagesUploadArea.addEventListener('drop', handleDrop, false);

    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        heroImagesInput.files = files; // Assign files to the file input
        previewFiles(files);
    }

    // Handle files selected via click
    heroImagesUploadArea.addEventListener('click', () => heroImagesInput.click());
    heroImagesInput.addEventListener('change', (e) => previewFiles(e.target.files));

    function previewFiles(files) {
        heroImagesPreview.innerHTML = ''; // Clear existing previews first (if you want to replace all)
        // If you want to append, you'd modify this line and manage existing files in a more complex way.

        Array.from(files).forEach(file => {
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const div = document.createElement('div');
                    div.classList.add('preview-item');
                    div.innerHTML = `
                        <img src="${e.target.result}" alt="Image Preview">
                        <button type="button" class="preview-remove"><i class="fas fa-times"></i></button>
                    `;
                    heroImagesPreview.appendChild(div);

                    div.querySelector('.preview-remove').addEventListener('click', () => {
                        div.remove();
                        // Note: Removing from visual preview does not remove from the FileList object attached to the input.
                        // For a full client-side file management, you'd need to create a new DataTransfer object
                        // or manage files in an array and then assign back to input.
                        // For server-side handling, the form submission will only send currently selected files.
                    });
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // Handle removal of existing hero images (for edit mode)
    document.querySelectorAll('#heroImagesPreview .preview-remove[data-image]').forEach(button => {
        button.addEventListener('click', function() {
            const imageUrlToRemove = this.dataset.image;
            const cityId = <?= isset($editCity) ? htmlspecialchars($editCity['id']) : 'null' ?>; // Get the current city ID

            if (confirm('Êtes-vous sûr de vouloir supprimer cette image?')) {
                // Construct URL for deletion
                const deleteUrl = `admin-city.php?delete_hero_image=1&city_id=${cityId}&image_path=${encodeURIComponent(imageUrlToRemove)}`;
                window.location.href = deleteUrl; // Redirect to trigger server-side deletion
            }
        });
    });

    // Search functionality for cities grid
    document.getElementById('searchCityInput').addEventListener('keyup', function() {
        const searchValue = this.value.toLowerCase();
        document.querySelectorAll('.admin-table tbody tr').forEach(row => {
            const textContent = row.textContent.toLowerCase();
            if (textContent.includes(searchValue)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
</script>
</body>
</html> 