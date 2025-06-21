<?php

function handleImageUpload($files, $uploadDir, $allowedTypes = ['jpg', 'jpeg', 'png', 'webp']) {
    $uploadedFiles = [];
    $errors = [];
    
    // Créer le répertoire s'il n'existe pas
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0775, true)) {
            return [
                'success' => false,
                'error' => "Impossible de créer le dossier d'upload"
            ];
        }
    }
    
    // Vérifier les permissions du dossier
    if (!is_writable($uploadDir)) {
        return [
            'success' => false,
            'error' => "Le dossier d'upload n'est pas accessible en écriture"
        ];
    }
    
    foreach ($files['name'] as $key => $name) {
        if ($files['error'][$key] === UPLOAD_ERR_OK) {
            $tmp_name = $files['tmp_name'][$key];
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            
            // Vérifier le type de fichier
            if (!in_array($ext, $allowedTypes)) {
                $errors[] = "Le type de fichier '$ext' n'est pas autorisé pour '$name'";
                continue;
            }
            
            // Vérifier si c'est vraiment une image
            if (!getimagesize($tmp_name)) {
                $errors[] = "Le fichier '$name' n'est pas une image valide";
                continue;
            }
            
            // Générer un nom de fichier unique
            $newName = uniqid('img_', true) . '.' . $ext;
            $targetPath = $uploadDir . '/' . $newName;
            
            // Déplacer le fichier
            if (move_uploaded_file($tmp_name, $targetPath)) {
                $uploadedFiles[] = str_replace('../', '', $targetPath);
            } else {
                $errors[] = "Erreur lors du déplacement de '$name'";
            }
        } elseif ($files['error'][$key] !== UPLOAD_ERR_NO_FILE) {
            $errors[] = "Erreur lors de l'upload de '$name': Code " . $files['error'][$key];
        }
    }
    
    return [
        'success' => !empty($uploadedFiles),
        'files' => $uploadedFiles,
        'errors' => $errors
    ];
}

function deleteImage($imagePath) {
    $fullPath = '../' . $imagePath;
    if (file_exists($fullPath) && is_file($fullPath)) {
        if (unlink($fullPath)) {
            return true;
        }
    }
    return false;
} 