<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Admin - Gestion des villes</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', Arial, sans-serif;
            background: #f6f7fb;
            color: #222;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 32px 16px 64px 16px;
        }
        .section-title h2, .section-title h3 {
            font-family: 'Poppins', Arial, sans-serif;
            font-weight: 700;
            color: #2d2d2d;
            letter-spacing: 1px;
        }
        .admin-filters-bar {
            display: flex;
            gap: 18px;
            align-items: center;
            justify-content: flex-start;
            margin: 0 0 32px 0;
            flex-wrap: wrap;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(44,44,44,0.07);
            padding: 18px 24px;
        }
        .admin-filters-bar select, .admin-filters-bar input[type="text"] {
            padding: 12px 20px;
            border: 1.5px solid #bfa14a;
            border-radius: 8px;
            background: #faf9f7;
            color: #222;
            font-size: 1.08rem;
            box-shadow: 0 2px 8px rgba(180,138,60,0.07);
            transition: border 0.2s, box-shadow 0.2s;
            outline: none;
            min-width: 180px;
        }
        .admin-filters-bar select:focus, .admin-filters-bar input[type="text"]:focus {
            border-color: #d4af37;
            box-shadow: 0 4px 16px rgba(180,138,60,0.13);
            background: #fff;
        }
        #btnRechercher {
            background: linear-gradient(90deg, #bfa14a 0%, #2d2d2d 100%);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1.08rem;
            padding: 12px 32px;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(44,44,44,0.07);
            transition: background 0.2s, box-shadow 0.2s;
        }
        #btnRechercher:hover {
            background: linear-gradient(90deg, #2d2d2d 0%, #bfa14a 100%);
            color: #fff;
        }
        .admin-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 20px;
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 6px 32px rgba(44,44,44,0.10);
            display: table;
            table-layout: auto;
        }
        .admin-table thead th {
            position: sticky;
            top: 0;
            background: linear-gradient(90deg, #2d2d2d 0%, #bfa14a 100%);
            color: #fff;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            padding: 20px 16px;
            border-bottom: 2.5px solid #bfa14a;
            z-index: 2;
        }
        .admin-table th, .admin-table td {
            padding: 18px 14px;
            text-align: left;
            border-bottom: 1px solid #ececec;
            white-space: normal;
            min-width: 110px;
            font-size: 1.01rem;
        }
        .admin-table tr {
            transition: background 0.18s;
        }
        .admin-table tr:nth-child(even) {
            background: #f8f8f8;
        }
        .admin-table tr:hover {
            background: #f3f0e7;
        }
        .admin-table td {
            vertical-align: top;
        }
        .admin-table img {
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(44,44,44,0.10);
        }
        .admin-actions {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .btn-outline {
            background: #fff;
            color: #bfa14a;
            border: 2px solid #bfa14a;
            border-radius: 6px;
            padding: 7px 18px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.2s, color 0.2s, border 0.2s;
            display: flex;
            align-items: center;
            gap: 7px;
        }
        .btn-outline:hover {
            background: #bfa14a;
            color: #fff;
        }
        .btn-delete {
            background: #dc3545;
            color: #fff;
            border: none;
            padding: 7px 18px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1rem;
            transition: background 0.2s;
            display: flex;
            align-items: center;
            gap: 7px;
        }
        .btn-delete:hover {
            background: #b52a37;
        }
        .form {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 4px 18px rgba(44,44,44,0.09);
            padding: 32px 28px 24px 28px;
            margin-bottom: 40px;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }
        .form-group {
            margin-bottom: 22px;
        }
        .form-group label {
            font-weight: 500;
            color: #2d2d2d;
            margin-bottom: 7px;
            display: block;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 13px 16px;
            border: 1.5px solid #bfa14a;
            border-radius: 8px;
            background: #faf9f7;
            color: #222;
            font-size: 1.05rem;
            box-shadow: 0 2px 8px rgba(180,138,60,0.07);
            transition: border 0.2s, box-shadow 0.2s;
            outline: none;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            border-color: #d4af37;
            box-shadow: 0 4px 16px rgba(180,138,60,0.13);
            background: #fff;
        }
        .btn-solid {
            background: linear-gradient(90deg, #bfa14a 0%, #2d2d2d 100%);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1.08rem;
            padding: 12px 32px;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(44,44,44,0.07);
            transition: background 0.2s, box-shadow 0.2s;
        }
        .btn-solid:hover {
            background: linear-gradient(90deg, #2d2d2d 0%, #bfa14a 100%);
            color: #fff;
        }
        footer {
            background: #fff;
            color: #bfa14a;
            text-align: center;
            padding: 18px 0 0 0;
            font-size: 1rem;
            border-top: 1.5px solid #e9cba7;
            margin-top: 48px;
        }
        @media (max-width: 1100px) {
            .container {
                max-width: 98vw;
                padding: 12px 2vw 32px 2vw;
            }
            .admin-table th, .admin-table td {
                font-size: 0.97rem;
                padding: 12px 7px;
            }
        }
        @media (max-width: 800px) {
            .admin-filters-bar {
                flex-direction: column;
                align-items: stretch;
                gap: 12px;
                padding: 12px 8px;
            }
            .form {
                padding: 18px 7px 12px 7px;
            }
            .admin-table th, .admin-table td {
                font-size: 0.93rem;
                padding: 8px 4px;
            }
        }
        @media (max-width: 600px) {
            .admin-table, .admin-table thead, .admin-table tbody, .admin-table th, .admin-table td, .admin-table tr {
                display: block;
            }
            .admin-table thead tr {
                display: none;
            }
            .admin-table tr {
                margin-bottom: 18px;
                border-radius: 8px;
                box-shadow: 0 2px 8px rgba(44,44,44,0.10);
                background: #fff;
            }
            .admin-table td {
                padding: 12px 8px;
                border: none;
                position: relative;
            }
            .admin-table td:before {
                content: attr(data-label);
                font-weight: 600;
                color: #bfa14a;
                display: block;
                margin-bottom: 6px;
                text-transform: uppercase;
                font-size: 0.9em;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="section-title">
            <h2>Gestion des villes</h2>
        </div>
        
        <div class="admin-filters-bar">
            <select>
                <option>Tous les pays</option>
                <option>France</option>
                <option>Belgique</option>
            </select>
            
            <select>
                <option>Toutes les régions</option>
                <option>Île-de-France</option>
                <option>Auvergne-Rhône-Alpes</option>
            </select>
            
            <input type="text" placeholder="Rechercher une ville...">
            
            <button id="btnRechercher">Rechercher</button>
        </div>
        
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Ville</th>
                    <th>Pays</th>
                    <th>Région</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td data-label="ID">1</td>
                    <td data-label="Ville">Paris</td>
                    <td data-label="Pays">France</td>
                    <td data-label="Région">Île-de-France</td>
                    <td data-label="Actions">
                        <div class="admin-actions">
                            <button class="btn-outline"><i class="fas fa-edit"></i> Éditer</button>
                            <button class="btn-delete"><i class="fas fa-trash"></i> Supprimer</button>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td data-label="ID">2</td>
                    <td data-label="Ville">Lyon</td>
                    <td data-label="Pays">France</td>
                    <td data-label="Région">Auvergne-Rhône-Alpes</td>
                    <td data-label="Actions">
                        <div class="admin-actions">
                            <button class="btn-outline"><i class="fas fa-edit"></i> Éditer</button>
                            <button class="btn-delete"><i class="fas fa-trash"></i> Supprimer</button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <div class="form">
            <div class="form-group">
                <label for="ville">Nom de la ville</label>
                <input type="text" id="ville" placeholder="Entrez le nom de la ville">
            </div>
            
            <div class="form-group">
                <label for="pays">Pays</label>
                <select id="pays">
                    <option>Sélectionnez un pays</option>
                    <option>France</option>
                    <option>Belgique</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="region">Région</label>
                <input type="text" id="region" placeholder="Entrez la région">
            </div>
            
            <button class="btn-solid">Enregistrer</button>
        </div>
    </div>
    
    <footer>
        <p>© 2023 Administration - Tous droits réservés</p>
    </footer>
</body>
</html> 