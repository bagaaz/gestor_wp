<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class PhpConfigService
{
    private string $phpIniPath;
    private string $nginxConfPath;

    /** Diretivas editáveis e seus metadados */
    public const DIRECTIVES = [
        'memory_limit' => ['label' => 'Limite de Memória', 'type' => 'size', 'hint' => 'Ex: 128M, 256M, 512M, 1G'],
        'upload_max_filesize' => ['label' => 'Upload Máximo (arquivo)', 'type' => 'size', 'hint' => 'Tamanho máximo por arquivo'],
        'post_max_size' => ['label' => 'Post Máximo (requisição)', 'type' => 'size', 'hint' => 'Deve ser >= upload_max_filesize'],
        'max_file_uploads' => ['label' => 'Máx. Arquivos Simultâneos', 'type' => 'number', 'hint' => 'Quantidade de arquivos por upload'],
        'max_execution_time' => ['label' => 'Tempo Máx. Execução', 'type' => 'seconds', 'hint' => 'Em segundos (0 = sem limite)'],
        'max_input_time' => ['label' => 'Tempo Máx. Input', 'type' => 'seconds', 'hint' => 'Em segundos (0 = sem limite)'],
        'max_input_vars' => ['label' => 'Máx. Variáveis de Input', 'type' => 'number', 'hint' => 'Útil para forms grandes (WooCommerce, Elementor)'],
        'display_errors' => ['label' => 'Exibir Erros', 'type' => 'toggle', 'hint' => 'Recomendado On para desenvolvimento'],
    ];

    public function __construct()
    {
        $this->phpIniPath = '/var/www/project/docker/php/php.ini';
        $this->nginxConfPath = '/var/www/project/docker/nginx/nginx.conf';
    }

    /**
     * Lê os valores atuais do arquivo php.ini
     */
    public function readFromFile(): array
    {
        $values = [];

        if (!file_exists($this->phpIniPath)) {
            return $values;
        }

        $content = file_get_contents($this->phpIniPath);

        foreach (array_keys(self::DIRECTIVES) as $key) {
            if (preg_match('/^\s*' . preg_quote($key, '/') . '\s*=\s*(.+)$/m', $content, $m)) {
                $values[$key] = trim($m[1]);
            }
        }

        return $values;
    }

    /**
     * Diretivas que PHP CLI sobreescreve (não refletem o valor real do FPM)
     * Para estas, usamos o valor do php.ini como fonte de verdade
     */
    private const CLI_OVERRIDDEN = ['max_execution_time', 'max_input_time'];

    /**
     * Lê os valores ativos do PHP em execução no container wp-php.
     * Combina valores lidos via CLI com valores do arquivo para diretivas
     * que o CLI sobreescreve (max_execution_time, max_input_time).
     */
    public function readActiveValues(): array
    {
        $keys = array_keys(self::DIRECTIVES);
        $phpCode = 'echo json_encode([' .
            implode(',', array_map(fn($k) => "'$k'=>ini_get('$k')", $keys)) .
            ']);';

        $cmd = "docker exec wp-php php -c /usr/local/etc/php/conf.d/ -r " . escapeshellarg($phpCode) . " 2>/dev/null";
        $output = trim(shell_exec($cmd) ?? '');

        $cliValues = json_decode($output, true);
        if (!is_array($cliValues)) {
            return [];
        }

        // Normalizar display_errors: CLI reporta "1"/"" em vez de "On"/"Off"
        if (isset($cliValues['display_errors'])) {
            $cliValues['display_errors'] = $cliValues['display_errors'] ? 'On' : 'Off';
        }

        // Para diretivas que CLI sobreescreve, usar valor do arquivo
        $fileValues = $this->readFromFile();
        foreach (self::CLI_OVERRIDDEN as $key) {
            if (isset($fileValues[$key])) {
                $cliValues[$key] = $fileValues[$key];
            }
        }

        return $cliValues;
    }

    /**
     * Atualiza os valores no php.ini, nginx.conf, e recarrega os serviços
     */
    public function update(array $newValues): array
    {
        $errors = $this->validate($newValues);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        // 1. Atualizar php.ini
        $this->updatePhpIni($newValues);

        // 2. Atualizar client_max_body_size no nginx se upload mudou
        if (isset($newValues['upload_max_filesize'])) {
            $this->updateNginxMaxBody($newValues['upload_max_filesize']);
        }

        // 3. Recarregar serviços
        $reloadResult = $this->reloadServices();

        // 4. Verificar valores ativos
        $activeValues = $this->readActiveValues();

        Log::info('PHP config atualizado', [
            'new_values' => $newValues,
            'active_values' => $activeValues,
        ]);

        return [
            'success' => true,
            'active_values' => $activeValues,
            'reload' => $reloadResult,
        ];
    }

    /**
     * Valida os valores antes de salvar
     */
    private function validate(array $values): array
    {
        $errors = [];

        foreach ($values as $key => $value) {
            if (!array_key_exists($key, self::DIRECTIVES)) {
                continue;
            }

            $meta = self::DIRECTIVES[$key];

            if ($meta['type'] === 'size' && !preg_match('/^\d+[KMG]?$/i', $value)) {
                $errors[$key] = "Formato inválido. Use número seguido de K, M ou G (ex: 256M)";
            }

            if ($meta['type'] === 'number' && !ctype_digit((string) $value)) {
                $errors[$key] = "Deve ser um número inteiro.";
            }

            if ($meta['type'] === 'seconds' && !ctype_digit((string) $value)) {
                $errors[$key] = "Deve ser um número inteiro (segundos).";
            }

            if ($meta['type'] === 'toggle' && !in_array($value, ['On', 'Off'], true)) {
                $errors[$key] = "Deve ser On ou Off.";
            }
        }

        // post_max_size deve ser >= upload_max_filesize
        if (isset($values['post_max_size'], $values['upload_max_filesize'])) {
            $post = $this->toBytes($values['post_max_size']);
            $upload = $this->toBytes($values['upload_max_filesize']);
            if ($post < $upload) {
                $errors['post_max_size'] = "Deve ser maior ou igual ao Upload Máximo ({$values['upload_max_filesize']}).";
            }
        }

        return $errors;
    }

    /**
     * Atualiza diretivas no php.ini preservando comentários e estrutura
     */
    private function updatePhpIni(array $values): void
    {
        $content = file_get_contents($this->phpIniPath);

        foreach ($values as $key => $value) {
            if (!array_key_exists($key, self::DIRECTIVES)) {
                continue;
            }
            // Substitui valor existente mantendo o resto da linha
            $content = preg_replace(
                '/^(\s*' . preg_quote($key, '/') . '\s*=\s*).+$/m',
                '${1}' . $value,
                $content
            );
        }

        file_put_contents($this->phpIniPath, $content);
    }

    /**
     * Atualiza client_max_body_size no nginx.conf para acompanhar upload_max_filesize
     */
    private function updateNginxMaxBody(string $size): void
    {
        if (!file_exists($this->nginxConfPath)) {
            return;
        }

        $content = file_get_contents($this->nginxConfPath);
        $content = preg_replace(
            '/^(\s*client_max_body_size\s+).+;/m',
            '${1}' . $size . ';',
            $content
        );
        file_put_contents($this->nginxConfPath, $content);
    }

    /**
     * Recarrega PHP-FPM e Nginx.
     * Reinicia o container PHP porque single-file bind mounts ficam stale
     * quando o arquivo é reescrito (inode muda).
     */
    private function reloadServices(): array
    {
        $results = [];

        // Reiniciar container PHP (necessário por causa do bind mount de arquivo único)
        $results['php'] = trim(shell_exec('cd /var/www/project && docker compose restart php 2>&1') ?? '');

        // Recarregar Nginx
        $results['nginx'] = trim(shell_exec('docker exec wp-nginx nginx -s reload 2>&1') ?? '');

        return $results;
    }

    /**
     * Converte notação PHP de tamanho para bytes (ex: 256M → 268435456)
     */
    private function toBytes(string $value): int
    {
        $value = trim($value);
        $num = (int) $value;
        $suffix = strtoupper(substr($value, -1));

        return match ($suffix) {
            'G' => $num * 1024 * 1024 * 1024,
            'M' => $num * 1024 * 1024,
            'K' => $num * 1024,
            default => $num,
        };
    }
}
