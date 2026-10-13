/*
 * painel.js · painéis que sobem de baixo (Sheet): filtros, guia de tamanhos, Pix
 *
 * HTML esperado (gerado por painel_inicio()/painel_fim() no PHP):
 *   <div class="painel" id="filtros" role="dialog" aria-modal="true">
 *     <div class="painel__fundo" data-fechar-painel></div>
 *     <div class="painel__caixa vidro-thick"> ... </div>
 *   </div>
 * Botão que abre: <button data-abrir-painel="filtros">
 *
 * Comportamento do design: mola com damping 0,8 e response 0,3s; arrastar para baixo
 * fecha seguindo o dedo; ao soltar, a velocidade decide se fecha ou volta; o fundo escurece.
 */
(function () {
  'use strict';

  const MOLA = { damping: 0.8, response: 0.3 };
  const MOLA_FECHAR = { damping: 1, response: 0.3 };
  const paineis = new Map();
  let painelAberto = null;

  function criar(elemento) {
    const caixa = elemento.querySelector('.painel__caixa');
    const fundo = elemento.querySelector('.painel__fundo');
    const corpo = elemento.querySelector('.painel__corpo');
    let focoAnterior = null;

    // progresso: 0 = fechado, 1 = aberto
    function desenhar(p) {
      const reduzir = Triade.reduzirMovimento();
      const altura = caixa.offsetHeight || window.innerHeight;
      fundo.style.opacity = Math.max(0, Math.min(1, p));
      if (reduzir) {
        caixa.style.transform = 'none';
        caixa.style.opacity = Math.max(0, Math.min(1, p));
      } else {
        // abaixo de 0 não passa; acima de 1 (quique) sobe só um pouquinho
        const deslocamento = p > 1 ? -(p - 1) * 40 : (1 - p) * altura;
        caixa.style.transform = `translateY(${deslocamento}px)`;
        caixa.style.opacity = '';
      }
    }

    const mola = Triade.mola({ de: 0, aoAtualizar: desenhar });

    function abrir() {
      if (painelAberto && painelAberto !== api) painelAberto.fechar();
      focoAnterior = document.activeElement;
      elemento.setAttribute('data-aberto', '');
      elemento.removeAttribute('aria-hidden');
      document.documentElement.classList.add('sem-rolagem');
      painelAberto = api;
      mola.irPara(1, { ...MOLA, aoTerminar: null });
      // foco no primeiro item que dá para usar, para quem navega pelo teclado
      const focavel = caixa.querySelector('[autofocus], button, [href], input, select, textarea');
      if (focavel) focavel.focus({ preventScroll: true });
      elemento.dispatchEvent(new CustomEvent('painel:aberto'));
    }

    function fechar(velocidade) {
      mola.irPara(0, {
        ...MOLA_FECHAR,
        velocidade: velocidade ?? mola.velocidade,
        aoTerminar: () => {
          elemento.removeAttribute('data-aberto');
          elemento.setAttribute('aria-hidden', 'true');
        },
      });
      document.documentElement.classList.remove('sem-rolagem');
      if (painelAberto === api) painelAberto = null;
      if (focoAnterior && document.contains(focoAnterior)) focoAnterior.focus({ preventScroll: true });
      elemento.dispatchEvent(new CustomEvent('painel:fechado'));
    }

    // ---------- Arrastar para baixo para fechar ----------
    let arrasto = null;

    caixa.addEventListener('pointerdown', (ev) => {
      if (ev.button !== 0) return;
      // dentro do conteúdo rolável só arrasta se ele estiver no topo
      const noCorpo = corpo && corpo.contains(ev.target);
      if (noCorpo && corpo.scrollTop > 0) return;
      if (ev.target.closest('input, select, textarea, button, a, label')) return;
      arrasto = {
        id: ev.pointerId,
        inicioY: ev.clientY,
        inicioP: mola.valor,
        altura: caixa.offsetHeight,
        amostras: [{ y: ev.clientY, t: ev.timeStamp }],
        ativo: false,
      };
    });

    caixa.addEventListener('pointermove', (ev) => {
      if (!arrasto || ev.pointerId !== arrasto.id) return;
      const dy = ev.clientY - arrasto.inicioY;
      if (!arrasto.ativo) {
        if (Math.abs(dy) < 6) return;
        if (dy < 0) { arrasto = null; return; } // para cima: deixa o conteúdo rolar
        arrasto.ativo = true;
        caixa.setPointerCapture(ev.pointerId);
        mola.parar();
      }
      // para cima além do aberto: resistência (anda só um terço)
      const p = dy >= 0 ? arrasto.inicioP - dy / arrasto.altura : arrasto.inicioP + (-dy / 3) / 400;
      mola.definir(p);
      arrasto.amostras.push({ y: ev.clientY, t: ev.timeStamp });
      if (arrasto.amostras.length > 5) arrasto.amostras.shift();
    });

    function soltar(ev) {
      if (!arrasto || ev.pointerId !== arrasto.id) return;
      const dados = arrasto;
      arrasto = null;
      if (!dados.ativo) return;

      // velocidade do dedo nos últimos milissegundos (px/s), convertida para "progresso/s"
      const a = dados.amostras[0];
      const b = dados.amostras[dados.amostras.length - 1];
      const dt = Math.max((b.t - a.t) / 1000, 0.001);
      const velocidadePx = (b.y - a.y) / dt;
      const velocidadeP = -velocidadePx / dados.altura;

      // projeta onde o painel pararia se continuasse no embalo
      const projetado = mola.valor + velocidadeP * 0.2;
      if (projetado < 0.5 || velocidadePx > 900) fechar(velocidadeP);
      else mola.irPara(1, { ...MOLA, velocidade: velocidadeP });
    }
    caixa.addEventListener('pointerup', soltar);
    caixa.addEventListener('pointercancel', soltar);

    const api = { abrir, fechar: () => fechar(), elemento };
    return api;
  }

  function obter(id) {
    if (!paineis.has(id)) {
      const el = document.getElementById(id);
      if (!el || !el.classList.contains('painel')) return null;
      paineis.set(id, criar(el));
    }
    return paineis.get(id);
  }

  Triade.painel = {
    abrir(id) { const p = obter(id); if (p) p.abrir(); },
    fechar(id) { const p = id ? obter(id) : painelAberto; if (p) p.fechar(); },
  };

  document.addEventListener('click', (ev) => {
    const abre = ev.target.closest('[data-abrir-painel]');
    if (abre) {
      ev.preventDefault();
      Triade.painel.abrir(abre.getAttribute('data-abrir-painel'));
      return;
    }
    const fecha = ev.target.closest('[data-fechar-painel]');
    if (fecha && painelAberto) {
      ev.preventDefault();
      painelAberto.fechar();
    }
  });

  document.addEventListener('keydown', (ev) => {
    if (ev.key === 'Escape' && painelAberto) painelAberto.fechar();
  });
})();
