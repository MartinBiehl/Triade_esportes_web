<?php
/**
 * Moldura de todas as páginas: <head>, cabeçalho, barra de abas e scripts.
 *
 * Uso numa página:
 *   pagina_inicio(['titulo' => 'Sacola', 'aba' => 'sacola']);
 *   ... conteúdo ...
 *   pagina_fim();
 *
 * Opções de pagina_inicio():
 *   titulo     texto da aba do navegador
 *   aba        qual aba fica marcada: inicio, buscar, esportes, sacola, conta (null = sem barra de abas)
 *   nav        cabeçalho inline em vidro das telas internas:
 *              ['titulo' => 'Produto', 'voltar' => url(...), 'acoes' => '<button ...>']
 *   classe     classes extras para o <main>
 */

$GLOBALS['pagina_atual'] = [];

/** Quantos itens há na sacola (para o contador da barra de abas). */
function sacola_quantidade(): int
{
    $total = 0;
    foreach ($_SESSION['sacola'] ?? [] as $item) {
        $total += (int) ($item['quantidade'] ?? 0);
    }
    return $total;
}

function pagina_inicio(array $opcoes = []): void
{
    $opcoes += ['titulo' => null, 'aba' => null, 'nav' => null, 'classe' => '', 'descricao' => null];
    $GLOBALS['pagina_atual'] = $opcoes;

    $titulo = $opcoes['titulo'] ? $opcoes['titulo'] . ' · ' . LOJA_NOME : LOJA_NOME;
    $descricao = $opcoes['descricao']
        ?? 'Camisas de times, chuteiras, bolas e acessórios de futebol e basquete. Entrega para todo o RS.';

    $classes = ['pagina'];
    if ($opcoes['nav']) {
        $classes[] = 'pagina--interna';
    }
    if ($opcoes['aba'] === null) {
        $classes[] = 'pagina--sem-abas';
    }
    if ($opcoes['classe']) {
        $classes[] = $opcoes['classe'];
    }
    ?>
<!doctype html>
<html lang="pt-BR" data-icones="<?= e(asset('img/icones.svg')) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="theme-color" content="#F8F8FA">
  <meta name="description" content="<?= e($descricao) ?>">
  <title><?= e($titulo) ?></title>
  <link rel="preload" href="<?= e(url('assets/fonts/inter-variavel.woff2')) ?>" as="font" type="font/woff2" crossorigin>
  <link rel="stylesheet" href="<?= e(asset('css/tokens.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('css/base.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('css/componentes.css')) ?>">
  <?php if (is_file(__DIR__ . '/../assets/css/telas.css')): ?>
  <link rel="stylesheet" href="<?= e(asset('css/telas.css')) ?>">
  <?php endif; ?>
  <meta name="csrf" content="<?= e(csrf_token()) ?>">
</head>
<body>
<?php if ($opcoes['nav']): ?>
  <?php $nav = $opcoes['nav'] + ['titulo' => '', 'voltar' => null, 'acoes' => '']; ?>
  <header class="nav-inline">
    <div class="nav-inline__barra">
      <div class="nav-inline__esquerda">
        <?php if ($nav['voltar']): ?>
          <a class="botao-icone botao-icone--plain" href="<?= e($nav['voltar']) ?>" aria-label="Voltar"><?= icone('voltar') ?></a>
        <?php endif; ?>
      </div>
      <h1 class="nav-inline__titulo"><?= e($nav['titulo']) ?></h1>
      <div class="nav-inline__direita"><?= $nav['acoes'] ?></div>
    </div>
  </header>
<?php endif; ?>
<main class="<?= e(implode(' ', $classes)) ?>" id="conteudo">
    <?php
}

function pagina_fim(array $opcoes = []): void
{
    $aba = $GLOBALS['pagina_atual']['aba'] ?? null;
    $abas = [
        'inicio'   => ['Início',   'inicio',   'index.php'],
        'buscar'   => ['Buscar',   'buscar',   'buscar.php'],
        'esportes' => ['Esportes', 'esportes', 'esportes.php'],
        'sacola'   => ['Sacola',   'sacola',   'sacola.php'],
        'conta'    => ['Conta',    'conta',    'conta/'],
    ];
    $qtd = sacola_quantidade();
    ?>
</main>
<?php if ($aba !== null): ?>
  <nav class="tab-bar vidro-regular" aria-label="Navegação principal">
    <ul class="tab-bar__lista" role="list">
      <?php foreach ($abas as $chave => [$rotulo, $ic, $link]): ?>
        <li>
          <a class="tab-bar__item" href="<?= e(url($link)) ?>"<?= $chave === $aba ? ' aria-current="page"' : '' ?>>
            <?= icone($ic) ?>
            <span><?= e($rotulo) ?></span>
            <?php if ($chave === 'sacola'): ?>
              <span class="tab-bar__contador" data-contador-sacola<?= $qtd ? '' : ' hidden' ?>><?= $qtd ?></span>
            <?php endif; ?>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  </nav>
<?php endif; ?>
<?php if ($aviso = aviso_obter()): ?>
  <div hidden id="aviso-pendente"
       data-texto="<?= e($aviso['texto']) ?>"
       data-tipo="<?= e($aviso['tipo']) ?>"
       <?php if ($aviso['acao']): ?>data-acao-rotulo="<?= e($aviso['acao']['rotulo']) ?>" data-acao-href="<?= e($aviso['acao']['href']) ?>"<?php endif; ?>></div>
<?php endif; ?>
<script src="<?= e(asset('js/mola.js')) ?>" defer></script>
<script src="<?= e(asset('js/painel.js')) ?>" defer></script>
<script src="<?= e(asset('js/aviso.js')) ?>" defer></script>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
<?php foreach ($opcoes['scripts'] ?? [] as $script): ?>
<script src="<?= e(asset('js/' . $script)) ?>" defer></script>
<?php endforeach; ?>
</body>
</html>
    <?php
}
