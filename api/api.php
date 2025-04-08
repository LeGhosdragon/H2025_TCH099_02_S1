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
            echo json_encode(['reussite' => false, 'erreurs' => [ID_ABSENT]]);
            return;
        }


        //S'assure que l'identifiant n'a pas déja été choisis
        $validPasseUnique = $pdo->prepare("SELECT count(*) FROM Utilisateurs WHERE nom_utilisateur = :identifiant");
        $validPasseUnique->execute(['identifiant' => $identifiant]);

        $res = $validPasseUnique->fetch();
        if ($res['count(*)'] != 0) {
            echo json_encode(['reussite' => false, 'erreurs' => [ID_DOUBLE]]);
            return;
        }
    } else {
        echo json_encode(['reussite' => false, 'erreurs' => [ID_UNSET]]);
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

    /**Modification du jeton */

    // Récupère l'ID du nouvel utilisateur
    $userId = $pdo->lastInsertId();

    // Génère un jeton 
    $jeton = bin2hex(random_bytes(32));

    // Ajoute le jeton dans la base de données
    $ajoutJeton = $pdo->prepare("INSERT INTO Jetons (id_utilisateur, data_jeton) VALUES(?, ?)");
    $ajoutJeton->execute([$userId, $jeton]);

    // echo json_encode(['reussite' => true]);
    echo json_encode(['reussite' => true, 'jeton' => $jeton]);
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
        echo json_encode(['reussite' => false, 'erreurs' => [ID_ABSENT]]);
        return;
    }
    //Valide si le mot de passe est set
    if (!isset($_POST['passe'])) {
        echo json_encode(['reussite' => false, 'erreurs' => [PASSE_UNSET]]);
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
        echo json_encode(['reussite' => false, 'erreurs' => [ID_INVALIDE]]);
        return;
    }

    if (!password_verify($passe, $mpdHash)) {
        echo json_encode(['reussite' => false, 'erreurs' => [PASSE_INVALIDE]]);
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
    if( intval($score) != intval($ennemis) * intval($duree) * intval($experience)){
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

// Route POST pour soumettre un feedback
/**
 * Cette route permet à un utilisateur connecté d'envoyer un feedback sur le jeu.
 * 
 * @param string 'jeton' jeton d'authentification de l'utilisateur
 * @param string 'contenu' le contenu textuel du feedback
 * @param int 'note' une note de 1 à 5 étoiles
 * @param string 'categorie' catégorie du feedback (gameplay, interface, difficulte, suggestions, bugs)
 * 
 * @return bool 'reussite' true si l'enregistrement du feedback est réussi, false sinon
 * @return string 'erreurs' message d'erreur si l'enregistrement échoue
 *   - JETON_UNSET: pas de jeton de connexion
 *   - JETON_INVALIDE: jeton invalide
 *   - CONTENU_VIDE: contenu du feedback vide
 *   - NOTE_INVALIDE: note hors plage (1-5)
 *   - CATEGORIE_INVALIDE: catégorie non reconnue
 *   - Erreur lors de l'enregistrement: problème technique
 */
$router->post('/api.php/feedback/soumettre', function () use ($pdo) {
    // Vérification du jeton
    if (!isset($_POST['jeton'])) {
        echo json_encode(['reussite' => false, 'erreurs' => JETON_UNSET]);
        return;
    }
    
    $jeton = htmlspecialchars($_POST['jeton']);
    
    // Vérification des autres paramètres
    if (!isset($_POST['contenu']) || trim($_POST['contenu']) === '') {
        echo json_encode(['reussite' => false, 'erreurs' => 'CONTENU_VIDE']);
        return;
    }
    
    if (!isset($_POST['note']) || !is_numeric($_POST['note']) || $_POST['note'] < 1 || $_POST['note'] > 5) {
        echo json_encode(['reussite' => false, 'erreurs' => 'NOTE_INVALIDE']);
        return;
    }
    
    // Correction: utiliser "difficulte" sans accent pour être cohérent avec le front-end
    $categories_valides = ['gameplay', 'interface', 'difficulté', 'suggestions', 'bugs'];
    if (!isset($_POST['categorie']) || !in_array($_POST['categorie'], $categories_valides)) {
        echo json_encode(['reussite' => false, 'erreurs' => 'CATEGORIE_INVALIDE']);
        return;
    }
    
    // Récupération de l'id utilisateur à partir du jeton
    $verifierUtilisateur = $pdo->prepare("
        SELECT utilisateur.id, utilisateur.type_utilisateur
        FROM Jetons jeton
        JOIN Utilisateurs utilisateur ON jeton.id_utilisateur = utilisateur.id
        WHERE jeton.data_jeton = :jeton AND jeton.date_expiration >= NOW()
    ");
    
    $verifierUtilisateur->execute(['jeton' => $jeton]);
    $utilisateur = $verifierUtilisateur->fetch();
    
    if (!$utilisateur) {
        echo json_encode(['reussite' => false, 'erreurs' => JETON_INVALIDE]);
        return;
    }
    
    
    // Ajout: Vérification que l'utilisateur n'est pas un admin
    if ($utilisateur['type_utilisateur'] === 'ADMIN') {
        echo json_encode(['reussite' => false, 'erreurs' => 'ADMIN_FEEDBACK_ERREUR']);
        return;
    }
    
    // Préparation des données
    $id_utilisateur = $utilisateur['id'];
    $contenu = htmlspecialchars($_POST['contenu']);
    $note = intval($_POST['note']);
    $categorie = htmlspecialchars($_POST['categorie']);
    
    // Insertion du feedback
    $insererFeedback = $pdo->prepare("
        INSERT INTO Feedbacks (id_utilisateur, contenu, note, categorie)
        VALUES (:id_utilisateur, :contenu, :note, :categorie)
    ");
    
    $resultat = $insererFeedback->execute([
        'id_utilisateur' => $id_utilisateur,
        'contenu' => $contenu,
        'note' => $note,
        'categorie' => $categorie
    ]);
    
    if ($resultat) {
        echo json_encode(['reussite' => true]);
    } else {
        echo json_encode(['reussite' => false, 'erreurs' => 'ERREUR_ENREGISTREMENT_FEEDBACK']);
    }
});

// Route GET pour obtenir les informations d'un utilisateur
/**
 * Cette route permet de récupérer les informations d'un utilisateur connecté et ses scores.
 * 
 * @param string 'jeton' jeton d'authentification de l'utilisateur
 * 
 * @return bool 'reussite' true si la récupération est réussie, false sinon
 * @return string 'erreurs' message d'erreur si la récupération échoue
 * @return object 'utilisateur' informations sur l'utilisateur et ses scores
 *   - id: identifiant de l'utilisateur
 *   - nom_utilisateur: nom de l'utilisateur
 *   - date_inscription: date d'inscription de l'utilisateur
 *   - score: objet contenant les détails du meilleur score de l'utilisateur
 *   - classement: position de l'utilisateur dans le classement général
 */
$router->get('/api.php/utilisateur/profil', function () use ($pdo) {
    // Vérification du jeton
    if (!isset($_GET['jeton'])) {
        echo json_encode(['reussite' => false, 'erreurs' => JETON_UNSET]);
        return;
    }
    
    $jeton = htmlspecialchars($_GET['jeton']);
    
    // Récupération des informations de l'utilisateur
    $reqUtilisateur = $pdo->prepare("
        SELECT u.id, u.nom_utilisateur, u.date_inscription
        FROM Jetons j
        JOIN Utilisateurs u ON j.id_utilisateur = u.id
        WHERE j.data_jeton = :jeton AND j.date_expiration >= NOW()
    ");
    
    $reqUtilisateur->execute(['jeton' => $jeton]);
    $utilisateur = $reqUtilisateur->fetch();
    
    if (!$utilisateur) {
        echo json_encode(['reussite' => false, 'erreurs' => JETON_INVALIDE]);
        return;
    }
    
    // Récupération du score de l'utilisateur
    $reqScore = $pdo->prepare("
        SELECT id, score, temps_partie, experience, ennemis_enleve, date_soumission
        FROM Scores
        WHERE id_utilisateur = :id_utilisateur
    ");
    
    $reqScore->execute(['id_utilisateur' => $utilisateur['id']]);
    $score = $reqScore->fetch();
    
    // Calcul du classement de l'utilisateur
    $classement = null;
    if ($score) {
        $reqClassement = $pdo->prepare("
            SELECT COUNT(*) + 1 as rang
            FROM Scores
            WHERE score > :score
        ");
        
        $reqClassement->execute(['score' => $score['score']]);
        $rangData = $reqClassement->fetch();
        $classement = $rangData['rang'];
    }
    
    // Construction de la réponse
    $response = [
        'reussite' => true,
        'utilisateur' => [
            'id' => $utilisateur['id'],
            'nom_utilisateur' => $utilisateur['nom_utilisateur'],
            'date_inscription' => $utilisateur['date_inscription'] ?? date('Y-m-d H:i:s')
        ],
        'score' => $score ?: null,
        'classement' => $classement
    ];
    
    echo json_encode($response);
});

// Acheminer la requête
$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);


