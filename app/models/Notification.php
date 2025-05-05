<?php
namespace App\Models;

use PDO;

class Notification {
    private $db;

    public function __construct(PDO $pdo) {
        $this->db = $pdo;
    }

    public function getAllByUser($utilisateur_id) {
        $stmt = $this->db->prepare("SELECT * FROM notifications WHERE utilisateur_id = ? ORDER BY date_notif DESC");
        $stmt->execute([$utilisateur_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($message, $type, $utilisateur_id) {
        $stmt = $this->db->prepare("INSERT INTO notifications (message, type, utilisateur_id) VALUES (?, ?, ?)");
        return $stmt->execute([$message, $type, $utilisateur_id]);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM notifications WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
