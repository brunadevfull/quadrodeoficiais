<?php

class NodeApiClient
{
    private string $baseUrl;
    private ?string $authType;
    private ?string $authToken;
    private ?string $username;
    private ?string $password;
    private int $timeout;

    public function __construct(array $config = [])
    {
        $this->baseUrl = rtrim($config['base_url'] ?? 'http://10.1.129.46:5001', '/');
        $this->timeout = (int)($config['timeout'] ?? 10);

        $authConfig = $config['auth'] ?? [];
        $this->authType = isset($authConfig['type']) ? strtolower((string)$authConfig['type']) : null;
        $this->authToken = $authConfig['token'] ?? null;
        $this->username = $authConfig['username'] ?? null;
        $this->password = $authConfig['password'] ?? null;

        if ($this->authType === null && !empty($this->authToken)) {
            $this->authType = 'bearer';
        }
    }

    /**
     * @throws RuntimeException
     */
    public function getPersonnelOptions(string $type): array
    {
        $payload = $this->request('GET', '/api/military_personnel', ['type' => $type]);

        if (!is_array($payload)) {
            throw new RuntimeException('Resposta inesperada da API de militares.');
        }

        $items = $payload['data'] ?? $payload;

        if (!is_array($items)) {
            throw new RuntimeException('Formato inválido retornado pela API de militares.');
        }

        $options = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $normalized = $this->normalizePersonnel($item, $type);

            if (empty($normalized['name'])) {
                continue;
            }

            $options[] = [
                'value' => $normalized['name'],
                'name' => $normalized['name'],
                'rank' => $normalized['rank'],
                'type' => $normalized['type'],
            ];
        }

        return $options;
    }

    /**
     * @throws RuntimeException
     */
    private function request(string $method, string $endpoint, array $query = []): array
    {
        $url = $this->baseUrl . $endpoint;

        if (!empty($query)) {
            $url .= ((strpos($url, '?') !== false) ? '&' : '?') . http_build_query($query);
        }

        $handle = curl_init($url);

        if ($handle === false) {
            throw new RuntimeException('Não foi possível iniciar a comunicação com a API de militares.');
        }

        $headers = ['Accept: application/json'];
        $authHeader = $this->resolveAuthHeader();

        if ($authHeader !== null) {
            $headers[] = $authHeader;
        }

        curl_setopt_array($handle, [
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => max(2, (int)floor($this->timeout / 2)),
            CURLOPT_HTTPHEADER => $headers,
        ]);

        $response = curl_exec($handle);

        if ($response === false) {
            $errorMessage = curl_error($handle);
            curl_close($handle);
            throw new RuntimeException('Falha de rede ao acessar a API de militares: ' . $errorMessage);
        }

        $statusCode = (int)curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);

        $decoded = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Não foi possível interpretar a resposta da API de militares.');
        }

        if ($statusCode >= 400) {
            $message = $decoded['message'] ?? $decoded['error'] ?? 'Resposta de erro da API de militares.';

            if ($statusCode === 401 || $statusCode === 403) {
                $message = 'Falha de autenticação ao acessar a API de militares.';
            }

            throw new RuntimeException($message);
        }

        return $decoded;
    }

    private function resolveAuthHeader(): ?string
    {
        if ($this->authType === 'bearer' && !empty($this->authToken)) {
            return 'Authorization: Bearer ' . $this->authToken;
        }

        if ($this->authType === 'basic' && $this->username !== null && $this->password !== null) {
            $credentials = base64_encode($this->username . ':' . $this->password);
            return 'Authorization: Basic ' . $credentials;
        }

        return null;
    }

    private function normalizePersonnel(array $item, string $requestedType): array
    {
        $name = $item['name'] ?? $item['nome'] ?? $item['fullName'] ?? '';
        $rank = $item['rank'] ?? $item['posto'] ?? $item['descricao'] ?? $item['patente'] ?? '';
        $type = $item['type'] ?? $requestedType;

        return [
            'name' => trim((string)$name),
            'rank' => trim((string)$rank),
            'type' => (string)$type,
        ];
    }
}
