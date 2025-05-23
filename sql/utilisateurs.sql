#Table des utilisateurs, devrait contenir tout les utilisateur incluant les administrateurs
CREATE OR REPLACE TABLE Utilisateurs(
    id INT PRIMARY KEY AUTO_INCREMENT,

    nom_utilisateur VARCHAR(32) NOT NULL UNIQUE,
    
    #Les mots de passes doivent être stocké sous forme de mot de passe haché avec Bcrypt
    #Il n'y a pas de contrainte dans le fichier sql, les contraintes sont dans la base de donnes
    mot_de_passe VARCHAR(64) NOT NULL,
    type_utilisateur ENUM('ADMIN','JOUEUR','BAN') DEFAULT 'JOUEUR' NOT NULL   
);

ALTER TABLE Utilisateurs ADD COLUMN date_inscription DATETIME NOT NULL DEFAULT NOW();

#Utilisateurs de tests

# utilisateur de test 1 : Garfield/JimDavis1945 -> $2y$10$rATwoKxPSI3B2NQuGHDeue14TRMZjB0R4XoMU6DlFER8qmLaLmKyG
INSERT INTO Utilisateurs (nom_utilisateur,mot_de_passe,type_utilisateur) VALUES("Garfield","$2y$10$rATwoKxPSI3B2NQuGHDeue14TRMZjB0R4XoMU6DlFER8qmLaLmKyG","ADMIN");

# utilisateur de test 2 : adminBrian/Password1 -> $2y$10$jPM74jRwJchtHzKNXsvjd.CJ7Dw21XDh14OEqzcB3i0VRmGCCFRDK
INSERT INTO Utilisateurs (nom_utilisateur,mot_de_passe,type_utilisateur) VALUES("adminBrian","$2y$10$jPM74jRwJchtHzKNXsvjd.CJ7Dw21XDh14OEqzcB3i0VRmGCCFRDK","ADMIN");

# utilisateur de test 3 : Toni300/Password2 -> $2y$10$qawye0KIxfCmO.YVz5TrAu07Eg1rgjF0zQ5BChWvt.tlkoz2UAHYy
INSERT INTO Utilisateurs  (nom_utilisateur,mot_de_passe,type_utilisateur) VALUES("Toni300","$2y$10$qawye0KIxfCmO.YVz5TrAu07Eg1rgjF0zQ5BChWvt.tlkoz2UAHYy","ADMIN");

