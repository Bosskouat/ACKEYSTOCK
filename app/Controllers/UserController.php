// Dans la méthode index()

public function index() {
    // Cette ligne est cruciale pour bloquer l'accès
    AuthMiddleware::requirePermission(Permissions::MODULE_UTILISATEURS, Permissions::ACTION_VIEW);
    
    // ... reste du code
}