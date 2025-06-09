<?php
// --- FORCE ERROR REPORTING --- //
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// --- END FORCE ERROR REPORTING --- //

session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: admin-login.php');
    exit();
}

$pdo = new PDO('mysql:host=localhost;dbname=vmaroc;charset=utf8', 'root', '');

// Feedback messages
$success = $error = '';

// Display session messages
if (isset($_SESSION['admin_message'])) {
    $message = $_SESSION['admin_message'];
    $message_type = $_SESSION['admin_message_type'] ?? 'info';
    unset($_SESSION['admin_message']);
    unset($_SESSION['admin_message_type']);
    // Echo the message later in the HTML body
}

// Initialisation des variables pour l'édition
$editMode = false;
$editCity = [
    'id' => '',
    'nom' => '',
    'photo' => '',
    'description' => '',
    'hero_images' => ''
];

// Ajout d'une ville
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['edit_id']) && $_POST['edit_id'] !== '') {
        // Modification
        $id = intval($_POST['edit_id']);
        $nom = trim($_POST['nom']);
        $photo = trim($_POST['photo']);
        $desc = trim($_POST['description']);
        
        // --- Refined Hero Images Upload Handling for Edit ---
        $uploaded_hero_images = []; // Array to store paths of newly uploaded hero images
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $uploadHeroDir = '../uploads/cities/hero/'; // Directory for city hero images (relative to admin-cities.php)
        $webAccessibleUploadDir = 'uploads/cities/hero/'; // Web accessible path (relative to root)
        
        // Check if files were uploaded for hero_images_upload and if the array structure is as expected
        if (isset($_FILES['hero_images_upload']) && is_array($_FILES['hero_images_upload']['name'])) {
             // Filter out empty file inputs (when user doesn't select a file)
            $valid_files_keys = array_filter(array_keys($_FILES['hero_images_upload']['name']), function($key) {
                return !empty($_FILES['hero_images_upload']['name'][$key]);
            });

            if (!empty($valid_files_keys)) {
                // S'assurer que le dossier existe
                if (!file_exists($uploadHeroDir)) {
                    mkdir($uploadHeroDir, 0777, true);
                }
                // We don't exit here, as upload might still work depending on server config

                foreach ($valid_files_keys as $key) {
                     // Check for upload errors for the current file
                    if ($_FILES['hero_images_upload']['error'][$key] === UPLOAD_ERR_OK) {
                        $filename = $_FILES['hero_images_upload']['name'][$key];
                        $tempPath = $_FILES['hero_images_upload']['tmp_name'][$key];
                        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION)); // Use PATHINFO_EXTENSION for compatibility

                        if (in_array($ext, $allowed)) {
                            $new_filename = uniqid('city_hero_', true) . '.' . $ext;
                            $targetPath = $uploadHeroDir . $new_filename;
                            $webAccessiblePath = $webAccessibleUploadDir . $new_filename; // Store web accessible path

                            // --- Attempt to move the uploaded file ---
                            if (move_uploaded_file($tempPath, $targetPath)) {
                                $uploaded_hero_images[] = $webAccessiblePath; // Save web accessible path
                                $_SESSION['admin_message'] = "Les images ont été téléchargées avec succès.";
                                $_SESSION['admin_message_type'] = 'success';
                            } else {
                                error_log("[PHP Error] Failed to move uploaded file: " . $tempPath . " to " . $targetPath . " (Error Code: " . $_FILES['hero_images_upload']['error'][$key] . ")");
                                $_SESSION['admin_message'] = "Erreur lors du déplacement du fichier hero " . htmlspecialchars($filename) . ". Code d'erreur PHP: " . $_FILES['hero_images_upload']['error'][$key];
                                $_SESSION['admin_message_type'] = 'error';
                            }
                        } else {
                            $_SESSION['admin_message'] = "Type de fichier non autorisé pour " . htmlspecialchars($filename) . ". Seuls " . implode(', ', $allowed) . " sont autorisés.";
                            $_SESSION['admin_message_type'] = 'error';
                        }
                    } elseif ($_FILES['hero_images_upload']['error'][$key] !== UPLOAD_ERR_NO_FILE) {
                         // Report other upload errors except NO_FILE for selected files
                         $_SESSION['admin_message'] = "Erreur lors de l'upload du fichier " . htmlspecialchars($name) . " : Code d'erreur PHP " . $_FILES['hero_images_upload']['error'][$key] . ".";
                         $_SESSION['admin_message_type'] = 'error';
                    }
                }
                 // Optional: Add a message if user selected files but none were successfully uploaded
                if (empty($uploaded_hero_images) && !empty($valid_files_keys)) {
                     $_SESSION['admin_message'] = "Aucun des fichiers sélectionnés pour le hero n'a pu être téléchargé lors de la modification. Vérifiez les erreurs ci-dessus et les permissions du dossier d'upload.";
                     $_SESSION['admin_message_type'] = 'error';
                }
            }
        }
        // --- End Refined Hero Images Upload Handling for Edit ---

        // Determine the final hero_images string to save
        $hero_images_string = '';
        if (!empty($uploaded_hero_images)) {
            // If new images were successfully uploaded, use their paths
            $hero_images_string = implode(',', $uploaded_hero_images);
        } else {
            // If no new images were uploaded, keep the existing ones from the database
            // Fetch the current hero_images value from the database for this city
            $stmt = $pdo->prepare("SELECT hero_images FROM villes WHERE id = ?");
            $stmt->execute([$id]);
            $existing_hero_images = $stmt->fetchColumn();
            $hero_images_string = $existing_hero_images; // This will be the value from the database (can be null or empty)
        }

        // --- DEBUG: Inspect hero_images_string before UPDATE (Edit) ---
        error_log("[PHP Debug - EDIT] Final hero_images_string: " . ($hero_images_string ?? 'NULL'));
        // --- End DEBUG ---

        if ($nom && $desc) {
            try {
                $pdo->beginTransaction();
                
                // --- MODIFIED: Update query to include hero_images ---
                $stmt = $pdo->prepare("UPDATE villes SET nom = ?, photo = ?, description = ?, hero_images = ? WHERE id = ?");
                $stmt->execute([$nom, $photo, $desc, $hero_images_string, $id]);
                
                $pdo->commit();
                // --- MODIFIED: Success message for Edit ---
                $_SESSION['admin_message'] = "Ville '" . htmlspecialchars($nom) . "' modifiée avec succès !";
                $_SESSION['admin_message_type'] = 'success';
                // Redirect to prevent multiple submissions and show message
                header('Location: admin-cities.php');
                exit();
            } catch (Exception $e) {
                $pdo->rollBack();
                $_SESSION['admin_message'] = "Erreur lors de la modification de la ville : " . $e->getMessage();
                $_SESSION['admin_message_type'] = 'error';
                // Redirect to show error message and keep edit mode
                header('Location: admin-cities.php?edit=' . $id);
                exit();
            }
        } else {
            $_SESSION['admin_message'] = "Veuillez remplir tous les champs obligatoires pour modifier une ville.";
            $_SESSION['admin_message_type'] = 'error';
            // Redirect to show error message and keep edit mode
            header('Location: admin-cities.php?edit=' . $id);
            exit();
        }
    } elseif (isset($_POST['nom']) && !isset($_POST['edit_id'])) { // Ensure it's an Add operation
        // Ajout
        $nom = trim($_POST['nom']);
        $photo = trim($_POST['photo']); // Keep this for the main city photo field if you still use it
        $desc = trim($_POST['description']);
        // Assuming categories are selected via checkboxes or similar and sent as an array
        $categories = isset($_POST['categories']) ? (is_array($_POST['categories']) ? $_POST['categories'] : []) : [];
        
        // --- Refined Hero Images Upload Handling for Add ---
        $uploaded_hero_images = [];
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $uploadHeroDir = '../uploads/cities/hero/'; // Directory for city hero images (relative to admin-cities.php)
        $webAccessibleUploadDir = 'uploads/cities/hero/'; // Web accessible path (relative to root)
        
         // Check if the file input is set and is not empty
        if (isset($_FILES['hero_images_upload']) && is_array($_FILES['hero_images_upload']['name'])) {
             // Filter out empty file inputs (when user doesn't select a file)
            $valid_files_keys = array_filter(array_keys($_FILES['hero_images_upload']['name']), function($key) {
                return !empty($_FILES['hero_images_upload']['name'][$key]);
            });

            if (!empty($valid_files_keys)) {
                
                // Attempt to create directory if it doesn't exist and set permissions
                if (!is_dir($uploadHeroDir)) {
                    if (!mkdir($uploadHeroDir, 0775, true)) {
                        $_SESSION['admin_message'] = "Erreur critique: Impossible de créer le dossier d'upload pour les images hero ('" . htmlspecialchars($uploadHeroDir) . "') lors de l'ajout. Vérifiez les permissions du serveur.";
                        $_SESSION['admin_message_type'] = 'error';
                        // Stop processing if directory cannot be created
                        header('Location: admin-cities.php');
                        exit();
                    }
                }
                // Attempt to set permissions even if directory existed
                 // Use @ to suppress warnings if permissions cannot be set (e.g., on certain OS/configurations)
                @chmod($uploadHeroDir, 0775);
                 // We don't exit here, as upload might still work depending on server config

                foreach ($valid_files_keys as $key) {
                 // Check if this specific file input had a file selected AND there were no upload errors
                if (!empty($name) && $_FILES['hero_images_upload']['error'][$key] === UPLOAD_ERR_OK) {
                        $filename = $_FILES['hero_images_upload']['name'][$key];
                        $tempPath = $_FILES['hero_images_upload']['tmp_name'][$key];
                        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION)); // Use PATHINFO_EXTENSION for compatibility

                        if (in_array($ext, $allowed)) {
                            $new_filename = uniqid('city_hero_', true) . '.' . $ext;
                            $targetPath = $uploadHeroDir . $new_filename;
                            $webAccessiblePath = $webAccessibleUploadDir . $new_filename; // Store web accessible path

                            // --- Attempt to move the uploaded file ---
                            if (move_uploaded_file($tempPath, $targetPath)) {
                                $uploaded_hero_images[] = $webAccessiblePath; // Save web accessible path
                            } else {
                               // Log a more detailed error if moving fails
                                error_log("[PHP Error] Failed to move uploaded file: " . $tempPath . " to " . $targetPath . " (Error Code: " . $_FILES['hero_images_upload']['error'][$key] . ")");
                                $_SESSION['admin_message'] = "Erreur lors du déplacement du fichier hero " . htmlspecialchars($filename) . " lors de l'ajout. Code d'erreur PHP: " . $_FILES['hero_images_upload']['error'][$key];
                                $_SESSION['admin_message_type'] = 'error';
                            }
                        } else {
                            $_SESSION['admin_message'] = "Type de fichier non autorisé pour " . htmlspecialchars($filename) . " lors de l'ajout. Seuls " . implode(', ', $allowed) . " sont autorisés.";
                            $_SESSION['admin_message_type'] = 'error';
                        }
                    } elseif ($_FILES['hero_images_upload']['error'][$key] !== UPLOAD_ERR_NO_FILE) {
                         // Report other upload errors except NO_FILE for selected files
                         $_SESSION['admin_message'] = "Erreur lors de l'upload du fichier " . htmlspecialchars($name) . " lors de l'ajout : Code d'erreur PHP " . $_FILES['hero_images_upload']['error'][$key] . ".";
                         $_SESSION['admin_message_type'] = 'error';
                    }
                }
            }
             // Optional: Add a message if user selected files but none were successfully uploaded
            if (empty($uploaded_hero_images) && !empty($valid_files_keys)) {
                 $_SESSION['admin_message'] = "Aucun des fichiers sélectionnés pour le hero n'a pu être téléchargé lors de l'ajout. Vérifiez les erreurs ci-dessus et les permissions du dossier d'upload.";
                 $_SESSION['admin_message_type'] = 'error';
            }
        }
        // --- End Refined Hero Images Upload Handling for Add ---

        $hero_images_string = implode(',', $uploaded_hero_images);

        // --- DEBUG: Inspect hero_images_string before INSERT (Add) ---
        error_log("[PHP Debug - ADD] Final hero_images_string: " . ($hero_images_string ?? 'NULL'));
        // --- End DEBUG ---

        if ($nom && $desc) {
            try {
                $pdo->beginTransaction();
                
                // Insérer la ville avec hero_images
                // --- MODIFIED: Insert query to include hero_images ---
                $stmt = $pdo->prepare("INSERT INTO villes (nom, photo, description, hero_images) VALUES (?, ?, ?, ?)");
                $stmt->execute([$nom, $photo, $desc, $hero_images_string]);
                $ville_id = $pdo->lastInsertId();
                
                // Ajouter des recommandations par défaut pour chaque catégorie sélectionnée
                // Note: This part seems to use the single $photo field for recommendation images. Adjust if needed.
                if (!empty($categories)) {
                    $recommandationsDefaut = [
                        'culture' => [
                            'titre' => 'Visite culturelle de ' . $nom,
                            'description' => 'Découvrez les sites culturels et historiques de ' . $nom,
                            'prix_min' => 500,
                            'prix_max' => 1200,
                            'duree_min' => 1,
                            'duree_max' => 2,
                            'image_url' => $photo
                        ],
                        'nature' => [
                            'titre' => 'Exploration nature à ' . $nom,
                            'description' => 'Randonnée et découverte des paysages naturels de ' . $nom,
                            'prix_min' => 600,
                            'prix_max' => 1500,
                            'duree_min' => 1,
                            'duree_max' => 2,
                            'image_url' => $photo
                        ],
                        'gastronomie' => [
                            'titre' => 'Découverte gastronomique de ' . $nom,
                            'description' => 'Dégustez les spécialités culinaires locales de ' . $nom,
                            'prix_min' => 700,
                            'prix_max' => 1400,
                            'duree_min' => 1,
                            'duree_max' => 1,
                            'image_url' => $photo
                        ],
                        'shopping' => [
                            'titre' => 'Shopping à ' . $nom,
                            'description' => 'Découvrez les marchés et boutiques d\'artisanat de ' . $nom,
                            'prix_min' => 400,
                            'prix_max' => 1000,
                            'duree_min' => 1,
                            'duree_max' => 1,
                            'image_url' => $photo
                        ],
                        'plage' => [
                            'titre' => 'Détente et relaxation à ' . $nom,
                            'description' => 'Profitez des espaces de détente et de bien-être de ' . $nom,
                            'prix_min' => 800,
                            'prix_max' => 1800,
                            'duree_min' => 1,
                            'duree_max' => 1,
                            'image_url' => $photo
                        ],
                        'famille' => [
                            'titre' => 'Activités familiales à ' . $nom,
                            'description' => 'Découvrez les activités adaptées aux familles à ' . $nom,
                            'prix_min' => 600,
                            'prix_max' => 1500,
                            'duree_min' => 1,
                            'duree_max' => 2,
                            'image_url' => $photo
                        ]
                    ];
                    
                    foreach ($categories as $categorie) {
                        if (isset($recommandationsDefaut[$categorie])) {
                            $rec = $recommandationsDefaut[$categorie];
                            $stmt = $pdo->prepare("INSERT INTO recommandations (ville_id, titre, description, categorie, prix_min, prix_max, duree_min, duree_max, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                            $stmt->execute([
                                $ville_id,
                                $rec['titre'],
                                $rec['description'],
                                $categorie,
                                $rec['prix_min'],
                                $rec['prix_max'],
                                $rec['duree_min'],
                                $rec['duree_max'],
                                $rec['image_url'] // This uses $photo, consider using a hero image path
                            ]);
                        }
                    }
                }
                
                $pdo->commit();
                // --- MODIFIED: Success message for Add ---
                $_SESSION['admin_message'] = "Ville '" . htmlspecialchars($nom) . "' ajoutée avec succès !";
                $_SESSION['admin_message_type'] = 'success';
                // Rediriger pour éviter la soumission multiple et afficher le message
                header('Location: admin-cities.php');
                exit();
            } catch (Exception $e) {
                $pdo->rollBack();
                $_SESSION['admin_message'] = "Erreur lors de l'ajout de la ville : " . $e->getMessage();
                $_SESSION['admin_message_type'] = 'error';
                // Redirect to show error message
                header('Location: admin-cities.php');
                exit();
            }
        } else {
            $_SESSION['admin_message'] = "Veuillez remplir tous les champs obligatoires pour ajouter une ville.";
            $_SESSION['admin_message_type'] = 'error';
            // Redirect to show error message
            header('Location: admin-cities.php');
            exit();
        }
    }
}

