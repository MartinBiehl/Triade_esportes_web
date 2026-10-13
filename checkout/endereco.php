<?php
/**
 * Tela 13 · Endereço
 */
require __DIR__ . '/../includes/bootstrap.php';
checkout_exigir('endereco');

$c = &checkout();
$dados = $c['endereco'] ?? [
    'destinatario' => $c['identificacao']['nome'] ?? '', 'cep' => cep_lembrado(), 'logradouro' => '', 'numero' => '',
    'complemento' => '', 'bairro' => '', 'cidade' => '', 'uf' => 'RS',
];
$erros = [];

// Com login: escolhe um endereço salvo ou digita um novo (que pode ser salvo na conta)
$cliente   = cliente_logado();
$salvos    = $cliente ? enderecos_do_cliente((int) $cliente['id']) : [];
$escolhido = $c['endereco_id'] ?? (isset($c['endereco']) ? 'novo' : ($salvos[0]['id'] ?? 'novo'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $escolhido = $_POST['endereco_salvo'] ?? 'novo';
    $salvo = ($cliente && $escolhido !== 'novo') ? endereco_do_cliente((int) $cliente['id'], (int) $escolhido) : null;

    if ($salvo) {
        $c['endereco'] = [
            'destinatario' => $salvo['destinatario'], 'cep' => $salvo['cep'], 'logradouro' => $salvo['logradouro'], 'numero' => $salvo['numero'],
            'complemento' => (string) $salvo['complemento'], 'bairro' => $salvo['bairro'], 'cidade' => $salvo['cidade'], 'uf' => 'RS',
        ];
        $c['endereco_id'] = (int) $salvo['id'];
        redirecionar('checkout/entrega.php');
    }

    $escolhido = 'novo';
    [$dados, $erros] = validar_endereco($_POST);
    if (!$erros) {
        $c['endereco'] = $dados;
        $c['endereco_id'] = ($cliente && !empty($_POST['salvar_endereco'])) ? salvar_endereco((int) $cliente['id'], $dados) : 'novo';
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

  <form id="form-etapa" class="pilha" method="post" data-validar data-enderecos novalidate>
    <?= csrf_campo() ?>
    <?php if ($salvos): ?>
      <div class="pilha-sm" role="radiogroup" aria-label="Endereços salvos">
        <?php foreach ($salvos as $end): ?>
          <?php [$rua, $cidade] = endereco_texto($end); ?>
          <input type="radio" class="visualmente-oculto" name="endereco_salvo" id="endereco-<?= (int) $end['id'] ?>" value="<?= (int) $end['id'] ?>" <?= (string) $escolhido === (string) $end['id'] ? 'checked' : '' ?>>
          <label class="opcao-card" for="endereco-<?= (int) $end['id'] ?>">
            <span class="opcao-card__radio"></span>
            <span class="opcao-card__textos">
              <span class="opcao-card__titulo"><?= e($end['apelido'] ?: 'Endereço') ?></span>
              <span class="opcao-card__descricao"><?= e($rua) ?></span>
              <span class="opcao-card__descricao"><?= e($cidade) ?></span>
            </span>
          </label>
        <?php endforeach; ?>
        <input type="radio" class="visualmente-oculto" name="endereco_salvo" id="endereco-novo" value="novo" <?= $escolhido === 'novo' ? 'checked' : '' ?>>
        <label class="opcao-card" for="endereco-novo">
          <span class="opcao-card__radio"></span>
          <span class="opcao-card__textos"><span class="opcao-card__titulo">Outro endereço</span></span>
          <?= icone('mais', 'icone--sm') ?>
        </label>
      </div>
    <?php else: ?>
      <input type="hidden" name="endereco_salvo" value="novo">
    <?php endif; ?>

    <div class="pilha" id="novo-endereco">
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
    <?php if ($cliente): ?>
      <label class="caixa linha linha--entre">
        <span class="t-callout">Salvar na minha conta</span>
        <input type="checkbox" class="interruptor" name="salvar_endereco" value="1" checked>
      </label>
    <?php endif; ?>
    </div>
  </form>
</div>
<?= checkout_barra('form-etapa', 'Continuar', $resumo['subtotal'], 'Frete na próxima etapa') ?>
<?php pagina_fim(['scripts' => ['validacao.js', 'checkout.js']]); ?>
