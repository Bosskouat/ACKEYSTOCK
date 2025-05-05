<?php
// filepath: c:\www\ACKEYSTOCK\app\Models\Rapport.php
namespace App\Models;

use App\Config\Database;
use PDO;

class Rapport {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // Rapport de mouvements de stock par période
    public function getMouvementsStock($debut, $fin) {
        try {
            $sql = "SELECT p.id, p.nom, p.categorie_id, c.nom AS categorie_nom,
                    COALESCE(SUM(CASE WHEN m.type = 'entree' THEN m.quantite ELSE 0 END), 0) AS total_entrees,
                    COALESCE(SUM(CASE WHEN m.type = 'sortie' THEN m.quantite ELSE 0 END), 0) AS total_sorties,
                    p.quantite AS stock_actuel
                    FROM produits p
                    LEFT JOIN categories c ON p.categorie_id = c.id
                    LEFT JOIN (
                        SELECT produit_id, quantite, 'entree' as type, date_mouvement
                        FROM entrees_stock
                        WHERE date_mouvement BETWEEN :debut1 AND :fin1
                        UNION ALL
                        SELECT produit_id, quantite, 'sortie' as type, date_mouvement
                        FROM sorties_stock
                        WHERE date_mouvement BETWEEN :debut1 AND :fin1
                    ) m ON p.id = m.produit_id
                    GROUP BY p.id
                    ORDER BY c.nom, p.nom";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':debut1', $debut);
            $stmt->bindParam(':fin1', $fin);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Erreur rapport mouvements stock: " . $e->getMessage());
            return [];
        }
    }
    
    // Rapport produits les plus vendus
    public function getProduitsPlusVendus($debut, $fin, $limit = 10) {
        try {
            $sql = "SELECT p.id, p.nom, c.nom AS categorie, 
                    SUM(s.quantite) AS total_sorties
                    FROM sorties_stock s
                    JOIN produits p ON s.produit_id = p.id
                    LEFT JOIN categories c ON p.categorie_id = c.id
                    WHERE s.date_mouvement BETWEEN :debut AND :fin
                    GROUP BY p.id
                    ORDER BY total_sorties DESC
                    LIMIT :limit";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':debut', $debut);
            $stmt->bindParam(':fin', $fin);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Erreur rapport produits plus vendus: " . $e->getMessage());
            return [];
        }
    }
    
    // Prévisions d'achats basées sur la consommation moyenne
    public function getPrevisionsAchats($periode = 30) {
        try {
            $sql = "SELECT 
                    p.id, 
                    p.nom, 
                    p.quantite AS stock_actuel, 
                    p.seuil,
                    c.nom AS categorie_nom,
                    COALESCE(AVG(s.quantite), 0) AS consommation_moyenne_jour,
                    CASE 
                        WHEN COALESCE(AVG(s.quantite), 0) > 0 
                        THEN FLOOR(p.quantite / COALESCE(AVG(s.quantite), 0.1)) 
                        ELSE NULL 
                    END AS jours_restants,
                    CASE 
                        WHEN COALESCE(AVG(s.quantite), 0) > 0 
                        THEN CEILING(COALESCE(AVG(s.quantite), 0) * :periode - p.quantite) 
                        ELSE 0
                    END AS quantite_a_commander
                    FROM produits p
                    LEFT JOIN categories c ON p.categorie_id = c.id
                    LEFT JOIN sorties_stock s ON p.id = s.produit_id AND 
                                               s.date_mouvement >= DATE_SUB(CURRENT_DATE, INTERVAL 90 DAY)
                    GROUP BY p.id
                    HAVING quantite_a_commander > 0
                    ORDER BY jours_restants";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':periode', $periode, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Erreur prévisions achats: " . $e->getMessage());
            return [];
        }
    }
    
    // Statistiques globales pour le tableau de bord
    public function getStatistiquesGlobales() {
        try {
            // Produits en stock
            $sqlProduits = "SELECT COUNT(*) AS total FROM produits";
            $totalProduits = $this->db->query($sqlProduits)->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Produits sous seuil
            $sqlSouseuil = "SELECT COUNT(*) AS total FROM produits WHERE quantite <= seuil AND quantite > 0";
            $totalSouseuil = $this->db->query($sqlSouseuil)->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Ruptures de stock
            $sqlRupture = "SELECT COUNT(*) AS total FROM produits WHERE quantite = 0";
            $totalRupture = $this->db->query($sqlRupture)->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Valeur totale du stock
            $sqlValeur = "SELECT SUM(quantite * prix_achat) AS total FROM produits";
            $valeurStock = $this->db->query($sqlValeur)->fetch(PDO::FETCH_ASSOC)['total'];
            
            return [
                'total_produits' => $totalProduits,
                'sous_seuil' => $totalSouseuil,
                'rupture' => $totalRupture,
                'valeur_stock' => $valeurStock
            ];
        } catch (\PDOException $e) {
            error_log("Erreur statistiques globales: " . $e->getMessage());
            return [];
        }
    }
}