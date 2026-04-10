@extends('layouts.app')

@section('title', 'Exportar para Produção - ' . $site->name)
@section('heading', 'Exportar: ' . $site->name)

@section('actions')
    <a href="{{ route('sites.show', $site) }}" class="inline-flex items-center gap-2 bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-50">
        <i class="fas fa-arrow-left"></i> Voltar
    </a>
@endsection

@section('content')
    <div class="max-w-3xl">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Gerar ZIP para Produção</h3>
                <p class="text-sm text-gray-500 mt-1">
                    Gera um pacote pronto para deploy com o domínio e credenciais de produção.
                    O ZIP inclui: arquivos WordPress, banco de dados (SQL com search-replace),
                    wp-config.php otimizado e .htaccess com segurança.
                </p>
            </div>

            <form action="{{ route('sites.export.generate', $site) }}" method="POST" class="p-6 space-y-6"
                  x-data="{ loading: false, protocol: 'https' }" @submit="loading = true">
                @csrf

                <!-- Domínio de Produção -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-5">
                    <h4 class="text-sm font-semibold text-blue-800 mb-3">
                        <i class="fas fa-globe mr-1"></i> Domínio de Produção
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-blue-700 mb-1">Protocolo</label>
                            <select name="prod_protocol" x-model="protocol"
                                    class="w-full rounded-lg border border-blue-300 px-3 py-2.5 text-sm bg-white focus:ring-2 focus:ring-blue-500 outline-none">
                                <option value="https" selected>https://</option>
                                <option value="http">http://</option>
                            </select>
                        </div>
                        <div class="md:col-span-3">
                            <label class="block text-xs font-medium text-blue-700 mb-1">Domínio <span class="text-red-500">*</span></label>
                            <input type="text" name="prod_domain" required
                                   placeholder="www.meusite.com.br"
                                   class="w-full rounded-lg border border-blue-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 outline-none"
                                   value="{{ old('prod_domain') }}">
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-blue-600">
                        Local: <code class="bg-blue-100 px-1 rounded">{{ $site->url }}</code>
                        &rarr; Produção: <code class="bg-blue-100 px-1 rounded" x-text="protocol + '://' + ($refs?.domain?.value || 'www.meusite.com.br')">https://www.meusite.com.br</code>
                    </p>
                </div>

                <!-- Banco de Dados de Produção -->
                <div class="bg-orange-50 border border-orange-200 rounded-lg p-5">
                    <h4 class="text-sm font-semibold text-orange-800 mb-3">
                        <i class="fas fa-database mr-1"></i> Banco de Dados de Produção
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-orange-700 mb-1">Host do MySQL <span class="text-red-500">*</span></label>
                            <input type="text" name="prod_db_host" required
                                   placeholder="localhost" value="{{ old('prod_db_host', 'localhost') }}"
                                   class="w-full rounded-lg border border-orange-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-orange-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-orange-700 mb-1">Nome do Banco <span class="text-red-500">*</span></label>
                            <input type="text" name="prod_db_name" required
                                   placeholder="meusite_wp" value="{{ old('prod_db_name') }}"
                                   class="w-full rounded-lg border border-orange-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-orange-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-orange-700 mb-1">Usuário do Banco <span class="text-red-500">*</span></label>
                            <input type="text" name="prod_db_user" required
                                   placeholder="meusite_user" value="{{ old('prod_db_user') }}"
                                   class="w-full rounded-lg border border-orange-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-orange-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-orange-700 mb-1">Senha do Banco <span class="text-red-500">*</span></label>
                            <div x-data="{ show: false }" class="relative">
                                <input :type="show ? 'text' : 'password'" name="prod_db_password" required
                                       placeholder="senha-segura-aqui"
                                       class="w-full rounded-lg border border-orange-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-orange-500 outline-none pr-10">
                                <button type="button" @click="show = !show" class="absolute right-3 top-1/2 -translate-y-1/2 text-orange-400 hover:text-orange-600">
                                    <i class="fas" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
                                </button>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-orange-700 mb-1">Prefixo das Tabelas</label>
                            <input type="text" name="prod_db_prefix"
                                   placeholder="wp_" value="{{ old('prod_db_prefix', 'wp_') }}"
                                   class="w-full rounded-lg border border-orange-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-orange-500 outline-none">
                            <p class="mt-1 text-xs text-orange-500">Recomendado usar um prefixo diferente de "wp_" por segurança.</p>
                        </div>
                    </div>
                </div>

                <!-- Opções de Produção -->
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-5">
                    <h4 class="text-sm font-semibold text-gray-800 mb-3">
                        <i class="fas fa-sliders-h mr-1"></i> Opções de Produção
                    </h4>
                    <div class="space-y-3">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" name="disable_debug" value="1" checked
                                   class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <div>
                                <span class="text-sm text-gray-700 font-medium">Desabilitar WP_DEBUG</span>
                                <p class="text-xs text-gray-400">Recomendado para produção</p>
                            </div>
                        </label>
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" name="enable_cache" value="1"
                                   class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <div>
                                <span class="text-sm text-gray-700 font-medium">Habilitar WP_CACHE</span>
                                <p class="text-xs text-gray-400">Necessário instalar plugin de cache (ex: WP Super Cache)</p>
                            </div>
                        </label>
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" name="remove_dev_plugins" value="1"
                                   class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <div>
                                <span class="text-sm text-gray-700 font-medium">Remover plugins de desenvolvimento</span>
                                <p class="text-xs text-gray-400">Remove Query Monitor, Debug Bar e similares</p>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- O que será incluído no ZIP -->
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-green-800 flex items-center gap-2">
                        <i class="fas fa-file-archive"></i> O que será incluído no ZIP
                    </h4>
                    <ul class="mt-2 text-xs text-green-700 space-y-1">
                        <li><i class="fas fa-check mr-1"></i> Todos os arquivos do WordPress (core + wp-content)</li>
                        <li><i class="fas fa-check mr-1"></i> <code>database.sql</code> com URLs já substituídas para produção</li>
                        <li><i class="fas fa-check mr-1"></i> <code>wp-config.php</code> de produção (novas salt keys, debug off)</li>
                        <li><i class="fas fa-check mr-1"></i> <code>.htaccess</code> com segurança e cache otimizados</li>
                        <li><i class="fas fa-check mr-1"></i> mu-plugin de segurança (sem SMTP dev, sem badge LOCAL)</li>
                        <li><i class="fas fa-check mr-1"></i> Logo customizada mantida no login</li>
                        <li><i class="fas fa-check mr-1"></i> Arquivos desnecessários removidos (readme, license, etc)</li>
                    </ul>
                </div>

                <!-- Submit -->
                <div class="flex items-center gap-3 pt-4 border-t border-gray-200">
                    <button type="submit"
                            :disabled="loading"
                            class="inline-flex items-center gap-2 bg-green-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-green-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        <template x-if="!loading">
                            <span class="flex items-center gap-2"><i class="fas fa-file-archive"></i> Gerar ZIP de Produção</span>
                        </template>
                        <template x-if="loading">
                            <span class="flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                Gerando ZIP... (pode levar alguns minutos)
                            </span>
                        </template>
                    </button>

                    <a href="{{ route('sites.show', $site) }}" class="px-4 py-2.5 text-sm text-gray-600 hover:text-gray-800">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>

        <!-- Instruções pós-export -->
        <div class="mt-6 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h4 class="text-sm font-semibold text-gray-900 mb-3">
                <i class="fas fa-info-circle mr-1 text-blue-500"></i> Depois de baixar o ZIP
            </h4>
            <ol class="text-sm text-gray-600 space-y-2 list-decimal list-inside">
                <li>Extraia o ZIP no diretório <code class="bg-gray-100 px-1 rounded">public_html</code> do servidor</li>
                <li>Crie o banco de dados no servidor com os dados informados acima</li>
                <li>Importe o arquivo <code class="bg-gray-100 px-1 rounded">database.sql</code> no banco via phpMyAdmin ou CLI</li>
                <li>Verifique as permissões: pastas <code class="bg-gray-100 px-1 rounded">755</code>, arquivos <code class="bg-gray-100 px-1 rounded">644</code></li>
                <li>Acesse o site e verifique se tudo está funcionando</li>
                <li>Apague o arquivo <code class="bg-gray-100 px-1 rounded">database.sql</code> do servidor por segurança</li>
                <li>Instale um certificado SSL se estiver usando HTTPS</li>
            </ol>
        </div>
    </div>
@endsection
