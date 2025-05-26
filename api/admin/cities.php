<?php
session_start();
header('Content-Type: application/json');

// Sécurité : vérifie que l'admin est connecté
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

require_once '../../config/database.php';

// Récupérer toutes les villes (GET)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->query("SELECT * FROM cities");
    echo json_encode($stmt->fetchAll());
    exit;
}

// Ajouter une ville (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['name'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Nom de ville requis']);
        exit;
    }
    $stmt = $pdo->prepare("INSERT INTO cities (name) VALUES (?)");
    $stmt->execute([$data['name']]);
    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    exit;
}

// Modifier une ville (PUT)
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['id'], $data['name'])) {
        http_response_code(400);
        echo json_encode(['error' => 'ID et nom requis']);
        exit;
    }
    $stmt = $pdo->prepare("UPDATE cities SET name = ? WHERE id = ?");
    $stmt->execute([$data['name'], $data['id']]);
    echo json_encode(['success' => true]);
    exit;
}

// Supprimer une ville (DELETE)
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'ID requis']);
        exit;
    }
    $stmt = $pdo->prepare("DELETE FROM cities WHERE id = ?");
    $stmt->execute([$data['id']]);
    echo json_encode(['success' => true]);
    exit;
}

// Si la méthode n'est pas gérée
http_response_code(405);
echo json_encode(['error' => 'Méthode non autorisée']); 