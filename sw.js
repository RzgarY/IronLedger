// v2 — bumped so every device picks up this fix and drops the old,
// stale-caching version of this file.
const CACHE_NAME = 'iron-ledger-v2';
const CORE_ASSETS = ['/index.html', '/manifest.json'];

self.addEventListener('install', (e) => {
  e.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.addAll(CORE_ASSETS))
  );
  self.skipWaiting();
});

self.addEventListener('activate', (e) => {
  e.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k)))
    )
  );
  self.clients.claim();
});

// API calls: always go to the network; only use a cached copy if the
// network request itself fails (e.g. offline).
//
// Everything else (index.html, manifest, etc.): network-FIRST, not
// cache-first. This app is actively being updated, so every reload should
// try to get the latest file from the server. The cache is only a fallback
// for when there's no connection at all — it should never be preferred
// over a live copy while you're online. (This was backwards before, which
// is why old versions kept showing up after re-uploading index.html.)
self.addEventListener('fetch', (e) => {
  const url = new URL(e.request.url);
  if (url.pathname.includes('/api/')) {
    e.respondWith(fetch(e.request).catch(() => caches.match(e.request)));
    return;
  }
  e.respondWith(
    fetch(e.request)
      .then((res) => {
        const resClone = res.clone();
        caches.open(CACHE_NAME).then((cache) => cache.put(e.request, resClone));
        return res;
      })
      .catch(() => caches.match(e.request))
  );
});
