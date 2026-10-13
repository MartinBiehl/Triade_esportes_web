<?php
/**
 * Tela 12 · Identificação (compra sem conta)
 */
require __DIR__ . '/../includes/bootstrap.php';
checkout_exigir('identificacao');

$c = &checkout();
$dados = $c['identificacao'] ?? ['nome' => '', 'email' => '', 'cpf' => '', 'telefone' => ''];
$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    [$dados, $erros] = validar_identificacao($_POST);
    if (!$erros) {
        $c['identificacao'] = $dados;
        redirecionar('checkout/endereco.php');
    }
}

$resumo = sacola_resumo(sacola_itens());

pagina_inicio([
    'titulo' => 'Identificação',
    'nav'    => ['titulo' => 'Identificação', 'voltar' => url('sacola.php')],
]);
?>
<div class="conteudo conteudo--leitura pilha-xl" style="padding-top: var(--spacing-lg)">
  <section class="pilha-sm">
    <h1 class="t-title-2">Quem está comprando?</h1>
    <p class="t-subheadline texto-secundario">
      Não precisa criar conta. Usamos estes dados para a nota fiscal e para avisar sobre a entrega.
    </p>
  </section>

  <form id="form-etapa" class="pilha" method="post" data-validar novalidate>
    <?= csrf_campo() ?>
    <?= campo_texto('nome', 'Nome completo', $dados['nome'], $erros['nome'] ?? null,
        ['autocomplete' => 'name', 'required' => true, 'data-validar' => 'nome-completo', 'maxlength' => 120]) ?>
    <?= campo_texto('email', 'E-mail', $dados['email'], $erros['email'] ?? null,
        ['type' => 'email', 'autocomplete' => 'email', 'inputmode' => 'email', 'required' => true, 'data-validar' => 'email', 'maxlength' => 160],
        'Enviamos a confirmação do pedido para ele.') ?>
    <div class="campos-lado-a-lado">
      <?= campo_texto('cpf', 'CPF', $dados['cpf'] ? formatar_cpf($dados['cpf']) : '', $erros['cpf'] ?? null,
          ['inputmode' => 'numeric', 'required' => true, 'data-validar' => 'cpf', 'data-mascara' => 'cpf', 'placeholder' => '000.000.000-00']) ?>
      <?= campo_texto('telefone', 'Celular', $dados['telefone'] ? formatar_telefone($dados['telefone']) : '', $erros['telefone'] ?? null,
          ['type' => 'tel', 'autocomplete' => 'tel-national', 'inputmode' => 'tel', 'required' => true, 'data-validar' => 'telefone', 'data-mascara' => 'telefone', 'placeholder' => '(51) 99999-0000']) ?>
    </div>
  </form>

  <p class="t-subheadline texto-secundario" style="text-align: center">
    Já tem conta? <a href="<?= e(url('conta/entrar.php?voltar=checkout')) ?>">Entrar</a>
  </p>
</div>
<?= checkout_barra('form-etapa', 'Continuar', $resumo['subtotal'], $resumo['quantidade'] . ($resumo['quantidade'] == 1 ? ' item' : ' itens')) ?>
<?php pagina_fim(['scripts' => ['validacao.js']]); ?>
