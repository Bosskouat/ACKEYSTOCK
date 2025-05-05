<?php
namespace App\Models;

use App\Config\Database;
use PDO;

class SortieStock {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function create($data) {
        try {
            // Vérifier que la quantité est disponible
            $sql = "SELECT quantite FROM produits WHERE id = :produit_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':produit_id' => $data['produit_id']]);
            $produit = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$produit || $produit['quantite'] < $data['quantite']) {
                return ['success' => false, 'message' => 'Quantité insuffisante en stock'];
            }
            
            // Début de la transaction
            $this->db->beginTransaction();

            // Insertion de la sortie de stock
            $sql = "INSERT INTO sorties_stock (produit_id, quantite, motif, date_mouvement, destination) 
                    VALUES (:produit_id, :quantite, :motif, :date_mouvement, :destination)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':produit_id' => $data['produit_id'],
                ':quantite' => $data['quantite'],
                ':motif' => $data['motif'] ?? 'Vente',
                ':date_mouvement' => $data['date_mouvement'] ?? date('Y-m-d'),
                ':destination' => $data['destination'] ?? null
            ]);

            // Mise à jour de la quantité du produit
            $sql = "UPDATE produits SET 
                    quantite = quantite - :quantite,
                    date_derniere_sortie = :date_mouvement
                    WHERE id = :produit_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':produit_id' => $data['produit_id'],
                ':quantite' => $data['quantite'],
                ':date_mouvement' => $data['date_mouvement'] ?? date('Y-m-d')
            ]);

            // Validation de la transaction
            $this->db->commit();
            
            // Vérifier si le produit est maintenant sous le seuil d'alerte
            $this->checkAndCreateAlert($data['produit_id']);
            
            return ['success' => true];

        } catch (\PDOException $e) {
            // Annulation en cas d'erreur
            $this->db->rollBack();
            error_log("Erreur lors de la création de sortie de stock: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de l\'enregistrement: ' . $e->getMessage()];
        }
    }

    private function checkAndCreateAlert($produit_id) {
        try {
            // Récupérer les informations du produit
            $sql = "SELECT id, nom, quantite, seuil FROM produits WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $produit_id]);
            $produit = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Vérifier si une alerte est nécessaire
            if ($produit['quantite'] <= $produit['seuil'] && $produit['quantite'] > 0) {
                // Stock bas
                $sql = "INSERT INTO alerts (produit_id, type, message) 
                        VALUES (:produit_id, 'stock_bas', :message)";
                $message = "Le produit {$produit['nom']} est en stock bas ({$produit['quantite']} unités).";
                
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    ':produit_id' => $produit_id,
                    ':message' => $message
                ]);
            } elseif ($produit['quantite'] == 0) {
                // Rupture de stock
                $sql = "INSERT INTO alerts (produit_id, type, message) 
                        VALUES (:produit_id, 'rupture', :message)";
                $message = "Le produit {$produit['nom']} est en rupture de stock!";
                
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    ':produit_id' => $produit_id,
                    ':message' => $message
                ]);
            }
            
        } catch (\PDOException $e) {
            error_log("Erreur lors de la création d'alerte: " . $e->getMessage());
        }
    }

    public function getAll($limit = 100, $offset = 0) {
        try {
            $sql = "SELECT s.*, p.nom as produit_nom
                    FROM sorties_stock s
                    JOIN produits p ON s.produit_id = p.id
                    ORDER BY s.date_mouvement DESC
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Erreur lors de la récupération des sorties: " . $e->getMessage());
            return [];
        }
    }

    public function getById($id) {
        try {
            $sql = "SELECT s.*, p.nom as produit_nom
                    FROM sorties_stock s
                    JOIN produits p ON s.produit_id = p.id
                    WHERE s.id = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Erreur lors de la récupération d'une sortie: " . $e->getMessage());
            return null;
        }
    }

    public function delete($id) {
        try {
            // Récupérer la sortie avant de la supprimer
            $sortie = $this->getById($id);
            if (!$sortie) {
                return false;
            }

            // Début de la transaction
            $this->db->beginTransaction();

            // Mise à jour de la quantité du produit (remise en stock)
            $sql = "UPDATE produits SET 
                    quantite = quantite + :quantite
                    WHERE id = :produit_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':produit_id' => $sortie['produit_id'],
                ':quantite' => $sortie['quantite']
            ]);

            // Suppression de la sortie
            $sql = "DELETE FROM sorties_stock WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);

            // Validation de la transaction
            $this->db->commit();
            return true;

        } catch (\PDOException $e) {
            // Annulation en cas d'erreur
            $this->db->rollBack();
            error_log("Erreur lors de la suppression de sortie: " . $e->getMessage());
            return false;
        }
    }

    // Méthode pour les statistiques des sorties par période
    public function getStatsByPeriod($debut, $fin) {
        try {
            $sql = "SELECT 
                    DATE_FORMAT(date_mouvement, '%Y-%m-%d') as jour,
                    COUNT(*) as nombre_sorties,
                    SUM(quantite) as total_quantite
                    FROM sorties_stock
                    WHERE date_mouvement BETWEEN :debut AND :fin
                    GROUP BY jour
                    ORDER BY jour ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':debut' => $debut,
                ':fin' => $fin
            ]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Erreur lors de la récupération des stats: " . $e->getMessage());
            return [];
        }
    }
}
