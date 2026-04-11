<?php

namespace App\Services;

use App\Models\Site;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Log;

class WordPressService
{
    private string $sitesPath;
    private string $nginxConfPath;
    private string $projectRoot;

    public function __construct()
    {
        $this->sitesPath = '/var/www/sites';
        $this->nginxConfPath = '/etc/nginx/conf.d';
        $this->projectRoot = '/var/www/project';
    }

    /**
     * Descobre sites no filesystem e sincroniza com o banco
     */
    public function syncSites(): void
    {
        if (!is_dir($this->sitesPath)) return;

        $dirs = array_filter(glob($this->sitesPath . '/*'), 'is_dir');

        foreach ($dirs as $dir) {
            $name = basename($dir);

            if (!file_exists($dir . '/wp-config.php')) continue;

            $site = Site::firstOrCreate(
                ['name' => $name],
                [
                    'url' => "http://{$name}.localhost",
                    'db_name' => 'wp_' . str_replace(['-', '.'], '_', $name),
                    'status' => 'active',
                ]
            );

            // Atualizar disk usage e wp_version
            $size = $this->getDirectorySize($dir);
            $wpVersion = $this->runWpCli($name, 'core version');
            $site->update([
                'disk_usage' => $size,
                'wp_version' => $wpVersion ?: $site->wp_version,
            ]);
        }

        // Marcar sites removidos do filesystem
        $existingDirs = array_map('basename', $dirs);
        Site::whereNotIn('name', $existingDirs)->update(['status' => 'inactive']);
    }

    /**
     * Obtém informações de um site WordPress via WP-CLI
     */
    public function getSiteInfo(string $name): array
    {
        $info = [
            'wp_version' => $this->runWpCli($name, 'core version') ?: 'desconhecida',
            'plugins' => $this->getPluginsList($name),
            'themes' => $this->getThemesList($name),
            'active_theme' => $this->runWpCli($name, 'theme list --status=active --field=name') ?: 'desconhecido',
        ];

        return $info;
    }

    /**
     * Cria um novo site WordPress (executa o script shell)
     */
    public function createSite(array $params): array
    {
        $name = $params['name'];
        $version = $params['version'] ?? 'latest';
        $title = $params['title'] ?? $name;

        $cmd = "cd {$this->projectRoot} && bash wp-manager.sh create {$name}";
        $cmd .= " --version {$version}";
        $cmd .= " --title " . escapeshellarg($title);

        if (!empty($params['woocommerce'])) {
            $cmd .= " --woocommerce";
        }
        if (!empty($params['multisite'])) {
            $cmd .= " --multisite";
        }

        $cmd .= " 2>&1";

        Log::info("Criando site '{$name}': {$cmd}");
        $output = shell_exec($cmd);
        Log::info("Saída do comando create '{$name}':", ['output' => $output]);

        $success = str_contains($output ?? '', 'sucesso');

        if ($success) {
            ActivityLog::create([
                'action' => 'created',
                'description' => "Site '{$name}' criado com WordPress {$version}",
                'metadata' => $params,
            ]);
        } else {
            Log::error("Falha ao criar site '{$name}'", ['output' => $output]);
            ActivityLog::create([
                'action' => 'error',
                'description' => "Falha ao criar site '{$name}': " . mb_substr($output ?? 'sem saída do comando', 0, 500),
                'metadata' => $params,
            ]);
        }

        return [
            'success' => $success,
            'output' => $output,
        ];
    }

    /**
     * Remove um site WordPress
     */
    public function removeSite(string $name, bool $keepDb = false): array
    {
        $cmd = "cd {$this->projectRoot} && bash wp-manager.sh remove {$name} --force";
        if ($keepDb) {
            $cmd .= " --keep-db";
        }
        $cmd .= " 2>&1";

        Log::info("Removendo site '{$name}': {$cmd}");
        $output = shell_exec($cmd);
        Log::info("Saída do comando remove '{$name}':", ['output' => $output]);

        $success = str_contains($output ?? '', 'sucesso');

        if ($success) {
            ActivityLog::create([
                'action' => 'removed',
                'description' => "Site '{$name}' removido" . ($keepDb ? ' (banco mantido)' : ''),
            ]);
        } else {
            Log::error("Falha ao remover site '{$name}'", ['output' => $output]);
        }

        return [
            'success' => $success,
            'output' => $output,
        ];
    }

    /**
     * Faz backup de um site
     */
    public function backupSite(string $name): array
    {
        $cmd = "cd {$this->projectRoot} && bash wp-manager.sh backup {$name} 2>&1";
        $output = shell_exec($cmd);
        $success = str_contains($output ?? '', 'Backup salvo');

        return [
            'success' => $success,
            'output' => $output,
        ];
    }

    /**
     * Clona um site
     */
    public function cloneSite(string $source, string $target): array
    {
        $cmd = "cd {$this->projectRoot} && bash wp-manager.sh clone {$source} {$target} 2>&1";
        $output = shell_exec($cmd);
        $success = str_contains($output ?? '', 'sucesso');

        return [
            'success' => $success,
            'output' => $output,
        ];
    }

    // ---- Helpers ----

    private function runWpCli(string $site, string $command): ?string
    {
        $cmd = "docker exec wp-php wp --allow-root --path=/var/www/sites/{$site} {$command} 2>/dev/null";
        $result = trim(shell_exec($cmd) ?? '');
        return $result ?: null;
    }

    private function getPluginsList(string $name): array
    {
        $output = $this->runWpCli($name, 'plugin list --format=json');
        return $output ? (json_decode($output, true) ?? []) : [];
    }

    private function getThemesList(string $name): array
    {
        $output = $this->runWpCli($name, 'theme list --format=json');
        return $output ? (json_decode($output, true) ?? []) : [];
    }

    private function getDirectorySize(string $path): int
    {
        $size = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            $size += $file->getSize();
        }
        return $size;
    }
}
