<?php
// filepath: c:\www\ACKEYSTOCK\app\Controllers\EntreeStockController.php
namespace App\Controllers;

use App\Models\EntreeStock;
use App\Models\Produit;
use App\Models\Fournisseur;

class EntreeStockController {
    private $twig;
    private $entreeStockModel;
    private $produitModel;
    private $fournisseurModel;

    public function __construct($twig) {
        $this->twig = $twig;
        $this->entreeStockModel = new EntreeStock();
        $this->produitModel = new Produit();
        $this->fournisseurModel = new Fournisseur();
    }

    /**
     * Afficher la liste des entrées de stock
     */
    public function index() {
        try {
            // Récupérer les données pour les filtres
            $produits = $this->produitModel->getAll();
            $fournisseurs = $this->fournisseurModel->getAll();
            
            // Paramètres de recherche
            $search = [
                'produit_id' => $_GET['produit_id'] ?? '',
                'fournisseur_id' => $_GET['fournisseur_id'] ?? '',
                'date_debut' => $_GET['date_debut'] ?? '',
                'date_fin' => $_GET['date_fin'] ?? ''
            ];
            
            // Récupérer les entrées (filtrées ou toutes)
            $entrees = empty(array_filter($search)) ? 
                      $this->entreeStockModel->getAll() : 
                      $this->entreeStockModel->search($search);
            
            // Récupérer le message de la session s'il existe
            $message = null;
            if (isset($_SESSION['message'])) {
                $message = $_SESSION['message'];
                unset($_SESSION['message']);
            }
            
            // Afficher la vue
            echo $this->twig->render('entrees-stock.html.twig', [
                'entrees' => $entrees,
                'produits' => $produits,
                'fournisseurs' => $fournisseurs,
                'search' => $search,
                'message' => $message,
                'current_page' => 'entrees-stock'
            ]);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            echo $this->twig->render('error.html.twig', [
                'message' => 'Une erreur est survenue lors du chargement des entrées de stock.'
            ]);
        }
    }
    
    /**
     * Afficher le formulaire d'ajout d'une entrée
     */
    public function afficherFormulaire() {
        try {
            $produitModel = new Produit();
            $fournisseurModel = new Fournisseur();
            
            $produits = $produitModel->getAll();
            $fournisseurs = $fournisseurModel->getAll();
            
            // Si un ID est fourni, c'est une modification
            $id = isset($_GET['id']) ? intval($_GET['id']) : null;
            $entree = null;
            
            if ($id) {
                $entree = $this->entreeModel->getById($id);
                if (!$entree) {
                    $_SESSION['message'] = ['type' => 'error', 'text' => 'Entrée non trouvée'];
                    header('Location: index.php?uri=entrees-stock');
                    exit;
                }
            }
            
            echo $this->twig->render('entrees-stock/formulaire.html.twig', [
                'produits' => $produits,
                'fournisseurs' => $fournisseurs,
                'entree' => $entree,
                'current_page' => 'entrees-stock'
            ]);
            
        } catch (\Exception $e) {
            error_log("Erreur dans EntreeStockController::afficherFormulaire: " . $e->getMessage());
            echo $this->twig->render('error.html.twig', [
                'message' => 'Une erreur est survenue lors du chargement du formulaire.'
            ]);
        }
    }
    
    /**
     * Traiter l'ajout/modification d'une entrée
     */
    public function traiterFormulaire() {
        try {
            // Vérifier si c'est un ajout ou une modification
            $id = isset($_POST['id']) ? intval($_POST['id']) : null;
            
            // Collecter les données du formulaire
            $data = [
                'produit_id' => intval($_POST['produit_id']),
                'fournisseur_id' => intval($_POST['fournisseur_id']),
                'quantite' => intval($_POST['quantite']),
                'prix_unitaire' => floatval($_POST['prix_unitaire']),
                'date_mouvement' => $_POST['date_mouvement'],
                'reference_document' => $_POST['reference_document'],
                'commentaire' => $_POST['commentaire']
            ];
            
            // Validation de base
            if ($data['quantite'] <= 0) {
                $_SESSION['message'] = ['type' => 'error', 'text' => 'La quantité doit être positive'];
                if ($id) {
                    header("Location: index.php?uri=modifier-entree&id=$id");
                } else {
                    header("Location: index.php?uri=ajouter-entree");
                }
                exit;
            }
            
            // Traitement selon le mode (ajout ou modification)
            if ($id) {
                $result = $this->entreeModel->update($id, $data);
                $message = $result['success'] ? 'Entrée modifiée avec succès' : 'Erreur: ' . $result['message'];
            } else {
                $result = $this->entreeModel->add($data);
                $message = $result['success'] ? 'Entrée ajoutée avec succès' : 'Erreur: ' . $result['message'];
            }
            
            // Message de feedback et redirection
            $_SESSION['message'] = ['type' => $result['success'] ? 'success' : 'error', 'text' => $message];
            header('Location: index.php?uri=entrees-stock');
            exit;
            
        } catch (\Exception $e) {
            error_log("Erreur dans EntreeStockController::traiterFormulaire: " . $e->getMessage());
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Une erreur est survenue'];
            header('Location: index.php?uri=entrees-stock');
            exit;
        }
    }
    
    /**
     * Supprimer une entrée
     */
    public function supprimer() {
        try {
            $id = isset($_GET['id']) ? intval($_GET['id']) : null;
            
            if (!$id) {
                $_SESSION['message'] = ['type' => 'error', 'text' => 'ID d\'entrée non valide'];
                header('Location: index.php?uri=entrees-stock');
                exit;
            }
            
            $result = $this->entreeModel->delete($id);
            
            $_SESSION['message'] = [
                'type' => $result['success'] ? 'success' : 'error',
                'text' => $result['success'] ? 'Entrée supprimée avec succès' : 'Erreur: ' . $result['message']
            ];
            
            header('Location: index.php?uri=entrees-stock');
            exit;
            
        } catch (\Exception $e) {
            error_log("Erreur dans EntreeStockController::supprimer: " . $e->getMessage());
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Une erreur est survenue lors de la suppression'];
            header('Location: index.php?uri=entrees-stock');
            exit;
        }
    }
}
