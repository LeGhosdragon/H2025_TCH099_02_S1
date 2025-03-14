<?php
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
    $router->post('/api.php/inscription', function() use ($pdo) {

        // Validation de l'identifiant
        if (isset($_GET['identifiant'])) {
            //Nettoie l'identifiant
            $identifiant = trim(htmlspecialchars($_GET['identifiant']));
            
            //Valide que l'identifiant nest pas vide
            if($identifiant === ''){
                echo json_encode(['reussite' => false, 'erreurs' => "ID_VIDE"]);
                return;
            }


            //S'assure que l'identifiant n'a pas déja été choisis
            $validPasseUnique = $pdo->prepare("SELECT count(*) FROM Utilisateurs WHERE id_utilisateur = :identifiant");
            $validPasseUnique->execute(['identifiant' => $identifiant]);

            $res = $validPasseUnique->fetch();
            if($res['count(*)'] != 0){
                echo json_encode(['reussite' => false, 'erreurs' => "ID_DOUBLE"]);
                return;
            }
        } else {
            echo json_encode(['reussite' => false, 'erreurs' => "ID_UNSET"]);
            return;
        }

        //Validations du mot de passe
        $erreurs = [];
        if(!isset($_GET['passe'])){
            $erreurs[] =  'PASSE_UNSET';
        }

        //Nettoie le mot de passe
        $passe = htmlspecialchars($_GET['passe']);  

        //valide le mot de passe: il doit y avoir entre 8 et 32 characteres
        if(strlen($passe) < 8 || strlen($passe) > 32){
            $erreurs[] = 'PASSE_TAILLE_INV: ' .  strlen($passe) . ' ATTENDU: ENTRE 8 ET 32 ';
        }
        //Valide le mot de passe: Contiens au moins une majuscule
        if(!preg_match('/[A-Z]/', $passe)){
            $erreurs[] = 'PASSE AUCUNE MAJUSCULE';
        }
        //Valide le mot de passe: Contiens au moins une minuscule
        if(!preg_match('/[a-z]/', $passe)){
            $erreurs[] = 'PASSE AUCUNE MINUSCULE';
        }
        //Valide le mot de passe: Contiens au moins un chiffre
        if(!preg_match('/[0-9]/', $passe)){
            $erreurs[] = 'PASSE AUCUN CHIFFRE';
        }

        //Retourne les erreurs si il y en a
        if (!empty($erreurs)) {
            echo json_encode(['reussite' => false, 'erreurs' => $erreurs]);
            return;
        }
 
        $mpdHash = password_hash($passe,PASSWORD_BCRYPT);

        //Requete d'inscription a la base de donnees
        $requeteInscription = $pdo->prepare("INSERT INTO Utilisateurs (id_utilisateur, mot_de_passe) VALUES (:identifiant, :passe)");
        $requeteInscription->execute([
            'identifiant' => $identifiant,
            'passe' => $mpdHash
        ]);

        $resInscription = $requeteInscription->fetch();
        echo json_encode(['reussite' => true]);
        return;
    });


    // Acheminer la requête
    $router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);

?>
