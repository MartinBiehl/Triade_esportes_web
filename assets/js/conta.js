/*
 * conta.js · favoritos e ações da conta
 *  - coração de favorito sem recarregar a página (sem login, leva para Entrar)
 *  - na tela Favoritos, o card some ao desfavoritar, com "Desfazer" no aviso
 *  - formulários com data-confirmar pedem confirmação antes de enviar (ex.: excluir endereço)
 */
(function () {
  'use strict';

  const animar = (el, quadros, ms) => (Triade.reduzirMovimento() ? Promise.resolve() : el.animate(quadros, { duration: ms, easing: 'ease-out' }).finished);

  function marcar(form, favorito) {
    const botao = form.querySelector('button');
    botao.setAttribute('aria-pressed', String(favorito));
    botao.setAttribute('aria-label', favorito ? 'Remover dos favoritos' : 'Favoritar');
    if (favorito) animar(botao, [{ transform: 'scale(1.3)' }, { transform: 'scale(1)' }], 300);
  }

  // ---------- Tela Favoritos: card some e pode voltar ----------
  function atualizarContagem() {
    const visiveis = document.querySelectorAll('[data-card-favorito]:not([hidden])').length;
    const contagem = document.getElementById('favoritos-contagem');
    const vazio = document.getElementById('favoritos-vazio');
    if (contagem) {
      contagem.textContent = visiveis + (visiveis === 1 ? ' produto salvo' : ' produtos salvos');
      contagem.hidden = visiveis === 0;
    }
    if (vazio) vazio.hidden = visiveis > 0;
  }

  async function enviarFavorito(form) {
    const resposta = await Triade.enviar(form.action, new FormData(form));
    if (resposta.entrar) {
      location.href = resposta.entrar;
      return null;
    }
    if (!resposta.ok) {
      Triade.aviso(resposta.mensagem, { tipo: 'erro' });
      return null;
    }
    marcar(form, resposta.favorito);
    return resposta;
  }

  document.querySelectorAll('form[data-favorito]').forEach((form) => {
    form.addEventListener('submit', async (ev) => {
      ev.preventDefault();
      const card = form.closest('[data-card-favorito]');
      try {
        const resposta = await enviarFavorito(form);
        if (!resposta) return;

        if (card && !resposta.favorito) {
          await animar(card, [{ opacity: 1, transform: 'scale(1)' }, { opacity: 0, transform: 'scale(0.92)' }], 220);
          card.hidden = true;
          atualizarContagem();
          Triade.aviso(resposta.mensagem, {
            acao: {
              rotulo: 'Desfazer',
              aoClicar: async () => {
                const volta = await enviarFavorito(form);
                if (volta && volta.favorito) {
                  card.hidden = false;
                  atualizarContagem();
                  animar(card, [{ opacity: 0, transform: 'scale(0.92)' }, { opacity: 1, transform: 'scale(1)' }], 220);
                }
              },
            },
          });
        } else {
          Triade.aviso(resposta.mensagem, resposta.favorito && !card
            ? { acao: { rotulo: 'Ver', href: form.action.replace(/api\/favoritos\.php$/, 'conta/favoritos.php') } }
            : {});
        }
      } catch (erro) {
        form.submit(); // sem conexão com o fetch: envia do jeito tradicional
      }
    });
  });

  // ---------- Confirmação antes de enviar ----------
  document.querySelectorAll('form[data-confirmar]').forEach((form) => {
    form.addEventListener('submit', (ev) => {
      if (!confirm(form.dataset.confirmar)) ev.preventDefault();
    });
  });
})();
