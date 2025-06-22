# VMaroc - Plateforme de Découverte Touristique

VMaroc est une plateforme web dédiée à la découverte des lieux touristiques au Maroc. Elle permet aux utilisateurs de découvrir, évaluer et partager leurs expériences sur différents lieux à travers le pays.

## Fonctionnalités

- 🏰 Découverte de lieux touristiques
- 🌟 Système d'évaluation et d'avis
- 📸 Galerie d'images avec slider
- 🗺️ Intégration de cartes pour la localisation
- 👤 Gestion des utilisateurs et profils
- 🔐 Interface d'administration sécurisée

## Configuration Requise

- PHP 7.4 ou supérieur
- MySQL 5.7 ou supérieur
- Serveur web (Apache recommandé)
- Extension PHP PDO
- Extension PHP GD (pour la manipulation d'images)

## Installation

1. Clonez le dépôt :
```bash
git clone [URL_DU_REPO]
```

2. Configurez votre base de données :
   - Créez une base de données MySQL
   - Copiez `config/database.example.php` vers `config/database.php`
   - Modifiez les informations de connexion dans `config/database.php`

3. Importez la structure de la base de données :
```bash
mysql -u [utilisateur] -p [nom_base_de_donnees] < database/structure.sql
```

4. Configurez votre serveur web pour pointer vers le dossier du projet

## Structure du Projet

```
project10/
├── config/             # Configuration de la base de données
├── css/               # Fichiers CSS
├── images/            # Images uploadées
├── includes/          # Fichiers inclus (header, footer)
├── js/                # Scripts JavaScript
└── pages/             # Pages PHP
```

## Sécurité

- Les mots de passe sont hachés avec bcrypt
- Protection contre les injections SQL via PDO
- Validation des entrées utilisateur
- Sessions sécurisées
- Contrôle d'accès basé sur les rôles

## Contribution

1. Fork le projet
2. Créez votre branche (`git checkout -b feature/AmazingFeature`)
3. Committez vos changements (`git commit -m 'Add some AmazingFeature'`)
4. Push vers la branche (`git push origin feature/AmazingFeature`)
5. Ouvrez une Pull Request

## Licence

Ce projet est sous licence MIT. Voir le fichier `LICENSE` pour plus de détails. 