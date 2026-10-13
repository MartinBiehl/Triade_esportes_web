/*
 * sacola.js · tela da Sacola
 *  - muda a quantidade sem recarregar a página
 *  - deslizar o item para a esquerda revela "Remover"; soltar com força remove
 *  - remover não pede confirmação: o aviso traz "Desfazer" (como no design)
 */
(function () {
  'use strict';

  const area = document.getElementById('sacola-conteudo');
  if (!area) return;

  const api = (document.querySelector('form[data-form-sacola]') || {}).action;

  /** Busca a página de novo e troca só o conteúdo da sacola (resumo, totais, itens). */
  async function atualizarTela() {
    const resposta = await fetch(location.href, { credentials: 'same-origin' });
    const html = await resposta.text();
    const nova = new DOMParser().parseFromString(html, 'text/html').getElementById('sacola-conteudo');
    if (nova) area.innerHTML = nova.innerHTML;
    ligarDeslize();
  }

  async function enviar(dados) {
    const resposta = await Triade.enviar(api, dados);
    Triade.atualizarContadorSacola(resposta.quantidade);
    return resposta;
  }

  async function remover(chave) {
    const dados = new FormData();
    dados.append('acao', 'remover');
    dados.append('chave', chave);
    const resposta = await enviar(dados);
    await atualizarTela();

    const item = resposta.removido;
    if (!item) return;
    Triade.aviso('Item removido', {
      acao: {
        rotulo: 'Desfazer',
        aoClicar: async () => {
          const volta = new FormData();
          volta.append('acao', 'restaurar');
          volta.append('produto_id', item.produto_id);
          volta.append('tamanho', item.tamanho);
          volta.append('quantidade', item.quantidade);
          const r = await enviar(volta);
          if (!r.ok) Triade.aviso(r.mensagem, { tipo: 'erro' });
          await atualizarTela();
        },
      },
    });
  }

  // ---------- Botões de quantidade ----------
  area.addEventListener('submit', async (ev) => {
    const form = ev.target.closest('form[data-form-sacola]');
    if (!form) return;
    ev.preventDefault();
    const botao = ev.submitter;
    const quantidade = Number(botao && botao.value);
    const chave = form.querySelector('[name="chave"]').value;

    if (quantidade <= 0) {
      const trilho = form.closest('.item-sacola-trilho');
      await sumir(trilho);
      return remover(chave);
    }

    const dados = new FormData(form);
    dados.set('quantidade', quantidade);
    form.querySelectorAll('button').forEach((b) => (b.disabled = true));
    const resposta = await enviar(dados);
    if (!resposta.ok && resposta.mensagem) Triade.aviso(resposta.mensagem, { tipo: 'erro' });
    await atualizarTela();
  });

  // ---------- Animação de saída: desliza para a esquerda e fecha o espaço ----------
  function sumir(trilho, velocidadeInicial) {
    return new Promise((resolver) => {
      if (!trilho) return resolver();
      const item = trilho.querySelector('.item-sacola');
      const largura = trilho.offsetWidth;
      const altura = trilho.offsetHeight;
      const atual = new DOMMatrix(getComputedStyle(item).transform).m41 || 0;

      Triade.mola({
        de: atual, para: -largura, velocidade: velocidadeInicial || 0, damping: 1, response: 0.3,
        aoAtualizar: (x) => { item.style.transform = `translateX(${x}px)`; },
        aoTerminar: () => {
          trilho.style.overflow = 'hidden';
          Triade.mola({
            de: altura, para: 0, damping: 1, response: 0.25,
            aoAtualizar: (h) => { trilho.style.height = Math.max(0, h) + 'px'; trilho.style.marginBottom = (h - altura) / altura * 8 + 'px'; },
            aoTerminar: resolver,
          });
        },
      });
    });
  }

  // ---------- Deslizar para remover ----------
  const LIMITE = 96; // quanto precisa deslizar para remover

  function ligarDeslize() {
    area.querySelectorAll('.item-sacola-trilho').forEach((trilho) => {
      if (trilho.dataset.ligado) return;
      trilho.dataset.ligado = '1';
      const item = trilho.querySelector('.item-sacola');
      const fundo = trilho.querySelector('.item-sacola__fundo');
      let x = 0;
      const mola = Triade.mola({
        de: 0,
        aoAtualizar: (v) => { x = v; desenhar(v); },
      });

      function desenhar(v) {
        item.style.transform = `translateX(${v}px)`;
        fundo.style.opacity = Math.min(1, Math.abs(v) / 40);
      }

      let gesto = null;
      item.addEventListener('pointerdown', (ev) => {
        if (ev.button !== 0 || ev.target.closest('button, a, input')) return;
        gesto = { id: ev.pointerId, x0: ev.clientX, y0: ev.clientY, inicio: x, horizontal: null, amostras: [] };
      });

      item.addEventListener('pointermove', (ev) => {
        if (!gesto || ev.pointerId !== gesto.id) return;
        const dx = ev.clientX - gesto.x0;
        const dy = ev.clientY - gesto.y0;
        if (gesto.horizontal === null) {
          if (Math.abs(dx) < 8 && Math.abs(dy) < 8) return;
          gesto.horizontal = Math.abs(dx) > Math.abs(dy);
          if (!gesto.horizontal) { gesto = null; return; } // deixa a página rolar
          item.setPointerCapture(ev.pointerId);
          trilho.setAttribute('data-arrastando', '');
          mola.parar();
        }
        let novo = gesto.inicio + dx;
        if (novo > 0) novo = novo / 4; // para a direita: resistência
        mola.definir(novo);
        gesto.amostras.push({ x: ev.clientX, t: ev.timeStamp });
        if (gesto.amostras.length > 5) gesto.amostras.shift();
      });

      function soltar(ev) {
        if (!gesto || ev.pointerId !== gesto.id) return;
        const g = gesto;
        gesto = null;
        trilho.removeAttribute('data-arrastando');
        if (!g.horizontal) return;

        const a = g.amostras[0] || { x: ev.clientX, t: ev.timeStamp };
        const b = g.amostras[g.amostras.length - 1] || a;
        const velocidade = (b.x - a.x) / Math.max((b.t - a.t) / 1000, 0.001);

        if (x < -LIMITE || velocidade < -800) {
          sumir(trilho, velocidade).then(() => remover(trilho.dataset.chave));
        } else {
          mola.irPara(0, { damping: 0.8, response: 0.3, velocidade });
        }
      }
      item.addEventListener('pointerup', soltar);
      item.addEventListener('pointercancel', soltar);

      // um toque rápido num link depois de arrastar não deve abrir o produto
      item.addEventListener('click', (ev) => { if (Math.abs(x) > 4) ev.preventDefault(); }, true);
    });
  }

  ligarDeslize();
})();
