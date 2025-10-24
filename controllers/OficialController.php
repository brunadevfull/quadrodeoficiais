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

        // Obtém os oficiais locais - usa APENAS banco local, não o externo
        $oficiais = Oficial::all();

        // Gera opções de oficiais e mestres a partir do banco local
        $officerOptions = $this->buildFallbackOptions($oficiais, 'officer');
        $masterOptions = $this->buildFallbackOptions($oficiais, 'master');

        // Inclui a view e passa as variáveis necessárias
        include 'views/oficiais/index.php';
    }

    private function buildFallbackOptions(array $oficiais, string $type): array
    {
        $options = [];

        foreach ($oficiais as $oficial) {
            $postoId = strtoupper((string)($oficial['posto_id'] ?? ''));

            if ($type === 'officer' && strpos($postoId, 'T') === false) {
                continue;
            }

            if ($type === 'master' && strpos($postoId, 'SG') === false) {
                continue;
            }

            $name = $oficial['nome'] ?? '';

            if (empty($name)) {
                continue;
            }

            $rank = $oficial['descricao'] ?? '';

            $options[] = [
                'value' => $name,
                'name' => $name,
                'rank' => $rank,
                'type' => $type,
            ];
        }

        return $options;
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

