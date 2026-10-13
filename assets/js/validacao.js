/*
 * validacao.js · formulários
 *  - máscaras enquanto digita (CPF, CEP, telefone, cartão, validade)
 *  - confere cada campo ao sair dele, e depois a cada tecla (o design pede validar enquanto digita)
 *  - o PHP confere tudo de novo no servidor: isto aqui é só para ajudar o cliente
 *
 * Uso no HTML: <form data-validar> com campos que tenham required e/ou data-validar="cpf"
 */
(function () {
  'use strict';

  const so = (v) => (v || '').replace(/\D+/g, '');

  // ---------- Máscaras ----------
  const MASCARAS = {
    cpf: (v) => so(v).slice(0, 11).replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d{1,2})$/, '$1-$2'),
    cep: (v) => so(v).slice(0, 8).replace(/(\d{5})(\d)/, '$1-$2'),
    telefone: (v) => {
      const n = so(v).slice(0, 11);
      if (n.length <= 2) return n.length ? '(' + n : '';
      if (n.length <= 6) return `(${n.slice(0, 2)}) ${n.slice(2)}`;
      if (n.length <= 10) return `(${n.slice(0, 2)}) ${n.slice(2, 6)}-${n.slice(6)}`;
      return `(${n.slice(0, 2)}) ${n.slice(2, 7)}-${n.slice(7)}`;
    },
    cartao: (v) => so(v).slice(0, 19).replace(/(\d{4})(?=\d)/g, '$1 '),
    validade: (v) => so(v).slice(0, 4).replace(/(\d{2})(\d)/, '$1/$2'),
    cvv: (v) => so(v).slice(0, 4),
  };

  // ---------- Regras ----------
  function cpfValido(v) {
    const c = so(v);
    if (c.length !== 11 || /^(\d)\1{10}$/.test(c)) return false;
    for (let t = 9; t < 11; t++) {
      let soma = 0;
      for (let i = 0; i < t; i++) soma += Number(c[i]) * (t + 1 - i);
      if (Number(c[t]) !== ((10 * soma) % 11) % 10) return false;
    }
    return true;
  }

  function luhn(v) {
    const n = so(v);
    if (n.length < 13 || n.length > 19) return false;
    let soma = 0, dobrar = false;
    for (let i = n.length - 1; i >= 0; i--) {
      let d = Number(n[i]);
      if (dobrar) { d *= 2; if (d > 9) d -= 9; }
      soma += d;
      dobrar = !dobrar;
    }
    return soma % 10 === 0;
  }

  function validadeOk(v) {
    const n = so(v);
    if (n.length !== 4) return false;
    const mes = Number(n.slice(0, 2));
    const ano = 2000 + Number(n.slice(2));
    if (mes < 1 || mes > 12) return false;
    const fim = new Date(ano, mes, 0, 23, 59, 59);
    return fim >= new Date();
  }

  const REGRAS = {
    cpf: [cpfValido, 'CPF inválido.'],
    email: [(v) => /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v.trim()), 'E-mail inválido.'],
    'cep-rs': [(v) => so(v).length === 8 && so(v)[0] === '9', 'CEP do RS com 8 números (começa com 9).'],
    telefone: [(v) => so(v).length >= 10 && so(v).length <= 11, 'Telefone com DDD.'],
    'nome-completo': [(v) => v.trim().length >= 5 && v.trim().includes(' '), 'Digite nome e sobrenome.'],
    cartao: [luhn, 'Número do cartão inválido.'],
    validade: [validadeOk, 'Data inválida ou cartão vencido.'],
    cvv: [(v) => so(v).length >= 3 && so(v).length <= 4, '3 ou 4 números no verso.'],
    senha: [(v) => v.length >= 8, 'Use pelo menos 8 caracteres.'],
  };

  function erroDoCampo(campo) {
    if (campo.disabled || campo.closest('[hidden]')) return null;
    const valor = campo.value || '';
    if (campo.required && valor.trim() === '') return campo.dataset.obrigatorio || 'Preencha este campo.';
    if (valor.trim() === '') return null;
    const regra = REGRAS[campo.dataset.validar];
    if (regra && !regra[0](valor)) return regra[1];
    if (campo.dataset.igualA) {
      const outro = campo.form.querySelector(`[name="${campo.dataset.igualA}"]`);
      if (outro && outro.value !== valor) return 'As senhas não são iguais.';
    }
    return null;
  }

  function mostrar(campo, erro) {
    const caixa = campo.closest('.campo');
    if (!caixa) return;
    const ajuda = caixa.querySelector('.campo__ajuda');
    caixa.classList.toggle('campo--erro', !!erro);
    if (erro) campo.setAttribute('aria-invalid', 'true');
    else campo.removeAttribute('aria-invalid');
    if (ajuda) ajuda.textContent = erro || ajuda.dataset.ajudaOriginal || '';
  }

  function validar(campo) {
    const erro = erroDoCampo(campo);
    mostrar(campo, erro);
    return !erro;
  }

  document.querySelectorAll('form[data-validar]').forEach((form) => {
    const campos = () => Array.from(form.querySelectorAll('input, select, textarea')).filter((c) => c.type !== 'hidden');

    form.addEventListener('input', (ev) => {
      const campo = ev.target;
      const mascara = MASCARAS[campo.dataset.mascara];
      if (mascara) {
        const fimAntes = campo.selectionEnd === campo.value.length;
        campo.value = mascara(campo.value);
        if (fimAntes) campo.setSelectionRange(campo.value.length, campo.value.length);
      }
      if (campo.dataset.tocado) validar(campo);
    });

    form.addEventListener('focusout', (ev) => {
      const campo = ev.target;
      if (!campo.matches('input, select, textarea') || campo.value === '') return;
      campo.dataset.tocado = '1';
      validar(campo);
    });

    form.addEventListener('submit', (ev) => {
      let primeiro = null;
      campos().forEach((c) => {
        c.dataset.tocado = '1';
        if (!validar(c) && !primeiro) primeiro = c;
      });
      if (primeiro) {
        ev.preventDefault();
        primeiro.focus();
        Triade.aviso('Confira os campos destacados', { tipo: 'erro' });
      }
    });

    // aplica as máscaras nos valores que já vieram preenchidos
    campos().forEach((c) => {
      const mascara = MASCARAS[c.dataset.mascara];
      if (mascara && c.value) c.value = mascara(c.value);
    });
  });
})();
