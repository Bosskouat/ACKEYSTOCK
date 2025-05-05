<?php
namespace App\Controllers;

use App\Models\Notification;
use App\Core\View;

class NotificationController {
    private $model;

    public function __construct($db) {
        $this->model = new Notification($db);
    }

    public function index($utilisateur_id) {
        $notifs = $this->model->getAllByUser($utilisateur_id);
        View::render('notifications/index.twig', ['notifications' => $notifs]);
    }

    public function destroy($id) {
        $this->model->delete($id);
        header("Location: /notifications");
        exit;
    }
}
