<?php
/**
 * Telas 07 · Produto, 08 · Guia de tamanhos (painel) e 09 · Produto com tamanho e aviso de "adicionado"
 */
require __DIR__ . '/includes/bootstrap.php';

$produto = produto_por_id((int) ($_GET['id'] ?? 0));

if (!$produto) {
    http_response_code(404);
    pagina_inicio(['titulo' => 'Produto não encontrado', 'aba' => 'inicio', 'nav' => ['titulo' => '', 'voltar' => url('index.php')]]);
    ?>
    <div class="conteudo estado-vazio">
      <div class="estado-vazio__icone"><?= icone('pacote') ?></div>
      <h1 class="t-title-3">Produto não encontrado</h1>
      <p class="t-subheadline">Ele pode ter saído do catálogo. Veja o que temos de novo.</p>
      <a class="botao botao--primary botao--md" href="<?= e(url('index.php')) ?>">Ir para o início</a>
    </div>
    <?php
    pagina_fim();
    exit;
}

$preco        = preco_atual($produto);
$esgotado     = (int) $produto['estoque_total'] === 0;
$guia         = guia_de_tamanhos($produto['guia_tamanhos']);
$relacionados = produtos_relacionados($produto);
$tamanho_unico = count($produto['tamanhos']) === 1;

// Frete pelo CEP: vem do formulário (sem JavaScript) ou do último CEP calculado
$cep_frete = so_numeros($_GET['cep'] ?? cep_lembrado());
[$html_frete, $erro_frete] = $cep_frete !== '' ? frete_cep_html($cep_frete, $preco) : ['', null];
if (!isset($_GET['cep']) && $erro_frete) {
    [$html_frete, $erro_frete] = ['', null]; // CEP lembrado inválido: só não mostra nada
}

$voltar = url_resultados([], ['esporte' => $produto['esporte_slug'], 'categoria' => $produto['categoria_slug']]);
$qtd    = sacola_quantidade();
$acoes  = botao_favorito((int) $produto['id'], 'botao-icone--plain', 'produto.php?id=' . (int) $produto['id'])
        . '<button type="button" class="botao-icone botao-icone--plain" data-compartilhar aria-label="Compartilhar">' . icone('compartilhar') . '</button>'
        . '<a class="botao-icone botao-icone--plain" href="' . e(url('sacola.php')) . '" aria-label="Sacola">' . icone('sacola')
        . '<span class="tab-bar__contador" data-contador-sacola' . ($qtd ? '' : ' hidden') . '>' . $qtd . '</span></a>';

