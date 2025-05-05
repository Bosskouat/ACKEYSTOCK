<?php
// filepath: c:\www\ACKEYSTOCK\app\config\Permissions.php
namespace App\Config;

class Permissions {
    // Assurez-vous que les constantes correspondent exactement aux rôles stockés en base de données

    // Définition des rôles
    const ROLE_ADMIN = 'admin';           // Doit correspondre exactement à la valeur en base
    const ROLE_GESTIONNAIRE = 'gestionnaire'; // Doit correspondre exactement à la valeur en base
    const ROLE_EMPLOYE = 'employe';       // Doit correspondre exactement à la valeur en base
    
    // Définition des modules
    const MODULE_DASHBOARD = 'dashboard';
    const MODULE_PRODUITS = 'produits';
    const MODULE_CATEGORIES = 'categories';
    const MODULE_FOURNISSEURS = 'fournisseurs';
    const MODULE_ENTREES = 'entrees-stock';
    const MODULE_SORTIES = 'sorties-stock';
    const MODULE_UTILISATEURS = 'utilisateurs';
    const MODULE_RAPPORTS = 'rapports';
    const MODULE_ALERTES = 'alertes';
    const MODULE_JOURNAL_ACTIONS = 'journal-actions';
    const MODULE_COMMANDES = 'commandes';
    
    // Définition des actions
    const ACTION_VIEW = 'view';
    const ACTION_ADD = 'add';
    const ACTION_EDIT = 'edit';
    const ACTION_DELETE = 'delete';
    const ACTION_EXPORT = 'export';
    
    // Matrice des permissions
    private static $permissionsMatrix = [
        // Admin a accès à tout
        self::ROLE_ADMIN => [
            self::MODULE_DASHBOARD => [self::ACTION_VIEW],
            self::MODULE_PRODUITS => [self::ACTION_VIEW, self::ACTION_ADD, self::ACTION_EDIT, self::ACTION_DELETE],
            self::MODULE_CATEGORIES => [self::ACTION_VIEW, self::ACTION_ADD, self::ACTION_EDIT, self::ACTION_DELETE],
            self::MODULE_FOURNISSEURS => [self::ACTION_VIEW, self::ACTION_ADD, self::ACTION_EDIT, self::ACTION_DELETE],
            self::MODULE_ENTREES => [self::ACTION_VIEW, self::ACTION_ADD, self::ACTION_EDIT, self::ACTION_DELETE],
            self::MODULE_SORTIES => [self::ACTION_VIEW, self::ACTION_ADD, self::ACTION_EDIT, self::ACTION_DELETE],
            self::MODULE_UTILISATEURS => [self::ACTION_VIEW, self::ACTION_ADD, self::ACTION_EDIT, self::ACTION_DELETE],
            self::MODULE_RAPPORTS => [self::ACTION_VIEW, self::ACTION_EXPORT],
            self::MODULE_ALERTES => [self::ACTION_VIEW, self::ACTION_EDIT],
            self::MODULE_JOURNAL_ACTIONS => [self::ACTION_VIEW, self::ACTION_EXPORT],
            self::MODULE_COMMANDES => [self::ACTION_VIEW, self::ACTION_ADD, self::ACTION_EDIT, self::ACTION_DELETE]
        ],
        
        // Gestionnaire n'a pas accès aux utilisateurs ni au journal d'actions
        self::ROLE_GESTIONNAIRE => [
            self::MODULE_DASHBOARD => [self::ACTION_VIEW],
            self::MODULE_PRODUITS => [self::ACTION_VIEW, self::ACTION_ADD, self::ACTION_EDIT, self::ACTION_DELETE], // Ajoutez DELETE
            self::MODULE_CATEGORIES => [self::ACTION_VIEW, self::ACTION_ADD, self::ACTION_EDIT, self::ACTION_DELETE], // Ajoutez DELETE
            self::MODULE_FOURNISSEURS => [self::ACTION_VIEW, self::ACTION_ADD, self::ACTION_EDIT],
            self::MODULE_ENTREES => [self::ACTION_VIEW, self::ACTION_ADD, self::ACTION_EDIT],
            self::MODULE_SORTIES => [self::ACTION_VIEW, self::ACTION_ADD, self::ACTION_EDIT],
            self::MODULE_RAPPORTS => [self::ACTION_VIEW],
            self::MODULE_ALERTES => [self::ACTION_VIEW, self::ACTION_EDIT],
            self::MODULE_COMMANDES => [self::ACTION_VIEW, self::ACTION_ADD, self::ACTION_EDIT]
            // Pas d'accès à UTILISATEURS et JOURNAL_ACTIONS
        ],
        
        // Employé - accès limité
        self::ROLE_EMPLOYE => [
            self::MODULE_DASHBOARD => [self::ACTION_VIEW],
            self::MODULE_PRODUITS => [self::ACTION_VIEW],  // Lecture seule
            self::MODULE_CATEGORIES => [self::ACTION_VIEW], // Lecture seule
            self::MODULE_ENTREES => [self::ACTION_VIEW],    // Lecture seule
            self::MODULE_SORTIES => [self::ACTION_VIEW]     // Lecture seule
            // MODULE_ALERTES et MODULE_RAPPORTS retirés
        ]
    ];
    
