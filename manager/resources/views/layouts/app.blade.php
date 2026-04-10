<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'WP Docker Manager')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#eff6ff',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            900: '#1e3a5f',
                        }
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        [x-cloak] { display: none !important; }
        .fade-in { animation: fadeIn 0.3s ease-in; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="h-full bg-gray-50">
    <div class="min-h-full" x-data="{ sidebarOpen: true }">
        <!-- Sidebar -->
        <aside class="fixed inset-y-0 left-0 z-50 w-64 bg-gray-900 text-white transform transition-transform duration-200"
               :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
            <div class="flex items-center gap-3 px-6 py-5 border-b border-gray-700">
                <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
                    <i class="fab fa-wordpress text-xl"></i>
                </div>
                <div>
                    <h1 class="text-lg font-bold leading-tight">WP Manager</h1>
                    <p class="text-xs text-gray-400">Docker Local Dev</p>
                </div>
            </div>

            <nav class="mt-6 px-3">
                <a href="{{ route('dashboard') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg mb-1 text-sm font-medium transition-colors
                          {{ request()->routeIs('dashboard') ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                    <i class="fas fa-th-large w-5 text-center"></i>
                    Dashboard
                </a>
                <a href="{{ route('sites.index') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg mb-1 text-sm font-medium transition-colors
                          {{ request()->routeIs('sites.*') ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                    <i class="fas fa-globe w-5 text-center"></i>
                    Sites WordPress
                </a>
                <a href="{{ route('sites.create') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg mb-1 text-sm font-medium transition-colors text-gray-300 hover:bg-gray-800 hover:text-white">
                    <i class="fas fa-plus-circle w-5 text-center"></i>
                    Novo Site
                </a>

                <a href="{{ route('logs.index') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg mb-1 text-sm font-medium transition-colors
                          {{ request()->routeIs('logs.*') ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                    <i class="fas fa-scroll w-5 text-center"></i>
                    Logs
                </a>
                <a href="{{ route('settings.index') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg mb-1 text-sm font-medium transition-colors
                          {{ request()->routeIs('settings.*') ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                    <i class="fas fa-cogs w-5 text-center"></i>
                    Configurações
                </a>

                <div class="mt-6 mb-2 px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Ferramentas</div>

                <a href="http://localhost:8080" target="_blank"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg mb-1 text-sm font-medium text-gray-300 hover:bg-gray-800 hover:text-white transition-colors">
                    <i class="fas fa-database w-5 text-center"></i>
                    phpMyAdmin
                    <i class="fas fa-external-link-alt text-xs ml-auto opacity-50"></i>
                </a>
                <a href="http://localhost:8025" target="_blank"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg mb-1 text-sm font-medium text-gray-300 hover:bg-gray-800 hover:text-white transition-colors">
                    <i class="fas fa-envelope w-5 text-center"></i>
                    Mailpit
                    <i class="fas fa-external-link-alt text-xs ml-auto opacity-50"></i>
                </a>
            </nav>

            <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-gray-700">
                <div class="flex items-center gap-2 text-xs text-gray-500">
                    <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                    Docker Running
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="transition-all duration-200" :class="sidebarOpen ? 'ml-64' : 'ml-0'">
            <!-- Top bar -->
            <header class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-40">
                <div class="flex items-center justify-between px-6 py-3">
                    <div class="flex items-center gap-4">
                        <button @click="sidebarOpen = !sidebarOpen" class="text-gray-500 hover:text-gray-700">
                            <i class="fas fa-bars text-lg"></i>
                        </button>
                        <h2 class="text-lg font-semibold text-gray-800">@yield('heading', 'Dashboard')</h2>
                    </div>
                    <div class="flex items-center gap-4">
                        @yield('actions')
                    </div>
                </div>
            </header>

            <!-- Flash Messages -->
            @if(session('success'))
                <div class="mx-6 mt-4 fade-in" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)">
                    <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-check-circle"></i>
                            {{ session('success') }}
                        </div>
                        <button @click="show = false" class="text-green-600 hover:text-green-800">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            @endif

            @if(session('warning'))
                <div class="mx-6 mt-4 fade-in" x-data="{ show: true }" x-show="show">
                    <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-lg flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-exclamation-triangle"></i>
                            {{ session('warning') }}
                        </div>
                        <button @click="show = false" class="text-amber-600 hover:text-amber-800">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            @endif

            @if(session('error'))
                <div class="mx-6 mt-4 fade-in" x-data="{ show: true, showDetail: false }" x-show="show">
                    <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-exclamation-circle"></i>
                                {{ session('error') }}
                                @if(session('error_detail'))
                                    <button @click="showDetail = !showDetail" class="text-xs underline ml-2">
                                        <span x-text="showDetail ? 'Ocultar detalhes' : 'Ver detalhes'"></span>
                                    </button>
                                @endif
                            </div>
                            <button @click="show = false" class="text-red-600 hover:text-red-800">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        @if(session('error_detail'))
                            <pre x-show="showDetail" x-cloak class="mt-3 p-3 bg-red-100 rounded text-xs text-red-900 overflow-x-auto max-h-64 overflow-y-auto whitespace-pre-wrap">{{ session('error_detail') }}</pre>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Page Content -->
            <main class="p-6">
                @yield('content')
            </main>
        </div>
    </div>

    {{-- Modal de confirmacao global --}}
    @include('components.confirm-modal')
</body>
</html>
