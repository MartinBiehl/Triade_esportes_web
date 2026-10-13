/*
 * admin.js · painel da loja
 *  - formulários com data-confirmar pedem confirmação (cancelar pedido, excluir)
 *  - filtro de categoria da lista de produtos aplica ao escolher
 *  - tamanhos do produto: adicionar, remover e preencher de uma vez (P a GG, 38 a 43, Único)
 *  - fotos escolhidas aparecem antes de salvar
 * Sem JavaScript tudo continua funcionando: o formulário já vem com linhas vazias de tamanho.
 */
(function () {
  'use strict';

  // ---------- Confirmação ----------
  document.querySelectorAll('form[data-confirmar]').forEach((form) => {
    form.addEventListener('submit', (ev) => {
      if (!confirm(form.dataset.confirmar)) ev.preventDefault();
    });
  });

  // ---------- Filtro que envia sozinho ----------
  document.querySelectorAll('[data-enviar-ao-mudar]').forEach((campo) => {
    campo.addEventListener('change', () => campo.form.submit());
  });

  // ---------- Tamanhos e estoque ----------
  const lista = document.querySelector('[data-lista-tamanhos]');
  if (lista) {
    const linhas = () => Array.from(lista.querySelectorAll('[data-linha-tamanho]'));
    const modelo = linhas()[0].cloneNode(true);

    function renumerar() {
      linhas().forEach((linha, i) => {
        const [tamanho, estoque] = linha.querySelectorAll('input');
        tamanho.setAttribute('aria-label', `Tamanho ${i + 1}`);
        estoque.setAttribute('aria-label', `Estoque do tamanho ${i + 1}`);
        const remover = linha.querySelector('[data-remover-tamanho]');
        remover.setAttribute('aria-label', `Remover tamanho ${i + 1}`);
        remover.hidden = linhas().length === 1;
      });
    }

    function novaLinha(tamanho = '', estoque = '') {
      const linha = modelo.cloneNode(true);
      const [campoTamanho, campoEstoque] = linha.querySelectorAll('input');
      campoTamanho.value = tamanho;
      campoEstoque.value = estoque;
      lista.appendChild(linha);
      renumerar();
      return linha;
    }

    // sem as linhas vazias extras que existem só para quem está sem JavaScript
    const vazias = linhas().filter((l) => Array.from(l.querySelectorAll('input')).every((c) => c.value === ''));
    vazias.slice(linhas().length === vazias.length ? 1 : 0).forEach((l) => l.remove());
    renumerar();

    lista.addEventListener('click', (ev) => {
      const botao = ev.target.closest('[data-remover-tamanho]');
      if (!botao) return;
      const linha = botao.closest('[data-linha-tamanho]');
      const anterior = linha.previousElementSibling?.querySelector('input') || lista.querySelector('[data-linha-tamanho] input');
      linha.remove();
      renumerar();
      if (anterior) anterior.focus();
    });

    const adicionar = document.querySelector('[data-adicionar-tamanho]');
    adicionar.hidden = false;
    adicionar.addEventListener('click', () => novaLinha().querySelector('input').focus());

    const atalhos = document.querySelector('[data-atalhos-tamanho]');
    atalhos.hidden = false;
    atalhos.addEventListener('click', (ev) => {
      const botao = ev.target.closest('[data-preencher-tamanhos]');
      if (!botao) return;
      const existentes = linhas().map((l) => l.querySelector('input').value.trim().toUpperCase());
      // linhas totalmente vazias dão lugar aos tamanhos do atalho
      linhas().filter((l) => Array.from(l.querySelectorAll('input')).every((c) => c.value === '')).forEach((l) => l.remove());
      botao.dataset.preencherTamanhos.split(',').forEach((t) => {
        if (!existentes.includes(t.toUpperCase())) novaLinha(t, '0');
      });
      if (!linhas().length) novaLinha();
      renumerar();
      Triade.aviso('Tamanhos adicionados. Agora digite o estoque de cada um.');
      const primeiroEstoque = linhas().find((l) => l.querySelectorAll('input')[1].value === '0');
      if (primeiroEstoque) primeiroEstoque.querySelectorAll('input')[1].select();
    });
  }

  // ---------- Prévia das fotos ----------
  const arquivo = document.querySelector('[data-previa-fotos]');
  if (arquivo) {
    const previa = arquivo.parentElement.querySelector('[data-previa]');
    let enderecos = [];
    arquivo.addEventListener('change', () => {
      enderecos.forEach((u) => URL.revokeObjectURL(u));
      enderecos = [];
      previa.innerHTML = '';
      Array.from(arquivo.files).forEach((f) => {
        const item = document.createElement('li');
        item.className = 'admin-foto admin-foto--nova';
        if (f.type.startsWith('image/')) {
          const img = document.createElement('img');
          const endereco = URL.createObjectURL(f);
          enderecos.push(endereco);
          img.src = endereco;
          img.alt = '';
          item.appendChild(img);
        }
        const nome = document.createElement('span');
        nome.className = 't-footnote texto-secundario admin-foto__nome';
        nome.textContent = f.size > 3 * 1024 * 1024 ? `${f.name} · maior que 3 MB` : f.name;
        if (f.size > 3 * 1024 * 1024) nome.classList.add('texto-erro');
        item.appendChild(nome);
        previa.appendChild(item);
      });
      previa.hidden = !arquivo.files.length;
    });
  }

  // foto marcada para remover fica apagada
  document.querySelectorAll('input[name="remover_foto[]"]').forEach((c) => {
    c.addEventListener('change', () => c.closest('.admin-foto').classList.toggle('admin-foto--remover', c.checked));
  });
})();
