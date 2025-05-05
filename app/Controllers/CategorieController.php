<?php
namespace App\Controllers;

use App\Models\Categorie;
use App\Middleware\AuthMiddleware;
use App\Config\Permissions;
use Twig\Environment;

class CategorieController {
    private $twig;
    private $categorieModel;

    public function __construct(Environment $twig) {
        $this->twig = $twig;
        $this->categorieModel = new Categorie();
    }

    public function index() {
        // Vérifier les permissions
        AuthMiddleware::requirePermission(Permissions::MODULE_CATEGORIES, Permissions::ACTION_VIEW);
        
        try {
            $categories = $this->categorieModel->getAll();
            
            echo $this->twig->render('categories.html.twig', [
                'categories' => $categories,
                'current_page' => 'categories',
                'can_add' => AuthMiddleware::checkPermission(Permissions::MODULE_CATEGORIES, Permissions::ACTION_ADD),
                'can_edit' => AuthMiddleware::checkPermission(Permissions::MODULE_CATEGORIES, Permissions::ACTION_EDIT),
                'can_delete' => AuthMiddleware::checkPermission(Permissions::MODULE_CATEGORIES, Permissions::ACTION_DELETE),
                'message' => $_SESSION['message'] ?? null
            ]);
            unset($_SESSION['message']);
        } catch (\Exception $e) {
            error_log("Erreur dans CategorieController::index : " . $e->getMessage());
            echo $this->twig->render('error.html.twig', [
                'message' => 'Erreur lors du chargement des catégories: ' . $e->getMessage()
            ]);
        }
    }

    public function showAddForm() {
        // Vérifier les permissions
        AuthMiddleware::requirePermission(Permissions::MODULE_CATEGORIES, Permissions::ACTION_ADD);
        
        try {
            echo $this->twig->render('ajouter-categorie.html.twig', [
                'current_page' => 'categories'
            ]);
        } catch (\Exception $e) {
            error_log("Erreur dans CategorieController::showAddForm : " . $e->getMessage());
            echo $this->twig->render('error.html.twig', [
                'message' => 'Erreur lors du chargement du formulaire d\'ajout de catégorie.'
            ]);
        }
    }

    public function create() {
        // Vérifier les permissions
        AuthMiddleware::requirePermission(Permissions::MODULE_CATEGORIES, Permissions::ACTION_ADD);
        
        try {
            // Validation
            if (empty($_POST['nom'])) {
                echo $this->twig->render('ajouter-categorie.html.twig', [
                    'error' => 'Le nom de la catégorie est obligatoire.',
                    'values' => $_POST,
                    'current_page' => 'categories'
                ]);
                return;
            }
            
            // Préparation des données
            $data = [
                'nom' => $_POST['nom'],
                'description' => $_POST['description'] ?? null
            ];
            
            // Insertion en base de données
            $result = $this->categorieModel->create($data);
            
            if ($result) {
                $_SESSION['message'] = [
                    'type' => 'success',
                    'text' => 'La catégorie a été ajoutée avec succès.'
                ];
                header('Location: index.php?uri=categories');
                exit;
            } else {
                throw new \Exception('Échec de l\'ajout de la catégorie.');
            }
        } catch (\Exception $e) {
            error_log("Erreur dans CategorieController::create : " . $e->getMessage());
            echo $this->twig->render('ajouter-categorie.html.twig', [
                'error' => 'Erreur lors de l\'ajout de la catégorie: ' . $e->getMessage(),
                'values' => $_POST,
                'current_page' => 'categories'
            ]);
        }
    }

    public function edit($id) {
        // Vérifier les permissions
        AuthMiddleware::requirePermission(Permissions::MODULE_CATEGORIES, Permissions::ACTION_EDIT);
        
        try {
            $categorie = $this->categorieModel->getById($id);
            
            if (!$categorie) {
                $_SESSION['message'] = [
                    'type' => 'error',
                    'text' => 'Catégorie non trouvée.'
                ];
                header('Location: index.php?uri=categories');
                exit;
            }
            
            echo $this->twig->render('modifier-categorie.html.twig', [
                'categorie' => $categorie,
                'current_page' => 'categories'
            ]);
        } catch (\Exception $e) {
            error_log("Erreur dans CategorieController::edit : " . $e->getMessage());
            echo $this->twig->render('error.html.twig', [
                'message' => 'Erreur lors du chargement du formulaire de modification: ' . $e->getMessage()
            ]);
        }
    }

    public function update($id) {
        // Vérifier les permissions
        AuthMiddleware::requirePermission(Permissions::MODULE_CATEGORIES, Permissions::ACTION_EDIT);
        
        try {
            // Validation
            if (empty($_POST['nom'])) {
                echo $this->twig->render('modifier-categorie.html.twig', [
                    'error' => 'Le nom de la catégorie est obligatoire.',
                    'categorie' => array_merge(['id' => $id], $_POST),
                    'current_page' => 'categories'
                ]);
                return;
            }
            
            // Préparation des données
            $data = [
                'nom' => $_POST['nom'],
                'description' => $_POST['description'] ?? null
            ];
            
            // Mise à jour en base de données
            $result = $this->categorieModel->update($id, $data);
            
            if ($result) {
                $_SESSION['message'] = [
                    'type' => 'success',
                    'text' => 'La catégorie a été mise à jour avec succès.'
                ];
                header('Location: index.php?uri=categories');
                exit;
            } else {
                throw new \Exception('Échec de la mise à jour de la catégorie.');
            }
        } catch (\Exception $e) {
            error_log("Erreur dans CategorieController::update : " . $e->getMessage());
            echo $this->twig->render('modifier-categorie.html.twig', [
                'error' => 'Erreur lors de la modification de la catégorie: ' . $e->getMessage(),
                'categorie' => array_merge(['id' => $id], $_POST),
                'current_page' => 'categories'
            ]);
        }
    }

    public function delete($id) {
        // Vérifier les permissions
        AuthMiddleware::requirePermission(Permissions::MODULE_CATEGORIES, Permissions::ACTION_DELETE);
        
        try {
            $result = $this->categorieModel->delete($id);
            
            if ($result) {
                $_SESSION['message'] = [
                    'type' => 'success',
                    'text' => 'La catégorie a été supprimée avec succès.'
                ];
            } else {
                $_SESSION['message'] = [
                    'type' => 'error',
                    'text' => 'Échec de la suppression de la catégorie.'
                ];
            }
            
            header('Location: index.php?uri=categories');
            exit;
        } catch (\Exception $e) {
            error_log("Erreur dans CategorieController::delete : " . $e->getMessage());
            $_SESSION['message'] = [
                'type' => 'error',
                'text' => 'Erreur lors de la suppression de la catégorie: ' . $e->getMessage()
            ];
            header('Location: index.php?uri=categories');
            exit;
        }
    }
}
