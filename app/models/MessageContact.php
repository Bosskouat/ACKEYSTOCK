<?php
namespace App\Models;

use PDO;

class MessageContact {
    private $db;

    public function __construct(PDO $pdo) {
        $this->db = $pdo;
    }

    public function getAll() {
        return $this->db->query("SELECT * FROM messages_contact ORDER BY date_message DESC")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function insert($data) {
        $stmt = $this->db->prepare("INSERT INTO messages_contact (nom, email, message) VALUES (?, ?, ?)");
        return $stmt->execute([
            $data['nom'],
            $data['email'],
            $data['message']
        ]);
    }
}
