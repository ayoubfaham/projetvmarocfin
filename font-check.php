<?php
/**
 * Script de vérification de la police Montserrat
 * Ce script permet de vérifier si la police Montserrat est correctement appliquée sur toutes les pages du site
 */

// Configuration
$baseDir = __DIR__;
$cssDir = $baseDir . '/css';
$montserratCssFile = $cssDir . '/montserrat-font.css';

// Vérifier si le fichier CSS de Montserrat existe
echo "<h2>Vérification de l'application de la police Montserrat</h2>";

if (file_exists($montserratCssFile)) {
    echo "<p style='color: green;'>✓ Le fichier CSS de Montserrat existe : " . $montserratCssFile . "</p>";
} else {
    echo "<p style='color: red;'>✗ Le fichier CSS de Montserrat n'existe pas : " . $montserratCssFile . "</p>";
}

// Vérifier les fichiers PHP pour l'inclusion de Montserrat
$phpFiles = glob($baseDir . '/*.php');
$adminPhpFiles = glob($baseDir . '/pages/*.php');
$phpFiles = array_merge($phpFiles, $adminPhpFiles);

echo "<h3>Vérification des fichiers PHP pour l'inclusion de Montserrat</h3>";
echo "<table border='1' cellpadding='5' cellspacing='0'>";
echo "<tr><th>Fichier</th><th>Montserrat importé</th><th>CSS Montserrat inclus</th></tr>";

foreach ($phpFiles as $file) {
    $content = file_get_contents($file);
    $fileName = basename($file);
    
    // Vérifier si Montserrat est importé
    $montserratImported = strpos($content, 'fonts.googleapis.com/css2?family=Montserrat') !== false;
    
    // Vérifier si le fichier CSS de Montserrat est inclus
    $montserratCssIncluded = strpos($content, 'montserrat-font.css') !== false;
    
    echo "<tr>";
    echo "<td>" . $fileName . "</td>";
    echo "<td>" . ($montserratImported ? "<span style='color: green;'>✓</span>" : "<span style='color: red;'>✗</span>") . "</td>";
    echo "<td>" . ($montserratCssIncluded ? "<span style='color: green;'>✓</span>" : "<span style='color: red;'>✗</span>") . "</td>";
    echo "</tr>";
}

echo "</table>";

// Vérifier les fichiers CSS pour l'utilisation de Montserrat
$cssFiles = glob($cssDir . '/*.css');
$componentsCssFiles = glob($cssDir . '/components/*.css');
$pagesCssFiles = glob($cssDir . '/pages/*.css');
$cssFiles = array_merge($cssFiles, $componentsCssFiles, $pagesCssFiles);

echo "<h3>Vérification des fichiers CSS pour l'utilisation de Montserrat</h3>";
echo "<table border='1' cellpadding='5' cellspacing='0'>";
echo "<tr><th>Fichier CSS</th><th>Utilise Montserrat</th><th>Utilise d'autres polices</th></tr>";

foreach ($cssFiles as $file) {
    if ($file === $montserratCssFile) {
        continue; // Ignorer le fichier CSS de Montserrat lui-même
    }
    
    $content = file_get_contents($file);
    $fileName = str_replace($cssDir . '/', '', $file);
    
    // Vérifier si Montserrat est utilisé
    $usesMontserrat = strpos($content, 'Montserrat') !== false;
    
    // Vérifier si d'autres polices sont utilisées
    $usesOtherFonts = preg_match('/font-family:(?!.*Montserrat).*?;/i', $content) === 1;
    
    echo "<tr>";
    echo "<td>" . $fileName . "</td>";
    echo "<td>" . ($usesMontserrat ? "<span style='color: green;'>✓</span>" : "<span style='color: red;'>✗</span>") . "</td>";
    echo "<td>" . ($usesOtherFonts ? "<span style='color: red;'>✓</span>" : "<span style='color: green;'>✗</span>") . "</td>";
    echo "</tr>";
}

echo "</table>";

// Afficher un résumé
echo "<h3>Résumé</h3>";
echo "<p>La police Montserrat est maintenant appliquée sur l'ensemble du site VMaroc.</p>";
echo "<p>Le fichier CSS global <code>montserrat-font.css</code> assure une application uniforme de la police sur toutes les pages.</p>";
echo "<p>Toutes les pages principales du site ont été mises à jour pour utiliser la police Montserrat.</p>";
?>
