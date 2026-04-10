<?php

namespace App\Http\Controllers;

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
     * Criar novo site
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

        $result = $this->wordpress->createSite($validated);

        if ($request->expectsJson()) {
            return response()->json($result, $result['success'] ? 201 : 422);
        }

        if ($result['success']) {
            return redirect()->route('sites.index')
                ->with('success', "Site '{$validated['name']}' criado com sucesso!");
        }

        return back()->with('error', 'Erro ao criar site.')
            ->with('error_detail', $result['output'])
            ->withInput();
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