pagina_inicio([
    'titulo'    => $produto['nome'],
    'descricao' => $produto['descricao'],
    'nav'       => ['titulo' => $produto['categoria_nome'], 'voltar' => $voltar, 'acoes' => $acoes],
    'classe'    => 'pagina-produto',
]);
?>
<div class="conteudo produto">

  <!-- Fotos -->
  <div class="produto__galeria">
    <?php if (count($produto['imagens']) > 1): ?>
      <div class="carrossel">
        <div class="rolagem-horizontal">
          <?php foreach ($produto['imagens'] as $imagem): ?>
            <?= midia_produto(['imagem' => $imagem] + $produto) ?>
          <?php endforeach; ?>
        </div>
        <div class="pontos-carrossel" aria-hidden="true">
          <?php foreach ($produto['imagens'] as $i => $_): ?><span<?= $i === 0 ? ' aria-current="true"' : '' ?>></span><?php endforeach; ?>
        </div>
      </div>
    <?php else: ?>
      <?= midia_produto($produto) ?>
    <?php endif; ?>
  </div>

  <div class="produto__detalhes pilha-xl">

    <!-- Nome e preço -->
    <section class="pilha-sm">
      <div class="linha" style="flex-wrap: wrap">
        <?php foreach (selos_produto($produto) as [$tipo, $texto]): ?>
          <?= selo($tipo, $texto) ?>
        <?php endforeach; ?>
      </div>
      <h1 class="t-title-2"><?= e($produto['nome']) ?></h1>
      <p class="t-footnote texto-secundario">
        <?= e($produto['esporte_nome']) ?> · <?= e($produto['categoria_nome']) ?><?= $produto['marca'] ? ' · ' . e($produto['marca']) : '' ?>
      </p>
      <div class="produto__preco">
        <span class="t-price-large"><?= dinheiro($preco) ?></span>
        <?php if (!empty($produto['preco_promocional'])): ?>
          <span class="texto-terciario riscado"><?= dinheiro((float) $produto['preco']) ?></span>
        <?php endif; ?>
      </div>
      <p class="t-callout">
        <span class="produto__pix"><?= icone('pix', 'icone--xs') ?> <?= dinheiro(preco_pix($preco)) ?> no Pix</span>
        <span class="texto-secundario"> ou <?= e(texto_parcelas($preco)) ?></span>
      </p>
    </section>

    <!-- Tamanho -->
    <form id="form-sacola" class="pilha" action="<?= e(url('api/sacola.php')) ?>" method="post" novalidate>
      <?= csrf_campo() ?>
      <input type="hidden" name="acao" value="adicionar">
      <input type="hidden" name="produto_id" value="<?= (int) $produto['id'] ?>">
      <input type="hidden" name="quantidade" value="1">

      <div class="pilha-sm" role="radiogroup" aria-labelledby="rotulo-tamanho">
        <div class="linha linha--entre">
          <h2 class="t-headline" id="rotulo-tamanho"><?= $tamanho_unico ? 'Tamanho único' : 'Tamanho' ?></h2>
          <?php if ($guia): ?>
            <button type="button" class="botao botao--ghost botao--md" data-abrir-painel="guia-tamanhos" style="padding-inline: var(--spacing-sm)">
              <?= icone('regua', 'icone--sm') ?> Guia de tamanhos
            </button>
          <?php endif; ?>
        </div>
        <div class="tamanhos" id="tamanhos" <?= $tamanho_unico ? 'hidden' : '' ?>>
          <?php foreach ($produto['tamanhos'] as $i => $t): ?>
            <input type="radio" class="visualmente-oculto" name="tamanho" id="tamanho-<?= $i ?>"
                   value="<?= e($t['tamanho']) ?>" data-estoque="<?= (int) $t['estoque'] ?>"
                   <?= (int) $t['estoque'] === 0 ? 'disabled' : '' ?>
                   <?= $tamanho_unico && (int) $t['estoque'] > 0 ? 'checked' : '' ?>>
            <label class="opcao-tamanho" for="tamanho-<?= $i ?>">
              <?= e($t['tamanho']) ?>
              <?php if ((int) $t['estoque'] === 0): ?><span class="visualmente-oculto">(esgotado)</span><?php endif; ?>
            </label>
          <?php endforeach; ?>
        </div>
        <p class="t-footnote texto-terciario" id="aviso-estoque" aria-live="polite">
          <?php if ($esgotado): ?>
            Este produto esgotou. Fale com a gente no WhatsApp para saber quando volta.
          <?php elseif (!$tamanho_unico): ?>
            Tamanhos riscados estão esgotados.
          <?php endif; ?>
        </p>
      </div>
    </form>

    <!-- Entrega -->
    <section class="pilha-sm">
      <h2 class="t-headline">Entrega</h2>
      <ul class="lista" role="list">
        <li><?= linha_lista(
            $preco >= FRETE_GRATIS_ACIMA ? 'Frete grátis (PAC) para todo o RS' : 'Frete grátis acima de ' . dinheiro(FRETE_GRATIS_ACIMA),
            'caminhao'
        ) ?></li>
        <li><?= linha_lista('Retire grátis em São Leopoldo em 1 dia útil', 'loja') ?></li>
      </ul>
      <form class="frete-cep pilha-sm" id="frete" action="<?= e(url('produto.php')) ?>#frete" method="get"
            data-frete="<?= e(url('api/frete.php')) ?>" data-valor="<?= e(number_format($preco, 2, '.', '')) ?>" novalidate>
        <input type="hidden" name="id" value="<?= (int) $produto['id'] ?>">
        <div class="frete-cep__linha">
          <?= campo_texto('cep', 'Calcular frete e prazo', $cep_frete ? formatar_cep($cep_frete) : '', $erro_frete,
              ['inputmode' => 'numeric', 'autocomplete' => 'postal-code', 'data-mascara' => 'cep', 'placeholder' => '93000-000', 'maxlength' => 9]) ?>
          <button type="submit" class="botao botao--glass botao--md">Calcular</button>
        </div>
        <div id="frete-resultado" class="pilha-sm" aria-live="polite"><?= $html_frete ?></div>
      </form>
    </section>

    <!-- Descrição -->
    <?php if ($produto['descricao']): ?>
      <section class="pilha-sm">
        <h2 class="t-headline">Sobre o produto</h2>
        <p class="t-body texto-secundario"><?= nl2br(e($produto['descricao'])) ?></p>
      </section>
    <?php endif; ?>

    <!-- Garantias -->
    <section>
      <ul class="lista" role="list">
        <?php if ($produto['oficial']): ?>
          <li><?= linha_lista('Produto oficial licenciado', 'escudo') ?></li>
        <?php endif; ?>
        <li><?= linha_lista('Troca grátis em até ' . TROCA_PRAZO_DIAS . ' dias', 'trocas', url('trocas.php')) ?></li>
        <li><?= linha_lista('Dúvidas? Fale no WhatsApp', 'whatsapp', 'https://wa.me/' . CONTATO_WHATSAPP) ?></li>
      </ul>
    </section>
  </div>
