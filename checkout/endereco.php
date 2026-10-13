<?php
/**
 * Tela 13 · Endereço
 */
require __DIR__ . '/../includes/bootstrap.php';
checkout_exigir('endereco');

$c = &checkout();
$dados = $c['endereco'] ?? [
    'destinatario' => $c['identificacao']['nome'] ?? '', 'cep' => '', 'logradouro' => '', 'numero' => '',
    'complemento' => '', 'bairro' => '', 'cidade' => '', 'uf' => 'RS',
];
$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    [$dados, $erros] = validar_endereco($_POST);
    if (!$erros) {
        $c['endereco'] = $dados;
        redirecionar('checkout/entrega.php');
    }
}

$resumo = sacola_resumo(sacola_itens());

pagina_inicio([
    'titulo' => 'Endereço',
    'nav'    => ['titulo' => 'Endereço', 'voltar' => url('checkout/identificacao.php')],
]);
?>
<div class="conteudo conteudo--leitura pilha-xl" style="padding-top: var(--spacing-lg)">
  <?= checkout_etapas('endereco') ?>

  <section class="pilha-sm">
    <h1 class="t-title-2">Onde entregar?</h1>
    <p class="t-subheadline texto-secundario">Entregamos em todo o Rio Grande do Sul. Se preferir retirar em São Leopoldo, escolha na próxima etapa.</p>
  </section>

  <form id="form-etapa" class="pilha" method="post" data-validar novalidate>
    <?= csrf_campo() ?>
    <?= campo_texto('destinatario', 'Quem vai receber', $dados['destinatario'], $erros['destinatario'] ?? null,
        ['autocomplete' => 'name', 'required' => true, 'maxlength' => 120]) ?>
    <div class="campos-lado-a-lado">
      <?= campo_texto('cep', 'CEP', $dados['cep'] ? formatar_cep($dados['cep']) : '', $erros['cep'] ?? null,
          ['inputmode' => 'numeric', 'autocomplete' => 'postal-code', 'required' => true, 'data-validar' => 'cep-rs', 'data-mascara' => 'cep', 'placeholder' => '93000-000']) ?>
      <div class="campo">
        <label class="campo__rotulo" for="campo-uf">Estado</label>
        <select class="campo__entrada" id="campo-uf" name="uf" disabled>
          <?php foreach (UFS_ENTREGA as $sigla => $nome): ?>
            <option value="<?= e($sigla) ?>" selected><?= e($nome) ?></option>
          <?php endforeach; ?>
        </select>
        <span class="campo__ajuda">Só RS, por enquanto</span>
      </div>
    </div>
    <?= campo_texto('logradouro', 'Rua', $dados['logradouro'], $erros['logradouro'] ?? null,
        ['autocomplete' => 'address-line1', 'required' => true, 'maxlength' => 160]) ?>
    <div class="campos-lado-a-lado">
      <?= campo_texto('numero', 'Número', $dados['numero'], $erros['numero'] ?? null,
          ['inputmode' => 'numeric', 'required' => true, 'maxlength' => 20]) ?>
      <?= campo_texto('complemento', 'Complemento', $dados['complemento'], $erros['complemento'] ?? null,
          ['autocomplete' => 'address-line2', 'maxlength' => 80], 'Opcional') ?>
    </div>
    <?= campo_texto('bairro', 'Bairro', $dados['bairro'], $erros['bairro'] ?? null, ['required' => true, 'maxlength' => 80]) ?>
    <?= campo_texto('cidade', 'Cidade', $dados['cidade'], $erros['cidade'] ?? null,
        ['autocomplete' => 'address-level2', 'required' => true, 'maxlength' => 80]) ?>
  </form>
</div>
<?= checkout_barra('form-etapa', 'Continuar', $resumo['subtotal'], 'Frete na próxima etapa') ?>
<?php pagina_fim(['scripts' => ['validacao.js']]); ?>
