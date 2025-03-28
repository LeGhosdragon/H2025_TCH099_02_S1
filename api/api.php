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
// Route POST pour connecter un utilisateur
/**
 * @param string 'jeton' jeton de l'utilisateur qui desire ajouter un palmares
 * @param int 'score' score qui a ete obtenu a la fin de la partie
 * @param int 'duree' duree de la partie en ms
 * @param int 'experience' quantitee dexperience aquise
 * @param int 'ennemis' nombre d'ennemis elimine
 * 
 * @return bool 'reussite' true si l'ajout est valide, false sinon
 * @return string 'erreurs' liste d'erreurs, vide si l'ajout est reussi
 */
$router->post('/api.php/palmares/ajouter', function () use ($pdo){
    //Valide si le jeton est set
    if (!isset($_POST['jeton'])) {
        echo json_encode(['reussite' => false, 'erreurs' => JETON_UNSET]);
        return;
    }
    //Lave le jeton
    $jeton = htmlspecialchars($_POST['jeton']);

    //Requete pour obtenir l'identifiant
    $obtenirUtilisateur = $pdo->prepare("SELECT j.id_utilisateur, s.score FROM Jetons j LEFT JOIN Scores s ON s.id_utilisateur = j.id_utilisateur WHERE j.data_jeton = :jeton AND j.date_expiration >= NOW()");

    $obtenirUtilisateur-> execute([
        'jeton' => $jeton
    ]);

    //Obtiens le id de l'utilisateur
    ['id_utilisateur' => $idUtil, 'score'=>$ancientScore] = $obtenirUtilisateur->fetch()??[];



    //Verifie si l'utilisateur existe pour le jeton 
    if(!$idUtil){
        echo json_encode(['reussite' => false, 'erreurs' => JETON_INVALIDE]);
        return;
    }
    //Fin de l'authentification


    //Recupere les elements du sccore et les mets a 0 si ils sont null
    $score = htmlspecialchars($_POST['score']??0);
    $duree = htmlspecialchars($_POST['duree']??0);
    $experience = htmlspecialchars($_POST['experience']??0);
    $ennemis = htmlspecialchars($_POST['ennemis']??0);

    if(!filter_var($score,FILTER_VALIDATE_INT)){
        echo json_encode(['reussite' => false, 'erreurs' => SCORE_NO_INT]);
        return;
    }

    if(!filter_var($duree,FILTER_VALIDATE_INT)){
        echo json_encode(['reussite' => false, 'erreurs' => DUREE_NO_INT]);
        return;
    }
    if(!filter_var($experience,FILTER_VALIDATE_INT)){
        echo json_encode(['reussite' => false, 'erreurs' => EXPERIENCE_NO_INT]);
        return;
    }
    if(!filter_var($ennemis,FILTER_VALIDATE_INT)){
        echo json_encode(['reussite' => false, 'erreurs' => ENNEMIS_NO_INT]);
        return;
    }



    //Verifie si le score nes pas null
    if($score == 0){
        echo json_encode(['reussite' => false, 'erreurs' => SCORE_NULL]);
        return;
    }
    //Valide si le score concorde avec les statistiques
    if($score != $ennemis * $duree * $experience ){
        echo json_encode(['reussite' => false, 'erreurs' => SCORE_INVALIDE]);
        return;
    }

    //Verifie si le nouveau score est meilleur que le precedent
    if(!$ancientScore || $ancientScore < $score ){
        $req = $pdo->prepare("
        INSERT INTO Scores(id_utilisateur, score, temps_partie, experience, ennemis_enleve) VALUES (:id_utilisateur,:score,:temps,:experience,:ennemis)
        ON DUPLICATE KEY UPDATE score = :score,
        temps_partie = :temps,
        experience = :experience,
        ennemis_enleve = :ennemis,
        date_soumission = NOW()");
        $req->execute([
            "id_utilisateur" => $idUtil,
            "score" => $score,
            "temps" => $duree,
            "experience" => $experience,
            "ennemis" => $ennemis
        ]);
        echo json_encode(['reussite' => true]);
        return;
    }
    echo json_encode(['reussite' => false,'erreurs'=> SCORE_BAS]);

});

// Route GET pour obtenir le palmares
/**
 * Cette route retourne les 20 meilleurs scores, classés par ordre décroissant.
 * 
 * @return bool 'reussite' true indiquant que la récupération des scores est réussie
 * @return array 'palmares' tableau contenant les scores des utilisateurs avec leurs détails
 *   - id: identifiant du score
 *   - nom_utilisateur: nom de l'utilisateur ayant obtenu le score
 *   - score: score total obtenu
 *   - temps_partie: durée de la partie en ms
 *   - experience: expérience acquise pendant la partie
 *   - ennemis_enleve: nombre d'ennemis éliminés
 *   - date_soumission: date de soumission du score
 */
$router->get('/api.php/palmares/obtenir', function () use ($pdo) {

    $requetePalmares = 
    $pdo -> query("SELECT score.id, utilisateur.nom_utilisateur, score.score, score.temps_partie,
    score.experience, score.ennemis_enleve, score.date_soumission
    FROM Scores score
    JOIN Utilisateurs utilisateur ON score.id_utilisateur = utilisateur.id
    ORDER BY score.score DESC
    LIMIT 20");
    
    $palmares = $requetePalmares->fetchAll();

    echo json_encode(['reussite' => true, 'palmares' => $palmares]);
});

// Route DELETE pour supprimer un score selon l'id
/**
 * Cette route permet à un administrateur de supprimer un score spécifique par son identifiant.
 * 
 * @param int {id} identifiant du score à supprimer (passé dans l'URL)
 * @param string 'jeton' jeton d'authentification de l'administrateur
 * 
 * @return bool 'reussite' true si la suppression est réussie, false sinon
 * @return string 'erreurs' message d'erreur si la suppression échoue
 *   - JETON_UNSET: pas de jeton de connexion
 *   - JETON_INVALIDE: jeton invalide
 *   - Accès non autorisé: tentative de suppression par un non-administrateur
 *   - ID de score invalide: identifiant de score incorrect
 *   - Erreur lors de la suppression du score: problème technique
 */
$router->delete('/api.php/palmares/supprimer/{id}', function($id) use ($pdo){
    if(!isset($_GET['jeton'])){
        echo json_encode(['reussite' => false, 'erreurs' => JETON_UNSET]);
        return;
    }

    $jeton = htmlspecialchars($_GET['jeton']);

    $verifierAdmin = $pdo->prepare("
        SELECT utilisateur.id, utilisateur.type_utilisateur
        FROM Jetons jeton
        JOIN Utilisateurs utilisateur ON jeton.id_utilisateur = utilisateur.id
        WHERE jeton.data_jeton = :jeton AND jeton.date_expiration >= NOW()
    ");

    $verifierAdmin->execute(['jeton'=> $jeton]);
    $utilisateur = $verifierAdmin->fetch();
    if(!$utilisateur){
        echo json_encode(['reussite' => false, 'erreurs' => JETON_INVALIDE]);
        return;
    }

    if($utilisateur['type_utilisateur'] !== 'ADMIN'){
        echo json_encode(['reussite' => false, 'erreurs' => 'Accès non autorisé']);
        return;
    }

    if (!filter_var($id, FILTER_VALIDATE_INT)) {
        echo json_encode(['reussite' => false, 'erreurs' => 'ID de score invalide']);
        return;
    }

    $supprimerScore = $pdo->prepare("DELETE FROM Scores WHERE id = :id");
    $resultat = $supprimerScore->execute(['id' => $id]);
    
    if ($resultat) {
        echo json_encode(['reussite' => true]);
    } else {
        echo json_encode(['reussite' => false, 'erreurs' => 'Erreur lors de la suppression du score']);
    }
});

// Route GET pour determiner si l'utilisateur est un admin
/**
 * Cette route vérifie si l'utilisateur connecté dispose de privilèges d'administrateur.
 * 
 * @param string 'jeton' jeton d'authentification de l'utilisateur
 * 
 * @return bool 'reussite' true indiquant que la vérification a été effectuée
 * @return bool 'estAdmin' true si l'utilisateur est un administrateur, false sinon
 * @return string 'erreurs' message d'erreur si la vérification échoue
 *   - JETON_UNSET: aucun jeton de connexion
 *   - JETON_INVALIDE: jeton invalide
 */
$router->get('/api.php/utilisateur/estAdmin', function () use ($pdo) {
    if (!isset($_GET['jeton'])) {
        echo json_encode(['reussite' => false, 'erreurs' => JETON_UNSET]);
        return;
    }
    
    $jeton = htmlspecialchars($_GET['jeton']);
    
    $verifierAdmin = $pdo->prepare("
        SELECT utilisateur.type_utilisateur 
        FROM Jetons jeton
        JOIN Utilisateurs utilisateur ON jeton.id_utilisateur = utilisateur.id 
        WHERE jeton.data_jeton = :jeton AND jeton.date_expiration >= NOW()
    ");
    
    $verifierAdmin->execute(['jeton' => $jeton]);
    $utilisateur = $verifierAdmin->fetch();
    
    if (!$utilisateur) {
        echo json_encode(['reussite' => false, 'erreurs' => JETON_INVALIDE]);
        return;
    }
    
    echo json_encode([
        'reussite' => true, 
        'estAdmin' => ($utilisateur['type_utilisateur'] === 'ADMIN')
    ]);
});



// Acheminer la requête
$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);


