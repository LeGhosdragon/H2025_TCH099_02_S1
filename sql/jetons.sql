CREATE OR REPLACE TABLE Jetons(
    id INT PRIMARY KEY AUTO_INCREMENT,
    #clee etranger de id dans Utilisateurs
    id_utilisateur INT NOT NULL,
    data_jeton VARCHAR(64) NOT NULL,
    #Delai dexpiration dune heure par default
    date_expiration DATETIME NOT NULL DEFAULT DATE_ADD(NOW(),interval 1 hour),
    FOREIGN KEY (id_utilisateur) REFERENCES Utilisateurs(id)
);