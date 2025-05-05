<?php
// filepath: c:\www\ACKEYSTOCK\app\Controllers\RapportController.php
namespace App\Controllers;

use App\Models\Rapport;
use Twig\Environment;
use App\Middleware\AuthMiddleware;

class RapportController {
    private $twig;
    private $rapportModel;

    public function __construct(Environment $twig) {
        $this->twig = $twig;
        $this->rapportModel = new Rapport();
    }

    public function index() {
        // Vérifier les permissions
        // AuthMiddleware::checkPermission('rapports', 'view');
        
        try {
            echo $this->twig->render('rapports/index.html.twig', [
                'current_page' => 'rapports'
            ]);
        } catch (\Exception $e) {
            error_log("Erreur dans rapports index: " . $e->getMessage());
            echo $this->twig->render('error.html.twig', [
                'message' => 'Une erreur est survenue lors du chargement des rapports.'
            ]);
        }
    }
    
    public function mouvementsStock() {
        // AuthMiddleware::checkPermission('rapports', 'view');
        
        try {
            $debut = $_GET['debut'] ?? date('Y-m-d', strtotime('-30 days'));
            $fin = $_GET['fin'] ?? date('Y-m-d');
            
            $mouvements = $this->rapportModel->getMouvementsStock($debut, $fin);
            
            echo $this->twig->render('rapports/mouvements-stock.html.twig', [
                'mouvements' => $mouvements,
                'debut' => $debut,
                'fin' => $fin,
                'current_page' => 'rapports'
            ]);
        } catch (\Exception $e) {
            error_log("Erreur dans mouvements stock: " . $e->getMessage());
            echo $this->twig->render('error.html.twig', [
                'message' => 'Une erreur est survenue lors du chargement du rapport de mouvements de stock.'
            ]);
        }
    }
    
    public function produitsPlusVendus() {
        // AuthMiddleware::checkPermission('rapports', 'view');
        
        try {
            $debut = $_GET['debut'] ?? date('Y-m-d', strtotime('-30 days'));
            $fin = $_GET['fin'] ?? date('Y-m-d');
            $limit = $_GET['limit'] ?? 10;
            
            $produits = $this->rapportModel->getProduitsPlusVendus($debut, $fin, $limit);
            
            echo $this->twig->render('rapports/produits-plus-vendus.html.twig', [
                'produits' => $produits,
                'debut' => $debut,
                'fin' => $fin,
                'limit' => $limit,
                'current_page' => 'rapports'
            ]);
        } catch (\Exception $e) {
            error_log("Erreur dans produits plus vendus: " . $e->getMessage());
            echo $this->twig->render('error.html.twig', [
                'message' => 'Une erreur est survenue lors du chargement du rapport des produits les plus vendus.'
            ]);
        }
    }
    
    public function previsionsAchats() {
        // AuthMiddleware::checkPermission('rapports', 'view');
        
        try {
            $periode = $_GET['periode'] ?? 30;
            
            $previsions = $this->rapportModel->getPrevisionsAchats($periode);
            
            echo $this->twig->render('rapports/previsions-achats.html.twig', [
                'previsions' => $previsions,
                'periode' => $periode,
                'current_page' => 'rapports'
            ]);
        } catch (\Exception $e) {
            error_log("Erreur dans prévisions achats: " . $e->getMessage());
            echo $this->twig->render('error.html.twig', [
                'message' => 'Une erreur est survenue lors du chargement des prévisions d\'achats.'
            ]);
        }
    }

    // Export rapports en PDF ou CSV
    public function exportRapport() {
        // AuthMiddleware::checkPermission('rapports', 'export');
        
        try {
            $type = $_GET['type'] ?? '';
            $format = $_GET['format'] ?? 'csv';
            
            switch ($type) {
                case 'mouvements':
                    $debut = $_GET['debut'] ?? date('Y-m-d', strtotime('-30 days'));
                    $fin = $_GET['fin'] ?? date('Y-m-d');
                    $data = $this->rapportModel->getMouvementsStock($debut, $fin);
                    $filename = 'mouvements_stock_' . date('Y-m-d');
                    break;
                    
                case 'vendus':
                    $debut = $_GET['debut'] ?? date('Y-m-d', strtotime('-30 days'));
                    $fin = $_GET['fin'] ?? date('Y-m-d');
                    $data = $this->rapportModel->getProduitsPlusVendus($debut, $fin, 100);
                    $filename = 'produits_plus_vendus_' . date('Y-m-d');
                    break;
                    
                case 'previsions':
                    $periode = $_GET['periode'] ?? 30;
                    $data = $this->rapportModel->getPrevisionsAchats($periode);
                    $filename = 'previsions_achats_' . date('Y-m-d');
                    break;
                    
                default:
                    throw new \Exception("Type de rapport inconnu: $type");
            }
            
            // Générer le fichier d'export
            if ($format === 'csv') {
                $this->exportCSV($data, $filename);
            } else {
                $_SESSION['message'] = ['type' => 'error', 'text' => 'Format d\'export non supporté'];
                header("Location: index.php?uri=rapports-$type");
            }
            
        } catch (\Exception $e) {
            error_log("Erreur dans export: " . $e->getMessage());
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Erreur lors de l\'export du rapport'];
            header("Location: index.php?uri=rapports");
        }
    }
    
    private function exportCSV($data, $filename) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename . '.csv');
        
        $output = fopen('php://output', 'w');
        
        // Écrire l'en-tête UTF-8 BOM pour Excel
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        if (!empty($data)) {
            // En-têtes de colonnes
            fputcsv($output, array_keys($data[0]));
            
            // Données
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
        }
        
        fclose($output);
        exit;
    }
}