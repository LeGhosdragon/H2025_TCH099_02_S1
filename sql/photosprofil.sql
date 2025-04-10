CREATE TABLE IF NOT EXISTS PhotosProfil (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_utilisateur INT NOT NULL,
    chemin_photo VARCHAR(255) NOT NULL,
    date_creation DATETIME NOT NULL,
    date_modification DATETIME NULL,
    FOREIGN KEY (id_utilisateur) REFERENCES Utilisateurs(id) ON DELETE CASCADE,
    UNIQUE (id_utilisateur)
);

ALTER TABLE PhotosProfil
ADD COLUMN image_data MEDIUMBLOB,
ADD COLUMN mime_type VARCHAR(100);