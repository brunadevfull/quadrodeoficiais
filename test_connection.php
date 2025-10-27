<?php
/**
 * Script de teste de conexão com o banco de dados marinha_papem
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== TESTE DE CONEXÃO - BANCO MARINHA_PAPEM ===\n\n";

// Carrega variáveis de ambiente
require_once __DIR__ . '/includes/load_env.php';

echo "1. Variáveis de integração...\n";
$databaseUrl = getenv('DATABASE_URL');
$externalApiBase = getenv('EXTERNAL_API_BASE_URL');
$dutyAssignmentsApi = getenv('DUTY_ASSIGNMENTS_API_URL');
$personnelApi = getenv('MILITARY_PERSONNEL_API_URL');

if ($externalApiBase) {
    echo "   ✓ EXTERNAL_API_BASE_URL: " . $externalApiBase . "\n";
} else {
    echo "   ℹ EXTERNAL_API_BASE_URL não definida (será usada URL completa por endpoint ou conexão direta ao banco).\n";
}

if ($dutyAssignmentsApi) {
    echo "   ✓ DUTY_ASSIGNMENTS_API_URL: " . $dutyAssignmentsApi . "\n";
} else {
    echo "   ℹ DUTY_ASSIGNMENTS_API_URL não definida.\n";
}

if ($personnelApi) {
    echo "   ✓ MILITARY_PERSONNEL_API_URL: " . $personnelApi . "\n";
} else {
    echo "   ℹ MILITARY_PERSONNEL_API_URL não definida.\n";
}

if ($databaseUrl) {
    echo "   ✓ DATABASE_URL carregada: " . $databaseUrl . "\n\n";
} else {
    echo "   ℹ DATABASE_URL não encontrada (repositórios usarão apenas APIs externas).\n\n";
}

// Testa DutyAssignmentsRepository
echo "2. Testando DutyAssignmentsRepository...\n";
try {
    require_once __DIR__ . '/includes/DutyAssignmentsRepository.php';
    $dutyRepo = new DutyAssignmentsRepository();
    echo "   ✓ Repositório instanciado com sucesso\n";

    echo "   Tentando buscar oficiais de serviço atuais...\n";
    $assignment = $dutyRepo->getCurrentAssignment();

    if ($assignment === null) {
        echo "   ℹ Nenhum oficial de serviço cadastrado\n";
    } else {
        echo "   ✓ Oficial encontrado:\n";
        echo "      - Oficial: " . ($assignment['officerDisplayName'] ?? 'N/A') . "\n";
        echo "      - Mestre: " . ($assignment['masterDisplayName'] ?? 'N/A') . "\n";
        echo "      - Válido desde: " . ($assignment['validFrom'] ?? 'N/A') . "\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "   ✗ ERRO: " . $e->getMessage() . "\n";
    echo "   Detalhes: " . $e->getTraceAsString() . "\n\n";
}

// Testa MilitaryPersonnelRepository
echo "3. Testando MilitaryPersonnelRepository...\n";
try {
    require_once __DIR__ . '/includes/MilitaryPersonnelRepository.php';
    $personnelRepo = new MilitaryPersonnelRepository();
    echo "   ✓ Repositório instanciado com sucesso\n";

    echo "   Tentando buscar oficiais...\n";
    $officers = $personnelRepo->getPersonnelOptions('officer');
    echo "   ✓ Encontrados " . count($officers) . " oficiais\n";

    if (count($officers) > 0) {
        echo "   Exemplo: " . $officers[0]['display'] . "\n";
    }

    echo "   Tentando buscar mestres...\n";
    $masters = $personnelRepo->getPersonnelOptions('master');
    echo "   ✓ Encontrados " . count($masters) . " mestres\n";

    if (count($masters) > 0) {
        echo "   Exemplo: " . $masters[0]['display'] . "\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "   ✗ ERRO: " . $e->getMessage() . "\n";
    echo "   Detalhes: " . $e->getTraceAsString() . "\n\n";
}

echo "=== TESTE CONCLUÍDO ===\n";
