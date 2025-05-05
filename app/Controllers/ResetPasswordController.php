<?php
namespace App\Controllers;

use App\Models\ReinitialisationMDP;
use App\Models\Utilisateur;
use App\Core\View;

class ResetPasswordController {
    private $model;
    private $userModel;

    public function __construct($db) {
        $this->model = new ReinitialisationMDP($db);
        $this->userModel = new Utilisateur($db);
    }

    public function showForm() {
        View::render('auth/forgot.twig');
    }

    public function requestReset($post) {
        $user = $this->userModel->getByEmail($post['email']);
        if (!$user) {
            View::render('auth/forgot.twig', ['error' => "Utilisateur inconnu"]);
            return;
        }

        $token = bin2hex(random_bytes(32));
        $expiration = date('Y-m-d H:i:s', time() + 3600);
        $this->model->create($user['id'], $token, $expiration);

        // À remplacer par un vrai envoi d’email :
        View::render('auth/reset-link.twig', ['lien' => "http://localhost/reset/{$token}"]);
    }

    public function showResetForm($token) {
        $entry = $this->model->getByToken($token);
        if (!$entry || strtotime($entry['expiration']) < time()) {
            View::render('auth/error.twig', ['message' => 'Lien invalide ou expiré']);
            return;
        }

        View::render('auth/reset.twig', ['token' => $token]);
    }

    public function resetPassword($token, $post) {
        $entry = $this->model->getByToken($token);
        if (!$entry) {
            View::render('auth/error.twig', ['message' => 'Lien invalide']);
            return;
        }

        $newPassword = password_hash($post['mot_de_passe'], PASSWORD_BCRYPT);
        $this->userModel->update($entry['utilisateur_id'], [
            'nom' => '', 'email' => '', 'role' => '', // Pas modifiés ici
            'mot_de_passe' => $newPassword
        ]);

        $this->model->deleteByToken($token);
        header("Location: /login");
    }
}