// Suppression d'une ville
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    // --- MODIFIED: Add try-catch and session messages for Delete and consider file deletion---
    try {
        $pdo->beginTransaction();
        // Optional: Delete associated hero image files when deleting a city
        $stmt = $pdo->prepare("SELECT hero_images FROM villes WHERE id = ?");
        $stmt->execute([$id]);
        $images_string = $stmt->fetchColumn();
        if (!empty($images_string)) {
            $images = explode(',', $images_string);
            foreach ($images as $img) {
                $filepath = '../' . trim($img); // Adjust path and trim whitespace
                // Check if file exists before attempting deletion
                if (file_exists($filepath) && is_file($filepath)) {
                    if (unlink($filepath)) {
                         // Log successful file deletion (optional)
                         error_log("[PHP Info] Deleted city hero image file: " . $filepath);
                    } else {
                        // Log file deletion failure (optional)
                        error_log("[PHP Warning] Failed to delete city hero image file: " . $filepath);
                         $_SESSION['admin_message'] = "Avertissement: Impossible de supprimer le fichier image hero ('" . htmlspecialchars($img) . "') pour la ville ID " . $id . ". Vérifiez les permissions du fichier.";
                         $_SESSION['admin_message_type'] = 'warning';
                    }
                } else if (!empty($img)) {
                     // Log if file was expected but not found (optional)
                     error_log("[PHP Warning] City hero image file not found for deletion: " . $filepath . " for city ID " . $id);
                }
            }
        }

        $pdo->prepare("DELETE FROM villes WHERE id = ?")->execute([$id]);
        $pdo->commit();
        $_SESSION['admin_message'] = "Ville supprimée avec succès !";
        $_SESSION['admin_message_type'] = 'success';
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['admin_message'] = "Erreur lors de la suppression de la ville : " . $e->getMessage();
        $_SESSION['admin_message_type'] = 'error';
    }
    // --- END MODIFIED: try-catch and session messages for Delete and consider file deletion---
    header('Location: admin-cities.php');
    exit();
}

