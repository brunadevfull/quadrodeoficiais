<?php

class ExternalApiClient
{
    private string $baseUrl;
    private array $defaultHeaders;
    private int $timeout;

    public function __construct(string $baseUrl, array $defaultHeaders = [], int $timeout = 10)
    {
        if ($baseUrl === '') {
            throw new InvalidArgumentException('A URL base da API externa não pode ser vazia.');
        }

        $this->baseUrl = rtrim($baseUrl, '/');
        $this->defaultHeaders = $defaultHeaders;
        $this->timeout = $timeout;
    }

    /**
     * @throws RuntimeException
     */
    public function get(string $path, array $query = []): array
    {
        return $this->request('GET', $path, null, $query);
    }

    /**
     * @throws RuntimeException
     */
    public function put(string $path, array $payload = [], array $query = []): array
    {
        return $this->request('PUT', $path, $payload, $query);
    }

    /**
     * @throws RuntimeException
     */
    public function post(string $path, array $payload = [], array $query = []): array
    {
        return $this->request('POST', $path, $payload, $query);
    }

    /**
     * @throws RuntimeException
     */
    private function request(string $method, string $path, ?array $payload = null, array $query = []): array
    {
        $url = $this->buildUrl($path, $query);
        $handle = curl_init($url);

        if ($handle === false) {
            throw new RuntimeException('Não foi possível inicializar a requisição HTTP.');
        }

        $headers = $this->buildHeaders($payload !== null);

        curl_setopt_array($handle, [
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => $this->timeout,
            CURLOPT_TIMEOUT => $this->timeout,
        ]);

        if (!empty($headers)) {
            curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
        }

        if ($payload !== null) {
            $encoded = json_encode($payload);

            if ($encoded === false) {
                curl_close($handle);
                throw new RuntimeException('Falha ao codificar payload em JSON.');
            }

            curl_setopt($handle, CURLOPT_POSTFIELDS, $encoded);
        }

        $responseBody = curl_exec($handle);

        if ($responseBody === false) {
            $error = curl_error($handle);
            $errno = curl_errno($handle);
            curl_close($handle);

            throw new RuntimeException(sprintf('Erro ao comunicar com a API externa: %s', $error), $errno);
        }

        $status = (int)curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);

        if ($status >= 400) {
            throw new RuntimeException(sprintf('API externa respondeu com status %d (%s).', $status, $url));
        }

        if ($responseBody === '' || $responseBody === null) {
            return [];
        }

        $decoded = json_decode($responseBody, true);

        if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Resposta da API externa não é um JSON válido.');
        }

        return is_array($decoded) ? $decoded : [];
    }

    private function buildUrl(string $path, array $query): string
    {
        if (preg_match('#^https?://#i', $path)) {
            $url = $path;
        } else {
            $url = $this->baseUrl . '/' . ltrim($path, '/');
        }

        if (!empty($query)) {
            $separator = str_contains($url, '?') ? '&' : '?';
            $url .= $separator . http_build_query($query);
        }

        return $url;
    }

    private function buildHeaders(bool $hasPayload): array
    {
        $headers = $this->defaultHeaders;
        $hasAccept = false;
        $hasContentType = false;

        foreach ($headers as $header) {
            $normalized = strtolower($header);

            if (str_starts_with($normalized, 'accept:')) {
                $hasAccept = true;
            }

            if (str_starts_with($normalized, 'content-type:')) {
                $hasContentType = true;
            }
        }

        if (!$hasAccept) {
            $headers[] = 'Accept: application/json';
        }

        if ($hasPayload && !$hasContentType) {
            $headers[] = 'Content-Type: application/json';
        }

        return $headers;
    }
}
