<?php
/**
 * Tela 21 · Recuperar senha
 *
 * SIMULAÇÃO: o site não envia e-mail de verdade. O "e-mail" com o link de troca
 * aparece na própria tela, como combinado no plano.
 */
require __DIR__ . '/../includes/bootstrap.php';

$email = '';
$erro = null;
$enviado = false;
$codigo = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'E-mail inválido.';
    } else {
        $codigo = criar_recuperacao($email);
        $enviado = true;
    }
}

pagina_inicio([
    'titulo' => 'Recuperar senha',
    'aba'    => 'conta',
    'nav'    => ['titulo' => '', 'voltar' => url('conta/entrar.php')],
]);
?>
<div class="conteudo conteudo--leitura pilha-xl conta-acesso">
  <?php if (!$enviado): ?>
    <section class="pilha-sm">
      <h1 class="t-title-1">Esqueceu a senha?</h1>
      <p class="t-subheadline texto-secundario">Digite o e-mail da sua conta. Vamos mandar um link para você criar uma senha nova.</p>
    </section>

    <form class="pilha" method="post" data-validar novalidate>
      <?= csrf_campo() ?>
      <?= campo_texto('email', 'E-mail', $email, $erro,
          ['type' => 'email', 'autocomplete' => 'email', 'inputmode' => 'email', 'required' => true, 'data-validar' => 'email', 'maxlength' => 160, 'autofocus' => true]) ?>
      <button type="submit" class="botao botao--primary botao--bloco">Enviar link</button>
    </form>
  <?php else: ?>
    <section class="estado-vazio" style="padding-block: var(--spacing-xl) 0">
      <div class="estado-vazio__icone"><?= icone('email') ?></div>
      <h1 class="t-title-2">Confira seu e-mail</h1>
      <p class="t-subheadline">Se <strong><?= e($email) ?></strong> tiver conta na Triade, o link chega em instantes. Ele vale por <?= RECUPERACAO_MINUTOS ?> minutos.</p>
    </section>

    <!-- Simulação do e-mail que seria enviado -->
    <section class="email-simulado pilha-sm" aria-label="Simulação do e-mail">
      <p class="t-label-caps texto-secundario">Simulação de envio de e-mail</p>
      <?php if ($codigo): ?>
        <div class="email-simulado__corpo pilha-sm">
          <p class="t-footnote texto-secundario">De: <?= e(LOJA_NOME) ?> · Para: <?= e($email) ?></p>
          <p class="t-headline">Crie uma senha nova</p>
          <p class="t-subheadline texto-secundario">Recebemos um pedido para trocar a senha da sua conta. Se não foi você, ignore este e-mail.</p>
          <a class="botao botao--primary botao--md" href="<?= e(url('conta/nova-senha.php?codigo=' . $codigo)) ?>">Criar senha nova</a>
        </div>
      <?php else: ?>
        <p class="t-footnote texto-secundario">Este e-mail não tem conta, então nenhuma mensagem seria enviada. A tela acima é igual nos dois casos, para ninguém descobrir quais e-mails têm conta.</p>
      <?php endif; ?>
    </section>

    <a class="botao botao--ghost botao--bloco" href="<?= e(url('conta/entrar.php')) ?>">Voltar para Entrar</a>
  <?php endif; ?>
</div>
<?php pagina_fim(['scripts' => ['validacao.js']]); ?>
