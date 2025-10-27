<?php

require_once __DIR__ . '/load_env.php';
require_once __DIR__ . '/MilitaryFormatter.php';
require_once __DIR__ . '/ExternalApiClient.php';

class DutyAssignmentsRepository
{
    private ?PDO $pdo = null;
    private ?ExternalApiClient $apiClient = null;
    private string $assignmentsEndpoint = '/api/duty-officers';

    public function __construct(?PDO $pdo = null, ?ExternalApiClient $apiClient = null, ?string $assignmentsEndpoint = null)
    {
        if ($assignmentsEndpoint !== null) {
            $this->assignmentsEndpoint = $assignmentsEndpoint;
        }

        $this->initializeApiClient($apiClient);

        if ($this->apiClient === null) {
            $this->pdo = $pdo ?? $this->createConnectionFromEnv();
        } else {
            $this->pdo = $pdo; // compatibilidade para testes que injetam PDO
        }
    }

    /**
     * @throws RuntimeException
     */
    public function getCurrentAssignment(): ?array
    {
        if ($this->apiClient !== null) {
            return $this->fetchCurrentAssignmentFromApi();
        }

        try {
            $statement = $this->pdo->query(
                'SELECT id, officer_name, officer_rank, master_name, master_rank, valid_from, updated_at
                 FROM duty_assignments
                 ORDER BY valid_from DESC, updated_at DESC
                 LIMIT 1'
            );

            $row = $statement->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $exception) {
            throw new RuntimeException('Falha ao consultar oficiais de serviço.', 0, $exception);
        }

        if (!is_array($row)) {
            return null;
        }

        return $this->normalizeAssignment($row);
    }

    /**
     * @throws RuntimeException
     */
    public function createAssignment(array $data): array
    {
        $officerName = $this->sanitizeNullableString($data['officerName'] ?? null);
        $officerRank = $this->sanitizeNullableString($data['officerRank'] ?? null);
        $masterName = $this->sanitizeNullableString($data['masterName'] ?? null);
        $masterRank = $this->sanitizeNullableString($data['masterRank'] ?? null);

        $officerName = $officerName !== null ? MilitaryFormatter::formatName($officerName) : null;
        $officerRank = $officerRank !== null ? MilitaryFormatter::formatRank($officerRank) : null;
        $officerRank = $officerRank === '' ? null : $officerRank;

        $masterName = $masterName !== null ? MilitaryFormatter::formatName($masterName) : null;
        $masterRank = $masterRank !== null ? MilitaryFormatter::formatRank($masterRank) : null;
        $masterRank = $masterRank === '' ? null : $masterRank;
        $validFrom = $data['validFrom'] ?? null;

        if ($validFrom instanceof DateTimeInterface) {
            $validFromString = $validFrom->format('Y-m-d H:i:s');
        } elseif (is_string($validFrom) && trim($validFrom) !== '') {
            $validFromString = $this->createDateTimeFromString($validFrom)->format('Y-m-d H:i:s');
        } else {
            $validFromString = (new DateTimeImmutable('now'))
                ->setTimezone(new DateTimeZone('UTC'))
                ->format('Y-m-d H:i:s');
        }

        if ($this->apiClient !== null) {
            return $this->createAssignmentViaApi([
                'officerName' => $officerName,
                'officerRank' => $officerRank,
                'masterName' => $masterName,
                'masterRank' => $masterRank,
                'validFrom' => $validFromString,
            ]);
        }

        try {
            $statement = $this->pdo->prepare(
                'INSERT INTO duty_assignments (officer_name, officer_rank, master_name, master_rank, valid_from)
                 VALUES (:officer_name, :officer_rank, :master_name, :master_rank, :valid_from)
                 RETURNING id, officer_name, officer_rank, master_name, master_rank, valid_from, updated_at'
            );

            $statement->execute([
                ':officer_name' => $officerName,
                ':officer_rank' => $officerRank,
                ':master_name' => $masterName,
                ':master_rank' => $masterRank,
                ':valid_from' => $validFromString,
            ]);

            $row = $statement->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $exception) {
            throw new RuntimeException('Falha ao salvar oficiais de serviço.', 0, $exception);
        }

        if (!is_array($row)) {
            throw new RuntimeException('Não foi possível recuperar o registro salvo.');
        }

        return $this->normalizeAssignment($row);
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
            throw new RuntimeException('Não foi possível conectar ao banco de oficiais de serviço.', 0, $exception);
        }

