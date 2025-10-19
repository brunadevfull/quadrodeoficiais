<?php
class OficialController {
    public function index() {
        include 'models/Oficial.php';

        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // Verifica se o usuário está logado
        $is_logged_in = isset($_SESSION['user_id']);
        $body_class = $is_logged_in ? 'logged-in' : 'logged-out';

        // Obtém os oficiais
        $oficiais = Oficial::all();

        // Inclui a view e passa as variáveis necessárias
        include 'views/oficiais/index.php';
    }

    public function add() {
        include '../models/Oficial.php';
        Oficial::add($_POST);
        header('Location: ../index.php');
    }

    public function edit() {
        include '../models/Oficial.php';
        Oficial::edit($_POST);
        header('Location: ../index.php');
    }

    public function remove() {
        include '../models/Oficial.php';
        Oficial::remove($_POST['id']);
        header('Location: ../index.php');
    }
}
?>

