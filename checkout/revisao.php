<?php
/**
 * Tela 16 · Revisão
 * Ao confirmar, o pedido é criado no banco e o estoque é baixado.
 */
require __DIR__ . '/../includes/bootstrap.php';
checkout_exigir('revisao');

$c = &checkout();
$erro = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $pag = $c['pagamento'];

    // SIMULAÇÃO: cartão terminado em 0002 é recusado e o pedido não é criado (tela 30)
    if ($pag['tipo'] === 'cartao' && $pag['final'] === '0002') {
        unset($c['pagamento']);
        redirecionar('checkout/recusado.php');
    }

    [$numero, $erro] = criar_pedido($_SESSION['cliente_id'] ?? null);
    if ($numero) {
        redirecionar($pag['tipo'] === 'pix' ? 'checkout/pix.php?pedido=' . urlencode($numero) : 'checkout/confirmado.php?pedido=' . urlencode($numero));
    }
}

$itens  = sacola_itens();
$pag    = $c['pagamento'];
$totais = checkout_totais($itens, $c['entrega'], $pag['tipo']);
$id     = $c['identificacao'];
$end    = $c['endereco'];
$entrega = opcoes_entrega($end['cep'], $totais['subtotal'])[$c['entrega']];

$texto_pagamento = match ($pag['tipo']) {
    'pix'    => 'Pix · ' . (int) (PIX_DESCONTO * 100) . '% de desconto',
    'cartao' => $pag['bandeira'] . ' final ' . $pag['final'] . ' · ' . $pag['parcelas'] . 'x de ' . dinheiro(round($totais['total'] / $pag['parcelas'], 2)),
    'boleto' => 'Boleto · aprovação em até ' . BOLETO_PRAZO_DIAS . ' dias úteis',
};

pagina_inicio([
    'titulo' => 'Revisão',
    'nav'    => ['titulo' => 'Revisão', 'voltar' => url('checkout/pagamento.php')],
]);
?>
<div class="conteudo conteudo--leitura pilha-xl" style="padding-top: var(--spacing-lg)">
  <?= checkout_etapas('revisao') ?>

  <h1 class="t-title-2">Confira antes de finalizar</h1>

  <?php if ($erro): ?>
    <p class="caixa texto-erro" role="alert"><?= icone('alerta', 'icone--sm') ?> <?= e($erro) ?> <a href="<?= e(url('sacola.php')) ?>">Ir para a sacola</a></p>
  <?php endif; ?>

  <!-- Itens -->
  <section class="pilha-sm">
    <div class="cabecalho-secao" style="margin: 0">
      <h2 class="t-headline"><?= count($itens) ?> <?= count($itens) === 1 ? 'produto' : 'produtos' ?></h2>
      <a class="cabecalho-secao__link" href="<?= e(url('sacola.php')) ?>">Alterar</a>
    </div>
    <ul class="lista revisao-itens" role="list">
      <?php foreach ($itens as $item): ?>
        <li class="linha-lista">
          <?= midia_produto($item['produto'], 'revisao-itens__foto') ?>
          <span class="linha-lista__rotulo">
            <span class="t-callout"><?= e($item['produto']['nome']) ?></span><br>
            <span class="t-footnote texto-secundario">Tamanho <?= e($item['tamanho']) ?> · <?= (int) $item['quantidade'] ?> un.</span>
          </span>
          <span class="linha-lista__valor"><?= dinheiro($item['subtotal']) ?></span>
        </li>
      <?php endforeach; ?>
    </ul>
  </section>

  <!-- Dados -->
  <section class="pilha-sm">
    <h2 class="t-headline">Entrega e pagamento</h2>
    <ul class="lista" role="list">
      <li>
        <a class="linha-lista" href="<?= e(url('checkout/identificacao.php')) ?>">
          <?= icone('conta', 'linha-lista__icone') ?>
          <span class="linha-lista__rotulo">
            <?= e($id['nome']) ?><br>
            <span class="t-footnote texto-secundario"><?= e($id['email']) ?> · CPF <?= e(formatar_cpf($id['cpf'])) ?></span>
          </span>
          <?= icone('seta-direita', 'icone--sm linha-lista__seta') ?>
        </a>
      </li>
      <li>
        <a class="linha-lista" href="<?= e(url('checkout/entrega.php')) ?>">
          <?= icone($c['entrega'] === 'retirada' ? 'loja' : 'caminhao', 'linha-lista__icone') ?>
          <span class="linha-lista__rotulo">
            <?= e($entrega['nome']) ?> · <?= e($entrega['prazo_texto']) ?><br>
            <span class="t-footnote texto-secundario">
              <?php if ($c['entrega'] === 'retirada'): ?>
                <?= e(DEPOSITO_ENDERECO) ?>
              <?php else: ?>
                <?= e($end['logradouro']) ?>, <?= e($end['numero']) ?><?= $end['complemento'] ? ' · ' . e($end['complemento']) : '' ?> · <?= e($end['cidade']) ?>/RS
              <?php endif; ?>
            </span>
          </span>
          <?= icone('seta-direita', 'icone--sm linha-lista__seta') ?>
        </a>
      </li>
      <li>
        <a class="linha-lista" href="<?= e(url('checkout/pagamento.php')) ?>">
          <?= icone($pag['tipo'] === 'pix' ? 'pix' : ($pag['tipo'] === 'cartao' ? 'cartao' : 'boleto'), 'linha-lista__icone') ?>
          <span class="linha-lista__rotulo"><?= e($texto_pagamento) ?></span>
          <?= icone('seta-direita', 'icone--sm linha-lista__seta') ?>
        </a>
      </li>
    </ul>
  </section>

  <!-- Totais -->
  <dl class="caixa resumo-valores" style="margin: 0">
    <div><dt>Produtos</dt><dd><?= dinheiro($totais['subtotal']) ?></dd></div>
    <div><dt>Frete</dt><dd class="<?= $totais['frete'] == 0 ? 'texto-sucesso' : '' ?>"><?= $totais['frete'] == 0 ? 'Grátis' : dinheiro($totais['frete']) ?></dd></div>
    <?php if ($totais['desconto'] > 0): ?>
      <div><dt>Desconto Pix</dt><dd class="texto-sucesso">− <?= dinheiro($totais['desconto']) ?></dd></div>
    <?php endif; ?>
    <div class="resumo-valores__total"><dt>Total</dt><dd><?= dinheiro($totais['total']) ?></dd></div>
  </dl>

  <form id="form-etapa" method="post">
    <?= csrf_campo() ?>
    <p class="t-footnote texto-terciario">
      Ao finalizar, você concorda com a <a href="<?= e(url('trocas.php')) ?>">política de trocas</a>.
      Esta loja é um trabalho acadêmico: nenhum pagamento é cobrado de verdade.
    </p>
  </form>
</div>
<?= checkout_barra('form-etapa', 'Finalizar pedido', $totais['total'], $pag['tipo'] === 'cartao' ? $pag['parcelas'] . 'x sem juros' : ($pag['tipo'] === 'pix' ? 'No Pix' : 'No boleto')) ?>
<?php pagina_fim(); ?>
