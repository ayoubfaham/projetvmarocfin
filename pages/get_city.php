<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    exit('Accès non autorisé');
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=vmaroc", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    exit('Erreur de connexion à la base de données');
}

if (!isset($_GET['id'])) {
    header('HTTP/1.1 400 Bad Request');
    exit('ID de ville manquant');
}

$id = intval($_GET['id']);

try {
    $stmt = $pdo->prepare("SELECT id, nom, photo, description, hero_images FROM villes WHERE id = ?");
    $stmt->execute([$id]);
    $city = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$city) {
        header('HTTP/1.1 404 Not Found');
        exit('Ville non trouvée');
    }

    header('Content-Type: application/json');
    echo json_encode($city);
} catch(PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    exit('Erreur lors de la récupération des données');
} 