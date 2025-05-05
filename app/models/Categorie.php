<?php
namespace App\Models;

use PDO;
use PDOException;
use App\Config\Database;

class Categorie {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getAll() {
        try {
            // Vérification explicite de la table
            $check = $this->db->query("SHOW TABLES LIKE 'categories'");
            if ($check->rowCount() == 0) {
                error_log("La table 'categories' n'existe pas");
                return [];
            }

            // Requête avec gestion d'erreur explicite
            $stmt = $this->db->prepare("SELECT * FROM categories ORDER BY nom ASC");
            if (!$stmt->execute()) {
                error_log("Erreur d'exécution de la requête: " . print_r($stmt->errorInfo(), true));
                return [];
            }

            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("Catégories trouvées : " . count($categories));
            return $categories;

        } catch (PDOException $e) {
            error_log("Erreur dans getAll: " . $e->getMessage());
            return [];
        }
    }

    public function insert($data) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO categories (nom, description) 
                VALUES (?, ?)
            ");
            
            $result = $stmt->execute([
                $data['nom'],
                $data['description']
            ]);

            if (!$result) {
                error_log("Erreur lors de l'insertion: " . print_r($stmt->errorInfo(), true));
                return false;
            }

            return true;
        } catch (PDOException $e) {
            error_log("Erreur dans insert: " . $e->getMessage());
            return false;
        }
    }

    public function update($id, $data) {
        try {
            $stmt = $this->db->prepare("
                UPDATE categories 
                SET nom = ?, 
                    description = ?
                WHERE id = ?
            ");
            
            $result = $stmt->execute([
                $data['nom'],
                $data['description'],
                $id
            ]);

            if (!$result) {
                error_log("Erreur lors de la mise à jour: " . print_r($stmt->errorInfo(), true));
                return false;
            }

            return true;
        } catch (PDOException $e) {
            error_log("Erreur dans update: " . $e->getMessage());
            return false;
        }
    }

    public function getById($id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            
            $categorie = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$categorie) {
                error_log("Catégorie avec l'ID $id non trouvée");
                return null;
            }
            
            return $categorie;
        } catch (PDOException $e) {
            error_log("Erreur dans getById: " . $e->getMessage());
            return null;
        }
    }

    public function delete($id) {
        try {
            $this->db->beginTransaction();

            // Vérifier si la catégorie existe
            $stmt = $this->db->prepare("SELECT id FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                throw new \Exception("Catégorie non trouvée");
            }

            // Supprimer la catégorie
            $stmt = $this->db->prepare("DELETE FROM categories WHERE id = ?");
            $result = $stmt->execute([$id]);

            if (!$result) {
                throw new \PDOException("Échec de la suppression de la catégorie");
            }

            $this->db->commit();
            return true;

        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("Erreur dans delete : " . $e->getMessage());
            throw $e;
        }
    }
}
