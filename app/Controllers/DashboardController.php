<?php
namespace App\Controllers;

use App\Models\Produit;
use App\Models\Commande;
use App\Models\Notification;
use App\Models\SortieStock;
use App\Models\EntreeStock;
use App\Core\View;

class DashboardController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function index() {
        $produitModel = new Produit($this->db);
        $commandeModel = new Commande($this->db);
        $notifModel = new Notification($this->db);
        $sortieModel = new SortieStock($this->db);
        $entreeModel = new EntreeStock($this->db);

        $nbProduits = count($produitModel->getAll());
        $nbCommandes = count($commandeModel->getAll());
        $nbSorties = count($sortieModel->getAll());
        $nbEntrees = count($entreeModel->getAll());
        $notifications = $notifModel->getAllByUser($_SESSION['user_id'] ?? null);

        View::render('dashboard/index.twig', [
            'nbProduits' => $nbProduits,
            'nbCommandes' => $nbCommandes,
            'nbSorties' => $nbSorties,
            'nbEntrees' => $nbEntrees,
            'notifications' => $notifications
        ]);
    }
}
