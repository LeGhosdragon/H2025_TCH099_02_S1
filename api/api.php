<?php

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

    // Route GET statique vers la racine
    $router->get('/', function() {
        echo "Bienvenue à la page d'acceuil!";
    });

    // Route GET dynamique avec l'identifiant de l'utilisateur
    $router->get('/user/{id}', function($id) {
        echo "Identifiant : " . htmlspecialchars($id);
    });

    // Route POST pour créer un utilisateur
    $router->post('/user', function() {

        // Récupérer les données de la requête et valider
        $data = file_get_contents('php://input');
        $userData = json_decode($data, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            echo "Erreur: Données JSON invalides.";
            return;
        }
        
        if ($userData === null) {
            echo "Erreur: Les données de l'usager sont invalides.";
            return;
        }

        // Traiter de la requête
        if (isset($userData['nom'])) {
            echo "Usager créé avec le nom : " . htmlspecialchars($userData['nom']);
        } else {
            echo "Erreur: Il manque le nom.";
        }

    });

    // Mise à jour de l'utilisateur par identifiant
    $router->put('/user/{id}', function($id) {

        // Récupérer les données de la requête et valider
        $data = file_get_contents('php://input');
        $userData = json_decode($data, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            echo "Erreur: Données JSON invalides.";
            return;
        }
        
        if ($userData === null) {
            echo "Erreur: Les données de l'usager sont invalides.";
            return;
        }

        // Traiter de la requête
        if (isset($userData['nom'])) {
            echo "Usager " . htmlspecialchars($id) . " mis à jour avec le nom: " . htmlspecialchars($userData['nom']);
        } else {
            echo "Erreur: Il manque le nom.";
        }
    });

    // Supprimer un utilisateur par identifiant
    $router->delete('/user/{id}', function($id) {
        echo "L'usager " . htmlspecialchars($id) . " fut supprimé.";
    });

    // Afficher tous les utilisateurs
    $router->get('/users', function() {
        
        // Données de test simulant une base de données
        $users = [
            ['id' => 1, 'nom' => 'Frédéric Gendron'],
            ['id' => 2, 'nom' => 'Amina Bouhoum']
        ];
        
        // Répondre avec les données en format JSON
        header('Content-Type: application/json');

        echo json_encode($users);
    
    });

    // Acheminer la requête
    $router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);

?>
