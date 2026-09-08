const CACHE_NAME = 'santini-v1.1.0';

// Núcleo de la app: instalable y funcional sin conexión
const cacheAssets = [
    '/manifest.json',
    '/favicon.ico',
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
    request.method === 'GET' &&
    (request.destination === 'script' ||
     request.destination === 'style' ||
     request.destination === 'image' ||
     request.destination === 'font' ||
     request.destination === 'manifest');

self.addEventListener('fetch', event => {
    // Solo responde a GET
    if (event.request.method !== 'GET') return;

    // Navegacion: red-primero, cache de respaldo offline
    if (isNavigate(event.request)) {
        event.respondWith(
            fetch(event.request)
                .then(response => {
                    const copy = response.clone();
                    caches.open(CACHE_NAME).then(cache => cache.put('/', copy));
                    return response;
                })
                .catch(() =>
                    caches.match(event.request).then(cached => {
                        const fallback = cached || caches.match('/');
                        return fallback || caches.match('/manifest.json');
                    })
                )
        );
        return;
    }

    // Navegacion dentro de la SPA (rutas suaves): cache,
    // luego red, revalidando la entrada en cache
    if (isAsset(event.request)) {
        event.respondWith(
            caches.match(event.request).then(cached => {
                const network = fetch(event.request)
                    .then(response => {
                        if (response && response.status === 200) {
                            const copy = response.clone();
                            caches.open(CACHE_NAME).then(cache => cache.put(event.request, copy));
                        }
                        return response;
                    })
                    .catch(() => cached);
                return cached || network;
            })
        );
        return;
    }

    // Otros GET: red con cache de respaldo (ej. API, HTML)
    event.respondWith(
        caches.match(event.request).then(cached => {
            return cached || fetch(event.request).then(response => {
                const copy = response.clone();
                caches.open(CACHE_NAME).then(cache => cache.put(event.request, copy));
                return response;
            }).catch(() => cached);
        })
    );
});