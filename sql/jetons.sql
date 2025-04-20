CREATE OR REPLACE TABLE Jetons(
    id INT PRIMARY KEY AUTO_INCREMENT,
    #clee etranger de id dans Utilisateurs
    id_utilisateur INT NOT NULL,
    data_jeton VARCHAR(64) NOT NULL,
    #Delai dexpiration dune heure par default
    date_expiration DATETIME NOT NULL DEFAULT DATE_ADD(NOW(),interval 1 HOUR),
    FOREIGN KEY (id_utilisateur) REFERENCES Utilisateurs(id)
);
 SET GLOBAL event_scheduler=ON

CREATE EVENT Expirer_jetons ON SCHEDULE
    EVERY 1 HOUR
    DO DELETE FROM Jetons WHERE Jetons.date_expiration < now();



#Nouvelle table!


CREATE OR REPLACE TABLE Jetons(
    id INT PRIMARY KEY AUTO_INCREMENT,
    -- Clé étrangère de id dans Utilisateurs
    id_utilisateur INT NOT NULL,
    -- Jeton d'accès
    data_jeton VARCHAR(64) NOT NULL,
    -- Délai d'expiration du jeton d'accès (1 heure par défaut)
    date_expiration DATETIME NOT NULL DEFAULT DATE_ADD(NOW(), INTERVAL 1 HOUR),
    -- Refresh token (longue durée)
    refresh_token VARCHAR(128),
    -- Délai d'expiration du refresh token (30 jours par défaut)
    date_expiration_refresh DATETIME DEFAULT DATE_ADD(NOW(), INTERVAL 30 DAY),
    -- Contrainte de clé étrangère
    FOREIGN KEY (id_utilisateur) REFERENCES Utilisateurs(id)
);

SET GLOBAL event_scheduler = ON;

-- Modifier l'événement pour nettoyer à la fois les jetons expirés et les refresh tokens expirés
CREATE OR REPLACE EVENT Expirer_jetons
  ON SCHEDULE EVERY 1 HOUR
  DO
    DELETE FROM Jetons
    WHERE date_expiration_refresh < NOW();
