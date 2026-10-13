<?php
/**
 * Adicionar ou editar endereço (a partir da tela 25 · Endereços)
 */
require __DIR__ . '/../includes/bootstrap.php';

$cliente = exigir_login();
$cid = (int) $cliente['id'];

$id = (int) ($_GET['id'] ?? 0);
$existente = $id ? endereco_do_cliente($cid, $id) : null;
if ($id && !$existente) {
    redirecionar('conta/enderecos.php');
}

$dados = $existente ?? [
    'apelido' => '', 'destinatario' => $cliente['nome'], 'cep' => '', 'logradouro' => '', 'numero' => '',
    'complemento' => '', 'bairro' => '', 'cidade' => '', 'principal' => 0,
];
$dados['complemento'] ??= '';
$dados['apelido'] ??= '';
$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    [$novos, $erros] = validar_endereco($_POST);
    $novos['apelido'] = mb_substr(trim((string) ($_POST['apelido'] ?? '')), 0, 40);
    $principal = !empty($_POST['principal']);
    $dados = $novos + ['principal' => $principal];
    if (!$erros) {
        salvar_endereco($cid, $novos, $existente ? $id : null, $principal);
        aviso_definir($existente ? 'Endereço atualizado' : 'Endereço salvo');
        redirecionar('conta/enderecos.php');
    }
}

$titulo = $existente ? 'Editar endereço' : 'Novo endereço';

pagina_inicio([
    'titulo' => $titulo,
    'aba'    => 'conta',
    'nav'    => ['titulo' => $titulo, 'voltar' => url('conta/enderecos.php')],
]);
?>
<div class="conteudo conteudo--leitura pilha-xl" style="padding-top: var(--spacing-lg)">
  <form class="pilha" method="post" data-validar novalidate>
    <?= csrf_campo() ?>
    <div class="pilha-sm">
      <span class="campo__rotulo" id="rotulo-apelido">Nome do endereço</span>
      <div class="linha" style="flex-wrap: wrap" role="radiogroup" aria-labelledby="rotulo-apelido">
        <?php foreach (['Casa', 'Trabalho', 'Outro'] as $sugestao): ?>
          <input type="radio" class="visualmente-oculto" name="apelido" id="apelido-<?= e($sugestao) ?>" value="<?= e($sugestao) ?>" <?= $dados['apelido'] === $sugestao ? 'checked' : '' ?>>
          <label class="chip" for="apelido-<?= e($sugestao) ?>"><?= e($sugestao) ?></label>
        <?php endforeach; ?>
      </div>
    </div>
    <?= campo_texto('destinatario', 'Quem vai receber', $dados['destinatario'], $erros['destinatario'] ?? null,
        ['autocomplete' => 'name', 'required' => true, 'maxlength' => 120]) ?>
    <?= campo_texto('cep', 'CEP', $dados['cep'] ? formatar_cep($dados['cep']) : '', $erros['cep'] ?? null,
        ['inputmode' => 'numeric', 'autocomplete' => 'postal-code', 'required' => true, 'data-validar' => 'cep-rs', 'data-mascara' => 'cep', 'placeholder' => '93000-000'], 'Só RS, por enquanto') ?>
    <?= campo_texto('logradouro', 'Rua', $dados['logradouro'], $erros['logradouro'] ?? null,
        ['autocomplete' => 'address-line1', 'required' => true, 'maxlength' => 160]) ?>
    <div class="campos-lado-a-lado">
      <?= campo_texto('numero', 'Número', $dados['numero'], $erros['numero'] ?? null, ['inputmode' => 'numeric', 'required' => true, 'maxlength' => 20]) ?>
      <?= campo_texto('complemento', 'Complemento', $dados['complemento'], $erros['complemento'] ?? null, ['autocomplete' => 'address-line2', 'maxlength' => 80], 'Opcional') ?>
    </div>
    <?= campo_texto('bairro', 'Bairro', $dados['bairro'], $erros['bairro'] ?? null, ['required' => true, 'maxlength' => 80]) ?>
    <?= campo_texto('cidade', 'Cidade', $dados['cidade'], $erros['cidade'] ?? null, ['autocomplete' => 'address-level2', 'required' => true, 'maxlength' => 80]) ?>

    <?php if (!($existente['principal'] ?? false)): ?>
      <label class="caixa linha linha--entre">
        <span class="t-callout">Usar como endereço principal</span>
        <input type="checkbox" class="interruptor" name="principal" value="1" <?= !empty($dados['principal']) ? 'checked' : '' ?>>
      </label>
    <?php endif; ?>

    <button type="submit" class="botao botao--primary botao--bloco">Salvar endereço</button>
  </form>
</div>
<?php pagina_fim(['scripts' => ['validacao.js']]); ?>
