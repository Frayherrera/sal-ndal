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
                    <a href="{{ route('gestion') }}" class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg shadow-blue-500/30">
                            <i class="fas fa-pepper-hot text-white"></i>
                        </div>
                        <span class="font-bold text-white text-lg">Santini</span>
                    </a>
                </div>

                <div class="flex-1 hidden lg:flex justify-center px-4">
                    <nav class="flex items-center gap-1 overflow-x-auto">
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
                               class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition-colors whitespace-nowrap
                               {{ $item['active'] ? 'bg-blue-500/20 text-blue-200' : 'text-white/70 hover:text-white hover:bg-white/5' }}">
                                <i class="fas fa-{{ $item['icon'] }}"></i>
                                <span>{{ $item['label'] }}</span>
                            </a>
                        @endforeach
                    </nav>
                </div>

                <div class="flex items-center gap-4">
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

        <!-- Mobile nav -->
        <div class="lg:hidden border-t border-white/10">
            <nav class="max-w-7xl mx-auto px-4 py-2 flex gap-1 overflow-x-auto">
                @foreach ($nav as $item)
                    <a href="{{ route($item['route']) }}"
                       class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium whitespace-nowrap transition-colors
                       {{ $item['active'] ? 'bg-blue-500/20 text-blue-200' : 'text-white/70 hover:text-white' }}">
                        <i class="fas fa-{{ $item['icon'] }}"></i>
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </nav>
        </div>
    </header>

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
    <script src="{{ asset('js/app.js') }}"></script>
    @stack('scripts')
</body>

</html>
