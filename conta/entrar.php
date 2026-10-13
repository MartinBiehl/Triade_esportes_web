<?php
/**
 * Tela 19 · Entrar
 */
require __DIR__ . '/../includes/bootstrap.php';

$voltar = destino_seguro($_GET['voltar'] ?? $_POST['voltar'] ?? null);
if (cliente_logado()) {
    redirecionar($voltar);
}

$email = '';
$erro = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $email = trim((string) ($_POST['email'] ?? ''));
    $erro = entrar($email, (string) ($_POST['senha'] ?? ''));
    if ($erro === null) {
        aviso_definir('Olá, ' . explode(' ', cliente_logado()['nome'] ?? '')[0] . '!');
        redirecionar($voltar);
    }
}

$parametro_voltar = isset($_GET['voltar']) || isset($_POST['voltar']) ? '?voltar=' . urlencode((string) ($_GET['voltar'] ?? $_POST['voltar'])) : '';

pagina_inicio([
    'titulo' => 'Entrar',
    'aba'    => 'conta',
    'nav'    => ['titulo' => '', 'voltar' => url(str_starts_with($voltar, 'checkout/') ? 'checkout/identificacao.php' : 'conta/')],
]);
?>
<div class="conteudo conteudo--leitura pilha-xl conta-acesso">
  <section class="pilha-sm">
    <h1 class="t-title-1">Entrar</h1>
    <p class="t-subheadline texto-secundario">Acompanhe seus pedidos, salve endereços e guarde seus favoritos.</p>
  </section>

  <form class="pilha" method="post" data-validar novalidate>
    <?= csrf_campo() ?>
    <input type="hidden" name="voltar" value="<?= e($_GET['voltar'] ?? $_POST['voltar'] ?? '') ?>">
    <?php if ($erro): ?>
      <p class="caixa-erro t-subheadline" role="alert"><?= icone('alerta', 'icone--sm') ?> <?= e($erro) ?></p>
    <?php endif; ?>
    <?= campo_texto('email', 'E-mail', $email, null,
        ['type' => 'email', 'autocomplete' => 'email', 'inputmode' => 'email', 'required' => true, 'data-validar' => 'email', 'maxlength' => 160, 'autofocus' => $email === '']) ?>
    <?= campo_senha('senha', 'Senha', null, ['autocomplete' => 'current-password', 'required' => true, 'data-obrigatorio' => 'Digite sua senha.']) ?>
    <a class="t-subheadline link-direita" href="<?= e(url('conta/recuperar.php')) ?>">Esqueci minha senha</a>
    <button type="submit" class="botao botao--primary botao--bloco">Entrar</button>
  </form>

  <div class="separador-texto"><span>ou</span></div>

  <a class="botao botao--glass botao--bloco" href="<?= e(url('conta/criar.php' . $parametro_voltar)) ?>">Criar conta</a>

  <?php if (str_starts_with($voltar, 'checkout/')): ?>
    <a class="botao botao--ghost botao--bloco" href="<?= e(url('checkout/identificacao.php')) ?>">Continuar sem conta</a>
  <?php endif; ?>

  <p class="t-footnote texto-terciario" style="text-align: center">
    <?= icone('cadeado', 'icone--xs') ?> Não precisa de conta para comprar.
  </p>
</div>
<?php pagina_fim(['scripts' => ['validacao.js']]); ?>
