<?php
namespace App\Controllers;

use App\Models\Alert;
use App\Models\Produit;

class AlertController {
    private $twig;
    private $alertModel;
    private $produitModel;

    public function __construct($twig) {
        $this->twig = $twig;
        $this->alertModel = new Alert();
        $this->produitModel = new Produit();
        
        // Ajouter le nombre d'alertes non lues à toutes les pages
        $this->twig->addGlobal('unread_alerts_count', $this->alertModel->countUnreadAlerts());
    }
    
    // Afficher toutes les alertes
    public function index() {
        $alerts = $this->alertModel->getAll();
        
        echo $this->twig->render('alertes/index.html.twig', [
            'alerts' => $alerts,
            'current_page' => 'alertes',
            'title' => 'Alertes'
        ]);
    }
    
    // Marquer une alerte comme lue (via AJAX)
    public function markAsRead() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
            return;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $alert_id = $data['alert_id'] ?? null;
        
        if (!$alert_id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID d\'alerte manquant']);
            return;
        }
        
        $success = $this->alertModel->markAsRead($alert_id);
        echo json_encode(['success' => $success]);
    }
    
    // Vérifier les niveaux de stock et créer des alertes si nécessaire
    public function checkStockLevels() {
        $result = $this->produitModel->checkStockLevels();
        
        if ($result) {
            $_SESSION['message'] = ['type' => 'success', 'text' => 'Niveaux de stock vérifiés avec succès'];
        } else {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Erreur lors de la vérification des stocks'];
        }
        
        header('Location: index.php?uri=dashboard');
        exit;
    }
}