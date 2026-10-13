<?php
/**
 * Painel da loja · Entrar
 * Login separado da conta de cliente (tabela administradores).
 */
require __DIR__ . '/../includes/admin.php';

$voltar = (string) ($_GET['voltar'] ?? $_POST['voltar'] ?? '');
if (admin_logado()) {
    redirecionar(admin_destino($voltar));
}

$email = '';
$erro = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $email = trim((string) ($_POST['email'] ?? ''));
    $erro = admin_entrar($email, (string) ($_POST['senha'] ?? ''));
    if ($erro === null) {
        redirecionar(admin_destino($voltar));
    }
}

pagina_inicio([
    'titulo' => 'Painel da loja',
    'robots' => 'noindex, nofollow',
    'nav'    => ['titulo' => '', 'voltar' => url('index.php')],
]);
?>
<div class="conteudo conteudo--leitura pilha-xl conta-acesso">
  <section class="pilha-sm">
    <span class="admin-marca"><?= icone('loja', 'icone--xs') ?> Painel da loja</span>
    <h1 class="t-title-1">Entrar no painel</h1>
    <p class="t-subheadline texto-secundario">Área da equipe da <?= e(LOJA_NOME) ?>: pedidos, produtos e estoque.</p>
  </section>

  <form class="pilha" method="post" data-validar novalidate>
    <?= csrf_campo() ?>
    <input type="hidden" name="voltar" value="<?= e($voltar) ?>">
    <?php if ($erro): ?>
      <p class="caixa-erro t-subheadline" role="alert"><?= icone('alerta', 'icone--sm') ?> <?= e($erro) ?></p>
    <?php endif; ?>
    <?= campo_texto('email', 'E-mail', $email, null,
        ['type' => 'email', 'autocomplete' => 'username', 'inputmode' => 'email', 'required' => true, 'data-validar' => 'email', 'maxlength' => 160, 'autofocus' => $email === '']) ?>
    <?= campo_senha('senha', 'Senha', null, ['autocomplete' => 'current-password', 'required' => true, 'data-obrigatorio' => 'Digite a senha.']) ?>
    <button type="submit" class="botao botao--primary botao--bloco">Entrar</button>
  </form>

  <p class="t-footnote texto-terciario" style="text-align: center">
    <?= icone('cadeado', 'icone--xs') ?> É cliente? <a href="<?= e(url('conta/entrar.php')) ?>">Entre na sua conta</a>.
  </p>
</div>
<?php pagina_fim(['scripts' => ['validacao.js']]); ?>
