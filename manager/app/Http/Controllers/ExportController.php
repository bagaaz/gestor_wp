<?php

namespace App\Http\Controllers;

use App\Models\Site;
use Illuminate\Http\Request;
use ZipArchive;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class ExportController extends Controller
{
    private string $sitesPath = '/var/www/sites';

    /**
     * Formulário de export para produção
     */
    public function showExportForm(Site $site)
    {
        return view('sites.export', compact('site'));
    }

    /**
     * Gerar ZIP pronto para produção
     */
    public function export(Request $request, Site $site)
    {
        $validated = $request->validate([
            'prod_domain' => ['required', 'string'],
            'prod_db_host' => ['required', 'string'],
            'prod_db_name' => ['required', 'string'],
            'prod_db_user' => ['required', 'string'],
            'prod_db_password' => ['required', 'string'],
            'prod_db_prefix' => ['nullable', 'string'],
            'prod_protocol' => ['required', 'in:http,https'],
            'disable_debug' => ['nullable', 'boolean'],
            'enable_cache' => ['nullable', 'boolean'],
            'remove_dev_plugins' => ['nullable', 'boolean'],
        ]);

        $siteDir = $this->sitesPath . '/' . $site->name;

        if (!is_dir($siteDir)) {
            return back()->with('error', 'Diretório do site não encontrado.');
        }

        $prodUrl = $validated['prod_protocol'] . '://' . $validated['prod_domain'];
        $localUrl = $site->url;
        $dbPrefix = $validated['prod_db_prefix'] ?? 'wp_';

        // Diretório temporário para montar o export
        $tmpDir = sys_get_temp_dir() . '/wp-export-' . $site->name . '-' . time();
        $zipPath = sys_get_temp_dir() . '/' . $site->name . '-production-' . date('Ymd_His') . '.zip';

        try {
            // 1. Copiar arquivos do site
            $this->copyDirectory($siteDir, $tmpDir);

            // 2. Exportar banco de dados com search-replace
            $this->exportDatabase($site, $tmpDir, $localUrl, $prodUrl);

            // 3. Gerar wp-config.php de produção
            $this->generateProdWpConfig($tmpDir, $validated, $prodUrl, $dbPrefix);

            // 4. Gerar .htaccess otimizado para produção
            $this->generateProdHtaccess($tmpDir);

            // 5. Remover mu-plugin de dev (substituir por versão produção)
            $this->cleanForProduction($tmpDir, $validated);

            // 6. Search-replace nos arquivos (urls hardcoded em temas/plugins)
            $this->searchReplaceInFiles($tmpDir, $localUrl, $prodUrl);

            // 7. Criar ZIP
            $this->createZip($tmpDir, $zipPath);

            // 8. Limpar temp
            $this->removeDirectory($tmpDir);

            // Retornar download
            return response()->download($zipPath, basename($zipPath))->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            // Limpar em caso de erro
            if (is_dir($tmpDir)) $this->removeDirectory($tmpDir);
            if (file_exists($zipPath)) unlink($zipPath);

            return back()->with('error', 'Erro ao gerar export: ' . $e->getMessage());
        }
    }

    /**
     * Exporta o banco de dados para SQL
     */
    private function exportDatabase(Site $site, string $tmpDir, string $localUrl, string $prodUrl): void
    {
        $sqlFile = $tmpDir . '/database.sql';

        // Dump do banco
        $cmd = "docker compose exec -T mysql mysqldump -uroot -proot {$site->db_name} 2>/dev/null";
        $sql = shell_exec($cmd);

        if (empty($sql)) {
            throw new \Exception("Falha ao exportar banco de dados '{$site->db_name}'.");
        }

        // Search-replace URLs no SQL
        $sql = str_replace(
            [
                $localUrl,
                addcslashes($localUrl, '/'),                   // URLs escaped em JSON
                str_replace('/', '\\/', $localUrl),            // URLs com barras escapadas
            ],
            [
                $prodUrl,
                addcslashes($prodUrl, '/'),
                str_replace('/', '\\/', $prodUrl),
            ],
            $sql
        );

        // Corrigir serialized data (WordPress usa muito)
        $sql = $this->fixSerializedReplace($sql, $localUrl, $prodUrl);

        file_put_contents($sqlFile, $sql);
    }

    /**
     * Corrige dados serializados após search-replace
     */
    private function fixSerializedReplace(string $sql, string $from, string $to): string
    {
        $diff = strlen($to) - strlen($from);
        if ($diff === 0) return $sql;

        // Corrigir s:N:"..." em dados serializados
        return preg_replace_callback(
            '/s:(\d+):"((?:[^"\\\\]|\\\\.)*)"/s',
            function ($matches) use ($from, $to, $diff) {
                $length = (int) $matches[1];
                $content = $matches[2];
                $count = substr_count($content, $to);
                if ($count > 0) {
                    // Recalcular comprimento real
                    $realLength = strlen(stripcslashes($content));
                    return 's:' . $realLength . ':"' . $content . '"';
                }
                return $matches[0];
            },
            $sql
        );
    }

    /**
     * Gera wp-config.php otimizado para produção
     */
    private function generateProdWpConfig(string $tmpDir, array $config, string $prodUrl, string $dbPrefix): void
    {
        $wpConfig = <<<PHP
<?php
/**
 * wp-config.php — Gerado pelo WP Docker Manager para PRODUÇÃO
 * Gerado em: %DATE%
 */

// === Banco de Dados ===
define('DB_NAME',     '{$config['prod_db_name']}');
define('DB_USER',     '{$config['prod_db_user']}');
define('DB_PASSWORD', '{$config['prod_db_password']}');
define('DB_HOST',     '{$config['prod_db_host']}');
define('DB_CHARSET',  'utf8mb4');
define('DB_COLLATE',  'utf8mb4_unicode_ci');

// === Prefixo das Tabelas ===
\$table_prefix = '{$dbPrefix}';

// === Chaves de Segurança ===
// IMPORTANTE: Gere novas chaves em https://api.wordpress.org/secret-key/1.1/salt/
%SALT_PLACEHOLDER%

// === URLs ===
define('WP_HOME',    '{$prodUrl}');
define('WP_SITEURL', '{$prodUrl}');

// === Debug (DESLIGADO em produção) ===
define('WP_DEBUG', false);
define('WP_DEBUG_LOG', false);
define('WP_DEBUG_DISPLAY', false);

// === Segurança ===
define('DISALLOW_FILE_EDIT', true);
define('FORCE_SSL_ADMIN', %FORCE_SSL%);

// === Performance ===
define('WP_MEMORY_LIMIT', '256M');
define('WP_MAX_MEMORY_LIMIT', '256M');
define('WP_POST_REVISIONS', 5);
define('AUTOSAVE_INTERVAL', 300);
define('EMPTY_TRASH_DAYS', 7);

// === File System ===
define('FS_METHOD', 'direct');

// === Atualizações ===
define('AUTOMATIC_UPDATER_DISABLED', false);
define('WP_AUTO_UPDATE_CORE', 'minor');

%CACHE_CONFIG%

/* Isso é tudo, pode parar de editar! */
if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}
require_once ABSPATH . 'wp-settings.php';
PHP;

        // Gerar salt keys
        $saltKeys = '';
        $saltNames = ['AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY', 'AUTH_SALT', 'SECURE_AUTH_SALT', 'LOGGED_IN_SALT', 'NONCE_SALT'];
        foreach ($saltNames as $name) {
            $salt = bin2hex(random_bytes(32));
            $saltKeys .= "define('{$name}', '{$salt}');\n";
        }

        $forceSsl = ($config['prod_protocol'] === 'https') ? 'true' : 'false';

        $cacheConfig = '';
        if (!empty($config['enable_cache'])) {
            $cacheConfig = "define('WP_CACHE', true);";
        }

        $wpConfig = str_replace(
            ['%DATE%', '%SALT_PLACEHOLDER%', '%FORCE_SSL%', '%CACHE_CONFIG%'],
            [date('d/m/Y H:i'), $saltKeys, $forceSsl, $cacheConfig],
            $wpConfig
        );

        file_put_contents($tmpDir . '/wp-config.php', $wpConfig);
    }

    /**
     * Gera .htaccess otimizado para produção
     */
    private function generateProdHtaccess(string $tmpDir): void
    {
        $htaccess = <<<'HTACCESS'
# ============================================
# .htaccess — Gerado pelo WP Docker Manager
# ============================================

# === WordPress Permalinks ===
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
RewriteBase /
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
</IfModule>

# === Segurança ===

# Bloquear acesso ao wp-config.php
<Files wp-config.php>
    Order allow,deny
    Deny from all
</Files>

# Bloquear xmlrpc.php
<Files xmlrpc.php>
    Order allow,deny
    Deny from all
</Files>

# Bloquear .htaccess
<Files .htaccess>
    Order allow,deny
    Deny from all
</Files>

# Bloquear readme.html e license.txt
<FilesMatch "^(readme\.html|license\.txt|wp-config-sample\.php)$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Desabilitar listagem de diretórios
Options -Indexes

# Bloquear acesso a arquivos PHP no uploads
<IfModule mod_rewrite.c>
    RewriteRule ^wp-content/uploads/.*\.php$ - [F]
</IfModule>

# Bloquear acesso a includes
<IfModule mod_rewrite.c>
    RewriteRule ^wp-includes/.*\.php$ - [F]
    RewriteRule ^wp-admin/includes/ - [F]
</IfModule>

# === Headers de Segurança ===
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
    Header set Referrer-Policy "strict-origin-when-cross-origin"
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains" env=HTTPS
</IfModule>

# === Performance ===

# Gzip
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE text/javascript
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
    AddOutputFilterByType DEFLATE application/json
    AddOutputFilterByType DEFLATE image/svg+xml
</IfModule>

# Cache de arquivos estáticos
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/webp "access plus 1 year"
    ExpiresByType image/svg+xml "access plus 1 year"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType text/javascript "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType application/pdf "access plus 1 month"
    ExpiresByType application/x-font-woff "access plus 1 year"
    ExpiresByType application/x-font-woff2 "access plus 1 year"
    ExpiresByType font/woff2 "access plus 1 year"
</IfModule>

# Limitar upload
LimitRequestBody 268435456
HTACCESS;

        file_put_contents($tmpDir . '/.htaccess', $htaccess);
    }

    /**
     * Limpa arquivos de desenvolvimento para produção
     */
    private function cleanForProduction(string $tmpDir, array $config): void
    {
        // Remover mu-plugin de dev
        $muPluginFile = $tmpDir . '/wp-content/mu-plugins/wp-local-dev.php';
        if (file_exists($muPluginFile)) {
            unlink($muPluginFile);
        }

        // Criar mu-plugin de produção (apenas segurança, sem SMTP dev, sem badge)
        $prodMuPlugin = <<<'PHP'
<?php
/**
 * Plugin: WP Production Security
 * Gerado pelo WP Docker Manager
 */

// Permitir SVG e WebP
add_filter('upload_mimes', function ($mimes) {
    $mimes['svg']  = 'image/svg+xml';
    $mimes['svgz'] = 'image/svg+xml';
    $mimes['webp'] = 'image/webp';
    $mimes['avif'] = 'image/avif';
    return $mimes;
});

add_filter('wp_check_filetype_and_ext', function ($data, $file, $filename, $mimes) {
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    if ($ext === 'svg') {
        $data['type'] = 'image/svg+xml';
        $data['ext']  = 'svg';
    }
    return $data;
}, 10, 4);

// Desabilitar XML-RPC
add_filter('xmlrpc_enabled', '__return_false');
add_filter('wp_headers', function ($headers) {
    unset($headers['X-Pingback']);
    return $headers;
});

// Bloquear enumeração de usuários
add_filter('rest_authentication_errors', function ($result) {
    if (!is_user_logged_in()) {
        $path = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($path, '/wp/v2/users') !== false) {
            return new WP_Error('rest_forbidden', 'Acesso negado.', ['status' => 403]);
        }
    }
    return $result;
});

