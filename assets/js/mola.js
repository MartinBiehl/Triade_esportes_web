/*
 * mola.js · motor de animação com física de mola
 *
 * Por que não usar só transição de CSS? Uma transição tem duração fixa e recomeça do zero
 * se for interrompida. A mola continua do ponto e da velocidade em que estava, então um
 * painel arrastado com o dedo e solto "rápido" sai na mesma velocidade, como no iPhone.
 *
 * Parâmetros iguais aos do design:
 *   damping  1,0 = para sem quicar · abaixo de 1 = quica um pouco
 *   response tempo aproximado (em segundos) para chegar ao destino
 *
 * Uso:
 *   const mola = Triade.mola({ de: 0, para: 1, damping: 0.8, response: 0.3,
 *                              aoAtualizar: v => el.style.transform = `translateY(${v}px)` });
 *   mola.irPara(0);   // muda o destino no meio do caminho, sem tranco
 *   mola.parar();
 */
(function () {
  'use strict';

  window.Triade = window.Triade || {};

  const PASSO = 1 / 240; // a física é calculada em passos pequenos para ficar estável

  /** O sistema pede menos movimento? (Ajustes de acessibilidade do celular/computador) */
  Triade.reduzirMovimento = function () {
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  };

  Triade.mola = function (opcoes) {
    let valor = opcoes.de ?? 0;
    let destino = opcoes.para ?? valor;
    let velocidade = opcoes.velocidade ?? 0;
    let damping = opcoes.damping ?? 1;
    let response = opcoes.response ?? 0.35;
    const precisao = opcoes.precisao ?? 0.001;
    const aoAtualizar = opcoes.aoAtualizar || function () {};
    let aoTerminar = opcoes.aoTerminar || function () {};

    let quadro = null;
    let ultimoTempo = null;

    function constantes() {
      // Conversão do modelo "damping/response" (Apple, Figma) para física de mola com massa 1
      const rigidez = Math.pow((2 * Math.PI) / response, 2);
      const atrito = (4 * Math.PI * damping) / response;
      return { rigidez, atrito };
    }

    function passo(tempo) {
      if (ultimoTempo === null) ultimoTempo = tempo;
      // limita o intervalo para a aba não "pular" depois de ficar em segundo plano
      let dt = Math.min((tempo - ultimoTempo) / 1000, 1 / 20);
      ultimoTempo = tempo;

      const { rigidez, atrito } = constantes();
      while (dt > 0) {
        const h = Math.min(PASSO, dt);
        const forca = -rigidez * (valor - destino) - atrito * velocidade;
        velocidade += forca * h;
        valor += velocidade * h;
        dt -= h;
      }

      const escala = Math.max(1, Math.abs(destino));
      const parado = Math.abs(valor - destino) < precisao * escala &&
                     Math.abs(velocidade) < precisao * escala * 10;
      if (parado) {
        valor = destino;
        velocidade = 0;
        quadro = null;
        aoAtualizar(valor);
        aoTerminar(valor);
        return;
      }

      aoAtualizar(valor);
      quadro = requestAnimationFrame(passo);
    }

    function iniciar() {
      if (Triade.reduzirMovimento()) {
        // sem física: vai direto ao destino (quem usa a mola troca deslizamento por esmaecer)
        valor = destino;
        velocidade = 0;
        aoAtualizar(valor);
        aoTerminar(valor);
        return;
      }
      if (quadro === null) {
        ultimoTempo = null;
        quadro = requestAnimationFrame(passo);
      }
    }

    const controle = {
      /** Muda o destino. Mantém a posição e a velocidade atuais. */
      irPara(novoDestino, extras) {
        destino = novoDestino;
        if (extras) {
          if (extras.velocidade !== undefined) velocidade = extras.velocidade;
          if (extras.damping !== undefined) damping = extras.damping;
          if (extras.response !== undefined) response = extras.response;
          if ('aoTerminar' in extras) aoTerminar = extras.aoTerminar || function () {};
        }
        iniciar();
        return controle;
      },
      /** Coloca num valor na hora, sem animar (usado enquanto o dedo arrasta). */
      definir(novoValor) {
        controle.parar();
        valor = novoValor;
        destino = novoValor;
        velocidade = 0;
        aoAtualizar(valor);
        return controle;
      },
      parar() {
        if (quadro !== null) cancelAnimationFrame(quadro);
        quadro = null;
        return controle;
      },
      get valor() { return valor; },
      get velocidade() { return velocidade; },
      get animando() { return quadro !== null; },
    };

    if (opcoes.para !== undefined && opcoes.para !== opcoes.de) iniciar();
    else aoAtualizar(valor);

    return controle;
  };
})();
