<?php
namespace App\Controllers;

use App\Models\Utilisateur;
use App\Config\Permissions; // Ajouter cette ligne
use App\Core\View;

class AuthController {
    private $model;
    private $twig;
    private $utilisateurModel;

    public function __construct($db, $twig) {
        $this->model = new Utilisateur($db);
        $this->twig = $twig;
        $this->utilisateurModel = new Utilisateur();
    }

    public function showLogin() {
        View::render('auth/login.twig');
    }

    public function login($post) {
        $email = $post['email'] ?? '';
        $password = $post['password'] ?? '';
        
        $user = $this->utilisateurModel->getByEmail($email);

        if ($user && password_verify($password, $user['mot_de_passe'])) {
            // Vérification stricte du rôle pour s'assurer qu'il correspond à une constante
            $role = $user['role'];
            if ($role === Permissions::ROLE_ADMIN || $role === Permissions::ROLE_GESTIONNAIRE || $role === Permissions::ROLE_EMPLOYE) {
                // Le rôle est valide
                error_log("Rôle valide: $role");
            } else {
                // Rôle non reconnu, utiliser un rôle par défaut
                error_log("Rôle non reconnu: $role, utilisation du rôle par défaut");
                $role = Permissions::ROLE_EMPLOYE; // Rôle par défaut
            }
            
            // Stocker l'utilisateur en session avec le rôle validé
            $_SESSION['user'] = [
                'id' => $user['id'],
                'nom' => $user['nom'],
                'prenom' => $user['prenom'] ?? '',
                'email' => $user['email'],
                'role' => $role // Rôle validé
            ];
            
            // Debug
            error_log("Connexion utilisateur réussie: " . print_r($_SESSION['user'], true));
            
            // Redirection
            $redirect = $_SESSION['redirect_after_login'] ?? 'index.php?uri=dashboard';
            unset($_SESSION['redirect_after_login']);
            header("Location: $redirect");
            exit;
        } else {
            // Échec de la connexion
            View::render('auth/login.twig', ['error' => 'Identifiants invalides']);
        }
    }

    public function logout() {
        // Supprimer les données de session
        unset($_SESSION['user']);
        session_destroy();
        
        // Rediriger vers la page de connexion
        header('Location: index.php?uri=login');
        exit;
    }
}