        return $pdo;
    }

    private function normalizeAssignment(array $row): array
    {
        $validFrom = $this->formatDateTime($row['valid_from'] ?? $row['validFrom'] ?? null);
        $updatedAt = $this->formatDateTime($row['updated_at'] ?? $row['updatedAt'] ?? null);

        $officerName = $this->sanitizeNullableString($row['officer_name'] ?? $row['officerName'] ?? null);
        $officerRank = $this->sanitizeNullableString($row['officer_rank'] ?? $row['officerRank'] ?? null);
        $masterName = $this->sanitizeNullableString($row['master_name'] ?? $row['masterName'] ?? null);
        $masterRank = $this->sanitizeNullableString($row['master_rank'] ?? $row['masterRank'] ?? null);

        $officerName = $officerName !== null ? MilitaryFormatter::formatName($officerName) : null;
        $officerRank = $officerRank !== null ? MilitaryFormatter::formatRank($officerRank) : null;
        $masterName = $masterName !== null ? MilitaryFormatter::formatName($masterName) : null;
        $masterRank = $masterRank !== null ? MilitaryFormatter::formatRank($masterRank) : null;

        return [
            'id' => isset($row['id']) && $row['id'] !== null ? (int)$row['id'] : null,
            'officerName' => $officerName,
            'officerRank' => $officerRank,
            'officerDisplayName' => $officerName !== null || $officerRank !== null
                ? MilitaryFormatter::buildDisplayName($officerRank, $officerName)
                : null,
            'masterName' => $masterName,
            'masterRank' => $masterRank,
            'masterDisplayName' => $masterName !== null || $masterRank !== null
                ? MilitaryFormatter::buildDisplayName($masterRank, $masterName)
                : null,
            'validFrom' => $validFrom,
            'updatedAt' => $updatedAt,
        ];
    }

    private function sanitizeNullableString($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string)$value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function formatDateTime($value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return (new DateTimeImmutable($value->format(DateTimeInterface::ATOM)))
                ->setTimezone(new DateTimeZone('UTC'))
                ->format(DateTimeInterface::ATOM);
        }

        if ($value === null) {
            return null;
        }

        $value = trim((string)$value);

        if ($value === '') {
            return null;
        }

        try {
            return $this->createDateTimeFromString($value)
                ->setTimezone(new DateTimeZone('UTC'))
                ->format(DateTimeInterface::ATOM);
        } catch (Exception $exception) {
            return null;
        }
    }

    /**
     * @throws RuntimeException
     */
    private function createDateTimeFromString(string $value): DateTimeImmutable
    {
        try {
            return new DateTimeImmutable($value);
        } catch (Exception $exception) {
            throw new RuntimeException('Data/hora inválida informada.', 0, $exception);
        }
    }

    private function initializeApiClient(?ExternalApiClient $client): void
    {
        if ($client instanceof ExternalApiClient) {
            $this->apiClient = $client;
            return;
        }

        $endpointFromEnv = getenv('DUTY_ASSIGNMENTS_API_URL') ?: '';
        $baseUrlFromEnv = getenv('EXTERNAL_API_BASE_URL') ?: '';

        if ($endpointFromEnv !== '') {
            if (preg_match('#^https?://#i', $endpointFromEnv)) {
                $this->apiClient = $this->createClientFromAbsoluteUrl($endpointFromEnv);
                return;
            }

            $this->assignmentsEndpoint = $endpointFromEnv;
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

        $this->assignmentsEndpoint = ($path === '' ? '/' : $path) . $query;

        return new ExternalApiClient($base);
    }

    /**
     * @throws RuntimeException
     */
    private function fetchCurrentAssignmentFromApi(): ?array
    {
        if ($this->apiClient === null) {
            return null;
        }

        $response = $this->apiClient->get($this->assignmentsEndpoint);

        if (isset($response['success']) && $response['success'] === false) {
            $message = isset($response['error']) ? (string)$response['error'] : 'API de oficiais de serviço retornou erro.';
            throw new RuntimeException($message);
        }

        if (isset($response['error']) && !isset($response['success'])) {
            throw new RuntimeException((string)$response['error']);
        }

        $assignment = $this->extractAssignmentFromApi($response);

        if ($assignment === null) {
            return null;
        }

        return $this->normalizeAssignment($assignment);
    }

    /**
     * @throws RuntimeException
     */
    private function createAssignmentViaApi(array $payload): array
    {
        if ($this->apiClient === null) {
            throw new RuntimeException('Cliente HTTP não inicializado.');
        }

        $response = $this->apiClient->put($this->assignmentsEndpoint, $payload);

        if (isset($response['success']) && $response['success'] === false) {
            $message = isset($response['error']) ? (string)$response['error'] : 'Falha ao salvar oficiais de serviço na API externa.';
            throw new RuntimeException($message);
        }

        if (isset($response['error']) && !isset($response['success'])) {
            throw new RuntimeException((string)$response['error']);
        }

        $assignment = $this->extractAssignmentFromApi($response);

        if (!is_array($assignment)) {
            throw new RuntimeException('Resposta da API de oficiais de serviço é inválida.');
        }

        return $this->normalizeAssignment($assignment);
    }

    private function extractAssignmentFromApi(array $response): ?array
    {
        foreach (['officers', 'data', 'assignment', 'record'] as $key) {
            if (!array_key_exists($key, $response)) {
                continue;
            }

            if ($response[$key] === null) {
                return null;
            }

            if (!is_array($response[$key])) {
                throw new RuntimeException('Formato inesperado da resposta da API de oficiais de serviço.');
            }

            return $response[$key];
        }

        if (empty($response)) {
            return null;
        }

        if (isset($response['id']) || isset($response['officerName']) || isset($response['officer_name'])) {
            return $response;
        }

        if ($this->isList($response)) {
            return $response[0] ?? null;
        }

        return null;
    }

    private function isList(array $value): bool
    {
        if (function_exists('array_is_list')) {
            return array_is_list($value);
        }

        return array_keys($value) === range(0, count($value) - 1);
    }
}
