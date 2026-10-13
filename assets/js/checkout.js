/*
 * checkout.js · etapas da compra
 *  - Entrega: atualiza o total da barra inferior ao trocar a opção
 *  - Pagamento: mostra os campos do cartão só quando o cartão está escolhido
 *  - Pix aguardando: contagem regressiva e botão de copiar o código
 */
(function () {
  'use strict';

  const totalBarra = document.querySelector('.barra-inferior .t-headline');
  const dinheiro = (v) => 'R$ ' + v.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');

  // ---------- Entrega ----------
  const formEntrega = document.querySelector('form[data-opcoes-total]');
  if (formEntrega && totalBarra) {
    const subtotal = Number(formEntrega.dataset.subtotal);
    formEntrega.addEventListener('change', (ev) => {
      if (ev.target.name === 'entrega') totalBarra.textContent = dinheiro(subtotal + Number(ev.target.dataset.preco));
    });
  }

  // ---------- Pagamento ----------
  const formPagamento = document.querySelector('form[data-pagamento]');
  if (formPagamento) {
    const cartao = document.getElementById('dados-cartao');
    const atualizar = () => {
      const escolhido = formPagamento.querySelector('input[name="pagamento"]:checked');
      const tipo = escolhido ? escolhido.value : '';
      cartao.hidden = tipo !== 'cartao';
      if (totalBarra) {
        totalBarra.textContent = dinheiro(Number(tipo === 'pix' ? formPagamento.dataset.totalPix : formPagamento.dataset.totalCartao));
      }
    };
    formPagamento.addEventListener('change', (ev) => {
      if (ev.target.name !== 'pagamento') return;
      atualizar();
      if (ev.target.value === 'cartao') cartao.querySelector('input').focus({ preventScroll: true });
    });
    atualizar();
  }

  // ---------- Pix aguardando ----------
  const relogio = document.querySelector('[data-expira-em]');
  if (relogio) {
    const fim = Number(relogio.dataset.expiraEm) * 1000;
    const tick = () => {
      const resto = Math.max(0, fim - Date.now());
      const min = Math.floor(resto / 60000);
      const seg = Math.floor((resto % 60000) / 1000);
      relogio.textContent = `${String(min).padStart(2, '0')}:${String(seg).padStart(2, '0')}`;
      if (resto === 0) {
        clearInterval(intervalo);
        location.reload(); // o PHP mostra o aviso de código expirado
      }
    };
    const intervalo = setInterval(tick, 1000);
    tick();
  }

  document.querySelectorAll('[data-copiar]').forEach((botao) => {
    botao.addEventListener('click', async () => {
      const alvo = document.getElementById(botao.dataset.copiar);
      try {
        await navigator.clipboard.writeText(alvo.value || alvo.textContent);
        Triade.aviso('Código Pix copiado');
      } catch (e) {
        alvo.select && alvo.select();
        Triade.aviso('Selecione e copie o código', { tipo: 'erro' });
      }
    });
  });
})();
