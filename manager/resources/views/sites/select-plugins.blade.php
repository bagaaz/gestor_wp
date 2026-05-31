@extends('layouts.app')

@section('title', 'Selecionar Plugins - WP Docker Manager')
@section('heading', 'Selecionar Plugins')

@section('content')
    <div class="max-w-2xl">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Plugins para o site "{{ $siteData['name'] }}"</h3>
                <p class="text-sm text-gray-500 mt-1">Selecione os plugins que deseja instalar e ativar neste site.</p>
            </div>

            <form action="{{ route('sites.store-with-plugins') }}" method="POST" x-data="{ loading: false, selectAll: false }" @submit="loading = true">
                @csrf

                <div class="p-6">
                    <!-- Selecionar todos -->
                    <label class="flex items-center gap-3 cursor-pointer pb-4 border-b border-gray-200 mb-4">
                        <input type="checkbox" x-model="selectAll"
                               @change="document.querySelectorAll('.plugin-checkbox').forEach(cb => cb.checked = selectAll)"
                               class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm font-medium text-gray-700">Selecionar todos</span>
                    </label>

                    <!-- Lista de plugins -->
                    <div class="space-y-3">
                        @foreach($plugins as $plugin)
                            <label class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:border-blue-300 hover:bg-blue-50/50 cursor-pointer transition-colors">
                                <input type="checkbox" name="plugins[]" value="{{ $plugin->id }}" class="plugin-checkbox w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0
                                    {{ $plugin->source === 'repository' ? 'bg-blue-100' : 'bg-purple-100' }}">
                                    <i class="fas {{ $plugin->source === 'repository' ? 'fa-globe text-blue-600' : 'fa-file-archive text-purple-600' }} text-sm"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <span class="text-sm font-medium text-gray-900">{{ $plugin->name }}</span>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <code class="text-xs text-gray-500 bg-gray-100 px-1.5 py-0.5 rounded">{{ $plugin->slug }}</code>
                                        <span class="text-xs text-gray-400">
                                            {{ $plugin->source === 'repository' ? 'WordPress.org' : 'Upload' }}
                                        </span>
                                    </div>
                                    @if($plugin->description)
                                        <p class="text-xs text-gray-400 mt-0.5">{{ $plugin->description }}</p>
                                    @endif
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- Resumo do site -->
                <div class="mx-6 mb-6 bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Resumo da criacao</h4>
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <span class="text-gray-500">Site:</span>
                        <span class="text-gray-900 font-medium">{{ $siteData['name'] }}.{{ config('wp.base_domain') }}</span>
                        <span class="text-gray-500">WordPress:</span>
                        <span class="text-gray-900">{{ $siteData['version'] ?? 'latest' }}</span>
                        @if(!empty($siteData['woocommerce']))
                            <span class="text-gray-500">WooCommerce:</span>
                            <span class="text-green-600 font-medium">Sim</span>
                        @endif
                        @if(!empty($siteData['multisite']))
                            <span class="text-gray-500">Multisite:</span>
                            <span class="text-green-600 font-medium">Sim</span>
                        @endif
                    </div>
                </div>

                <!-- Submit -->
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 rounded-b-xl flex items-center gap-3">
                    <button type="submit"
                            :disabled="loading"
                            class="inline-flex items-center gap-2 bg-blue-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        <template x-if="!loading">
                            <span class="flex items-center gap-2"><i class="fas fa-plus"></i> Criar Site</span>
                        </template>
                        <template x-if="loading">
                            <span class="flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                Criando... (pode levar alguns minutos)
                            </span>
                        </template>
                    </button>

                    <a href="{{ route('sites.create') }}" class="px-4 py-2.5 text-sm text-gray-600 hover:text-gray-800">
                        Voltar
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection
