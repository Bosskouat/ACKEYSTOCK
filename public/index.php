<?php
// Activation du debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/controllers/PageController.php';
require_once __DIR__ . '/../app/controllers/ProduitController.php';
require_once __DIR__ . '/../app/controllers/UtilisateurController.php';
require_once __DIR__ . '/../app/controllers/FournisseurController.php';
require_once __DIR__ . '/../app/controllers/CommandeController.php';
require_once __DIR__ . '/../app/controllers/CategorieController.php';
require_once __DIR__ . '/../app/controllers/EntreeStockController.php';
require_once __DIR__ . '/../app/controllers/SortieStockController.php';
require_once __DIR__ . '/../app/controllers/RapportController.php';
require_once __DIR__ . '/../app/Controllers/AlertController.php';
require_once __DIR__ . '/../app/config/Permissions.php';
require_once __DIR__ . '/../app/middleware/AuthMiddleware.php';

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use App\Controllers\PageController;
use App\Controllers\ProduitController;
use App\Controllers\UtilisateurController;
use App\Controllers\FournisseurController;
use App\Controllers\CommandeController;
use App\Controllers\CategorieController;
use App\Controllers\EntreeStockController;
use App\Controllers\SortieStockController;
use App\Controllers\RapportController;
use App\Controllers\AlertController;
use App\Middleware\AuthMiddleware;
use App\Config\Permissions;
use App\Controllers\JournalActionsController;

// Initialisation de Twig
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../app/templates');
$twig = new \Twig\Environment($loader, [
    'debug' => true,
    'cache' => false,
]);

// Définir la fonction checkPermission pour Twig
$twig->addFunction(new \Twig\TwigFunction('checkPermission', function($role, $module, $action) {
    return \App\Config\Permissions::hasPermission($role, $module, $action);
}));

// Ajouter une fonction pour encoder en JSON (utile pour le debug)
$twig->addFilter(new \Twig\TwigFilter('json_encode', function ($data) {
    return json_encode($data, JSON_PRETTY_PRINT);
}));

// Ajouter l'utilisateur comme variable globale Twig
$twig->addGlobal('user', $_SESSION['user'] ?? null);

// URI demandée
$uri = $_GET['uri'] ?? 'accueil';
error_log("URI demandée : " . $uri); // Ajoutez cette ligne pour déboguer

// Instanciation du contrôleur principal
$controller = new PageController($twig);
$produitController = new ProduitController($twig);
$utilisateurController = new UtilisateurController($twig);
$fournisseurController = new FournisseurController($twig);
$commandeController = new CommandeController($twig);
$categorieController = new CategorieController($twig);
$entreeStockController = new EntreeStockController($twig);
$sortieStockController = new SortieStockController($twig);
$rapportController = new RapportController($twig);
$alertController = new AlertController($twig);
$journalController = new JournalActionsController($twig);

// En haut de votre fichier index.php ou dans le contrôleur du dashboard
if (isset($_SESSION['user'])) {
    error_log("Session utilisateur: " . print_r($_SESSION['user'], true));
}

// Code de diagnostic
if (isset($_SESSION['user'])) {
    error_log("=== DIAGNOSTIC PERMISSIONS ===");
    error_log("User Role: " . $_SESSION['user']['role']);
    error_log("Module 'categories', Action 'view': " . 
        (\App\Config\Permissions::hasPermission($_SESSION['user']['role'], 'categories', 'view') ? 'OUI' : 'NON'));
    error_log("Module 'fournisseurs', Action 'view': " . 
        (\App\Config\Permissions::hasPermission($_SESSION['user']['role'], 'fournisseurs', 'view') ? 'OUI' : 'NON'));
    error_log("Module 'utilisateurs', Action 'view': " . 
        (\App\Config\Permissions::hasPermission($_SESSION['user']['role'], 'utilisateurs', 'view') ? 'OUI' : 'NON'));
    error_log("============================");
}

// Diagnostic détaillé
if (isset($_SESSION['user'])) {
    error_log("========== DIAGNOSTIC SESSION ET PERMISSIONS ==========");
    error_log("SESSION USER: " . print_r($_SESSION['user'], true));
    error_log("USER ROLE: " . $_SESSION['user']['role']);
    
    // Vérifier si le rôle correspond exactement à une des constantes
    error_log("ROLE MATCH ADMIN? " . (($_SESSION['user']['role'] === \App\Config\Permissions::ROLE_ADMIN) ? "OUI" : "NON"));
    error_log("ROLE MATCH GESTIONNAIRE? " . (($_SESSION['user']['role'] === \App\Config\Permissions::ROLE_GESTIONNAIRE) ? "OUI" : "NON"));
    error_log("ROLE MATCH EMPLOYE? " . (($_SESSION['user']['role'] === \App\Config\Permissions::ROLE_EMPLOYE) ? "OUI" : "NON"));
    
    // Tester quelques permissions
    foreach (['produits', 'categories', 'utilisateurs', 'commandes'] as $module) {
        error_log("Permission $module/view: " . 
            (\App\Config\Permissions::hasPermission($_SESSION['user']['role'], $module, 'view') ? "OUI" : "NON"));
    }
    error_log("====================================================");
}

