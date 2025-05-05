<?php
namespace App\Controllers;

use App\Models\Fournisseur;
use App\Middleware\AuthMiddleware;
use App\Config\Permissions;
use Twig\Environment;

class FournisseurController {
    private $twig;
    private $fournisseurModel;

    public function __construct(Environment $twig) {
        $this->twig = $twig;
        $this->fournisseurModel = new Fournisseur();
    }

    public function index() {
        // Vérifier les permissions
        AuthMiddleware::requirePermission(Permissions::MODULE_FOURNISSEURS, Permissions::ACTION_VIEW);
        
        try {
            $fournisseurs = $this->fournisseurModel->getAll();
            
            echo $this->twig->render('fournisseurs.html.twig', [
                'fournisseurs' => $fournisseurs,
                'current_page' => 'fournisseurs',
                'can_add' => AuthMiddleware::checkPermission(Permissions::MODULE_FOURNISSEURS, Permissions::ACTION_ADD),
                'can_edit' => AuthMiddleware::checkPermission(Permissions::MODULE_FOURNISSEURS, Permissions::ACTION_EDIT),
                'can_delete' => AuthMiddleware::checkPermission(Permissions::MODULE_FOURNISSEURS, Permissions::ACTION_DELETE),
                'message' => $_SESSION['message'] ?? null
            ]);
            unset($_SESSION['message']);
        } catch (\Exception $e) {
            error_log("Erreur dans FournisseurController::index : " . $e->getMessage());
            echo $this->twig->render('error.html.twig', [
                'message' => 'Erreur lors du chargement des fournisseurs: ' . $e->getMessage()
            ]);
        }
    }

    public function showAddForm() {
        // Vérifier les permissions
        AuthMiddleware::requirePermission(Permissions::MODULE_FOURNISSEURS, Permissions::ACTION_ADD);
        
        try {
            echo $this->twig->render('ajouter-fournisseur.html.twig', [
                'current_page' => 'fournisseurs'
            ]);
        } catch (\Exception $e) {
            error_log("Erreur dans FournisseurController::showAddForm : " . $e->getMessage());
            echo $this->twig->render('error.html.twig', [
                'message' => 'Erreur lors du chargement du formulaire d\'ajout de fournisseur.'
            ]);
        }
    }

