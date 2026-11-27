const CACHE_NAME = 'santini-v1.2.0';

// Núcleo de la app: instalable y con respaldo sin conexión
const cacheAssets = [
    '/manifest.json',
    '/favicon.ico',
    '/css/tailwind.css',
    '/pwa/icons/android/launchericon-192x192.png',
    '/pwa/icons/android/launchericon-512x512.png',
    '/pwa/icons/ios/192.png',
];

self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => {
            return Promise.allSettled(
                cacheAssets.map(url => cache.add(url).catch(() => {}))
            );
        }).then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keyList => {
            return Promise.all(keyList.map(key => {
                if (key !== CACHE_NAME) {
                    return caches.delete(key);
                }
            }));
        }).then(() => self.clients.claim())
    );
});

const isNavigate = request =>
    request.mode === 'navigate' ||
    (request.method === 'GET' && request.destination === 'document');

const isAsset = request =>
    request.destination === 'script' ||
    request.destination === 'style' ||
    request.destination === 'image' ||
    request.destination === 'font' ||
    request.destination === 'manifest';

// Descargas (PDF, CSV) y recibos: nunca se guardan
const noCachear = url =>
    /\/(exportar|exportar-pdf|reporte-pdf)(\/|$)/.test(url.pathname) ||
    /\.(pdf|csv)$/i.test(url.pathname);

const guardar = (request, response) => {
    if (!response || response.status !== 200 || response.type === 'opaqueredirect') return;
    const tipo = response.headers.get('content-type') || '';
    if (/application\/(pdf|octet-stream)|text\/csv/.test(tipo)) return;
    const copy = response.clone();
    caches.open(CACHE_NAME).then(cache => cache.put(request, copy));
};

self.addEventListener('fetch', event => {
    const request = event.request;
    if (request.method !== 'GET') return;

    const url = new URL(request.url);
    if (noCachear(url)) return; // el navegador la maneja directo

    // Páginas: red primero (datos al día); sin conexión, la copia de esa misma URL
    if (isNavigate(request)) {
        event.respondWith(
            fetch(request)
                .then(response => {
                    // No se guarda la pantalla de login ni redirecciones
                    if (!response.redirected) guardar(request, response);
                    return response;
                })
                .catch(() =>
                    caches.match(request).then(cached => cached || caches.match('/gestion'))
                )
        );
        return;
    }

    // Archivos versionados (/build con hash, ?v=): caché primero, sin revalidar
    if (isAsset(request) && (url.pathname.startsWith('/build/') || url.searchParams.has('v'))) {
        event.respondWith(
            caches.match(request).then(cached => cached || fetch(request).then(response => {
                guardar(request, response);
                return response;
            }))
        );
        return;
    }

    // Resto de archivos estáticos: caché y revalidación en segundo plano
    if (isAsset(request)) {
        event.respondWith(
            caches.match(request).then(cached => {
                const network = fetch(request)
                    .then(response => {
                        guardar(request, response);
                        return response;
                    })
                    .catch(() => cached);
                return cached || network;
            })
        );
        return;
    }

    // Otros GET (datos): red primero, caché solo como respaldo sin conexión
    event.respondWith(
        fetch(request)
            .then(response => {
                guardar(request, response);
                return response;
            })
            .catch(() => caches.match(request))
    );
});
