<?php

//Messages d'erreurs
define("ID_ABSENT","Aucun identifiant n'a été trouvé");
define("ID_DOUBLE","L'identifiant existe deja");
define("ID_UNSET", "Aucun identifiant n'a ete envoye");

define("PASSE_UNSET","Aucun mot de passe n'a ete envoye");
define("PASSE_TAILLE_INV","Longueur du mot de passe invalide: %d Longueur attendue entre 8 et 32");
define("PASSE_NO_MAJ","Le mot de passe ne contiens pas de majuscule");
define("PASSE_NO_MIN","Le mot de passe ne contiens pas de minuscule");
define("PASSE_NO_CHIFFRE","Le mot de passe ne contiens pas de chiffres");

define("ID_INVALIDE","L'identifiant est invalide");
define("PASSE_INVALIDE","Le mot de passe est incorect");

define("JETON_UNSET","Pas de jeton de connexion");
define("JETON_INVALIDE","Jeton invalide");

define("SCORE_NULL","Le score nest pas set");
define("SCORE_INVALIDE","Les elements du score ne sont pas valide");
define("SCORE_BAS","Score plus bas que le precedent");

define("SCORE_NO_INT","Le score nest pas un int");
define("DUREE_NO_INT","La duree nest pas un int");
define("EXPERIENCE_NO_INT","L'experience nest pas un int");
define("ENNEMIS_NO_INT","Le nombre d'ennemis nest pas un int");