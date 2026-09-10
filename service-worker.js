const CACHE_NAME = 'farmacerta-pwa-v1';

const ARQUIVOS_ESTATICOS = [
  './assets/LOGO_1.png',
  './assets/LOGO_2.png',
  './assets/farmacerta-icon-192x192.png',
  './assets/farmacerta-icon-512x512.png',
  './assets/css/tema.css',
  './manifest.webmanifest'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.addAll(ARQUIVOS_ESTATICOS))
  );
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((nomes) =>
      Promise.all(
        nomes
          .filter((nome) => nome !== CACHE_NAME)
          .map((nome) => caches.delete(nome))
      )
    )
  );
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  const requisicao = event.request;

  if (requisicao.method !== 'GET') {
    return;
  }

  const url = new URL(requisicao.url);

  if (url.origin !== self.location.origin) {
    return;
  }

  if (requisicao.mode === 'navigate') {
    event.respondWith(
      fetch(requisicao).catch(() => caches.match(requisicao))
    );
    return;
  }

  event.respondWith(
    caches.match(requisicao).then((respostaCache) => {
      if (respostaCache) {
        return respostaCache;
      }

      return fetch(requisicao).then((respostaRede) => {
        if (!respostaRede || respostaRede.status !== 200 || respostaRede.type !== 'basic') {
          return respostaRede;
        }

        const copia = respostaRede.clone();
        caches.open(CACHE_NAME).then((cache) => cache.put(requisicao, copia));
        return respostaRede;
      });
    })
  );
});
