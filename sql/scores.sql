CREATE OR REPLACE TABLE Scores(
    id int PRIMARY KEY AUTO_INCREMENT,
    id_utilisateur INT NOT NULL,

    score int NOT NULL,
    #Temps en ms
    temps_partie int NOT NULL,
    experience int NOT NULL,
    ennemis_enleve int NOT NULL,


    date_soumission DATETIME NOT NULL DEFAULT NOW(),

    FOREIGN KEY (id_utilisateur) REFERENCES Utilisateurs(id),
    UNIQUE (id_utilisateur)
);

#Score de tests
INSERT INTO Scores (id_utilisateur, score, temps_partie, experience, ennemis_enleve, date_soumission) 
VALUES 
    (1, 1500, 120000, 500, 30, '2025-03-18 14:25:00'),
    (2, 2200, 90000, 700, 45, '2025-03-18 15:10:00'),
    (3, 1850, 110000, 620, 38, '2025-03-18 16:30:00');