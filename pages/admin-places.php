<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: admin-login.php');
    exit();
}

$pdo = new PDO('mysql:host=localhost;dbname=vmaroc;charset=utf8', 'root', '');

// --- REMOVED: Database Connection Test Echos ---
// if ($pdo) {
//     echo '<div style="color: green; font-weight: bold;">Database connection successful!</div>';
// } else {
//     echo '<div style="color: red; font-weight: bold;">Database connection failed!</div>';
//     // Depending on the error, the script might stop here anyway
// }
// --- End REMOVED Database Connection Test Echos ---

// Ajout d'un lieu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nom']) && !isset($_POST['edit_id'])) {
    echo "Trace 6: Inside Add POST handler.<br>"; // Trace point 6
    // --- DEBUG: Inspect $_FILES array for Add ---
    error_log("[PHP Debug - ADD] FILES array: " . print_r($_FILES, true));
    var_dump($_FILES);
    // --- End DEBUG ---

    $nom = $_POST['nom'];
    $desc = $_POST['description'];
    $id_ville = intval($_POST['id_ville']);
    $categorie = $_POST['categorie'] ?? '';
    $url_activite = $_POST['url_activite'] ?? '';
    // Sécuriser la longueur de l'URL d'activité (évite l'erreur SQL 1406)
    $url_activite = mb_substr($url_activite, 0, 255);
    // --- REMOVED: Old photo variables ---
    // $photo = $_POST['photo'] ?? '';
    // $photo2 = $_POST['photo2'] ?? '';
    // $photo3 = $_POST['photo3'] ?? '';
    // $photo4 = $_POST['photo4'] ?? '';
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    
    // --- REMOVED: Old uploaded_photos array ---
    // $uploaded_photos = [];
    $uploaded_hero_images = []; // Array to store paths of uploaded hero images

    // --- Handle Hero Images Upload for Add ---
     // Check if files were uploaded and if the 'hero_images_upload' key exists and is an array
    if (isset($_FILES['hero_images_upload']['name']) && is_array($_FILES['hero_images_upload']['name'])) {
        // Filter out empty file inputs (when user doesn't select a file)
        $valid_files = array_filter($_FILES['hero_images_upload']['name'], function($name) { return !empty($name); });

        if (!empty($valid_files)) {
            $uploadHeroDir = '../uploads/lieux/hero/'; // Chemin physique pour l'upload
            $webAccessibleDir = 'uploads/lieux/hero/'; // Chemin relatif pour la base de données
            
            // S'assurer que le dossier existe
            if (!file_exists($uploadHeroDir)) {
                mkdir($uploadHeroDir, 0777, true);
            }
            
            // Déjà traité au-dessus

            foreach (array_keys($_FILES['hero_images_upload']['name']) as $key) {
                 // Check for upload errors for the current file
                if ($_FILES['hero_images_upload']['error'][$key] === UPLOAD_ERR_OK) {
                    $filename = $_FILES['hero_images_upload']['name'][$key];
                    $tempPath = $_FILES['hero_images_upload']['tmp_name'][$key];
                    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                    if (in_array($ext, $allowed)) {
                        $new_filename = uniqid('hero_', true) . '.' . $ext;
                        $targetPath = $uploadHeroDir . $new_filename;

                        if (move_uploaded_file($tempPath, $targetPath)) {
                            // Utiliser le chemin web accessible pour la base de données
                            $uploaded_hero_images[] = $webAccessibleDir . $new_filename;
                            // Afficher un message de succès
                            $_SESSION['admin_message'] = "Les images ont été téléchargées avec succès.";
                            $_SESSION['admin_message_type'] = 'success';
                        } else {
                            $_SESSION['admin_message'] = "Erreur lors du déplacement du fichier hero " . htmlspecialchars($filename) . ".";
                            $_SESSION['admin_message_type'] = 'error';
                        }
                    } else {
                        $_SESSION['admin_message'] = "Type de fichier non autorisé pour " . htmlspecialchars($filename) . " lors de l'ajout. Seuls " . implode(', ', $allowed) . " sont autorisés.";
                        $_SESSION['admin_message_type'] = 'error';
                    }
                } elseif ($_FILES['hero_images_upload']['error'][$key] !== UPLOAD_ERR_NO_FILE) {
                     // Report other upload errors except NO_FILE
                     $_SESSION['admin_message'] = "Erreur lors de l'upload du fichier " . htmlspecialchars($_FILES['hero_images_upload']['name'][$key]) . " lors de l'ajout : Code d'erreur " . $_FILES['hero_images_upload']['error'][$key] . ".";
                     $_SESSION['admin_message_type'] = 'error';
                }
            }
        }

        // If upload was attempted but no files were successfully uploaded (and files were selected)
        if (empty($uploaded_hero_images) && !empty($valid_files)) {
             $_SESSION['admin_message'] = "Aucun fichier hero n'a pu être téléchargé avec succès lors de l'ajout.";
             $_SESSION['admin_message_type'] = 'error';
        }
    }
    // --- End Handle Hero Images Upload for Add ---

    // --- DEBUG: Inspect uploaded_hero_images array for Add ---
    error_log("[PHP Debug - ADD] uploaded_hero_images array: " . print_r($uploaded_hero_images, true));
    var_dump($uploaded_hero_images);
    // --- End DEBUG ---

    $hero_images_string = implode(',', $uploaded_hero_images);

    try {
        $pdo->beginTransaction();
        
        // --- MODIFIED: Inserer le lieu avec seulement hero_images (removed old photo columns) ---
        $stmt = $pdo->prepare("INSERT INTO lieux (nom, description, id_ville, url_activite, categorie, hero_images) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nom, $desc, $id_ville, $url_activite, $categorie, $hero_images_string]);
        $lieu_id = $pdo->lastInsertId();
        
        // Ru00e9cupu00e9rer le nom de la ville
        $stmt = $pdo->prepare("SELECT nom FROM villes WHERE id = ?");
        $stmt->execute([$id_ville]);
        $ville_nom = $stmt->fetchColumn();
        
        // Cru00e9er une recommandation liu00e9e au lieu si une catu00e9gorie est su00e9lectionnu00e9e
        if (!empty($categorie)) {
            // Du00e9finir les prix et duru00e9es par du00e9faut selon la catu00e9gorie
            $prix_durees = [
                'hotels' => ['prix_min' => 800, 'prix_max' => 2000, 'duree_min' => 1, 'duree_max' => 3],
                'restaurants' => ['prix_min' => 300, 'prix_max' => 1000, 'duree_min' => 1, 'duree_max' => 1],
                'parcs' => ['prix_min' => 100, 'prix_max' => 300, 'duree_min' => 1, 'duree_max' => 1],
                'plages' => ['prix_min' => 0, 'prix_max' => 200, 'duree_min' => 1, 'duree_max' => 1],
                'cinemas' => ['prix_min' => 100, 'prix_max' => 200, 'duree_min' => 1, 'duree_max' => 1],
                'theatres' => ['prix_min' => 200, 'prix_max' => 500, 'duree_min' => 1, 'duree_max' => 1],
                'monuments' => ['prix_min' => 100, 'prix_max' => 300, 'duree_min' => 1, 'duree_max' => 1],
                'musees' => ['prix_min' => 100, 'prix_max' => 300, 'duree_min' => 1, 'duree_max' => 2],
                'shopping' => ['prix_min' => 400, 'prix_max' => 1500, 'duree_min' => 1, 'duree_max' => 2],
                'vie_nocturne' => ['prix_min' => 300, 'prix_max' => 1000, 'duree_min' => 1, 'duree_max' => 1]
            ];
            
            // Titre et description par défaut selon la catégorie
            $titres_descriptions = [
                'hotels' => [
                    'titre' => 'Séjour à ' . $nom,
                    'description' => 'Profitez d\'un séjour confortable à ' . $nom . ', un hôtel de qualité à ' . $ville_nom . '.'
                ],
                'restaurants' => [
                    'titre' => 'Dégustation à ' . $nom,
                    'description' => 'Savourez une expérience culinaire unique à ' . $nom . ', un restaurant réputé de ' . $ville_nom . '.'
                ],
                'parcs' => [
                    'titre' => 'Promenade à ' . $nom,
                    'description' => 'Profitez d\'une promenade relaxante au ' . $nom . ', un parc magnifique de ' . $ville_nom . '.'
                ],
                'plages' => [
                    'titre' => 'Détente à ' . $nom,
                    'description' => 'Relaxez-vous sur la plage de ' . $nom . ', un lieu de détente idéal à ' . $ville_nom . '.'
                ],
                'cinemas' => [
                    'titre' => 'Séance au cinéma ' . $nom,
                    'description' => 'Profitez d\'une séance de cinéma au ' . $nom . ', une salle moderne à ' . $ville_nom . '.'
                ],
                'theatres' => [
                    'titre' => 'Spectacle au théâtre ' . $nom,
                    'description' => 'Assistez à un spectacle captivant au théâtre ' . $nom . ' de ' . $ville_nom . '.'
                ],
                'monuments' => [
                    'titre' => 'Visite de ' . $nom,
                    'description' => 'Découvrez ' . $nom . ', un monument historique incontournable de ' . $ville_nom . '.'
                ],
                'musees' => [
                    'titre' => 'Visite du musée ' . $nom,
                    'description' => 'Explorez les collections fascinantes du musée ' . $nom . ' à ' . $ville_nom . '.'
                ],
                'shopping' => [
                    'titre' => 'Shopping à ' . $nom,
                    'description' => 'Faites du shopping à ' . $nom . ', une destination incontournable pour les achats à ' . $ville_nom . '.'
                ],
                'vie_nocturne' => [
                    'titre' => 'Soirée à ' . $nom,
                    'description' => 'Profitez de la vie nocturne à ' . $nom . ', un lieu branché de ' . $ville_nom . '.'
                ]
            ];
            
            // Utiliser les valeurs par du00e9faut pour la catu00e9gorie su00e9lectionnu00e9e
            $prix_min = $prix_durees[$categorie]['prix_min'];
            $prix_max = $prix_durees[$categorie]['prix_max'];
            $duree_min = $prix_durees[$categorie]['duree_min'];
            $duree_max = $prix_durees[$categorie]['duree_max'];
            $titre = $titres_descriptions[$categorie]['titre'];
            $description = $titres_descriptions[$categorie]['description'];
            
            // Insu00e9rer la recommandation
            $stmt = $pdo->prepare("INSERT INTO recommandations (ville_id, titre, description, categorie, prix_min, prix_max, duree_min, duree_max, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$id_ville, $titre, $description, $categorie, $prix_min, $prix_max, $duree_min, $duree_max, $uploaded_hero_images[0]]);
        }
        
        $pdo->commit();
        // Rediriger vers la page des lieux filtrée par la ville sélectionnée
        header('Location: admin-places.php?ville_id=' . $id_ville);
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['admin_message'] = "Erreur lors de la modification du lieu : " . $e->getMessage();
        $_SESSION['admin_message_type'] = 'error';
        header('Location: admin-places.php' . (isset($_GET['ville_id']) ? '?ville_id=' . intval($_GET['ville_id']) : ''));
        exit();
    }
}

// Modification d'un lieu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    // --- REMOVED: Debugging code ---
    // error_log("[PHP Debug] FILES array: " . print_r($_FILES, true));
    // var_dump($_FILES);
    // --- End REMOVED Debug ---

    $edit_id = intval($_POST['edit_id']);
    $nom = $_POST['nom'];
    $desc = $_POST['description'];
    $id_ville = intval($_POST['id_ville']);
    $categorie = $_POST['categorie'] ?? '';
    $url_activite = $_POST['url_activite'] ?? '';
    // Sécuriser la longueur de l'URL d'activité (évite l'erreur SQL 1406)
    $url_activite = mb_substr($url_activite, 0, 255);
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    
    $uploaded_hero_images = []; // Array to store paths of newly uploaded hero images

    // --- Handle Hero Images Upload for Edit ---
    // Check if files were uploaded and if the 'hero_images_upload' key exists and is an array
    if (isset($_FILES['hero_images_upload']['name']) && is_array($_FILES['hero_images_upload']['name'])) {
        // Filter out empty file inputs (when user doesn't select a file)
        $valid_files = array_filter($_FILES['hero_images_upload']['name'], function($name) { return !empty($name); });

        if (!empty($valid_files)) {
            $uploadHeroDir = '../uploads/lieux/hero/'; // Separate directory for hero images
            
            // Attempt to create directory if it doesn't exist and set permissions
            if (!is_dir($uploadHeroDir)) {
                if (!mkdir($uploadHeroDir, 0775, true)) {
                    $_SESSION['admin_message'] = "Erreur: Impossible de créer le dossier d'upload pour les images hero.";
                    $_SESSION['admin_message_type'] = 'error';
                    // Consider exiting here if directory creation is critical
                }
            }
            // Attempt to set permissions even if directory existed
            if (!chmod($uploadHeroDir, 0775)) {
                $_SESSION['admin_message'] = "Avertissement: Impossible de définir les permissions pour le dossier d'upload des images hero.";
                $_SESSION['admin_message_type'] = 'warning';
            }

            foreach (array_keys($_FILES['hero_images_upload']['name']) as $key) {
                 // Check for upload errors for the current file
                if ($_FILES['hero_images_upload']['error'][$key] === UPLOAD_ERR_OK) {
                    $filename = $_FILES['hero_images_upload']['name'][$key];
                    $tempPath = $_FILES['hero_images_upload']['tmp_name'][$key];
                    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                    if (in_array($ext, $allowed)) {
                        $new_filename = uniqid('hero_', true) . '.' . $ext;
                        $targetPath = $uploadHeroDir . $new_filename;

                        if (move_uploaded_file($tempPath, $targetPath)) {
                            $uploaded_hero_images[] = 'uploads/lieux/hero/' . $new_filename;
                        } else {
                            $_SESSION['admin_message'] = "Erreur lors du déplacement du fichier hero " . htmlspecialchars($filename) . ".";
                            $_SESSION['admin_message_type'] = 'error';
                        }
                    } else {
                        $_SESSION['admin_message'] = "Type de fichier non autorisé pour " . htmlspecialchars($filename) . ". Seuls " . implode(', ', $allowed) . " sont autorisés.";
                        $_SESSION['admin_message_type'] = 'error';
                    }
                } elseif ($_FILES['hero_images_upload']['error'][$key] !== UPLOAD_ERR_NO_FILE) {
                     // Report other upload errors except NO_FILE
                     $_SESSION['admin_message'] = "Erreur lors de l'upload du fichier " . htmlspecialchars($_FILES['hero_images_upload']['name'][$key]) . " : Code d'erreur " . $_FILES['hero_images_upload']['error'][$key] . ".";
                     $_SESSION['admin_message_type'] = 'error';
                }
            }
        }

        // If upload was attempted but no files were successfully uploaded (and files were selected)
        if (empty($uploaded_hero_images) && !empty($valid_files)) {
             $_SESSION['admin_message'] = "Aucun fichier hero n'a pu être téléchargé avec succès.";
             $_SESSION['admin_message_type'] = 'error';
        }
    }
    // --- End Handle Hero Images Upload for Edit ---

    // Determine the final hero_images string to save
    // Fetch the current hero_images value from the database for this place
    $stmt = $pdo->prepare("SELECT hero_images FROM lieux WHERE id = ?");
    $stmt->execute([$edit_id]);
    $existing_hero_images = $stmt->fetchColumn();
    
    // Convertir la chaîne existante en tableau
    $existing_hero_array = [];
    if (!empty($existing_hero_images)) {
        $existing_hero_array = array_filter(array_map('trim', explode(',', $existing_hero_images)));
    }
    
    // Combiner les images existantes avec les nouvelles images téléchargées
    $all_hero_images = array_merge($existing_hero_array, $uploaded_hero_images);
    
    // Supprimer les doublons et les valeurs vides
    $all_hero_images = array_filter(array_unique($all_hero_images));
    
    // Créer la chaîne finale pour la base de données
    $hero_images_string = implode(',', $all_hero_images);
    
    // Débogage des images hero
    error_log("[DEBUG HERO IMAGES] Images existantes: " . print_r($existing_hero_array, true));
    error_log("[DEBUG HERO IMAGES] Nouvelles images uploadées: " . print_r($uploaded_hero_images, true));
    error_log("[DEBUG HERO IMAGES] Toutes les images combinées: " . print_r($all_hero_images, true));
    error_log("[DEBUG HERO IMAGES] Chaîne finale: " . $hero_images_string);

    try {
        $pdo->beginTransaction();
        
        // --- MODIFIED: Update only the hero_images column (and other relevant fields) ---
        $stmt = $pdo->prepare("UPDATE lieux SET nom = ?, description = ?, id_ville = ?, url_activite = ?, categorie = ?, hero_images = ? WHERE id = ?");
        $stmt->execute([$nom, $desc, $id_ville, $url_activite, $categorie, $hero_images_string, $edit_id]);
        
        // Récupérer le nom de la ville pour le message de succès
        $stmt = $pdo->prepare("SELECT nom FROM villes WHERE id = ?");
        $stmt->execute([$id_ville]);
        $ville_nom = $stmt->fetchColumn();
        
        // Handle recommendation update/insertion (remains largely unchanged, ensure it uses current photo if needed, though hero_images is separate)
        // Note: The recommendation image_url currently uses $photo. If you want it to use one of the hero images, you'd need to pick one from $hero_images_string.
        // For now, assuming recommendation image is separate or uses a default.
        
        // Vérifier si une recommandation existe déjà pour ce lieu (using nom for matching)
        $stmt = $pdo->prepare("SELECT id FROM recommandations WHERE ville_id = ? AND titre LIKE ?");
        $stmt->execute([$id_ville, '%' . $nom . '%']); // Using LIKE %% to find recommendation by name
        $recommandation_id = $stmt->fetchColumn();
        
        if (!empty($categorie)) {
            // Définir les prix et durées par défaut selon la catégorie
            $prix_durees = [
                'hotels' => ['prix_min' => 800, 'prix_max' => 2000, 'duree_min' => 1, 'duree_max' => 3],
                'restaurants' => ['prix_min' => 300, 'prix_max' => 1000, 'duree_min' => 1, 'duree_max' => 1],
                'parcs' => ['prix_min' => 100, 'prix_max' => 300, 'duree_min' => 1, 'duree_max' => 1],
                'plages' => ['prix_min' => 0, 'prix_max' => 200, 'duree_min' => 1, 'duree_max' => 1],
                'cinemas' => ['prix_min' => 100, 'prix_max' => 200, 'duree_min' => 1, 'duree_max' => 1],
                'theatres' => ['prix_min' => 200, 'prix_max' => 500, 'duree_min' => 1, 'duree_max' => 1],
                'monuments' => ['prix_min' => 100, 'prix_max' => 300, 'duree_min' => 1, 'duree_max' => 1],
                'musees' => ['prix_min' => 100, 'prix_max' => 300, 'duree_min' => 1, 'duree_max' => 2],
                'shopping' => ['prix_min' => 400, 'prix_max' => 1500, 'duree_min' => 1, 'duree_max' => 2],
                'vie_nocturne' => ['prix_min' => 300, 'prix_max' => 1000, 'duree_min' => 1, 'duree_max' => 1]
            ];
            
            // Titre et description par défaut selon la catégorie
            $titres_descriptions = [
                'hotels' => [
                    'titre' => 'Séjour à ' . $nom,
                    'description' => 'Profitez d\'un séjour confortable à ' . $nom . ', un hôtel de qualité à ' . $ville_nom . '.'
                ],
                'restaurants' => [
                    'titre' => 'Dégustation à ' . $nom,
                    'description' => 'Savourez une expérience culinaire unique à ' . $nom . ', un restaurant réputé de ' . $ville_nom . '.'
                ],
                'parcs' => [
                    'titre' => 'Promenade à ' . $nom,
                    'description' => 'Profitez d\'une promenade relaxante au ' . $nom . ', un parc magnifique de ' . $ville_nom . '.'
                ],
                'plages' => [
                    'titre' => 'Détente à ' . $nom,
                    'description' => 'Relaxez-vous sur la plage de ' . $nom . ', un lieu de détente idéal à ' . $ville_nom . '.'
                ],
                'cinemas' => [
                    'titre' => 'Séance au cinéma ' . $nom,
                    'description' => 'Profitez d\'une séance de cinéma au ' . $nom . ', une salle moderne à ' . $ville_nom . '.'
                ],
                'theatres' => [
                    'titre' => 'Spectacle au théâtre ' . $nom,
                    'description' => 'Assistez à un spectacle captivant au théâtre ' . $nom . ' de ' . $ville_nom . '.'
                ],
                'monuments' => [
                    'titre' => 'Visite de ' . $nom,
                    'description' => 'Découvrez ' . $nom . ', un monument historique incontournable de ' . $ville_nom . '.'
                ],
                'musees' => [
                    'titre' => 'Visite du musée ' . $nom,
                    'description' => 'Explorez les collections fascinantes du musée ' . $nom . ' à ' . $ville_nom . '.'
                ],
                'shopping' => [
                    'titre' => 'Shopping à ' . $nom,
                    'description' => 'Faites du shopping à ' . $nom . ', une destination incontournable pour les achats à ' . $ville_nom . '.'
                ],
                'vie_nocturne' => [
                    'titre' => 'Soirée à ' . $nom,
                    'description' => 'Profitez de la vie nocturne à ' . $nom . ', un lieu branché de ' . $ville_nom . '.'
                ]
            ];
            
            // Utiliser les valeurs par défaut pour la catégorie sélectionnée
            $prix_min = $prix_durees[$categorie]['prix_min'];
            $prix_max = $prix_durees[$categorie]['prix_max'];
            $duree_min = $prix_durees[$categorie]['duree_min'];
            $duree_max = $prix_durees[$categorie]['duree_max'];
            $titre = $titres_descriptions[$categorie]['titre'];
            $description = $titres_descriptions[$categorie]['description'];
            
            if ($recommandation_id) {
                // Mettre à jour la recommandation existante
                $stmt = $pdo->prepare("UPDATE recommandations SET titre = ?, description = ?, categorie = ?, prix_min = ?, prix_max = ?, duree_min = ?, duree_max = ?, image_url = ? WHERE id = ?");
                $stmt->execute([$titre, $description, $categorie, $prix_min, $prix_max, $duree_min, $duree_max, $uploaded_hero_images[0], $recommandation_id]);
            } else {
                // Créer une nouvelle recommandation
                $stmt = $pdo->prepare("INSERT INTO recommandations (ville_id, titre, description, categorie, prix_min, prix_max, duree_min, duree_max, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$id_ville, $titre, $description, $categorie, $prix_min, $prix_max, $duree_min, $duree_max, $uploaded_hero_images[0]]);
            }
        }
        
        $pdo->commit();
        // --- NEW: Success message after successful update ---
        $_SESSION['admin_message'] = "Lieu '" . htmlspecialchars($nom) . "' modifié avec succès.";
        $_SESSION['admin_message_type'] = 'success';

        // Rediriger vers la page des lieux filtrée par la ville sélectionnée
        header('Location: admin-places.php?ville_id=' . $id_ville);
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['admin_message'] = "Erreur lors de la modification du lieu : " . $e->getMessage();
        $_SESSION['admin_message_type'] = 'error';
        header('Location: admin-places.php' . (isset($_GET['ville_id']) ? '?ville_id=' . intval($_GET['ville_id']) : ''));
        exit();
    }
}

// Suppression d'un lieu
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $pdo->prepare("DELETE FROM lieux WHERE id = ?")->execute([$id]);
    header('Location: admin-places.php');
    exit();
}

