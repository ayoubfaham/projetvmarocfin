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

// Récupérer tous les lieux (GET)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->query("SELECT * FROM places");
    echo json_encode($stmt->fetchAll());
    exit;
}

// Ajouter un lieu (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['name'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Nom du lieu requis']);
        exit;
    }
    $stmt = $pdo->prepare("INSERT INTO places (name) VALUES (?)");
    $stmt->execute([$data['name']]);
    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    exit;
}

// Modifier un lieu (PUT)
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['id'], $data['name'])) {
        http_response_code(400);
        echo json_encode(['error' => 'ID et nom requis']);
        exit;
    }
    $stmt = $pdo->prepare("UPDATE places SET name = ? WHERE id = ?");
    $stmt->execute([$data['name'], $data['id']]);
    echo json_encode(['success' => true]);
    exit;
}

// Supprimer un lieu (DELETE)
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'ID requis']);
        exit;
    }
    $stmt = $pdo->prepare("DELETE FROM places WHERE id = ?");
    $stmt->execute([$data['id']]);
    echo json_encode(['success' => true]);
    exit;
}

// Si la méthode n'est pas gérée
http_response_code(405);
echo json_encode(['error' => 'Méthode non autorisée']); 