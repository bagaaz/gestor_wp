<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EvolutionService
{
    private string $apiUrl;
    private string $apiKey;

    public function __construct()
    {
        $this->apiUrl = rtrim(config('wp.evolution_api_url', ''), '/');
        $this->apiKey = config('wp.evolution_global_api_key', '');
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiUrl) && !empty($this->apiKey);
    }

    /**
     * Retorna todas as instâncias cadastradas na Evolution Go.
     * Cada item: ['name', 'id', 'token', 'connected']
     * Retorna null em caso de falha de comunicação.
     */
    public function fetchInstances(): ?array
    {
        if (empty($this->apiUrl) || empty($this->apiKey)) {
            return null;
        }

        try {
            $response = Http::withHeaders(['apikey' => $this->apiKey])
                ->get("{$this->apiUrl}/instance/all");

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json('data', []);

            return collect($data)->map(fn($i) => [
                'name'      => $i['name'] ?? '',
                'id'        => $i['id'] ?? '',
                'token'     => $i['token'] ?? '',
                'connected' => (bool) ($i['connected'] ?? false),
            ])->values()->all();
        } catch (\Throwable $e) {
            Log::warning('Evolution API: falha ao buscar instâncias', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function sendText(string $instance, string $number, string $text): bool
    {
        if (!$this->isConfigured() || empty($instance) || empty($number)) {
            return false;
        }

        // Token da instância tem prioridade sobre a global API key do .env
        $apiKey = \App\Models\Setting::get('whatsapp_instance_token') ?: $this->apiKey;

        // Normaliza o número: mantém apenas dígitos
        $number = preg_replace('/\D/', '', $number);

        try {
            $response = Http::withHeaders([
                'apikey'       => $apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->apiUrl}/send/text", [
                'number' => $number,
                'text'   => $text,
            ]);

            if (!$response->successful()) {
                Log::warning('Evolution API: falha ao enviar mensagem', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('Evolution API: exceção ao enviar mensagem', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
