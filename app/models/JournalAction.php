<?php
// filepath: c:\www\ACKEYSTOCK\app\models\JournalAction.php
namespace App\Models;

use PDO;
use App\Config\Database;

class JournalAction {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getAll() {
        try {
            $sql = "SELECT ja.*, u.nom as utilisateur_nom 
                    FROM journal_actions ja
                    LEFT JOIN utilisateurs u ON ja.utilisateur_id = u.id
                    ORDER BY ja.date_action DESC";
            
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Erreur dans getAll JournalAction : " . $e->getMessage());
            return [];
        }
    }

    public function add($data) {
        try {
            $sql = "INSERT INTO journal_actions 
                    (utilisateur_id, action, entite, entite_id, details, date_action) 
                    VALUES (:utilisateur_id, :action, :entite, :entite_id, :details, NOW())";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':utilisateur_id' => $data['utilisateur_id'] ?? null,
                ':action' => $data['action'],
                ':entite' => $data['entite'],
                ':entite_id' => $data['entite_id'] ?? null,
                ':details' => $data['details'] ?? null
            ]);
        } catch (\PDOException $e) {
            error_log("Erreur dans add JournalAction : " . $e->getMessage());
            return false;
        }
    }

    public function getEntries($filters = [], $limit = null, $offset = null) {
        try {
            $conditions = [];
            $params = [];
            
            // Construction de la requête avec filtres
            if (!empty($filters['user_id'])) {
                $conditions[] = "ja.utilisateur_id = :user_id";
                $params[':user_id'] = $filters['user_id'];
            }
            
            if (!empty($filters['action'])) {
                $conditions[] = "ja.action = :action";
                $params[':action'] = $filters['action'];
            }
            
            if (!empty($filters['module'])) {
                $conditions[] = "ja.entite = :module";
                $params[':module'] = $filters['module'];
            }
            
            if (!empty($filters['date_debut'])) {
                $conditions[] = "ja.date_action >= :date_debut";
                $params[':date_debut'] = $filters['date_debut'] . ' 00:00:00';
            }
            
            if (!empty($filters['date_fin'])) {
                $conditions[] = "ja.date_action <= :date_fin";
                $params[':date_fin'] = $filters['date_fin'] . ' 23:59:59';
            }
            
            // Construction de la clause WHERE
            $whereClause = !empty($conditions) ? " WHERE " . implode(" AND ", $conditions) : "";
            
            // Construction de la requête SQL avec pagination
            $sql = "SELECT ja.*, u.nom as utilisateur_nom 
                    FROM journal_actions ja
                    LEFT JOIN utilisateurs u ON ja.utilisateur_id = u.id
                    $whereClause
                    ORDER BY ja.date_action DESC";
            
            // Ajout de la limite si spécifiée
            if ($limit !== null) {
                $sql .= " LIMIT :limit";
                $params[':limit'] = $limit;
                
                if ($offset !== null) {
                    $sql .= " OFFSET :offset";
                    $params[':offset'] = $offset;
                }
            }
            
            $stmt = $this->db->prepare($sql);
            
            // Bind des paramètres
            foreach ($params as $key => $value) {
                if ($key == ':limit' || $key == ':offset') {
                    $stmt->bindValue($key, $value, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue($key, $value);
                }
            }
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Erreur dans getEntries JournalAction : " . $e->getMessage());
            return [];
        }
    }

    public function countEntries($filters = []) {
        try {
            $conditions = [];
            $params = [];
            
            // Construction des filtres comme dans getEntries()
            if (!empty($filters['user_id'])) {
                $conditions[] = "ja.utilisateur_id = :user_id";
                $params[':user_id'] = $filters['user_id'];
            }
            
            if (!empty($filters['action'])) {
                $conditions[] = "ja.action = :action";
                $params[':action'] = $filters['action'];
            }
            
            if (!empty($filters['module'])) {
                $conditions[] = "ja.entite = :module";
                $params[':module'] = $filters['module'];
            }
            
            if (!empty($filters['date_debut'])) {
                $conditions[] = "ja.date_action >= :date_debut";
                $params[':date_debut'] = $filters['date_debut'] . ' 00:00:00';
            }
            
            if (!empty($filters['date_fin'])) {
                $conditions[] = "ja.date_action <= :date_fin";
                $params[':date_fin'] = $filters['date_fin'] . ' 23:59:59';
            }
            
            // Construction de la clause WHERE
            $whereClause = !empty($conditions) ? " WHERE " . implode(" AND ", $conditions) : "";
            
            $sql = "SELECT COUNT(*) as total FROM journal_actions ja $whereClause";
            
            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['total'];
        } catch (\PDOException $e) {
            error_log("Erreur dans countEntries JournalAction : " . $e->getMessage());
            return 0;
        }
    }

    public function getUsersList() {
        try {
            $sql = "SELECT id, nom FROM utilisateurs ORDER BY nom";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Erreur dans getUsersList JournalAction : " . $e->getMessage());
            return [];
        }
    }
}