// Suppression d'une image hero individuelle
if ((isset($_GET['delete_hero_image']) && $_GET['delete_hero_image'] == 1) && isset($_GET['place_id']) && isset($_GET['image_path'])) {
    $place_id = intval($_GET['place_id']);
    $image_path = urldecode($_GET['image_path']);
    
    try {
        // Récupérer les images hero actuelles
        $stmt = $pdo->prepare("SELECT hero_images FROM lieux WHERE id = ?");
        $stmt->execute([$place_id]);
        $hero_images_string = $stmt->fetchColumn();
        
        if ($hero_images_string) {
            // Convertir la chaîne en tableau et nettoyer les espaces
            $hero_images = array_map('trim', explode(',', $hero_images_string));
            
            // Retirer l'image spécifiée du tableau
            $hero_images = array_filter($hero_images, function($img) use ($image_path) {
                return trim($img) !== trim($image_path);
            });
            
            // Réindexer le tableau pour éviter les clés non séquentielles
            $hero_images = array_values($hero_images);
            
            // Mettre à jour la base de données avec la nouvelle liste d'images
            $new_hero_images = implode(',', $hero_images);
            
            $update_stmt = $pdo->prepare("UPDATE lieux SET hero_images = ? WHERE id = ?");
            $result = $update_stmt->execute([$new_hero_images, $place_id]);
            
            // Supprimer physiquement le fichier (optionnel)
            $file_path = '../' . $image_path;
            if (file_exists($file_path)) {
                @unlink($file_path);
            }
            
            $_SESSION['admin_message'] = "L'image a été supprimée avec succès.";
            $_SESSION['admin_message_type'] = 'success';
        }
    } catch (Exception $e) {
        // En cas d'erreur, enregistrer l'erreur et afficher un message
        $_SESSION['admin_message'] = "Erreur lors de la suppression de l'image: " . $e->getMessage();
        $_SESSION['admin_message_type'] = 'error';
    }
    
    // Rediriger vers la page d'édition
    header('Location: admin-places.php?edit=' . $place_id);
    exit();
}

