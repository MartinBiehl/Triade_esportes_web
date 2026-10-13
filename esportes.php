<?php
/**
 * Tela 02 · Esportes
 * Lista cada esporte com as suas categorias. Esporte novo no banco aparece aqui sozinho.
 */
require __DIR__ . '/includes/bootstrap.php';

$esportes = esportes_com_categorias();

pagina_inicio(['titulo' => 'Esportes', 'aba' => 'esportes']);
?>
<div class="conteudo pilha-xl">
  <?= nav_large('Esportes') ?>

  <?php foreach ($esportes as $esporte): ?>
    <section class="pilha" aria-labelledby="esporte-<?= e($esporte['slug']) ?>">
      <div class="cabecalho-secao" style="margin-bottom: 0">
        <h2 class="cabecalho-secao__titulo" id="esporte-<?= e($esporte['slug']) ?>"><?= e($esporte['nome']) ?></h2>
        <a class="cabecalho-secao__link" href="<?= e(url_resultados([], ['esporte' => $esporte['slug']])) ?>">
          Ver tudo (<?= (int) $esporte['total_produtos'] ?>)
        </a>
      </div>
      <?php if ($esporte['descricao']): ?>
        <p class="t-subheadline texto-secundario" style="margin-top: calc(var(--spacing-sm) * -1)"><?= e($esporte['descricao']) ?></p>
      <?php endif; ?>

      <div class="grade-tiles">
        <?php foreach ($esporte['categorias'] as $i => $categoria): ?>
          <?php $cor = $i === 0 ? $esporte['cor'] : 'light'; ?>
          <a class="tile-categoria tile-categoria--<?= e($cor) ?>"
             href="<?= e(url_resultados([], ['esporte' => $esporte['slug'], 'categoria' => $categoria['slug']])) ?>">
            <span class="tile-categoria__titulo"><?= e($categoria['nome']) ?></span>
            <span class="tile-categoria__detalhe">
              <?= (int) $categoria['total_produtos'] ?> <?= $categoria['total_produtos'] == 1 ? 'produto' : 'produtos' ?>
            </span>
            <?= silhueta($categoria['midia'] ?: 'camisa', 'tile-categoria__arte') ?>
          </a>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endforeach; ?>

  <section class="caixa caixa--sutil pilha-sm">
    <h2 class="t-headline">Novos esportes em breve</h2>
    <p class="t-subheadline texto-secundario">Estamos ampliando o catálogo. Quer algo que não encontrou? Fale com a gente no WhatsApp.</p>
  </section>
</div>
<?php pagina_fim(); ?>
