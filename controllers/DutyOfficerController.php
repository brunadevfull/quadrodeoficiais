<?php
class DutyOfficerController {
    public function index() {
        include 'models/Oficial.php';
        require_once 'includes/MilitaryPersonnelRepository.php';

        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // Verifica se o usuário está logado
        $is_logged_in = isset($_SESSION['user_id']);
        if (!$is_logged_in) {
            header('Location: views/login.php');
            exit();
        }
        
        $body_class = 'logged-in';

        $personnelRepository = new MilitaryPersonnelRepository();
        $personnelErrors = [];

        $officerOptions = [];
        $masterOptions = [];

        try {
            $officerOptions = $personnelRepository->getPersonnelOptions('officer');
        } catch (Exception $exception) {
            $personnelErrors[] = $exception->getMessage();
        }

        try {
            $masterOptions = $personnelRepository->getPersonnelOptions('master');
        } catch (Exception $exception) {
            $personnelErrors[] = $exception->getMessage();
        }

        $needsOfficerFallback = empty($officerOptions);
        $needsMasterFallback = empty($masterOptions);

        if ($needsOfficerFallback || $needsMasterFallback) {
            $oficiais = Oficial::all();

            if ($needsOfficerFallback) {
                $officerOptions = $this->buildFallbackOptions($oficiais, 'officer');
            }

            if ($needsMasterFallback) {
                $masterOptions = $this->buildFallbackOptions($oficiais, 'master');
            }

            if (empty($personnelErrors)) {
                $personnelErrors[] = 'Nenhum registro encontrado no banco de militares. Exibindo dados locais.';
            }
        }

        // Inclui a view e passa as variáveis necessárias
        include 'views/duty_officers/index.php';
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

    public function update() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // Verifica se o usuário está logado
        if (!isset($_SESSION['user_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Usuário não autenticado']);
            exit();
        }

        // Recebe dados do formulário
        $officerName = $_POST['officerName'] ?? '';
        $masterName = $_POST['masterName'] ?? '';

        if (empty($officerName) && empty($masterName)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Pelo menos um oficial deve ser fornecido']);
            exit();
        }

        // A função updateDutyOfficers será implementada no JavaScript para comunicação com a API Node.js
        // Aqui, apenas retornamos uma resposta para o AJAX
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Solicitação recebida. O JavaScript irá processar a atualização.',
            'data' => [
                'officerName' => $officerName,
                'masterName' => $masterName
            ]
        ]);
        exit();
    }

    public function loadCurrentDutyOfficers() {
        header('Content-Type: application/json');
        // Esta função apenas inicia o processo de busca dos oficiais atuais
        // A lógica real será implementada no JavaScript
        echo json_encode(['success' => true]);
        exit();
    }
}
?>