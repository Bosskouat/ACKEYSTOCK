<?php
// filepath: c:\www\ACKEYSTOCK\app\Controllers\SortieStockController.php
namespace App\Controllers;

use App\Models\SortieStock;
use App\Models\Produit;
use Twig\Environment;

class SortieStockController {
    private $twig;
    private $sortieModel;
    private $produitModel;

    public function __construct(Environment $twig) {
        $this->twig = $twig;
        $this->sortieModel = new SortieStock();
        $this->produitModel = new Produit();
    }

    public function index() {
        try {
            $sorties = $this->sortieModel->getAll();
            
            echo $this->twig->render('stock/sorties-index.html.twig', [
                'sorties' => $sorties,
                'current_page' => 'sorties-stock'
            ]);
        } catch (\Exception $e) {
            $this->renderError("Erreur lors du chargement des sorties: " . $e->getMessage());
        }
    }

    public function create() {
        try {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Validation
                if (empty($_POST['produit_id']) || empty($_POST['quantite']) || empty($_POST['motif'])) {
                    $_SESSION['message'] = ['type' => 'error', 'text' => 'Tous les champs obligatoires doivent être remplis'];
                    $this->redirectWithFormData('ajouter-sortie', $_POST);
                    return;
                }

                // Création de la sortie
                $result = $this->sortieModel->create($_POST);

                if ($result['success']) {
                    $_SESSION['message'] = ['type' => 'success', 'text' => 'Sortie de stock ajoutée avec succès'];
                    header('Location: index.php?uri=sorties-stock');
                } else {
                    $_SESSION['message'] = ['type' => 'error', 'text' => $result['message'] ?? 'Erreur lors de l\'ajout de la sortie'];
                    $this->redirectWithFormData('ajouter-sortie', $_POST);
                }
                exit;
            }

            // Affichage du formulaire
            $produits = $this->produitModel->getAllWithStock();
            
            echo $this->twig->render('stock/sortie-form.html.twig', [
                'produits' => $produits,
                'form_data' => $_SESSION['form_data'] ?? [],
                'current_page' => 'sorties-stock'
            ]);
            
            // Nettoyer les données de formulaire
            if (isset($_SESSION['form_data'])) {
                unset($_SESSION['form_data']);
            }
        } catch (\Exception $e) {
            $this->renderError("Erreur lors de la création de sortie: " . $e->getMessage());
        }
    }

    public function delete($id) {
        try {
            $success = $this->sortieModel->delete($id);
            
            if ($success) {
                $_SESSION['message'] = ['type' => 'success', 'text' => 'Sortie supprimée avec succès'];
            } else {
                $_SESSION['message'] = ['type' => 'error', 'text' => 'Erreur lors de la suppression de la sortie'];
            }
        } catch (\Exception $e) {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Une erreur est survenue: ' . $e->getMessage()];
        }
        
        header('Location: index.php?uri=sorties-stock');
        exit;
    }

    private function redirectWithFormData($uri, $data) {
        $_SESSION['form_data'] = $data;
        header("Location: index.php?uri=$uri");
        exit;
    }

    private function renderError($message) {
        $_SESSION['message'] = ['type' => 'error', 'text' => $message];
        header('Location: index.php?uri=sorties-stock');
        exit;
    }
}
