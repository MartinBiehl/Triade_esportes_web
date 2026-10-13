<?php
/**
 * Tela 26 · Favoritos
 */
require __DIR__ . '/../includes/bootstrap.php';

$cliente = exigir_login();
$produtos = produtos_favoritos((int) $cliente['id']);

pagina_inicio([
    'titulo' => 'Favoritos',
    'aba'    => 'conta',
    'nav'    => ['titulo' => 'Favoritos', 'voltar' => url('conta/')],
]);
?>
<div class="conteudo pilha" style="padding-top: var(--spacing-lg)">
  <section class="estado-vazio" id="favoritos-vazio"<?= $produtos ? ' hidden' : '' ?>>
    <div class="estado-vazio__icone"><?= icone('coracao') ?></div>
    <h1 class="t-title-3">Nenhum favorito ainda</h1>
    <p class="t-subheadline">Toque no coração de um produto para guardar aqui e achar depois.</p>
    <a class="botao botao--primary botao--md" href="<?= e(url('index.php')) ?>">Ver produtos</a>
  </section>

  <?php if ($produtos): ?>
    <p class="t-footnote texto-secundario" id="favoritos-contagem"><?= count($produtos) ?> <?= count($produtos) === 1 ? 'produto salvo' : 'produtos salvos' ?></p>
    <div class="grade-produtos">
      <?php foreach ($produtos as $p): ?>
        <div class="card-favorito" data-card-favorito>
          <?= card_produto($p) ?>
          <div class="card-produto__favorito"><?= botao_favorito((int) $p['id'], 'botao-icone--glass botao-icone--sm', 'conta/favoritos.php') ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
<?php pagina_fim(['scripts' => ['conta.js']]); ?>