// Suppression d'une image hero individuelle
if ((isset($_GET['delete_hero_image']) && $_GET['delete_hero_image'] == 1) && isset($_GET['city_id']) && isset($_GET['image_path'])) {
    $city_id = intval($_GET['city_id']);
    $image_path = urldecode($_GET['image_path']);
    
    // Récupérer les images hero actuelles
    $stmt = $pdo->prepare("SELECT hero_images FROM villes WHERE id = ?");
    $stmt->execute([$city_id]);
    $hero_images_string = $stmt->fetchColumn();
    
    if ($hero_images_string) {
        $hero_images = array_map('trim', explode(',', $hero_images_string));
        
        // Retirer l'image spécifiée du tableau
        $hero_images = array_filter($hero_images, function($img) use ($image_path) {
            return trim($img) !== trim($image_path);
        });
        
        // Mettre à jour la base de données avec la nouvelle liste d'images
        $new_hero_images = implode(',', $hero_images);
        $stmt = $pdo->prepare("UPDATE villes SET hero_images = ? WHERE id = ?");
        $stmt->execute([$new_hero_images, $city_id]);
        
        // Supprimer physiquement le fichier (optionnel)
        $file_path = '../' . $image_path;
        if (file_exists($file_path)) {
            @unlink($file_path);
        }
        
        $_SESSION['admin_message'] = "L'image a été supprimée avec succès.";
        $_SESSION['admin_message_type'] = 'success';
    }
    
    // Rediriger vers la page d'édition
    header('Location: admin-cities.php?edit=' . $city_id);
    exit();
}

