<?php
require_once 'config/database.php';

$stmt = $pdo->prepare("SELECT nom, photo FROM villes WHERE LOWER(nom) IN ('marrakech', 'tanger', 'casablanca')");
$stmt->execute();

while($row = $stmt->fetch()) {
    echo $row['nom'] . ': ' . $row['photo'] . "\n";
}
