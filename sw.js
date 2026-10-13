/*
 * sw.js · "service worker": um ajudante que o navegador roda por trás do site.
 *  - guarda a página "Sem internet" (tela 29) e a mostra quando a conexão cai
 *  - guarda CSS, JS, fonte e ícones para o site abrir mais rápido
 *  - páginas e pedidos sempre vêm do servidor (preço e estoque precisam estar atualizados)
 *
 * Para atualizar o que fica guardado, troque o número da VERSAO.
 */
const VERSAO = 'triade-v1';
const BASE = new URL('./', self.location).pathname; // pasta do site (ex.: /Triade_esportes_web/)
const OFFLINE = BASE + 'offline.html';
const GUARDAR_NA_INSTALACAO = [
  OFFLINE,
  BASE + 'assets/fonts/inter-variavel.woff2',
  BASE + 'assets/img/app/icone-192.png',
];

self.addEventListener('install', (ev) => {
  ev.waitUntil(caches.open(VERSAO).then((cache) => cache.addAll(GUARDAR_NA_INSTALACAO)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (ev) => {
  ev.waitUntil(
    caches.keys()
      .then((nomes) => Promise.all(nomes.filter((n) => n !== VERSAO).map((n) => caches.delete(n))))
      .then(() => self.clients.claim())
  );
});

/* A tela "Sem internet" aparece no endereço da página que o cliente tentou abrir
   (ex.: conta/pedidos.php). O <base> faz a fonte ser buscada na pasta certa. */
async function paginaOffline() {
  const guardada = await caches.match(OFFLINE);
  if (!guardada) return Response.error();
  const html = (await guardada.text()).replace('<head>', `<head><base href="${BASE}">`);
  return new Response(html, { headers: { 'Content-Type': 'text/html; charset=utf-8' } });
}

self.addEventListener('fetch', (ev) => {
  const pedido = ev.request;
  if (pedido.method !== 'GET' || new URL(pedido.url).origin !== self.location.origin) return;

  // Páginas: sempre do servidor. Sem conexão, mostra a tela "Sem internet".
  if (pedido.mode === 'navigate') {
    ev.respondWith(fetch(pedido).catch(paginaOffline));
    return;
  }

  // Arquivos de assets/: usa o guardado na hora e atualiza por trás.
  if (new URL(pedido.url).pathname.startsWith(BASE + 'assets/')) {
    ev.respondWith(
      caches.open(VERSAO).then(async (cache) => {
        const guardado = await cache.match(pedido);
        const daRede = fetch(pedido).then((resposta) => {
          if (resposta.ok) cache.put(pedido, resposta.clone());
          return resposta;
        }).catch(() => guardado);
        return guardado || daRede;
      })
    );
  }
});
