<?php
/**
 * Nova senha (link do e-mail de recuperação, tela 21)
 */
require __DIR__ . '/../includes/bootstrap.php';

$codigo = (string) ($_GET['codigo'] ?? $_POST['codigo'] ?? '');
$recuperacao = recuperacao_valida($codigo);
$erros = [];

if ($recuperacao && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $erros = validar_senha((string) ($_POST['senha'] ?? ''), (string) ($_POST['senha_confirmar'] ?? ''));
    if (!$erros) {
        trocar_senha($recuperacao, (string) $_POST['senha']);
        logar((int) $recuperacao['cliente_id']);
        aviso_definir('Senha alterada');
        redirecionar('conta/');
    }
}

pagina_inicio([
    'titulo' => 'Nova senha',
    'aba'    => 'conta',
    'nav'    => ['titulo' => '', 'voltar' => url('conta/entrar.php')],
]);
?>
<div class="conteudo conteudo--leitura pilha-xl conta-acesso">
  <?php if (!$recuperacao): ?>
    <section class="estado-vazio">
      <div class="estado-vazio__icone estado-vazio__icone--erro"><?= icone('relogio') ?></div>
      <h1 class="t-title-2">Link expirado</h1>
      <p class="t-subheadline">Este link já foi usado ou passou de <?= RECUPERACAO_MINUTOS ?> minutos. Peça um novo.</p>
      <a class="botao botao--primary botao--md" href="<?= e(url('conta/recuperar.php')) ?>">Pedir novo link</a>
    </section>
  <?php else: ?>
    <section class="pilha-sm">
      <h1 class="t-title-1">Nova senha</h1>
      <p class="t-subheadline texto-secundario">Para a conta <?= e($recuperacao['email']) ?>.</p>
    </section>
    <form class="pilha" method="post" data-validar novalidate>
      <?= csrf_campo() ?>
      <input type="hidden" name="codigo" value="<?= e($codigo) ?>">
      <?= campo_senha('senha', 'Nova senha', $erros['senha'] ?? null,
          ['autocomplete' => 'new-password', 'required' => true, 'data-validar' => 'senha', 'minlength' => SENHA_MINIMA, 'autofocus' => true], 'Pelo menos ' . SENHA_MINIMA . ' caracteres') ?>
      <?= campo_senha('senha_confirmar', 'Confirmar senha', $erros['senha_confirmar'] ?? null,
          ['autocomplete' => 'new-password', 'required' => true, 'data-igual-a' => 'senha']) ?>
      <button type="submit" class="botao botao--primary botao--bloco">Salvar e entrar</button>
    </form>
  <?php endif; ?>
</div>
<?php pagina_fim(['scripts' => ['validacao.js']]); ?>