// Préparation de l'édition
if (isset($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM villes WHERE id = ?");
    $stmt->execute([$editId]);
    $editCity = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($editCity) {
        $editMode = true;
    }
}

// Liste des villes
$cities = $pdo->query("SELECT id, nom, photo, description, hero_images FROM villes")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Admin - Gestion des villes</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/montserrat-font.css">
    <link rel="stylesheet" href="../css/main.css">
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
    <main class="admin-section" style="margin-top:100px;">
    <div class="container">
            <div class="section-title" style="display:flex;align-items:center;justify-content:space-between;gap:20px;flex-wrap:wrap;">
                <h2>Gestion des Villes <span id="cityCount" style="font-size:1rem;font-weight:400;color:var(--secondary-color);">(<?php echo count($cities); ?>)</span></h2>
                <input type="text" id="searchCityInput" placeholder="Rechercher une ville..." style="padding:10px 16px;border:1px solid var(--border-color);border-radius:6px;min-width:220px;">
            </div>
            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            <div class="form" style="max-width:900px;margin:0 auto 40px auto;">
                <h3 style="text-align:center;">
                    <?php echo $editMode ? 'Modifier la ville' : 'Ajouter une ville'; ?>
                </h3>
                <form method="post" class="admin-form-flex" enctype="multipart/form-data">
                    <?php if ($editMode): ?>
                        <input type="hidden" name="edit_id" value="<?php echo htmlspecialchars($editCity['id']); ?>">
                    <?php endif; ?>
                    <div class="form-group">
                        <input type="text" name="nom" class="form-control" placeholder="Nom de la ville *" required value="<?php echo htmlspecialchars($editCity['nom']); ?>">
                    </div>
                    <div class="form-group">
                        <input type="text" name="photo" class="form-control" placeholder="URL ou nom du fichier photo (ex: marrakech.jpg)" value="<?php echo htmlspecialchars($editCity['photo']); ?>">
                    </div>
                    <div class="form-group">
                        <input type="text" name="description" class="form-control" placeholder="Description *" required value="<?php echo htmlspecialchars($editCity['description']); ?>">
                    </div>
                    <div class="form-group" style="flex-basis: 100%;">
                        <label for="hero_images_upload">Images pour le Hero Slider (plusieurs fichiers possibles) :</label>
                        <input type="file" name="hero_images_upload[]" id="hero_images_upload" accept="image/*" multiple>
                        <?php if ($editMode && !empty($editCity['hero_images'])): ?>
                            <div style="margin-top: 15px; font-size: 0.9em; color: #555;">
                                <p style="margin-bottom: 10px;"><strong>Images actuelles :</strong></p>
                                <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                                <?php
                                $current_hero_images = array_map('trim', explode(',', $editCity['hero_images']));
                                foreach ($current_hero_images as $img_path) {
                                    if (!empty($img_path)) {
                                        echo '<div style="position: relative; width: 80px;">';
                                        echo '<img src="../' . htmlspecialchars($img_path) . '" alt="Hero Image" style="width: 80px; height: 80px; object-fit: cover; border-radius: 5px;">';
                                        echo '<a href="?delete_hero_image=1&city_id=' . $editCity['id'] . '&image_path=' . urlencode($img_path) . '" onclick="return confirm(\'Supprimer cette image ?\')" style="position: absolute; top: -5px; right: -5px; background: #dc3545; color: white; border-radius: 50%; width: 22px; height: 22px; display: flex; align-items: center; justify-content: center; text-decoration: none; font-size: 12px;"><i class="fas fa-times"></i></a>';
                                        echo '</div>';
                                    }
                                }
                                ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div style="display:flex;gap:8px;align-items:center;">
                        <button type="submit" class="btn-solid" style="min-width:120px;">
                            <?php echo $editMode ? 'Enregistrer' : 'Ajouter'; ?>
                        </button>
                        <?php if ($editMode): ?>
                            <a href="admin-cities.php" class="btn-outline" style="min-width:100px;">Annuler</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            <div class="section-title">
                <h3>Liste des villes</h3>
            </div>
            <div style="overflow-x:auto;">
                <table class="admin-table" id="citiesTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Hero Photos</th>
                            <th>Description</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($cities)): ?>
                            <tr><td colspan="5" style="text-align:center;color:var(--secondary-color);">Aucune ville enregistrée.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($cities as $city): ?>
                        <tr>
                            <td data-label="ID"><?php echo $city['id']; ?></td>
                            <td data-label="Nom"><?php echo htmlspecialchars($city['nom']); ?></td>
                            <td data-label="Hero Photos">
                                <?php
                                $hero_images = [];
                                if (!empty($city['hero_images'])) {
                                    $hero_images = array_map('trim', explode(',', $city['hero_images']));
                                }
                                if (!empty($hero_images)) {
                                    foreach ($hero_images as $img_path) {
                                        if (!empty($img_path)) {
                                            echo '<img src="../' . htmlspecialchars($img_path) . '" alt="Hero Image" style="width: 40px; height: 40px; object-fit: cover; margin-right: 5px; border-radius: 4px;">';
                                        }
                                    }
                                } else {
                                    echo '<span style="color:var(--secondary-color);font-size:0.9em;">Aucune</span>';
                                }
                                ?>
                            </td>
                            <td data-label="Description"><?php echo htmlspecialchars($city['description']); ?></td>
                            <td data-label="Action">
                                <div class="admin-actions">
                                    <a href="?edit=<?php echo $city['id']; ?>" class="btn-outline" style="padding:4px 12px;font-size:0.9rem;">Modifier</a>
                                    <a href="?delete=<?php echo $city['id']; ?>" class="btn-delete" style="padding:4px 12px;font-size:0.9rem;" onclick="return confirm('Supprimer cette ville ?')">Supprimer</a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html> 