    public function create() {
        // Vérifier les permissions
        AuthMiddleware::requirePermission(Permissions::MODULE_FOURNISSEURS, Permissions::ACTION_ADD);
        
        try {
            // Validation
            $errors = [];
            
            if (empty($_POST['nom'])) {
                $errors['nom'] = 'Le nom du fournisseur est obligatoire.';
            }
            
            if (empty($_POST['email'])) {
                $errors['email'] = 'L\'email du fournisseur est obligatoire.';
            } elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'L\'email est invalide.';
            }
            
            if (!empty($errors)) {
                echo $this->twig->render('ajouter-fournisseur.html.twig', [
                    'errors' => $errors,
                    'values' => $_POST,
                    'current_page' => 'fournisseurs'
                ]);
                return;
            }
            
            // Préparation des données
            $data = [
                'nom' => $_POST['nom'],
                'adresse' => $_POST['adresse'] ?? null,
                'email' => $_POST['email'],
                'telephone' => $_POST['telephone'] ?? null,
                'contact' => $_POST['contact'] ?? null
            ];
            
            // Insertion en base de données
            $result = $this->fournisseurModel->create($data);
            
            if ($result) {
                $_SESSION['message'] = [
                    'type' => 'success',
                    'text' => 'Le fournisseur a été ajouté avec succès.'
                ];
                header('Location: index.php?uri=fournisseurs');
                exit;
            } else {
                throw new \Exception('Échec de l\'ajout du fournisseur.');
            }
        } catch (\Exception $e) {
            error_log("Erreur dans FournisseurController::create : " . $e->getMessage());
            echo $this->twig->render('ajouter-fournisseur.html.twig', [
                'error' => 'Erreur lors de l\'ajout du fournisseur: ' . $e->getMessage(),
                'values' => $_POST,
                'current_page' => 'fournisseurs'
            ]);
        }
    }

    public function edit($id) {
        // Vérifier les permissions
        AuthMiddleware::requirePermission(Permissions::MODULE_FOURNISSEURS, Permissions::ACTION_EDIT);
        
        try {
            $fournisseur = $this->fournisseurModel->getById($id);
            
            if (!$fournisseur) {
                $_SESSION['message'] = [
                    'type' => 'error',
                    'text' => 'Fournisseur non trouvé.'
                ];
                header('Location: index.php?uri=fournisseurs');
                exit;
            }
            
            echo $this->twig->render('modifier-fournisseur.html.twig', [
                'fournisseur' => $fournisseur,
                'current_page' => 'fournisseurs'
            ]);
        } catch (\Exception $e) {
            error_log("Erreur dans FournisseurController::edit : " . $e->getMessage());
            echo $this->twig->render('error.html.twig', [
                'message' => 'Erreur lors du chargement du formulaire de modification: ' . $e->getMessage()
            ]);
        }
    }

    public function update($id) {
        // Vérifier les permissions
        AuthMiddleware::requirePermission(Permissions::MODULE_FOURNISSEURS, Permissions::ACTION_EDIT);
        
        try {
            // Validation
            $errors = [];
            
            if (empty($_POST['nom'])) {
                $errors['nom'] = 'Le nom du fournisseur est obligatoire.';
            }
            
            if (empty($_POST['email'])) {
                $errors['email'] = 'L\'email du fournisseur est obligatoire.';
            } elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'L\'email est invalide.';
            }
            
            if (!empty($errors)) {
                echo $this->twig->render('modifier-fournisseur.html.twig', [
                    'errors' => $errors,
                    'fournisseur' => array_merge(['id' => $id], $_POST),
                    'current_page' => 'fournisseurs'
                ]);
                return;
            }
            
            // Préparation des données
            $data = [
                'nom' => $_POST['nom'],
                'adresse' => $_POST['adresse'] ?? null,
                'email' => $_POST['email'],
                'telephone' => $_POST['telephone'] ?? null,
                'contact' => $_POST['contact'] ?? null
            ];
            
            // Mise à jour en base de données
            $result = $this->fournisseurModel->update($id, $data);
            
            if ($result) {
                $_SESSION['message'] = [
                    'type' => 'success',
                    'text' => 'Le fournisseur a été mis à jour avec succès.'
                ];
                header('Location: index.php?uri=fournisseurs');
                exit;
            } else {
                throw new \Exception('Échec de la mise à jour du fournisseur.');
            }
        } catch (\Exception $e) {
            error_log("Erreur dans FournisseurController::update : " . $e->getMessage());
            echo $this->twig->render('modifier-fournisseur.html.twig', [
                'error' => 'Erreur lors de la modification du fournisseur: ' . $e->getMessage(),
                'fournisseur' => array_merge(['id' => $id], $_POST),
                'current_page' => 'fournisseurs'
            ]);
        }
    }

    public function delete($id) {
        // Vérifier les permissions
        AuthMiddleware::requirePermission(Permissions::MODULE_FOURNISSEURS, Permissions::ACTION_DELETE);
        
        try {
            $result = $this->fournisseurModel->delete($id);
            
            if ($result) {
                $_SESSION['message'] = [
                    'type' => 'success',
                    'text' => 'Le fournisseur a été supprimé avec succès.'
                ];
            } else {
                $_SESSION['message'] = [
                    'type' => 'error',
                    'text' => 'Échec de la suppression du fournisseur.'
                ];
            }
            
            header('Location: index.php?uri=fournisseurs');
            exit;
        } catch (\Exception $e) {
            error_log("Erreur dans FournisseurController::delete : " . $e->getMessage());
            $_SESSION['message'] = [
                'type' => 'error',
                'text' => 'Erreur lors de la suppression du fournisseur: ' . $e->getMessage()
            ];
            header('Location: index.php?uri=fournisseurs');
            exit;
        }
    }
}
