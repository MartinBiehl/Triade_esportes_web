<?php
/**
 * Tela 14 · Entrega
 */
require __DIR__ . '/../includes/bootstrap.php';
checkout_exigir('entrega');

$c = &checkout();
$itens  = sacola_itens();
$resumo = sacola_resumo($itens);
$opcoes = opcoes_entrega($c['endereco']['cep'], $resumo['subtotal']);
$escolha = $c['entrega'] ?? 'pac';
$erro = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $escolha = (string) ($_POST['entrega'] ?? '');
    if (!isset($opcoes[$escolha])) {
        $erro = 'Escolha uma forma de entrega.';
    } else {
        $c['entrega'] = $escolha;
        redirecionar('checkout/pagamento.php');
    }
}

$end = $c['endereco'];
$totais = checkout_totais($itens, isset($opcoes[$escolha]) ? $escolha : null, null);

pagina_inicio([
    'titulo' => 'Entrega',
    'nav'    => ['titulo' => 'Entrega', 'voltar' => url('checkout/endereco.php')],
]);
?>
<div class="conteudo conteudo--leitura pilha-xl" style="padding-top: var(--spacing-lg)">
  <?= checkout_etapas('entrega') ?>

  <section class="pilha-sm">
    <h1 class="t-title-2">Como você quer receber?</h1>
    <a class="caixa linha linha--entre" href="<?= e(url('checkout/endereco.php')) ?>" style="color: inherit">
      <span class="linha" style="align-items: flex-start">
        <?= icone('local', 'icone--sm') ?>
        <span class="t-subheadline">
          <?= e($end['logradouro']) ?>, <?= e($end['numero']) ?><?= $end['complemento'] ? ' · ' . e($end['complemento']) : '' ?><br>
          <span class="texto-secundario"><?= e($end['bairro']) ?>, <?= e($end['cidade']) ?>/RS · <?= e(formatar_cep($end['cep'])) ?></span>
        </span>
      </span>
      <span class="cabecalho-secao__link">Alterar</span>
    </a>
  </section>

  <form id="form-etapa" class="pilha-sm" method="post" data-opcoes-total data-subtotal="<?= e(number_format($resumo['subtotal'], 2, '.', '')) ?>">
    <?= csrf_campo() ?>
    <?php if ($erro): ?><p class="texto-erro t-subheadline" role="alert"><?= e($erro) ?></p><?php endif; ?>
    <?php foreach ($opcoes as $tipo => $o): ?>
      <input type="radio" class="visualmente-oculto" name="entrega" id="entrega-<?= e($tipo) ?>" value="<?= e($tipo) ?>"
             data-preco="<?= e(number_format($o['preco_final'], 2, '.', '')) ?>" <?= $escolha === $tipo ? 'checked' : '' ?>>
      <label class="opcao-card" for="entrega-<?= e($tipo) ?>">
        <span class="opcao-card__radio"></span>
        <span class="opcao-card__textos">
          <span class="opcao-card__titulo"><?= e($o['nome']) ?></span>
          <span class="opcao-card__descricao"><?= e($o['prazo_texto']) ?></span>
          <?php if ($tipo === 'retirada'): ?>
            <span class="opcao-card__descricao"><?= e(DEPOSITO_ENDERECO) ?></span>
          <?php endif; ?>
        </span>
        <span class="opcao-card__valor <?= $o['preco_final'] == 0 ? 'texto-sucesso' : '' ?>">
          <?= $o['preco_final'] == 0 ? 'Grátis' : dinheiro($o['preco_final']) ?>
        </span>
      </label>
    <?php endforeach; ?>
    <?php if (!$resumo['frete_gratis']): ?>
      <p class="t-footnote texto-secundario">Faltam <?= dinheiro($resumo['falta_frete']) ?> para o PAC sair de graça.</p>
    <?php endif; ?>
    <?php $regiao = regiao_do_cep($c['endereco']['cep']); ?>
    <p class="t-footnote texto-terciario">
      Frete para <?= e(formatar_cep($c['endereco']['cep'])) ?><?= $regiao ? ' · ' . e($regiao['nome']) : '' ?>. Valores e prazos simulados para o trabalho.
    </p>
  </form>
</div>
<?= checkout_barra('form-etapa', 'Continuar', $totais['total'], 'Produtos + frete') ?>
<?php pagina_fim(['scripts' => ['checkout.js']]); ?>
