<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Panel de Gestión - {{ config('app.name', 'Santini') }}</title>
    <meta name="theme-color" content="#f59e0b">
    <link rel="manifest" href="/manifest.json">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        html { scroll-behavior: smooth; }
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
    </style>
</head>

<body class="min-h-screen bg-gradient-to-br from-gray-900 via-amber-900 to-orange-900">
    <!-- Decorative -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute top-20 left-10 w-72 h-72 bg-amber-500/10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-20 right-10 w-96 h-96 bg-orange-500/10 rounded-full blur-3xl"></div>
    </div>

    <!-- Header -->
    <header class="relative z-50 glass border-b border-white/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-pepper-hot text-white"></i>
                    </div>
                    <span class="font-bold text-white text-lg">Santini</span>
                </div>

                <div class="flex items-center gap-6">
                    <nav class="hidden md:flex items-center gap-6">
                        <a href="#" class="text-amber-300 border-b-2 border-amber-400 pb-1 text-sm font-medium">Dashboard</a>
                        <a href="#" class="text-white/70 hover:text-white transition-colors text-sm">Materia Prima</a>
                        <a href="#" class="text-white/70 hover:text-white transition-colors text-sm">Productos</a>
                        <a href="#" class="text-white/70 hover:text-white transition-colors text-sm">Ventas</a>
                    </nav>
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
        </div>
    </header>

    <!-- Main Content -->
    <main class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Welcome -->
        <div class="mb-8 animate-fade-in">
            <h1 class="text-3xl font-bold text-white mb-2">
                Bienvenido, {{ Auth::user()->name ?? 'Usuario' }}
            </h1>
            <p class="text-white/60">Panel de inventario — Resumen de materia prima, producción y ventas.</p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <!-- Stat 1: Materia prima -->
            <div class="glass-card rounded-2xl p-6 animate-fade-in">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-amber-500/20 rounded-xl flex items-center justify-center">
                        <i class="fas fa-seedling text-amber-400 text-xl"></i>
                    </div>
                    <span class="text-xs text-green-400 bg-green-400/10 px-2 py-1 rounded-full">+5%</span>
                </div>
                <div class="text-3xl font-bold text-white mb-1">320 kg</div>
                <div class="text-sm text-white/50">Materia prima en stock</div>
            </div>

            <!-- Stat 2: Presentaciones -->
            <div class="glass-card rounded-2xl p-6 animate-delay-100">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-orange-500/20 rounded-xl flex items-center justify-center">
                        <i class="fas fa-box-open text-orange-400 text-xl"></i>
                    </div>
                    <span class="text-xs text-green-400 bg-green-400/10 px-2 py-1 rounded-full">+12%</span>
                </div>
                <div class="text-3xl font-bold text-white mb-1">1.240</div>
                <div class="text-sm text-white/50">Unidades empacadas</div>
            </div>

            <!-- Stat 3: Producto terminado -->
            <div class="glass-card rounded-2xl p-6 animate-delay-200">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-red-500/20 rounded-xl flex items-center justify-center">
                        <i class="fas fa-warehouse text-red-400 text-xl"></i>
                    </div>
                    <span class="text-xs text-yellow-400 bg-yellow-400/10 px-2 py-1 rounded-full">Bajo</span>
                </div>
                <div class="text-3xl font-bold text-white mb-1">48</div>
                <div class="text-sm text-white/50">Productos terminados</div>
            </div>

            <!-- Stat 4: Ventas -->
            <div class="glass-card rounded-2xl p-6 animate-delay-300">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-green-500/20 rounded-xl flex items-center justify-center">
                        <i class="fas fa-chart-line text-green-400 text-xl"></i>
                    </div>
                    <span class="text-xs text-green-400 bg-green-400/10 px-2 py-1 rounded-full">+18%</span>
                </div>
                <div class="text-3xl font-bold text-white mb-1">96</div>
                <div class="text-sm text-white/50">Pedidos este mes</div>
            </div>
        </div>

        <!-- Two Column Layout -->
        <div class="grid lg:grid-cols-3 gap-6">
            <!-- Recent Activity -->
            <div class="lg:col-span-2 glass-card rounded-2xl p-6 animate-fade-in">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-lg font-semibold text-white">Movimientos Recientes</h2>
                    <button class="text-sm text-amber-300 hover:text-amber-200 transition-colors">Ver todo</button>
                </div>

                <div class="space-y-4">
                    <div class="flex items-center gap-4 p-3 rounded-xl hover:bg-white/5 transition-colors">
                        <div class="w-10 h-10 bg-green-500/20 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-arrow-down text-green-400"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm text-white">Compra de materia prima — Orégano (25 kg)</div>
                            <div class="text-xs text-white/40">Hace 2 horas · Proveedor Mayorista</div>
                        </div>
                        <span class="text-sm font-medium text-green-400">+25 kg</span>
                    </div>

                    <div class="flex items-center gap-4 p-3 rounded-xl hover:bg-white/5 transition-colors">
                        <div class="w-10 h-10 bg-amber-500/20 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-box-open text-amber-400"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm text-white">Empaque de Pimentón — 200 frascos de 50 g</div>
                            <div class="text-xs text-white/40">Hace 5 horas · Línea de producción</div>
                        </div>
                        <span class="text-sm font-medium text-amber-400">+200 u</span>
                    </div>

                    <div class="flex items-center gap-4 p-3 rounded-xl hover:bg-white/5 transition-colors">
                        <div class="w-10 h-10 bg-red-500/20 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-arrow-up text-red-400"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm text-white">Venta a supermercado — Comino (40 bolsas 100 g)</div>
                            <div class="text-xs text-white/40">Ayer · Supermercado El Éxito</div>
                        </div>
                        <span class="text-sm font-medium text-red-400">-40 u</span>
                    </div>

                    <div class="flex items-center gap-4 p-3 rounded-xl hover:bg-white/5 transition-colors">
                        <div class="w-10 h-10 bg-orange-500/20 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-truck text-orange-400"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm text-white">Despacho a restaurante — Curry (60 bolsas 30 g)</div>
                            <div class="text-xs text-white/40">Hace 2 días · Restaurante La Abuela</div>
                        </div>
                        <span class="text-sm font-medium text-orange-400">-60 u</span>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="glass-card rounded-2xl p-6 animate-delay-200">
                <h2 class="text-lg font-semibold text-white mb-6">Acciones Rápidas</h2>

                <div class="space-y-3">
                    <a href="#" class="flex items-center gap-3 p-3 rounded-xl bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 transition-colors w-full text-left">
                        <i class="fas fa-seedling"></i>
                        <span class="text-sm font-medium">Registrar Compra</span>
                    </a>

                    <a href="#" class="flex items-center gap-3 p-3 rounded-xl bg-orange-500/20 hover:bg-orange-500/30 text-orange-300 transition-colors w-full text-left">
                        <i class="fas fa-box-open"></i>
                        <span class="text-sm font-medium">Registrar Empaque</span>
                    </a>

                    <a href="#" class="flex items-center gap-3 p-3 rounded-xl bg-green-500/20 hover:bg-green-500/30 text-green-300 transition-colors w-full text-left">
                        <i class="fas fa-tag"></i>
                        <span class="text-sm font-medium">Nueva Venta</span>
                    </a>

                    <a href="#" class="flex items-center gap-3 p-3 rounded-xl bg-red-500/20 hover:bg-red-500/30 text-red-300 transition-colors w-full text-left">
                        <i class="fas fa-users"></i>
                        <span class="text-sm font-medium">Gestionar Clientes</span>
                    </a>
                </div>

                <div class="mt-6 pt-6 border-t border-white/10">
                    <h3 class="text-sm font-medium text-white/60 mb-3">Capacidad de producción</h3>
                    <div class="w-full bg-white/10 rounded-full h-2 mb-2">
                        <div class="bg-gradient-to-r from-amber-500 to-orange-500 h-2 rounded-full" style="width: 65%"></div>
                    </div>
                    <div class="text-xs text-white/40">65% de la capacidad usada</div>
                </div>
            </div>
        </div>
    </main>

    @vite(['resources/js/app.js'])
    <script src="{{ asset('js/app.js') }}"></script>
</body>

</html>
