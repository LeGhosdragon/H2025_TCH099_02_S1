CREATE OR REPLACE TABLE Feedbacks(
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_utilisateur INT NOT NULL,
    contenu VARCHAR(500) NOT NULL,
    note INT NOT NULL CHECK (note BETWEEN 1 AND 5),
    categorie ENUM('gameplay', 'interface', 'difficulté', 'suggestions', 'bugs') NOT NULL,
    date_soumission DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_utilisateur) REFERENCES Utilisateurs(id)
);