    /**
     * Vérifie si un rôle a une permission spécifique
     */
    public static function hasPermission($role, $module, $action) {
        // Trim pour supprimer les espaces indésirables
        $role = trim($role);
        $module = trim($module);
        $action = trim($action);
        
        // Debug détaillé
        error_log("hasPermission: rôle='$role', module='$module', action='$action'");
        
        // Vérifier si le rôle existe
        if (!isset(self::$permissionsMatrix[$role])) {
            error_log("hasPermission: rôle '$role' non trouvé dans la matrice");
            
            // Afficher tous les rôles disponibles pour le debug
            $availableRoles = implode("', '", array_keys(self::$permissionsMatrix));
            error_log("hasPermission: rôles disponibles: '$availableRoles'");
            return false;
        }
        
        // Vérifier si le module existe pour ce rôle
        if (!isset(self::$permissionsMatrix[$role][$module])) {
            error_log("hasPermission: module '$module' non trouvé pour le rôle '$role'");
            return false;
        }
        
        // Vérifier si l'action est autorisée
        $hasPermission = in_array($action, self::$permissionsMatrix[$role][$module]);
        error_log("hasPermission: résultat = " . ($hasPermission ? "OUI" : "NON"));
        
        return $hasPermission;
    }
    
    /**
     * Récupère toutes les permissions d'un rôle
     */
    public static function getRolePermissions($role) {
        return self::$permissionsMatrix[$role] ?? [];
    }
    
    /**
     * Récupère tous les rôles disponibles
     */
    public static function getRoles() {
        return [
            self::ROLE_ADMIN => 'Administrateur',
            self::ROLE_GESTIONNAIRE => 'Gestionnaire',
            self::ROLE_EMPLOYE => 'Employé'
        ];
    }
    
    /**
     * Récupère tous les modules disponibles
     */
    public static function getModules() {
        return [
            self::MODULE_DASHBOARD => 'Tableau de bord',
            self::MODULE_PRODUITS => 'Produits',
            self::MODULE_CATEGORIES => 'Catégories',
            self::MODULE_FOURNISSEURS => 'Fournisseurs',
            self::MODULE_ENTREES => 'Entrées en stock',
            self::MODULE_SORTIES => 'Sorties de stock',
            self::MODULE_UTILISATEURS => 'Utilisateurs',
            self::MODULE_RAPPORTS => 'Rapports',
            self::MODULE_ALERTES => 'Alertes',
            self::MODULE_JOURNAL_ACTIONS => 'Journal des actions',
            self::MODULE_COMMANDES => 'Commandes'
        ];
    }
    
    /**
     * Récupère toutes les actions disponibles
     */
    public static function getActions() {
        return [
            self::ACTION_VIEW => 'Voir',
            self::ACTION_ADD => 'Ajouter',
            self::ACTION_EDIT => 'Modifier',
            self::ACTION_DELETE => 'Supprimer',
            self::ACTION_EXPORT => 'Exporter'
        ];
    }
}