add_action('template_redirect', function () {
    if (is_author() && !is_user_logged_in()) {
        wp_redirect(home_url(), 301);
        exit;
    }
});

// Remover meta generator
remove_action('wp_head', 'wp_generator');
add_filter('the_generator', '__return_empty_string');
remove_action('wp_head', 'wlwmanifest_link');
remove_action('wp_head', 'rsd_link');
remove_action('wp_head', 'wp_shortlink_wp_head');

// Proteção brute-force
add_filter('authenticate', function ($user, $username, $password) {
    if (empty($username) || empty($password)) return $user;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $transient_key = 'login_attempts_' . md5($ip);
    $attempts = (int) get_transient($transient_key);
    if ($attempts >= 5) {
        return new WP_Error('too_many_attempts', '<strong>BLOQUEADO:</strong> Muitas tentativas. Aguarde 15 minutos.');
    }
    return $user;
}, 30, 3);

add_action('wp_login_failed', function () {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $key = 'login_attempts_' . md5($ip);
    set_transient($key, (int) get_transient($key) + 1, 15 * MINUTE_IN_SECONDS);
});

add_action('wp_login', function () {
    delete_transient('login_attempts_' . md5($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'));
});

// Desabilitar pingbacks e trackbacks
add_filter('pings_open', '__return_false', 20, 2);
add_filter('pre_option_default_ping_status', '__return_zero');

// Headers de segurança
add_action('send_headers', function () {
    if (!is_admin()) {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }
});

// Logo customizada no login (se existir)
add_action('login_enqueue_scripts', function () {
    $logo_path = ABSPATH . 'wp-content/mu-plugins/assets/login-logo.svg';
    if (file_exists($logo_path)) {
        $logo_url = content_url('mu-plugins/assets/login-logo.svg');
        echo '<style>
            #login h1 a, .login h1 a {
                background-image: url(' . esc_url($logo_url) . ') !important;
                background-size: contain !important;
                background-repeat: no-repeat !important;
                background-position: center !important;
                width: 100% !important;
                height: 80px !important;
                margin-bottom: 20px !important;
            }
        </style>';
    }
});

add_filter('login_headerurl', function () { return home_url(); });
add_filter('login_headertext', function () { return get_bloginfo('name'); });
PHP;

        file_put_contents($tmpDir . '/wp-content/mu-plugins/wp-production-security.php', $prodMuPlugin);

        // Manter a pasta assets (com a logo)
        // Remover arquivos desnecessários
        $filesToRemove = [
            $tmpDir . '/readme.html',
            $tmpDir . '/license.txt',
            $tmpDir . '/wp-config-sample.php',
            $tmpDir . '/wp-content/debug.log',
        ];

        foreach ($filesToRemove as $file) {
            if (file_exists($file)) unlink($file);
        }
    }

    /**
     * Search-replace recursivo em arquivos do tema e plugins
     */
    private function searchReplaceInFiles(string $dir, string $from, string $to): void
    {
        $extensions = ['php', 'css', 'js', 'json', 'html', 'htm', 'txt', 'xml'];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) continue;

            $ext = strtolower($file->getExtension());
            if (!in_array($ext, $extensions)) continue;

            // Pular core do WP, só processar wp-content
            $path = $file->getPathname();
            if (!str_contains($path, 'wp-content') && $ext !== 'json') continue;

            $content = file_get_contents($path);
            if (str_contains($content, $from)) {
                $content = str_replace($from, $to, $content);
                file_put_contents($path, $content);
            }
        }
    }

    // ---- Helpers ----

    private function copyDirectory(string $src, string $dst): void
    {
        if (!is_dir($dst)) mkdir($dst, 0755, true);

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($src, \FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $target = $dst . '/' . $iterator->getSubPathname();
            if ($item->isDir()) {
                if (!is_dir($target)) mkdir($target, 0755, true);
            } else {
                copy($item->getPathname(), $target);
            }
        }
    }

    private function createZip(string $sourceDir, string $zipPath): void
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \Exception('Não foi possível criar o arquivo ZIP.');
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceDir, \FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $relativePath = substr($file->getPathname(), strlen($sourceDir) + 1);
                $zip->addFile($file->getPathname(), $relativePath);
            }
        }

        $zip->close();
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) return;

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir($dir);
    }
}