// Routage basique
switch ($uri) {
    case '/':
    case 'index':
        $controller->renderPage('index');
        break;

    case 'login':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $utilisateurController->login();
        } else {
            $controller->renderPage('login');
        }
        break;

    case 'dashboard':
        AuthMiddleware::requirePermission(Permissions::MODULE_DASHBOARD, Permissions::ACTION_VIEW);
        $controller->renderPage('dashboard');
        break;
    case 'gestion-stock':
        $controller->renderPage('gestion-stock');
        break;

     case 'entrees-stock':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $entreeStockController->store($_POST);
        } else {
            $entreeStockController->index();
        }
        break;

    // Route pour afficher la liste des sorties
    case 'sorties-stock':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $sortieStockController->store($_POST);
        } else {
            $sortieStockController->index();
        }
        break;

    // Route pour ajouter une nouvelle sortie
    case 'ajouter-sortie-stock':
        $sortieStockController->create();
        break;

    case 'stock':
        $controller->renderPage('stock');
        break;
            
    // Pour les routes de rapports
    case 'rapports':
        AuthMiddleware::requirePermission(Permissions::MODULE_RAPPORTS, Permissions::ACTION_VIEW);
        $rapportController->index();
        break;

    case 'rapports-mouvements':
        AuthMiddleware::requirePermission(Permissions::MODULE_RAPPORTS, Permissions::ACTION_VIEW);
        $rapportController->mouvementsStock();
        break;

    case 'rapports-vendus':
        AuthMiddleware::requirePermission(Permissions::MODULE_RAPPORTS, Permissions::ACTION_VIEW);
        $rapportController->produitsPlusVendus();
        break;

    case 'rapports-previsions':
        AuthMiddleware::requirePermission(Permissions::MODULE_RAPPORTS, Permissions::ACTION_VIEW);
        $rapportController->previsionsAchats();
        break;

    case 'export-rapport':
        AuthMiddleware::requirePermission(Permissions::MODULE_RAPPORTS, Permissions::ACTION_EXPORT);
        $rapportController->exportRapport();
        break;

    case 'produits':
        AuthMiddleware::requirePermission(Permissions::MODULE_PRODUITS, Permissions::ACTION_VIEW);
        $produitController->index();
        break;
    
    case 'ajouter-produit':
        AuthMiddleware::requirePermission(Permissions::MODULE_PRODUITS, Permissions::ACTION_ADD);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $produitController->store();
        } else {
            $produitController->create();
        }
        break;
    
    case 'modifier-produit':
        AuthMiddleware::requirePermission(Permissions::MODULE_PRODUITS, Permissions::ACTION_EDIT);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $produitController->update($_GET['id']);
        } else {
            $produitController->edit($_GET['id']);
        }
        break;

    case 'supprimer-produit':
        AuthMiddleware::requirePermission(Permissions::MODULE_PRODUITS, Permissions::ACTION_DELETE);
        if (isset($_GET['id'])) {
            $produitController->delete($_GET['id']);
        } else {
            header('Location: index.php?uri=produits');
        }
        break;

    case 'commandes':
        AuthMiddleware::requirePermission(Permissions::MODULE_COMMANDES, Permissions::ACTION_VIEW);
        $commandeController->index();
        break;
    
    case 'ajouter-commande':
        AuthMiddleware::requirePermission(Permissions::MODULE_COMMANDES, Permissions::ACTION_ADD);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $commandeController->create();
        } else {
            $commandeController->showAddForm();
        }
        break;
    
    case 'modifier-commande':
        $id = isset($_GET['id']) ? $_GET['id'] : null;
        if ($id) {
            $commandeController->edit($id);
        } else {
            header('Location: index.php?uri=commandes');
        }
        break;
    
    case 'supprimer-commande':
        if (isset($_GET['id'])) {
            $commandeController->delete($_GET['id']);
        } else {
            header('Location: index.php?uri=commandes');
            exit;
        }
        break;
    
    
    

    case 'fournisseurs':
        AuthMiddleware::requirePermission(Permissions::MODULE_FOURNISSEURS, Permissions::ACTION_VIEW);
        $fournisseurController->index();
        break;
    
    case 'ajouter-fournisseur':
        AuthMiddleware::requirePermission(Permissions::MODULE_FOURNISSEURS, Permissions::ACTION_ADD);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $fournisseurController->create();
        } else {
            $fournisseurController->showAddForm();
        }
        break;
    
    case 'modifier-fournisseur':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $fournisseurController->update($_GET['id']);
        } else {
            $fournisseurController->edit($_GET['id']);
        }
        break;
    
    case 'supprimer-fournisseur':
        if (isset($_GET['id'])) {
            $fournisseurController->delete($_GET['id']);
        } else {
            header('Location: index.php?uri=fournisseurs');
        }
        break;
    
        case 'categories':
            AuthMiddleware::requirePermission(Permissions::MODULE_CATEGORIES, Permissions::ACTION_VIEW);
            $categorieController->index();
            break;
    
    
    case 'ajouter-categorie':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $categorieController->create();
        } else {
            $categorieController->showAddForm();
        }
        break;
    
    case 'modifier-categorie':
        AuthMiddleware::requirePermission(Permissions::MODULE_CATEGORIES, Permissions::ACTION_EDIT);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $categorieController->update($_GET['id']);
        } else {
            $categorieController->edit($_GET['id']);
        }
        break;
    
    case 'supprimer-categorie':
        AuthMiddleware::requirePermission(Permissions::MODULE_CATEGORIES, Permissions::ACTION_DELETE);
        if (isset($_GET['id'])) {
            $categorieController->delete($_GET['id']);
        } else {
            header('Location: index.php?uri=categories');
        }
        break;
    
    

    case 'utilisateurs':
        AuthMiddleware::requirePermission(Permissions::MODULE_UTILISATEURS, Permissions::ACTION_VIEW);
        $utilisateurController->index();
        break;
    
    case 'ajouter-utilisateur':
        AuthMiddleware::requirePermission(Permissions::MODULE_UTILISATEURS, Permissions::ACTION_ADD);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $utilisateurController->create();
        } else {
            $utilisateurController->showAddForm();
        }
        break;
    
    case 'modifier-utilisateur':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $utilisateurController->update($_GET['id']);
        } else {
            $utilisateurController->edit($_GET['id']);
        }
        break;
    
    case 'supprimer-utilisateur':
        if (isset($_GET['id'])) {
            $utilisateurController->delete($_GET['id']);
        } else {
            header('Location: index.php?uri=utilisateurs');
        }
        break;
    

    case 'contact':
        $controller->renderPage('contact');
        break;

    case 'mentions-legales':
        $controller->renderPage('mentions-legales');
        break;

    case 'politique-confidentialite':
        $controller->renderPage('politique-confidentialite');
        break;

    case 'resetPassword':
        $controller->renderPage('resetPassword');
        break;

    case 'logout':
        $controller->renderPage('logout');
        break;
        
    case 'profil':
        $controller->renderPage('profil');
        break;  

    case 'accessibilite':
        $controller->renderPage('accessibilite');
        break;

    // Route pour afficher la liste des entrées
    case 'entrees-stock':
        AuthMiddleware::requirePermission(Permissions::MODULE_ENTREES, Permissions::ACTION_VIEW);
        $entreeStockController->index();
        break;

    // Route pour afficher le formulaire d'ajout
    case 'ajouter-entree':
        $controller = new EntreeStockController($twig);
        $controller->afficherFormulaire();
        break;

    // Route pour modifier une entrée de stock
    case 'modifier-entree':
        $controller = new EntreeStockController($twig);
        $controller->afficherFormulaire();
        break;

    // Route pour traiter le formulaire d'ajout ou de modification
    case 'traiter-entree':
        $controller = new EntreeStockController($twig);
        $controller->traiterFormulaire();
        break;

    // Route pour supprimer une entrée de stock
    case 'supprimer-entree':
        $controller = new EntreeStockController($twig);
        $controller->supprimer();
        break;

    // Route pour supprimer une sortie de stock
    case 'supprimer-sortie-stock':
        if (isset($_GET['id'])) {
            $sortieStockController->delete($_GET['id']);
        } else {
            $_SESSION['message'] = [
                'type' => 'error',
                'text' => 'ID de sortie non spécifié'
            ];
            header('Location: index.php?uri=sorties-stock');
        }
        break;

    // Route pour modifier une sortie de stock
    case 'modifier-sortie-stock':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $sortieStockController->update($_POST);
        } else {
            $sortieStockController->edit($_GET['id']);
        }
        break;

    // Route pour mettre à jour uniquement le statut
    case 'update-commande':
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['id'])) {
            $commandeController->update($_GET['id']);
        } else {
            header('Location: index.php?uri=commandes');
            exit;
        }
        break;

    // Route pour mettre à jour uniquement le statut
    case 'update-commande-status':
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['id'])) {
            $commandeController->updateStatus($_GET['id']);
        } else {
            header('Location: index.php?uri=commandes');
            exit;
        }
        break;

    // Pour les routes d'alertes
    case 'alertes':
        AuthMiddleware::requirePermission(Permissions::MODULE_ALERTES, Permissions::ACTION_VIEW);
        $alertController->index();
        break;

    case 'mark-alert-read':
        AuthMiddleware::requirePermission(Permissions::MODULE_ALERTES, Permissions::ACTION_EDIT);
        $alertController->markAsRead();
        break;

    case 'check-stock-levels':
        AuthMiddleware::requirePermission(Permissions::MODULE_ALERTES, Permissions::ACTION_VIEW);
        $alertController->checkStockLevels();
        break;

    case 'journal-actions':
        // Vérifier la permission
        AuthMiddleware::requirePermission(Permissions::MODULE_JOURNAL_ACTIONS, Permissions::ACTION_VIEW);
        // Appeler le contrôleur approprié
        $journalController->index();
        break;

    // Route par défaut pour les pages non trouvées
    default:
        header('HTTP/1.0 404 Not Found');
        echo $twig->render('404.html.twig');
        break;
}
