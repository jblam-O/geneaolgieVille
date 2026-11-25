const CACHE = 'genealogy-shell-v3';
const ASSETS = ['/genealogy/index.html', '/genealogy/assets/app.js', '/genealogy/assets/index.css', '/genealogy/assets/html2canvas.esm.js', '/genealogy/assets/index.es.js', '/genealogy/assets/purify.es.js', '/genealogy/manifest.webmanifest', '/genealogy/icon-192.png', '/genealogy/icon-512.png'];
self.addEventListener('install', event => { event.waitUntil(caches.open(CACHE).then(cache => cache.addAll(ASSETS))); });
self.addEventListener('activate', event => { event.waitUntil(caches.keys().then(keys => Promise.all(keys.filter(key => key.startsWith('genealogy-shell-') && key !== CACHE).map(key => caches.delete(key)))).then(() => self.clients.claim())); });
self.addEventListener('fetch', event => {
  const url = new URL(event.request.url);
  if (url.origin !== self.location.origin || event.request.method !== 'GET') return;
  if (event.request.mode === 'navigate' && url.pathname === '/visualisation') {
    event.respondWith(fetch(event.request).catch(async () => {
      const cache = await caches.open(CACHE);
      const shell = await cache.match('/genealogy/index.html');
      // The account is kept separately; never cache authenticated HTML or API responses.
      const identity = await cache.match('/offline-identity');
      if (!shell || !identity) return new Response('Ouvrez l’atelier avec Internet pour préparer le mode hors connexion.', { status: 503 });
      const account = await identity.text();
      const html = (await shell.text()).replace('id="genealogy-root"', `id="genealogy-root" data-account="${account.replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]))}"`);
      return new Response(html, { headers: { 'Content-Type': 'text/html; charset=utf-8' } });
    }));
  } else if (url.pathname.startsWith('/genealogy/')) {
    event.respondWith(caches.open(CACHE).then(async cache => (await cache.match(event.request, { ignoreSearch: true })) || fetch(event.request)));
  } else if (url.pathname === '/logout') {
    event.waitUntil(caches.open(CACHE).then(cache => cache.delete('/offline-identity')));
  }
});
self.addEventListener('message', event => {
  if (event.data?.type === 'account' && typeof event.data.account === 'string') event.waitUntil(caches.open(CACHE).then(cache => cache.put('/offline-identity', new Response(event.data.account))));
});
