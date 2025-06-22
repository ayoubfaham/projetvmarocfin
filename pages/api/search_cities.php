<?php
header('Content-Type: application/json');

try {
    $pdo = new PDO("mysql:host=localhost;dbname=vmaroc;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(['error' => 'Database connection failed']);
    http_response_code(500);
    exit();
}

$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

$sql = "SELECT id, nom, description, hero_images FROM villes";
$params = [];

if (!empty($search_query)) {
    $sql .= " WHERE nom LIKE :search OR description LIKE :search";
    $params[':search'] = '%' . $search_query . '%';
}

$sql .= " ORDER BY nom ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$cities = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($cities);
?>
