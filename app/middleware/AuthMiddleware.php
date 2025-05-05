<?php
// filepath: c:\www\ACKEYSTOCK\app\middleware\AuthMiddleware.php
namespace App\Middleware;

use App\Config\Permissions;

class AuthMiddleware {
    /**
     * Vérifie si l'utilisateur est connecté
     * @return bool
     */
    public static function isLoggedIn() {
        return isset($_SESSION['user']) && !empty($_SESSION['user']);
    }
    
    /**
     * Vérifie si l'utilisateur a la permission requise
     * @param string $module Le module à vérifier
     * @param string $action L'action à vérifier
     * @return bool
     */
    public static function checkPermission($module, $action) {
        // Vérifie si l'utilisateur est connecté
        if (!self::isLoggedIn()) {
            return false;
        }
        
        $role = $_SESSION['user']['role'];
        $hasPermission = Permissions::hasPermission($role, $module, $action);
        
        // Ajouter ce code de débogage
        error_log("Vérification de permission: rôle=$role, module=$module, action=$action, résultat=" . ($hasPermission ? "autorisé" : "refusé"));
        
        return $hasPermission;
    }
    
    /**
     * Redirige vers la page de connexion si non connecté
     * @return void
     */
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            header('Location: index.php?uri=login');
            exit;
        }
    }
    
    /**
     * Vérifie la permission et redirige si non autorisé
     * @param string $module Le module à vérifier
     * @param string $action L'action à vérifier
     * @return void
     */
    public static function requirePermission($module, $action) {
        self::requireLogin();
        
        if (!self::checkPermission($module, $action)) {
            header('Location: index.php?uri=access-denied');
            exit;
        }
    }
}