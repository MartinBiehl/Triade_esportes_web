<?php
/**
 * Painel da loja · Pedidos
 * Filtros por situação e busca por número, nome ou e-mail.
 */
require __DIR__ . '/../includes/admin.php';

exigir_admin();

$filtro = isset(FILTROS_PEDIDO[$_GET['filtro'] ?? '']) ? $_GET['filtro'] : 'todos';
$busca = mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 60);
$pedidos = pedidos_admin($filtro, $busca);
$contagem = contagem_filtros_pedido();

admin_pagina_inicio(['titulo' => 'Pedidos', 'aba' => 'pedidos']);
?>
<div class="conteudo pilha">
  <?= nav_large('Pedidos') ?>

  <form method="get" role="search" class="pilha-sm">
    <input type="hidden" name="filtro" value="<?= e($filtro) ?>">
    <label class="campo-busca">
      <?= icone('buscar', 'icone--sm') ?>
      <span class="visualmente-oculto">Buscar pedidos</span>
      <input type="search" name="q" value="<?= e($busca) ?>" placeholder="Número, nome ou e-mail" autocomplete="off" enterkeyhint="search">
    </label>
  </form>

  <nav class="rolagem-horizontal chips-filtro" aria-label="Filtrar pedidos" tabindex="0">
    <?php foreach (FILTROS_PEDIDO as $chave => [$rotulo]): ?>
      <a class="chip" href="<?= e(url('admin/pedidos.php?filtro=' . $chave . ($busca !== '' ? '&q=' . urlencode($busca) : ''))) ?>"<?= $chave === $filtro ? ' aria-current="page"' : '' ?>>
        <?= e($rotulo) ?> <span class="chip__contagem"><?= (int) $contagem[$chave] ?></span>
      </a>
    <?php endforeach; ?>
  </nav>

  <?php if ($pedidos): ?>
    <h2 class="visualmente-oculto">Lista de pedidos</h2>
    <ul class="lista" role="list">
      <?php foreach ($pedidos as $p): ?>
        <li><?= linha_pedido_admin($p) ?></li>
      <?php endforeach; ?>
    </ul>
    <?php if (count($pedidos) === 100): ?>
      <p class="t-footnote texto-secundario">Mostrando os 100 mais recentes. Use a busca para achar um pedido antigo.</p>
    <?php endif; ?>
  <?php else: ?>
    <section class="estado-vazio">
      <div class="estado-vazio__icone"><?= icone('pacote') ?></div>
      <h2 class="t-title-3"><?= $busca !== '' ? 'Nada encontrado' : 'Nenhum pedido aqui' ?></h2>
      <p class="t-subheadline"><?= $busca !== '' ? 'Confira o número ou tente pelo nome do cliente.' : 'Quando um pedido chegar nesta situação, ele aparece aqui.' ?></p>
      <?php if ($busca !== '' || $filtro !== 'todos'): ?>
        <a class="botao botao--glass botao--md" href="<?= e(url('admin/pedidos.php')) ?>">Ver todos</a>
      <?php endif; ?>
    </section>
  <?php endif; ?>
</div>
<?php pagina_fim(); ?>
