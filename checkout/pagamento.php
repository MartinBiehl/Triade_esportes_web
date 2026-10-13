<?php
/**
 * Telas 15 · Pagamento Pix e 15b · Pagamento cartão (e boleto)
 *
 * SIMULAÇÃO: os dados do cartão são conferidos (número possível, validade, CVV) mas não são guardados.
 */
require __DIR__ . '/../includes/bootstrap.php';
checkout_exigir('pagamento');

$c = &checkout();
$itens  = sacola_itens();
$totais = checkout_totais($itens, $c['entrega'], null);
$escolha = $c['pagamento']['tipo'] ?? 'pix';
$parcelas_escolhida = $c['pagamento']['parcelas'] ?? max_parcelas($totais['total_cartao']);
$erros = [];
$cartao = ['cartao_numero' => '', 'cartao_nome' => '', 'cartao_validade' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    [$dados, $erros] = validar_pagamento($_POST, $totais['total_cartao']);
    $escolha = $dados['tipo'];
    $parcelas_escolhida = $dados['parcelas'];
    // devolve o que foi digitado para o cliente corrigir (menos o CVV)
    $cartao = [
        'cartao_numero'   => (string) ($_POST['cartao_numero'] ?? ''),
        'cartao_nome'     => (string) ($_POST['cartao_nome'] ?? ''),
        'cartao_validade' => (string) ($_POST['cartao_validade'] ?? ''),
    ];
    if (!$erros) {
        $c['pagamento'] = $dados;
        redirecionar('checkout/revisao.php');
    }
}

$max = max_parcelas($totais['total_cartao']);

pagina_inicio([
    'titulo' => 'Pagamento',
    'nav'    => ['titulo' => 'Pagamento', 'voltar' => url('checkout/entrega.php')],
]);
?>
<div class="conteudo conteudo--leitura pilha-xl" style="padding-top: var(--spacing-lg)">
  <?= checkout_etapas('pagamento') ?>

  <h1 class="t-title-2">Como você quer pagar?</h1>

  <form id="form-etapa" class="pilha" method="post" data-validar novalidate data-pagamento
        data-total-pix="<?= e(number_format($totais['total_pix'], 2, '.', '')) ?>"
        data-total-cartao="<?= e(number_format($totais['total_cartao'], 2, '.', '')) ?>">
    <?= csrf_campo() ?>
    <?php if (isset($erros['pagamento'])): ?><p class="texto-erro" role="alert"><?= e($erros['pagamento']) ?></p><?php endif; ?>

    <div class="pilha-sm" role="radiogroup" aria-label="Forma de pagamento">
      <input type="radio" class="visualmente-oculto" name="pagamento" id="pag-pix" value="pix" <?= $escolha === 'pix' ? 'checked' : '' ?>>
      <label class="opcao-card" for="pag-pix">
        <span class="opcao-card__radio"></span>
        <span class="opcao-card__textos">
          <span class="opcao-card__titulo linha"><?= icone('pix', 'icone--sm') ?> Pix <?= selo('novo', (int) (PIX_DESCONTO * 100) . '% off') ?></span>
          <span class="opcao-card__descricao">Aprovação na hora. O código vale <?= PIX_VALIDADE_MINUTOS ?> minutos.</span>
        </span>
        <span class="opcao-card__valor"><?= dinheiro($totais['total_pix']) ?></span>
      </label>

      <input type="radio" class="visualmente-oculto" name="pagamento" id="pag-cartao" value="cartao" <?= $escolha === 'cartao' ? 'checked' : '' ?>>
      <label class="opcao-card" for="pag-cartao">
        <span class="opcao-card__radio"></span>
        <span class="opcao-card__textos">
          <span class="opcao-card__titulo linha"><?= icone('cartao', 'icone--sm') ?> Cartão de crédito</span>
          <span class="opcao-card__descricao"><?= e(texto_parcelas($totais['total_cartao'])) ?></span>
        </span>
        <span class="opcao-card__valor"><?= dinheiro($totais['total_cartao']) ?></span>
      </label>

      <input type="radio" class="visualmente-oculto" name="pagamento" id="pag-boleto" value="boleto" <?= $escolha === 'boleto' ? 'checked' : '' ?>>
      <label class="opcao-card" for="pag-boleto">
        <span class="opcao-card__radio"></span>
        <span class="opcao-card__textos">
          <span class="opcao-card__titulo linha"><?= icone('boleto', 'icone--sm') ?> Boleto</span>
          <span class="opcao-card__descricao">Aprovação em até <?= BOLETO_PRAZO_DIAS ?> dias úteis</span>
        </span>
        <span class="opcao-card__valor"><?= dinheiro($totais['total_cartao']) ?></span>
      </label>
    </div>

    <!-- Tela 15b · dados do cartão -->
    <section class="caixa pilha" id="dados-cartao" aria-label="Dados do cartão">
      <?= campo_texto('cartao_numero', 'Número do cartão', $cartao['cartao_numero'], $erros['cartao_numero'] ?? null,
          ['inputmode' => 'numeric', 'autocomplete' => 'cc-number', 'required' => true, 'data-validar' => 'cartao', 'data-mascara' => 'cartao', 'placeholder' => '0000 0000 0000 0000']) ?>
      <?= campo_texto('cartao_nome', 'Nome impresso no cartão', $cartao['cartao_nome'], $erros['cartao_nome'] ?? null,
          ['autocomplete' => 'cc-name', 'required' => true, 'maxlength' => 60, 'style' => 'text-transform: uppercase']) ?>
      <div class="campos-lado-a-lado">
        <?= campo_texto('cartao_validade', 'Validade', $cartao['cartao_validade'], $erros['cartao_validade'] ?? null,
            ['inputmode' => 'numeric', 'autocomplete' => 'cc-exp', 'required' => true, 'data-validar' => 'validade', 'data-mascara' => 'validade', 'placeholder' => 'MM/AA']) ?>
        <?= campo_texto('cartao_cvv', 'CVV', '', $erros['cartao_cvv'] ?? null,
            ['inputmode' => 'numeric', 'autocomplete' => 'cc-csc', 'required' => true, 'data-validar' => 'cvv', 'data-mascara' => 'cvv', 'placeholder' => '123']) ?>
      </div>
      <div class="campo">
        <label class="campo__rotulo" for="campo-parcelas">Parcelas</label>
        <select class="campo__entrada" id="campo-parcelas" name="parcelas">
          <?php for ($n = 1; $n <= $max; $n++): ?>
            <option value="<?= $n ?>" <?= (int) $parcelas_escolhida === $n ? 'selected' : '' ?>>
              <?= $n ?>x de <?= dinheiro(round($totais['total_cartao'] / $n, 2)) ?> sem juros
            </option>
          <?php endfor; ?>
        </select>
      </div>
      <p class="t-footnote texto-terciario">
        <?= icone('cadeado', 'icone--xs') ?> Simulação: os dados do cartão não são guardados.
        Para testar a recusa, use um cartão terminado em 0002 (ex.: 4000 0000 0000 0002).
      </p>
    </section>
  </form>
</div>
<?= checkout_barra('form-etapa', 'Revisar pedido', $escolha === 'pix' ? $totais['total_pix'] : $totais['total_cartao'], 'Total com frete') ?>
<?php pagina_fim(['scripts' => ['validacao.js', 'checkout.js']]); ?>
