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

if ($ville_filter > 0) {
    // Filtrer les lieux par ville si un ID de ville est spécifié
    $stmt = $pdo->prepare("SELECT lieux.*, villes.nom AS ville_nom FROM lieux JOIN villes ON lieux.id_ville = villes.id WHERE lieux.id_ville = ?");
    $stmt->execute([$ville_filter]);
    $places = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer le nom de la ville filtrée pour l'afficher
    $stmt = $pdo->prepare("SELECT nom FROM villes WHERE id = ?");
    $stmt->execute([$ville_filter]);
    $filtered_city_name = $stmt->fetchColumn();
} else {
    // Afficher tous les lieux si aucun filtre n'est spécifié
    $places = $pdo->query("SELECT lieux.*, villes.nom AS ville_nom FROM lieux JOIN villes ON lieux.id_ville = villes.id")->fetchAll(PDO::FETCH_ASSOC);
}

$cities = $pdo->query("SELECT * FROM villes")->fetchAll(PDO::FETCH_ASSOC);

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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/main.css">
    <style>
        body, .admin-table, .admin-table td, .admin-table th, .form-group, .form-group input, .form-group select, .form-group textarea {
            font-size: 0.91rem;
        }
        
        /* Styles modernes pour les messages d'administration */
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
            content: '\f058'; /* check-circle */
            color: #28a745;
        }
        
        .admin-message-error {
            background-color: #fef0f0;
            color: #b02a37;
            border-color: #dc3545;
        }
        
        .admin-message-error::before {
            content: '\f057'; /* times-circle */
            color: #dc3545;
        }
        
        .admin-message-warning {
            background-color: #fff8e6;
            color: #997404;
            border-color: #ffc107;
        }
        
        .admin-message-warning::before {
            content: '\f071'; /* exclamation-triangle */
            color: #ffc107;
        }
        
        .admin-message-info {
            background-color: #e6f3ff;
            color: #0a58ca;
            border-color: #0d6efd;
        }
        
        .admin-message-info::before {
            content: '\f05a'; /* info-circle */
            color: #0d6efd;
        }
        
        /* Animation pour les messages */
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
        .place-thumb {
            width: 48px;
            height: 48px;
            object-fit: cover;
            border-radius: 8px;
            background: #eee;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .btn-delete {
            background: #dc3545;
            color: #fff;
            border: none;
            padding: 8px 18px;
            border-radius: 6px;
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
        .admin-filters-bar {
            display: flex;
            gap: 18px;
            align-items: center;
            justify-content: center;
            margin: 0 0 32px 0;
            flex-wrap: wrap;
        }
        .admin-filters-bar select {
            padding: 10px 18px;
            border: 1.5px solid var(--primary-color, #b48a3c);
            border-radius: 8px;
            background: #faf9f7;
            color: #222;
            font-size: 1.05rem;
            box-shadow: 0 2px 8px rgba(180,138,60,0.07);
            transition: border 0.2s, box-shadow 0.2s;
            outline: none;
            min-width: 180px;
        }
        .admin-filters-bar select:focus {
            border-color: var(--accent-color, #d4af37);
            box-shadow: 0 4px 16px rgba(180,138,60,0.13);
            background: #fff;
        }
        .search-bar-admin {
            max-width: 400px;
            margin: 0;
            position: relative;
        }
        .search-bar-admin input[type="text"] {
            width: 100%;
            padding: 12px 16px;
            border: 1.5px solid var(--primary-color, #b48a3c);
            border-radius: 8px;
            font-size: 1.08rem;
            background: #faf9f7;
            color: #222;
            box-shadow: 0 2px 8px rgba(180,138,60,0.07);
            transition: border 0.2s, box-shadow 0.2s;
            outline: none;
        }
        .search-bar-admin input[type="text"]:focus {
            border-color: var(--accent-color, #d4af37);
            box-shadow: 0 4px 16px rgba(180,138,60,0.13);
            background: #fff;
        }
        #suggestionsLieu {
            position: absolute;
            z-index: 10;
            width: 100%;
            background: #fff;
            border: 1.5px solid var(--primary-color, #b48a3c);
            border-top: none;
            border-radius: 0 0 8px 8px;
            box-shadow: 0 8px 24px rgba(180,138,60,0.10);
            display: none;
            max-height: 220px;
            overflow-y: auto;
        }
        #suggestionsLieu div {
            padding: 12px 16px;
            cursor: pointer;
            font-size: 1.05rem;
            color: #222;
            transition: background 0.15s;
        }
        #suggestionsLieu div:hover {
            background: #f7f3e7;
            color: var(--primary-color, #b48a3c);
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
            <?php if (isset($message)): ?>
                <div class="admin-message admin-message-<?= $message_type ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
            <div class="section-title">
                <h2>Gestion des lieux</h2>
                <?php if (isset($ville_filter) && $ville_filter > 0 && isset($filtered_city_name)): ?>
                <div style="text-align: center; margin-top: 10px;">
                    <p style="color: var(--primary-color); font-weight: 500;">Lieux filtrés pour la ville de <strong><?= htmlspecialchars($filtered_city_name) ?></strong></p>
                    <a href="admin-places.php" class="btn-outline" style="display: inline-block; margin-top: 10px;">Voir tous les lieux</a>
                </div>
                <?php endif; ?>
            </div>
            <section class="section">
                <div class="form" style="max-width:600px;margin:0 auto 40px auto;">
                    <h3 style="text-align:center;">
                        <?= isset($editMode) && $editMode ? 'Modifier le lieu' : 'Ajouter un lieu' ?>
                    </h3>
        <form method="post" enctype="multipart/form-data">
                        <?php if (isset($editMode) && $editMode): ?>
                            <input type="hidden" name="edit_id" value="<?= htmlspecialchars($editPlace['id']) ?>">
                        <?php endif; ?>
                        <div class="form-group">
                            <input type="text" name="nom" class="form-control" placeholder="Nom du lieu" required value="<?= isset($editPlace) ? htmlspecialchars($editPlace['nom']) : '' ?>">
                        </div>
                        <div class="form-group">
                            <label for="hero_images_upload">Images pour le Hero Slider (plusieurs fichiers possibles) :</label>
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
                        <div class="form-group">
                            <input type="text" name="description" class="form-control" placeholder="Description" required value="<?= isset($editPlace) ? htmlspecialchars($editPlace['description']) : '' ?>">
                        </div>
                        <div class="form-group">
                            <input type="text" name="url_activite" class="form-control" placeholder="URL de l'activité (ex: https://www.hotel.com)" value="<?= isset($editPlace) ? htmlspecialchars($editPlace['url_activite'] ?? '') : '' ?>">
                        </div>
                        <div class="form-group">
                            <select name="id_ville" class="form-control" required>
                                <option value="">Ville</option>
                                <?php foreach ($cities as $city): ?>
                                    <option value="<?= $city['id'] ?>" <?= (isset($editPlace) && $editPlace['id_ville'] == $city['id']) || (!isset($editPlace) && isset($ville_filter) && $ville_filter == $city['id']) ? 'selected' : '' ?>><?= htmlspecialchars($city['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <select name="categorie" class="form-control" required>
                                <option value="">Catégorie</option>
                                <?php
                                // Utiliser le même tableau de catégories que pour le filtre
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
                                
                                // Trier les catégories par ordre alphabétique de leur nom d'affichage
                                asort($category_display_names);
                                
                                foreach ($category_display_names as $cat_value => $cat_display_name) {
                                    $selected = (isset($editPlace) && strtolower($editPlace['categorie']) == strtolower($cat_value)) ? 'selected' : '';
                                    echo "<option value=\"$cat_value\" $selected>$cat_display_name</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <button type="submit" class="btn-solid" style="width:100%;">
                            <?= isset($editMode) && $editMode ? 'Enregistrer' : 'Ajouter' ?>
                        </button>
                        <?php if (isset($editMode) && $editMode): ?>
                            <a href="admin-places.php" class="btn-outline" style="min-width:100px;">Annuler</a>
                        <?php endif; ?>
        </form>
                </div>

                <div class="section-title">
                    <h3>Liste des lieux</h3>
                </div>
                <!-- Filtres combinés -->
                <div class="admin-filters-bar">
                    <select id="filterVille" class="form-control">
                        <option value="">Toutes les villes</option>
                        <?php foreach ($cities as $city): ?>
                            <option value="<?= htmlspecialchars($city['nom']) ?>"><?= htmlspecialchars($city['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select id="filterCategorie" class="form-control">
                        <option value="">Toutes les catu00e9gories</option>
                        <?php
                        // Define a mapping for display names (avec normalisation)
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
                            // Add any other categories here
                        ];
                        
                        // Collect unique categories from existing places in the database
                        $stmt = $pdo->query("SELECT DISTINCT categorie FROM lieux WHERE categorie IS NOT NULL AND categorie != ''");
                        $db_categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
                        
                        // Normaliser les catégories de la base de données (minuscules)
                        $normalized_db_categories = [];
                        foreach ($db_categories as $cat) {
                            $normalized_db_categories[] = strtolower($cat);
                        }
                        
                        // Créer un tableau unique de catégories normalisées
                        $all_categories = array_unique(array_merge(array_keys($category_display_names), $normalized_db_categories));
                        sort($all_categories);

                        // Utiliser uniquement les catégories définies dans $category_display_names pour éviter les doublons
                        // Trier les catégories par ordre alphabétique de leur nom d'affichage
                        asort($category_display_names);
                        
                        foreach ($category_display_names as $cat_value => $cat_display_name) {
                            echo '<option value="' . htmlspecialchars($cat_value) . '">' . htmlspecialchars($cat_display_name) . '</option>';
                        }
                         ?>
                    </select>
                    <div class="search-bar-admin">
                        <input type="text" id="searchLieu" class="form-control" placeholder="Rechercher un lieu..." autocomplete="off">
                        <div id="suggestionsLieu"></div>
                    </div>
                </div>
                <div style="overflow-x:auto;">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nom</th>
                                <th>Hero Photos</th>
                                <th>Description</th>
                                <th>Ville</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
            <?php if (empty($places)): ?>
                <tr><td colspan="6" style="text-align:center;color:var(--secondary-color);">Aucun lieu enregistré.</td></tr>
            <?php endif; ?>
            <?php foreach ($places as $place): ?>
                <tr data-ville="<?= htmlspecialchars($place['ville_nom']) ?>" data-categorie="<?= htmlspecialchars($place['categorie'] ?? '') ?>">
                                    <td data-label="ID"><?= $place['id'] ?></td>
                                    <td data-label="Nom"><?= htmlspecialchars($place['nom']) ?></td>
                                    <td data-label="Hero Photos">
                                        <?php
                                        $hero_images = [];
                                        if (!empty($place['hero_images'])) {
                                            $hero_images = array_map('trim', explode(',', $place['hero_images']));
                                        }
                                        
                                        if (!empty($hero_images)) {
                                            foreach ($hero_images as $img_path) {
                                                if (!empty($img_path)) {
                                                     // Assuming images are in ../uploads/lieux/hero/ or similar accessible path
                                                    echo '<img src="../' . htmlspecialchars($img_path) . '" alt="Hero Image" style="width: 40px; height: 40px; object-fit: cover; margin-right: 5px; border-radius: 4px;">';
                                                }
                                            }
                                        } else {
                                            echo '<span style="color:var(--secondary-color);font-size:0.9em;">Aucune</span>';
                                        }
                                        ?>
                                    </td>
                                    <td data-label="Description"><?= htmlspecialchars($place['description']) ?></td>
                                    <td data-label="Ville"><?= htmlspecialchars($place['ville_nom']) ?></td>
                                    <td data-label="Action">
                                        <div class="admin-actions">
                                            <a href="?edit=<?= $place['id'] ?>" class="btn-outline" style="padding:4px 12px;font-size:0.9rem;">Modifier</a>
                                            <a href="?delete=<?= $place['id'] ?>" class="btn-delete" style="padding:4px 12px;font-size:0.9rem;" onclick="return confirm('Supprimer ce lieu ?')">Supprimer</a>
                                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
                        </tbody>
        </table>
    </div>
            </section>
        </div>
    </main>
    <script>
    // Récupérer tous les noms de lieux côté JS
    const lieux = [
        <?php foreach ($places as $place): ?>
            "<?= addslashes($place['nom']) ?>",
        <?php endforeach; ?>
    ];
    const tableRows = document.querySelectorAll('.admin-table tbody tr');
    const searchInput = document.getElementById('searchLieu');
    const suggestionsBox = document.getElementById('suggestionsLieu');

    searchInput.addEventListener('input', function() {
        const val = this.value.toLowerCase();
        suggestionsBox.innerHTML = '';
        if (val.length === 0) {
            suggestionsBox.style.display = 'none';
            tableRows.forEach(row => row.style.display = '');
            return;
        }
        // Suggestions
        const suggestions = lieux.filter(nom => nom.toLowerCase().includes(val));
        if (suggestions.length > 0) {
            suggestionsBox.style.display = 'block';
            suggestions.forEach(sugg => {
                const div = document.createElement('div');
                div.textContent = sugg;
                div.style.padding = '8px 12px';
                div.style.cursor = 'pointer';
                div.addEventListener('mousedown', function(e) {
                    e.preventDefault();
                    searchInput.value = sugg;
                    suggestionsBox.style.display = 'none';
                    // Filtrer la table
                    tableRows.forEach(row => {
                        const nomCell = row.querySelector('td[data-label="Nom"]');
                        if (nomCell && nomCell.textContent.trim() === sugg) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                });
                suggestionsBox.appendChild(div);
            });
        } else {
            suggestionsBox.style.display = 'none';
        }
        // Filtrage live (affiche toutes les lignes qui contiennent la lettre)
        tableRows.forEach(row => {
            const nomCell = row.querySelector('td[data-label="Nom"]');
            if (nomCell && nomCell.textContent.toLowerCase().includes(val)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
    // Cacher suggestions si clic ailleurs
    window.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !suggestionsBox.contains(e.target)) {
            suggestionsBox.style.display = 'none';
        }
    });

    // Filtres combinés ville + catégorie
    const filterCategorie = document.getElementById('filterCategorie');
    function applyFilters() {
        const ville = filterVille.value;
        const categorie = filterCategorie.value;
        tableRows.forEach(row => {
            const rowVille = row.getAttribute('data-ville');
            const rowCat = row.getAttribute('data-categorie');
            const villeOk = !ville || rowVille === ville;
            const catOk = !categorie || rowCat === categorie;
            row.style.display = (villeOk && catOk) ? '' : 'none';
        });
        // Réinitialise la recherche
        searchInput.value = '';
        suggestionsBox.style.display = 'none';
    }
    filterVille.addEventListener('change', applyFilters);
    filterCategorie.addEventListener('change', applyFilters);
    </script>
</body>
</html> 