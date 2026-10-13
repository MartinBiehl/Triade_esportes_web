<?php
/**
 * Painel da loja · Cadastrar ou editar produto
 * Dados, preço, tamanhos com estoque, fotos, selos e se aparece na loja.
 */
require __DIR__ . '/../includes/admin.php';

exigir_admin();

$id = (int) ($_GET['id'] ?? 0);
$produto = $id ? produto_admin($id) : null;
if ($id && !$produto) {
    aviso_definir('Produto não encontrado', 'erro');
    redirecionar('admin/produtos.php');
}

$dados = $produto ? [
    'nome' => $produto['nome'], 'categoria_id' => (int) $produto['categoria_id'], 'marca' => $produto['marca'] ?? '',
    'time_nome' => $produto['time_nome'] ?? '', 'descricao' => $produto['descricao'] ?? '',
    'preco_texto' => preco_campo((float) $produto['preco']),
    'promocional_texto' => preco_campo($produto['preco_promocional'] !== null ? (float) $produto['preco_promocional'] : null),
    'midia' => $produto['midia'], 'guia_tamanhos' => $produto['guia_tamanhos'] ?? '',
    'oficial' => (int) $produto['oficial'], 'lancamento' => (int) $produto['lancamento'],
    'destaque' => (int) $produto['destaque'], 'ativo' => (int) $produto['ativo'],
] : [
    'nome' => '', 'categoria_id' => (int) ($_GET['categoria'] ?? 0), 'marca' => '', 'time_nome' => '', 'descricao' => '',
    'preco_texto' => '', 'promocional_texto' => '', 'midia' => 'camisa', 'guia_tamanhos' => '',
    'oficial' => 0, 'lancamento' => 1, 'destaque' => 0, 'ativo' => 1,
];
$tamanhos = $produto['tamanhos'] ?? [];
$erros = [];
$erros_fotos = $_SESSION['erros_fotos'] ?? [];
unset($_SESSION['erros_fotos']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // fotos grandes demais para o servidor chegam com o formulário vazio (sem nem o código de segurança)
    if (!$_POST && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        $erros_fotos = ['As fotos passaram do limite do servidor (' . ini_get('post_max_size') . ' por envio). Mande menos fotos de cada vez. Nada foi salvo.'];
    } else {
        csrf_validar();

        if (($_POST['acao'] ?? '') === 'excluir' && $produto) {
            $erro = excluir_produto($id);
            aviso_definir($erro ?? 'Produto excluído', $erro ? 'erro' : 'sucesso');
            redirecionar($erro ? 'admin/produto.php?id=' . $id : 'admin/produtos.php');
        }

        [$dados, $tamanhos, $erros] = validar_produto($_POST);
        if (!$erros) {
            $id = salvar_produto($dados, $tamanhos, $produto ? $id : null);
            remover_fotos($id, (array) ($_POST['remover_foto'] ?? []));
            $erros_fotos = salvar_fotos($id, $_FILES['fotos'] ?? []);
            if (!empty($_POST['capa'])) {
                definir_capa($id, (int) $_POST['capa']);
            }
            if ($erros_fotos) {
                $_SESSION['erros_fotos'] = $erros_fotos;
            }
            aviso_definir($produto ? 'Produto salvo' : 'Produto cadastrado', 'sucesso',
                ['rotulo' => 'Ver na loja', 'href' => url('produto.php?id=' . $id)]);
            redirecionar('admin/produto.php?id=' . $id);
        }
    }
}

$imagens = $produto['imagens'] ?? [];
$titulo = $produto ? 'Editar produto' : 'Novo produto';
$linhas_tamanho = array_merge($tamanhos, array_fill(0, $tamanhos ? 1 : 3, ['tamanho' => '', 'estoque' => '']));

