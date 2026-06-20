const CACHE = 'bd-fashion-v1';
const STATIC_ASSETS = [
  '/assets/css/style.css?v=2',
  '/assets/img/logo.svg',
  '/assets/img/hero.svg',
  '/assets/img/pwa-icon-192.svg',
  '/assets/img/pwa-icon-512.svg',
  '/assets/img/placeholder.svg',
  '/offline.php',
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE).then(cache => {
      return cache.addAll(STATIC_ASSETS);
    }).then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys => {
      return Promise.all(
        keys.filter(k => k !== CACHE).map(k => caches.delete(k))
      );
    }).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', event => {
  const req = event.request;
  const url = new URL(req.url);

  if (req.method !== 'GET') return;

  if (url.origin !== location.origin) {
    event.respondWith(
      caches.match(req).then(cached => cached || fetch(req))
    );
    return;
  }

  if (STATIC_ASSETS.includes(url.pathname) || /\.(css|js|woff2?|ttf|svg|png|jpg|jpeg|gif|webp|ico)(\?.*)?$/.test(url.pathname)) {
    event.respondWith(
      caches.match(req).then(cached => {
        const fetched = fetch(req).then(res => {
          const copy = res.clone();
          if (res.ok) caches.open(CACHE).then(c => c.put(req, copy));
          return res;
        });
        return cached || fetched;
      })
    );
    return;
  }

  event.respondWith(
    fetch(req).then(res => {
      if (res.ok && res.type === 'basic') {
        const copy = res.clone();
        caches.open(CACHE).then(c => c.put(req, copy));
      }
      return res;
    }).catch(() => {
      return caches.match(req).then(cached => {
        if (cached) return cached;
        if (req.mode === 'navigate') {
          return caches.match('/offline.php');
        }
        return new Response('Offline', { status: 503 });
      });
    })
  );
});
