<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Tu Proyecto') }}</title>
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
            0%, 100% { box-shadow: 0 0 20px rgba(59, 130, 246, 0.3); }
            50% { box-shadow: 0 0 40px rgba(59, 130, 246, 0.6); }
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
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
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
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-rocket text-white text-lg"></i>
                    </div>
                    <span class="font-bold text-white text-lg">TuProyecto</span>
                </div>
                <nav class="hidden md:flex items-center gap-8">
                    <a href="#inicio" class="text-white/80 hover:text-white transition-colors">Inicio</a>
                    <a href="#caracteristicas" class="text-white/80 hover:text-white transition-colors">Características</a>
                    <a href="#footer" class="text-white/80 hover:text-white transition-colors">Contacto</a>
                </nav>
                <a href="{{ route('gestion') }}"
                    class="bg-white/10 hover:bg-white/20 text-white px-5 py-2 rounded-full text-sm font-medium transition-all border border-white/20">
                    Abrir App
                </a>
            </div>
        </div>
    </header>

    <!-- Hero -->
    <section id="inicio" class="relative min-h-screen flex items-center overflow-hidden bg-gradient-to-br from-gray-900 via-blue-900 to-purple-900">
        <!-- Decorative elements -->
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute top-20 left-10 w-72 h-72 bg-blue-500/20 rounded-full blur-3xl"></div>
            <div class="absolute bottom-20 right-10 w-96 h-96 bg-purple-500/20 rounded-full blur-3xl"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-blue-600/10 rounded-full blur-3xl"></div>
        </div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-32">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <div class="animate-fade-in-up">
                    <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-sm border border-white/20 rounded-full px-4 py-2 mb-6">
                        <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span>
                        <span class="text-white/90 text-sm">Nuevo — Disponible ahora</span>
                    </div>

                    <h1 class="text-5xl lg:text-7xl font-bold text-white mb-6 leading-tight">
                        Transforma tu<br>
                        <span class="gradient-text">experiencia digital</span>
                    </h1>

                    <p class="text-xl text-white/70 mb-8 max-w-lg leading-relaxed">
                        Una aplicación diseñada para simplificar tu día a día. Rápida, intuitiva y siempre disponible.
                    </p>

                    <div class="flex flex-col sm:flex-row gap-4">
                        <a href="{{ route('gestion') }}"
                            class="bg-gradient-to-r from-blue-500 to-purple-600 text-white px-8 py-4 rounded-xl font-semibold hover:from-blue-600 hover:to-purple-700 transition-all shadow-lg shadow-blue-500/30 text-center animate-pulse-glow">
                            <i class="fas fa-play mr-2"></i>Comenzar Ahora
                        </a>
                        <a href="#caracteristicas"
                            class="bg-white/10 backdrop-blur-sm text-white px-8 py-4 rounded-xl font-semibold hover:bg-white/20 transition-all border border-white/20 text-center">
                            Conocer Más
                        </a>
                    </div>

                    <div class="flex items-center gap-8 mt-12">
                        <div class="text-center">
                            <div class="text-3xl font-bold text-white">100%</div>
                            <div class="text-sm text-white/60">Gratuito</div>
                        </div>
                        <div class="w-px h-12 bg-white/20"></div>
                        <div class="text-center">
                            <div class="text-3xl font-bold text-white">24/7</div>
                            <div class="text-sm text-white/60">Disponible</div>
                        </div>
                        <div class="w-px h-12 bg-white/20"></div>
                        <div class="text-center">
                            <div class="text-3xl font-bold text-white">PWA</div>
                            <div class="text-sm text-white/60">Sin instalar</div>
                        </div>
                    </div>
                </div>

                <!-- Phone mockup -->
                <div class="hidden lg:flex justify-center animate-fade-in-up animate-delay-300">
                    <div class="relative animate-float">
                        <div class="w-72 h-[550px] bg-gray-900 rounded-[3rem] border-4 border-gray-700 shadow-2xl overflow-hidden">
                            <div class="absolute top-0 left-1/2 -translate-x-1/2 w-32 h-6 bg-gray-900 rounded-b-2xl"></div>
                            <div class="w-full h-full bg-gradient-to-b from-blue-500 to-purple-600 flex flex-col items-center justify-center text-white p-6">
                                <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center mb-4">
                                    <i class="fas fa-rocket text-3xl"></i>
                                </div>
                                <div class="text-xl font-bold mb-1">TuProyecto</div>
                                <div class="text-sm text-white/70 mb-6">Tu app favorita</div>
                                <div class="w-full space-y-3">
                                    <div class="bg-white/10 rounded-xl p-3 flex items-center gap-3">
                                        <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                                            <i class="fas fa-bolt"></i>
                                        </div>
                                        <div class="text-left">
                                            <div class="text-sm font-medium">Rápido</div>
                                            <div class="text-xs text-white/60">Carga instantánea</div>
                                        </div>
                                    </div>
                                    <div class="bg-white/10 rounded-xl p-3 flex items-center gap-3">
                                        <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                                            <i class="fas fa-shield-alt"></i>
                                        </div>
                                        <div class="text-left">
                                            <div class="text-sm font-medium">Seguro</div>
                                            <div class="text-xs text-white/60">Datos protegidos</div>
                                        </div>
                                    </div>
                                    <div class="bg-white/10 rounded-xl p-3 flex items-center gap-3">
                                        <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                                            <i class="fas fa-cloud"></i>
                                        </div>
                                        <div class="text-left">
                                            <div class="text-sm font-medium">Offline</div>
                                            <div class="text-xs text-white/60">Funciona sin internet</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Decorative dots -->
                        <div class="absolute -top-4 -right-4 w-8 h-8 bg-yellow-400 rounded-full opacity-60"></div>
                        <div class="absolute -bottom-4 -left-4 w-6 h-6 bg-pink-400 rounded-full opacity-60"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Características -->
    <section id="caracteristicas" class="py-24 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16 animate-fade-in-up">
                <span class="text-blue-600 font-semibold text-sm uppercase tracking-wider">Características</span>
                <h2 class="text-4xl lg:text-5xl font-bold text-gray-900 mt-3 mb-4">
                    Todo lo que necesitas,<br>
                    <span class="gradient-text">nada que no necesites</span>
                </h2>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                    Diseñada con el usuario en mente. Cada función pensada para hacer tu vida más fácil.
                </p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="group p-8 rounded-2xl border border-gray-100 hover:border-blue-200 hover:shadow-xl transition-all duration-300 animate-fade-in-up animate-delay-100">
                    <div class="w-14 h-14 bg-blue-100 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-blue-600 group-hover:scale-110 transition-all duration-300">
                        <i class="fas fa-bolt text-blue-600 text-2xl group-hover:text-white transition-colors"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Ultra Rápido</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Carga instantánea y navegación fluida. Olvidate de los tiempos de espera.
                    </p>
                </div>

                <!-- Feature 2 -->
                <div class="group p-8 rounded-2xl border border-gray-100 hover:border-purple-200 hover:shadow-xl transition-all duration-300 animate-fade-in-up animate-delay-200">
                    <div class="w-14 h-14 bg-purple-100 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-purple-600 group-hover:scale-110 transition-all duration-300">
                        <i class="fas fa-shield-alt text-purple-600 text-2xl group-hover:text-white transition-colors"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Seguro y Privado</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Tus datos están protegidos con encriptación de extremo a extremo.
                    </p>
                </div>

                <!-- Feature 3 -->
                <div class="group p-8 rounded-2xl border border-gray-100 hover:border-green-200 hover:shadow-xl transition-all duration-300 animate-fade-in-up animate-delay-300">
                    <div class="w-14 h-14 bg-green-100 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-green-600 group-hover:scale-110 transition-all duration-300">
                        <i class="fas fa-cloud-download-alt text-green-600 text-2xl group-hover:text-white transition-colors"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Funciona Offline</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Sin conexión a internet? No hay problema. La app sigue funcionando.
                    </p>
                </div>

                <!-- Feature 4 -->
                <div class="group p-8 rounded-2xl border border-gray-100 hover:border-orange-200 hover:shadow-xl transition-all duration-300 animate-fade-in-up animate-delay-100">
                    <div class="w-14 h-14 bg-orange-100 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-orange-600 group-hover:scale-110 transition-all duration-300">
                        <i class="fas fa-mobile-alt text-orange-600 text-2xl group-hover:text-white transition-colors"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Multiplataforma</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Disponible en cualquier dispositivo. Celular, tablet o computador.
                    </p>
                </div>

                <!-- Feature 5 -->
                <div class="group p-8 rounded-2xl border border-gray-100 hover:border-pink-200 hover:shadow-xl transition-all duration-300 animate-fade-in-up animate-delay-200">
                    <div class="w-14 h-14 bg-pink-100 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-pink-600 group-hover:scale-110 transition-all duration-300">
                        <i class="fas fa-palette text-pink-600 text-2xl group-hover:text-white transition-colors"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Diseño Moderno</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Interfaz intuitiva y elegante. Diseñada para que disfrutes usarla.
                    </p>
                </div>

                <!-- Feature 6 -->
                <div class="group p-8 rounded-2xl border border-gray-100 hover:border-cyan-200 hover:shadow-xl transition-all duration-300 animate-fade-in-up animate-delay-300">
                    <div class="w-14 h-14 bg-cyan-100 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-cyan-600 group-hover:scale-110 transition-all duration-300">
                        <i class="fas fa-sync-alt text-cyan-600 text-2xl group-hover:text-white transition-colors"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Sincronización</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Tus datos se sincronizan automáticamente entre todos tus dispositivos.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-24 bg-gradient-to-br from-gray-900 via-blue-900 to-purple-900 relative overflow-hidden">
        <div class="absolute inset-0">
            <div class="absolute top-10 left-10 w-64 h-64 bg-blue-500/20 rounded-full blur-3xl"></div>
            <div class="absolute bottom-10 right-10 w-80 h-80 bg-purple-500/20 rounded-full blur-3xl"></div>
        </div>
        <div class="relative max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-4xl lg:text-5xl font-bold text-white mb-6">
                ¿Listo para empezar?
            </h2>
            <p class="text-xl text-white/70 mb-10 max-w-2xl mx-auto">
                Únete a los miles de usuarios que ya están transformando su experiencia digital.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('gestion') }}"
                    class="bg-white text-gray-900 px-10 py-4 rounded-xl font-bold hover:bg-gray-100 transition-all shadow-lg text-center">
                    <i class="fas fa-rocket mr-2"></i>Empezar Gratis
                </a>
                <a href="#caracteristicas"
                    class="bg-white/10 backdrop-blur-sm text-white px-10 py-4 rounded-xl font-bold hover:bg-white/20 transition-all border border-white/20 text-center">
                    Ver Demo
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
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center">
                            <i class="fas fa-rocket text-white"></i>
                        </div>
                        <span class="font-bold text-xl">TuProyecto</span>
                    </div>
                    <p class="text-gray-400 leading-relaxed">
                        La aplicación que necesitabas. Simple, rápida y poderosa.
                    </p>
                </div>

                <div>
                    <h4 class="font-semibold text-lg mb-6">Enlaces</h4>
                    <ul class="space-y-3 text-gray-400">
                        <li><a href="#inicio" class="hover:text-white transition-colors">Inicio</a></li>
                        <li><a href="#caracteristicas" class="hover:text-white transition-colors">Características</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Documentación</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Soporte</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="font-semibold text-lg mb-6">Conéctate</h4>
                    <div class="flex gap-4">
                        <a href="#" class="w-10 h-10 bg-white/10 rounded-lg flex items-center justify-center hover:bg-white/20 transition-colors">
                            <i class="fab fa-github"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-white/10 rounded-lg flex items-center justify-center hover:bg-white/20 transition-colors">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-white/10 rounded-lg flex items-center justify-center hover:bg-white/20 transition-colors">
                            <i class="fab fa-discord"></i>
                        </a>
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-800 pt-8 text-center text-gray-500 text-sm">
                &copy; {{ date('Y') }} TuProyecto. Todos los derechos reservados.
            </div>
        </div>
    </footer>

    @vite(['resources/js/app.js'])
    <script src="{{ asset('js/app.js') }}"></script>

</body>

</html>
