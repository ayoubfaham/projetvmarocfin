<?php
// Générateur de code PHP pour lieux par ville
$categories = ['Vie nocturne', 'Shopping', 'Musées', 'Monuments', 'Théâtres', 'Cinémas', 'Plages', 'Parcs', 'Restaurants', 'Hôtels'];
$lieux = [];
$ville = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ville = trim($_POST['ville']);
    if (isset($_POST['add_lieu'])) {
        // Ajout d'un lieu à la session
        session_start();
        $_SESSION['lieux'] = $_SESSION['lieux'] ?? [];
        $_SESSION['ville'] = $ville;
        $_SESSION['lieux'][] = [
            'nom' => $_POST['nom'],
            'photo' => $_POST['photo'],
            'description' => $_POST['description'],
            'categorie' => $_POST['categorie']
        ];
        $lieux = $_SESSION['lieux'];
    } elseif (isset($_POST['reset'])) {
        session_start();
        $_SESSION['lieux'] = [];
        $lieux = [];
    } elseif (isset($_POST['generate'])) {
        session_start();
        $ville = $_SESSION['ville'] ?? $ville;
        $lieux = $_SESSION['lieux'] ?? [];
    }
} else {
    session_start();
    $lieux = $_SESSION['lieux'] ?? [];
    $ville = $_SESSION['ville'] ?? '';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Générateur de lieux par ville</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Poppins', Arial, sans-serif; background: #f8f9fa; }
        .container { max-width: 700px; margin: 40px auto; background: #fff; border-radius: 14px; box-shadow: 0 2px 10px rgba(44,62,80,0.07); padding: 32px 28px; }
        h2 { text-align: center; margin-bottom: 24px; }
        .form-group { margin-bottom: 18px; }
        label { font-weight: 500; }
        input, select, textarea { width: 100%; padding: 8px 10px; border-radius: 6px; border: 1px solid #ccc; margin-top: 4px; }
        button { background: #e67e22; color: #fff; border: none; border-radius: 6px; padding: 10px 22px; font-weight: 600; cursor: pointer; margin-top: 10px; }
        button:hover { background: #c0392b; }
        .lieux-list { margin: 24px 0; }
        .lieux-list li { margin-bottom: 8px; }
        pre { background: #f4f4f4; padding: 18px; border-radius: 8px; font-size: 0.98em; }
        .actions { display: flex; gap: 10px; }
    </style>
</head>
<body>
<div class="container">
    <h2>Générateur de code PHP pour lieux d'une ville</h2>
    <form method="post">
        <div class="form-group">
            <label for="ville">Nom de la ville :</label>
            <input type="text" id="ville" name="ville" required value="<?= htmlspecialchars($ville) ?>">
        </div>
        <div class="form-group">
            <label for="nom">Nom du lieu :</label>
            <input type="text" id="nom" name="nom">
        </div>
        <div class="form-group">
            <label for="photo">URL de la photo :</label>
            <input type="text" id="photo" name="photo">
        </div>
        <div class="form-group">
            <label for="description">Description :</label>
            <textarea id="description" name="description" rows="2"></textarea>
        </div>
        <div class="form-group">
            <label for="categorie">Catégorie :</label>
            <select id="categorie" name="categorie">
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="actions">
            <button type="submit" name="add_lieu">Ajouter ce lieu</button>
            <button type="submit" name="reset" style="background:#bbb;">Réinitialiser</button>
            <button type="submit" name="generate" style="background:#27ae60;">Générer le code</button>
        </div>
    </form>
    <?php if (!empty($lieux)): ?>
        <div class="lieux-list">
            <h4>Lieux ajoutés :</h4>
            <ul>
                <?php foreach ($lieux as $l): ?>
                    <li><b><?= htmlspecialchars($l['nom']) ?></b> (<?= htmlspecialchars($l['categorie']) ?>)</li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <?php if (isset($_POST['generate']) && !empty($lieux) && $ville): ?>
        <h4>Code PHP à copier dans <code>$lieux_par_ville</code> :</h4>
        <pre>
'<?= addslashes(ucfirst(strtolower($ville))) ?>' => [
<?php foreach ($lieux as $l): ?>    [
        'nom' => '<?= addslashes($l['nom']) ?>',
        'photo' => '<?= addslashes($l['photo']) ?>',
        'description' => '<?= addslashes($l['description']) ?>',
        'categorie' => '<?= addslashes($l['categorie']) ?>'
    ],
<?php endforeach; ?>],
        </pre>
    <?php endif; ?>
</div>
</body>
</html> 