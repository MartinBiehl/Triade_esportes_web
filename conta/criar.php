<?php
/**
 * Tela 20 · Criar conta
 * Vindo do pedido confirmado (?pedido=TR-...), os dados da compra já vêm preenchidos
 * e o pedido passa a aparecer em "Meus pedidos".
 */
require __DIR__ . '/../includes/bootstrap.php';

$voltar_bruto = (string) ($_GET['voltar'] ?? $_POST['voltar'] ?? '');
$voltar = destino_seguro($voltar_bruto);
if (cliente_logado()) {
    redirecionar($voltar);
}

$pedido = pedido_da_sessao((string) ($_GET['pedido'] ?? $_POST['pedido'] ?? ''));
$dados = [
    'nome'     => $pedido['nome'] ?? ($_SESSION['checkout']['identificacao']['nome'] ?? ''),
    'email'    => $pedido['email'] ?? ($_SESSION['checkout']['identificacao']['email'] ?? ''),
    'cpf'      => $pedido['cpf'] ?? ($_SESSION['checkout']['identificacao']['cpf'] ?? ''),
    'telefone' => so_numeros($pedido['telefone'] ?? ($_SESSION['checkout']['identificacao']['telefone'] ?? '')),
];
$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    [$dados, $erros] = validar_cadastro($_POST);
    if (!$erros) {
        $id = criar_cliente($dados);
        // o endereço da compra vira o primeiro endereço salvo
        if ($pedido && $pedido['cep']) {
            salvar_endereco($id, [
                'apelido' => 'Casa', 'destinatario' => $pedido['nome'], 'cep' => $pedido['cep'], 'logradouro' => $pedido['logradouro'],
                'numero' => $pedido['numero_end'], 'complemento' => (string) $pedido['complemento'], 'bairro' => $pedido['bairro'], 'cidade' => $pedido['cidade'],
            ]);
        }
        aviso_definir('Conta criada. Bem-vindo(a)!');
        redirecionar($pedido ? 'conta/pedido.php?numero=' . urlencode($pedido['numero']) : $voltar);
    }
}

pagina_inicio([
    'titulo' => 'Criar conta',
    'aba'    => 'conta',
    'nav'    => ['titulo' => '', 'voltar' => url('conta/entrar.php' . ($voltar_bruto !== '' ? '?voltar=' . urlencode($voltar_bruto) : ''))],
]);
?>
<div class="conteudo conteudo--leitura pilha-xl conta-acesso">
  <section class="pilha-sm">
    <h1 class="t-title-1">Criar conta</h1>
    <p class="t-subheadline texto-secundario">
      <?= $pedido
        ? 'Seus dados do pedido #' . e($pedido['numero']) . ' já estão aqui. Só falta escolher uma senha.'
        : 'Leva menos de um minuto. CPF e celular são opcionais, mas agilizam a próxima compra.' ?>
    </p>
  </section>

  <form class="pilha" method="post" data-validar novalidate>
    <?= csrf_campo() ?>
    <input type="hidden" name="voltar" value="<?= e($voltar_bruto) ?>">
    <?php if ($pedido): ?><input type="hidden" name="pedido" value="<?= e($pedido['numero']) ?>"><?php endif; ?>

    <?= campo_texto('nome', 'Nome completo', $dados['nome'], $erros['nome'] ?? null,
        ['autocomplete' => 'name', 'required' => true, 'data-validar' => 'nome-completo', 'maxlength' => 120]) ?>
    <?= campo_texto('email', 'E-mail', $dados['email'], $erros['email'] ?? null,
        ['type' => 'email', 'autocomplete' => 'email', 'inputmode' => 'email', 'required' => true, 'data-validar' => 'email', 'maxlength' => 160]) ?>
    <div class="campos-lado-a-lado">
      <?= campo_texto('cpf', 'CPF', $dados['cpf'] ? formatar_cpf($dados['cpf']) : '', $erros['cpf'] ?? null,
          ['inputmode' => 'numeric', 'data-validar' => 'cpf', 'data-mascara' => 'cpf', 'placeholder' => '000.000.000-00'], 'Opcional') ?>
      <?= campo_texto('telefone', 'Celular', $dados['telefone'] ? formatar_telefone($dados['telefone']) : '', $erros['telefone'] ?? null,
          ['type' => 'tel', 'autocomplete' => 'tel-national', 'inputmode' => 'tel', 'data-validar' => 'telefone', 'data-mascara' => 'telefone', 'placeholder' => '(51) 99999-0000'], 'Opcional') ?>
    </div>
    <?= campo_senha('senha', 'Senha', $erros['senha'] ?? null,
        ['autocomplete' => 'new-password', 'required' => true, 'data-validar' => 'senha', 'minlength' => SENHA_MINIMA], 'Pelo menos ' . SENHA_MINIMA . ' caracteres') ?>
    <?= campo_senha('senha_confirmar', 'Confirmar senha', $erros['senha_confirmar'] ?? null,
        ['autocomplete' => 'new-password', 'required' => true, 'data-igual-a' => 'senha']) ?>

    <button type="submit" class="botao botao--primary botao--bloco">Criar conta</button>
  </form>

  <p class="t-subheadline texto-secundario" style="text-align: center">
    Já tem conta? <a href="<?= e(url('conta/entrar.php' . ($voltar_bruto !== '' ? '?voltar=' . urlencode($voltar_bruto) : ''))) ?>">Entrar</a>
  </p>
</div>
<?php pagina_fim(['scripts' => ['validacao.js']]); ?>
