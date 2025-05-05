<?php
namespace App\Models;

use PDO;

class ReinitialisationMDP {
    private $db;

    public function __construct(PDO $pdo) {
        $this->db = $pdo;
    }

    public function create($utilisateur_id, $token, $expiration) {
        $stmt = $this->db->prepare("INSERT INTO reinitialisations_mdp (utilisateur_id, token, expiration) VALUES (?, ?, ?)");
        return $stmt->execute([$utilisateur_id, $token, $expiration]);
    }

    public function getByToken($token) {
        $stmt = $this->db->prepare("SELECT * FROM reinitialisations_mdp WHERE token = ?");
        $stmt->execute([$token]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function deleteByToken($token) {
        $stmt = $this->db->prepare("DELETE FROM reinitialisations_mdp WHERE token = ?");
        return $stmt->execute([$token]);
    }
}