</div>

<?php if ($relacionados): ?>
  <section class="conteudo" style="margin-top: var(--spacing-3xl)">
    <?= cabecalho_secao('Você também pode gostar') ?>
    <div class="rolagem-horizontal">
      <?php foreach ($relacionados as $r): ?>
        <?= card_produto($r) ?>
      <?php endforeach; ?>
    </div>
  </section>
<?php endif; ?>

<!-- Barra inferior com o botão principal -->
<div class="barra-inferior vidro-thick">
  <div class="barra-inferior__conteudo">
    <div class="barra-inferior__resumo">
      <span class="t-headline"><?= dinheiro($preco) ?></span>
      <span class="t-caption texto-secundario" id="resumo-tamanho"><?= $tamanho_unico ? 'Tamanho único' : 'Escolha o tamanho' ?></span>
    </div>
    <button type="submit" form="form-sacola" class="botao botao--primary" style="flex: 1" <?= $esgotado ? 'disabled' : '' ?>>
      <?= icone('sacola', 'icone--sm') ?> <?= $esgotado ? 'Esgotado' : 'Adicionar à sacola' ?>
    </button>
  </div>
</div>

<?php if ($guia): ?>
  <!-- Tela 08 · Guia de tamanhos -->
  <?= painel_inicio('guia-tamanhos', 'Guia de tamanhos') ?>
    <div class="pilha">
      <p class="t-subheadline texto-secundario"><?= e($guia['dica']) ?></p>
      <div class="tabela-medidas" role="region" aria-label="Tabela de medidas" tabindex="0">
        <table>
          <thead>
            <tr><?php foreach ($guia['colunas'] as $coluna): ?><th scope="col"><?= e($coluna) ?></th><?php endforeach; ?></tr>
          </thead>
          <tbody>
            <?php foreach ($guia['linhas'] as $linha): ?>
              <tr><?php foreach ($linha as $j => $valor): ?>
                <?= $j === 0 ? '<th scope="row">' . e($valor) . '</th>' : '<td>' . e($valor) . '</td>' ?>
              <?php endforeach; ?></tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <p class="t-footnote texto-terciario">Medidas em centímetros, aproximadas. Pode variar até 2 cm. Entre dois tamanhos, escolha o maior.</p>
    </div>
  <?= painel_fim('<button type="button" class="botao botao--primary botao--bloco" data-fechar-painel>Entendi</button>') ?>
<?php endif; ?>

<?php pagina_fim(['scripts' => ['produto.js', 'conta.js']]); ?>
