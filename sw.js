const CACHE_NAME = 'gezondheidsmeter-v2';
const urlsToCache = [
    '/',
    '/index.php',
    '/manifest.json',
    '/assets/css/style.css',
    '/assets/css/admin.css',
    '/assets/images/icons/gm192x192.png',
    '/assets/images/icons/gm512x512.png',
    '/js/pwa.js',
    '/pages/home.php',
    '/pages/account.php',
    '/pages/geschiedenis.php',
    '/pages/vragen.php',
    '/src/views/auth/login.php',
    '/src/views/auth/register.php'
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
    // Check if the request is for an HTML page (navigation)
    if (event.request.mode === 'navigate' || event.request.headers.get('accept').includes('text/html')) {
        event.respondWith(
            fetch(event.request)
                .then(response => {
                    // Return the network response and cache it
                    if (!response || response.status !== 200 || response.type !== 'basic') {
                        return response;
                    }
                    const responseToCache = response.clone();
                    caches.open(CACHE_NAME)
                        .then(cache => {
                            cache.put(event.request, responseToCache);
                        });
                    return response;
                })
                .catch(() => {
                    // If network fails, try to serve from cache
                    return caches.match(event.request);
                })
        );
    } else {
        // For other assets (CSS, JS, Images), use Cache First strategy
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
