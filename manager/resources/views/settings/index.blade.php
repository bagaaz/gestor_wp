@extends('layouts.app')

@section('title', 'Configurações - WP Docker Manager')
@section('heading', 'Configurações')

@section('content')
    @php $tab = request('tab', session('active_tab', 'general')); @endphp

    <!-- Tabs -->
    <div class="mb-6">
        <nav class="flex gap-1 bg-white rounded-lg shadow-sm border border-gray-200 p-1 inline-flex">
            <a href="{{ route('settings.index', ['tab' => 'general']) }}"
               class="px-4 py-2 rounded-md text-sm font-medium transition-colors
                      {{ $tab === 'general' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                <i class="fas fa-sliders-h mr-1"></i> Geral
            </a>
            <a href="{{ route('settings.index', ['tab' => 'php']) }}"
               class="px-4 py-2 rounded-md text-sm font-medium transition-colors
                      {{ $tab === 'php' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                <i class="fab fa-php mr-1"></i> PHP
            </a>
            <a href="{{ route('settings.index', ['tab' => 'plugins']) }}"
               class="px-4 py-2 rounded-md text-sm font-medium transition-colors
                      {{ $tab === 'plugins' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                <i class="fas fa-puzzle-piece mr-1"></i> Plugins
            </a>
            <a href="{{ route('settings.index', ['tab' => 'login']) }}"
               class="px-4 py-2 rounded-md text-sm font-medium transition-colors
                      {{ $tab === 'login' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                <i class="fas fa-paint-brush mr-1"></i> Login
            </a>
            <a href="{{ route('settings.index', ['tab' => 'whatsapp']) }}"
               class="px-4 py-2 rounded-md text-sm font-medium transition-colors
                      {{ $tab === 'whatsapp' ? 'bg-green-600 text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                <i class="fab fa-whatsapp mr-1"></i> WhatsApp
            </a>
        </nav>
    </div>

    {{-- ===== TAB: GERAL ===== --}}
    @if($tab === 'general')
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Credenciais WordPress -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Credenciais WordPress</h3>
                    <p class="text-sm text-gray-500 mt-1">Credenciais padrão usadas ao criar novos sites.</p>
                </div>
                <form action="{{ route('settings.update') }}" method="POST" class="p-6 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Usuário Admin</label>
                        <input type="text" name="default_admin_user"
                               value="{{ $settings['default_admin_user'] ?? 'devconecta' }}"
                               class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Senha Admin</label>
                        <div x-data="{ show: false }" class="relative">
                            <input :type="show ? 'text' : 'password'" name="default_admin_password"
                                   value="{{ $settings['default_admin_password'] ?? 'Ga96911431@' }}"
                                   class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none pr-10">
                            <button type="button" @click="show = !show" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <i class="fas" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email Admin</label>
                        <input type="email" name="default_admin_email"
                               value="{{ $settings['default_admin_email'] ?? 'admin@localhost.test' }}"
                               class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    </div>
                    <button type="submit" class="w-full bg-blue-600 text-white py-2.5 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors">
                        <i class="fas fa-save mr-1"></i> Salvar Credenciais WordPress
                    </button>
                </form>
            </div>

            <!-- Credenciais MySQL -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Credenciais MySQL</h3>
                    <p class="text-sm text-gray-500 mt-1">Credenciais do banco de dados local.</p>
                </div>
                <form action="{{ route('settings.update') }}" method="POST" class="p-6 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Senha Root</label>
                        <div x-data="{ show: false }" class="relative">
                            <input :type="show ? 'text' : 'password'" name="mysql_root_password"
                                   value="{{ $settings['mysql_root_password'] ?? 'root' }}"
                                   class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none pr-10">
                            <button type="button" @click="show = !show" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <i class="fas" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Usuário WordPress</label>
                        <input type="text" name="mysql_user"
                               value="{{ $settings['mysql_user'] ?? 'wordpress' }}"
                               class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Senha WordPress</label>
                        <div x-data="{ show: false }" class="relative">
                            <input :type="show ? 'text' : 'password'" name="mysql_password"
                                   value="{{ $settings['mysql_password'] ?? 'wordpress' }}"
                                   class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none pr-10">
                            <button type="button" @click="show = !show" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <i class="fas" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="w-full bg-blue-600 text-white py-2.5 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors">
                        <i class="fas fa-save mr-1"></i> Salvar Credenciais MySQL
                    </button>
                </form>

                <div class="px-6 pb-6">
                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                        <p class="text-xs text-amber-700">
                            <i class="fas fa-exclamation-triangle mr-1"></i>
                            Alterar credenciais MySQL aqui <strong>não</strong> altera o container em execução.
                            Estas são apenas referências visuais e valores padrão para novos sites.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Referência Rápida -->
            <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Referência Rápida</h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h4 class="text-sm font-semibold text-gray-700 mb-2"><i class="fab fa-wordpress mr-1"></i> WordPress</h4>
                            <div class="grid grid-cols-2 gap-2 text-sm">
                                <span class="text-gray-500">Usuário:</span>
                                <code class="text-gray-900 bg-white px-2 py-0.5 rounded font-mono text-xs">{{ $settings['default_admin_user'] ?? 'devconecta' }}</code>
                                <span class="text-gray-500">Senha:</span>
                                <code class="text-gray-900 bg-white px-2 py-0.5 rounded font-mono text-xs">{{ $settings['default_admin_password'] ?? 'Ga96911431@' }}</code>
                                <span class="text-gray-500">Email:</span>
                                <code class="text-gray-900 bg-white px-2 py-0.5 rounded font-mono text-xs">{{ $settings['default_admin_email'] ?? 'admin@localhost.test' }}</code>
                            </div>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h4 class="text-sm font-semibold text-gray-700 mb-2"><i class="fas fa-database mr-1"></i> MySQL</h4>
                            <div class="grid grid-cols-2 gap-2 text-sm">
                                <span class="text-gray-500">Host:</span>
                                <code class="text-gray-900 bg-white px-2 py-0.5 rounded font-mono text-xs">localhost:3309</code>
                                <span class="text-gray-500">Root:</span>
                                <code class="text-gray-900 bg-white px-2 py-0.5 rounded font-mono text-xs">root / {{ $settings['mysql_root_password'] ?? 'root' }}</code>
                            </div>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h4 class="text-sm font-semibold text-gray-700 mb-2"><i class="fas fa-link mr-1"></i> URLs</h4>
                            <div class="grid grid-cols-2 gap-2 text-sm">
                                <span class="text-gray-500">Painel:</span>
                                <a href="http://manager.localhost" class="text-blue-600 hover:underline text-xs">manager.localhost</a>
                                <span class="text-gray-500">phpMyAdmin:</span>
                                <a href="http://localhost:8080" target="_blank" class="text-blue-600 hover:underline text-xs">localhost:8080</a>
                                <span class="text-gray-500">Mailpit:</span>
                                <a href="http://localhost:8025" target="_blank" class="text-blue-600 hover:underline text-xs">localhost:8025</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    {{-- ===== TAB: PHP ===== --}}
    @elseif($tab === 'php')
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Formulário de configuração -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Configurações do PHP</h3>
                        <p class="text-sm text-gray-500 mt-1">Alterações são aplicadas imediatamente em todos os sites WordPress.</p>
                    </div>

                    <form action="{{ route('settings.php') }}" method="POST" class="p-6">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            @foreach($phpDirectives as $key => $meta)
                                <div>
                                    <label for="php_{{ $key }}" class="block text-sm font-medium text-gray-700 mb-1">
                                        {{ $meta['label'] }}
                                    </label>

                                    @if($meta['type'] === 'toggle')
                                        <select name="{{ $key }}" id="php_{{ $key }}"
                                                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white">
                                            <option value="On" {{ (old($key, $phpValues[$key] ?? 'On')) === 'On' ? 'selected' : '' }}>On</option>
                                            <option value="Off" {{ (old($key, $phpValues[$key] ?? 'On')) === 'Off' ? 'selected' : '' }}>Off</option>
                                        </select>
                                    @elseif($meta['type'] === 'size')
                                        <div class="relative">
                                            <input type="text" name="{{ $key }}" id="php_{{ $key }}"
                                                   value="{{ old($key, $phpValues[$key] ?? '') }}"
                                                   placeholder="256M"
                                                   pattern="[0-9]+[KMGkmg]?"
                                                   class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none pr-10
                                                          @error($key) border-red-400 @enderror">
                                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs">
                                                <i class="fas fa-memory"></i>
                                            </span>
                                        </div>
                                    @elseif($meta['type'] === 'seconds')
                                        <div class="relative">
                                            <input type="number" name="{{ $key }}" id="php_{{ $key }}"
                                                   value="{{ old($key, $phpValues[$key] ?? '') }}"
                                                   min="0"
                                                   class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none pr-10
                                                          @error($key) border-red-400 @enderror">
                                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs">seg</span>
                                        </div>
                                    @else
                                        <input type="number" name="{{ $key }}" id="php_{{ $key }}"
                                               value="{{ old($key, $phpValues[$key] ?? '') }}"
                                               min="1"
                                               class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none
                                                      @error($key) border-red-400 @enderror">
                                    @endif

                                    <p class="mt-1 text-xs text-gray-400">{{ $meta['hint'] }}</p>

                                    @error($key)
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-6 pt-5 border-t border-gray-200 flex items-center gap-4">
                            <button type="submit" class="inline-flex items-center gap-2 bg-blue-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors">
                                <i class="fas fa-save"></i> Salvar e Aplicar
                            </button>
                            <p class="text-xs text-gray-400">
                                <i class="fas fa-info-circle mr-1"></i>
                                PHP-FPM e Nginx serão recarregados automaticamente.
                            </p>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Valores ativos verificados -->
            <div class="space-y-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-sm font-semibold text-gray-900 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                            Valores Ativos no PHP
                        </h3>
                        <p class="text-xs text-gray-500 mt-1">Lidos em tempo real do container <code class="bg-gray-100 px-1 rounded">wp-php</code></p>
                    </div>
                    <div class="divide-y divide-gray-100">
                        @forelse($phpActiveValues as $key => $value)
                            @if(array_key_exists($key, $phpDirectives))
                                @php
                                    $fileValue = $phpValues[$key] ?? null;
                                    $match = $fileValue !== null && $fileValue === $value;
                                @endphp
                                <div class="px-6 py-3 flex items-center justify-between">
                                    <span class="text-xs text-gray-600">{{ $phpDirectives[$key]['label'] }}</span>
                                    <div class="flex items-center gap-2">
                                        <code class="text-sm font-mono font-semibold {{ $match ? 'text-green-700' : 'text-amber-700' }}">{{ $value }}</code>
                                        @if($match)
                                            <i class="fas fa-check-circle text-green-500 text-xs"></i>
                                        @else
                                            <i class="fas fa-exclamation-circle text-amber-500 text-xs" title="Difere do php.ini ({{ $fileValue }})"></i>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        @empty
                            <div class="px-6 py-8 text-center">
                                <i class="fas fa-exclamation-triangle text-amber-400 text-2xl mb-2"></i>
                                <p class="text-sm text-gray-500">Não foi possível ler os valores do container.</p>
                                <p class="text-xs text-gray-400 mt-1">Verifique se o container <code>wp-php</code> está rodando.</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Dica -->
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-5">
                    <h4 class="text-sm font-semibold text-blue-800 flex items-center gap-2 mb-2">
                        <i class="fas fa-lightbulb"></i> Como funciona
                    </h4>
                    <ul class="text-xs text-blue-700 space-y-2">
                        <li><i class="fas fa-check text-blue-500 mr-1"></i> O arquivo <code class="bg-blue-100 px-1 rounded">php.ini</code> é editado diretamente</li>
                        <li><i class="fas fa-check text-blue-500 mr-1"></i> O Nginx atualiza o <code class="bg-blue-100 px-1 rounded">client_max_body_size</code> para acompanhar o upload</li>
                        <li><i class="fas fa-check text-blue-500 mr-1"></i> PHP-FPM e Nginx são recarregados sem downtime</li>
                        <li><i class="fas fa-check text-blue-500 mr-1"></i> Os valores ativos são verificados em tempo real</li>
                    </ul>
                </div>
            </div>
        </div>

    {{-- ===== TAB: PLUGINS ===== --}}
    @elseif($tab === 'plugins')
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Formulário de cadastro -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Cadastrar Plugin</h3>
                        <p class="text-sm text-gray-500 mt-1">Adicione plugins para usar na criação de sites.</p>
                    </div>

                    <form action="{{ route('settings.plugins.store') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-4"
                          x-data="{ source: '{{ old('source', 'repository') }}' }">
                        @csrf

                        <!-- Tipo de fonte -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Fonte do plugin</label>
                            <div class="flex gap-3">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="source" value="repository" x-model="source"
                                           class="w-4 h-4 text-blue-600 focus:ring-blue-500">
                                    <span class="text-sm text-gray-700">WordPress.org</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="source" value="upload" x-model="source"
                                           class="w-4 h-4 text-blue-600 focus:ring-blue-500">
                                    <span class="text-sm text-gray-700">Upload ZIP</span>
                                </label>
                            </div>
                        </div>

                        <!-- Nome -->
                        <div>
                            <label for="plugin_name" class="block text-sm font-medium text-gray-700 mb-1">
                                Nome do plugin <span class="text-red-500">*</span>
                                <i class="fas fa-question-circle text-gray-400 ml-1 cursor-help" title="Apenas para identificacao no painel. O WordPress nao fornece essa info pelo slug antes da instalacao."></i>
                            </label>
                            <input type="text" name="name" id="plugin_name" required
                                   value="{{ old('name') }}"
                                   placeholder="Contact Form 7"
                                   class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                            @error('name')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Slug (repositório) -->
                        <div x-show="source === 'repository'">
                            <label for="plugin_slug" class="block text-sm font-medium text-gray-700 mb-1">
                                Slug do plugin <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="slug" id="plugin_slug"
                                   value="{{ old('slug') }}"
                                   placeholder="contact-form-7"
                                   class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                            <p class="mt-1 text-xs text-gray-400">Slug do wordpress.org (ex: contact-form-7)</p>
                            @error('slug')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Upload ZIP -->
                        <div x-show="source === 'upload'">
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Arquivo ZIP <span class="text-red-500">*</span>
                            </label>
                            <label class="flex items-center justify-center px-4 py-3 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:border-blue-400 hover:bg-blue-50 transition-colors">
                                <div class="text-center">
                                    <i class="fas fa-cloud-upload-alt text-gray-400 text-lg mb-1"></i>
                                    <p class="text-sm text-gray-600">Clique para selecionar o ZIP</p>
                                    <p class="text-xs text-gray-400">Arquivo .zip (max 50MB)</p>
                                </div>
                                <input type="file" name="plugin_file" accept=".zip" class="hidden"
                                       onchange="document.getElementById('plugin-filename').textContent = this.files[0]?.name || ''">
                            </label>
                            <p id="plugin-filename" class="mt-1 text-sm text-blue-600 font-medium"></p>
                            @error('plugin_file')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Descrição -->
                        <div>
                            <label for="plugin_description" class="block text-sm font-medium text-gray-700 mb-1">
                                Descrição
                                <i class="fas fa-question-circle text-gray-400 ml-1 cursor-help" title="Opcional. Ajuda a identificar o plugin na listagem e na tela de selecao ao criar sites."></i>
                            </label>
                            <input type="text" name="description" id="plugin_description"
                                   value="{{ old('description') }}"
                                   placeholder="Formulário de contato"
                                   class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        </div>

                        <button type="submit" class="w-full bg-blue-600 text-white py-2.5 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors">
                            <i class="fas fa-plus mr-1"></i> Cadastrar Plugin
                        </button>
                    </form>
                </div>
            </div>

            <!-- Lista de plugins cadastrados -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Plugins Cadastrados</h3>
                        <p class="text-sm text-gray-500 mt-1">Estes plugins ficam disponíveis para seleção ao criar um novo site.</p>
                    </div>

                    @if($plugins->isEmpty())
                        <div class="p-12 text-center">
                            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-puzzle-piece text-2xl text-gray-400"></i>
                            </div>
                            <h4 class="text-sm font-medium text-gray-900 mb-1">Nenhum plugin cadastrado</h4>
                            <p class="text-xs text-gray-500">Cadastre plugins para que eles apareçam na criação de sites.</p>
                        </div>
                    @else
                        <div class="divide-y divide-gray-100">
                            @foreach($plugins as $plugin)
                                <div class="px-6 py-4 flex items-center justify-between hover:bg-gray-50">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-lg flex items-center justify-center
                                            {{ $plugin->source === 'repository' ? 'bg-blue-100' : 'bg-purple-100' }}">
                                            <i class="fas {{ $plugin->source === 'repository' ? 'fa-globe text-blue-600' : 'fa-file-archive text-purple-600' }} text-sm"></i>
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-medium text-gray-900">{{ $plugin->name }}</h4>
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
                                    </div>

                                    <form id="delete-plugin-{{ $plugin->id }}" action="{{ route('settings.plugins.destroy', $plugin) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button"
                                                @click="$dispatch('confirm-action', {
                                                    title: 'Remover plugin',
                                                    message: 'Remover {{ $plugin->name }} do registro de plugins?',
                                                    action: 'delete-plugin-{{ $plugin->id }}'
                                                })"
                                                class="text-red-400 hover:text-red-600 p-2" title="Remover">
                                            <i class="fas fa-trash-alt text-sm"></i>
                                        </button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Dica -->
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-5 mt-6">
                    <h4 class="text-sm font-semibold text-blue-800 flex items-center gap-2 mb-2">
                        <i class="fas fa-lightbulb"></i> Como funciona
                    </h4>
                    <ul class="text-xs text-blue-700 space-y-2">
                        <li><i class="fas fa-check text-blue-500 mr-1"></i> Plugins cadastrados aqui aparecem como opção ao criar um novo site</li>
                        <li><i class="fas fa-check text-blue-500 mr-1"></i> <strong>WordPress.org</strong>: instala direto do repositório via WP-CLI</li>
                        <li><i class="fas fa-check text-blue-500 mr-1"></i> <strong>Upload</strong>: instala a partir do arquivo ZIP enviado</li>
                        <li><i class="fas fa-check text-blue-500 mr-1"></i> Todos são instalados e ativados automaticamente</li>
                    </ul>
                </div>
            </div>
        </div>

    {{-- ===== TAB: LOGIN ===== --}}
    @elseif($tab === 'login')
        @php
            $loginBg = $settings['login_bg_color'] ?? '#f5f5f5';
            $loginPrimary = $settings['login_primary_color'] ?? '#204AE3';
            $loginText = $settings['login_text_color'] ?? '#111317';
        @endphp

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6" x-data="{
            bg: '{{ $loginBg }}',
            primary: '{{ $loginPrimary }}',
            text: '{{ $loginText }}'
        }">
            <!-- Configurações -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Cores -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Cores da Tela de Login</h3>
                        <p class="text-sm text-gray-500 mt-1">Personalize as cores da tela de login (/wp-login.php) de todos os sites.</p>
                    </div>
                    <form action="{{ route('settings.login-colors') }}" method="POST" class="p-6 space-y-5">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                            <div>
                                <label for="login_primary_color" class="block text-sm font-medium text-gray-700 mb-1">Cor primaria</label>
                                <div class="flex items-center gap-2">
                                    <input type="color" name="login_primary_color" id="login_primary_color"
                                           x-model="primary"
                                           class="w-10 h-10 rounded-lg border border-gray-300 cursor-pointer p-0.5">
                                    <input type="text" x-model="primary" readonly
                                           class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm font-mono bg-gray-50 text-gray-700">
                                </div>
                                <p class="mt-1 text-xs text-gray-400">Botao, links hover, focus</p>
                            </div>

                            <div>
                                <label for="login_bg_color" class="block text-sm font-medium text-gray-700 mb-1">Cor de fundo</label>
                                <div class="flex items-center gap-2">
                                    <input type="color" name="login_bg_color" id="login_bg_color"
                                           x-model="bg"
                                           class="w-10 h-10 rounded-lg border border-gray-300 cursor-pointer p-0.5">
                                    <input type="text" x-model="bg" readonly
                                           class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm font-mono bg-gray-50 text-gray-700">
                                </div>
                                <p class="mt-1 text-xs text-gray-400">Fundo da pagina</p>
                            </div>

                            <div>
                                <label for="login_text_color" class="block text-sm font-medium text-gray-700 mb-1">Cor do texto</label>
                                <div class="flex items-center gap-2">
                                    <input type="color" name="login_text_color" id="login_text_color"
                                           x-model="text"
                                           class="w-10 h-10 rounded-lg border border-gray-300 cursor-pointer p-0.5">
                                    <input type="text" x-model="text" readonly
                                           class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm font-mono bg-gray-50 text-gray-700">
                                </div>
                                <p class="mt-1 text-xs text-gray-400">Labels, links, textos</p>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-gray-200">
                            <button type="submit" class="inline-flex items-center gap-2 bg-blue-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors">
                                <i class="fas fa-palette"></i> Salvar Cores e Aplicar em Todos os Sites
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Logo -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Logo do Login</h3>
                        <p class="text-sm text-gray-500 mt-1">Imagem exibida na tela de login de todos os sites.</p>
                    </div>
                    <div class="p-6">
                        <!-- Preview da logo atual -->
                        <div class="mb-6 p-6 bg-gray-100 rounded-xl flex flex-col items-center">
                            <p class="text-xs text-gray-500 mb-3 uppercase tracking-wider font-medium">Logo Atual</p>
                            @if($hasLogo)
                                <div class="bg-white rounded-lg p-4 shadow-sm w-full max-w-xs">
                                    <img src="data:image/svg+xml;base64,{{ base64_encode(file_get_contents(base_path('../docker/assets/login-logo.svg'))) }}"
                                         alt="Login Logo" class="w-full h-20 object-contain">
                                </div>
                            @else
                                <div class="bg-white rounded-lg p-8 shadow-sm text-center">
                                    <i class="fas fa-image text-4xl text-gray-300 mb-2"></i>
                                    <p class="text-sm text-gray-400">Nenhuma logo definida</p>
                                </div>
                            @endif
                        </div>

                        <form action="{{ route('settings.logo') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                            @csrf
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Enviar nova logo</label>
                                <label class="flex items-center justify-center px-4 py-3 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:border-blue-400 hover:bg-blue-50 transition-colors">
                                    <div class="text-center">
                                        <i class="fas fa-cloud-upload-alt text-gray-400 text-lg mb-1"></i>
                                        <p class="text-sm text-gray-600">Clique para selecionar</p>
                                        <p class="text-xs text-gray-400">SVG, PNG, JPG ou WebP (max 2MB)</p>
                                    </div>
                                    <input type="file" name="login_logo" accept=".svg,.png,.jpg,.jpeg,.webp" class="hidden"
                                           onchange="document.getElementById('logo-filename').textContent = this.files[0]?.name || ''">
                                </label>
                                <p id="logo-filename" class="mt-1 text-sm text-blue-600 font-medium"></p>
                                @error('login_logo')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <button type="submit" class="w-full bg-green-600 text-white py-2.5 rounded-lg text-sm font-medium hover:bg-green-700 transition-colors">
                                <i class="fas fa-upload mr-1"></i> Atualizar Logo em Todos os Sites
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Preview ao vivo -->
            <div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 sticky top-6">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-sm font-semibold text-gray-900 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                            Preview ao Vivo
                        </h3>
                    </div>
                    <div class="p-4">
                        <div class="rounded-lg overflow-hidden border border-gray-200" :style="'background-color: ' + bg">
                            <div class="p-6 flex flex-col items-center">
                                <!-- Logo placeholder -->
                                <div class="w-32 h-12 rounded mb-4 flex items-center justify-center" :style="'background-color: ' + primary">
                                    <span class="text-xs font-bold" :style="'color: ' + bg">LOGO</span>
                                </div>
                                <!-- Form -->
                                <div class="w-full bg-white rounded-lg p-4 shadow-sm border border-gray-200">
                                    <label class="block text-xs font-medium mb-1" :style="'color: ' + text">Usuario</label>
                                    <div class="w-full h-7 rounded border border-gray-300 mb-3 px-2 flex items-center">
                                        <span class="text-xs text-gray-400">devconecta</span>
                                    </div>
                                    <label class="block text-xs font-medium mb-1" :style="'color: ' + text">Senha</label>
                                    <div class="w-full h-7 rounded border border-gray-300 mb-3 px-2 flex items-center">
                                        <span class="text-xs text-gray-400">********</span>
                                    </div>
                                    <div class="flex items-center gap-1.5 mb-3">
                                        <div class="w-3.5 h-3.5 rounded border-2 flex items-center justify-center" :style="'border-color: ' + primary + '; background-color: ' + primary">
                                            <svg class="w-2.5 h-2.5 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg>
                                        </div>
                                        <span class="text-xs" :style="'color: ' + text">Lembrar-me</span>
                                    </div>
                                    <button class="w-full py-1.5 rounded text-xs font-semibold" :style="'background-color: ' + primary + '; color: ' + bg">
                                        Acessar
                                    </button>
                                </div>
                                <!-- Links -->
                                <div class="mt-3 text-center">
                                    <span class="text-xs" :style="'color: ' + text">Perdeu a senha?</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    {{-- ===== TAB: WHATSAPP ===== --}}
    @elseif($tab === 'whatsapp')
        @php
            $waPhone         = $settings['whatsapp_phone'] ?? '';
            $waInstance      = $settings['whatsapp_instance'] ?? '';
            $waInstanceToken = $settings['whatsapp_instance_token'] ?? '';
            $apiUrl          = config('wp.evolution_api_url');
            $apiKey          = config('wp.evolution_global_api_key');
            $apiReady        = !empty($apiUrl) && !empty($apiKey);
        @endphp

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Configurações -->
            <div class="lg:col-span-2 space-y-6">

                @if(!$apiReady)
                    {{-- Sem credenciais no .env: mostrar apenas o aviso --}}
                    <div class="bg-amber-50 border border-amber-300 rounded-xl p-5 flex items-start gap-3">
                        <i class="fas fa-exclamation-triangle text-amber-500 mt-0.5 text-lg"></i>
                        <div>
                            <p class="text-sm font-semibold text-amber-800">Variáveis de ambiente não configuradas</p>
                            <p class="text-xs text-amber-700 mt-1">
                                Configure as variáveis abaixo no <code class="bg-amber-100 px-1 rounded">.env</code> e execute
                                <code class="bg-amber-100 px-1 rounded">php artisan config:cache</code> para liberar as configurações de notificação.
                            </p>
                            <pre class="mt-3 text-xs bg-amber-100 text-amber-900 rounded p-3 font-mono leading-relaxed">EVOLUTION_API_URL=https://sua-evolution-api.com
EVOLUTION_GLOBAL_API_KEY=sua-global-api-key</pre>
                        </div>
                    </div>

                @else
                    {{-- API configurada --}}

                    {{-- Status da API --}}
                    @if($evolutionInstances === null)
                        <div class="bg-red-50 border border-red-200 rounded-xl p-4 flex items-start gap-3">
                            <i class="fas fa-times-circle text-red-400 mt-0.5"></i>
                            <div>
                                <p class="text-sm font-semibold text-red-800">Não foi possível conectar à Evolution API</p>
                                <p class="text-xs text-red-600 mt-1">
                                    Verifique se o servidor <code class="bg-red-100 px-1 rounded">{{ $apiUrl }}</code> está acessível e a chave global está correta.
                                </p>
                            </div>
                        </div>
                    @elseif(count($evolutionInstances) === 0)
                        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 flex items-center gap-3">
                            <i class="fas fa-exclamation-circle text-amber-400"></i>
                            <p class="text-sm text-amber-800">Nenhuma instância encontrada na Evolution API. Crie uma instância primeiro.</p>
                        </div>
                    @else
                        <div class="bg-green-50 border border-green-200 rounded-xl p-4 flex items-center gap-3">
                            <i class="fas fa-check-circle text-green-500"></i>
                            <div>
                                <p class="text-sm font-semibold text-green-800">Evolution API conectada</p>
                                <p class="text-xs text-green-700">
                                    {{ count($evolutionInstances) }} {{ count($evolutionInstances) === 1 ? 'instância encontrada' : 'instâncias encontradas' }}
                                    em <code class="bg-green-100 px-1 rounded">{{ $apiUrl }}</code>
                                </p>
                            </div>
                        </div>
                    @endif

                    {{-- Formulário (só aparece se houver instâncias) --}}
                    @if($evolutionInstances !== null && count($evolutionInstances) > 0)
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200"
                             x-data="{
                                instances: {!! Js::from($evolutionInstances) !!},
                                selectedName: '{{ old('whatsapp_instance', $waInstance) }}',
                                selectedToken: '{{ old('whatsapp_instance_token', $waInstanceToken) }}',
                                selectInstance(name) {
                                    this.selectedName = name;
                                    const inst = this.instances.find(i => i.name === name);
                                    this.selectedToken = inst ? inst.token : '';
                                }
                             }">
                            <div class="px-6 py-4 border-b border-gray-200">
                                <h3 class="text-lg font-semibold text-gray-900">
                                    <i class="fab fa-whatsapp text-green-500 mr-1"></i> Notificações WhatsApp
                                </h3>
                                <p class="text-sm text-gray-500 mt-1">
                                    Quando um novo site WordPress for criado, uma mensagem será enviada para o número configurado.
                                </p>
                            </div>

                            <form action="{{ route('settings.whatsapp') }}" method="POST" class="p-6 space-y-5">
                                @csrf

                                {{-- Número de destino --}}
                                <div>
                                    <label for="whatsapp_phone" class="block text-sm font-medium text-gray-700 mb-1">
                                        Número de destino <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="whatsapp_phone" id="whatsapp_phone"
                                           value="{{ old('whatsapp_phone', $waPhone) }}"
                                           placeholder="5527998700053"
                                           class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none font-mono
                                                  @error('whatsapp_phone') border-red-400 @enderror">
                                    <p class="mt-1 text-xs text-gray-400">Apenas dígitos com código do país. Ex: <code>5527998700053</code></p>
                                    @error('whatsapp_phone')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Seleção de instância --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Instância <span class="text-red-500">*</span>
                                    </label>
                                    <div class="grid grid-cols-1 gap-2">
                                        <template x-for="inst in instances" :key="inst.name">
                                            <label class="flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition-colors"
                                                   :class="selectedName === inst.name
                                                       ? 'border-green-500 bg-green-50'
                                                       : 'border-gray-200 bg-white hover:border-gray-300'">
                                                <input type="radio"
                                                       class="text-green-600 focus:ring-green-500"
                                                       :checked="selectedName === inst.name"
                                                       @change="selectInstance(inst.name)">
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-center gap-2">
                                                        <span class="text-sm font-medium text-gray-900 font-mono" x-text="inst.name"></span>
                                                        <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full font-medium"
                                                              :class="inst.connected ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600'">
                                                            <span class="w-1.5 h-1.5 rounded-full inline-block"
                                                                  :class="inst.connected ? 'bg-green-500' : 'bg-red-400'"></span>
                                                            <span x-text="inst.connected ? 'Conectada' : 'Desconectada'"></span>
                                                        </span>
                                                    </div>
                                                    <p class="text-xs text-gray-400 font-mono truncate mt-0.5" x-text="inst.id"></p>
                                                </div>
                                            </label>
                                        </template>
                                    </div>
                                    {{-- Campos ocultos: enviados no form e preenchidos via Alpine --}}
                                    <input type="hidden" name="whatsapp_instance" :value="selectedName">
                                    <input type="hidden" name="whatsapp_instance_token" :value="selectedToken">
                                    @error('whatsapp_instance')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="pt-4 border-t border-gray-200 flex items-center gap-3">
                                    <button type="submit"
                                            class="inline-flex items-center gap-2 bg-green-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-green-700 transition-colors">
                                        <i class="fas fa-save"></i> Salvar
                                    </button>
                                </div>
                            </form>
                        </div>

                        {{-- Botão de teste --}}
                        @if($waPhone && $waInstance)
                            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                                <h4 class="text-sm font-semibold text-gray-900 mb-1">Testar notificação</h4>
                                <p class="text-xs text-gray-500 mb-4">
                                    Envia uma mensagem de teste para <code class="bg-gray-100 px-1 rounded">{{ $waPhone }}</code>
                                    via instância <code class="bg-gray-100 px-1 rounded">{{ $waInstance }}</code>.
                                </p>
                                <form action="{{ route('settings.whatsapp.test') }}" method="POST">
                                    @csrf
                                    <button type="submit"
                                            class="inline-flex items-center gap-2 bg-gray-800 text-white px-5 py-2 rounded-lg text-sm font-medium hover:bg-gray-700 transition-colors">
                                        <i class="fab fa-whatsapp"></i> Enviar mensagem de teste
                                    </button>
                                </form>
                            </div>
                        @endif
                    @endif

                @endif
            </div>

            <!-- Sidebar info -->
            <div class="space-y-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-sm font-semibold text-gray-900">Exemplo de notificação</h3>
                    </div>
                    <div class="p-5">
                        <div class="bg-[#e9fdd0] rounded-xl p-4 text-sm text-gray-800 font-sans leading-relaxed shadow-sm border border-gray-200">
                            <p>🚀 <strong>Novo site WordPress criado!</strong></p>
                            <br>
                            <p>📌 <strong>Nome:</strong> meu-site</p>
                            <p>📝 <strong>Título:</strong> Meu Site</p>
                            <p>🌐 <strong>URL:</strong> https://meu-site.automatizacoes.com.br</p>
                            <p>📦 <strong>Versão WP:</strong> latest</p>
                            <p>🛒 <strong>WooCommerce:</strong> Não</p>
                            <p>🗃️ <strong>Banco:</strong> wp_meu_site</p>
                            <p>📅 <strong>Criado em:</strong> {{ now()->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-blue-50 border border-blue-200 rounded-xl p-5">
                    <h4 class="text-sm font-semibold text-blue-800 flex items-center gap-2 mb-2">
                        <i class="fas fa-lightbulb"></i> Como funciona
                    </h4>
                    <ul class="text-xs text-blue-700 space-y-2">
                        <li><i class="fas fa-check text-blue-500 mr-1"></i> A notificação é enviada logo após a criação bem-sucedida do site</li>
                        <li><i class="fas fa-check text-blue-500 mr-1"></i> Usa a Evolution API Go para enviar via WhatsApp</li>
                        <li><i class="fas fa-check text-blue-500 mr-1"></i> Se a API estiver indisponível, a criação do site não é afetada</li>
                        <li><i class="fas fa-check text-blue-500 mr-1"></i> Erros de envio ficam registrados no log do Laravel</li>
                    </ul>
                </div>
            </div>
        </div>
    @endif
@endsection
