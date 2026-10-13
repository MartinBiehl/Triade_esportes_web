<?php
/**
 * Painel da loja · Produtos
 * Todos os produtos, inclusive os ocultos, com estoque total e aviso de tamanho acabando.
 */
require __DIR__ . '/../includes/admin.php';

exigir_admin();

$busca = mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 60);
$categoria_id = (int) ($_GET['categoria'] ?? 0);
$produtos = produtos_admin($busca, $categoria_id);
$categorias = ['0' => 'Todas as categorias'] + opcoes_categoria();

admin_pagina_inicio(['titulo' => 'Produtos', 'aba' => 'produtos']);
?>
<div class="conteudo pilha">
  <?= nav_large('Produtos', '<a class="botao botao--primary botao--md" href="' . e(url('admin/produto.php')) . '">' . icone('mais', 'icone--sm') . ' Novo</a>') ?>

  <form method="get" role="search" class="pilha-sm admin-filtros">
    <label class="campo-busca">
      <?= icone('buscar', 'icone--sm') ?>
      <span class="visualmente-oculto">Buscar produtos</span>
      <input type="search" name="q" value="<?= e($busca) ?>" placeholder="Nome, marca ou time" autocomplete="off" enterkeyhint="search">
    </label>
    <?= campo_selecao('categoria', 'Categoria', $categorias, $categoria_id, null, ['data-enviar-ao-mudar' => true]) ?>
    <noscript><button type="submit" class="botao botao--glass botao--bloco">Filtrar</button></noscript>
  </form>

  <p class="t-footnote texto-secundario" aria-live="polite"><?= count($produtos) ?> <?= count($produtos) === 1 ? 'produto' : 'produtos' ?></p>

  <?php if ($produtos): ?>
    <h2 class="visualmente-oculto">Lista de produtos</h2>
    <ul class="lista" role="list">
      <?php foreach ($produtos as $p): ?>
        <li>
          <a class="linha-lista admin-linha-produto<?= $p['ativo'] ? '' : ' admin-linha-produto--oculto' ?>" href="<?= e(url('admin/produto.php?id=' . (int) $p['id'])) ?>">
            <?= midia_produto($p, 'midia-produto--mini') ?>
            <span class="linha-lista__rotulo pilha-xs">
              <span class="t-callout"><?= e($p['nome']) ?></span>
              <span class="t-footnote texto-secundario"><?= e($p['esporte_nome']) ?> · <?= e($p['categoria_nome']) ?></span>
              <span class="linha" style="flex-wrap: wrap; gap: var(--spacing-xs)">
                <?php if (!$p['ativo']): ?><span class="status-pedido status-pedido--erro">Oculto</span><?php endif; ?>
                <?php if ((int) $p['estoque_total'] === 0): ?>
                  <span class="status-pedido status-pedido--erro">Esgotado</span>
                <?php elseif ($p['tamanhos_baixos']): ?>
                  <span class="status-pedido status-pedido--alerta"><?= (int) $p['tamanhos_baixos'] ?> <?= (int) $p['tamanhos_baixos'] === 1 ? 'tamanho acabando' : 'tamanhos acabando' ?></span>
                <?php endif; ?>
              </span>
            </span>
            <span class="linha-lista__valor admin-linha-produto__valores">
              <span class="t-callout"><?= dinheiro(preco_atual($p)) ?></span>
              <span class="t-footnote texto-secundario"><?= (int) $p['estoque_total'] ?> un.</span>
            </span>
            <?= icone('seta-direita', 'icone--sm linha-lista__seta') ?>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php else: ?>
    <section class="estado-vazio">
      <div class="estado-vazio__icone"><?= icone('grade') ?></div>
      <h2 class="t-title-3">Nenhum produto encontrado</h2>
      <p class="t-subheadline">Tente outra busca ou cadastre um produto novo.</p>
      <a class="botao botao--primary botao--md" href="<?= e(url('admin/produto.php')) ?>">Cadastrar produto</a>
    </section>
  <?php endif; ?>
</div>
<?php pagina_fim(['scripts' => ['admin.js']]); ?>