// Préparation de l'édition
if (isset($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM lieux WHERE id = ?");
    $stmt->execute([$editId]);
    $editPlace = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($editPlace) {
        $editMode = true;
    }
}

// Liste des lieux et villes
$ville_filter = isset($_GET['ville_id']) ? intval($_GET['ville_id']) : 0;
$categorie_filter = isset($_GET['categorie']) ? trim($_GET['categorie']) : '';

// Charger toutes les villes avec gestion d'erreur
$cities = [];
try {
    $stmt = $pdo->query("SELECT id, nom FROM villes ORDER BY nom");
    $cities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Villes chargées depuis la base de données : " . count($cities));
    
    // Afficher les noms des villes pour le débogage
    $city_names = array_map(function($city) { return $city['nom']; }, $cities);
    error_log("Liste des villes : " . implode(", ", $city_names));
    
} catch (PDOException $e) {
    error_log("Erreur lors du chargement des villes : " . $e->getMessage());
    $_SESSION['admin_message'] = "Erreur lors du chargement des villes : " . $e->getMessage();
    $_SESSION['admin_message_type'] = 'error';
}

if ($ville_filter > 0 && $categorie_filter !== '') {
    // Filtrer par ville ET catégorie
    try {
        $stmt = $pdo->prepare("SELECT lieux.*, villes.nom AS ville_nom FROM lieux LEFT JOIN villes ON lieux.id_ville = villes.id WHERE lieux.id_ville = ? AND LOWER(TRIM(lieux.categorie)) = LOWER(TRIM(?))");
        $stmt->execute([$ville_filter, $categorie_filter]);
        $places = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = $pdo->prepare("SELECT nom FROM villes WHERE id = ?");
        $stmt->execute([$ville_filter]);
        $filtered_city_name = $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Erreur lors du filtrage par ville et catégorie : " . $e->getMessage());
        $places = [];
    }
} elseif ($ville_filter > 0) {
    // Filtrer par ville uniquement
    try {
        $stmt = $pdo->prepare("SELECT lieux.*, villes.nom AS ville_nom FROM lieux LEFT JOIN villes ON lieux.id_ville = villes.id WHERE lieux.id_ville = ?");
        $stmt->execute([$ville_filter]);
        $places = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = $pdo->prepare("SELECT nom FROM villes WHERE id = ?");
        $stmt->execute([$ville_filter]);
        $filtered_city_name = $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Erreur lors du filtrage par ville : " . $e->getMessage());
        $places = [];
    }
} elseif ($categorie_filter !== '') {
    // Filtrer par catégorie uniquement
    try {
        $stmt = $pdo->prepare("SELECT lieux.*, villes.nom AS ville_nom FROM lieux LEFT JOIN villes ON lieux.id_ville = villes.id WHERE LOWER(TRIM(lieux.categorie)) = LOWER(TRIM(?))");
        $stmt->execute([$categorie_filter]);
        $places = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur lors du filtrage par catégorie : " . $e->getMessage());
        $places = [];
    }
} else {
    // Afficher tous les lieux si aucun filtre n'est spécifié
    try {
        $stmt = $pdo->query("SELECT lieux.*, villes.nom AS ville_nom FROM lieux LEFT JOIN villes ON lieux.id_ville = villes.id");
        $places = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Debug: Afficher le nombre total de lieux
        error_log("Nombre total de lieux chargés : " . count($places));
        
        // Debug: Afficher le nombre de lieux par catégorie
        $categories = [];
        foreach ($places as $place) {
            $categorie = $place['categorie'] ?? 'Non défini';
            if (!isset($categories[$categorie])) {
                $categories[$categorie] = 0;
            }
            $categories[$categorie]++;
        }
        foreach ($categories as $categorie => $count) {
            error_log("Catégorie '$categorie': $count lieux");
        }
    } catch (PDOException $e) {
        error_log("Erreur lors du chargement des lieux : " . $e->getMessage());
        $places = [];
    }
}

// --- Display admin messages ---
if (isset($_SESSION['admin_message'])) {
    $message = $_SESSION['admin_message'];
    $message_type = $_SESSION['admin_message_type'] ?? 'info'; // default to info
    unset($_SESSION['admin_message']);
    unset($_SESSION['admin_message_type']);
}
// --- End Display admin messages ---
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Admin - Gestion des lieux</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Montserrat', 'Poppins', Arial, sans-serif;
            background: #f6f7fb;
            color: #222;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 32px 16px 64px 16px;
        }
        .section-title h2, .section-title h3 {
            font-family: 'Montserrat', 'Poppins', Arial, sans-serif;
            font-weight: 800;
            color: #2d2d2d;
            letter-spacing: 1px;
        }
        .admin-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 20px;
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(44,44,44,0.06);
            border: 1.5px solid #e9cba7;
        }
        .admin-table thead th {
            background: #fff;
            color: #bfa14a;
            font-family: 'Montserrat', 'Poppins', Arial, sans-serif;
            font-weight: 700;
            font-size: 1.08rem;
            border-bottom: 2px solid #e9cba7;
            padding: 18px 14px;
            letter-spacing: 0.5px;
            text-align: left;
        }
        .admin-table th, .admin-table td {
            padding: 18px 14px;
            text-align: left;
            border-bottom: 1px solid #f3e9d1;
            font-size: 1.01rem;
        }
        .admin-table tr:nth-child(even) {
            background: #fafbfc;
        }
        .admin-table tr:hover {
            background: #f9f6f2;
        }
        .btn-solid, .btn-outline {
            font-family: 'Montserrat', 'Poppins', Arial, sans-serif;
            font-weight: 600;
            border-radius: 8px;
            padding: 10px 28px;
            font-size: 1.08rem;
            border: 1.5px solid #e9cba7;
            box-shadow: 0 2px 8px #e9cba733;
            transition: background 0.2s, color 0.2s, border 0.2s;
            cursor: pointer;
        }
        .btn-solid {
            background: #e9cba7;
            color: #222;
        }
        .btn-solid:hover {
            background: #bfa14a;
            color: #fff;
        }
        .btn-outline {
            background: #fff;
            color: #bfa14a;
        }
        .btn-outline:hover {
            background: #bfa14a;
            color: #fff;
        }
        .btn-delete {
            background: #dc3545;
            color: #fff;
            border: none;
            padding: 9px 22px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1rem;
            transition: background 0.2s;
        }
        .btn-delete:hover {
            background: #b52a37;
        }
        input, select, textarea {
            padding: 12px 20px;
            border: 1.5px solid #e9cba7;
            border-radius: 8px;
            background: #fff;
            color: #222;
            font-size: 1.08rem;
            box-shadow: 0 2px 8px #e9cba733;
            transition: border 0.2s, box-shadow 0.2s;
            outline: none;
        }
        input:focus, select:focus, textarea:focus {
            border-color: #bfa14a;
            box-shadow: 0 4px 16px #e9cba755;
            background: #fff;
        }
        .admin-filters-bar {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 4px 18px #e9cba733;
            padding: 18px 28px;
            display: flex;
            align-items: center;
            gap: 18px;
            max-width: 900px;
            margin: 0 auto 28px auto;
        }
        .admin-filters-bar select,
        .admin-filters-bar input[type="text"] {
            min-width: 170px;
            height: 44px;
            font-size: 1.05rem;
            border-radius: 10px;
            border: 1.5px solid #e9cba7;
            background: #faf9f7;
            color: #222;
            padding: 0 18px;
            font-family: 'Montserrat', 'Poppins', Arial, sans-serif;
            box-shadow: 0 2px 8px #e9cba733;
            transition: border 0.2s, box-shadow 0.2s;
            outline: none;
        }
        .admin-filters-bar select:focus,
        .admin-filters-bar input[type="text"]:focus {
            border-color: #bfa14a;
            background: #fff;
        }
        #btnRechercher {
            background: #e9cba7;
            color: #222;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 1.08rem;
            padding: 0 32px;
            height: 44px;
            cursor: pointer;
            box-shadow: 0 2px 8px #e9cba733;
            transition: background 0.2s, color 0.2s;
            font-family: 'Montserrat', 'Poppins', Arial, sans-serif;
            margin-left: 8px;
            display: flex;
            align-items: center;
        }
        #btnRechercher:hover {
            background: #bfa14a;
            color: #fff;
        }
        @media (max-width: 900px) {
            .admin-filters-bar {
                flex-direction: column;
                align-items: stretch;
                gap: 12px;
                padding: 14px 8px;
                max-width: 98vw;
            }
        }
        footer {
            background: #fff;
            color: #bfa14a;
            text-align: center;
            padding: 18px 0 0 0;
            font-size: 1rem;
            border-top: 1.5px solid #e9cba7;
            margin-top: 48px;
        }
        @media (max-width: 900px) {
            .container { max-width: 98vw; padding: 12px 2vw 32px 2vw; }
            .admin-table th, .admin-table td { font-size: 0.97rem; padding: 12px 7px; }
        }
        @media (max-width: 600px) {
            .admin-table, .admin-table thead, .admin-table tbody, .admin-table th, .admin-table td, .admin-table tr {
                display: block;
            }
            .admin-table thead tr { display: none; }
            .admin-table tr {
                margin-bottom: 18px;
                border-radius: 8px;
                box-shadow: 0 2px 8px #e9cba733;
                background: #fff;
            }
            .admin-table td {
                padding: 12px 8px;
                border: none;
                position: relative;
            }
            .admin-table td:before {
                content: attr(data-label);
                font-weight: 600;
                color: #bfa14a;
                display: block;
                margin-bottom: 6px;
                text-transform: uppercase;
                font-size: 0.9em;
            }
        }
        .admin-alert {
            max-width: 480px;
            margin: 32px auto 0 auto;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 4px 18px rgba(44,44,44,0.09);
            padding: 22px 32px 20px 32px;
            display: flex;
            align-items: center;
            gap: 16px;
            font-size: 1.13rem;
            font-family: 'Poppins', Arial, sans-serif;
            font-weight: 500;
            justify-content: center;
            text-align: center;
            position: relative;
            z-index: 10;
            border: 1.5px solid #e9cba7;
            animation: fadeInDown 0.7s cubic-bezier(.23,1.01,.32,1) both;
        }
        .admin-alert i {
            font-size: 1.6em;
            flex-shrink: 0;
        }
        .admin-alert-success {
            color: #2e7d32;
            border-color: #b7e1c6;
        }
        .admin-alert-error {
            color: #b52a37;
            border-color: #f5b7b1;
        }
        .admin-alert-info {
            color: #bfa14a;
            border-color: #e9cba7;
        }
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @media (max-width: 600px) {
            .admin-alert {
                max-width: 98vw;
                padding: 14px 8vw 14px 8vw;
                font-size: 1rem;
            }
        }
        .admin-table th.col-description,
        .admin-table td.col-description {
            min-width: 400px;
            max-width: 700px;
            width: 40vw;
            white-space: pre-line;
            word-break: break-word;
        }
        .admin-table th.col-hero,
        .admin-table td.col-hero {
            min-width: 120px;
            max-width: 200px;
        }
        .admin-table th.col-id,
        .admin-table td.col-id,
        .admin-table th.col-idville,
        .admin-table td.col-idville,
        .admin-table th.col-lat,
        .admin-table td.col-lat,
        .admin-table th.col-lng,
        .admin-table td.col-lng,
        .admin-table th.col-rating,
        .admin-table td.col-rating {
            width: 80px;
            max-width: 90px;
            white-space: nowrap;
            font-size: 0.98em;
            text-align: center;
        }
        .admin-table th.col-budget,
        .admin-table td.col-budget {
            width: 50px;
            max-width: 60px;
            text-align: center;
        }
    </style>
