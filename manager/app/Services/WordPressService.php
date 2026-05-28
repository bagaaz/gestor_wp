<?php

namespace App\Services;

use App\Models\PluginRegistry;
use App\Models\Site;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Log;

class WordPressService
{
    private string $sitesPath;
    private string $nginxConfPath;
    private string $projectRoot;

    private string $nginxEnabledPath;
    private string $baseDomain;

    public function __construct()
    {
        $this->sitesPath = config('wp.sites_path');
        $this->nginxConfPath = config('wp.nginx_conf');
        $this->nginxEnabledPath = config('wp.nginx_enabled');
        $this->projectRoot = config('wp.project_root');
        $this->baseDomain = config('wp.base_domain');
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
                    'url' => "https://{$name}.{$this->baseDomain}",
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
            // Instalar plugins selecionados
            if (!empty($params['selected_plugins'])) {
                $this->installSelectedPlugins($name, $params['selected_plugins']);
            }

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

    /**
     * Instala plugins selecionados do registro em um site
     */
    private function installSelectedPlugins(string $siteName, array $pluginIds): void
    {
        $plugins = PluginRegistry::whereIn('id', $pluginIds)->get();
        $hasElementor = false;

        foreach ($plugins as $plugin) {
            $source = $plugin->source === 'upload' && $plugin->file_path
                ? $plugin->file_path
                : $plugin->slug;

            $result = $this->runWpCli($siteName, "plugin install {$source} --activate");

            if ($result) {
                Log::info("Plugin '{$plugin->name}' instalado no site '{$siteName}'");
                if (str_contains($plugin->slug, 'elementor')) {
                    $hasElementor = true;
                }
            } else {
                Log::warning("Falha ao instalar plugin '{$plugin->name}' no site '{$siteName}'");
            }
        }

        // Configurar Elementor: pular onboarding, habilitar SVG, desativar tracker
        if ($hasElementor) {
            $this->runWpCli($siteName, 'option update elementor_unfiltered_files_upload 1');
            $this->runWpCli($siteName, 'option update elementor_onboarded 2.0.0');
            $this->runWpCli($siteName, "option update elementor_onboarding_progress '{\"current_step\":0,\"current_step_index\":0,\"current_step_id\":null,\"completed_steps\":[],\"exit_type\":\"user_exit\",\"last_active_timestamp\":null,\"started_at\":null,\"starter_dismissed\":true}' --format=json");
            $this->runWpCli($siteName, 'option update elementor_one_onboarding_completed 1');
            $this->runWpCli($siteName, 'option update elementor_one_welcome_screen_completed 1');
            $this->runWpCli($siteName, 'option update elementor_tracker_notice 1');
            $this->runWpCli($siteName, 'transient delete elementor_activation_redirect');
        }
    }

    /**
     * Ativa um plugin via WP-CLI
     */
    public function activatePlugin(string $siteName, string $pluginSlug): array
    {
        $output = $this->runWpCliWithStderr($siteName, 'plugin activate ' . escapeshellarg($pluginSlug));
        $success = str_contains($output, 'Success');

        if ($success) {
            ActivityLog::create([
                'action' => 'plugin_activated',
                'description' => "Plugin '{$pluginSlug}' ativado no site '{$siteName}'",
            ]);
        }

        return ['success' => $success, 'output' => $output];
    }

    /**
     * Desativa um plugin via WP-CLI
     */
    public function deactivatePlugin(string $siteName, string $pluginSlug): array
    {
        $output = $this->runWpCliWithStderr($siteName, 'plugin deactivate ' . escapeshellarg($pluginSlug));
        $success = str_contains($output, 'Success');

        if ($success) {
            ActivityLog::create([
                'action' => 'plugin_deactivated',
                'description' => "Plugin '{$pluginSlug}' desativado no site '{$siteName}'",
            ]);
        }

        return ['success' => $success, 'output' => $output];
    }

    /**
     * Remove um plugin via WP-CLI
     */
    public function deletePlugin(string $siteName, string $pluginSlug): array
    {
        $output = $this->runWpCliWithStderr($siteName, 'plugin delete ' . escapeshellarg($pluginSlug));
        $success = str_contains($output, 'Success') || str_contains($output, 'Deleted');

        if ($success) {
            ActivityLog::create([
                'action' => 'plugin_deleted',
                'description' => "Plugin '{$pluginSlug}' removido do site '{$siteName}'",
            ]);
        }

        return ['success' => $success, 'output' => $output];
    }

    // ---- Helpers ----

    private function runWpCli(string $site, string $command): ?string
    {
        $path = $this->sitesPath . '/' . $site;
        $cmd = "/usr/local/bin/wp --allow-root --path={$path} {$command} 2>/dev/null";
        $result = trim(shell_exec($cmd) ?? '');
        return $result ?: null;
    }

    private function runWpCliWithStderr(string $site, string $command): string
    {
        $path = $this->sitesPath . '/' . $site;
        $cmd = "/usr/local/bin/wp --allow-root --path={$path} {$command} 2>&1";
        return trim(shell_exec($cmd) ?? '');
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
