<?php
namespace App\Controllers;

use App\Models\Produit;
use App\Models\Categorie;
use App\Models\Fournisseur;
use App\Middleware\AuthMiddleware;
use App\Config\Permissions;
use Twig\Environment;

class ProduitController {
    private $twig;
    private $produitModel;
    private $categorieModel;
    private $fournisseurModel;

    public function __construct(Environment $twig) {
        $this->twig = $twig;
        $this->produitModel = new Produit();
        $this->categorieModel = new Categorie();
        $this->fournisseurModel = new Fournisseur();
    }

    public function index() {
        // Vérifier les permissions
        AuthMiddleware::requirePermission(Permissions::MODULE_PRODUITS, Permissions::ACTION_VIEW);
        
        try {
            // Paramètres de recherche
            $search = [
                'nom' => $_GET['nom'] ?? '',
                'categorie_id' => $_GET['categorie_id'] ?? '',
                'fournisseur_id' => $_GET['fournisseur_id'] ?? '',
                'stock_filter' => $_GET['stock_filter'] ?? ''
            ];
            
            // Récupérer les données pour les filtres
            $categories = $this->categorieModel->getAll();
            $fournisseurs = $this->fournisseurModel->getAll();
            
            // Filtrer les produits ou récupérer tous les produits
            $produits = empty(array_filter($search)) ? 
                $this->produitModel->getAll() : 
                $this->produitModel->search($search);
            
            // Rendre la vue
            echo $this->twig->render('produits.html.twig', [
                'produits' => $produits,
                'categories' => $categories,
                'fournisseurs' => $fournisseurs,
                'search' => $search,
                'current_page' => 'produits',
                'can_add' => AuthMiddleware::checkPermission(Permissions::MODULE_PRODUITS, Permissions::ACTION_ADD),
                'can_edit' => AuthMiddleware::checkPermission(Permissions::MODULE_PRODUITS, Permissions::ACTION_EDIT),
                'can_delete' => AuthMiddleware::checkPermission(Permissions::MODULE_PRODUITS, Permissions::ACTION_DELETE)
            ]);
        } catch (\Exception $e) {
            error_log("Erreur dans ProduitController::index : " . $e->getMessage());
            echo $this->twig->render('error.html.twig', [
                'message' => 'Erreur lors du chargement des produits: ' . $e->getMessage()
            ]);
        }
    }

    public function show($id) {
        try {
            $produit = $this->produitModel->getById($id);
            if (!$produit) {
                $this->renderError("Produit non trouvé");
                return;
            }
            echo $this->twig->render('produit_details.html.twig', [
                'produit' => $produit
            ]);
        } catch (\Exception $e) {
            error_log("Erreur dans show : " . $e->getMessage());
            $this->renderError("Impossible d'afficher le produit");
        }
    }

    private function getCategoriesForForm() {
        try {
            return $this->categorieModel->getAll();
        } catch (\Exception $e) {
            error_log("Erreur lors de la récupération des catégories : " . $e->getMessage());
            return [];
        }
    }

    public function create() {
        // Vérifier les permissions
        AuthMiddleware::requirePermission(Permissions::MODULE_PRODUITS, Permissions::ACTION_ADD);
        
        try {
            $categories = $this->categorieModel->getAll();
            $fournisseurs = $this->fournisseurModel->getAll();
            
            echo $this->twig->render('ajouter-produit.html.twig', [
                'categories' => $categories,
                'fournisseurs' => $fournisseurs,
                'current_page' => 'produits'
            ]);
        } catch (\Exception $e) {
            error_log("Erreur dans ProduitController::create : " . $e->getMessage());
            echo $this->twig->render('error.html.twig', [
                'message' => 'Erreur lors du chargement du formulaire d\'ajout.'
            ]);
        }
    }

    public function store() {
        // Vérifier les permissions
        AuthMiddleware::requirePermission(Permissions::MODULE_PRODUITS, Permissions::ACTION_ADD);
        
        try {
            // Validation du formulaire
            $errors = [];
            
            if (empty($_POST['nom'])) {
                $errors['nom'] = 'Le nom du produit est obligatoire.';
            }
            
            if (empty($_POST['categorie_id'])) {
                $errors['categorie_id'] = 'La catégorie est obligatoire.';
            }
            
            if (!is_numeric($_POST['prix']) || $_POST['prix'] < 0) {
                $errors['prix'] = 'Le prix doit être un nombre positif.';
            }
            
            if (!is_numeric($_POST['quantite']) || $_POST['quantite'] < 0) {
                $errors['quantite'] = 'La quantité doit être un nombre positif.';
            }
            
            if (!empty($errors)) {
                // En cas d'erreur, réafficher le formulaire avec les messages
                $categories = $this->categorieModel->getAll();
                $fournisseurs = $this->fournisseurModel->getAll();
                
                echo $this->twig->render('ajouter-produit.html.twig', [
                    'categories' => $categories,
                    'fournisseurs' => $fournisseurs,
                    'errors' => $errors,
                    'values' => $_POST,
                    'current_page' => 'produits'
                ]);
                return;
            }
            
            // Préparation des données
            $data = [
                'nom' => $_POST['nom'],
                'description' => $_POST['description'] ?? null,
                'prix' => $_POST['prix'],
                'quantite' => $_POST['quantite'],
                'categorie_id' => $_POST['categorie_id'],
                'fournisseur_id' => $_POST['fournisseur_id'],
                'reference' => $_POST['reference'] ?? null,
                'seuil_alerte' => $_POST['seuil_alerte'] ?? 5,
                'emplacement' => $_POST['emplacement'] ?? null
            ];
            
            // Insertion en base de données
            $result = $this->produitModel->create($data);
            
            if ($result) {
                // Redirection avec message de succès
                $_SESSION['message'] = [
                    'type' => 'success',
                    'text' => 'Le produit a été ajouté avec succès.'
                ];
                header('Location: index.php?uri=produits');
                exit;
            } else {
                throw new \Exception("Échec de l'ajout du produit.");
            }
        } catch (\Exception $e) {
            error_log("Erreur dans ProduitController::store : " . $e->getMessage());
            echo $this->twig->render('error.html.twig', [
                'message' => 'Erreur lors de l\'ajout du produit: ' . $e->getMessage()
            ]);
        }
    }

