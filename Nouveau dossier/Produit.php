<?php

namespace App\Models;

use App\Config\Database;

class Produit {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getAllProduits() {
        $query = "SELECT * FROM produits";
        $stmt = $this->db->query($query);
        return $stmt->fetchAll();
    }
}
