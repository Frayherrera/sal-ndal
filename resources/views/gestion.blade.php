<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dashboard - {{ config('app.name', 'Tu Proyecto') }}</title>
    <meta name="theme-color" content="#4A90E2">
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

<body class="min-h-screen bg-gradient-to-br from-gray-900 via-blue-900 to-purple-900">
    <!-- Decorative -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute top-20 left-10 w-72 h-72 bg-blue-500/10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-20 right-10 w-96 h-96 bg-purple-500/10 rounded-full blur-3xl"></div>
    </div>

    <!-- Header -->
    <header class="relative z-50 glass border-b border-white/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-rocket text-white"></i>
                    </div>
                    <span class="font-bold text-white text-lg">TuProyecto</span>
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
    </header>

    <!-- Main Content -->
    <main class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Welcome -->
        <div class="mb-8 animate-fade-in">
            <h1 class="text-3xl font-bold text-white mb-2">
                Bienvenido, {{ Auth::user()->name ?? 'Usuario' }}
            </h1>
            <p class="text-white/60">Panel de control — Aquí tienes un resumen de tu actividad.</p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <!-- Stat 1 -->
            <div class="glass-card rounded-2xl p-6 animate-fade-in">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-blue-500/20 rounded-xl flex items-center justify-center">
                        <i class="fas fa-folder text-blue-400 text-xl"></i>
                    </div>
                    <span class="text-xs text-green-400 bg-green-400/10 px-2 py-1 rounded-full">+12%</span>
                </div>
                <div class="text-3xl font-bold text-white mb-1">24</div>
                <div class="text-sm text-white/50">Proyectos activos</div>
            </div>

            <!-- Stat 2 -->
            <div class="glass-card rounded-2xl p-6 animate-delay-100">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-purple-500/20 rounded-xl flex items-center justify-center">
                        <i class="fas fa-check-circle text-purple-400 text-xl"></i>
                    </div>
                    <span class="text-xs text-green-400 bg-green-400/10 px-2 py-1 rounded-full">+8%</span>
                </div>
                <div class="text-3xl font-bold text-white mb-1">156</div>
                <div class="text-sm text-white/50">Tareas completadas</div>
            </div>

            <!-- Stat 3 -->
            <div class="glass-card rounded-2xl p-6 animate-delay-200">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-orange-500/20 rounded-xl flex items-center justify-center">
                        <i class="fas fa-clock text-orange-400 text-xl"></i>
                    </div>
                    <span class="text-xs text-yellow-400 bg-yellow-400/10 px-2 py-1 rounded-full">3 pendientes</span>
                </div>
                <div class="text-3xl font-bold text-white mb-1">8</div>
                <div class="text-sm text-white/50">En progreso</div>
            </div>

            <!-- Stat 4 -->
            <div class="glass-card rounded-2xl p-6 animate-delay-300">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-pink-500/20 rounded-xl flex items-center justify-center">
                        <i class="fas fa-bell text-pink-400 text-xl"></i>
                    </div>
                    <span class="text-xs text-red-400 bg-red-400/10 px-2 py-1 rounded-full">5 nuevas</span>
                </div>
                <div class="text-3xl font-bold text-white mb-1">12</div>
                <div class="text-sm text-white/50">Notificaciones</div>
            </div>
        </div>

        <!-- Two Column Layout -->
        <div class="grid lg:grid-cols-3 gap-6">
            <!-- Recent Activity -->
            <div class="lg:col-span-2 glass-card rounded-2xl p-6 animate-fade-in">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-lg font-semibold text-white">Actividad Reciente</h2>
                    <button class="text-sm text-blue-400 hover:text-blue-300 transition-colors">Ver todo</button>
                </div>

                <div class="space-y-4">
                    <div class="flex items-center gap-4 p-3 rounded-xl hover:bg-white/5 transition-colors">
                        <div class="w-10 h-10 bg-green-500/20 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-plus text-green-400"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm text-white">Nuevo proyecto creado</div>
                            <div class="text-xs text-white/40">Hace 2 horas</div>
                        </div>
                    </div>

                    <div class="flex items-center gap-4 p-3 rounded-xl hover:bg-white/5 transition-colors">
                        <div class="w-10 h-10 bg-blue-500/20 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-edit text-blue-400"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm text-white">Tarea actualizada</div>
                            <div class="text-xs text-white/40">Hace 5 horas</div>
                        </div>
                    </div>

                    <div class="flex items-center gap-4 p-3 rounded-xl hover:bg-white/5 transition-colors">
                        <div class="w-10 h-10 bg-purple-500/20 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-check text-purple-400"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm text-white">Tarea completada</div>
                            <div class="text-xs text-white/40">Ayer</div>
                        </div>
                    </div>

                    <div class="flex items-center gap-4 p-3 rounded-xl hover:bg-white/5 transition-colors">
                        <div class="w-10 h-10 bg-orange-500/20 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-comment text-orange-400"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm text-white">Nuevo comentario</div>
                            <div class="text-xs text-white/40">Hace 2 días</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="glass-card rounded-2xl p-6 animate-delay-200">
                <h2 class="text-lg font-semibold text-white mb-6">Acciones Rápidas</h2>

                <div class="space-y-3">
                    <button class="w-full flex items-center gap-3 p-3 rounded-xl bg-blue-500/20 hover:bg-blue-500/30 text-blue-300 transition-colors text-left">
                        <i class="fas fa-plus"></i>
                        <span class="text-sm font-medium">Nuevo Proyecto</span>
                    </button>

                    <button class="w-full flex items-center gap-3 p-3 rounded-xl bg-purple-500/20 hover:bg-purple-500/30 text-purple-300 transition-colors text-left">
                        <i class="fas fa-tasks"></i>
                        <span class="text-sm font-medium">Crear Tarea</span>
                    </button>

                    <button class="w-full flex items-center gap-3 p-3 rounded-xl bg-green-500/20 hover:bg-green-500/30 text-green-300 transition-colors text-left">
                        <i class="fas fa-upload"></i>
                        <span class="text-sm font-medium">Subir Archivo</span>
                    </button>

                    <button class="w-full flex items-center gap-3 p-3 rounded-xl bg-orange-500/20 hover:bg-orange-500/30 text-orange-300 transition-colors text-left">
                        <i class="fas fa-chart-bar"></i>
                        <span class="text-sm font-medium">Ver Reportes</span>
                    </button>
                </div>

                <div class="mt-6 pt-6 border-t border-white/10">
                    <h3 class="text-sm font-medium text-white/60 mb-3">Uso del almacenamiento</h3>
                    <div class="w-full bg-white/10 rounded-full h-2 mb-2">
                        <div class="bg-gradient-to-r from-blue-500 to-purple-500 h-2 rounded-full" style="width: 35%"></div>
                    </div>
                    <div class="text-xs text-white/40">3.5 GB de 10 GB usados</div>
                </div>
            </div>
        </div>
    </main>

    @vite(['resources/js/app.js'])
    <script src="{{ asset('js/app.js') }}"></script>
</body>

</html>
