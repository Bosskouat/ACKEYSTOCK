<?php

namespace App\Controllers;

use App\Models\Produit;
use Twig\Environment;

class ProduitController {
    private $twig;
    private $produitModel;

    public function __construct(Environment $twig) {
        $this->twig = $twig;
        $this->produitModel = new Produit();
    }

    public function afficherProduits() {
        $produits = $this->produitModel->getAllProduits();
        echo $this->twig->render('produits.html.twig', [
            'produits' => $produits
        ]);
    }
}
