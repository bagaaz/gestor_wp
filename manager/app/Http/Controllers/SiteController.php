<?php

namespace App\Http\Controllers;

use App\Models\PluginRegistry;
use App\Models\Site;
use App\Models\ActivityLog;
use App\Services\DockerService;
use App\Services\WordPressService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SiteController extends Controller
{
    public function __construct(
        private DockerService $docker,
        private WordPressService $wordpress
    ) {}

    /**
     * Listagem de sites
     */
    public function index()
    {
        $this->wordpress->syncSites();
        $sites = Site::orderBy('name')->get();
        return view('sites.index', compact('sites'));
    }

    /**
     * Form de criação
     */
    public function create()
    {
        return view('sites.create');
    }

    /**
     * Criar novo site (step 1: valida e verifica se há plugins)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'regex:/^[a-z0-9][a-z0-9-]*[a-z0-9]$|^[a-z0-9]$/', 'unique:sites,name'],
            'version' => ['nullable', 'string'],
            'title' => ['nullable', 'string', 'max:255'],
            'woocommerce' => ['nullable', 'boolean'],
            'multisite' => ['nullable', 'boolean'],
        ]);

        if ($request->expectsJson()) {
            $result = $this->wordpress->createSite($validated);
            return response()->json($result, $result['success'] ? 201 : 422);
        }

        // Verificar se há plugins cadastrados
        $plugins = PluginRegistry::orderBy('name')->get();
        if ($plugins->isNotEmpty()) {
            // Guardar dados na session e redirecionar para seleção de plugins
            session(['site_creation_data' => $validated]);
            return redirect()->route('sites.select-plugins');
        }

        // Sem plugins, criar direto
        return $this->performCreation($validated);
    }

    /**
     * Tela de seleção de plugins (step 2)
     */
    public function selectPlugins()
    {
        $siteData = session('site_creation_data');
        if (!$siteData) {
            return redirect()->route('sites.create');
        }

        $plugins = PluginRegistry::orderBy('name')->get();
        return view('sites.select-plugins', compact('plugins', 'siteData'));
    }

    /**
     * Criar site com plugins selecionados (step 3)
     */
    public function storeWithPlugins(Request $request)
    {
        $siteData = session('site_creation_data');
        if (!$siteData) {
            return redirect()->route('sites.create');
        }

        $request->validate([
            'plugins' => ['nullable', 'array'],
            'plugins.*' => ['integer', 'exists:plugin_registry,id'],
        ]);

        $siteData['selected_plugins'] = $request->input('plugins', []);
        session()->forget('site_creation_data');

        return $this->performCreation($siteData);
    }

    /**
     * Executa a criação do site
     */
    private function performCreation(array $params): \Illuminate\Http\RedirectResponse
    {
        $result = $this->wordpress->createSite($params);

        if ($result['success']) {
            return redirect()->route('sites.index')
                ->with('success', "Site '{$params['name']}' criado com sucesso!");
        }

        return redirect()->route('sites.create')
            ->with('error', 'Erro ao criar site.')
            ->with('error_detail', $result['output']);
    }

    /**
     * Detalhes do site
     */
    public function show(Site $site)
    {
        $info = $this->wordpress->getSiteInfo($site->name);
        $logs = $site->activityLogs()->latest()->take(20)->get();
        $backups = $site->backups()->latest()->get();
        $dbSize = $this->docker->getDatabaseSize($site->db_name);

        $site->update([
            'db_size' => $dbSize,
            'wp_version' => $info['wp_version'],
            'plugins' => $info['plugins'],
            'themes' => $info['themes'],
        ]);

        return view('sites.show', compact('site', 'info', 'logs', 'backups'));
    }

    /**
     * Remover site
     */
    public function destroy(Request $request, Site $site)
    {
        $keepDb = $request->boolean('keep_db', false);
        $sitePath = '/var/www/sites/' . $site->name;
        $siteExists = is_dir($sitePath) && file_exists($sitePath . '/wp-config.php');

        if ($siteExists) {
            $result = $this->wordpress->removeSite($site->name, $keepDb);
        } else {
            // Site já não existe no filesystem — apenas limpar o registro
            $result = ['success' => true, 'output' => 'Site já removido do filesystem.'];
        }

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        if ($result['success']) {
            $site->delete();
            return redirect()->route('sites.index')
                ->with('success', "Site '{$site->name}' removido com sucesso!");
        }

        return back()->with('error', 'Erro ao remover site.')
            ->with('error_detail', $result['output']);
    }

    /**
     * Backup de um site
     */
    public function backup(Site $site)
    {
        $result = $this->wordpress->backupSite($site->name);

        return back()->with(
            $result['success'] ? 'success' : 'error',
            $result['success'] ? 'Backup criado com sucesso!' : 'Erro ao criar backup.'
        );
    }

    /**
     * Clonar site
     */
    public function clone(Request $request, Site $site)
    {
        $validated = $request->validate([
            'target_name' => ['required', 'string', 'regex:/^[a-z0-9][a-z0-9-]*[a-z0-9]$|^[a-z0-9]$/', 'unique:sites,name'],
        ]);

        $result = $this->wordpress->cloneSite($site->name, $validated['target_name']);

        return back()->with(
            $result['success'] ? 'success' : 'error',
            $result['success']
                ? "Site clonado para '{$validated['target_name']}' com sucesso!"
                : 'Erro ao clonar site.'
        );
    }

    /**
     * Ativar plugin de um site
     */
    public function pluginActivate(Request $request, Site $site, string $plugin)
    {
        $result = $this->wordpress->activatePlugin($site->name, $plugin);

        return back()->with(
            $result['success'] ? 'success' : 'error',
            $result['success'] ? "Plugin '{$plugin}' ativado com sucesso!" : "Erro ao ativar plugin: {$result['output']}"
        );
    }

    /**
     * Desativar plugin de um site
     */
    public function pluginDeactivate(Request $request, Site $site, string $plugin)
    {
        $result = $this->wordpress->deactivatePlugin($site->name, $plugin);

        return back()->with(
            $result['success'] ? 'success' : 'error',
            $result['success'] ? "Plugin '{$plugin}' desativado com sucesso!" : "Erro ao desativar plugin: {$result['output']}"
        );
    }

    /**
     * Remover plugin de um site
     */
    public function pluginDestroy(Request $request, Site $site, string $plugin)
    {
        $result = $this->wordpress->deletePlugin($site->name, $plugin);

        return back()->with(
            $result['success'] ? 'success' : 'error',
            $result['success'] ? "Plugin '{$plugin}' removido com sucesso!" : "Erro ao remover plugin: {$result['output']}"
        );
    }

    // ==========================================
    // API Endpoints (usados pelo wp-manager.sh)
    // ==========================================

    /**
     * API: Registrar site
     */
    public function apiStore(Request $request): JsonResponse
    {
        $site = Site::updateOrCreate(
            ['name' => $request->input('name')],
            $request->only(['url', 'db_name', 'wp_version', 'admin_user', 'admin_email', 'status'])
        );

        ActivityLog::create([
            'site_id' => $site->id,
            'action' => 'created',
            'description' => "Site '{$site->name}' registrado via CLI",
        ]);

        return response()->json($site, 201);
    }

    /**
     * API: Remover registro de site
     */
    public function apiDestroy(string $name): JsonResponse
    {
        $site = Site::where('name', $name)->first();

        if ($site) {
            ActivityLog::create([
                'site_id' => $site->id,
                'action' => 'removed',
                'description' => "Site '{$name}' removido via CLI",
            ]);
            $site->delete();
        }

        return response()->json(['success' => true]);
    }
}
