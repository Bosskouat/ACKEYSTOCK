<?php
namespace App\Models;

use PDO;
use PDOException;
use App\Config\Database;

class Produit {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getAll() {
        try {
            $sql = "SELECT p.*, c.nom as nom_categorie 
                    FROM produits p 
                    LEFT JOIN categories c ON p.categorie_id = c.id 
                    ORDER BY p.nom ASC";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur dans getAll : " . $e->getMessage());
            return [];
        }
    }

    public function getAllWithStock() {
        try {
            $sql = "SELECT p.*, c.nom AS categorie_nom 
                    FROM produits p 
                    LEFT JOIN categories c ON p.categorie_id = c.id
                    WHERE p.quantite > 0 
                    ORDER BY p.nom ASC";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Erreur dans getAllWithStock: " . $e->getMessage());
            return false;
        }
    }

    public function getAllWithDetails() {
        try {
            $sql = "SELECT p.*, c.nom AS nom_categorie
                    FROM produits p
                    LEFT JOIN categories c ON p.categorie_id = c.id
                    ORDER BY p.nom ASC";
            
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Erreur dans getAllWithDetails: " . $e->getMessage());
            return [];
        }
    }

    public function getById($id) {
        try {
            $stmt = $this->db->prepare("SELECT p.*, c.nom AS nom_categorie 
                                       FROM produits p 
                                       LEFT JOIN categories c ON p.categorie_id = c.id 
                                       WHERE p.id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur dans getById : " . $e->getMessage());
            return null;
        }
    }

    public function getMouvementsById($id) {
        try {
            $sql = "SELECT 'entrée' AS type, e.quantite, e.date_mouvement, e.prix_achat, f.nom AS source, e.reference_document
                    FROM entrees_stock e
                    JOIN fournisseurs f ON e.fournisseur_id = f.id
                    WHERE e.produit_id = :id
                    UNION ALL
                    SELECT 'sortie' AS type, s.quantite, s.date_mouvement, NULL AS prix_achat, s.destination AS source, s.motif AS reference_document
                    FROM sorties_stock s
                    WHERE s.produit_id = :id
                    ORDER BY date_mouvement DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Erreur dans getMouvementsById: " . $e->getMessage());
            return [];
        }
    }

    public function insert($data) {
        try {
            $stmt = $this->db->prepare("INSERT INTO produits (nom, quantite, seuil, description, categorie_id) 
                                      VALUES (?, ?, ?, ?, ?)");
            $success = $stmt->execute([
                $data['nom'],
                $data['quantite'],
                $data['seuil'],
                $data['description'],
                $data['categorie_id']
            ]);
            
            if ($success) {
                return $this->db->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            error_log("Erreur dans insert : " . $e->getMessage());
            return false;
        }
    }

    public function update($id, $data) {
        try {
            $stmt = $this->db->prepare("UPDATE produits 
                                      SET nom = ?, quantite = ?, seuil = ?, 
                                          description = ?, categorie_id = ? 
                                      WHERE id = ?");
            return $stmt->execute([
                $data['nom'],
                $data['quantite'],
                $data['seuil'],
                $data['description'],
                $data['categorie_id'],
                $id
            ]);
        } catch (PDOException $e) {
            error_log("Erreur dans update : " . $e->getMessage());
            return false;
        }
    }

    public function delete($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM produits WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Erreur dans delete : " . $e->getMessage());
            return false;
        }
    }

    public function getProduitsByCategorieId($categorieId) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM produits WHERE categorie_id = ?");
            $stmt->execute([$categorieId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur dans getProduitsByCategorieId : " . $e->getMessage());
            return [];
        }
    }

    public function getTotalProduits() {
        try {
            $sql = "SELECT COUNT(*) as total FROM produits";
            $stmt = $this->db->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'];
        } catch (\PDOException $e) {
            error_log("Erreur dans getTotalProduits : " . $e->getMessage());
            return 0;
        }
    }

    public function getProduitsSousSeuilAlerte() {
        try {
            $sql = "SELECT p.*, c.nom as categorie_nom 
                    FROM produits p 
                    LEFT JOIN categories c ON p.categorie_id = c.id 
                    WHERE p.quantite <= p.seuil 
                    ORDER BY p.quantite ASC";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Erreur dans getProduitsSousSeuilAlerte : " . $e->getMessage());
            return [];
        }
    }

    public function getMoyenneSorties($produit_id, $mois = 3) {
        try {
            $sql = "SELECT AVG(quantite) as moyenne 
                    FROM sorties_stock 
                    WHERE produit_id = :produit_id 
                    AND date_sortie >= DATE_SUB(NOW(), INTERVAL :mois MONTH)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':produit_id' => $produit_id,
                ':mois' => $mois
            ]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['moyenne'] ?? 0;
        } catch (\PDOException $e) {
            error_log("Erreur dans getMoyenneSorties : " . $e->getMessage());
            return 0;
        }
    }

    public function checkStockLevels() {
        try {
            $sql = "SELECT id, nom, quantite, seuil 
                    FROM produits 
                    WHERE quantite <= seuil";
            $stmt = $this->db->query($sql);
            $produits = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $alertModel = new Alert();
            foreach ($produits as $produit) {
                if ($produit['quantite'] <= 0) {
                    $alertModel->createStockAlert(
                        $produit['id'],
                        'rupture',
                        "RUPTURE DE STOCK : {$produit['nom']} est en rupture de stock !"
                    );
                } elseif ($produit['quantite'] <= $produit['seuil']) {
                    $alertModel->createStockAlert(
                        $produit['id'],
                        'stock_bas',
                        "STOCK BAS : {$produit['nom']} est sous le seuil d'alerte ({$produit['quantite']} restants)"
                    );
                }
            }
            return true;
        } catch (\PDOException $e) {
            error_log("Erreur vérification stocks : " . $e->getMessage());
            return false;
        }
    }

    public function search($params) {
        try {
            $conditions = [];
            $parameters = [];
            
            $sql = "SELECT p.*, c.nom as nom_categorie, f.nom as nom_fournisseur
                    FROM produits p
                    LEFT JOIN categories c ON p.categorie_id = c.id
                    LEFT JOIN (
                        SELECT DISTINCT produit_id, fournisseur_id 
                        FROM entrees_stock
                    ) es ON p.id = es.produit_id
                    LEFT JOIN fournisseurs f ON es.fournisseur_id = f.id
                    WHERE 1=1";
            
            // Filtre par nom
            if (!empty($params['nom'])) {
                $conditions[] = "p.nom LIKE :nom";
                $parameters[':nom'] = '%' . $params['nom'] . '%';
            }
            
            // Filtre par catégorie
            if (!empty($params['categorie_id'])) {
                $conditions[] = "p.categorie_id = :categorie_id";
                $parameters[':categorie_id'] = $params['categorie_id'];
            }
            
            // Filtre par fournisseur
            if (!empty($params['fournisseur_id'])) {
                $conditions[] = "es.fournisseur_id = :fournisseur_id";
                $parameters[':fournisseur_id'] = $params['fournisseur_id'];
            }
            
            // Filtre par seuil de stock
            if (!empty($params['stock_filter'])) {
                switch($params['stock_filter']) {
                    case 'low':
                        $conditions[] = "p.quantite <= p.seuil AND p.quantite > 0";
                        break;
                    case 'out':
                        $conditions[] = "p.quantite = 0";
                        break;
                    case 'available':
                        $conditions[] = "p.quantite > 0";
                        break;
                }
            }
            
            // Ajouter les conditions à la requête
            if (!empty($conditions)) {
                $sql .= " AND " . implode(" AND ", $conditions);
            }
            
            // Tri
            $sql .= " GROUP BY p.id ORDER BY p.nom ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($parameters);
            
            // Débuguer la requête
            error_log("Requête SQL: $sql");
            error_log("Paramètres: " . print_r($parameters, true));
            error_log("Nombre de résultats: " . $stmt->rowCount());
            
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Erreur dans search : " . $e->getMessage());
            return [];
        }
    }
}
