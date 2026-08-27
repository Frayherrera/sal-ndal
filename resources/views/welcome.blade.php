<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Alcaldía Municipal de Río Viejo, Bolívar - Ventanilla Única Digital</title>
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

    <!-- Font Awesome (opcional, como respaldo) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        /* Smooth scrolling */
        html {
            scroll-behavior: smooth;
        }

        /* Animaciones personalizadas */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in-up {
            animation: fadeInUp 0.6s ease-out forwards;
        }

        .animate-fade-in-up-delay-1 {
            animation: fadeInUp 0.6s ease-out 0.1s forwards;
            opacity: 0;
        }

        .animate-fade-in-up-delay-2 {
            animation: fadeInUp 0.6s ease-out 0.2s forwards;
            opacity: 0;
        }

        .animate-fade-in-up-delay-3 {
            animation: fadeInUp 0.6s ease-out 0.3s forwards;
            opacity: 0;
        }

        .hover-scale {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .hover-scale:hover {
            transform: translateY(-8px);
        }
    </style>
</head>

<body class="min-h-screen bg-white">
    <a href="{{ route('gestion') }}" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition-colors inline-block">
        Gestionar
    </a>

    <!-- Header -->
    <header class="sticky top-0 z-50 bg-white border-b border-gray-200 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center gap-3">
                    <i class="fas fa-landmark w-8 h-8 text-blue-800 text-2xl"></i>
                    <div>
                        <div class="font-semibold text-blue-900">Alcaldía Municipal</div>
                        <div class="text-sm text-blue-700">Río Viejo, Bolívar</div>
                    </div>
                </div>
                <nav class="hidden md:flex items-center gap-8">
                    <a href="#inicio" class="text-blue-800 hover:text-red-600 transition-colors">Inicio</a>
                    <a href="#servicios" class="text-blue-800 hover:text-red-600 transition-colors">Servicios</a>
                    <a href="#tramites" class="text-blue-800 hover:text-red-600 transition-colors">Trámites</a>
                    <a href="#contacto" class="text-blue-800 hover:text-red-600 transition-colors">Contacto</a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Hero -->
    <section id="inicio" class="relative h-[600px] overflow-hidden">
        <div class="absolute inset-0">
            <img src="https://images.unsplash.com/photo-1700769670643-14361ffa6dfe?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwyfHxDb2xvbWJpYW4lMjB0b3duJTIwZ292ZXJubWVudCUyMGJ1aWxkaW5nJTIwYXJjaGl0ZWN0dXJlfGVufDF8fHx8MTc3NTg1Njk4OXww&ixlib=rb-4.1.0&q=80&w=1080"
                alt="Alcaldía Municipal de Río Viejo" class="w-full h-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-r from-blue-900/95 to-blue-800/85"></div>
        </div>

        <div class="relative h-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center">
            <div class="max-w-2xl text-white animate-fade-in-up">
                <h1 class="text-5xl font-bold mb-4">Ventanilla Única Digital</h1>
                <p class="text-xl mb-8 text-blue-100">
                    Todos tus trámites municipales en un solo lugar. Rápido, seguro y sin filas.
                </p>

                <div class="flex flex-col sm:flex-row gap-4">
                    <a href=""
                        class="bg-red-600 text-white px-8 py-3 rounded-lg font-medium hover:bg-red-700 transition-colors shadow-lg text-center">
                        Iniciar Trámite
                    </a>
                    <a href=""
                        class="bg-white text-blue-900 px-8 py-3 rounded-lg font-medium hover:bg-gray-50 transition-colors shadow-lg text-center">
                        Consultar Estado
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Buscador -->
    <section class="py-12 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-2xl mx-auto">
                <form action="" method="GET" class="relative">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-blue-400"></i>
                    <input type="text" name="q" placeholder="Buscar trámite o servicio..."
                        class="w-full pl-12 pr-4 py-4 rounded-lg border-2 border-blue-200 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500">
                </form>
            </div>
        </div>
    </section>

    <!-- Servicios Principales -->
    <section id="servicios" class="py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16 animate-fade-in-up">
                <h2 class="text-4xl font-bold text-blue-900 mb-4">Servicios Principales</h2>
                <p class="text-lg text-blue-700">Accede a los servicios más solicitados de manera rápida</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                @php
                    $servicios = [
                        ['icon' => 'fa-file-alt', 'titulo' => 'Trámites y Servicios', 'descripcion' => 'Realiza tus solicitudes sin filas', 'color' => 'bg-blue-50', 'iconColor' => 'text-blue-800'],
                        ['icon' => 'fa-credit-card', 'titulo' => 'Pagos en Línea', 'descripcion' => 'Impuestos y servicios municipales', 'color' => 'bg-red-50', 'iconColor' => 'text-red-600'],
                        ['icon' => 'fa-building', 'titulo' => 'Obras Públicas', 'descripcion' => 'Consulta proyectos en ejecución', 'color' => 'bg-blue-50', 'iconColor' => 'text-blue-800'],
                        ['icon' => 'fa-users', 'titulo' => 'Atención Ciudadana', 'descripcion' => 'PQRS y solicitudes de ayuda', 'color' => 'bg-red-50', 'iconColor' => 'text-red-600'],
                    ];
                @endphp

                @foreach($servicios as $index => $servicio)
                    <div class="group cursor-pointer hover-scale animate-fade-in-up-delay-{{ $index }}"
                        style="animation-delay: {{ $index * 0.1 }}s">
                        <div
                            class="bg-white p-6 rounded-xl border-2 border-blue-200 hover:border-red-500 hover:shadow-lg transition-all">
                            <div
                                class="{{ $servicio['color'] }} w-14 h-14 rounded-lg flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                <i class="fas {{ $servicio['icon'] }} w-7 h-7 {{ $servicio['iconColor'] }} text-2xl"></i>
                            </div>
                            <h3 class="font-semibold text-blue-900 mb-2">{{ $servicio['titulo'] }}</h3>
                            <p class="text-sm text-blue-700 mb-4">{{ $servicio['descripcion'] }}</p>
                            <div class="flex items-center text-red-600 text-sm font-medium">
                                Acceder
                                <i
                                    class="fas fa-chevron-right w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform"></i>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Trámites Frecuentes -->
    <section id="tramites" class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <div class="animate-fade-in-up">
                    <h2 class="text-4xl font-bold text-blue-900 mb-4">Trámites Frecuentes</h2>
                    <p class="text-lg text-blue-700 mb-8">
                        Los certificados y documentos más solicitados por nuestros ciudadanos
                    </p>

                    <div class="space-y-3">
                        @php
                            $tramites = [
                                'Certificado de Residencia',
                                'Paz y Salvo de Impuestos',
                                'Permiso de Construcción',
                                'Registro de Defunción',
                                'Licencia de Funcionamiento',
                                'Certificado de Nomenclatura',
                            ];
                        @endphp

                        @foreach($tramites as $index => $tramite)
                            <div class="bg-white p-4 rounded-lg border-2 border-blue-200 hover:border-red-500 hover:shadow-md transition-all cursor-pointer flex items-center justify-between group"
                                style="animation: fadeInUp 0.4s ease-out {{ $index * 0.05 }}s forwards; opacity: 0;">
                                <div class="flex items-center gap-3">
                                    <i class="fas fa-file-alt text-blue-700"></i>
                                    <span class="font-medium text-blue-900">{{ $tramite }}</span>
                                </div>
                                <i
                                    class="fas fa-chevron-right text-red-500 group-hover:translate-x-1 transition-transform"></i>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="bg-blue-800 text-white p-8 rounded-2xl animate-fade-in-up">
                    <i class="fas fa-clock w-12 h-12 mb-4 text-4xl"></i>
                    <h3 class="text-2xl font-bold mb-4">Horarios de Atención</h3>

                    <div class="space-y-4">
                        <div>
                            <div class="font-semibold mb-1">Lunes a Viernes</div>
                            <div class="text-blue-100">8:00 AM - 12:00 PM</div>
                            <div class="text-blue-100">2:00 PM - 6:00 PM</div>
                        </div>

                        <div class="border-t border-blue-600 pt-4">
                            <div class="font-semibold mb-1">Sábados</div>
                            <div class="text-blue-100">8:00 AM - 12:00 PM</div>
                        </div>

                        <div class="border-t border-blue-600 pt-4">
                            <div class="bg-red-600 p-4 rounded-lg">
                                <div class="font-semibold mb-2">
                                    ⚡ Atención Virtual 24/7
                                </div>
                                <div class="text-sm text-white">
                                    Realiza tus trámites en línea en cualquier momento
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contacto -->
    <section id="contacto" class="py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16 animate-fade-in-up">
                <h2 class="text-4xl font-bold text-blue-900 mb-4">Contáctanos</h2>
                <p class="text-lg text-blue-700">Estamos aquí para ayudarte</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <div class="text-center animate-fade-in-up-delay-1">
                    <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-map-marker-alt w-8 h-8 text-blue-800 text-2xl"></i>
                    </div>
                    <h3 class="font-semibold text-blue-900 mb-2">Ubicación</h3>
                    <p class="text-blue-700">
                        Calle Principal #12-34<br>
                        Río Viejo, Bolívar
                    </p>
                </div>

                <div class="text-center animate-fade-in-up-delay-2">
                    <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-phone w-8 h-8 text-red-600 text-2xl"></i>
                    </div>
                    <h3 class="font-semibold text-blue-900 mb-2">Teléfono</h3>
                    <p class="text-blue-700">
                        (605) 123-4567<br>
                        Línea gratuita: 018000-123456
                    </p>
                </div>

                <div class="text-center animate-fade-in-up-delay-3">
                    <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-envelope w-8 h-8 text-blue-800 text-2xl"></i>
                    </div>
                    <h3 class="font-semibold text-blue-900 mb-2">Correo Electrónico</h3>
                    <p class="text-blue-700">
                        contacto@rioviejo-bolivar.gov.co<br>
                        tramites@rioviejo-bolivar.gov.co
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-blue-950 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-3 gap-8 mb-8">
                <div>
                    <div class="flex items-center gap-2 mb-4">
                        <i class="fas fa-landmark"></i>
                        <span class="font-semibold">Alcaldía Municipal</span>
                    </div>
                    <p class="text-gray-400 text-sm">
                        Comprometidos con el desarrollo y bienestar de Río Viejo, Bolívar.
                    </p>
                </div>

                <div>
                    <h4 class="font-semibold mb-4">Enlaces Rápidos</h4>
                    <ul class="space-y-2 text-sm text-gray-400">
                        <li><a href="#" class="hover:text-white transition-colors">Transparencia</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Plan de Desarrollo</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Normatividad</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Convocatorias</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="font-semibold mb-4">Atención al Ciudadano</h4>
                    <ul class="space-y-2 text-sm text-gray-400">
                        <li><a href="#" class="hover:text-white transition-colors">PQRS</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Notificaciones Judiciales</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Política de Privacidad</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Términos de Uso</a></li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-blue-900 pt-8 text-center text-sm text-blue-200">
                © {{ date('Y') }} Alcaldía Municipal de Río Viejo, Bolívar. Todos los derechos reservados.
            </div>
        </div>
    </footer>
   @vite(['resources/js/app.js'])
   <script src="{{ asset('js/app.js') }}"></script>

</body>

</html>