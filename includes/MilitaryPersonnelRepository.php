<?php

require_once __DIR__ . '/load_env.php';
require_once __DIR__ . '/MilitaryFormatter.php';
require_once __DIR__ . '/ExternalApiClient.php';

class MilitaryPersonnelRepository
{
    private ?PDO $pdo = null;
    private ?ExternalApiClient $apiClient = null;
    private string $personnelEndpoint = '/api/military-personnel';

    public function __construct(?PDO $pdo = null, ?ExternalApiClient $apiClient = null, ?string $personnelEndpoint = null)
    {
        if ($personnelEndpoint !== null) {
            $this->personnelEndpoint = $personnelEndpoint;
        }

        $this->initializeApiClient($apiClient);

        if ($this->apiClient === null) {
            $this->pdo = $pdo ?? $this->createConnectionFromEnv();
        } else {
            $this->pdo = $pdo; // mantido apenas para compatibilidade com testes legados
        }
    }

    /**
     * @throws RuntimeException
     */
    public function getPersonnelOptions(string $type): array
    {
        $type = trim($type);

        if ($type === '') {
            throw new InvalidArgumentException('Tipo de militar inválido informado.');
        }

        if ($this->apiClient !== null) {
            $rows = $this->fetchFromApi($type);
        } else {
            $rows = $this->fetchFromDatabase($type);
        }

        $options = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $normalized = $this->normalizePersonnel($row, $type);

            if ($normalized['display'] === '') {
                continue;
            }

            $optionKey = $this->buildOptionKey($normalized);

            if (isset($options[$optionKey])) {
                continue;
            }

            $options[$optionKey] = [
                'id' => $normalized['id'],
                'value' => $normalized['name'],
                'name' => $normalized['name'],
                'rank' => $normalized['rank_display'],
                'short_rank' => $normalized['rank'],
                'type' => $normalized['type'],
                'specialty' => $normalized['specialty'],
                'display' => $normalized['display'],
            ];
        }

        $options = array_values($options);

        usort($options, static function (array $left, array $right): int {
            return strcmp($left['display'], $right['display']);
        });

