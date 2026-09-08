<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Santini') — {{ config('app.name', 'Santini') }}</title>
    <meta name="theme-color" content="#4A90E2">
    <link rel="manifest" href="/manifest.json">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,600,700&display=swap" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    @include('partials.theme')
    <style>
        html { scroll-behavior: smooth; }
        body { font-family: 'Figtree', sans-serif; }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in { animation: fadeInUp 0.6s ease-out forwards; }
        .animate-delay-100 { animation: fadeInUp 0.6s ease-out 0.1s forwards; opacity: 0; }
        .animate-delay-200 { animation: fadeInUp 0.6s ease-out 0.2s forwards; opacity: 0; }
        .animate-delay-300 { animation: fadeInUp 0.6s ease-out 0.3s forwards; opacity: 0; }
        .glass {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.15);
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.06);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .input-glass {
            width: 100%;
            padding: 0.75rem 1rem;
            background: rgba(255, 255, 255, 0.10);
            border: 1px solid rgba(255, 255, 255, 0.20);
            border-radius: 0.75rem;
            color: #fff;
        }
        .input-glass::placeholder { color: rgba(255, 255, 255, 0.40); }
        .input-glass:focus {
            outline: none;
            border-color: transparent;
            --tw-ring-shadow: 0 0 0 2px rgba(59, 130, 246, 0.5);
            box-shadow: var(--tw-ring-offset-shadow, 0 0 #0000), var(--tw-ring-shadow, 0 0 #0000), var(--tw-shadow, 0 0 #0000);
        }
        @media (prefers-reduced-motion: reduce) {
            .animate-fade-in, .animate-delay-100, .animate-delay-200, .animate-delay-300 { animation: none; opacity: 1; }
            #sidebar, #sidebarOverlay { transition: none; }
        }

        .sidebar {
            background: rgba(10, 15, 30, 0.88);
            backdrop-filter: blur(16px);
            border-right: 1px solid rgba(255, 255, 255, 0.12);
            box-shadow: 20px 0 50px rgba(0, 0, 0, 0.35);
        }
        .sidebar-overlay {
            background: rgba(0, 0, 0, 0.55);
            backdrop-filter: blur(2px);
        }
        html.light .sidebar {
            background: rgba(255, 255, 255, 0.90);
            border-right-color: rgba(100, 116, 139, 0.25);
            box-shadow: 20px 0 50px rgba(15, 23, 42, 0.12);
        }
        html.light .sidebar-overlay {
            background: rgba(15, 23, 42, 0.30);
        }
    </style>
</head>

<body class="min-h-screen bg-gradient-to-br from-gray-900 via-blue-900 to-purple-900">
    <!-- Decorative -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute top-20 left-10 w-72 h-72 bg-blue-500/10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-20 right-10 w-96 h-96 bg-blue-500/10 rounded-full blur-3xl"></div>
    </div>

    <!-- Header -->
    <header class="relative z-50 glass border-b border-white/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center gap-3">
                    <button type="button" id="sidebarToggle" class="w-10 h-10 rounded-xl glass flex items-center justify-center text-white/70 hover:text-white hover:bg-white/10 transition-colors" aria-label="Abrir menú" aria-controls="sidebar" aria-expanded="false">
                        <i class="fas fa-bars text-lg"></i>
                    </button>
                    <a href="{{ route('gestion') }}" class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg shadow-blue-500/30">
                            <i class="fas fa-pepper-hot text-white"></i>
                        </div>
                        <span class="font-bold text-white text-lg">Santini</span>
                    </a>
                </div>

                <div class="flex items-center gap-4">
                    <button type="button" data-theme-toggle class="theme-toggle" aria-label="Cambiar tema" title="Cambiar tema claro/oscuro">
                        <i class="fas fa-sun theme-icon-sun text-lg"></i>
                        <i class="fas fa-moon theme-icon-moon text-lg"></i>
                    </button>
                    <div class="hidden sm:flex items-center gap-2 text-white/70">
                        <i class="fas fa-user-circle"></i>
                        <span class="text-sm">{{ Auth::user()->name ?? 'Usuario' }}</span>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-white/60 hover:text-white transition-colors" title="Cerrar sesión">
                            <i class="fas fa-sign-out-alt text-lg"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </header>

    <!-- Sidebar overlay -->
    <div id="sidebarOverlay" class="fixed inset-0 z-[55] sidebar-overlay opacity-0 pointer-events-none transition-opacity duration-300"></div>

    <!-- Sidebar (menú hamburguesa) -->
    <aside id="sidebar" class="sidebar fixed top-0 left-0 z-[60] h-full w-72 max-w-[85vw] -translate-x-full transition-transform duration-300 ease-out flex flex-col" aria-hidden="true">
        <!-- Marca -->
        <div class="flex items-center justify-between px-5 h-16 border-b border-white/10 flex-shrink-0">
            <a href="{{ route('gestion') }}" class="flex items-center gap-3">
                <div class="w-9 h-9 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg shadow-blue-500/30">
                    <i class="fas fa-pepper-hot text-white"></i>
                </div>
                <span class="font-bold text-white text-base">Santini</span>
            </a>
            <button type="button" id="sidebarClose" class="w-9 h-9 rounded-xl flex items-center justify-center text-white/60 hover:text-white hover:bg-white/10 transition-colors" aria-label="Cerrar menú">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Navegación -->
        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
            @php
                $nav = [
                    ['route' => 'gestion', 'label' => 'Dashboard', 'icon' => 'chart-pie', 'active' => request()->routeIs('gestion', 'inventario.dashboard')],
                    ['route' => 'inventario.materia-prima.index', 'label' => 'Materia Prima', 'icon' => 'seedling', 'active' => request()->routeIs('inventario.materia-prima.*')],
                    ['route' => 'inventario.productos-terminados.index', 'label' => 'Productos', 'icon' => 'box-open', 'active' => request()->routeIs('inventario.productos-terminados.*')],
                    ['route' => 'inventario.recetas.index', 'label' => 'Recetas', 'icon' => 'book-open', 'active' => request()->routeIs('inventario.recetas.*')],
                    ['route' => 'inventario.movimientos.index', 'label' => 'Movimientos', 'icon' => 'arrows-rotate', 'active' => request()->routeIs('inventario.movimientos.*')],
                    ['route' => 'inventario.conteo-fisico.index', 'label' => 'Conteo Físico', 'icon' => 'clipboard-check', 'active' => request()->routeIs('inventario.conteo-fisico.*')],
                    ['route' => 'inventario.alertas', 'label' => 'Alertas', 'icon' => 'bell', 'active' => request()->routeIs('inventario.alertas')],
                ];
            @endphp
            @foreach ($nav as $item)
                <a href="{{ route($item['route']) }}"
                   class="relative flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-colors
                   {{ $item['active'] ? 'bg-blue-500/20 text-blue-200' : 'text-white/70 hover:text-white hover:bg-white/5' }}">
                    @if ($item['active'])
                        <span class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-6 rounded-full bg-gradient-to-b from-blue-500 to-purple-600"></span>
                    @endif
                    <i class="fas fa-{{ $item['icon'] }} w-5 text-center"></i>
                    <span>{{ $item['label'] }}</span>
                    @if ($item['active'])
                        <i class="fas fa-chevron-right ml-auto text-xs text-blue-300"></i>
                    @endif
                </a>
            @endforeach
        </nav>

        <!-- Usuario -->
        <div class="px-4 py-4 border-t border-white/10 flex-shrink-0 space-y-3">
            <div class="flex items-center gap-3 px-2 py-2">
                <div class="w-9 h-9 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white text-sm font-bold">
                    {{ strtoupper(mb_substr(Auth::user()->name ?? 'U', 0, 1)) }}
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-white truncate">{{ Auth::user()->name ?? 'Usuario' }}</p>
                    <p class="text-xs text-white/40 truncate">{{ Auth::user()->email ?? '' }}</p>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-sm font-medium border border-white/20 text-white/70 hover:text-white hover:bg-white/10 transition-colors">
                    <i class="fas fa-sign-out-alt"></i>
                    Cerrar sesión
                </button>
            </form>
        </div>
    </aside>

    <!-- Flash Messages -->
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4">
        @if (session('success'))
            <div class="glass-card rounded-xl p-4 border border-green-500/30 bg-green-500/10 flex items-center gap-3 animate-fade-in">
                <i class="fas fa-check-circle text-green-400"></i>
                <span class="text-green-200 text-sm">{{ session('success') }}</span>
            </div>
        @endif
        @if (session('error'))
            <div class="glass-card rounded-xl p-4 border border-red-500/30 bg-red-500/10 flex items-center gap-3 animate-fade-in">
                <i class="fas fa-exclamation-circle text-red-400"></i>
                <span class="text-red-200 text-sm">{{ session('error') }}</span>
            </div>
        @endif
    </div>

    <!-- Main Content -->
    <main class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @yield('content')
    </main>

    @vite(['resources/js/app.js'])
    @stack('scripts')

    <script>
        (function () {
            var toggle = document.getElementById('sidebarToggle');
            var close  = document.getElementById('sidebarClose');
            var drawer = document.getElementById('sidebar');
            var overlay = document.getElementById('sidebarOverlay');

            function openSidebar() {
                drawer.classList.remove('-translate-x-full');
                drawer.classList.remove('invisible');
                drawer.setAttribute('aria-hidden', 'false');
                overlay.classList.remove('opacity-0', 'pointer-events-none');
                toggle.setAttribute('aria-expanded', 'true');
            }
            function closeSidebar() {
                drawer.classList.add('-translate-x-full');
                drawer.classList.add('invisible');
                drawer.setAttribute('aria-hidden', 'true');
                overlay.classList.add('opacity-0', 'pointer-events-none');
                toggle.setAttribute('aria-expanded', 'false');
            }

            drawer.classList.add('invisible');

            toggle.addEventListener('click', openSidebar);
            close.addEventListener('click', closeSidebar);
            overlay.addEventListener('click', closeSidebar);
            drawer.querySelectorAll('a').forEach(function (link) {
                link.addEventListener('click', closeSidebar);
            });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') closeSidebar();
            });
        })();
    </script>
</body>

</html>
