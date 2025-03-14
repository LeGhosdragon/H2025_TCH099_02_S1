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

        // Récupérer les données de la requête et valider
       /* $data = file_get_contents('php://input');
        $userData = json_decode($data, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            echo "Erreur: Données JSON invalides.";
            return;
        }
        
        if ($userData === null) {
            echo "Erreur: Les données de l'usager sont invalides.";
            return;
        }*/


        // Validation de l'identifiant
        if (isset($_GET['identifiant'])) {
            //Nettoie l'identifiant
            $identifiant = htmlspecialchars($_GET['identifiant']);

            //S'assure que l'identifiant n'a pas déja été choisis
            $validPasseUnique = $pdo->prepare("SELECT count(*) FROM Utilisateurs WHERE id_utilisateur = :identifiant");
            $validPasseUnique->execute(['identifiant' => $identifiant]);

            $res = $validPasseUnique->fetch();
            if($res['count(*)'] != 0){
                echo json_encode(['reussite' => false, 'erreurs' => "ID_DUPLICATE " . $res['count(*)']]);
                return;
            }
        } else {
            echo json_encode(['reussite' => false, 'erreurs' => "ID_UNSET"]);
            return;
        }

        //Validation du mot de passe
        if(isset($_GET['passe'])){
            //Nettoie le mot de passe
            $passe = htmlspecialchars($_GET['passe']);  
            
            //valide le mot de passe (il doit y avoir entre 8 et 32 characteres, au moins une majuscule, au moins un chiffre )
            if (!preg_match('/^.*(?=.{8,32})(?=.*[A-Z])(?=.*[a-z])(?=.*\d).*$/', $passe,$mdpValid)){
                echo json_encode(['reussite' => false, 'erreurs' => "PASSE_INVALIDE"]);
                return;
            }

        }else{
            echo json_encode(['reussite' => false, 'erreurs' => "PASSE_UNSET"]);
            return;
        }

        
        $mpdHash = password_hash($passe,PASSWORD_BCRYPT);

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
