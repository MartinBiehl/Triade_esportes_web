/*
 * aviso.js · aviso flutuante (Toast) em vidro escuro
 *
 * Entra pelo topo com mola e sai pelo mesmo caminho depois de 3 segundos.
 * Uso:
 *   Triade.aviso('Adicionado à sacola', { acao: { rotulo: 'Ver sacola', href: 'sacola.php' } });
 *   Triade.aviso('Item removido', { acao: { rotulo: 'Desfazer', aoClicar: () => {...} } });
 *   Triade.aviso('Não foi possível salvar', { tipo: 'erro' });
 */
(function () {
  'use strict';

  const ENTRADA = { damping: 0.75, response: 0.4 };
  const SAIDA = { damping: 1, response: 0.3 };
  let atual = null;

  function icone(nome, classe) {
    const sprite = document.documentElement.dataset.icones || 'assets/img/icones.svg';
    return `<svg class="icone aviso__icone ${classe || ''}" aria-hidden="true"><use href="${sprite}#i-${nome}"></use></svg>`;
  }

  Triade.aviso = function (texto, opcoes) {
    opcoes = opcoes || {};
    const duracao = opcoes.duracao ?? 3000;
    const erro = opcoes.tipo === 'erro';

    if (atual) atual.fechar(true);

    const el = document.createElement('div');
    el.className = 'aviso';
    el.setAttribute('role', erro ? 'alert' : 'status');
    el.innerHTML = icone(erro ? 'alerta' : 'check-circulo', erro ? 'aviso__icone--erro' : '') +
                   '<span class="aviso__texto"></span>';
    el.querySelector('.aviso__texto').textContent = texto;

    if (opcoes.acao) {
      const acao = document.createElement(opcoes.acao.href ? 'a' : 'button');
      acao.className = 'aviso__acao';
      acao.textContent = opcoes.acao.rotulo;
      if (opcoes.acao.href) acao.href = opcoes.acao.href;
      else acao.type = 'button';
      acao.addEventListener('click', () => {
        if (opcoes.acao.aoClicar) opcoes.acao.aoClicar();
        controle.fechar();
      });
      el.appendChild(acao);
    }

    document.body.appendChild(el);

    // progresso 0 = escondido acima da tela, 1 = visível
    const desenhar = (p) => {
      const reduzir = Triade.reduzirMovimento();
      const altura = el.offsetHeight + 24;
      el.style.transform = reduzir ? 'none' : `translateY(${(1 - p) * -altura}px)`;
      el.style.opacity = reduzir ? Math.max(0, Math.min(1, p)) : Math.min(1, Math.max(0, p * 2));
    };
    const mola = Triade.mola({ de: 0, para: 1, ...ENTRADA, aoAtualizar: desenhar });

    let timer = setTimeout(() => controle.fechar(), duracao);
    // dedo ou mouse em cima segura o aviso na tela
    el.addEventListener('pointerenter', () => clearTimeout(timer));
    el.addEventListener('pointerleave', () => { timer = setTimeout(() => controle.fechar(), 1500); });

    const controle = {
      fechar(imediato) {
        clearTimeout(timer);
        if (atual === controle) atual = null;
        if (imediato) { mola.parar(); el.remove(); return; }
        mola.irPara(0, { ...SAIDA, aoTerminar: () => el.remove() });
      },
    };
    atual = controle;
    return controle;
  };
})();
