<?php
namespace App\Controllers;

use App\Models\JournalAction;
use App\Models\Utilisateur;
use App\Middleware\AuthMiddleware;
use App\Config\Permissions;

class JournalActionsController {
    private $twig;
    private $journalModel;
    private $utilisateurModel;

    public function __construct($twig) {
        $this->twig = $twig;
        $this->journalModel = new JournalAction();
        $this->utilisateurModel = new Utilisateur();
    }

    public function index() {
        // Vérification des permissions
        AuthMiddleware::requirePermission(Permissions::MODULE_JOURNAL_ACTIONS, Permissions::ACTION_VIEW);
        
        try {
            // Récupérer les filtres
            $filters = [
                'user_id' => $_GET['user_id'] ?? '',
                'date_debut' => $_GET['date_debut'] ?? '',
                'date_fin' => $_GET['date_fin'] ?? '',
                'action' => $_GET['action'] ?? '',
                'entite' => $_GET['entite'] ?? ''
            ];
            
            // Récupérer les utilisateurs pour le filtre
            $users = $this->utilisateurModel->getAll();
            
            // Récupérer les actions du journal
            $actions = $this->journalModel->getAll();
            
            // Afficher la vue
            echo $this->twig->render('journal-actions.html.twig', [
                'actions' => $actions,
                'users' => $users,
                'filters' => $filters,
                'current_page' => 'journal-actions'
            ]);
        } catch (\Exception $e) {
            error_log("Erreur dans JournalActionsController::index : " . $e->getMessage());
            echo $this->twig->render('error.html.twig', [
                'message' => 'Une erreur est survenue lors du chargement du journal d\'actions.'
            ]);
        }
    }

    /**
     * Enregistre une action dans le journal
     *
     * @param string $action Type d'action (create, update, delete, etc.)
     * @param string $entite Nom de l'entité concernée (produit, utilisateur, etc.)
     * @param int|null $entiteId ID de l'entité concernée
     * @param string|null $details Détails supplémentaires sur l'action
     * @return bool Succès ou échec
     */
    public static function logAction($action, $entite, $entiteId = null, $details = null) {
        try {
            $journalModel = new JournalAction();
            
            $data = [
                'utilisateur_id' => $_SESSION['user']['id'] ?? null,
                'action' => $action,
                'entite' => $entite,
                'entite_id' => $entiteId,
                'details' => $details,
            ];
            
            return $journalModel->add($data);
        } catch (\Exception $e) {
            error_log("Erreur lors de l'enregistrement dans le journal: " . $e->getMessage());
            return false;
        }
    }
}
