<?php
session_start();
require_once 'config/database.php';

// Récupération des recommandations uniques
$recommandations = [
    [
        'title' => 'Séjour dans le désert',
        'description' => 'Vivez une expérience inoubliable dans le désert du Sahara. Nuit sous les étoiles, promenade en dromadaire et découverte de la culture berbère.',
        'image' => 'https://images.unsplash.com/photo-1518546305927-5a555bb7020d?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80',
        'location' => 'Merzouga'
    ],
    // ... autres recommandations ...
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recommendations - Maroc Authentique</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/main.css">
    <style>
    body, .recommandation-content, .recommandation-content p, .recommandation-location {
      font-size: 0.91rem;
    }
    .recommandation-content h3 {
      font-size: 1.18rem;
      font-weight: bold;
      margin-bottom: 6px;
      color: #222;
    }
    </style>
</head>