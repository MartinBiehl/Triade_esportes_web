<?php
/**
 * Telas 04 · Resultados, 05 · Filtros (painel) e 06 · Busca sem resultado
 * Tudo vem pela URL (?q=...&esporte=...&tamanho[]=M), então dá para voltar e compartilhar o link.
 */
require __DIR__ . '/includes/bootstrap.php';

$f = filtros_da_url($_GET);

$esporte   = $f['esporte'] !== '' ? esporte_por_slug($f['esporte']) : null;
$categoria = $esporte && $f['categoria'] !== '' ? categoria_por_slug((int) $esporte['id'], $f['categoria']) : null;

// Filtro de esporte/categoria que não existe: ignora em vez de mostrar lista vazia
if (!$esporte)   { $f['esporte'] = ''; $f['categoria'] = ''; }
if (!$categoria) { $f['categoria'] = ''; }

$produtos = buscar_produtos($f);
$opcoes   = opcoes_de_filtro($f);
$ativos   = total_filtros_ativos($f);

// Título da tela
if ($f['q'] !== '') {
    $titulo = '“' . $f['q'] . '”';
} elseif ($categoria) {
    $titulo = $categoria['nome'] . ' de ' . mb_strtolower($esporte['nome']);
} elseif ($esporte) {
    $titulo = $esporte['nome'];
} elseif ($f['promo']) {
    $titulo = 'Promoções';
} elseif ($f['ordem'] === 'novidades') {
    $titulo = 'Lançamentos';
} else {
    $titulo = 'Todos os produtos';
}

$voltar = $f['q'] !== '' ? url('buscar.php') : ($esporte ? url('esportes.php') : url('index.php'));

// Chips rápidos: categorias do esporte, ou os esportes
$chips = [];
if ($esporte) {
    $chips[] = ['Todos', url_resultados($f, ['categoria' => '']), !$categoria];
    foreach (categorias_do_esporte((int) $esporte['id']) as $c) {
        $chips[] = [$c['nome'], url_resultados($f, ['categoria' => $c['slug']]), $categoria && $categoria['id'] == $c['id']];
    }
} else {
    $chips[] = ['Todos', url_resultados($f, ['esporte' => '', 'categoria' => '']), true];
    foreach (esportes_com_categorias() as $e) {
        $chips[] = [$e['nome'], url_resultados($f, ['esporte' => $e['slug'], 'categoria' => '']), false];
    }
}

$botao_filtros = '<button type="button" class="botao-icone botao-icone--plain" data-abrir-painel="filtros" aria-label="Filtros'
    . ($ativos ? ', ' . $ativos . ' ativos' : '') . '">' . icone('filtros')
    . ($ativos ? '<span class="contador-filtros">' . $ativos . '</span>' : '') . '</button>';

pagina_inicio([
    'titulo' => strip_tags($titulo),
    'aba'    => $f['q'] !== '' ? 'buscar' : 'esportes',
    'nav'    => ['titulo' => $titulo, 'voltar' => $voltar, 'acoes' => $botao_filtros],
]);
?>
<div class="conteudo pilha">

  <?php if ($f['q'] !== ''): ?>
    <form action="<?= e(url('resultados.php')) ?>" method="get" role="search" style="padding-top: var(--spacing-md)">
      <label class="campo-busca">
        <?= icone('buscar', 'icone--sm') ?>
        <span class="visualmente-oculto">Buscar produtos</span>
        <input type="search" name="q" value="<?= e($f['q']) ?>" autocomplete="off" enterkeyhint="search">
      </label>
    </form>
  <?php endif; ?>

  <nav class="rolagem-horizontal" aria-label="Categorias" style="padding-top: var(--spacing-md)">
    <?php foreach ($chips as [$rotulo, $link, $atual]): ?>
      <a class="chip" href="<?= e($link) ?>"<?= $atual ? ' aria-current="page"' : '' ?>><?= e($rotulo) ?></a>
    <?php endforeach; ?>
  </nav>

  <?php if ($produtos): ?>
    <div class="linha linha--entre">
      <p class="t-footnote texto-secundario" role="status">
        <?= count($produtos) ?> <?= count($produtos) === 1 ? 'produto' : 'produtos' ?>
        <?php if ($f['ordem'] !== 'relevancia'): ?> · <?= e(ORDENACOES[$f['ordem']]) ?><?php endif; ?>
      </p>
      <button type="button" class="botao botao--ghost botao--md" data-abrir-painel="filtros" style="padding-inline: var(--spacing-sm)">
        <?= icone('filtros', 'icone--sm') ?> Filtrar<?= $ativos ? ' (' . $ativos . ')' : '' ?>
      </button>
    </div>

    <h2 class="visualmente-oculto">Produtos</h2>
    <div class="grade-produtos">
      <?php foreach ($produtos as $produto): ?>
        <?= card_produto($produto) ?>
      <?php endforeach; ?>
    </div>

  <?php else: ?>
    <!-- Tela 06 · Busca sem resultado -->
    <div class="estado-vazio">
      <div class="estado-vazio__icone"><?= icone('buscar') ?></div>
      <h2 class="t-title-3">
        <?= $f['q'] !== '' ? 'Nada encontrado para “' . e($f['q']) . '”' : 'Nenhum produto com esses filtros' ?>
      </h2>
      <p class="t-subheadline">
        <?= $ativos ? 'Tente tirar alguns filtros.' : 'Confira se a palavra está certa ou tente um termo mais simples, como o nome do time.' ?>
      </p>
      <?php if ($ativos): ?>
        <a class="botao botao--primary botao--md" href="<?= e(url_resultados(['q' => $f['q'], 'esporte' => $f['esporte'], 'categoria' => $f['categoria']])) ?>">Limpar filtros</a>
      <?php else: ?>
        <div class="linha" style="flex-wrap: wrap; justify-content: center">
          <?php foreach (['Grêmio', 'Internacional', 'Chuteira', 'Lakers'] as $termo): ?>
            <a class="chip" href="<?= e(url_resultados([], ['q' => $termo])) ?>"><?= e($termo) ?></a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <?php $sugestoes = produtos_destaque(4); ?>
    <?php if ($sugestoes): ?>
      <section>
        <?= cabecalho_secao('Talvez você goste') ?>
        <div class="grade-produtos">
          <?php foreach ($sugestoes as $produto): ?>
            <?= card_produto($produto) ?>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endif; ?>
  <?php endif; ?>
