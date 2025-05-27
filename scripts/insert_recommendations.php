<?php
require_once '../config/database.php';

// Données initiales pour les recommandations
$recommendations = [
    // MARRAKECH (ID 1)
    // Culture et Histoire
    [
        'ville_id' => 1,
        'titre' => 'Visite des Jardins Majorelle',
        'description' => 'Découvrez ce magnifique jardin créé par Jacques Majorelle et restauré par Yves Saint Laurent.',
        'categorie' => 'culture',
        'prix_min' => 800,
        'prix_max' => 1200,
        'duree_min' => 1,
        'duree_max' => 2,
        'image_url' => 'https://images.unsplash.com/photo-1535320485706-44d43b919500?auto=format&fit=crop&w=800&q=80'
    ],
    [
        'ville_id' => 1,
        'titre' => 'Palais Bahia',
        'description' => 'Explorez ce palais du 19ème siècle avec ses jardins, cours et salles décorées.',
        'categorie' => 'culture',
        'prix_min' => 500,
        'prix_max' => 900,
        'duree_min' => 1,
        'duree_max' => 1,
        'image_url' => 'https://images.unsplash.com/photo-1548019979-e5c3c08bacc8?auto=format&fit=crop&w=800&q=80'
    ],
    [
        'ville_id' => 1,
        'titre' => 'Musée Yves Saint Laurent',
        'description' => 'Découvrez l\'œuvre du grand couturier et son lien avec Marrakech.',
        'categorie' => 'culture',
        'prix_min' => 700,
        'prix_max' => 1000,
        'duree_min' => 1,
        'duree_max' => 1,
        'image_url' => 'https://images.unsplash.com/photo-1548019979-e5c3c08bacc8?auto=format&fit=crop&w=800&q=80'
    ],
    
    // Nature et Randonnées
    [
        'ville_id' => 1,
        'titre' => 'Excursion dans la vallée de l\'Ourika',
        'description' => 'Randonnée dans la vallée de l\'Ourika avec ses cascades et villages berbères.',
        'categorie' => 'nature',
        'prix_min' => 1500,
        'prix_max' => 3000,
        'duree_min' => 1,
        'duree_max' => 3,
        'image_url' => 'https://images.unsplash.com/photo-1518730518541-d0843268c287?auto=format&fit=crop&w=800&q=80'
    ],
    [
        'ville_id' => 1,
        'titre' => 'Jardin Menara',
        'description' => 'Promenade dans ce jardin historique avec son grand bassin et son pavillon.',
        'categorie' => 'nature',
        'prix_min' => 400,
        'prix_max' => 800,
        'duree_min' => 1,
        'duree_max' => 1,
        'image_url' => 'https://images.unsplash.com/photo-1518730518541-d0843268c287?auto=format&fit=crop&w=800&q=80'
    ],
    
    // Gastronomie
    [
        'ville_id' => 1,
        'titre' => 'Cours de cuisine marocaine',
        'description' => 'Apprenez à préparer tajines, couscous et pâtisseries avec un chef local.',
        'categorie' => 'gastronomie',
        'prix_min' => 1200,
        'prix_max' => 2500,
        'duree_min' => 1,
        'duree_max' => 2,
        'image_url' => 'https://images.unsplash.com/photo-1511690656952-34342bb7c2f2?auto=format&fit=crop&w=800&q=80'
    ],
    [
        'ville_id' => 1,
        'titre' => 'Tour gastronomique des souks',
        'description' => 'Découvrez les saveurs locales en visitant les étals alimentaires des souks.',
        'categorie' => 'gastronomie',
        'prix_min' => 900,
        'prix_max' => 1800,
        'duree_min' => 1,
        'duree_max' => 1,
        'image_url' => 'https://images.unsplash.com/photo-1511690656952-34342bb7c2f2?auto=format&fit=crop&w=800&q=80'
    ],
    
    // Shopping
    [
        'ville_id' => 1,
        'titre' => 'Exploration des souks',
        'description' => 'Découvrez les souks traditionnels et leurs artisans : tapis, épices, bijoux et objets en cuivre.',
        'categorie' => 'shopping',
        'prix_min' => 500,
        'prix_max' => 3000,
        'duree_min' => 1,
        'duree_max' => 2,
        'image_url' => 'https://images.unsplash.com/photo-1489493585363-d69421e0edd3?auto=format&fit=crop&w=800&q=80'
    ],
    [
        'ville_id' => 1,
        'titre' => 'Atelier d\'artisanat local',
        'description' => 'Visitez un atelier d\'artisanat et apprenez les techniques traditionnelles.',
        'categorie' => 'shopping',
        'prix_min' => 800,
        'prix_max' => 1500,
        'duree_min' => 1,
        'duree_max' => 1,
        'image_url' => 'https://images.unsplash.com/photo-1489493585363-d69421e0edd3?auto=format&fit=crop&w=800&q=80'
    ],
    
    // Plage et Détente
    [
        'ville_id' => 1,
        'titre' => 'Hammam traditionnel',
        'description' => 'Détendez-vous dans un hammam traditionnel avec gommage et massage à l\'huile d\'argan.',
        'categorie' => 'plage',
        'prix_min' => 1000,
        'prix_max' => 2000,
        'duree_min' => 1,
        'duree_max' => 1,
        'image_url' => 'https://images.unsplash.com/photo-1565689478170-6a0dbc6cbf6f?auto=format&fit=crop&w=800&q=80'
    ],
    
    // Famille
    [
        'ville_id' => 1,
        'titre' => 'Balade en calèche',
        'description' => 'Tour de la ville en calèche traditionnelle, idéal pour les familles.',
        'categorie' => 'famille',
        'prix_min' => 800,
        'prix_max' => 1500,
        'duree_min' => 1,
        'duree_max' => 2,
        'image_url' => 'https://images.unsplash.com/photo-1565689478170-6a0dbc6cbf6f?auto=format&fit=crop&w=800&q=80'
    ],
    [
        'ville_id' => 1,
        'titre' => 'Parc aquatique Oasiria',
        'description' => 'Journée de détente en famille dans ce parc aquatique avec toboggans et piscines.',
        'categorie' => 'famille',
        'prix_min' => 1500,
        'prix_max' => 2500,
        'duree_min' => 1,
        'duree_max' => 1,
        'image_url' => 'https://images.unsplash.com/photo-1565689478170-6a0dbc6cbf6f?auto=format&fit=crop&w=800&q=80'
    ],
    
    // FÈS (ID 2)
    // Culture et Histoire
    [
        'ville_id' => 2,
        'titre' => 'Visite de la Médina de Fès',
        'description' => 'Explorez la plus grande médina piétonne au monde, classée au patrimoine mondial de l\'UNESCO.',
        'categorie' => 'culture',
        'prix_min' => 700,
        'prix_max' => 1500,
        'duree_min' => 1,
        'duree_max' => 3,
        'image_url' => 'https://images.unsplash.com/photo-1548019979-e5c3c08bacc8?auto=format&fit=crop&w=800&q=80'
    ],
    [
        'ville_id' => 2,
        'titre' => 'Médersa Bou Inania',
        'description' => 'Visitez cette école coranique du 14ème siècle, chef-d\'œuvre de l\'architecture mérinide.',
        'categorie' => 'culture',
        'prix_min' => 600,
        'prix_max' => 1000,
        'duree_min' => 1,
        'duree_max' => 1,
        'image_url' => 'https://images.unsplash.com/photo-1548019979-e5c3c08bacc8?auto=format&fit=crop&w=800&q=80'
    ],
    [
        'ville_id' => 2,
        'titre' => 'Musée Nejjarine',
        'description' => 'Découvrez ce musée des arts et métiers du bois installé dans un ancien caravansérail.',
        'categorie' => 'culture',
        'prix_min' => 500,
        'prix_max' => 900,
        'duree_min' => 1,
        'duree_max' => 1,
        'image_url' => 'https://images.unsplash.com/photo-1548019979-e5c3c08bacc8?auto=format&fit=crop&w=800&q=80'
    ],
    
    // Nature et Randonnées
    [
        'ville_id' => 2,
        'titre' => 'Jardins Jnan Sbil',
        'description' => 'Promenade dans ces jardins historiques datant du 18ème siècle.',
        'categorie' => 'nature',
        'prix_min' => 400,
        'prix_max' => 800,
        'duree_min' => 1,
        'duree_max' => 1,
        'image_url' => 'https://images.unsplash.com/photo-1518730518541-d0843268c287?auto=format&fit=crop&w=800&q=80'
    ],
    [
        'ville_id' => 2,
        'titre' => 'Excursion au Mont Zalagh',
        'description' => 'Randonnée offrant une vue panoramique sur la ville de Fès.',
        'categorie' => 'nature',
        'prix_min' => 900,
        'prix_max' => 1800,
        'duree_min' => 1,
        'duree_max' => 2,
        'image_url' => 'https://images.unsplash.com/photo-1518730518541-d0843268c287?auto=format&fit=crop&w=800&q=80'
    ],
    
    // Gastronomie
    [
        'ville_id' => 2,
        'titre' => 'Dégustation de spécialités fassi',
        'description' => 'Découvrez les spécialités culinaires de Fès comme la pastilla et les tajines locaux.',
        'categorie' => 'gastronomie',
        'prix_min' => 900,
        'prix_max' => 1800,
        'duree_min' => 1,
        'duree_max' => 2,
        'image_url' => 'https://images.unsplash.com/photo-1541518763669-27fef9b49644?auto=format&fit=crop&w=800&q=80'
    ],
    [
        'ville_id' => 2,
        'titre' => 'Cours de pâtisserie marocaine',
        'description' => 'Apprenez à préparer les cornes de gazelle, fekkas et autres douceurs marocaines.',
        'categorie' => 'gastronomie',
        'prix_min' => 1000,
        'prix_max' => 2000,
        'duree_min' => 1,
        'duree_max' => 1,
        'image_url' => 'https://images.unsplash.com/photo-1541518763669-27fef9b49644?auto=format&fit=crop&w=800&q=80'
    ],
    
    // Shopping
    [
        'ville_id' => 2,
        'titre' => 'Artisanat traditionnel',
        'description' => 'Visitez les ateliers de poterie, de tannerie et de tissage traditionnels.',
        'categorie' => 'shopping',
        'prix_min' => 600,
        'prix_max' => 2500,
        'duree_min' => 1,
        'duree_max' => 2,
        'image_url' => 'https://images.unsplash.com/photo-1531501410720-c8d437636169?auto=format&fit=crop&w=800&q=80'
    ],
    [
        'ville_id' => 2,
        'titre' => 'Visite des tanneries',
        'description' => 'Découvrez le processus traditionnel de tannage du cuir dans les célèbres tanneries de Fès.',
        'categorie' => 'shopping',
        'prix_min' => 700,
        'prix_max' => 1200,
        'duree_min' => 1,
        'duree_max' => 1,
        'image_url' => 'https://images.unsplash.com/photo-1531501410720-c8d437636169?auto=format&fit=crop&w=800&q=80'
    ],
    
    // Plage et Détente
    [
        'ville_id' => 2,
        'titre' => 'Spa traditionnel',
        'description' => 'Profitez d\'un moment de détente dans un spa traditionnel avec hammam et massage.',
        'categorie' => 'plage',
        'prix_min' => 1200,
        'prix_max' => 2200,
        'duree_min' => 1,
        'duree_max' => 1,
        'image_url' => 'https://images.unsplash.com/photo-1565689478170-6a0dbc6cbf6f?auto=format&fit=crop&w=800&q=80'
    ],
    
    // Famille
    [
        'ville_id' => 2,
        'titre' => 'Atelier de poterie pour enfants',
        'description' => 'Initiation à la poterie traditionnelle pour toute la famille.',
        'categorie' => 'famille',
        'prix_min' => 800,
        'prix_max' => 1500,
        'duree_min' => 1,
        'duree_max' => 1,
        'image_url' => 'https://images.unsplash.com/photo-1565689478170-6a0dbc6cbf6f?auto=format&fit=crop&w=800&q=80'
    ],
    [
        'ville_id' => 2,
        'titre' => 'Parc Boujloud',
        'description' => 'Moment de détente en famille dans ce parc urbain avec aires de jeux.',
        'categorie' => 'famille',
        'prix_min' => 300,
        'prix_max' => 600,
        'duree_min' => 1,
        'duree_max' => 1,
        'image_url' => 'https://images.unsplash.com/photo-1565689478170-6a0dbc6cbf6f?auto=format&fit=crop&w=800&q=80'
    ],
    
    // CHEFCHAOUEN (ID 3)
    // Culture et Histoire
    [
        'ville_id' => 3,
        'titre' => 'Visite de la Kasbah',
        'description' => 'Découvrez la forteresse et le musée ethnographique au cœur de la ville bleue.',
        'categorie' => 'culture',
        'prix_min' => 500,
        'prix_max' => 900,
        'duree_min' => 1,
        'duree_max' => 1,
        'image_url' => 'https://images.unsplash.com/photo-1548019979-e5c3c08bacc8?auto=format&fit=crop&w=800&q=80'
    ],
    [
        'ville_id' => 3,
        'titre' => 'Exploration de la médina bleue',
        'description' => 'Promenade guidée dans les ruelles bleues emblématiques de Chefchaouen.',
        'categorie' => 'culture',
        'prix_min' => 600,
        'prix_max' => 1200,
        'duree_min' => 1,
        'duree_max' => 2,
        'image_url' => 'https://images.unsplash.com/photo-1548019979-e5c3c08bacc8?auto=format&fit=crop&w=800&q=80'
    ],
    
    // Nature et Randonnées
    [
        'ville_id' => 3,
        'titre' => 'Randonnée au Parc National de Talassemtane',
        'description' => 'Explorez les montagnes du Rif et leurs forêts de cèdres et de sapins.',
        'categorie' => 'nature',
        'prix_min' => 1200,
        'prix_max' => 2800,
        'duree_min' => 1,
        'duree_max' => 3,
        'image_url' => 'https://images.unsplash.com/photo-1518730518541-d0843268c287?auto=format&fit=crop&w=800&q=80'
    ],
    [
        'ville_id' => 3,
        'titre' => 'Cascade d\'Akchour',
        'description' => 'Randonnée vers la magnifique cascade et le Pont de Dieu, formations rocheuses naturelles.',
        'categorie' => 'nature',
        'prix_min' => 900,
        'prix_max' => 1800,
        'duree_min' => 1,
        'duree_max' => 2,
        'image_url' => 'https://images.unsplash.com/photo-1518730518541-d0843268c287?auto=format&fit=crop&w=800&q=80'
    ],
    [
        'ville_id' => 3,
        'titre' => 'Source Ras El Maa',
        'description' => 'Visite de cette source d\'eau fraîche où les locaux lavent leur linge de façon traditionnelle.',
        'categorie' => 'nature',
        'prix_min' => 400,
        'prix_max' => 800,
        'duree_min' => 1,
        'duree_max' => 1,
        'image_url' => 'https://images.unsplash.com/photo-1518730518541-d0843268c287?auto=format&fit=crop&w=800&q=80'
    ],
    
    // Gastronomie
    [
        'ville_id' => 3,
        'titre' => 'Dégustation de fromage de chèvre local',
        'description' => 'Goûtez au fromage de chèvre traditionnel produit dans les montagnes du Rif.',
        'categorie' => 'gastronomie',
        'prix_min' => 600,
        'prix_max' => 1200,
        'duree_min' => 1,
        'duree_max' => 1,
        'image_url' => 'https://images.unsplash.com/photo-1511690656952-34342bb7c2f2?auto=format&fit=crop&w=800&q=80'
    ],
    [
        'ville_id' => 3,
        'titre' => 'Repas chez l\'habitant',
        'description' => 'Partagez un repas traditionnel dans une maison locale et découvrez la cuisine rifaine.',
        'categorie' => 'gastronomie',
        'prix_min' => 800,
        'prix_max' => 1500,
        'duree_min' => 1,
        'duree_max' => 1,
        'image_url' => 'https://images.unsplash.com/photo-1511690656952-34342bb7c2f2?auto=format&fit=crop&w=800&q=80'
    ],
    
    // Shopping
    [
        'ville_id' => 3,
        'titre' => 'Artisanat local',
        'description' => 'Découvrez les tissages, poteries et objets en cuir fabriqués par les artisans locaux.',
        'categorie' => 'shopping',
        'prix_min' => 500,
        'prix_max' => 1500,
        'duree_min' => 1,
        'duree_max' => 1,
        'image_url' => 'https://images.unsplash.com/photo-1489493585363-d69421e0edd3?auto=format&fit=crop&w=800&q=80'
    ],
    [
        'ville_id' => 3,
        'titre' => 'Atelier de tissage',
        'description' => 'Visitez un atelier de tissage traditionnel et découvrez les techniques ancestrales.',
        'categorie' => 'shopping',
        'prix_min' => 600,
        'prix_max' => 1200,
        'duree_min' => 1,
        'duree_max' => 1,
        'image_url' => 'https://images.unsplash.com/photo-1489493585363-d69421e0edd3?auto=format&fit=crop&w=800&q=80'
    ],
    
    // Plage et Détente
    [
        'ville_id' => 3,
        'titre' => 'Relaxation dans un hammam local',
        'description' => 'Détendez-vous dans un hammam traditionnel avec vue sur les montagnes.',
        'categorie' => 'plage',
        'prix_min' => 900,
        'prix_max' => 1800,
        'duree_min' => 1,
        'duree_max' => 1,
        'image_url' => 'https://images.unsplash.com/photo-1565689478170-6a0dbc6cbf6f?auto=format&fit=crop&w=800&q=80'
    ],
    
    // Famille
    [
        'ville_id' => 3,
        'titre' => 'Atelier de peinture bleue',
        'description' => 'Participez en famille à un atelier de peinture traditionnelle avec les pigments bleus caractéristiques de Chefchaouen.',
        'categorie' => 'famille',
        'prix_min' => 800,
        'prix_max' => 1600,
        'duree_min' => 1,
        'duree_max' => 2,
        'image_url' => 'https://images.unsplash.com/photo-1565689478170-6a0dbc6cbf6f?auto=format&fit=crop&w=800&q=80'
    ],
    [
        'ville_id' => 3,
        'titre' => 'Balade à dos d\'âne',
        'description' => 'Découvrez les environs de Chefchaouen à dos d\'âne, une activité amusante pour les enfants.',
        'categorie' => 'famille',
        'prix_min' => 700,
        'prix_max' => 1400,
        'duree_min' => 1,
        'duree_max' => 2,
        'image_url' => 'https://images.unsplash.com/photo-1565689478170-6a0dbc6cbf6f?auto=format&fit=crop&w=800&q=80'
    ]
];

try {
    // Désactiver les transactions pour les inserts multiples
    $pdo->beginTransaction();
    
    // Supprimer les données existantes
    $pdo->exec("DELETE FROM recommandations");
    
    // Insérer les nouvelles données
    $stmt = $pdo->prepare("INSERT INTO recommandations (ville_id, titre, description, categorie, prix_min, prix_max, duree_min, duree_max, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    foreach ($recommendations as $recommendation) {
        $stmt->execute([
            $recommendation['ville_id'],
            $recommendation['titre'],
            $recommendation['description'],
            $recommendation['categorie'],
            $recommendation['prix_min'],
            $recommendation['prix_max'],
            $recommendation['duree_min'],
            $recommendation['duree_max'],
            $recommendation['image_url']
        ]);
    }
    
    $pdo->commit();
    echo "Données insérées avec succès !";
} catch (PDOException $e) {
    $pdo->rollBack();
    echo "Erreur : " . $e->getMessage();
}
