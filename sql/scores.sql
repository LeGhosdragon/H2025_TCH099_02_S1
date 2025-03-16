CREATE OR REPLACE TABLE Scores(
    id int PRIMARY KEY AUTO_INCREMENT,
    id_utilisateur INT NOT NULL,

    score int NOT NULL,
    #Temps en ms
    temps_partie int NOT NULL,
    experience int NOT NULL,
    ennemis_enleve int NOT NULL,


    date_soumission DATETIME NOT NULL DEFAULT NOW(),

    FOREIGN KEY (id_utilisateur) REFERENCES Utilisateurs(id)
);