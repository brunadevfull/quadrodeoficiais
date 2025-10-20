<?php

class MilitaryPersonnelRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        if ($pdo !== null) {
            $this->pdo = $pdo;
            return;
        }

        $this->pdo = $this->createConnectionFromEnv();
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

        try {
            $statement = $this->pdo->prepare(
                'SELECT * FROM military_personnel WHERE type = :type'
            );
            $statement->execute([':type' => $type]);
            $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $exception) {
            throw new RuntimeException('Falha ao consultar militares no banco de dados.', 0, $exception);
        }

        $options = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $normalized = $this->normalizePersonnel($row, $type);

            if ($normalized['name'] === '') {
                continue;
            }

            $options[] = [
                'value' => $normalized['name'],
                'name' => $normalized['name'],
                'rank' => $normalized['rank'],
                'type' => $normalized['type'],
                'specialty' => $normalized['specialty'],
            ];
        }

        usort($options, static function (array $left, array $right): int {
            return strcmp($left['name'], $right['name']);
        });

        return $options;
    }

    /**
     * @throws RuntimeException
     */
    private function createConnectionFromEnv(): PDO
    {
        $databaseUrl = getenv('DATABASE_URL') ?: 'postgresql://postgres:postgres123@localhost:5432/marinha_papem';
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
        $specialty = $row['specialty'] ?? $row['especialidade'] ?? $row['especialidade_sigla'] ?? '';
        $type = $row['type'] ?? $requestedType;

        $normalizedSpecialty = $this->normalizeToUppercase($specialty);
        $formattedRank = $this->formatRankWithSpecialty($rank, $normalizedSpecialty);

        return [
            'name' => trim((string)$name),
            'rank' => $formattedRank,
            'type' => (string)$type,
            'specialty' => $normalizedSpecialty,
        ];
    }

    private function formatRankWithSpecialty($rank, string $normalizedSpecialty): string
    {
        $normalizedRank = $this->normalizeToUppercase($rank);

        if ($normalizedSpecialty === '') {
            return $normalizedRank;
        }

        if ($normalizedRank === '') {
            return $normalizedSpecialty;
        }

        if (mb_stripos($normalizedRank, $normalizedSpecialty, 0, 'UTF-8') !== false) {
            return $normalizedRank;
        }

        return trim($normalizedRank . ' ' . $normalizedSpecialty);
    }

    private function normalizeToUppercase($value): string
    {
        if ($value === null) {
            return '';
        }

        $trimmed = trim((string)$value);

        if ($trimmed === '') {
            return '';
        }

        return mb_strtoupper($trimmed, 'UTF-8');
    }
}
