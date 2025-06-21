-- Supprimer l'ancienne contrainte
ALTER TABLE recommandations
DROP FOREIGN KEY recommandations_ibfk_1;
 
-- Ajouter la nouvelle contrainte avec ON DELETE CASCADE
ALTER TABLE recommandations
ADD CONSTRAINT recommandations_ibfk_1
FOREIGN KEY (id_ville) REFERENCES villes(id)
ON DELETE CASCADE; 