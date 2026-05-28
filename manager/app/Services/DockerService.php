<?php

namespace App\Services;

class DockerService
{
    private string $mysqlHost;
    private string $mysqlPassword;

    public function __construct()
    {
        $this->mysqlHost = env('DB_HOST', '127.0.0.1');
        $this->mysqlPassword = env('DB_PASSWORD', '');
    }

    /**
     * Retorna o status dos serviços do sistema
     */
    public function getContainersStatus(): array
    {
        $services = ['nginx', 'php8.4-fpm', 'mysql'];
        $containers = [];

        foreach ($services as $svc) {
            $active = trim(shell_exec("systemctl is-active {$svc} 2>/dev/null") ?? 'inactive');
            $containers[] = [
                'name'    => $svc,
                'status'  => $active,
                'health'  => '',
                'ports'   => '',
                'running' => $active === 'active',
            ];
        }

        return $containers;
    }

    /**
     * Verifica se o nginx está rodando
     */
    public function isRunning(): bool
    {
        $status = trim(shell_exec('systemctl is-active nginx 2>/dev/null') ?? '');
        return $status === 'active';
    }

    /**
     * Executa query diretamente no MySQL
     */
    public function mysqlQuery(string $query): ?string
    {
        $escaped = escapeshellarg($query);
        $pass = $this->mysqlPassword;
        return shell_exec("MYSQL_PWD={$pass} mysql -uroot -h{$this->mysqlHost} --batch -e {$escaped} 2>/dev/null");
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
