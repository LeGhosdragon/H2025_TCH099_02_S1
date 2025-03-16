<?php
require_once 'constantes.php';
$config = require_once '../config.php';


// Creation du PDO
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8",
    $config['username'],
    $config['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

/**
 * Ce fichier contient des exemples d'utilisation du routeur fourni dans Routeur.php.
 * 
 * Les gestionnaires de route sont des fonctions de rappel avec du contenu de réponse simulé.
 * 
 * Pour tester les routes, vous pouvez utiliser un logiciel comme Postman ou CURL.
 */

require_once 'Routeur.php';

// Instancier le routeur
$router = new Routeur();

// Route POST pour créer un utilisateur
/**
 * @param string 'identifiant' nom_utilisateur qui desire s'inscrire  
 * @param string 'passe' mot de passe de l'utilisateur qui desire s'inscrire
 * 
 * @return bool 'reussite' true si l'inscription est valide, false sinon
 * @return string 'erreurs' liste d'erreurs, vide si l'inscription est reussie
 */
$router->post('/api.php/inscription', function () use ($pdo) {

    // Validation de l'identifiant
    if (isset($_POST['identifiant'])) {
        //Nettoie l'identifiant
        $identifiant = trim(htmlspecialchars($_POST['identifiant']));

        //Valide que l'identifiant nest pas vide
        if ($identifiant === '') {
            echo json_encode(['reussite' => false, 'erreurs' => ID_ABSENT]);
            return;
        }


        //S'assure que l'identifiant n'a pas déja été choisis
        $validPasseUnique = $pdo->prepare("SELECT count(*) FROM Utilisateurs WHERE nom_utilisateur = :identifiant");
        $validPasseUnique->execute(['identifiant' => $identifiant]);

        $res = $validPasseUnique->fetch();
        if ($res['count(*)'] != 0) {
            echo json_encode(['reussite' => false, 'erreurs' => ID_DOUBLE]);
            return;
        }
    } else {
        echo json_encode(['reussite' => false, 'erreurs' => ID_UNSET]);
        return;
    }

    //Validations du mot de passe
    $erreurs = [];
    if (!isset($_POST['passe'])) {
        $erreurs[] =  PASSE_UNSET;
    }

    //Nettoie le mot de passe
    $passe = htmlspecialchars($_POST['passe']);

    //valide le mot de passe: il doit y avoir entre 8 et 32 characteres
    if (strlen($passe) < 8 || strlen($passe) > 32) {
        $erreurs[] = sprintf(PASSE_TAILLE_INV, strlen($passe));
    }
    //Valide le mot de passe: Contiens au moins une majuscule
    if (!preg_match('/[A-Z]/', $passe)) {
        $erreurs[] = PASSE_NO_MAJ;
    }
    //Valide le mot de passe: Contiens au moins une minuscule
    if (!preg_match('/[a-z]/', $passe)) {
        $erreurs[] = PASSE_NO_MIN;
    }
    //Valide le mot de passe: Contiens au moins un chiffre
    if (!preg_match('/[0-9]/', $passe)) {
        $erreurs[] = PASSE_NO_CHIFFRE;
    }

    //Retourne les erreurs si il y en a
    if (!empty($erreurs)) {
        echo json_encode(['reussite' => false, 'erreurs' => $erreurs]);
        return;
    }

    $mpdHash = password_hash($passe, PASSWORD_BCRYPT);

    //Requete d'inscription a la base de donnees
    $requeteInscription = $pdo->prepare("INSERT INTO Utilisateurs (nom_utilisateur, mot_de_passe) VALUES (:identifiant, :passe)");
    $requeteInscription->execute([
        'identifiant' => $identifiant,
        'passe' => $mpdHash
    ]);

    echo json_encode(['reussite' => true]);
});


// Route POST pour connecter un utilisateur
/**
 * @param string 'identifiant' nom_utilisateur qui desire se connecter  
 * @param string 'passe' mot de passe de l'utilisateur qui desire se connecter
 * 
 * @return bool 'reussite' true si la connexion est valide, false sinon
 * @return string 'erreurs' liste d'erreurs, vide si la connexion est reussie
 * @return string 'jeton' jeton de la connexion, vide si la connexion est invalide
 */
$router->post('/api.php/connexion', function () use ($pdo) {

    //Valide si l'identifiant est set
    if (!isset($_POST['identifiant'])) {
        echo json_encode(['reussite' => false, 'erreurs' => ID_ABSENT]);
        return;
    }
    //Valide si le mot de passe est set
    if (!isset($_POST['passe'])) {
        echo json_encode(['reussite' => false, 'erreurs' => PASSE_UNSET]);
        return;
    }

    //nettoie les identifiants
    $identifiant = trim(htmlspecialchars($_POST['identifiant']));
    $passe = htmlspecialchars($_POST['passe']);

    //Genere la requete de connexion
    $reqConnexion = $pdo->prepare("SELECT mot_de_passe,id FROM Utilisateurs WHERE nom_utilisateur = :identifiant");
    $reqConnexion->execute([
        'identifiant' => $identifiant
    ]);
    ['mot_de_passe' => $mpdHash, "id" => $idUtil] = $reqConnexion->fetch() ?? [];


    //Verifie les erreurs
    if (!$mpdHash) {
        echo json_encode(['reussite' => false, 'erreurs' => ID_INVALIDE]);
        return;
    }

    if (!password_verify($passe, $mpdHash)) {
        echo json_encode(['reussite' => false, 'erreurs' => PASSE_INVALIDE]);
        return;
    }

    //Genere le jeton
    $jeton = bin2hex(random_bytes(32));

    //Ajoute le jeton dans la base de donnes
    $ajoutJeton = $pdo->prepare("INSERT INTO Jetons (id_utilisateur,data_jeton) VALUES(?,?)");

    $ajoutJeton->execute([$idUtil, $jeton]);

    
    echo json_encode(['reussite' => true,'jeton'=> $jeton]);
});
$router->get('/api.php/palmares/obtenir', function () use ($pdo) {});

// Acheminer la requête
$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
