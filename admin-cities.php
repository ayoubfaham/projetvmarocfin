<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Admin - Gestion des villes</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
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
        <?php if (isset($_SESSION['admin_message'])): ?>
            <div class="notification <?= $_SESSION['admin_message_type'] === 'success' ? 'success' : 'error' ?>">
                <?= $_SESSION['admin_message'] ?>
            </div>
            <?php unset($_SESSION['admin_message']); unset($_SESSION['admin_message_type']); // Clear the message after displaying ?>
        <?php endif; ?>

        <div class="page-header">
            <div class="page-title">
                <h1>Gestion des Villes <span class="city-count">(<?php echo count($cities); ?>)</span></h1>
            </div>
            <div class="search-box">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="searchCityInput" placeholder="Rechercher une ville..." class="search-input">
            </div>
        </div>

        <div class="form-card">
            <div class="form-header">
                <h2><?= $editMode ? 'Modifier la ville' : 'Ajouter une ville' ?></h2>
            </div>
            <form method="post" class="city-form" enctype="multipart/form-data">
                <?php if ($editMode): ?>
                    <input type="hidden" name="edit_id" value="<?php echo htmlspecialchars($editCity['id']); ?>">
                <?php endif; ?>
                <div class="form-row">
                    <div class="form-group">
                        <label for="nom">Nom de la ville</label>
                        <input type="text" id="nom" name="nom" value="<?php echo htmlspecialchars($editCity['nom']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="photo">URL ou nom du fichier photo</label>
                        <input type="text" id="photo" name="photo" value="<?php echo htmlspecialchars($editCity['photo']); ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4" required><?php echo htmlspecialchars($editCity['description']); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="hero_images_upload">Images du hero slider (glisser-déposer ou cliquer)</label>
                    <div class="file-upload-area" id="fileUploadArea">
                        <i class="fas fa-cloud-upload-alt upload-icon"></i>
                        <div class="upload-content">
                            <p>Glissez et déposez vos images ici, ou <span class="upload-link">cliquez pour sélectionner</span></p>
                        </div>
                        <input type="file" id="hero_images_upload" name="hero_images_upload[]" multiple accept="image/*" style="display: none;">
                    </div>
                    <div class="image-preview" id="imagePreview">
                        <?php if ($editMode && !empty($editCity['hero_images'])): ?>
                            <?php
                            $current_hero_images = array_map('trim', explode(',', $editCity['hero_images']));
                            foreach ($current_hero_images as $img_path) {
                                if (!empty($img_path)) {
                                    echo '<div class="preview-item">';
                                    echo '<img src="../' . htmlspecialchars($img_path) . '" alt="Hero Image">';
                                    echo '<a href="?delete_hero_image=1&city_id=' . $editCity['id'] . '&image_path=' . urlencode($img_path) . '" class="preview-remove" onclick="return confirm(\'Supprimer cette image ?\')">';
                                    echo '<i class="fas fa-times"></i></a>';
                                    echo '</div>';
                                }
                            }
                            ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <?php echo $editMode ? 'Enregistrer les modifications' : 'Ajouter la ville'; ?>
                    </button>
                    <?php if ($editMode): ?>
                        <a href="admin-cities.php" class="btn btn-outline">Annuler</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="list-card" id="adminCitiesListCard">
            <div class="list-header">
                <h2>Liste des villes</h2>
            </div>
            <div style="overflow-x:auto;">
                <table class="admin-table" id="citiesTable">
                    <thead>
                        <tr>
                            <th>ACTION</th>
                            <th>ID</th>
                            <th>NOM</th>
                            <th>HERO IMAGES</th>
                            <th>DESCRIPTION</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($cities)): ?>
                            <tr><td colspan="5" class="text-center">Aucune ville enregistrée.</td></tr>
                        <?php else: ?>
                            <?php foreach ($cities as $city): ?>
                                <tr>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="?edit=<?php echo $city['id']; ?>" class="btn btn-outline btn-sm">Modifier</a>
                                            <a href="?delete=<?php echo $city['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Supprimer cette ville ?')">Supprimer</a>
                                        </div>
                                    </td>
                                    <td><?php echo $city['id']; ?></td>
                                    <td><?php echo htmlspecialchars($city['nom']); ?></td>
                                    <td>
                                        <div class="hero-images-container">
                                            <?php
                                            $hero_images = [];
                                            if (!empty($city['hero_images'])) {
                                                $hero_images = array_map('trim', explode(',', $city['hero_images']));
                                            }
                                            if (!empty($hero_images)) {
                                                foreach ($hero_images as $img_path) {
                                                    if (!empty($img_path)) {
                                                        echo '<img src="../' . htmlspecialchars($img_path) . '" alt="Hero Image" class="city-image-thumbnail">';
                                                    }
                                                }
                                            } else {
                                                echo '<span class="text-muted">Aucune</span>';
                                            }
                                            ?>
                                        </div>
                                    </td>
                                    <td class="col-description"><?php echo htmlspecialchars($city['description']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    </main>
    <footer>
        <div class="container">
            <p>© 2025 Maroc Authentique. Tous droits réservés.</p>
        </div>
    </footer>

    <script>
    // JavaScript pour la gestion des images et le glisser-déposer
    const fileUploadArea = document.getElementById('fileUploadArea');
    const fileInput = document.getElementById('hero_images_upload');
    const imagePreview = document.getElementById('imagePreview');

    // Empêcher le comportement par défaut de glisser-déposer
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        fileUploadArea.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    // Mettre en évidence la zone de dépôt lors du survol
    ['dragenter', 'dragover'].forEach(eventName => {
        fileUploadArea.addEventListener(eventName, () => fileUploadArea.classList.add('dragover'), false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        fileUploadArea.addEventListener(eventName, () => fileUploadArea.classList.remove('dragover'), false);
    });

    // Gérer les fichiers déposés
    fileUploadArea.addEventListener('drop', handleDrop, false);

    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        fileInput.files = files;
        updateImagePreview(files);
    }

    // Gérer le clic sur la zone de dépôt
    fileUploadArea.addEventListener('click', () => {
        fileInput.click();
    });

    // Gérer la sélection de fichiers via le dialogue
    fileInput.addEventListener('change', function() {
        updateImagePreview(this.files);
    });

    // Mettre à jour l'aperçu des images
    function updateImagePreview(files) {
        if (!files || files.length === 0) return;
        
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            if (!file.type.match('image.*')) continue;
            
            const reader = new FileReader();
            reader.onload = function(e) {
                const div = document.createElement('div');
                div.className = 'preview-item new-upload';
                div.innerHTML = `
                    <img src="${e.target.result}" alt="Image Preview">
                    <button type="button" class="preview-remove"><i class="fas fa-times"></i></button>
                `;
                imagePreview.appendChild(div);
                
                // Ajouter un gestionnaire d'événements pour le bouton de suppression
                div.querySelector('.preview-remove').addEventListener('click', function() {
                    div.remove();
                });
            };
            reader.readAsDataURL(file);
        }
    }

    // Recherche de villes
    document.getElementById('searchCityInput').addEventListener('keyup', function() {
        const searchValue = this.value.toLowerCase();
        const rows = document.querySelectorAll('#citiesTable tbody tr');
        
        rows.forEach(row => {
            const cityName = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
            const cityDesc = row.querySelector('td:nth-child(5)').textContent.toLowerCase();
            
            if (cityName.includes(searchValue) || cityDesc.includes(searchValue)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
    </script>
</body>
</html>