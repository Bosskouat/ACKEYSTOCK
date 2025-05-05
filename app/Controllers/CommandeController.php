<?php
namespace App\Controllers;

use App\Models\Commande;
use App\Models\Fournisseur;
use App\Models\Produit;
use App\Middleware\AuthMiddleware; // Ajoutez cette ligne
use App\Config\Permissions; // Ajoutez cette ligne
use Twig\Environment;

class CommandeController {
    private $twig;
    private $commandeModel;
    private $fournisseurModel;
    private $produitModel;

    public function __construct(Environment $twig) {
        $this->twig = $twig;
        $this->commandeModel = new Commande();
        $this->fournisseurModel = new Fournisseur();
        $this->produitModel = new Produit();
    }

    public function index() {
        // Cette vérification est importante
        AuthMiddleware::requirePermission(Permissions::MODULE_COMMANDES, Permissions::ACTION_VIEW);
        
        try {
            $commandes = $this->commandeModel->getAll();
            echo $this->twig->render('commandes.html.twig', [
                'commandes' => $commandes,
                'current_page' => 'commandes',
                'message' => $_SESSION['message'] ?? null
            ]);
            if (isset($_SESSION['message'])) {
                unset($_SESSION['message']);
            }
        } catch (\Exception $e) {
            error_log("Erreur dans CommandeController::index : " . $e->getMessage());
            echo $this->twig->render('error.html.twig', [
                'message' => 'Une erreur est survenue lors du chargement des commandes.'
            ]);
        }
    }

    public function show($id) {
        $commande = $this->model->getById($id);
        $produits = $this->model->getProduits($id);
        View::render('commandes/show.twig', ['commande' => $commande, 'produits' => $produits]);
    }

    public function showAddForm() {
        try {
            // Récupération des données
            $fournisseurs = $this->fournisseurModel->getAll();
            $produits = $this->produitModel->getAllWithStock();

            // Vérification des données récupérées
            if ($fournisseurs === false || $produits === false) {
                throw new \Exception("Erreur lors de la récupération des données");
            }

            // Debug - Affichage des données récupérées
            error_log("Fournisseurs : " . print_r($fournisseurs, true));
            error_log("Produits : " . print_r($produits, true));

            echo $this->twig->render('ajouter-commande.html.twig', [
                'fournisseurs' => $fournisseurs,
                'produits' => $produits,
                'message' => $_SESSION['message'] ?? null
            ]);
            
            unset($_SESSION['message']);
        } catch (\Exception $e) {
            error_log("Erreur dans showAddForm : " . $e->getMessage());
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Erreur lors du chargement du formulaire'];
            header('Location: index.php?uri=commandes');
            exit;
        }
    }

    public function create() {
        try {
            $data = [
                'reference' => uniqid('CMD-'),
                'date_commande' => date('Y-m-d H:i:s'),
                'fournisseur_id' => $_POST['fournisseur_id'],
                'produits' => []
            ];

            foreach ($_POST['produits'] as $index => $produitId) {
                if (!empty($_POST['quantites'][$index])) {
                    $data['produits'][] = [
                        'id' => $produitId,
                        'quantite' => $_POST['quantites'][$index]
                    ];
                }
            }

            if ($this->commandeModel->create($data)) {
                $_SESSION['message'] = ['type' => 'success', 'text' => 'Commande créée avec succès'];
            } else {
                $_SESSION['message'] = ['type' => 'error', 'text' => 'Erreur lors de la création de la commande'];
            }

            header('Location: index.php?uri=commandes');
            exit;
        } catch (\Exception $e) {
            error_log("Erreur dans create : " . $e->getMessage());
            $this->renderError("Impossible de créer la commande");
        }
    }

    public function store($post) {
        $commande_id = $this->model->insert($post);
        if (isset($post['produits'])) {
            $this->model->insertProduits($commande_id, $post['produits']);
        }
        header("Location: /commandes");
        exit;
    }

