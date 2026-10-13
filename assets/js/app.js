/*
 * app.js · comportamentos gerais de todas as páginas
 *  - barra de abas que encolhe ao rolar para baixo e volta ao rolar para cima
 *  - pontos do carrossel do banner
 *  - botões encolhendo ao encostar o dedo também no iPhone
 */
(function () {
  'use strict';

  // No Safari do iPhone, o :active do CSS só funciona se a página escutar o toque.
  document.addEventListener('touchstart', function () {}, { passive: true });

  // ---------- Barra de abas ----------
  // damping 1,0 e response 0,35s, como no design
  const barra = document.querySelector('.tab-bar');
  if (barra) {
    const mola = Triade.mola({
      de: 0,
      aoAtualizar: (p) => {
        // p = 0 normal, p = 1 recolhida
        barra.style.transform = `translateY(${p * 10}px) scale(${1 - p * 0.14})`;
      },
    });
    let ultimoY = window.scrollY;
    let recolhida = false;

    window.addEventListener('scroll', () => {
      const y = window.scrollY;
      const delta = y - ultimoY;
      if (Math.abs(delta) < 8) return;
      const deveRecolher = delta > 0 && y > 80;
      if (deveRecolher !== recolhida) {
        recolhida = deveRecolher;
        mola.irPara(recolhida ? 1 : 0, { damping: 1, response: 0.35 });
      }
      ultimoY = y;
    }, { passive: true });
  }

  // ---------- Aviso vindo do PHP (depois de enviar um formulário sem JavaScript) ----------
  const pendente = document.getElementById('aviso-pendente');
  if (pendente) {
    const d = pendente.dataset;
    Triade.aviso(d.texto, {
      tipo: d.tipo,
      acao: d.acaoRotulo ? { rotulo: d.acaoRotulo, href: d.acaoHref } : null,
    });
  }

  // ---------- Contador da sacola na barra de abas ----------
  Triade.atualizarContadorSacola = function (quantidade) {
    document.querySelectorAll('[data-contador-sacola]').forEach((el) => {
      el.textContent = quantidade;
      el.hidden = quantidade < 1;
      if (quantidade > 0 && !Triade.reduzirMovimento()) {
        // pulinho para chamar atenção
        Triade.mola({ de: 1.5, para: 1, damping: 0.5, response: 0.35,
          aoAtualizar: (v) => { el.style.transform = `scale(${v})`; } });
      }
    });
  };

  /** Envia um formulário com fetch e devolve o JSON da resposta. */
  Triade.enviar = async function (url, dados) {
    const csrf = document.querySelector('meta[name="csrf"]');
    const resposta = await fetch(url, {
      method: 'POST',
      body: dados,
      headers: { 'X-Requested-With': 'fetch', 'X-CSRF-Token': csrf ? csrf.content : '' },
      credentials: 'same-origin',
    });
    return resposta.json();
  };

  // ---------- Carrossel ----------
  // O deslize com impulso e o encaixe por página são do próprio navegador (CSS scroll-snap).
  // Aqui só atualizamos os pontinhos.
  document.querySelectorAll('.carrossel').forEach((carrossel) => {
    const faixa = carrossel.querySelector('.rolagem-horizontal');
    const pontos = carrossel.querySelectorAll('.pontos-carrossel span');
    if (!faixa || !pontos.length) return;
    const itens = Array.from(faixa.children);
    const observador = new IntersectionObserver((entradas) => {
      entradas.forEach((entrada) => {
        if (entrada.intersectionRatio < 0.6) return;
        const i = itens.indexOf(entrada.target);
        pontos.forEach((p, j) => p.setAttribute('aria-current', i === j ? 'true' : 'false'));
      });
    }, { root: faixa, threshold: [0.6] });
    itens.forEach((item) => observador.observe(item));
  });

  // ---------- Carregando (tela 31 · esqueleto) ----------
  // Se a próxima página demorar (internet lenta, hospedagem gratuita), mostra o esqueleto
  // no lugar do conteúdo, em vez de deixar a tela parada sem resposta.
  const ESPERA_ESQUELETO = 300;
  let timerEsqueleto = null;

  function esqueleto(tipo) {
    const linha = (largura) => `<div class="esqueleto esqueleto--texto" style="width:${largura}%"></div>`;
    const card = `<div class="pilha-sm"><div class="esqueleto esqueleto--midia"></div>${linha(90)}${linha(50)}</div>`;
    const corpo = tipo === 'produto'
      ? `<div class="esqueleto esqueleto--midia" style="border-radius:0"></div><div class="conteudo pilha" style="padding-top:var(--spacing-xl)">
           <div class="esqueleto esqueleto--titulo" style="width:80%"></div>${linha(40)}<div class="esqueleto esqueleto--titulo" style="width:35%;height:32px"></div>
           <div class="linha">${'<div class="esqueleto" style="width:52px;height:44px;border-radius:12px"></div>'.repeat(5)}</div>${linha(95)}${linha(85)}</div>`
      : `<div class="conteudo pilha"><div class="esqueleto esqueleto--titulo"></div>
           <div class="linha">${'<div class="esqueleto" style="width:84px;height:36px;border-radius:999px"></div>'.repeat(3)}</div>
           <div class="grade-produtos">${card.repeat(4)}</div></div>`;
    const el = document.createElement('div');
    el.className = 'carregando' + (tipo === 'produto' ? ' carregando--produto' : '');
    el.setAttribute('role', 'status');
    el.innerHTML = `<span class="visualmente-oculto">Carregando…</span>${corpo}`;
    return el;
  }

  function agendarEsqueleto(destino) {
    clearTimeout(timerEsqueleto);
    const tipo = /produto\.php/.test(destino) ? 'produto' : 'lista';
    timerEsqueleto = setTimeout(() => {
      document.querySelectorAll('.carregando').forEach((el) => el.remove());
      document.body.appendChild(esqueleto(tipo));
    }, ESPERA_ESQUELETO);
  }

  document.addEventListener('click', (ev) => {
    const link = ev.target.closest('a[href]');
    if (!link || ev.defaultPrevented || ev.button !== 0 || ev.metaKey || ev.ctrlKey || ev.shiftKey || ev.altKey) return;
    if (link.target && link.target !== '_self') return;
    const url = new URL(link.href, location.href);
    if (url.origin !== location.origin || (url.pathname === location.pathname && url.hash)) return;
    if (!/(index|esportes|resultados|produto|buscar|sacola|favoritos|pedidos)\.php$|\/$/.test(url.pathname)) return;
    agendarEsqueleto(url.pathname);
  });

  document.addEventListener('submit', (ev) => {
    const form = ev.target;
    if (ev.defaultPrevented || (form.method || 'get').toLowerCase() !== 'get') return;
    agendarEsqueleto(form.action || location.href);
  });

  // voltando pelo botão "Voltar", a página antiga reaparece: tira o esqueleto
  window.addEventListener('pageshow', () => {
    clearTimeout(timerEsqueleto);
    document.querySelectorAll('.carregando').forEach((el) => el.remove());
  });

  // ---------- Site instalável e tela "Sem internet" (tela 29) ----------
  window.addEventListener('offline', () => Triade.aviso('Você está sem internet', { tipo: 'erro', duracao: 5000 }));
  window.addEventListener('online', () => Triade.aviso('Conexão de volta'));

  const sw = document.documentElement.dataset.sw;
  if (sw && 'serviceWorker' in navigator) {
    window.addEventListener('load', () => navigator.serviceWorker.register(sw).catch(() => {}));
  }
})();
