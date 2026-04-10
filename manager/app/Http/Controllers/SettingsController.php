<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = Setting::all()->pluck('value', 'key')->toArray();

        // Verificar se existe logo customizada
        $logoPath = '/var/www/sites/../docker/assets/login-logo.svg';
        $hasLogo = file_exists(base_path('../docker/assets/login-logo.svg'));

        return view('settings.index', compact('settings', 'hasLogo'));
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

        return redirect()->route('settings.index')
            ->with('success', 'Logo atualizada em todos os sites!');
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
