<?php
/**
 * Fonction d'aide pour déterminer l'image à utiliser pour une ville donnée
 * Assure la cohérence entre la page d'accueil et la page des destinations
 * 
 * @param string $cityName Nom de la ville
 * @param string $photoUrl URL de la photo dans la base de données
 * @return string URL de l'image à utiliser
 */
function getCityImageUrl($cityName, $photoUrl) {
    // Conversion en minuscules pour la comparaison
    $cityNameLower = strtolower($cityName);
    
    // Mappage des images pour chaque ville (en utilisant les images existantes)
    $cityImages = [
        'agadir' => 'images/agadir.png',
        'casablanca' => 'images/casablanca1.png',
        'chefchaouen' => 'images/chefchaouen.png',
        'el jadida' => 'images/eljadida.png',
        'essaouira' => 'images/essaouira.png',
        'fès' => 'images/fes.png',
        'marrakech' => 'images/marrakech.png',
        'tanger' => 'images/tanger.png',
        'rabat' => 'images/rabat.png',
        'ouarzazate' => 'images/ouarzazate.png',
        'taza' => 'images/taza.png',
        'meknès' => 'images/destination1.png'  // Image par défaut pour Meknès
    ];
    
    // Vérifier si la ville a une image spécifique
    if (isset($cityImages[$cityNameLower])) {
        return $cityImages[$cityNameLower];
    }
    
    // Pour les autres villes, utiliser l'URL de la base de données ou une image par défaut
    return !empty($photoUrl) ? $photoUrl : 'images/destination1.png';  // Image par défaut
}
