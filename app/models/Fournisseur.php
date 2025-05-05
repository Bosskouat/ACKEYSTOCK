<?php
namespace App\Models;

use PDO;
use PDOException;
use App\Config\Database;

class Fournisseur {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getAll() {
        try {
            $stmt = $this->db->query("SELECT * FROM fournisseurs ORDER BY nom ASC");
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Debug - Vérification des données récupérées
            error_log("Données fournisseurs : " . print_r($result, true));
            
            return $result;
        } catch (PDOException $e) {
            error_log("Erreur dans getAll : " . $e->getMessage());
            return false;
        }
    }

    // Ajout de la méthode getById manquante
    public function getById($id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM fournisseurs WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur dans getById : " . $e->getMessage());
            return null;
        }
    }

    public function update($id, $data) {
        try {
            $stmt = $this->db->prepare("
                UPDATE fournisseurs 
                SET nom = ?, 
                    email = ?, 
                    telephone = ?, 
                    adresse = ? 
                WHERE id = ?
            ");
            
            $result = $stmt->execute([
                $data['nom'],
                $data['email'],
                $data['telephone'],
                $data['adresse'],
                $id
            ]);

            if (!$result) {
                throw new \PDOException("La mise à jour a échoué");
            }

            return true;
        } catch (PDOException $e) {
            error_log("Erreur dans update : " . $e->getMessage());
            return false;
        }
    }

    public function delete($id) {
        try {
            $this->db->beginTransaction();

            // Vérifier si le fournisseur existe
            $stmt = $this->db->prepare("SELECT id FROM fournisseurs WHERE id = ?");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                throw new \Exception("Fournisseur non trouvé");
            }

            // Supprimer le fournisseur
            $stmt = $this->db->prepare("DELETE FROM fournisseurs WHERE id = ?");
            $result = $stmt->execute([$id]);

            if (!$result) {
                throw new \PDOException("Échec de la suppression du fournisseur");
            }

            $this->db->commit();
            return true;

        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("Erreur dans delete : " . $e->getMessage());
            return false;
        }
    }

    public function create($data) {
        try {
            // Validation des données
            if (empty($data['nom']) || empty($data['email'])) {
                return false;
            }

            $sql = "INSERT INTO fournisseurs (nom, email, telephone, adresse) 
                    VALUES (:nom, :email, :telephone, :adresse)";
            
            $stmt = $this->db->prepare($sql);
            $success = $stmt->execute([
                ':nom' => $data['nom'],
                ':email' => $data['email'],
                ':telephone' => $data['telephone'] ?? '',
                ':adresse' => $data['adresse'] ?? ''
            ]);
            
            if ($success) {
                return $this->db->lastInsertId();
            }
            
            return false;
        } catch (\PDOException $e) {
            error_log("Erreur création fournisseur : " . $e->getMessage());
            return false;
        }
    }
}
