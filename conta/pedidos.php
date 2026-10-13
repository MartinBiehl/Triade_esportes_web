<?php
/**
 * Tela 23 · Meus pedidos
 */
require __DIR__ . '/../includes/bootstrap.php';

$cliente = exigir_login();
$pedidos = pedidos_do_cliente((int) $cliente['id']);

pagina_inicio([
    'titulo' => 'Meus pedidos',
    'aba'    => 'conta',
    'nav'    => ['titulo' => 'Meus pedidos', 'voltar' => url('conta/')],
]);
?>
<div class="conteudo pilha" style="padding-top: var(--spacing-lg)">
  <?php if (!$pedidos): ?>
    <section class="estado-vazio">
      <div class="estado-vazio__icone"><?= icone('pacote') ?></div>
      <h1 class="t-title-3">Nenhum pedido ainda</h1>
      <p class="t-subheadline">Quando você comprar, o pedido aparece aqui com o rastreio.</p>
      <a class="botao botao--primary botao--md" href="<?= e(url('index.php')) ?>">Ver produtos</a>
    </section>
  <?php else: ?>
    <ul class="pilha" role="list" style="padding: 0; margin: 0; list-style: none">
      <?php foreach ($pedidos as $p): ?>
        <li>
          <a class="cartao-pedido" href="<?= e(url('conta/pedido.php?numero=' . urlencode($p['numero']))) ?>">
            <div class="linha linha--entre">
              <span class="t-headline">#<?= e($p['numero']) ?></span>
              <?= selo_status($p['status']) ?>
            </div>
            <div class="cartao-pedido__miniaturas" aria-hidden="true">
              <?php foreach (array_slice($p['miniaturas'], 0, 3) as $m): ?>
                <?= midia_produto(['midia' => $m['midia'] ?? 'camisa', 'imagem' => $m['imagem']], 'midia-produto--mini') ?>
              <?php endforeach; ?>
              <?php if (count($p['miniaturas']) > 3): ?>
                <span class="cartao-pedido__mais">+<?= count($p['miniaturas']) - 3 ?></span>
              <?php endif; ?>
            </div>
            <div class="linha linha--entre">
              <span class="t-footnote texto-secundario">
                <?= e(data_longa($p['criado_em'])) ?> · <?= (int) $p['quantidade_itens'] ?> <?= (int) $p['quantidade_itens'] === 1 ? 'item' : 'itens' ?>
              </span>
              <span class="t-callout"><?= dinheiro((float) $p['total']) ?></span>
            </div>
            <?php if (in_array($p['status'], ['pago', 'em_separacao', 'enviado', 'pronto_retirada'], true)): ?>
              <span class="t-footnote cartao-pedido__prazo"><?= icone($p['entrega_tipo'] === 'retirada' ? 'loja' : 'caminhao', 'icone--xs') ?> <?= e($p['entrega_prazo']) ?></span>
            <?php endif; ?>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</div>
<?php pagina_fim(); ?>