</head>
<body>
    <!-- Header/Navbar moderne -->
    <header style="background:#fff; box-shadow:0 2px 12px #e9cba733; padding:0;">
        <div class="container header-container" style="display:flex;align-items:center;justify-content:space-between;min-height:80px;width:100%;">
            <a href="../index.php" class="logo" style="flex:0 0 auto;display:flex;align-items:center;gap:12px;text-decoration:none;">
                <img src="https://i.postimg.cc/g07GgLp5/VMaroc-logo-trf.png" alt="Maroc Authentique" class="logo-img" style="height:54px;">
            </a>
            <ul class="nav-menu" style="flex:1 1 0;display:flex;justify-content:center;gap:36px;list-style:none;margin:0;padding:0;">
                <li><a href="../index.php" style="color:#222;font-family:'Montserrat',sans-serif;font-weight:600;font-size:1.08rem;text-decoration:none;transition:color .2s;">Accueil</a></li>
                <li><a href="../destinations.php" style="color:#222;font-family:'Montserrat',sans-serif;font-weight:600;font-size:1.08rem;text-decoration:none;transition:color .2s;">Destinations</a></li>
                <li><a href="../recommandations.php" style="color:#222;font-family:'Montserrat',sans-serif;font-weight:600;font-size:1.08rem;text-decoration:none;transition:color .2s;">Recommandations</a></li>
            </ul>
            <div class="auth-buttons" style="flex:0 0 auto;display:flex;align-items:center;gap:12px;">
                <a href="admin-panel.php" class="btn-outline">Panel Admin</a>
                <a href="../logout.php" class="btn-solid" style="background:#dc3545;color:#fff;border:none;">Déconnexion</a>
            </div>
        </div>
    </header>

    <main style="margin-top:48px;">
    <div class="container" style="max-width:98vw;width:100%;padding:0 10px;">
            <?php if (isset($message)): ?>
                <div class="admin-alert admin-alert-<?= $message_type ?>">
                    <?php if ($message_type === 'success'): ?>
                        <i class="fas fa-check-circle"></i>
                    <?php elseif ($message_type === 'error'): ?>
                        <i class="fas fa-times-circle"></i>
                    <?php else: ?>
                        <i class="fas fa-info-circle"></i>
                    <?php endif; ?>
                    <span><?= htmlspecialchars($message) ?></span>
                </div>
            <?php endif; ?>
            <div class="section-title" style="text-align:center;margin-bottom:38px;">
                <h2 style="font-size:2.1rem;">Gestion des lieux</h2>
                <?php if (isset($ville_filter) && $ville_filter > 0 && isset($filtered_city_name)): ?>
                <div style="text-align: center; margin-top: 10px;">
                    <p style="color: var(--primary-color); font-weight: 500;">Lieux filtrés pour la ville de <strong><?= htmlspecialchars($filtered_city_name) ?></strong></p>
                    <a href="admin-places.php" class="btn-outline" style="display: inline-block; margin-top: 10px;">Voir tous les lieux</a>
                </div>
                <?php endif; ?>
            </div>
            <section class="section" style="padding:0;">
                <div class="form" style="max-width:520px;margin:0 auto 40px auto;background:#fff;border-radius:18px;box-shadow:0 4px 18px #e9cba733;padding:38px 32px 28px 32px;">
                    <h3 style="text-align:center;font-size:1.25rem;font-weight:700;margin-bottom:24px;letter-spacing:0.5px;"> <?= isset($editMode) && $editMode ? 'Modifier le lieu' : 'Ajouter un lieu' ?> </h3>
        <form method="post" enctype="multipart/form-data">
                        <?php if (isset($editMode) && $editMode): ?>
                            <input type="hidden" name="edit_id" value="<?= htmlspecialchars($editPlace['id']) ?>">
                        <?php endif; ?>
                        <div class="form-group" style="margin-bottom:18px;">
                            <label for="nom" style="font-weight:600;color:#2d2d2d;margin-bottom:7px;display:block;">Nom du lieu</label>
                            <input type="text" id="nom" name="nom" class="form-control" placeholder="Nom du lieu" required value="<?= isset($editPlace) ? htmlspecialchars($editPlace['nom']) : '' ?>">
                        </div>
                        <div class="form-group" style="margin-bottom:18px;">
                            <label for="hero_images_upload" style="font-weight:600;color:#2d2d2d;margin-bottom:7px;display:block;">Images pour le Hero Slider (plusieurs fichiers possibles) :</label>
                            <input type="file" name="hero_images_upload[]" id="hero_images_upload" accept="image/*" multiple>
                            <?php if (isset($editPlace) && !empty($editPlace['hero_images'])): ?>
                                <div style="margin-top: 15px; font-size: 0.9em; color: #555;">
                                    <p style="margin-bottom: 10px;"><strong>Images actuelles :</strong></p>
                                    <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                                    <?php
                                    $current_hero_images = array_map('trim', explode(',', $editPlace['hero_images']));
                                    foreach ($current_hero_images as $img_path) {
                                        if (!empty($img_path)) {
                                            echo '<div style="position: relative; width: 80px;">';
                                            echo '<img src="../' . htmlspecialchars($img_path) . '" alt="Hero Image" style="width: 80px; height: 80px; object-fit: cover; border-radius: 5px;">';
                                            echo '<a href="?delete_hero_image=1&place_id=' . $editPlace['id'] . '&image_path=' . urlencode($img_path) . '" onclick="return confirm(\'Supprimer cette image ?\')" style="position: absolute; top: -5px; right: -5px; background: #dc3545; color: white; border-radius: 50%; width: 22px; height: 22px; display: flex; align-items: center; justify-content: center; text-decoration: none; font-size: 12px;"><i class="fas fa-times"></i></a>';
                                            echo '</div>';
                                        }
                                    }
                                    ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="form-group" style="margin-bottom:18px;">
                            <label for="description" style="font-weight:600;color:#2d2d2d;margin-bottom:7px;display:block;">Description</label>
                            <input type="text" id="description" name="description" class="form-control" placeholder="Description" required value="<?= isset($editPlace) ? htmlspecialchars($editPlace['description']) : '' ?>">
                        </div>
                        <div class="form-group" style="margin-bottom:18px;">
                            <label for="url_activite" style="font-weight:600;color:#2d2d2d;margin-bottom:7px;display:block;">URL de l'activité (ex: https://www.hotel.com)</label>
                            <input type="text" id="url_activite" name="url_activite" class="form-control" placeholder="URL de l'activité (ex: https://www.hotel.com)" value="<?= isset($editPlace) ? htmlspecialchars($editPlace['url_activite'] ?? '') : '' ?>">
                        </div>
                        <div class="form-group" style="margin-bottom:18px;">
                            <label for="id_ville" style="font-weight:600;color:#2d2d2d;margin-bottom:7px;display:block;">Ville</label>
                            <select id="id_ville" name="id_ville" class="form-control" required>
                                <option value="">Ville</option>
                                <?php foreach ($cities as $city): ?>
                                    <option value="<?= $city['id'] ?>" <?= (isset($editPlace) && $editPlace['id_ville'] == $city['id']) || (!isset($editPlace) && isset($ville_filter) && $ville_filter == $city['id']) ? 'selected' : '' ?>><?= htmlspecialchars($city['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom:28px;">
                            <label for="categorie" style="font-weight:600;color:#2d2d2d;margin-bottom:7px;display:block;">Catégorie</label>
                            <select id="categorie" name="categorie" class="form-control" required>
                                <option value="">Catégorie</option>
                                <?php
                                $category_display_names = [
                                    'hotels' => 'Hôtels',
                                    'restaurants' => 'Restaurants',
                                    'parcs' => 'Parcs',
                                    'plages' => 'Plages',
                                    'cinemas' => 'Cinémas',
                                    'shopping' => 'Shopping',
                                    'theatres' => 'Théâtres',
                                    'vie_nocturne' => 'Vie nocturne',
                                    'monuments' => 'Monuments',
                                    'musees' => 'Musées',
                                    'nature' => 'Nature',
                                    'culture' => 'Culture',
                                    'histoire' => 'Histoire'
                                ];
                                asort($category_display_names);
                                foreach ($category_display_names as $cat_value => $cat_display_name) {
                                    $selected = (isset($editPlace) && strtolower(trim($editPlace['categorie'])) == strtolower(trim($cat_value))) ? 'selected' : '';
                                    echo "<option value=\"$cat_value\" $selected>$cat_display_name</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div style="display:flex;justify-content:center;gap:18px;align-items:center;">
                            <button type="submit" class="btn-solid" style="min-width:140px;"> <?= isset($editMode) && $editMode ? 'Enregistrer' : 'Ajouter' ?> </button>
                            <?php if (isset($editMode) && $editMode): ?>
                                <a href="admin-places.php" class="btn-outline" style="min-width:100px;">Annuler</a>
                            <?php endif; ?>
                        </div>
        </form>
                </div>

                <div class="section-title" style="text-align:center;margin:48px 0 18px 0;">
                    <h3 style="font-size:1.18rem;font-weight:700;">Liste des lieux</h3>
                </div>
                <!-- Filtres combinés -->
                <div class="admin-filters-bar">
                    <select id="filterVille" class="form-control">
                        <option value="">Toutes les villes</option>
                        <?php 
                        if (!empty($cities)) {
                            foreach ($cities as $city): 
                                $selected = (isset($ville_filter) && $ville_filter == $city['id']) ? 'selected' : '';
                                ?>
                                <option value="<?= $city['id'] ?>" data-ville-id="<?= $city['id'] ?>" <?= $selected ?>>
                                    <?= htmlspecialchars($city['nom']) ?>
                                </option>
                            <?php 
                            endforeach; 
                        } else {
                            echo '<option value="" disabled>Aucune ville disponible</option>';
                        }
                        ?>
                    </select>
                    <select id="filterCategorie" class="form-control">
                        <option value="">Toutes les catégories</option>
                        <?php
                        $category_display_names = [
                            'hotels' => 'Hôtels',
                            'restaurants' => 'Restaurants',
                            'parcs' => 'Parcs',
                            'plages' => 'Plages',
                            'cinemas' => 'Cinémas',
                            'shopping' => 'Shopping',
                            'theatres' => 'Théâtres',
                            'vie_nocturne' => 'Vie nocturne',
                            'monuments' => 'Monuments',
                            'musees' => 'Musées',
                            'nature' => 'Nature',
                            'culture' => 'Culture',
                            'histoire' => 'Histoire'
                        ];
                        asort($category_display_names);
                        echo '<option value="">Toutes les catégories</option>';
                        foreach ($category_display_names as $cat_value => $cat_display_name) {
                            $selected = ($categorie_filter !== '' && strtolower(trim($categorie_filter)) == strtolower(trim($cat_value))) ? 'selected' : '';
                            echo '<option value="' . strtolower(trim($cat_value)) . '" ' . $selected . '>' . htmlspecialchars($cat_display_name) . '</option>';
                        }
                         ?>
                    </select>
                    <input type="text" id="searchLieu" class="form-control" placeholder="Rechercher un lieu..." autocomplete="off" style="flex:1;min-width:180px;">
                    <button id="btnRechercher" class="btn-solid" type="button">Rechercher</button>
                </div>
                <div style="overflow-x:auto;">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Action</th>
                                <th class="col-id">ID</th>
                                <th class="col-idville">ID Ville</th>
                                <th>Nom</th>
                                <th class="col-hero">Hero Images</th>
                                <th>Catégorie</th>
                                <th class="col-description">Description</th>
                                <th>Adresse</th>
                                <th>Équipements</th>
                                <th>Boutiques/Services</th>
                                <th class="col-lat">Latitude</th>
                                <th class="col-lng">Longitude</th>
                                <th class="col-rating">Rating</th>
                                <th>URL Activité</th>
                                <th class="col-budget">Budget</th>
                            </tr>
                        </thead>
                        <tbody>
            <?php if (empty($places)): ?>
                <tr><td colspan="15" style="text-align:center;color:var(--secondary-color);">Aucun lieu enregistré.</td></tr>
            <?php endif; ?>
            <?php foreach ($places as $place): ?>
                <tr>
                    <td>
                        <div class="admin-actions" style="display:flex;flex-direction:column;gap:8px;align-items:flex-start;">
                            <a href="?edit=<?= $place['id'] ?>" class="btn-outline" style="padding:4px 12px;font-size:0.9rem;">Modifier</a>
                            <a href="?delete=<?= $place['id'] ?>" class="btn-delete" style="padding:4px 12px;font-size:0.9rem;" onclick="return confirm('Supprimer ce lieu ?')">Supprimer</a>
                        </div>
                    </td>
                    <td class="col-id"><?= $place['id'] ?></td>
                    <td class="col-idville"><?= $place['id_ville'] ?></td>
                    <td><?= htmlspecialchars($place['nom']) ?></td>
                    <td class="col-hero">
                        <?php
                        $hero_images = [];
                        if (!empty($place['hero_images'])) {
                            $hero_images = array_map('trim', explode(',', $place['hero_images']));
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
                    <td><?= htmlspecialchars($place['categorie']) ?></td>
                    <td class="col-description"><?= htmlspecialchars($place['description']) ?></td>
                    <td><?= htmlspecialchars($place['adresse']) ?></td>
                    <td><?= htmlspecialchars($place['equipements']) ?></td>
                    <td><?= htmlspecialchars($place['boutiques_services']) ?></td>
                    <td class="col-lat"><?= htmlspecialchars($place['latitude']) ?></td>
                    <td class="col-lng"><?= htmlspecialchars($place['longitude']) ?></td>
                    <td class="col-rating"><?= htmlspecialchars($place['rating']) ?></td>
                    <td><?= htmlspecialchars($place['url_activite']) ?></td>
                    <td class="col-budget"><?= htmlspecialchars($place['budget']) ?></td>
                </tr>
            <?php endforeach; ?>
                        </tbody>
        </table>
    </div>
            </section>
        </div>
    </main>
    <footer>
        <div class="container" style="text-align:center;">
            <p style="color:#bfa14a;font-family:'Montserrat',sans-serif;font-size:1rem;margin:18px 0 0 0;">© 2025 Maroc Authentique. Tous droits réservés.</p>
        </div>
    </footer>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const filterVille = document.getElementById('filterVille');
        const filterCategorie = document.getElementById('filterCategorie');
        const searchInput = document.getElementById('searchLieu');
        const btnRechercher = document.getElementById('btnRechercher');
        const rows = document.querySelectorAll('tbody tr[data-ville][data-categorie]');
        const suggestionsBox = document.getElementById('suggestionsLieu');
        
        // Récupérer tous les noms de lieux pour la recherche
        const lieux = Array.from(rows).map(row => ({
            id: row.querySelector('td:first-child').textContent,
            nom: row.querySelector('td:nth-child(2)').textContent,
            ville: row.getAttribute('data-ville') || '',
            categorie: row.getAttribute('data-categorie') || ''
        }));
        
        // Fonction pour normaliser les chaînes (gestion des accents et majuscules)
        function normalizeString(str) {
            if (!str) return '';
            return str.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
        }
        
        // Fonction pour filtrer les lignes du tableau
        function filterTable() {
            const selectedCategorie = filterCategorie ? filterCategorie.value.trim() : '';
            const searchTerm = searchInput ? searchInput.value.trim().toLowerCase() : '';
            
            rows.forEach(row => {
                const categorie = row.getAttribute('data-categorie') || '';
                const nomLieu = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                const description = row.querySelector('td:nth-child(4)').textContent.toLowerCase();
                
                // Vérifier les conditions de filtrage (catégorie et recherche SEULEMENT)
                const categorieMatch = !selectedCategorie || 
                    normalizeString(categorie) === normalizeString(selectedCategorie) ||
                    normalizeString(categorie).includes(normalizeString(selectedCategorie));
                
                const searchMatch = !searchTerm || 
                    nomLieu.includes(searchTerm) || 
                    description.includes(searchTerm) ||
                    normalizeString(nomLieu).includes(normalizeString(searchTerm)) ||
                    normalizeString(description).includes(normalizeString(searchTerm));
                
                // Afficher ou masquer la ligne selon les filtres
                row.style.display = (categorieMatch && searchMatch) ? '' : 'none';
            });
            
            // Mettre à jour l'URL avec les paramètres de filtrage
            updateUrlParams();
        }
        
        // Fonction pour mettre à jour les paramètres d'URL
        function updateUrlParams() {
            const params = new URLSearchParams(window.location.search);
            
            if (filterCategorie && filterCategorie.value) {
                params.set('categorie', encodeURIComponent(filterCategorie.value));
            } else {
                params.delete('categorie');
            }
            
            if (searchInput && searchInput.value) {
                params.set('recherche', encodeURIComponent(searchInput.value));
            } else {
                params.delete('recherche');
            }
            
            // Mettre à jour l'URL sans recharger la page
            const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
            window.history.pushState({}, '', newUrl);
        }
        
        // Fonction pour afficher les suggestions de recherche
        function showSuggestions() {
            if (!searchInput || !suggestionsBox) return;
            
            const searchTerm = searchInput.value.trim().toLowerCase();
            suggestionsBox.innerHTML = '';
            
            if (searchTerm.length < 2) {
                suggestionsBox.style.display = 'none';
                return;
            }
            
            const suggestions = lieux.filter(lieu => 
                normalizeString(lieu.nom).includes(normalizeString(searchTerm)) ||
                normalizeString(lieu.ville).includes(normalizeString(searchTerm)) ||
                normalizeString(lieu.categorie).includes(normalizeString(searchTerm))
            );
            
            if (suggestions.length > 0) {
                suggestions.forEach(sugg => {
                    const div = document.createElement('div');
                    div.textContent = `${sugg.nom} (${sugg.ville})`;
                    div.addEventListener('click', () => {
                        searchInput.value = sugg.nom;
                        suggestionsBox.style.display = 'none';
                        filterTable();
                    });
                    suggestionsBox.appendChild(div);
                });
                suggestionsBox.style.display = 'block';
            } else {
                suggestionsBox.style.display = 'none';
            }
        }
        
        // Charger les filtres depuis l'URL au chargement de la page
        function loadFiltersFromUrl() {
            const params = new URLSearchParams(window.location.search);
            
            if (filterCategorie && params.has('categorie')) {
                const categorie = decodeURIComponent(params.get('categorie'));
                filterCategorie.value = categorie;
            }
            
            if (searchInput && params.has('recherche')) {
                searchInput.value = decodeURIComponent(params.get('recherche'));
            }
            
            // Appliquer les filtres
            filterTable();
        }
        
        // Écouteurs d'événements
        if (filterCategorie) filterCategorie.addEventListener('change', filterTable);
        
        if (searchInput) {
            // Gestion de la recherche avec suggestions
            searchInput.addEventListener('input', function() {
                showSuggestions();
                // Utiliser un debounce pour éviter de filtrer à chaque frappe
                clearTimeout(this.timer);
                this.timer = setTimeout(() => filterTable(), 300);
            });
            
            // Cacher les suggestions quand on clique ailleurs
            document.addEventListener('click', function(e) {
                if (e.target !== searchInput && e.target !== suggestionsBox) {
                    suggestionsBox.style.display = 'none';
                }
            });
        }
        
        // Charger les filtres depuis l'URL au chargement de la page
        loadFiltersFromUrl();
        
        // Gérer les boutons de suppression avec confirmation
        document.querySelectorAll('.btn-delete').forEach(button => {
            button.addEventListener('click', function(e) {
                if (!confirm('Êtes-vous sûr de vouloir supprimer ce lieu ? Cette action est irréversible.')) {
                    e.preventDefault();
                }
            });
        });

        if (btnRechercher) {
            btnRechercher.addEventListener('click', function(e) {
                e.preventDefault();
                let url = 'admin-places.php';
                const params = [];
                if (filterVille && filterVille.value) {
                    params.push('ville_id=' + encodeURIComponent(filterVille.value));
                }
                if (filterCategorie && filterCategorie.value) {
                    params.push('categorie=' + encodeURIComponent(filterCategorie.value));
                }
                if (searchInput && searchInput.value) {
                    params.push('recherche=' + encodeURIComponent(searchInput.value));
                }
                if (params.length > 0) {
                    url += '?' + params.join('&');
                }
                window.location.href = url;
            });
        }

        // Désactiver le rechargement automatique sur changement de filtre
        if (filterVille) {
            filterVille.onchange = null;
        }
        if (filterCategorie) {
            filterCategorie.onchange = null;
        }
    });
    </script>
</body>
</html> 