</div>

<!-- Tela 05 · Filtros -->
<?= painel_inicio('filtros', 'Filtros') ?>
  <form id="form-filtros" action="<?= e(url('resultados.php')) ?>" method="get" class="pilha-xl">
    <?php foreach (['q', 'esporte', 'categoria'] as $campo): ?>
      <?php if ($f[$campo] !== ''): ?>
        <input type="hidden" name="<?= $campo ?>" value="<?= e($f[$campo]) ?>">
      <?php endif; ?>
    <?php endforeach; ?>

    <fieldset class="filtro-grupo">
      <legend class="t-headline">Ordenar por</legend>
      <div class="filtro-opcoes">
        <?php foreach (ORDENACOES as $valor => $rotulo): ?>
          <input type="radio" class="visualmente-oculto" name="ordem" id="ordem-<?= e($valor) ?>" value="<?= e($valor) ?>" <?= $f['ordem'] === $valor ? 'checked' : '' ?>>
          <label class="chip" for="ordem-<?= e($valor) ?>"><?= e($rotulo) ?></label>
        <?php endforeach; ?>
      </div>
    </fieldset>

    <fieldset class="filtro-grupo">
      <legend class="t-headline">Preço</legend>
      <div class="filtro-opcoes">
        <input type="radio" class="visualmente-oculto" name="preco" id="preco-todos" value="" <?= $f['preco'] === '' ? 'checked' : '' ?>>
        <label class="chip" for="preco-todos">Qualquer preço</label>
        <?php foreach (FAIXAS_PRECO as $valor => [$rotulo]): ?>
          <input type="radio" class="visualmente-oculto" name="preco" id="preco-<?= e($valor) ?>" value="<?= e($valor) ?>" <?= $f['preco'] === $valor ? 'checked' : '' ?>>
          <label class="chip" for="preco-<?= e($valor) ?>"><?= e($rotulo) ?></label>
        <?php endforeach; ?>
      </div>
    </fieldset>

    <?php if ($opcoes['tamanhos']): ?>
    <fieldset class="filtro-grupo">
      <legend class="t-headline">Tamanho</legend>
      <div class="filtro-opcoes">
        <?php foreach ($opcoes['tamanhos'] as $i => $tamanho): ?>
          <input type="checkbox" class="visualmente-oculto" name="tamanho[]" id="tam-<?= $i ?>" value="<?= e($tamanho) ?>" <?= in_array($tamanho, $f['tamanho'], true) ? 'checked' : '' ?>>
          <label class="chip" for="tam-<?= $i ?>"><?= e($tamanho) ?></label>
        <?php endforeach; ?>
      </div>
      <p class="t-footnote texto-terciario">Mostra só produtos com o tamanho em estoque.</p>
    </fieldset>
    <?php endif; ?>

    <?php if ($opcoes['times']): ?>
    <fieldset class="filtro-grupo">
      <legend class="t-headline">Time</legend>
      <div class="filtro-opcoes">
        <?php foreach ($opcoes['times'] as $i => $time): ?>
          <input type="checkbox" class="visualmente-oculto" name="time[]" id="time-<?= $i ?>" value="<?= e($time) ?>" <?= in_array($time, $f['time'], true) ? 'checked' : '' ?>>
          <label class="chip" for="time-<?= $i ?>"><?= e($time) ?></label>
        <?php endforeach; ?>
      </div>
    </fieldset>
    <?php endif; ?>

    <fieldset class="filtro-grupo">
      <legend class="t-headline">Mostrar só</legend>
      <div class="filtro-opcoes">
        <input type="checkbox" class="visualmente-oculto" name="oficial" id="so-oficial" value="1" <?= $f['oficial'] ? 'checked' : '' ?>>
        <label class="chip" for="so-oficial"><?= icone('escudo', 'icone--xs') ?> Produtos oficiais</label>
        <input type="checkbox" class="visualmente-oculto" name="promo" id="so-promo" value="1" <?= $f['promo'] ? 'checked' : '' ?>>
        <label class="chip" for="so-promo">Em promoção</label>
      </div>
    </fieldset>
  </form>
<?= painel_fim(
    '<a class="botao botao--glass" href="' . e(url_resultados(['q' => $f['q'], 'esporte' => $f['esporte'], 'categoria' => $f['categoria']])) . '">Limpar</a>'
  . '<button type="submit" form="form-filtros" class="botao botao--primary" style="flex: 1">Ver resultados</button>'
) ?>
<?php pagina_fim(); ?>