admin_pagina_inicio([
    'titulo' => $titulo,
    'aba'    => 'produtos',
    'nav'    => [
        'titulo' => $titulo,
        'voltar' => url('admin/produtos.php'),
        'acoes'  => $produto && $produto['ativo']
            ? '<a class="botao-icone botao-icone--plain" href="' . e(url('produto.php?id=' . $id)) . '" aria-label="Ver na loja">' . icone('loja') . '</a>'
            : '',
    ],
]);
?>
<div class="conteudo conteudo--leitura pilha-xl" style="padding-top: var(--spacing-lg)">

  <?php if ($erros_fotos): ?>
    <div class="caixa-erro t-subheadline admin-erros" role="alert">
      <?= icone('alerta', 'icone--sm') ?>
      <ul role="list"><?php foreach ($erros_fotos as $msg): ?><li><?= e($msg) ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <form class="pilha-xl" method="post" enctype="multipart/form-data" data-validar novalidate id="form-produto">
    <?= csrf_campo() ?>
    <?= caixa_erros($erros) ?>

    <!-- Informações -->
    <section class="pilha">
      <h2 class="t-headline">Informações</h2>
      <?= campo_texto('nome', 'Nome do produto', $dados['nome'], $erros['nome'] ?? null,
          ['required' => true, 'maxlength' => 120, 'placeholder' => 'Ex.: Camisa Grêmio I 26/27', 'data-obrigatorio' => 'Digite o nome do produto.']) ?>
      <?= campo_selecao('categoria_id', 'Categoria', ['' => 'Escolha…'] + opcoes_categoria(), $dados['categoria_id'] ?: '', $erros['categoria_id'] ?? null,
          ['required' => true, 'data-obrigatorio' => 'Escolha a categoria.']) ?>
      <div class="campos-lado-a-lado">
        <?= campo_texto('marca', 'Marca', $dados['marca'], null, ['maxlength' => 60], 'Opcional') ?>
        <?= campo_texto('time_nome', 'Time', $dados['time_nome'], null, ['maxlength' => 60], 'Aparece no filtro Time') ?>
      </div>
      <div class="campo">
        <label class="campo__rotulo" for="campo-descricao">Descrição</label>
        <textarea class="campo__entrada" id="campo-descricao" name="descricao" maxlength="2000" rows="4" aria-describedby="campo-descricao-ajuda"><?= e($dados['descricao']) ?></textarea>
        <span class="campo__ajuda" id="campo-descricao-ajuda">Tecido, tecnologia, para quem é. Aparece na página do produto.</span>
      </div>
    </section>

    <!-- Preço -->
    <section class="pilha">
      <h2 class="t-headline">Preço</h2>
      <div class="campos-lado-a-lado">
        <?= campo_texto('preco', 'Preço (R$)', $dados['preco_texto'], $erros['preco'] ?? null,
            ['required' => true, 'inputmode' => 'decimal', 'maxlength' => 12, 'placeholder' => '199,90', 'data-validar' => 'preco', 'data-obrigatorio' => 'Digite o preço.']) ?>
        <?= campo_texto('preco_promocional', 'Promoção (R$)', $dados['promocional_texto'], $erros['preco_promocional'] ?? null,
            ['inputmode' => 'decimal', 'maxlength' => 12, 'placeholder' => 'Vazio = sem promo', 'data-validar' => 'preco'], 'Ganha o selo Promo') ?>
      </div>
      <p class="t-footnote texto-secundario">No Pix o cliente paga <?= (int) (PIX_DESCONTO * 100) ?>% a menos, e o parcelamento é calculado sozinho (até <?= CARTAO_MAX_PARCELAS ?>x, parcela mínima de <?= dinheiro(CARTAO_PARCELA_MINIMA) ?>).</p>
    </section>

    <!-- Tamanhos e estoque -->
    <section class="pilha" id="tamanhos">
      <div class="pilha-xs">
        <h2 class="t-headline">Tamanhos e estoque</h2>
        <p class="t-footnote texto-secundario">Estoque 0 deixa o tamanho riscado na loja. Linhas vazias são ignoradas.</p>
      </div>
      <?php if (isset($erros['tamanhos'])): ?>
        <p class="caixa-erro t-subheadline" role="alert"><?= icone('alerta', 'icone--sm') ?> <?= e($erros['tamanhos']) ?></p>
      <?php endif; ?>
      <div class="linha admin-atalhos-tamanho" hidden data-atalhos-tamanho>
        <span class="t-footnote texto-secundario">Preencher:</span>
        <button type="button" class="chip" data-preencher-tamanhos="P,M,G,GG">P a GG</button>
        <button type="button" class="chip" data-preencher-tamanhos="38,39,40,41,42,43">38 a 43</button>
        <button type="button" class="chip" data-preencher-tamanhos="Único">Único</button>
      </div>
      <div class="admin-tamanhos" data-lista-tamanhos>
        <div class="admin-tamanhos__cabecalho t-footnote texto-secundario" aria-hidden="true"><span>Tamanho</span><span>Estoque</span><span></span></div>
        <?php foreach ($linhas_tamanho as $i => $t): ?>
          <div class="admin-tamanhos__linha" data-linha-tamanho>
            <input class="campo__entrada" name="tamanho[]" value="<?= e((string) $t['tamanho']) ?>" maxlength="10" aria-label="Tamanho <?= $i + 1 ?>" placeholder="M" autocapitalize="characters">
            <input class="campo__entrada" name="estoque[]" value="<?= e((string) $t['estoque']) ?>" inputmode="numeric" pattern="[0-9]*" maxlength="5" aria-label="Estoque do tamanho <?= $i + 1 ?>" placeholder="0">
            <button type="button" class="botao-icone botao-icone--plain botao-icone--sm" data-remover-tamanho aria-label="Remover tamanho <?= $i + 1 ?>" hidden><?= icone('lixeira', 'icone--sm') ?></button>
          </div>
        <?php endforeach; ?>
      </div>
      <button type="button" class="botao botao--glass botao--md" data-adicionar-tamanho hidden><?= icone('mais', 'icone--sm') ?> Adicionar tamanho</button>
      <?= campo_selecao('guia_tamanhos', 'Guia de tamanhos', GUIAS_TAMANHO, $dados['guia_tamanhos']) ?>
    </section>

    <!-- Fotos -->
    <section class="pilha">
      <div class="pilha-xs">
        <h2 class="t-headline">Fotos</h2>
        <p class="t-footnote texto-secundario">JPG, PNG ou WebP, até 3 MB cada e até <?= FOTO_MAX_POR_PRODUTO ?> por produto. Fotos em pé (4:5) ficam melhores.</p>
      </div>
      <?php if ($imagens): ?>
        <ul class="admin-fotos" role="list">
          <?php foreach ($imagens as $i => $img): ?>
            <li class="admin-foto">
              <img src="<?= e(url('assets/img/produtos/' . $img['arquivo'])) ?>" alt="Foto <?= $i + 1 ?>">
              <label class="admin-foto__opcao">
                <input type="radio" name="capa" value="<?= (int) $img['id'] ?>" <?= $i === 0 ? 'checked' : '' ?>> Capa
              </label>
              <label class="admin-foto__opcao texto-erro">
                <input type="checkbox" name="remover_foto[]" value="<?= (int) $img['id'] ?>"> Remover
              </label>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
      <?php if (count($imagens) < FOTO_MAX_POR_PRODUTO): ?>
        <div class="campo">
          <label class="campo__rotulo" for="campo-fotos"><?= $imagens ? 'Adicionar fotos' : 'Escolher fotos' ?></label>
          <input class="campo__entrada admin-arquivo" id="campo-fotos" type="file" name="fotos[]" accept="image/jpeg,image/png,image/webp" multiple data-previa-fotos>
          <ul class="admin-fotos" role="list" data-previa hidden></ul>
        </div>
      <?php endif; ?>
      <?= campo_selecao('midia', 'Desenho enquanto não tem foto', MIDIAS_PRODUTO, $dados['midia']) ?>
    </section>

    <!-- Vitrine -->
    <section class="pilha-sm">
      <h2 class="t-headline">Selos e vitrine</h2>
      <?= campo_interruptor('ativo', 'Aparece na loja', (bool) $dados['ativo'], 'Desligado, o produto some do site, mas os pedidos antigos continuam.') ?>
      <?= campo_interruptor('destaque', 'Destaque no Início', (bool) $dados['destaque']) ?>
      <?= campo_interruptor('lancamento', 'Selo "Novo"', (bool) $dados['lancamento'], 'Também entra em Lançamentos no Início.') ?>
      <?= campo_interruptor('oficial', 'Selo "Oficial"', (bool) $dados['oficial'], 'Produto licenciado do time.') ?>
    </section>

    <button type="submit" class="botao botao--primary botao--bloco"><?= $produto ? 'Salvar alterações' : 'Cadastrar produto' ?></button>
  </form>

  <?php if ($produto): ?>
    <?php if ($produto['vendido']): ?>
      <p class="t-footnote texto-secundario" style="text-align: center">Este produto já foi vendido, então não pode ser excluído. Para tirá-lo do site, desligue "Aparece na loja".</p>
    <?php else: ?>
      <form method="post" data-confirmar="Excluir &quot;<?= e($produto['nome']) ?>&quot;? As fotos e o estoque somem junto e isso não pode ser desfeito.">
        <?= csrf_campo() ?>
        <input type="hidden" name="acao" value="excluir">
        <button type="submit" class="botao botao--ghost botao--bloco texto-erro"><?= icone('lixeira', 'icone--sm') ?> Excluir produto</button>
      </form>
    <?php endif; ?>
  <?php endif; ?>
</div>
<?php pagina_fim(['scripts' => ['validacao.js', 'admin.js']]); ?>
