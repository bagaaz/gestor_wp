<?php

namespace App\Http\Controllers;

use App\Models\PluginRegistry;
use App\Models\Setting;
use App\Services\PhpConfigService;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function __construct(
        private PhpConfigService $phpConfig
    ) {}

    public function index()
    {
        $settings = Setting::all()->pluck('value', 'key')->toArray();

        // Verificar se existe logo customizada
        $logoPath = '/var/www/sites/../docker/assets/login-logo.svg';
        $hasLogo = file_exists(base_path('../docker/assets/login-logo.svg'));

        // PHP config
        $phpDirectives = PhpConfigService::DIRECTIVES;
        $phpValues = $this->phpConfig->readFromFile();
        $phpActiveValues = $this->phpConfig->readActiveValues();

        // Plugins registrados
        $plugins = PluginRegistry::orderBy('name')->get();

        return view('settings.index', compact('settings', 'hasLogo', 'phpDirectives', 'phpValues', 'phpActiveValues', 'plugins'));
    }

    public function update(Request $request)
    {
        $fields = [
            'default_admin_user',
            'default_admin_password',
            'default_admin_email',
            'mysql_root_password',
            'mysql_user',
            'mysql_password',
        ];

        foreach ($fields as $field) {
            if ($request->has($field)) {
                Setting::set($field, $request->input($field));
            }
        }

        return redirect()->route('settings.index')
            ->with('success', 'Configurações salvas com sucesso!');
    }

    /**
     * Atualizar configurações do PHP
     */
    public function updatePhp(Request $request)
    {
        $values = $request->only(array_keys(PhpConfigService::DIRECTIVES));

        $result = $this->phpConfig->update($values);

        if (!$result['success']) {
            return back()
                ->withErrors($result['errors'])
                ->withInput()
                ->with('active_tab', 'php');
        }

        // Verificar se todos os valores foram aplicados
        $mismatches = [];
        foreach ($values as $key => $expected) {
            $active = $result['active_values'][$key] ?? null;
            if ($active !== null && $active !== $expected) {
                $mismatches[$key] = "Configurado: {$expected}, Ativo: {$active}";
            }
        }

        if (!empty($mismatches)) {
            return redirect()->route('settings.index', ['tab' => 'php'])
                ->with('warning', 'Configurações salvas, mas alguns valores diferem. Pode ser necessário rodar: ./wp-manager.sh rebuild')
                ->with('active_tab', 'php');
        }

        return redirect()->route('settings.index', ['tab' => 'php'])
            ->with('success', 'Configurações PHP aplicadas e verificadas com sucesso!')
            ->with('active_tab', 'php');
    }

    /**
     * Upload de nova logo para o login do WordPress
     */
    public function uploadLogo(Request $request)
    {
        $request->validate([
            'login_logo' => ['required', 'file', 'mimes:svg,png,jpg,jpeg,webp', 'max:2048'],
        ]);

        $file = $request->file('login_logo');
        $extension = $file->getClientOriginalExtension();
        $filename = 'login-logo.' . $extension;

        // Salvar no diretório de assets do Docker
        $assetsDir = base_path('../docker/assets');
        if (!is_dir($assetsDir)) {
            mkdir($assetsDir, 0755, true);
        }

        // Remover logo anterior (qualquer extensão)
        foreach (glob($assetsDir . '/login-logo.*') as $oldLogo) {
            unlink($oldLogo);
        }

        $file->move($assetsDir, $filename);

        // Atualizar em todos os sites existentes
        $sitesDir = '/var/www/sites';
        if (is_dir($sitesDir)) {
            foreach (glob($sitesDir . '/*/wp-content/mu-plugins/assets') as $siteAssetsDir) {
                // Remover logo antiga
                foreach (glob($siteAssetsDir . '/login-logo.*') as $oldLogo) {
                    unlink($oldLogo);
                }
                // Copiar nova logo
                copy($assetsDir . '/' . $filename, $siteAssetsDir . '/' . $filename);
            }
        }

        // Se mudou a extensão, atualizar o mu-plugin para refletir
        if ($extension !== 'svg') {
            $this->updateMuPluginLogoExtension($sitesDir, $extension);
        }

        return redirect()->route('settings.index', ['tab' => 'login'])
            ->with('success', 'Logo atualizada em todos os sites!');
    }

    /**
     * Atualizar cores da tela de login
     */
    public function updateLoginColors(Request $request)
    {
        $validated = $request->validate([
            'login_primary_color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'login_bg_color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'login_text_color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        foreach ($validated as $key => $value) {
            Setting::set($key, $value);
        }

        // Aplicar em todos os sites existentes
        $this->applyLoginColorsToAllSites($validated);

        return redirect()->route('settings.index', ['tab' => 'login'])
            ->with('success', 'Cores do login atualizadas em todos os sites!');
    }

    /**
     * Aplica as cores do login em todos os mu-plugins existentes
     */
    private function applyLoginColorsToAllSites(array $colors): void
    {
        $sitesDir = '/var/www/sites';
        if (!is_dir($sitesDir)) return;

        $primary = $colors['login_primary_color'];
        $bg = $colors['login_bg_color'];
        $text = $colors['login_text_color'];

        foreach (glob($sitesDir . '/*/wp-content/mu-plugins/wp-local-dev.php') as $muPlugin) {
            $content = file_get_contents($muPlugin);

            // Substituir as variáveis PHP de cor no mu-plugin
            $content = preg_replace(
                "/\\$primary\s*=\s*'#[0-9A-Fa-f]{6}'/",
                "\$primary = '{$primary}'",
                $content
            );
            $content = preg_replace(
                "/\\$bg\s*=\s*'#[0-9A-Fa-f]{6}'/",
                "\$bg      = '{$bg}'",
                $content
            );
            $content = preg_replace(
                "/\\$text\s*=\s*'#[0-9A-Fa-f]{6}'/",
                "\$text    = '{$text}'",
                $content
            );

            file_put_contents($muPlugin, $content);
        }
    }

    /**
     * Cadastrar plugin no registro
     */
    public function storePlugin(Request $request)
    {
        $validated = $request->validate([
            'source' => ['required', 'in:repository,upload'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required_if:source,repository', 'nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'plugin_file' => ['required_if:source,upload', 'nullable', 'file', 'mimes:zip', 'max:51200'],
        ]);

        $slug = $validated['slug'];
        $filePath = null;

        if ($validated['source'] === 'upload' && $request->hasFile('plugin_file')) {
            $file = $request->file('plugin_file');
            $slug = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $pluginsDir = base_path('../docker/plugins');
            if (!is_dir($pluginsDir)) {
                mkdir($pluginsDir, 0755, true);
            }
            $file->move($pluginsDir, $file->getClientOriginalName());
            $filePath = '/var/www/project/docker/plugins/' . $file->getClientOriginalName();
        }

        PluginRegistry::updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $validated['name'],
                'source' => $validated['source'],
                'file_path' => $filePath,
                'description' => $validated['description'] ?? null,
            ]
        );

        return redirect()->route('settings.index', ['tab' => 'plugins'])
            ->with('success', "Plugin '{$validated['name']}' cadastrado com sucesso!");
    }

    /**
     * Remover plugin do registro
     */
    public function destroyPlugin(PluginRegistry $plugin)
    {
        // Remover arquivo ZIP se for upload
        if ($plugin->source === 'upload' && $plugin->file_path) {
            $localPath = base_path('../docker/plugins/' . basename($plugin->file_path));
            if (file_exists($localPath)) {
                unlink($localPath);
            }
        }

        $name = $plugin->name;
        $plugin->delete();

        return redirect()->route('settings.index', ['tab' => 'plugins'])
            ->with('success', "Plugin '{$name}' removido do registro.");
    }

    /**
     * Atualiza referência da extensão da logo nos mu-plugins
     */
    private function updateMuPluginLogoExtension(string $sitesDir, string $extension): void
    {
        if (!is_dir($sitesDir)) return;

        foreach (glob($sitesDir . '/*/wp-content/mu-plugins/wp-local-dev.php') as $muPlugin) {
            $content = file_get_contents($muPlugin);
            $content = preg_replace(
                "/login-logo\.\w+/",
                "login-logo.{$extension}",
                $content
            );
            file_put_contents($muPlugin, $content);
        }
    }
}