    public function delete($id) {
        try {
            // Debug - Log le début de la suppression
            error_log("Début de la suppression de la commande ID: " . $id);

            // Validation de l'ID
            if (!$id || !is_numeric($id)) {
                error_log("ID invalide: " . $id);
                throw new \Exception("ID de commande invalide");
            }

            // Vérification de l'existence de la commande
            $commande = $this->commandeModel->getById($id);
            if (!$commande) {
                error_log("Commande non trouvée avec l'ID: " . $id);
                throw new \Exception("Commande introuvable");
            }

            // Tentative de suppression
            if ($this->commandeModel->delete($id)) {
                $_SESSION['message'] = [
                    'type' => 'success',
                    'text' => 'Commande supprimée avec succès'
                ];
                error_log("Suppression réussie de la commande ID: " . $id);
            } else {
                throw new \Exception("Échec de la suppression");
            }

        } catch (\Exception $e) {
            error_log("Erreur de suppression: " . $e->getMessage());
            error_log("Trace: " . $e->getTraceAsString());
            $_SESSION['message'] = [
                'type' => 'error',
                'text' => 'Impossible de supprimer la commande : ' . $e->getMessage()
            ];
        }

        // Redirection
        header('Location: index.php?uri=commandes');
        exit;
    }

    // Méthode d'édition avec support du statut
    public function edit($id) {
        try {
            $commande = $this->commandeModel->getById($id);
            if (!$commande) {
                $_SESSION['message'] = ['type' => 'error', 'text' => 'Commande non trouvée'];
                header('Location: index.php?uri=commandes');
                exit;
            }

            $fournisseurs = $this->fournisseurModel->getAll();
            
            echo $this->twig->render('commandes/edit.html.twig', [
                'commande' => $commande,
                'fournisseurs' => $fournisseurs,
                'statuts' => ['en_attente' => 'En attente', 'livree' => 'Livrée'],
                'current_page' => 'commandes'
            ]);
            
        } catch (\Exception $e) {
            error_log("Erreur dans edit commande : " . $e->getMessage());
            $this->renderError("Impossible de modifier la commande");
        }
    }

    // Méthode de mise à jour avec support du statut
    public function update($id) {
        try {
            // Validation
            if (empty($_POST['reference']) || empty($_POST['fournisseur_id'])) {
                $_SESSION['message'] = ['type' => 'error', 'text' => 'Tous les champs obligatoires doivent être remplis'];
                header("Location: index.php?uri=modifier-commande&id=$id");
                exit;
            }
            
            // Préparation des données
            $data = [
                'reference' => $_POST['reference'],
                'fournisseur_id' => $_POST['fournisseur_id'],
                'date_commande' => $_POST['date_commande'],
                'statut' => $_POST['statut'] ?? 'en_attente'
            ];
            
            // Mise à jour
            $success = $this->commandeModel->update($id, $data);
            
            if ($success) {
                $_SESSION['message'] = ['type' => 'success', 'text' => 'Commande modifiée avec succès'];
                header('Location: index.php?uri=commandes');
            } else {
                $_SESSION['message'] = ['type' => 'error', 'text' => 'Erreur lors de la modification'];
                header("Location: index.php?uri=modifier-commande&id=$id");
            }
            exit;
        } catch (\Exception $e) {
            error_log("Erreur mise à jour commande: " . $e->getMessage());
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Une erreur est survenue'];
            header('Location: index.php?uri=commandes');
            exit;
        }
    }

    // Méthode pour changer uniquement le statut
    public function updateStatus($id) {
        try {
            if (!isset($_POST['statut'])) {
                throw new \Exception("Statut non fourni");
            }

            $statut = $_POST['statut'];
            if (!in_array($statut, ['en_attente', 'livree'])) {
                throw new \Exception("Statut invalide");
            }

            if ($this->commandeModel->updateStatus($id, $statut)) {
                $_SESSION['message'] = ['type' => 'success', 'text' => 'Statut de la commande mis à jour'];
            } else {
                $_SESSION['message'] = ['type' => 'error', 'text' => 'Erreur lors de la mise à jour du statut'];
            }
        } catch (\Exception $e) {
            error_log("Erreur mise à jour statut : " . $e->getMessage());
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Impossible de mettre à jour le statut'];
        }

        header('Location: index.php?uri=commandes');
        exit;
    }
}
