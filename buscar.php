<?php
/**
 * Tela 03 · Buscar
 * O formulário envia para resultados.php (tela 04).
 */
require __DIR__ . '/includes/bootstrap.php';

$esportes = esportes_com_categorias();
$populares = ['Grêmio', 'Internacional', 'Chuteira', 'Lakers', 'Bola', 'Seleção'];

pagina_inicio(['titulo' => 'Buscar', 'aba' => 'buscar']);
?>
<div class="conteudo pilha-xl">
  <?= nav_large('Buscar') ?>

  <form action="<?= e(url('resultados.php')) ?>" method="get" role="search" style="margin-top: calc(var(--spacing-2xl) * -1)">
    <label class="campo-busca">
      <?= icone('buscar', 'icone--sm') ?>
      <span class="visualmente-oculto">Buscar produtos</span>
      <input type="search" name="q" placeholder="Times, produtos, esportes" autocomplete="off" enterkeyhint="search" autofocus>
    </label>
  </form>

  <section class="pilha">
    <h2 class="t-label-caps texto-secundario">Buscas populares</h2>
    <div class="linha" style="flex-wrap: wrap">
      <?php foreach ($populares as $termo): ?>
        <a class="chip" href="<?= e(url_resultados([], ['q' => $termo])) ?>"><?= icone('buscar', 'icone--xs') ?> <?= e($termo) ?></a>
      <?php endforeach; ?>
    </div>
  </section>

  <?php foreach ($esportes as $esporte): ?>
    <section class="pilha">
      <h2 class="t-label-caps texto-secundario"><?= e($esporte['nome']) ?></h2>
      <ul class="lista" role="list">
        <?php foreach ($esporte['categorias'] as $categoria): ?>
          <li><?= linha_lista(
              $categoria['nome'],
              null,
              url_resultados([], ['esporte' => $esporte['slug'], 'categoria' => $categoria['slug']]),
              (string) $categoria['total_produtos']
          ) ?></li>
        <?php endforeach; ?>
      </ul>
    </section>
  <?php endforeach; ?>
</div>
<?php pagina_fim(); ?>
