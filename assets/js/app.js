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
})();
