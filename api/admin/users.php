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

// Récupérer tous les utilisateurs (GET)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->query("SELECT * FROM users");
    echo json_encode($stmt->fetchAll());
    exit;
}

// Ajouter un utilisateur (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['username'], $data['password'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Nom d\'utilisateur et mot de passe requis']);
        exit;
    }
    $hash = password_hash($data['password'], PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    $stmt->execute([$data['username'], $hash]);
    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    exit;
}

// Modifier un utilisateur (PUT)
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['id'], $data['username'])) {
        http_response_code(400);
        echo json_encode(['error' => 'ID et nom d\'utilisateur requis']);
        exit;
    }
    $fields = [ $data['username'] ];
    $sql = "UPDATE users SET username = ?";
    if (!empty($data['password'])) {
        $sql .= ", password = ?";
        $fields[] = password_hash($data['password'], PASSWORD_DEFAULT);
    }
    $sql .= " WHERE id = ?";
    $fields[] = $data['id'];
    $stmt = $pdo->prepare($sql);
    $stmt->execute($fields);
    echo json_encode(['success' => true]);
    exit;
}

// Supprimer un utilisateur (DELETE)
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'ID requis']);
        exit;
    }
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$data['id']]);
    echo json_encode(['success' => true]);
    exit;
}

// Si la méthode n'est pas gérée
http_response_code(405);
echo json_encode(['error' => 'Méthode non autorisée']); 