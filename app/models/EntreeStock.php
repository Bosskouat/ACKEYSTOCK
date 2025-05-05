<?php
// filepath: c:\www\ACKEYSTOCK\app\models\EntreeStock.php
namespace App\Models;

use PDO;
use App\Config\Database;

class EntreeStock {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Récupérer toutes les entrées de stock avec infos produits et fournisseurs
     */
    public function getAll($limit = null) {
        try {
            $sql = "SELECT e.*, p.nom as produit_nom, f.nom as fournisseur_nom 
                    FROM entrees_stock e 
                    LEFT JOIN produits p ON e.produit_id = p.id 
                    LEFT JOIN fournisseurs f ON e.fournisseur_id = f.id 
                    ORDER BY e.date_mouvement DESC";
            
            if ($limit) {
                $sql .= " LIMIT :limit";
            }
            
            $stmt = $this->db->prepare($sql);
            
            if ($limit) {
                $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (\PDOException $e) {
            error_log("Erreur dans EntreeStock::getAll: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupérer une entrée de stock par son ID
     */
    public function getById($id) {
        try {
            $sql = "SELECT e.*, p.nom as produit_nom, f.nom as fournisseur_nom 
                    FROM entrees_stock e 
                    LEFT JOIN produits p ON e.produit_id = p.id 
                    LEFT JOIN fournisseurs f ON e.fournisseur_id = f.id 
                    WHERE e.id = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (\PDOException $e) {
            error_log("Erreur dans EntreeStock::getById: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Ajouter une nouvelle entrée de stock
     */
    public function add($data) {
        try {
            $this->db->beginTransaction();
            
            // 1. Insérer l'entrée dans la table entrees_stock
            $sql = "INSERT INTO entrees_stock (produit_id, fournisseur_id, quantite, prix_unitaire, date_mouvement, reference_document, commentaire) 
                    VALUES (:produit_id, :fournisseur_id, :quantite, :prix_unitaire, :date_mouvement, :reference_document, :commentaire)";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                ':produit_id' => $data['produit_id'],
                ':fournisseur_id' => $data['fournisseur_id'],
                ':quantite' => $data['quantite'],
                ':prix_unitaire' => $data['prix_unitaire'],
                ':date_mouvement' => $data['date_mouvement'] ?? date('Y-m-d H:i:s'),
                ':reference_document' => $data['reference_document'] ?? null,
                ':commentaire' => $data['commentaire'] ?? null
            ]);
            
            if (!$result) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Erreur lors de l\'ajout de l\'entrée'];
            }
            
            $entree_id = $this->db->lastInsertId();
            
            // 2. Mettre à jour le stock du produit
            $sql = "UPDATE produits SET quantite = quantite + :quantite WHERE id = :produit_id";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                ':quantite' => $data['quantite'],
                ':produit_id' => $data['produit_id']
            ]);
            
            if (!$result) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Erreur lors de la mise à jour du stock'];
            }
            
            // 3. Ajouter une entrée dans le journal d'activité
            $sql = "INSERT INTO journal_activite (type_activite, description, entite_id, entite_type, utilisateur_id) 
                    VALUES ('entrée stock', 'Entrée de :quantite :produit_nom', :entite_id, 'entree_stock', :utilisateur_id)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':quantite' => $data['quantite'],
                ':produit_nom' => $this->getProduitNom($data['produit_id']),
                ':entite_id' => $entree_id,
                ':utilisateur_id' => $_SESSION['user_id'] ?? null
            ]);
            
            $this->db->commit();
            return ['success' => true, 'id' => $entree_id];
            
        } catch (\PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur dans EntreeStock::add: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de l\'ajout: ' . $e->getMessage()];
        }
    }
    
    /**
     * Mettre à jour une entrée de stock
     */
    public function update($id, $data) {
        try {
            // D'abord récupérer l'entrée actuelle pour calculer la différence
            $current = $this->getById($id);
            if (!$current) {
                return ['success' => false, 'message' => 'Entrée non trouvée'];
            }
            
            $this->db->beginTransaction();
            
            // Calculer la différence de quantité
            $quantite_diff = $data['quantite'] - $current['quantite'];
            
            // 1. Mettre à jour l'entrée
            $sql = "UPDATE entrees_stock SET 
                    produit_id = :produit_id,
                    fournisseur_id = :fournisseur_id,
                    quantite = :quantite,
                    prix_unitaire = :prix_unitaire,
                    date_mouvement = :date_mouvement,
                    reference_document = :reference_document,
                    commentaire = :commentaire
                    WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                ':produit_id' => $data['produit_id'],
                ':fournisseur_id' => $data['fournisseur_id'],
                ':quantite' => $data['quantite'],
                ':prix_unitaire' => $data['prix_unitaire'],
                ':date_mouvement' => $data['date_mouvement'],
                ':reference_document' => $data['reference_document'] ?? null,
                ':commentaire' => $data['commentaire'] ?? null,
                ':id' => $id
            ]);
            
            if (!$result) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Erreur lors de la mise à jour de l\'entrée'];
            }
            
            // 2. Ajuster le stock du produit
            if ($quantite_diff != 0) {
                $sql = "UPDATE produits SET quantite = quantite + :quantite_diff WHERE id = :produit_id";
                $stmt = $this->db->prepare($sql);
                $result = $stmt->execute([
                    ':quantite_diff' => $quantite_diff,
                    ':produit_id' => $data['produit_id']
                ]);
                
                if (!$result) {
                    $this->db->rollBack();
                    return ['success' => false, 'message' => 'Erreur lors de la mise à jour du stock'];
                }
            }
            
            // 3. Ajouter une entrée dans le journal
            $sql = "INSERT INTO journal_activite (type_activite, description, entite_id, entite_type, utilisateur_id) 
                    VALUES ('modification entrée', 'Modification entrée pour :produit_nom', :entite_id, 'entree_stock', :utilisateur_id)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':produit_nom' => $this->getProduitNom($data['produit_id']),
                ':entite_id' => $id,
                ':utilisateur_id' => $_SESSION['user_id'] ?? null
            ]);
            
            $this->db->commit();
            return ['success' => true];
            
        } catch (\PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur dans EntreeStock::update: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()];
        }
    }
    
    /**
     * Supprimer une entrée de stock
     */
    public function delete($id) {
        try {
            // Récupérer l'entrée avant de la supprimer
            $entree = $this->getById($id);
            if (!$entree) {
                return ['success' => false, 'message' => 'Entrée non trouvée'];
            }
            
            $this->db->beginTransaction();
            
            // 1. Ajuster le stock du produit
            $sql = "UPDATE produits SET quantite = quantite - :quantite WHERE id = :produit_id";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                ':quantite' => $entree['quantite'],
                ':produit_id' => $entree['produit_id']
            ]);
            
            if (!$result) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Erreur lors de la mise à jour du stock'];
            }
            
            // 2. Supprimer l'entrée
            $sql = "DELETE FROM entrees_stock WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([':id' => $id]);
            
            if (!$result) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Erreur lors de la suppression de l\'entrée'];
            }
            
            // 3. Ajouter une entrée dans le journal
            $sql = "INSERT INTO journal_activite (type_activite, description, entite_id, entite_type, utilisateur_id) 
                    VALUES ('suppression entrée', 'Suppression entrée de :quantite :produit_nom', :entite_id, 'entree_stock', :utilisateur_id)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':quantite' => $entree['quantite'],
                ':produit_nom' => $entree['produit_nom'],
                ':entite_id' => $id,
                ':utilisateur_id' => $_SESSION['user_id'] ?? null
            ]);
            
            $this->db->commit();
            return ['success' => true];
            
        } catch (\PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur dans EntreeStock::delete: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la suppression: ' . $e->getMessage()];
        }
    }
    
    /**
     * Récupérer le nom d'un produit par son ID
     */
    private function getProduitNom($produit_id) {
        $sql = "SELECT nom FROM produits WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $produit_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['nom'] : 'Produit inconnu';
    }
    
    /**
     * Recherche des entrées de stock avec filtres
     */
    public function search($params) {
        try {
            $conditions = [];
            $parameters = [];
            
            $sql = "SELECT e.*, p.nom as produit_nom, f.nom as fournisseur_nom 
                    FROM entrees_stock e 
                    LEFT JOIN produits p ON e.produit_id = p.id 
                    LEFT JOIN fournisseurs f ON e.fournisseur_id = f.id 
                    WHERE 1=1";
            
            // Filtre par produit
            if (!empty($params['produit_id'])) {
                $conditions[] = "e.produit_id = :produit_id";
                $parameters[':produit_id'] = $params['produit_id'];
            }
            
            // Filtre par fournisseur
            if (!empty($params['fournisseur_id'])) {
                $conditions[] = "e.fournisseur_id = :fournisseur_id";
                $parameters[':fournisseur_id'] = $params['fournisseur_id'];
            }
            
            // Filtre par date de début
            if (!empty($params['date_debut'])) {
                $conditions[] = "e.date_mouvement >= :date_debut";
                $parameters[':date_debut'] = $params['date_debut'] . ' 00:00:00';
            }
            
            // Filtre par date de fin
            if (!empty($params['date_fin'])) {
                $conditions[] = "e.date_mouvement <= :date_fin";
                $parameters[':date_fin'] = $params['date_fin'] . ' 23:59:59';
            }
            
            // Filtre par référence
            if (!empty($params['reference'])) {
                $conditions[] = "e.reference_document LIKE :reference";
                $parameters[':reference'] = '%' . $params['reference'] . '%';
            }
            
            // Ajouter les conditions à la requête
            if (!empty($conditions)) {
                $sql .= " AND " . implode(" AND ", $conditions);
            }
            
            $sql .= " ORDER BY e.date_mouvement DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($parameters);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (\PDOException $e) {
            error_log("Erreur dans EntreeStock::search: " . $e->getMessage());
            return [];
        }
    }
}