    public function edit($id) {
        // Vérifier les permissions
        AuthMiddleware::requirePermission(Permissions::MODULE_PRODUITS, Permissions::ACTION_EDIT);
        
        try {
            $produit = $this->produitModel->getById($id);
            
            if (!$produit) {
                $_SESSION['message'] = [
                    'type' => 'error',
                    'text' => 'Produit non trouvé.'
                ];
                header('Location: index.php?uri=produits');
                exit;
            }
            
            $categories = $this->categorieModel->getAll();
            $fournisseurs = $this->fournisseurModel->getAll();
            
            echo $this->twig->render('modifier-produit.html.twig', [
                'produit' => $produit,
                'categories' => $categories,
                'fournisseurs' => $fournisseurs,
                'current_page' => 'produits'
            ]);
        } catch (\Exception $e) {
            error_log("Erreur dans ProduitController::edit : " . $e->getMessage());
            echo $this->twig->render('error.html.twig', [
                'message' => 'Erreur lors du chargement du formulaire de modification.'
            ]);
        }
    }

    public function update($id) {
        // Vérifier les permissions
        AuthMiddleware::requirePermission(Permissions::MODULE_PRODUITS, Permissions::ACTION_EDIT);
        
        try {
            // Validation du formulaire
            $errors = [];
            
            if (empty($_POST['nom'])) {
                $errors['nom'] = 'Le nom du produit est obligatoire.';
            }
            
            if (empty($_POST['categorie_id'])) {
                $errors['categorie_id'] = 'La catégorie est obligatoire.';
            }
            
            if (!is_numeric($_POST['prix']) || $_POST['prix'] < 0) {
                $errors['prix'] = 'Le prix doit être un nombre positif.';
            }
            
            if (!is_numeric($_POST['quantite']) || $_POST['quantite'] < 0) {
                $errors['quantite'] = 'La quantité doit être un nombre positif.';
            }
            
            if (!empty($errors)) {
                // En cas d'erreur, réafficher le formulaire avec les messages
                $categories = $this->categorieModel->getAll();
                $fournisseurs = $this->fournisseurModel->getAll();
                $_POST['id'] = $id;
                
                echo $this->twig->render('modifier-produit.html.twig', [
                    'produit' => $_POST,
                    'categories' => $categories,
                    'fournisseurs' => $fournisseurs,
                    'errors' => $errors,
                    'current_page' => 'produits'
                ]);
                return;
            }
            
            // Préparation des données
            $data = [
                'id' => $id,
                'nom' => $_POST['nom'],
                'description' => $_POST['description'] ?? null,
                'prix' => $_POST['prix'],
                'quantite' => $_POST['quantite'],
                'categorie_id' => $_POST['categorie_id'],
                'fournisseur_id' => $_POST['fournisseur_id'],
                'reference' => $_POST['reference'] ?? null,
                'seuil_alerte' => $_POST['seuil_alerte'] ?? 5,
                'emplacement' => $_POST['emplacement'] ?? null
            ];
            
            // Mise à jour en base de données
            $result = $this->produitModel->update($data);
            
            if ($result) {
                // Redirection avec message de succès
                $_SESSION['message'] = [
                    'type' => 'success',
                    'text' => 'Le produit a été mis à jour avec succès.'
                ];
                header('Location: index.php?uri=produits');
                exit;
            } else {
                throw new \Exception("Échec de la mise à jour du produit.");
            }
        } catch (\Exception $e) {
            error_log("Erreur dans ProduitController::update : " . $e->getMessage());
            echo $this->twig->render('error.html.twig', [
                'message' => 'Erreur lors de la mise à jour du produit: ' . $e->getMessage()
            ]);
        }
    }

    public function delete($id) {
        // Vérifier les permissions
        AuthMiddleware::requirePermission(Permissions::MODULE_PRODUITS, Permissions::ACTION_DELETE);
        
        try {
            $produit = $this->produitModel->getById($id);
            
            if (!$produit) {
                $_SESSION['message'] = [
                    'type' => 'error',
                    'text' => 'Produit non trouvé.'
                ];
                header('Location: index.php?uri=produits');
                exit;
            }
            
            // Suppression du produit
            $result = $this->produitModel->delete($id);
            
            if ($result) {
                $_SESSION['message'] = [
                    'type' => 'success',
                    'text' => 'Le produit a été supprimé avec succès.'
                ];
            } else {
                $_SESSION['message'] = [
                    'type' => 'error',
                    'text' => 'Échec de la suppression du produit.'
                ];
            }
        } catch (\Exception $e) {
            error_log("Erreur dans ProduitController::delete : " . $e->getMessage());
            $_SESSION['message'] = [
                'type' => 'error',
                'text' => 'Erreur lors de la suppression du produit.'
            ];
        }
        
        // Redirection vers la liste des produits
        header('Location: index.php?uri=produits');
        exit;
    }
}
