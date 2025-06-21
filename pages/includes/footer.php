<?php
// Assurez-vous que la session est démarrée dans chaque page qui utilise le footer
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclure les styles du footer principal
include_once '../../includes/footer.php';
?>

<!-- Footer -->
<footer>
    <div class="container">
        <div class="footer-grid">
            <div class="footer-col">
                <a href="../../index.php" class="footer-logo">
                    <img src="https://i.postimg.cc/g07GgLp5/VMaroc-logo-trf.png" alt="VMaroc Logo">
                </a>
                <p>Découvrez les merveilles du Maroc avec VMaroc, votre guide de voyage personnalisé pour une expérience authentique et inoubliable.</p>
                <div class="social-links">
                    <a href="#" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" title="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" title="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="#" title="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
            <div class="footer-col">
                <h3>Navigation</h3>
                <ul>
                    <li><a href="../../index.php">Accueil</a></li>
                    <li><a href="../../destinations.php">Destinations</a></li>
                    <li><a href="../../recommandations.php">Recommandations</a></li>
                    <li><a href="../../about.php">À propos</a></li>
                    <li><a href="../../contact.php">Contact</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h3>Destinations Populaires</h3>
                <ul>
                    <?php
                    // Récupérer les IDs des villes populaires
                    $popularCities = [
                        'marrakech' => ['nom' => 'Marrakech', 'id' => null],
                        'casablanca' => ['nom' => 'Casablanca', 'id' => null],
                        'fes' => ['nom' => 'Fès', 'id' => null],
                        'chefchaouen' => ['nom' => 'Chefchaouen', 'id' => null]
                    ];
                    
                    try {
                        $stmt = $pdo->prepare("SELECT id, nom FROM villes WHERE LOWER(nom) IN (?, ?, ?, ?)");
                        $stmt->execute([
                            'marrakech',
                            'casablanca',
                            'fès',
                            'chefchaouen'
                        ]);
                        
                        while ($row = $stmt->fetch()) {
                            $nomVille = strtolower(str_replace('è', 'e', $row['nom']));
                            if (isset($popularCities[$nomVille])) {
                                $popularCities[$nomVille]['id'] = $row['id'];
                            }
                        }
                    } catch (PDOException $e) {
                        error_log("Erreur lors de la récupération des IDs des villes: " . $e->getMessage());
                    }
                    ?>
                    <?php foreach ($popularCities as $city): ?>
                        <li><a href="../../city.php?id=<?= $city['id'] ?? '' ?>"><?= $city['nom'] ?></a></li>
                    <?php endforeach; ?>
                    <li><a href="../../destinations.php">Toutes les destinations</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h3>Contact</h3>
                <p><i class="fas fa-envelope"></i> contact@vmaroc.com</p>
                <p><i class="fas fa-phone"></i> +212 522 123 456</p>
                <p><i class="fas fa-map-marker-alt"></i> Avenue Mohammed V, Casablanca, Maroc</p>
            </div>
        </div>
        <div class="copyright">
            <p>© <?php echo date('Y'); ?> VMaroc. Tous droits réservés.</p>
            <p>
                <a href="../politique-confidentialite.php">Politique de confidentialité</a> |
                <a href="../conditions-utilisation.php">Conditions d'utilisation</a>
            </p>
        </div>
    </div>
</footer> 