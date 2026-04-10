@extends('layouts.app')

@section('title', 'Logs - WP Docker Manager')
@section('heading', 'Logs do Sistema')

@section('content')
    <!-- Tabs -->
    <div class="mb-6">
        <nav class="flex gap-1 bg-white rounded-lg shadow-sm border border-gray-200 p-1 inline-flex">
            <a href="{{ route('logs.index', ['tab' => 'activity']) }}"
               class="px-4 py-2 rounded-md text-sm font-medium transition-colors
                      {{ $tab === 'activity' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                <i class="fas fa-list-ul mr-1"></i> Atividades
            </a>
            <a href="{{ route('logs.index', ['tab' => 'laravel']) }}"
               class="px-4 py-2 rounded-md text-sm font-medium transition-colors
                      {{ $tab === 'laravel' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                <i class="fas fa-file-alt mr-1"></i> Log do Laravel
            </a>
        </nav>
    </div>

    @if($tab === 'activity')
        <!-- Activity Logs -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Registro de Atividades</h3>
                <span class="text-sm text-gray-500">{{ $activityLogs->total() }} registros</span>
            </div>

            @if($activityLogs->isEmpty())
                <div class="p-12 text-center text-gray-400">
                    <i class="fas fa-clipboard-list text-4xl mb-3"></i>
                    <p>Nenhuma atividade registrada.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-600 text-xs uppercase tracking-wider">
                            <tr>
                                <th class="px-6 py-3 text-left">Data</th>
                                <th class="px-6 py-3 text-left">Acao</th>
                                <th class="px-6 py-3 text-left">Site</th>
                                <th class="px-6 py-3 text-left">Descricao</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($activityLogs as $log)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-3 text-gray-500 whitespace-nowrap">
                                        {{ $log->created_at->format('d/m/Y H:i:s') }}
                                    </td>
                                    <td class="px-6 py-3">
                                        @php
                                            $actionColors = [
                                                'created' => 'bg-green-100 text-green-700',
                                                'removed' => 'bg-red-100 text-red-700',
                                                'error' => 'bg-red-100 text-red-700',
                                                'backup' => 'bg-blue-100 text-blue-700',
                                                'cloned' => 'bg-purple-100 text-purple-700',
                                            ];
                                            $actionIcons = [
                                                'created' => 'fa-plus-circle',
                                                'removed' => 'fa-minus-circle',
                                                'error' => 'fa-exclamation-triangle',
                                                'backup' => 'fa-download',
                                                'cloned' => 'fa-clone',
                                            ];
                                            $color = $actionColors[$log->action] ?? 'bg-gray-100 text-gray-700';
                                            $icon = $actionIcons[$log->action] ?? 'fa-info-circle';
                                        @endphp
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium {{ $color }}">
                                            <i class="fas {{ $icon }}"></i>
                                            {{ $log->action }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-3 text-gray-700">
                                        {{ $log->site?->name ?? '-' }}
                                    </td>
                                    <td class="px-6 py-3 text-gray-600 max-w-md truncate" title="{{ $log->description }}">
                                        {{ $log->description }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $activityLogs->appends(['tab' => 'activity'])->links() }}
                </div>
            @endif
        </div>
    @else
        <!-- Laravel Log -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Log do Laravel</h3>
                <div class="flex items-center gap-3">
                    <span class="text-xs text-gray-400">storage/logs/laravel.log</span>
                    <form action="{{ route('logs.clear-laravel') }}" method="POST"
                          onsubmit="return confirm('Tem certeza que deseja limpar o log?')">
                        @csrf
                        <button type="submit" class="text-xs text-red-600 hover:text-red-800 font-medium">
                            <i class="fas fa-trash-alt mr-1"></i> Limpar
                        </button>
                    </form>
                </div>
            </div>

            @if(empty($laravelLog))
                <div class="p-12 text-center text-gray-400">
                    <i class="fas fa-file-alt text-4xl mb-3"></i>
                    <p>Log vazio.</p>
                </div>
            @else
                <div class="p-4">
                    <pre class="bg-gray-900 text-green-400 text-xs p-4 rounded-lg overflow-x-auto max-h-[600px] overflow-y-auto leading-relaxed whitespace-pre-wrap break-words">{{ $laravelLog }}</pre>
                </div>
            @endif
        </div>
    @endif
@endsection
