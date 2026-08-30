<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Santini') }}</title>
    <meta name="theme-color" content="#4A90E2">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">

    <link rel="manifest" href="/manifest.json">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />

    <link rel="apple-touch-icon" sizes="16x16" href="/pwa/icons/ios/16.png">
    <link rel="apple-touch-icon" sizes="20x20" href="/pwa/icons/ios/20.png">
    <link rel="apple-touch-icon" sizes="29x29" href="/pwa/icons/ios/29.png">
    <link rel="apple-touch-icon" sizes="32x32" href="/pwa/icons/ios/32.png">
    <link rel="apple-touch-icon" sizes="40x40" href="/pwa/icons/ios/40.png">
    <link rel="apple-touch-icon" sizes="50x50" href="/pwa/icons/ios/50.png">
    <link rel="apple-touch-icon" sizes="57x57" href="/pwa/icons/ios/57.png">
    <link rel="apple-touch-icon" sizes="58x58" href="/pwa/icons/ios/58.png">
    <link rel="apple-touch-icon" sizes="60x60" href="/pwa/icons/ios/60.png">
    <link rel="apple-touch-icon" sizes="64x64" href="/pwa/icons/ios/64.png">
    <link rel="apple-touch-icon" sizes="72x72" href="/pwa/icons/ios/72.png">
    <link rel="apple-touch-icon" sizes="76x76" href="/pwa/icons/ios/76.png">
    <link rel="apple-touch-icon" sizes="80x80" href="/pwa/icons/ios/80.png">
    <link rel="apple-touch-icon" sizes="87x87" href="/pwa/icons/ios/87.png">
    <link rel="apple-touch-icon" sizes="100x100" href="/pwa/icons/ios/100.png">
    <link rel="apple-touch-icon" sizes="114x114" href="/pwa/icons/ios/114.png">
    <link rel="apple-touch-icon" sizes="120x120" href="/pwa/icons/ios/120.png">
    <link rel="apple-touch-icon" sizes="128x128" href="/pwa/icons/ios/128.png">
    <link rel="apple-touch-icon" sizes="144x144" href="/pwa/icons/ios/144.png">
    <link rel="apple-touch-icon" sizes="152x152" href="/pwa/icons/ios/152.png">
    <link rel="apple-touch-icon" sizes="167x167" href="/pwa/icons/ios/167.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/pwa/icons/ios/180.png">
    <link rel="apple-touch-icon" sizes="192x192" href="/pwa/icons/ios/192.png">
    <link rel="apple-touch-icon" sizes="256x256" href="/pwa/icons/ios/256.png">
    <link rel="apple-touch-icon" sizes="512x512" href="/pwa/icons/ios/512.png">
    <link rel="apple-touch-icon" sizes="1024x1024" href="/pwa/icons/ios/1024.png">

    <link href="/pwa/icons/ios/1024.png" sizes="1024x1024" rel="apple-touch-startup-image">
    <link href="/pwa/icons/ios/512.png" sizes="512x512" rel="apple-touch-startup-image">
    <link href="/pwa/icons/ios/256.png" sizes="256x256" rel="apple-touch-startup-image">
    <link href="/pwa/icons/ios/192.png" sizes="192x192" rel="apple-touch-startup-image">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        html {
            scroll-behavior: smooth;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 0 20px rgba(245, 158, 11, 0.3); }
            50% { box-shadow: 0 0 40px rgba(245, 158, 11, 0.6); }
        }

        .animate-fade-in-up {
            animation: fadeInUp 0.8s ease-out forwards;
        }

        .animate-delay-100 {
            animation: fadeInUp 0.8s ease-out 0.1s forwards;
            opacity: 0;
        }

        .animate-delay-200 {
            animation: fadeInUp 0.8s ease-out 0.2s forwards;
            opacity: 0;
        }

        .animate-delay-300 {
            animation: fadeInUp 0.8s ease-out 0.3s forwards;
            opacity: 0;
        }

        .animate-delay-400 {
            animation: fadeInUp 0.8s ease-out 0.4s forwards;
            opacity: 0;
        }

        .animate-float {
            animation: float 3s ease-in-out infinite;
        }

        .animate-pulse-glow {
            animation: pulse-glow 2s ease-in-out infinite;
        }

        .gradient-text {
            background: linear-gradient(135deg, #f59e0b, #ea580c);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .glass {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
    </style>
</head>

<body class="min-h-screen bg-gray-50">

    <!-- Header -->
    <header class="fixed top-0 w-full z-50 glass">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-pepper-hot text-white text-lg"></i>
                    </div>
                    <span class="font-bold text-white text-lg">Santini</span>
                </div>
                <nav class="hidden md:flex items-center gap-8">
                    <a href="#inicio" class="text-white/80 hover:text-white transition-colors">Inicio</a>
                    <a href="#proceso" class="text-white/80 hover:text-white transition-colors">Proceso</a>
                    <a href="#productos" class="text-white/80 hover:text-white transition-colors">Productos</a>
                    <a href="#footer" class="text-white/80 hover:text-white transition-colors">Contacto</a>
                </nav>
                <a href="{{ route('login') }}"
                    class="bg-white/10 hover:bg-white/20 text-white px-5 py-2 rounded-full text-sm font-medium transition-all border border-white/20">
                    Iniciar Sesión
                </a>
            </div>
        </div>
    </header>

    <!-- Hero -->
    <section id="inicio" class="relative min-h-screen flex items-center overflow-hidden bg-gradient-to-br from-gray-900 via-amber-900 to-orange-900">
        <!-- Decorative elements -->
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute top-20 left-10 w-72 h-72 bg-amber-500/20 rounded-full blur-3xl"></div>
            <div class="absolute bottom-20 right-10 w-96 h-96 bg-orange-500/20 rounded-full blur-3xl"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-amber-600/10 rounded-full blur-3xl"></div>
        </div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-32">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <div class="animate-fade-in-up">
                    <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-sm border border-white/20 rounded-full px-4 py-2 mb-6">
                        <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span>
                        <span class="text-white/90 text-sm">Condimentos y especias de calidad</span>
                    </div>

                    <h1 class="text-5xl lg:text-7xl font-bold text-white mb-6 leading-tight">
                        El sabor que<br>
                        <span class="gradient-text">da vida a tu cocina</span>
                    </h1>

                    <p class="text-xl text-white/70 mb-8 max-w-lg leading-relaxed">
                        Compramos, almacenamos, empacamos y distribuimos condimentos y especias en presentaciones comerciales.
                    </p>

                    <div class="flex flex-col sm:flex-row gap-4">
                        <a href="{{ route('login') }}"
                            class="bg-gradient-to-r from-amber-500 to-orange-600 text-white px-8 py-4 rounded-xl font-semibold hover:from-amber-600 hover:to-orange-700 transition-all shadow-lg shadow-amber-500/30 text-center animate-pulse-glow">
                            <i class="fas fa-sign-in-alt mr-2"></i>Acceder al Sistema
                        </a>
                        <a href="#proceso"
                            class="bg-white/10 backdrop-blur-sm text-white px-8 py-4 rounded-xl font-semibold hover:bg-white/20 transition-all border border-white/20 text-center">
                            Conocer el Proceso
                        </a>
                    </div>

                    <div class="flex items-center gap-8 mt-12">
                        <div class="text-center">
                            <div class="text-3xl font-bold text-white">30 g</div>
                            <div class="text-sm text-white/60">Presentaciones</div>
                        </div>
                        <div class="w-px h-12 bg-white/20"></div>
                        <div class="text-center">
                            <div class="text-3xl font-bold text-white">100 g</div>
                            <div class="text-sm text-white/60">Bolsas y frascos</div>
                        </div>
                        <div class="w-px h-12 bg-white/20"></div>
                        <div class="text-center">
                            <div class="text-3xl font-bold text-white">50 g</div>
                            <div class="text-sm text-white/60">Para todos</div>
                        </div>
                    </div>
                </div>

                <!-- Phone mockup -->
                <div class="hidden lg:flex justify-center animate-fade-in-up animate-delay-300">
                    <div class="relative animate-float">
                        <div class="w-72 h-[550px] bg-gray-900 rounded-[3rem] border-4 border-gray-700 shadow-2xl overflow-hidden">
                            <div class="absolute top-0 left-1/2 -translate-x-1/2 w-32 h-6 bg-gray-900 rounded-b-2xl"></div>
                            <div class="w-full h-full bg-gradient-to-b from-amber-500 to-orange-600 flex flex-col items-center justify-center text-white p-6">
                                <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center mb-4">
                                    <i class="fas fa-pepper-hot text-3xl"></i>
                                </div>
                                <div class="text-xl font-bold mb-1">Santini</div>
                                <div class="text-sm text-white/70 mb-6">Gestión de inventario</div>
                                <div class="w-full space-y-3">
                                    <div class="bg-white/10 rounded-xl p-3 flex items-center gap-3">
                                        <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                                            <i class="fas fa-box"></i>
                                        </div>
                                        <div class="text-left">
                                            <div class="text-sm font-medium">Materia prima</div>
                                            <div class="text-xs text-white/60">Por kilogramos</div>
                                        </div>
                                    </div>
                                    <div class="bg-white/10 rounded-xl p-3 flex items-center gap-3">
                                        <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                                            <i class="fas fa-tag"></i>
                                        </div>
                                        <div class="text-left">
                                            <div class="text-sm font-medium">Presentaciones</div>
                                            <div class="text-xs text-white/60">30g · 50g · 100g</div>
                                        </div>
                                    </div>
                                    <div class="bg-white/10 rounded-xl p-3 flex items-center gap-3">
                                        <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                                            <i class="fas fa-truck"></i>
                                        </div>
                                        <div class="text-left">
                                            <div class="text-sm font-medium">Distribución</div>
                                            <div class="text-xs text-white/60">Tiendas y supermercados</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Decorative dots -->
                        <div class="absolute -top-4 -right-4 w-8 h-8 bg-amber-400 rounded-full opacity-60"></div>
                        <div class="absolute -bottom-4 -left-4 w-6 h-6 bg-orange-400 rounded-full opacity-60"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Proceso -->
    <section id="proceso" class="py-24 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16 animate-fade-in-up">
                <span class="text-amber-600 font-semibold text-sm uppercase tracking-wider">Nuestro Proceso</span>
                <h2 class="text-4xl lg:text-5xl font-bold text-gray-900 mt-3 mb-4">
                    De la materia prima<br>
                    <span class="gradient-text">al producto final</span>
                </h2>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                    Control total del flujo de inventario: compra, almacenamiento, empaque y distribución.
                </p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-5 gap-8">
                <!-- Step 1 -->
                <div class="group p-8 rounded-2xl border border-gray-100 hover:border-amber-200 hover:shadow-xl transition-all duration-300 animate-fade-in-up animate-delay-100">
                    <div class="w-14 h-14 bg-amber-100 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-amber-500 group-hover:scale-110 transition-all duration-300">
                        <i class="fas fa-hand-holding-usd text-amber-500 text-2xl group-hover:text-white transition-colors"></i>
                    </div>
                    <div class="text-xs font-bold text-amber-500 mb-2">PASO 1</div>
                    <h3 class="text-lg font-bold text-gray-900 mb-3">Compra</h3>
                    <p class="text-gray-600 leading-relaxed text-sm">
                        Adquirimos condimentos y especias por kilogramos.
                    </p>
                </div>

                <!-- Step 2 -->
                <div class="group p-8 rounded-2xl border border-gray-100 hover:border-amber-200 hover:shadow-xl transition-all duration-300 animate-fade-in-up animate-delay-200">
                    <div class="w-14 h-14 bg-amber-100 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-amber-500 group-hover:scale-110 transition-all duration-300">
                        <i class="fas fa-warehouse text-amber-500 text-2xl group-hover:text-white transition-colors"></i>
                    </div>
                    <div class="text-xs font-bold text-amber-500 mb-2">PASO 2</div>
                    <h3 class="text-lg font-bold text-gray-900 mb-3">Almacena</h3>
                    <p class="text-gray-600 leading-relaxed text-sm">
                        Guardamos la materia prima en condiciones óptimas.
                    </p>
                </div>

                <!-- Step 3 -->
                <div class="group p-8 rounded-2xl border border-gray-100 hover:border-amber-200 hover:shadow-xl transition-all duration-300 animate-fade-in-up animate-delay-300">
                    <div class="w-14 h-14 bg-amber-100 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-amber-500 group-hover:scale-110 transition-all duration-300">
                        <i class="fas fa-box-open text-amber-500 text-2xl group-hover:text-white transition-colors"></i>
                    </div>
                    <div class="text-xs font-bold text-amber-500 mb-2">PASO 3</div>
                    <h3 class="text-lg font-bold text-gray-900 mb-3">Empaca</h3>
                    <p class="text-gray-600 leading-relaxed text-sm">
                        Preparamos presentaciones de 30g, 50g y 100g.
                    </p>
                </div>

                <!-- Step 4 -->
                <div class="group p-8 rounded-2xl border border-gray-100 hover:border-amber-200 hover:shadow-xl transition-all duration-300 animate-fade-in-up animate-delay-100">
                    <div class="w-14 h-14 bg-amber-100 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-amber-500 group-hover:scale-110 transition-all duration-300">
                        <i class="fas fa-boxes text-amber-500 text-2xl group-hover:text-white transition-colors"></i>
                    </div>
                    <div class="text-xs font-bold text-amber-500 mb-2">PASO 4</div>
                    <h3 class="text-lg font-bold text-gray-900 mb-3">Guarda</h3>
                    <p class="text-gray-600 leading-relaxed text-sm">
                        Registramos el producto terminado en inventario.
                    </p>
                </div>

                <!-- Step 5 -->
                <div class="group p-8 rounded-2xl border border-gray-100 hover:border-amber-200 hover:shadow-xl transition-all duration-300 animate-fade-in-up animate-delay-200">
                    <div class="w-14 h-14 bg-amber-100 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-amber-500 group-hover:scale-110 transition-all duration-300">
                        <i class="fas fa-truck text-amber-500 text-2xl group-hover:text-white transition-colors"></i>
                    </div>
                    <div class="text-xs font-bold text-amber-500 mb-2">PASO 5</div>
                    <h3 class="text-lg font-bold text-gray-900 mb-3">Vende</h3>
                    <p class="text-gray-600 leading-relaxed text-sm">
                        Distribuimos a tiendas, supermercados y restaurantes.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Productos -->
    <section id="productos" class="py-24 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16 animate-fade-in-up">
                <span class="text-amber-600 font-semibold text-sm uppercase tracking-wider">Presentaciones</span>
                <h2 class="text-4xl lg:text-5xl font-bold text-gray-900 mt-3 mb-4">Nuestros Formatos</h2>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                    Bolsas y frascos en presentaciones pensadas para cada necesidad.
                </p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <!-- 30g -->
                <div class="group bg-white p-8 rounded-2xl border-2 border-transparent hover:border-amber-400 hover:shadow-xl transition-all duration-300 text-center animate-fade-in-up animate-delay-100">
                    <div class="w-20 h-20 mx-auto bg-amber-100 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-amber-500 transition-colors duration-300">
                        <i class="fas fa-shopping-bag text-amber-500 text-3xl group-hover:text-white transition-colors"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">30 g</h3>
                    <p class="text-sm text-gray-500 mb-4">Bolsas con sello</p>
                    <a href="{{ route('login') }}" class="inline-block text-amber-600 font-semibold hover:text-amber-700 transition-colors">
                        Cotizar <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>

                <!-- 50g -->
                <div class="group bg-white p-8 rounded-2xl border-2 border-amber-400 shadow-lg shadow-amber-100 text-center animate-fade-in-up animate-delay-200 relative">
                    <span class="absolute top-4 right-4 bg-amber-500 text-white text-xs font-bold px-2 py-1 rounded-full">Popular</span>
                    <div class="w-20 h-20 mx-auto bg-amber-500 rounded-2xl flex items-center justify-center mb-6">
                        <i class="fas fa-shopping-bag text-white text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">50 g</h3>
                    <p class="text-sm text-gray-500 mb-4">Bolsa o frasco</p>
                    <a href="{{ route('login') }}" class="inline-block text-amber-600 font-semibold hover:text-amber-700 transition-colors">
                        Cotizar <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>

                <!-- 100g -->
                <div class="group bg-white p-8 rounded-2xl border-2 border-transparent hover:border-amber-400 hover:shadow-xl transition-all duration-300 text-center animate-fade-in-up animate-delay-300">
                    <div class="w-20 h-20 mx-auto bg-amber-100 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-amber-500 transition-colors duration-300">
                        <i class="fas fa-glass-whiskey text-amber-500 text-3xl group-hover:text-white transition-colors"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">100 g</h3>
                    <p class="text-sm text-gray-500 mb-4">Frasco con tapa</p>
                    <a href="{{ route('login') }}" class="inline-block text-amber-600 font-semibold hover:text-amber-700 transition-colors">
                        Cotizar <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-24 bg-gradient-to-br from-gray-900 via-amber-900 to-orange-900 relative overflow-hidden">
        <div class="absolute inset-0">
            <div class="absolute top-10 left-10 w-64 h-64 bg-amber-500/20 rounded-full blur-3xl"></div>
            <div class="absolute bottom-10 right-10 w-80 h-80 bg-orange-500/20 rounded-full blur-3xl"></div>
        </div>
        <div class="relative max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-4xl lg:text-5xl font-bold text-white mb-6">
                Gestiona tu inventario hoy
            </h2>
            <p class="text-xl text-white/70 mb-10 max-w-2xl mx-auto">
                Controla la compra, el empaque y la distribución de tus condimentos desde un solo lugar.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('login') }}"
                    class="bg-white text-gray-900 px-10 py-4 rounded-xl font-bold hover:bg-gray-100 transition-all shadow-lg text-center">
                    <i class="fas fa-sign-in-alt mr-2"></i>Iniciar Sesión
                </a>
                <a href="#proceso"
                    class="bg-white/10 backdrop-blur-sm text-white px-10 py-4 rounded-xl font-bold hover:bg-white/20 transition-all border border-white/20 text-center">
                    Ver Proceso
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer id="footer" class="bg-gray-900 text-white py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-3 gap-12 mb-12">
                <div>
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl flex items-center justify-center">
                            <i class="fas fa-pepper-hot text-white"></i>
                        </div>
                        <span class="font-bold text-xl">Santini</span>
                    </div>
                    <p class="text-gray-400 leading-relaxed">
                        Comercializadora de condimentos y especias. De la materia prima al producto terminado.
                    </p>
                </div>

                <div>
                    <h4 class="font-semibold text-lg mb-6">Enlaces</h4>
                    <ul class="space-y-3 text-gray-400">
                        <li><a href="#inicio" class="hover:text-white transition-colors">Inicio</a></li>
                        <li><a href="#proceso" class="hover:text-white transition-colors">Proceso</a></li>
                        <li><a href="#productos" class="hover:text-white transition-colors">Productos</a></li>
                        <li><a href="{{ route('login') }}" class="hover:text-white transition-colors">Acceso</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="font-semibold text-lg mb-6">Clientes</h4>
                    <ul class="space-y-3 text-gray-400">
                        <li><i class="fas fa-store mr-2 text-amber-400"></i>Tiendas</li>
                        <li><i class="fas fa-shopping-cart mr-2 text-amber-400"></i>Supermercados</li>
                        <li><i class="fas fa-utensils mr-2 text-amber-400"></i>Restaurantes</li>
                        <li><i class="fas fa-truck-loading mr-2 text-amber-400"></i>Mayoristas</li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-gray-800 pt-8 text-center text-gray-500 text-sm">
                &copy; {{ date('Y') }} Santini. Todos los derechos reservados.
            </div>
        </div>
    </footer>

    @vite(['resources/js/app.js'])
    <script src="{{ asset('js/app.js') }}"></script>

</body>

</html>
