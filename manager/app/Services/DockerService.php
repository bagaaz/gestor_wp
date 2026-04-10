<?php

namespace App\Services;

class DockerService
{
    /**
     * Retorna o status dos containers Docker
     */
    public function getContainersStatus(): array
    {
        $output = shell_exec('docker compose ps --format json 2>/dev/null') ?? '';
        $containers = [];

        foreach (explode("\n", trim($output)) as $line) {
            if (empty($line)) continue;
            $data = json_decode($line, true);
            if ($data) {
                $containers[] = [
                    'name' => $data['Name'] ?? '',
                    'status' => $data['State'] ?? 'unknown',
                    'health' => $data['Health'] ?? '',
                    'ports' => $data['Ports'] ?? '',
                    'running' => ($data['State'] ?? '') === 'running',
                ];
            }
        }

        return $containers;
    }

    /**
     * Verifica se os serviços estão rodando
     */
    public function isRunning(): bool
    {
        $containers = $this->getContainersStatus();
        foreach ($containers as $c) {
            if (str_contains($c['name'], 'nginx') && $c['running']) {
                return true;
            }
        }
        return false;
    }

    /**
     * Executa comando no container MySQL
     */
    public function mysqlQuery(string $query): ?string
    {
        $escaped = escapeshellarg($query);
        return shell_exec("docker compose exec -T mysql mysql -uroot -proot -e {$escaped} 2>/dev/null");
    }

    /**
     * Retorna o tamanho de um banco de dados
     */
    public function getDatabaseSize(string $dbName): int
    {
        $query = "SELECT SUM(data_length + index_length) as size FROM information_schema.TABLES WHERE table_schema = '{$dbName}'";
        $result = $this->mysqlQuery($query);

        if ($result && preg_match('/(\d+)/', $result, $matches)) {
            return (int) $matches[1];
        }

        return 0;
    }

    /**
     * Lista todos os bancos de dados WordPress
     */
    public function listDatabases(): array
    {
        $result = $this->mysqlQuery("SHOW DATABASES LIKE 'wp_%'");
        $databases = [];

        if ($result) {
            foreach (explode("\n", trim($result)) as $line) {
                $line = trim($line);
                if ($line && $line !== 'Database (wp_%)' && !str_starts_with($line, '+')) {
                    $databases[] = $line;
                }
            }
        }

        return $databases;
    }
}
