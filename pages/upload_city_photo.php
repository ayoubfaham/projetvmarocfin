<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    exit(json_encode(['error' => 'Accès non autorisé']));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    exit(json_encode(['error' => 'Méthode non autorisée']));
}

if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    header('HTTP/1.1 400 Bad Request');
    exit(json_encode(['error' => 'Aucun fichier uploadé ou erreur durant l\'upload']));
}

$file = $_FILES['photo'];
$allowed = ['jpg', 'jpeg', 'png', 'webp'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!in_array($ext, $allowed)) {
    header('HTTP/1.1 400 Bad Request');
    exit(json_encode(['error' => 'Type de fichier non autorisé']));
}

$uploadDir = '../uploads/cities/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$filename = uniqid('city_', true) . '.' . $ext;
$uploadPath = $uploadDir . $filename;

if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'url' => 'uploads/cities/' . $filename
    ]);
} else {
    header('HTTP/1.1 500 Internal Server Error');
    exit(json_encode(['error' => 'Erreur lors de l\'enregistrement du fichier']));
} 