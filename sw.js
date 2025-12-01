const CACHE_NAME = 'gezondheidsmeter-v4';
const urlsToCache = [
    '/',
    '/index.php',
    '/manifest.json',
    '/assets/css/style.css',
    '/assets/css/admin.css',
    '/assets/images/icons/gm192x192.png',
    '/assets/images/icons/gm512x512.png',
    '/js/pwa.js'
];

self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('Opened cache');
                return cache.addAll(urlsToCache);
            })
    );
    self.skipWaiting();
});

self.addEventListener('fetch', event => {
    const url = new URL(event.request.url);

    // Network-first strategy for admin pages and PHP files
    if (url.pathname.includes('/admin/') ||
        url.pathname.endsWith('.php') ||
        url.pathname.includes('/pages/') ||
        url.pathname.includes('/src/')) {
        event.respondWith(
            fetch(event.request)
                .then(response => {
                    // Clone the response before caching
                    const responseToCache = response.clone();
                    caches.open(CACHE_NAME)
                        .then(cache => {
                            cache.put(event.request, responseToCache);
                        });
                    return response;
                })
                .catch(() => {
                    // If network fails, try cache
                    return caches.match(event.request);
                })
        );
    } else {
        // Cache-first strategy for static assets
        event.respondWith(
            caches.match(event.request)
                .then(response => {
                    if (response) {
                        return response;
                    }
                    return fetch(event.request);
                })
        );
    }
});

self.addEventListener('activate', event => {
    const cacheWhitelist = [CACHE_NAME];
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheWhitelist.indexOf(cacheName) === -1) {
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});
