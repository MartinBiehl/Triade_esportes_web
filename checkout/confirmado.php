<?php
/**
 * Tela 18 · Pedido confirmado
 */
require __DIR__ . '/../includes/bootstrap.php';

$pedido = pedido_da_sessao((string) ($_GET['pedido'] ?? ''));
if (!$pedido) {
    redirecionar('index.php');
}

$aguardando = $pedido['status'] === 'aguardando_pagamento';
$logado = !empty($_SESSION['cliente_id']);
$retirada = $pedido['entrega_tipo'] === 'retirada';

pagina_inicio(['titulo' => 'Pedido confirmado', 'aba' => 'inicio']);
?>
<div class="conteudo conteudo--leitura pilha-xl" style="padding-top: var(--spacing-3xl)">
  <section class="confirmado">
    <div class="confirmado__selo <?= $aguardando ? 'confirmado__selo--aguardando' : '' ?>" aria-hidden="true">
      <?= icone($aguardando ? 'relogio' : 'check') ?>
    </div>
    <h1 class="t-title-1"><?= $aguardando ? 'Pedido recebido' : 'Pedido confirmado!' ?></h1>
    <p class="t-body texto-secundario">
      <?php if ($aguardando && $pedido['pagamento_tipo'] === 'boleto'): ?>
        Pague o boleto para liberar o envio. A aprovação leva até <?= BOLETO_PRAZO_DIAS ?> dias úteis.
      <?php elseif ($aguardando): ?>
        Assim que o pagamento cair, a gente separa o seu pedido.
      <?php else: ?>
        Obrigado, <?= e(explode(' ', $pedido['nome'])[0]) ?>! Enviamos os detalhes para <?= e($pedido['email']) ?>.
      <?php endif; ?>
    </p>
    <p class="confirmado__numero">Pedido <strong>#<?= e($pedido['numero']) ?></strong></p>
  </section>

  <ul class="lista" role="list">
    <li><?= linha_lista($retirada ? 'Retirada em São Leopoldo' : 'Previsão de entrega', $retirada ? 'loja' : 'caminhao', null, $pedido['entrega_prazo']) ?></li>
    <li><?= linha_lista('Total', $pedido['pagamento_tipo'] === 'pix' ? 'pix' : ($pedido['pagamento_tipo'] === 'cartao' ? 'cartao' : 'boleto'), null,
        dinheiro((float) $pedido['total']) . ($pedido['parcelas'] > 1 ? ' em ' . $pedido['parcelas'] . 'x' : '')) ?></li>
  </ul>

  <?php if ($pedido['pagamento_tipo'] === 'boleto' && $aguardando): ?>
    <section class="caixa pilha-sm">
      <h2 class="t-headline">Código do boleto (simulação)</h2>
      <p class="t-footnote pix-codigo" style="word-break: break-all">34191.79001 01043.510047 91020.150008 1 <?= e(so_numeros($pedido['numero'])) ?>0000<?= e(str_pad(so_numeros(number_format((float) $pedido['total'], 2, '', '')), 8, '0', STR_PAD_LEFT)) ?></p>
    </section>
  <?php endif; ?>

  <section class="pilha-sm">
    <h2 class="t-headline">Itens</h2>
    <ul class="lista" role="list">
      <?php foreach ($pedido['itens'] as $item): ?>
        <li><?= linha_lista($item['quantidade'] . '× ' . $item['nome_produto'] . ' (' . $item['tamanho'] . ')', null, null, dinheiro($item['quantidade'] * (float) $item['preco_unitario'])) ?></li>
      <?php endforeach; ?>
    </ul>
  </section>

  <?php if (!$logado): ?>
    <!-- Conta oferecida depois da compra, como no design -->
    <section class="caixa caixa--sutil pilha-sm">
      <h2 class="t-headline">Acompanhe pelo site</h2>
      <p class="t-subheadline texto-secundario">Crie uma senha e veja o rastreio deste e dos próximos pedidos. Seus dados já estão preenchidos.</p>
      <a class="botao botao--primary botao--md" href="<?= e(url('conta/criar.php?pedido=' . urlencode($pedido['numero']))) ?>">Criar conta</a>
    </section>
  <?php else: ?>
    <a class="botao botao--primary botao--bloco" href="<?= e(url('conta/pedido.php?numero=' . urlencode($pedido['numero']))) ?>">Acompanhar pedido</a>
  <?php endif; ?>

  <a class="botao botao--ghost botao--bloco" href="<?= e(url('index.php')) ?>">Continuar comprando</a>
</div>
<?php pagina_fim(); ?>