        return $options;
    }

    /**
     * @throws RuntimeException
     */
    private function fetchFromApi(string $type): array
    {
        if ($this->apiClient === null) {
            return [];
        }

        $response = $this->apiClient->get($this->personnelEndpoint, ['type' => $type]);

        if (isset($response['success']) && $response['success'] === false) {
            $message = isset($response['error']) ? (string)$response['error'] : 'API de militares retornou erro.';
            throw new RuntimeException($message);
        }

        if (isset($response['error']) && !isset($response['success'])) {
            throw new RuntimeException((string)$response['error']);
        }

        $records = $this->extractRecordsFromApi($response);

        if (!is_array($records)) {
            throw new RuntimeException('Resposta da API de militares não contém uma lista de registros.');
        }

        return $records;
    }

    /**
     * @throws RuntimeException
     */
    private function fetchFromDatabase(string $type): array
    {
        if (!$this->pdo instanceof PDO) {
            throw new RuntimeException('Conexão com banco de militares não inicializada.');
        }

        try {
            $statement = $this->pdo->prepare(
                'SELECT * FROM military_personnel WHERE type = :type'
            );
            $statement->execute([':type' => $type]);
            $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $exception) {
            throw new RuntimeException('Falha ao consultar militares no banco de dados.', 0, $exception);
        }

        return $rows;
    }

    /**
     * @throws RuntimeException
     */
    private function createConnectionFromEnv(): PDO
    {
        $databaseUrl = getenv('DATABASE_URL') ?: 'postgresql://postgres:suasenha123@localhost:5432/marinha_papem';
        $parts = parse_url($databaseUrl);

        if ($parts === false || !isset($parts['scheme'])) {
            throw new RuntimeException('DATABASE_URL inválida ou ausente.');
        }

        $scheme = strtolower($parts['scheme']);

        if ($scheme !== 'postgresql' && $scheme !== 'postgres') {
            throw new RuntimeException('DATABASE_URL deve utilizar o driver PostgreSQL.');
        }

        $host = $parts['host'] ?? 'localhost';
        $port = (int)($parts['port'] ?? 5432);
        $user = isset($parts['user']) ? urldecode($parts['user']) : '';
        $password = isset($parts['pass']) ? urldecode($parts['pass']) : '';
        $database = isset($parts['path']) ? ltrim($parts['path'], '/') : '';

        if ($database === '') {
            throw new RuntimeException('DATABASE_URL não contém o nome do banco de dados.');
        }

        $dsn = sprintf('pgsql:host=%s;port=%d;dbname=%s', $host, $port, $database);

        try {
            $pdo = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $exception) {
            throw new RuntimeException('Não foi possível conectar ao banco de militares.', 0, $exception);
        }

        return $pdo;
    }

    private function normalizePersonnel(array $row, string $requestedType): array
    {
        $name = $row['name'] ?? $row['nome'] ?? $row['fullName'] ?? $row['full_name'] ?? '';
        $rank = $row['rank'] ?? $row['posto'] ?? $row['descricao'] ?? $row['patente'] ?? '';
        $type = $row['type'] ?? $requestedType;
        $specialty = $row['specialty'] ?? $row['especialidade'] ?? '';
        $identifier = $row['id'] ?? null;

        $formattedName = MilitaryFormatter::formatName((string)$name);
        $formattedRank = MilitaryFormatter::formatRank((string)$rank);
        $formattedSpecialty = MilitaryFormatter::formatSpecialty((string)$specialty);

        return [
            'id' => $identifier !== null ? (int)$identifier : null,
            'name' => $formattedName,
            'rank' => $formattedRank,
            'rank_display' => MilitaryFormatter::buildRankWithSpecialty($formattedRank, $formattedSpecialty),
            'type' => trim((string)$type),
            'specialty' => $formattedSpecialty,
            'display' => MilitaryFormatter::buildDisplayName($formattedRank, $formattedName, $formattedSpecialty),
        ];
    }

    private function buildOptionKey(array $normalized): string
    {
        $keyParts = [
            $normalized['id'] ?? '',
            $normalized['name'] ?? '',
            $normalized['rank_display'] ?? ($normalized['rank'] ?? ''),
            $normalized['type'] ?? '',
        ];

        $normalizedParts = array_map(static function ($part) {
            $part = (string)$part;

            if (function_exists('mb_strtolower')) {
                return mb_strtolower($part, 'UTF-8');
            }

            return strtolower($part);
        }, $keyParts);

        return implode('|', $normalizedParts);
    }

    private function initializeApiClient(?ExternalApiClient $client): void
    {
        if ($client instanceof ExternalApiClient) {
            $this->apiClient = $client;
            return;
        }

        $endpointFromEnv = getenv('MILITARY_PERSONNEL_API_URL') ?: '';
        $baseUrlFromEnv = getenv('EXTERNAL_API_BASE_URL') ?: '';

        if ($endpointFromEnv !== '') {
            if (preg_match('#^https?://#i', $endpointFromEnv)) {
                $this->apiClient = $this->createClientFromAbsoluteUrl($endpointFromEnv);
                return;
            }

            $this->personnelEndpoint = $endpointFromEnv;
        }

        if ($baseUrlFromEnv !== '') {
            $this->apiClient = new ExternalApiClient($baseUrlFromEnv);
        }
    }

    /**
     * @throws RuntimeException
     */
    private function createClientFromAbsoluteUrl(string $url): ExternalApiClient
    {
        $parts = parse_url($url);

        if ($parts === false || !isset($parts['scheme'], $parts['host'])) {
            throw new RuntimeException('URL de API externa inválida: ' . $url);
        }

        $base = $parts['scheme'] . '://' . $parts['host'];

        if (isset($parts['port'])) {
            $base .= ':' . $parts['port'];
        }

        $path = $parts['path'] ?? '/';
        $query = isset($parts['query']) && $parts['query'] !== '' ? '?' . $parts['query'] : '';

        $this->personnelEndpoint = ($path === '' ? '/' : $path) . $query;

        return new ExternalApiClient($base);
    }

    private function extractRecordsFromApi(array $response): array
    {
        foreach (['data', 'items', 'results', 'personnel', 'records'] as $key) {
            if (isset($response[$key]) && is_array($response[$key])) {
                return $response[$key];
            }
        }

        if ($this->isList($response)) {
            return $response;
        }

        if (empty($response)) {
            return [];
        }

        if (isset($response['success']) && $response['success'] === true) {
            return [];
        }

        if (isset($response['error'])) {
            throw new RuntimeException((string)$response['error']);
        }

        throw new RuntimeException('Formato da resposta da API de militares é desconhecido.');
    }

    private function isList(array $value): bool
    {
        if (function_exists('array_is_list')) {
            return array_is_list($value);
        }

        return array_keys($value) === range(0, count($value) - 1);
    }
}
