/*
 * produto.js · página do produto
 *  - mostra o tamanho escolhido e o aviso de últimas unidades
 *  - adiciona à sacola sem recarregar a página (com aviso "Adicionado à sacola")
 *  - botão de compartilhar
 */
(function () {
  'use strict';

  const form = document.getElementById('form-sacola');
  if (!form) return;

  const resumo = document.getElementById('resumo-tamanho');
  const avisoEstoque = document.getElementById('aviso-estoque');
  const grupoTamanhos = document.getElementById('tamanhos');
  const botao = document.querySelector('button[form="form-sacola"]');
  const textoOriginalEstoque = avisoEstoque ? avisoEstoque.textContent.trim() : '';

  form.addEventListener('change', (ev) => {
    if (ev.target.name !== 'tamanho') return;
    const estoque = Number(ev.target.dataset.estoque);
    if (resumo) resumo.textContent = 'Tamanho ' + ev.target.value;
    if (avisoEstoque) {
      avisoEstoque.textContent = estoque <= 3
        ? (estoque === 1 ? 'Última unidade neste tamanho.' : `Últimas ${estoque} unidades neste tamanho.`)
        : textoOriginalEstoque;
      avisoEstoque.classList.toggle('texto-erro', estoque <= 3);
    }
  });

  // Chacoalha os tamanhos quando o cliente tenta adicionar sem escolher
  function chacoalhar(el) {
    if (!el || Triade.reduzirMovimento()) return;
    Triade.mola({
      de: 0, para: 0, velocidade: 900, damping: 0.25, response: 0.25,
      aoAtualizar: (x) => { el.style.transform = `translateX(${x}px)`; },
    }).irPara(0, { velocidade: 900 });
  }

  form.addEventListener('submit', async (ev) => {
    ev.preventDefault();
    const escolhido = form.querySelector('input[name="tamanho"]:checked');
    if (!escolhido) {
      Triade.aviso('Escolha um tamanho', { tipo: 'erro' });
      chacoalhar(grupoTamanhos);
      if (grupoTamanhos) grupoTamanhos.scrollIntoView({ behavior: 'smooth', block: 'center' });
      return;
    }

    botao.disabled = true;
    try {
      const resposta = await Triade.enviar(form.action, new FormData(form));
      if (resposta.ok) {
        Triade.atualizarContadorSacola(resposta.quantidade);
        Triade.aviso(resposta.mensagem, {
          acao: { rotulo: 'Ver sacola', href: form.action.replace(/api\/sacola\.php$/, 'sacola.php') },
        });
      } else {
        Triade.aviso(resposta.mensagem, { tipo: 'erro' });
      }
    } catch (erro) {
      // sem conexão: envia o formulário do jeito tradicional
      form.submit();
    } finally {
      botao.disabled = false;
    }
  });

  // Compartilhar: usa o menu do celular quando existe; senão copia o link
  document.querySelectorAll('[data-compartilhar]').forEach((el) => {
    el.addEventListener('click', async () => {
      const dados = { title: document.title, url: location.href };
      try {
        if (navigator.share) {
          await navigator.share(dados);
        } else {
          await navigator.clipboard.writeText(location.href);
          Triade.aviso('Link copiado');
        }
      } catch (e) { /* o cliente cancelou */ }
    });
  });

  // Calcular frete pelo CEP sem recarregar a página
  const formFrete = document.querySelector('form[data-frete]');
  if (formFrete) {
    const campo = formFrete.querySelector('input[name="cep"]');
    const resultado = document.getElementById('frete-resultado');
    const caixa = campo.closest('.campo');
    const ajuda = caixa.querySelector('.campo__ajuda');
    const mostrarErro = (texto) => {
      caixa.classList.toggle('campo--erro', !!texto);
      ajuda.textContent = texto || '';
      if (texto) campo.setAttribute('aria-invalid', 'true'); else campo.removeAttribute('aria-invalid');
    };

    campo.addEventListener('input', () => {
      const n = campo.value.replace(/\D+/g, '').slice(0, 8);
      campo.value = n.length > 5 ? n.slice(0, 5) + '-' + n.slice(5) : n;
      if (n.length === 8) formFrete.requestSubmit(); // calcula sozinho ao completar o CEP
    });

    formFrete.addEventListener('submit', async (ev) => {
      ev.preventDefault();
      const cep = campo.value.replace(/\D+/g, '');
      if (cep.length !== 8) { mostrarErro('Digite o CEP com 8 números.'); return; }
      resultado.setAttribute('aria-busy', 'true');
      try {
        const url = `${formFrete.dataset.frete}?cep=${cep}&valor=${formFrete.dataset.valor}`;
        const resposta = await (await fetch(url, { headers: { 'X-Requested-With': 'fetch' } })).json();
        mostrarErro(resposta.ok ? null : resposta.mensagem);
        resultado.innerHTML = resposta.ok ? resposta.html : '';
      } catch (e) {
        formFrete.submit();
      } finally {
        resultado.removeAttribute('aria-busy');
      }
    });
  }
})();
