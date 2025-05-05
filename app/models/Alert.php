<?php
namespace App\Models;

use App\Config\Database;
use PDO;

class Alert {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // Créer une alerte pour stock bas ou rupture
    public function createStockAlert($produit_id, $type, $message) {
        // Vérifier si une alerte similaire non lue existe déjà
        if ($this->similarAlertExists($produit_id, $type)) {
            return true; // Ne pas créer de doublon
        }
        
        try {
            $sql = "INSERT INTO alerts (produit_id, type, message) 
                    VALUES (:produit_id, :type, :message)";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':produit_id' => $produit_id,
                ':type' => $type,
                ':message' => $message
            ]);
        } catch (\PDOException $e) {
            error_log("Erreur création alerte : " . $e->getMessage());
            return false;
        }
    }
    
    // Vérifier si une alerte similaire non lue existe déjà
    private function similarAlertExists($produit_id, $type) {
        try {
            $sql = "SELECT COUNT(*) FROM alerts 
                    WHERE produit_id = :produit_id 
                    AND type = :type 
                    AND lu = FALSE";
                    
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':produit_id' => $produit_id,
                ':type' => $type
            ]);
            
            return $stmt->fetchColumn() > 0;
        } catch (\PDOException $e) {
            error_log("Erreur vérification alerte : " . $e->getMessage());
            return false;
        }
    }
    
    // Récupérer toutes les alertes non lues
    public function getUnreadAlerts() {
        try {
            $sql = "SELECT a.*, p.nom as produit_nom 
                    FROM alerts a
                    JOIN produits p ON a.produit_id = p.id
                    WHERE a.lu = FALSE
                    ORDER BY a.date_creation DESC";
            
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Erreur récupération alertes : " . $e->getMessage());
            return [];
        }
    }
    
    // Marquer une alerte comme lue
    public function markAsRead($alert_id) {
        try {
            $sql = "UPDATE alerts SET lu = TRUE WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':id' => $alert_id]);
        } catch (\PDOException $e) {
            error_log("Erreur marquage alerte : " . $e->getMessage());
            return false;
        }
    }
    
    // Récupérer toutes les alertes
    public function getAll($limit = 50) {
        try {
            $sql = "SELECT a.*, p.nom as produit_nom 
                    FROM alerts a
                    JOIN produits p ON a.produit_id = p.id
                    ORDER BY a.date_creation DESC
                    LIMIT :limit";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Erreur récupération toutes alertes : " . $e->getMessage());
            return [];
        }
    }
    
    // Compter les alertes non lues
    public function countUnreadAlerts() {
        try {
            $sql = "SELECT COUNT(*) FROM alerts WHERE lu = FALSE";
            $stmt = $this->db->query($sql);
            return $stmt->fetchColumn();
        } catch (\PDOException $e) {
            error_log("Erreur comptage alertes : " . $e->getMessage());
            return 0;
        }
    }
}