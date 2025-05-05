<?php
namespace App\Controllers;

use App\Models\Utilisateur;
use Twig\Environment;
use App\Middleware\AuthMiddleware;
use App\Config\Permissions;

class UtilisateurController {
    private $twig;
    private $utilisateurModel;

    public function __construct(Environment $twig) {
        $this->twig = $twig;
        $this->utilisateurModel = new Utilisateur();
    }

    public function showAddForm() {
        echo $this->twig->render('ajouter-utilisateur.html.twig', [
            'roles' => ['admin', 'gestionnaire', 'employe']
        ]);
    }

    public function create() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header('Location: index.php?uri=utilisateurs');
                exit;
            }

            $data = [
                'nom' => $_POST['nom'] ?? '',
                'email' => $_POST['email'] ?? '',
                'mot_de_passe' => $_POST['mot_de_passe'] ?? '',
                'role' => $_POST['role'] ?? 'employe'
            ];

            if ($this->utilisateurModel->create($data)) {
                $_SESSION['message'] = ['type' => 'success', 'text' => 'Utilisateur ajouté avec succès'];
            } else {
                $_SESSION['message'] = ['type' => 'error', 'text' => 'Erreur lors de l\'ajout de l\'utilisateur'];
            }

            header('Location: index.php?uri=utilisateurs');
            exit;
        } catch (\Exception $e) {
            error_log("Erreur dans create : " . $e->getMessage());
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Une erreur est survenue'];
            header('Location: index.php?uri=utilisateurs');
            exit;
        }
    }

    public function index() {
        AuthMiddleware::requirePermission(Permissions::MODULE_UTILISATEURS, Permissions::ACTION_VIEW);
        try {
            $utilisateurs = $this->utilisateurModel->getAll();
            echo $this->twig->render('utilisateurs.html.twig', [
                'utilisateurs' => $utilisateurs,
                'message' => $_SESSION['message'] ?? null
            ]);
            unset($_SESSION['message']);
        } catch (\Exception $e) {
            error_log("Erreur dans index : " . $e->getMessage());
            $this->renderError("Impossible de charger la liste des utilisateurs");
        }
    }

    public function edit($id) {
        try {
            $utilisateur = $this->utilisateurModel->getById($id);
            if (!$utilisateur) {
                $_SESSION['message'] = ['type' => 'error', 'text' => 'Utilisateur non trouvé'];
                header('Location: index.php?uri=utilisateurs');
                exit;
            }

            echo $this->twig->render('modifier-utilisateur.html.twig', [
                'utilisateur' => $utilisateur,
                'roles' => ['admin', 'gestionnaire', 'employe']
            ]);
        } catch (\Exception $e) {
            error_log("Erreur dans edit : " . $e->getMessage());
            $this->renderError("Impossible de modifier l'utilisateur");
        }
    }

    public function update($id) {
        try {
            $data = [
                'nom' => $_POST['nom'] ?? '',
                'email' => $_POST['email'] ?? '',
                'role' => $_POST['role'] ?? '',
                'mot_de_passe' => $_POST['mot_de_passe'] ?? ''
            ];

            if ($this->utilisateurModel->update($id, $data)) {
                $_SESSION['message'] = ['type' => 'success', 'text' => 'Utilisateur modifié avec succès'];
            } else {
                $_SESSION['message'] = ['type' => 'error', 'text' => 'Erreur lors de la modification'];
            }

            header('Location: index.php?uri=utilisateurs');
            exit;
        } catch (\Exception $e) {
            error_log("Erreur dans update : " . $e->getMessage());
            $this->renderError("Impossible de mettre à jour l'utilisateur");
        }
    }

    public function delete($id) {
        try {
            $utilisateur = $this->utilisateurModel->getById($id);
            if (!$utilisateur) {
                $_SESSION['message'] = ['type' => 'error', 'text' => 'Utilisateur non trouvé'];
                header('Location: index.php?uri=utilisateurs');
                exit;
            }

            if ($utilisateur['role'] === 'admin') {
                $_SESSION['message'] = ['type' => 'error', 'text' => 'Impossible de supprimer un administrateur'];
                header('Location: index.php?uri=utilisateurs');
                exit;
            }

            if ($this->utilisateurModel->delete($id)) {
                $_SESSION['message'] = ['type' => 'success', 'text' => 'Utilisateur supprimé avec succès'];
            } else {
                $_SESSION['message'] = ['type' => 'error', 'text' => 'Erreur lors de la suppression'];
            }

            header('Location: index.php?uri=utilisateurs');
            exit;
        } catch (\Exception $e) {
            error_log("Erreur dans delete : " . $e->getMessage());
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Une erreur est survenue'];
            header('Location: index.php?uri=utilisateurs');
            exit;
        }
    }

    public function login() {
        try {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $email = $_POST['email'] ?? '';
                $password = $_POST['password'] ?? '';

                if (empty($email) || empty($password)) {
                    throw new \Exception("Veuillez remplir tous les champs");
                }

                $user = $this->utilisateurModel->authenticate($email, $password);

                if ($user) {
                    $_SESSION['user'] = $user;
                    $_SESSION['message'] = [
                        'type' => 'success',
                        'text' => 'Connexion réussie'
                    ];
                    header('Location: index.php?uri=dashboard');
                    exit;
                } else {
                    throw new \Exception("Email ou mot de passe incorrect");
                }
            }

            echo $this->twig->render('login.html.twig', [
                'message' => $_SESSION['message'] ?? null
            ]);
            unset($_SESSION['message']);

        } catch (\Exception $e) {
            $_SESSION['message'] = [
                'type' => 'error',
                'text' => $e->getMessage()
            ];
            header('Location: index.php?uri=login');
            exit;
        }
    }
}
