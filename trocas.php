<?php
/**
 * Tela 27 · Trocas e devoluções
 * Regras definidas no design (ainda não confirmadas pela empresa): ficam em includes/config.php.
 */
require __DIR__ . '/includes/bootstrap.php';

$whatsapp = 'https://wa.me/' . CONTATO_WHATSAPP . '?text=' . rawurlencode('Olá! Quero trocar um produto do pedido #');

pagina_inicio([
    'titulo'    => 'Trocas e devoluções',
    'descricao' => 'Troca grátis em até ' . TROCA_PRAZO_DIAS . ' dias para todo o RS. Veja como trocar ou devolver um produto da Triade Esporte.',
    'aba'       => 'conta',
    'nav'       => ['titulo' => 'Trocas e devoluções', 'voltar' => url('conta/')],
]);
?>
<div class="conteudo conteudo--leitura pilha-xl" style="padding-top: var(--spacing-lg)">

  <section class="hero hero--lime" style="min-height: 0">
    <span class="hero__sobretitulo">Para todo o RS</span>
    <h1 class="hero__titulo">Primeira troca grátis</h1>
    <p class="hero__subtitulo">Você tem <?= TROCA_PRAZO_DIAS ?> dias, a partir do recebimento, para trocar ou devolver.</p>
  </section>

  <section class="pilha-sm">
    <h2 class="t-headline">Como trocar</h2>
    <ol class="passos-numerados" role="list">
      <li>
        <span class="t-callout">Chame a gente no WhatsApp</span>
        <span class="t-footnote texto-secundario">Mande o número do pedido (#TR-...) e diga o que quer trocar. Ele está em Conta, Meus pedidos.</span>
      </li>
      <li>
        <span class="t-callout">Envie ou traga o produto</span>
        <span class="t-footnote texto-secundario">Mandamos um código de postagem grátis dos Correios. Se preferir, entregue no depósito em São Leopoldo.</span>
      </li>
      <li>
        <span class="t-callout">Receba o novo ou o reembolso</span>
        <span class="t-footnote texto-secundario">Assim que o produto chegar e for conferido, enviamos a troca ou devolvemos o dinheiro.</span>
      </li>
    </ol>
    <a class="botao botao--primary botao--bloco" href="<?= e($whatsapp) ?>"><?= icone('whatsapp', 'icone--sm') ?> Pedir troca no WhatsApp</a>
  </section>

  <section class="pilha-sm">
    <h2 class="t-headline">Perguntas frequentes</h2>
    <div class="sanfona">
      <details>
        <summary>Em que condição o produto precisa estar?</summary>
        <p>Sem uso, sem lavar, com a etiqueta presa e na embalagem original. Camisas com nome ou número aplicados depois da compra não podem ser trocadas.</p>
      </details>
      <details>
        <summary>A troca é grátis mesmo?</summary>
        <p>A primeira troca de cada pedido é grátis para todo o Rio Grande do Sul. A partir da segunda, o frete de volta é por conta do cliente.</p>
      </details>
      <details>
        <summary>Posso trocar por outro produto?</summary>
        <p>Sim. Se o novo for mais caro, você paga a diferença. Se for mais barato, devolvemos a diferença do mesmo jeito que você pagou.</p>
      </details>
      <details>
        <summary>Como recebo o dinheiro de volta?</summary>
        <p>Pix e boleto: devolvemos por Pix em até 5 dias úteis depois de receber o produto. Cartão: o estorno aparece em até 2 faturas, conforme o banco.</p>
      </details>
      <details>
        <summary>E se o produto veio com defeito?</summary>
        <p>Fale com a gente em até 90 dias. Pagamos o frete de ida e volta e trocamos por um igual ou devolvemos o valor.</p>
      </details>
      <details>
        <summary>O tamanho não serviu. E agora?</summary>
        <p>É o caso mais comum, e é grátis na primeira troca. Antes de pedir, vale olhar o guia de tamanhos na página do produto para acertar o novo.</p>
      </details>
    </div>
  </section>

  <p class="t-footnote texto-terciario">
    Esta política segue o Código de Defesa do Consumidor: em compras pela internet, você pode desistir em até 7 dias do recebimento, com devolução integral.
  </p>
</div>
<?php pagina_fim(); ?>
