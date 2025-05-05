<?php
namespace App\Models;

use PDO;
use PDOException;
use App\Config\Database;

class Utilisateur {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getAll() {
        try {
            $stmt = $this->db->query("SELECT id, nom, email, role FROM utilisateurs ORDER BY nom ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur dans getAll : " . $e->getMessage());
            return [];
        }
    }

    public function getById($id) {
        try {
            $stmt = $this->db->prepare("SELECT id, nom, email, role FROM utilisateurs WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur dans getById : " . $e->getMessage());
            return null;
        }
    }

    public function getByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM utilisateurs WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function insert($data) {
        $stmt = $this->db->prepare("INSERT INTO utilisateurs (nom, email, mot_de_passe, role) VALUES (?, ?, ?, ?)");
        return $stmt->execute([
            $data['nom'],
            $data['email'],
            $data['mot_de_passe'],
            $data['role']
        ]);
    }

    public function update($id, $data) {
        try {
            // Si un nouveau mot de passe est fourni
            if (!empty($data['mot_de_passe'])) {
                $sql = "UPDATE utilisateurs 
                        SET nom = ?, email = ?, mot_de_passe = ?, role = ? 
                        WHERE id = ?";
                $hashedPassword = password_hash($data['mot_de_passe'], PASSWORD_DEFAULT);
                $params = [$data['nom'], $data['email'], $hashedPassword, $data['role'], $id];
            } else {
                // Sans modification du mot de passe
                $sql = "UPDATE utilisateurs 
                        SET nom = ?, email = ?, role = ? 
                        WHERE id = ?";
                $params = [$data['nom'], $data['email'], $data['role'], $id];
            }

            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Erreur dans update : " . $e->getMessage());
            return false;
        }
    }

    public function delete($id) {
        try {
            // Vérifier que ce n'est pas un admin
            $stmt = $this->db->prepare("SELECT role FROM utilisateurs WHERE id = ?");
            $stmt->execute([$id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && $user['role'] === 'admin') {
                return false;
            }

            $stmt = $this->db->prepare("DELETE FROM utilisateurs WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Erreur dans delete : " . $e->getMessage());
            return false;
        }
    }

    public function create($data) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO utilisateurs (nom, email, mot_de_passe, role) 
                VALUES (?, ?, ?, ?)
            ");
            
            // Hasher le mot de passe
            $hashedPassword = password_hash($data['mot_de_passe'], PASSWORD_DEFAULT);
            
            return $stmt->execute([
                $data['nom'],
                $data['email'],
                $hashedPassword,
                $data['role']
            ]);
        } catch (PDOException $e) {
            error_log("Erreur dans create : " . $e->getMessage());
            return false;
        }
    }

    public function authenticate($email, $password) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM utilisateurs WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['mot_de_passe'])) {
                unset($user['mot_de_passe']); // Ne pas stocker le mot de passe en session
                return $user;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Erreur d'authentification : " . $e->getMessage());
            return false;
        }
    }
}
