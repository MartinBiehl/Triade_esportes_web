<?php
/**
 * Painel da loja · Início
 * O que precisa de atenção agora: pedidos para separar, pagamentos pendentes e estoque acabando.
 */
require __DIR__ . '/../includes/admin.php';

$admin = exigir_admin();
$resumo = resumo_painel();
$recentes = pedidos_admin('todos', '', 5);
$acabando = estoque_baixo();
$meses = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];

admin_pagina_inicio(['titulo' => 'Início', 'aba' => 'painel']);
?>
<div class="conteudo pilha-xl">
  <?= nav_large('Painel da loja') ?>

  <p class="t-subheadline texto-secundario" style="margin-top: calc(var(--spacing-2xl) * -1)">Olá, <?= e(explode(' ', $admin['nome'])[0]) ?>. Estes são os números da loja agora.</p>

  <section class="admin-numeros" aria-label="Resumo">
    <a class="admin-numero<?= $resumo['separar'] ? ' admin-numero--destaque' : '' ?>" href="<?= e(url('admin/pedidos.php?filtro=separar')) ?>">
      <span class="admin-numero__valor"><?= $resumo['separar'] ?></span>
      <span class="admin-numero__rotulo">Para separar</span>
    </a>
    <a class="admin-numero" href="<?= e(url('admin/pedidos.php?filtro=pagamento')) ?>">
      <span class="admin-numero__valor"><?= $resumo['pagamento'] ?></span>
      <span class="admin-numero__rotulo">Aguardando pagamento</span>
    </a>
    <div class="admin-numero">
      <span class="admin-numero__valor"><?= $resumo['pedidos_hoje'] ?></span>
      <span class="admin-numero__rotulo">Pedidos hoje</span>
    </div>
    <div class="admin-numero">
      <span class="admin-numero__valor admin-numero__valor--dinheiro"><?= dinheiro($resumo['vendas_mes']) ?></span>
      <span class="admin-numero__rotulo">Vendas pagas em <?= e($meses[(int) date('n') - 1]) ?> · <?= $resumo['pedidos_mes'] ?> <?= $resumo['pedidos_mes'] === 1 ? 'pedido' : 'pedidos' ?></span>
    </div>
  </section>

  <?php if ($resumo['retirar']): ?>
    <a class="caixa caixa--sutil linha linha--entre perfil__andamento" href="<?= e(url('admin/pedidos.php?filtro=enviados')) ?>">
      <span class="linha"><?= icone('loja') ?>
        <span class="t-callout"><?= $resumo['retirar'] === 1 ? '1 pedido esperando retirada' : $resumo['retirar'] . ' pedidos esperando retirada' ?></span>
      </span>
      <?= icone('seta-direita', 'icone--sm') ?>
    </a>
  <?php endif; ?>

  <section class="pilha-sm">
    <?= cabecalho_secao('Últimos pedidos', url('admin/pedidos.php')) ?>
    <?php if ($recentes): ?>
      <ul class="lista" role="list">
        <?php foreach ($recentes as $p): ?>
          <li><?= linha_pedido_admin($p) ?></li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <p class="t-subheadline texto-secundario">Nenhum pedido ainda.</p>
    <?php endif; ?>
  </section>

  <section class="pilha-sm">
    <?= cabecalho_secao('Estoque acabando', url('admin/produtos.php')) ?>
    <?php if ($acabando): ?>
      <ul class="lista" role="list">
        <?php foreach ($acabando as $t): ?>
          <li>
            <a class="linha-lista" href="<?= e(url('admin/produto.php?id=' . (int) $t['id'] . '#tamanhos')) ?>">
              <?= midia_produto($t, 'midia-produto--mini') ?>
              <span class="linha-lista__rotulo">
                <span class="t-callout"><?= e($t['nome']) ?></span><br>
                <span class="t-footnote texto-secundario">Tamanho <?= e($t['tamanho']) ?></span>
              </span>
              <span class="linha-lista__valor <?= (int) $t['estoque'] === 0 ? 'texto-erro' : '' ?>"><?= (int) $t['estoque'] === 0 ? 'Esgotado' : (int) $t['estoque'] . ' un.' ?></span>
              <?= icone('seta-direita', 'icone--sm linha-lista__seta') ?>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <p class="t-subheadline texto-secundario">Nenhum tamanho com <?= ESTOQUE_BAIXO ?> peças ou menos.</p>
    <?php endif; ?>
  </section>

  <section class="pilha-sm">
    <h2 class="t-label-caps texto-secundario">Atalhos</h2>
    <ul class="lista" role="list">
      <li><?= linha_lista('Cadastrar produto', 'mais', url('admin/produto.php')) ?></li>
      <li><?= linha_lista('Esportes e categorias', 'esportes', url('admin/catalogo.php')) ?></li>
      <li><?= linha_lista('Ver a loja', 'loja', url('index.php')) ?></li>
      <li><?= linha_lista('Trocar senha do painel', 'cadeado', url('admin/senha.php')) ?></li>
    </ul>
  </section>

  <form method="post" action="<?= e(url('admin/sair.php')) ?>">
    <?= csrf_campo() ?>
    <button type="submit" class="botao botao--ghost botao--bloco texto-erro"><?= icone('sair', 'icone--sm') ?> Sair do painel</button>
  </form>
</div>
<?php pagina_fim(); ?>
