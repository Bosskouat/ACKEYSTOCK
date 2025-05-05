<?php
namespace App\Models;

use PDO;
use PDOException;
use App\Config\Database;

class Commande {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getAll() {
        try {
            $sql = "SELECT c.*, f.nom as fournisseur_nom 
                    FROM commandes c 
                    LEFT JOIN fournisseurs f ON c.fournisseur_id = f.id 
                    ORDER BY c.date_commande DESC";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur dans getAll : " . $e->getMessage());
            return [];
        }
    }

    public function getById($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT c.*, f.nom as fournisseur_nom 
                FROM commandes c 
                LEFT JOIN fournisseurs f ON c.fournisseur_id = f.id 
                WHERE c.id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur dans getById: " . $e->getMessage());
            return null;
        }
    }

    public function getProduits($commande_id) {
        $stmt = $this->db->prepare("
            SELECT cp.*, p.nom AS nom_produit
            FROM commandes_produits cp
            JOIN produits p ON cp.produit_id = p.id
            WHERE cp.commande_id = ?
        ");
        $stmt->execute([$commande_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function insert($data) {
        $stmt = $this->db->prepare("INSERT INTO commandes (reference, date_commande, statut, fournisseur_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $data['reference'],
            $data['date_commande'],
            $data['statut'],
            $data['fournisseur_id']
        ]);
        return $this->db->lastInsertId();
    }

    public function insertProduits($commande_id, $produits) {
        $stmt = $this->db->prepare("INSERT INTO commandes_produits (commande_id, produit_id, quantite) VALUES (?, ?, ?)");
        foreach ($produits as $p) {
            $stmt->execute([$commande_id, $p['produit_id'], $p['quantite']]);
        }
    }

    public function delete($id) {
        try {
            error_log("Tentative de suppression de la commande ID: " . $id);
            $this->db->beginTransaction();

            // Vérification de l'existence de la commande
            $stmt = $this->db->prepare("SELECT id FROM commandes WHERE id = ?");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                error_log("Commande ID " . $id . " non trouvée");
                throw new \Exception("Commande non trouvée");
            }

            // Suppression des produits liés - Notez le changement de nom de table ici
            $stmt = $this->db->prepare("DELETE FROM commandes_produits WHERE commande_id = ?");
            $stmt->execute([$id]);
            $produitsDeleted = $stmt->rowCount();
            error_log("Produits de la commande supprimés - Rows affected: " . $produitsDeleted);

            // Suppression de la commande
            $stmt = $this->db->prepare("DELETE FROM commandes WHERE id = ?");
            $stmt->execute([$id]);
            $commandeDeleted = $stmt->rowCount();
            error_log("Commande supprimée - Rows affected: " . $commandeDeleted);

            if ($commandeDeleted === 0) {
                throw new \Exception("La commande n'a pas pu être supprimée");
            }

            $this->db->commit();
            error_log("Transaction validée - Suppression réussie");
            return true;

        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("Erreur lors de la suppression: " . $e->getMessage());
            error_log("Trace: " . $e->getTraceAsString());
            throw $e; // Propager l'erreur pour une meilleure gestion
        }
    }

    // Ajouter le statut lors de la création
    public function create($data) {
        try {
            $sql = "INSERT INTO commandes (fournisseur_id, date_commande, reference, statut) 
                    VALUES (:fournisseur_id, :date_commande, :reference, :statut)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':fournisseur_id' => $data['fournisseur_id'],
                ':date_commande' => $data['date_commande'] ?? date('Y-m-d'),
                ':reference' => $data['reference'],
                ':statut' => $data['statut'] ?? 'en_attente'
            ]);
            
            return $this->db->lastInsertId();
        } catch (\PDOException $e) {
            error_log("Erreur création commande : " . $e->getMessage());
            return false;
        }
    }

    // Mise à jour du statut
    public function updateStatus($id, $statut) {
        try {
            $sql = "UPDATE commandes SET statut = :statut WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':id' => $id,
                ':statut' => $statut
            ]);
        } catch (\PDOException $e) {
            error_log("Erreur mise à jour statut : " . $e->getMessage());
            return false;
        }
    }

    // Mise à jour complète d'une commande
    public function update($id, $data) {
        try {
            $sql = "UPDATE commandes SET 
                    fournisseur_id = :fournisseur_id,
                    date_commande = :date_commande,
                    reference = :reference,
                    statut = :statut
                    WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':id' => $id,
                ':fournisseur_id' => $data['fournisseur_id'],
                ':date_commande' => $data['date_commande'],
                ':reference' => $data['reference'],
                ':statut' => $data['statut']
            ]);
        } catch (\PDOException $e) {
            error_log("Erreur mise à jour commande : " . $e->getMessage());
            return false;
        }
    }

    // Autres méthodes existantes...
}
