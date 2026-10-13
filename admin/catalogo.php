<?php
/**
 * Painel da loja · Catálogo: esportes e as categorias de cada um
 */
require __DIR__ . '/../includes/admin.php';

exigir_admin();
$esportes = catalogo_admin();

admin_pagina_inicio(['titulo' => 'Catálogo', 'aba' => 'catalogo']);
?>
<div class="conteudo pilha-xl">
  <?= nav_large('Catálogo', '<a class="botao botao--primary botao--md" href="' . e(url('admin/esporte.php')) . '">' . icone('mais', 'icone--sm') . ' Esporte</a>') ?>
  <p class="t-subheadline texto-secundario" style="margin-top: calc(var(--spacing-2xl) * -1)">A loja organiza os produtos em Esporte, depois Categoria. A ordem aqui é a mesma da aba Esportes do site.</p>

  <?php foreach ($esportes as $esporte): ?>
    <section class="pilha-sm">
      <div class="linha linha--entre">
        <h2 class="t-title-3 linha">
          <span class="admin-cor admin-cor--<?= e($esporte['cor']) ?>" aria-hidden="true"></span>
          <?= e($esporte['nome']) ?>
          <?php if (!$esporte['ativo']): ?><span class="status-pedido status-pedido--erro">Oculto</span><?php endif; ?>
        </h2>
        <a class="botao botao--ghost botao--md" href="<?= e(url('admin/esporte.php?id=' . (int) $esporte['id'])) ?>"><?= icone('editar', 'icone--sm') ?> Editar</a>
      </div>
      <ul class="lista" role="list">
        <?php foreach ($esporte['categorias'] as $c): ?>
          <li>
            <a class="linha-lista" href="<?= e(url('admin/categoria.php?id=' . (int) $c['id'])) ?>">
              <span class="linha-lista__rotulo">
                <?= e($c['nome']) ?>
                <?php if (!$c['ativo']): ?> <span class="status-pedido status-pedido--erro">Oculta</span><?php endif; ?>
              </span>
              <span class="linha-lista__valor"><?= (int) $c['total_produtos'] ?> <?= (int) $c['total_produtos'] === 1 ? 'produto' : 'produtos' ?></span>
              <?= icone('seta-direita', 'icone--sm linha-lista__seta') ?>
            </a>
          </li>
        <?php endforeach; ?>
        <li><?= linha_lista('Nova categoria em ' . $esporte['nome'], 'mais', url('admin/categoria.php?esporte=' . (int) $esporte['id'])) ?></li>
      </ul>
    </section>
  <?php endforeach; ?>

  <?php if (!$esportes): ?>
    <section class="estado-vazio">
      <div class="estado-vazio__icone"><?= icone('esportes') ?></div>
      <h2 class="t-title-3">Nenhum esporte ainda</h2>
      <p class="t-subheadline">Cadastre o primeiro esporte para começar.</p>
      <a class="botao botao--primary botao--md" href="<?= e(url('admin/esporte.php')) ?>">Cadastrar esporte</a>
    </section>
  <?php endif; ?>
</div>
<?php pagina_fim(); ?>
