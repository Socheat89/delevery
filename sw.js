const CACHE_NAME = 'delitrack-v1';
const STATIC_ASSETS = [
    'index.php',
    'driver/index.php',
];

// Install: cache static pages
self.addEventListener('install', event => {
    self.skipWaiting();
});

// Activate: clean old caches
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys =>
            Promise.all(keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k)))
        )
    );
    self.clients.claim();
});

// Fetch: network first, fallback to cache
self.addEventListener('fetch', event => {
    // Skip API requests from caching
    if (event.request.url.includes('/api/')) return;

    event.respondWith(
        fetch(event.request).catch(() => caches.match(event.request))
    );
});
