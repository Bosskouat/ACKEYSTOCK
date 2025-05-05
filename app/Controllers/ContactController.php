<?php
namespace App\Controllers;

use App\Models\MessageContact;
use App\Core\View;

class ContactController {
    private $model;

    public function __construct($db) {
        $this->model = new MessageContact($db);
    }

    public function index() {
        $messages = $this->model->getAll();
        View::render('contact/index.twig', ['messages' => $messages]);
    }

    public function showForm() {
        View::render('contact/form.twig');
    }

    public function send($post) {
        $this->model->insert($post);
        View::render('contact/form.twig', ['success' => 'Message envoyé avec succès !']);
    }
}
