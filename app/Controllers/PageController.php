<?php

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Config\Permissions;
use Twig\Environment;
use App\Models\Alert;

class PageController 
{
    private $twig;
    protected $alertModel;

    public function __construct(Environment $twig) 
    {
        $this->twig = $twig;
        $this->alertModel = new Alert();
        
        // Ajouter les alertes globalement
        $this->twig->addGlobal('alerts', $this->getAlerts());
    }

    protected function getAlerts() {
        return $this->alertModel->getUnreadAlerts();
    }

    public function renderPage($template, $data = [])
    {
        try {
            // Pages qui ne nécessitent pas d'authentification
            $publicPages = ['index', 'login', 'resetPassword', 'contact', 'mentions-legales', 'politique-confidentialite'];
            
            // Si la page n'est pas publique, vérifier l'authentification
            if (!in_array($template, $publicPages)) {
                AuthMiddleware::requireLogin();
            }
            
            // Ajouter page courante aux données
            $data['current_page'] = $template;
            
            // Récupérer les messages de session
            if (isset($_SESSION['message'])) {
                $data['message'] = $_SESSION['message'];
                unset($_SESSION['message']);
            }
            
            // Rendre la vue
            echo $this->twig->render($template . '.html.twig', $data);
        } catch (\Exception $e) {
            error_log("Erreur dans renderPage : " . $e->getMessage());
            echo $this->twig->render('error.html.twig', [
                'message' => 'Une erreur est survenue lors du chargement de la page.'
            ]);
        }
    }

    public function index()
    {
        $this->renderPage('index');
    }

    public function login()
    {
        $this->renderPage('login');
    }

    public function dashboard()
    {
        $this->renderPage('dashboard');
    }

    public function produits()
    {
        $this->renderPage('produits');
    }
    public function ajouterProduit()
    { 
        $this->renderPage('ajouter-produit');
    }

    public function modifierProduit()
    { 
        $this->renderPage('modifier-produit'); 
    }

    public function categories()
    { 
        $this->renderPage('categories'); 
    }

    public function ajouterCategorie() 
    { 
        $this->renderPage('ajouter-categorie');
    }

    public function modifierCategorie() 
    { 
        $this->renderPage('modifier-categorie');
    }


    public function commandes()
    {
        $this->renderPage('commandes');
    }

    public function ajouterCommande()
    { 
        $this->renderPage('ajouter-commande');
    }
    public function modifierCommande()
    { 
        $this->renderPage('modifier-commande');
    }

    public function utilisateurs()
    {
        $this->renderPage('utilisateurs');
    }
    public function ajouterUtilisateur()
    { 
        $this->renderPage('ajouter-utilisateur');
    }
    public function modifierUtilisateur()
    { 
        $this->renderPage('modifier-utilisateur');
    }
    public function fournisseurs()
     {
         $this->renderPage('fournisseurs');
     }

    public function ajouterFournisseur()
     { 
        $this->renderPage('ajouter-fournisseur');
     }

    public function modifierFournisseur()
     { 
        $this->renderPage('modifier-fournisseur'); 
    }

    public function contact()
    {
        $this->renderPage('contact');
    }

    public function mentionsLegales()
    {
        $this->renderPage('mentions-legales');
    }

    public function politiqueConfidentialite()
    {
        $this->renderPage('politique-confidentialite');
    }

    public function journal() 
    { 
        $this->renderPage('journal-actions');
    }

    public function rapports() 
    { 
        $this->renderPage('rapports'); 
    }

    public function recherche()
    { 
        $this->renderPage('recherche'); 
    }

    public function alertes() 
    { 
        $this->renderPage('alertes'); 
    }

    public function resetPassword() 
    { 
        $this->renderPage('reset-password'); 
    }

    public function logout()
     { 
        $this->renderPage('logout'); }

    public function accessibilite()
    {
        $this->renderPage('accessibilite');
    }

    public function error404()
    {
        http_response_code(404);
        $this->renderPage('404');
    }
}