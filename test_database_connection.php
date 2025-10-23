<?php
/**
 * Script de teste para verificar conexão com banco de dados
 * e listar tabelas disponíveis
 */

echo "=== TESTE DE CONEXÃO COM BANCO DE DADOS ===\n\n";

// Verificar variável de ambiente
echo "1. Verificando DATABASE_URL...\n";
$databaseUrl = getenv('DATABASE_URL');
if ($databaseUrl) {
    echo "   ✓ DATABASE_URL definida: " . $databaseUrl . "\n\n";
} else {
    echo "   ✗ DATABASE_URL não definida, usando padrão\n";
    $databaseUrl = 'postgresql://postgres:postgres123@localhost:5432/marinha_papem';
    echo "   Padrão: " . $databaseUrl . "\n\n";
}

// Parse da URL
$parts = parse_url($databaseUrl);
echo "2. Configurações de conexão:\n";
echo "   Host: " . ($parts['host'] ?? 'localhost') . "\n";
echo "   Port: " . ($parts['port'] ?? 5432) . "\n";
echo "   User: " . ($parts['user'] ?? 'postgres') . "\n";
echo "   Database: " . (isset($parts['path']) ? ltrim($parts['path'], '/') : 'N/A') . "\n\n";

// Tentar conectar
echo "3. Testando conexão...\n";
$host = $parts['host'] ?? 'localhost';
$port = (int)($parts['port'] ?? 5432);
$user = isset($parts['user']) ? urldecode($parts['user']) : 'postgres';
$password = isset($parts['pass']) ? urldecode($parts['pass']) : '';
$database = isset($parts['path']) ? ltrim($parts['path'], '/') : '';

$dsn = sprintf('pgsql:host=%s;port=%d;dbname=%s', $host, $port, $database);

try {
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    echo "   ✓ Conexão estabelecida com sucesso!\n\n";
} catch (PDOException $e) {
    echo "   ✗ ERRO ao conectar: " . $e->getMessage() . "\n\n";
    echo "POSSÍVEIS CAUSAS:\n";
    echo "1. Banco de dados não existe\n";
    echo "2. Credenciais incorretas\n";
    echo "3. Servidor PostgreSQL não está rodando\n";
    echo "4. Porta ou host incorretos\n\n";
    echo "SOLUÇÕES:\n";
    echo "- Verifique se o banco 'marinha_papem' existe:\n";
    echo "  psql -U postgres -l | grep marinha_papem\n\n";
    echo "- Configure a variável DATABASE_URL corretamente:\n";
    echo "  export DATABASE_URL='postgresql://usuario:senha@host:porta/banco'\n\n";
    exit(1);
}

// Listar todas as tabelas
echo "4. Listando tabelas disponíveis no banco '$database'...\n";
try {
    $stmt = $pdo->query("
        SELECT tablename
        FROM pg_tables
        WHERE schemaname = 'public'
        ORDER BY tablename
    ");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($tables)) {
        echo "   ✗ Nenhuma tabela encontrada\n\n";
    } else {
        echo "   ✓ Encontradas " . count($tables) . " tabelas:\n";
        foreach ($tables as $table) {
            echo "      - " . $table . "\n";
        }
        echo "\n";
    }
} catch (PDOException $e) {
    echo "   ✗ Erro ao listar tabelas: " . $e->getMessage() . "\n\n";
}

// Verificar tabelas específicas necessárias
echo "5. Verificando tabelas necessárias para o sistema...\n";
$requiredTables = ['duty_assignments', 'military_personnel'];
$missingTables = [];

foreach ($requiredTables as $table) {
    try {
        $stmt = $pdo->query("SELECT EXISTS (
            SELECT FROM information_schema.tables
            WHERE table_schema = 'public'
            AND table_name = '$table'
        )");
        $exists = $stmt->fetchColumn();

        if ($exists === 't' || $exists === true || $exists === 1) {
            echo "   ✓ $table - EXISTE\n";

            // Contar registros
            $countStmt = $pdo->query("SELECT COUNT(*) FROM $table");
            $count = $countStmt->fetchColumn();
            echo "      └─ Registros: $count\n";
        } else {
            echo "   ✗ $table - NÃO EXISTE\n";
            $missingTables[] = $table;
        }
    } catch (PDOException $e) {
        echo "   ✗ $table - ERRO: " . $e->getMessage() . "\n";
        $missingTables[] = $table;
    }
}

echo "\n";

// Resumo final
echo "=== RESUMO ===\n";
if (empty($missingTables)) {
    echo "✓ TODAS as tabelas necessárias existem!\n";
    echo "\nO problema pode ser:\n";
    echo "1. Permissões de acesso às tabelas\n";
    echo "2. Dados vazios nas tabelas\n";
    echo "3. Problema na aplicação PHP\n";
} else {
    echo "✗ Tabelas FALTANDO: " . implode(', ', $missingTables) . "\n";
    echo "\nSOLUÇÃO:\n";
    echo "As tabelas existem em OUTRO banco de dados?\n";
    echo "Configure a DATABASE_URL correta:\n";
    echo "  export DATABASE_URL='postgresql://usuario:senha@host:porta/banco_correto'\n";
}

echo "\n=== FIM DO TESTE ===\n";
