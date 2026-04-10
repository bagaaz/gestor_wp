@extends('layouts.app')

@section('title', $site->name . ' - WP Docker Manager')
@section('heading', 'Site: ' . $site->name)

@section('actions')
    <div class="flex items-center gap-2">
        <a href="{{ $site->url }}" target="_blank"
           class="inline-flex items-center gap-2 bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-50">
            <i class="fas fa-external-link-alt"></i> Abrir Site
        </a>
        <a href="{{ $site->url }}/wp-admin" target="_blank"
           class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700">
            <i class="fas fa-cog"></i> WP Admin
        </a>
    </div>
@endsection

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Info -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Site Info -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Informações do Site</h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wider">URL</p>
                            <a href="{{ $site->url }}" target="_blank" class="text-sm text-blue-600 hover:underline">{{ $site->url }}</a>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wider">Status</p>
                            <span class="inline-flex items-center gap-1 text-sm
                                {{ $site->status === 'active' ? 'text-green-600' : 'text-gray-500' }}">
                                <span class="w-2 h-2 rounded-full {{ $site->status === 'active' ? 'bg-green-500' : 'bg-gray-400' }}"></span>
                                {{ $site->status === 'active' ? 'Ativo' : 'Inativo' }}
                            </span>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wider">WordPress</p>
                            <p class="text-sm text-gray-900">{{ $site->wp_version }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wider">PHP</p>
                            <p class="text-sm text-gray-900">{{ $site->php_version }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wider">Banco de Dados</p>
                            <p class="text-sm text-gray-900">{{ $site->db_name }} ({{ $site->formatted_db_size }})</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wider">Uso de Disco</p>
                            <p class="text-sm text-gray-900">{{ $site->formatted_disk_usage }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wider">Admin</p>
                            <p class="text-sm text-gray-900">{{ $site->admin_user }} ({{ $site->admin_email }})</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wider">Criado em</p>
                            <p class="text-sm text-gray-900">{{ $site->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Plugins -->
            @if(!empty($info['plugins']))
                <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Plugins ({{ count($info['plugins']) }})</h3>
                    </div>
                    <div class="divide-y divide-gray-100">
                        @foreach($info['plugins'] as $plugin)
                            <div class="px-6 py-3 flex items-center justify-between">
                                <div>
                                    <span class="text-sm font-medium text-gray-900">{{ $plugin['name'] ?? 'N/A' }}</span>
                                    <span class="text-xs text-gray-400 ml-2">v{{ $plugin['version'] ?? '?' }}</span>
                                </div>
                                <span class="text-xs px-2 py-0.5 rounded
                                    {{ ($plugin['status'] ?? '') === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                    {{ $plugin['status'] ?? 'inactive' }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Actions -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-sm font-semibold text-gray-900 mb-4">Ações</h3>
                <div class="space-y-2">
                    <form action="{{ route('sites.backup', $site) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <i class="fas fa-download w-5 text-center text-gray-400"></i>
                            Fazer Backup
                        </button>
                    </form>

                    <div x-data="{ showClone: false }">
                        <button @click="showClone = !showClone" class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <i class="fas fa-clone w-5 text-center text-gray-400"></i>
                            Clonar Site
                        </button>
                        <form x-show="showClone" x-cloak action="{{ route('sites.clone', $site) }}" method="POST" class="mt-2 space-y-2">
                            @csrf
                            <input type="text" name="target_name" placeholder="nome-do-clone" required
                                   class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                            <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg text-sm hover:bg-blue-700">
                                Clonar
                            </button>
                        </form>
                    </div>

                    <a href="{{ route('sites.export', $site) }}"
                       class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-green-700 bg-green-50 rounded-lg hover:bg-green-100 transition-colors">
                        <i class="fas fa-file-archive w-5 text-center"></i>
                        Exportar para Produção
                    </a>

                    <a href="http://localhost:8080/index.php?db={{ $site->db_name }}" target="_blank"
                       class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                        <i class="fas fa-database w-5 text-center text-gray-400"></i>
                        phpMyAdmin
                        <i class="fas fa-external-link-alt text-xs ml-auto text-gray-300"></i>
                    </a>

                    <hr class="my-3">

                    <form action="{{ route('sites.destroy', $site) }}" method="POST"
                          onsubmit="return confirm('ATENÇÃO: Isso vai remover o site {{ $site->name }} e seu banco de dados. Continuar?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition-colors">
                            <i class="fas fa-trash-alt w-5 text-center"></i>
                            Remover Site
                        </button>
                    </form>
                </div>
            </div>

            <!-- Backups -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-sm font-semibold text-gray-900">Backups</h3>
                </div>
                <div class="divide-y divide-gray-100">
                    @forelse($backups as $backup)
                        <div class="px-6 py-3">
                            <p class="text-sm text-gray-700">{{ $backup->created_at->format('d/m/Y H:i') }}</p>
                            <p class="text-xs text-gray-400">{{ $backup->type }}</p>
                        </div>
                    @empty
                        <div class="px-6 py-6 text-center text-gray-400 text-sm">
                            Nenhum backup registrado.
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Activity -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-sm font-semibold text-gray-900">Histórico</h3>
                </div>
                <div class="divide-y divide-gray-100 max-h-64 overflow-y-auto">
                    @foreach($logs as $log)
                        <div class="px-6 py-3">
                            <p class="text-sm text-gray-700">{{ $log->description }}</p>
                            <p class="text-xs text-gray-400">{{ $log->created_at->diffForHumans() }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection
