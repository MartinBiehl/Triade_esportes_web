<?php
/**
 * Tela 22 · Conta
 * Sem login: convite para entrar ou criar conta. Com login: atalhos da conta.
 */
require __DIR__ . '/../includes/bootstrap.php';

$cliente = cliente_logado();

if ($cliente) {
    $contagem = consulta(
        'SELECT (SELECT COUNT(*) FROM pedidos   WHERE cliente_id = ?) AS pedidos,
                (SELECT COUNT(*) FROM enderecos WHERE cliente_id = ?) AS enderecos,
                (SELECT COUNT(*) FROM favoritos WHERE cliente_id = ?) AS favoritos,
                (SELECT COUNT(*) FROM pedidos   WHERE cliente_id = ? AND status IN (\'aguardando_pagamento\', \'pago\', \'em_separacao\', \'enviado\', \'pronto_retirada\')) AS andamento',
        array_fill(0, 4, $cliente['id'])
    )->fetch();
}

pagina_inicio(['titulo' => 'Conta', 'aba' => 'conta']);
?>
<?= nav_large('Conta') ?>
<div class="conteudo pilha-xl">

  <?php if ($cliente): ?>
    <section class="perfil">
      <div class="perfil__avatar" aria-hidden="true"><?= e(iniciais($cliente['nome'])) ?></div>
      <div class="perfil__textos">
        <h2 class="t-title-3"><?= e($cliente['nome']) ?></h2>
        <p class="t-footnote texto-secundario"><?= e($cliente['email']) ?></p>
      </div>
    </section>

    <?php if ((int) $contagem['andamento'] > 0): ?>
      <a class="caixa caixa--sutil linha linha--entre perfil__andamento" href="<?= e(url('conta/pedidos.php')) ?>">
        <span class="linha"><?= icone('caminhao') ?>
          <span class="t-callout"><?= (int) $contagem['andamento'] === 1 ? '1 pedido em andamento' : (int) $contagem['andamento'] . ' pedidos em andamento' ?></span>
        </span>
        <?= icone('seta-direita', 'icone--sm') ?>
      </a>
    <?php endif; ?>

    <ul class="lista" role="list">
      <li><?= linha_lista('Meus pedidos', 'pacote', url('conta/pedidos.php'), $contagem['pedidos'] ? (string) $contagem['pedidos'] : null) ?></li>
      <li><?= linha_lista('Endereços', 'local', url('conta/enderecos.php'), $contagem['enderecos'] ? (string) $contagem['enderecos'] : null) ?></li>
      <li><?= linha_lista('Favoritos', 'coracao', url('conta/favoritos.php'), $contagem['favoritos'] ? (string) $contagem['favoritos'] : null) ?></li>
    </ul>
  <?php else: ?>
    <section class="caixa pilha conta-convite">
      <div class="perfil__avatar perfil__avatar--vazio" aria-hidden="true"><?= icone('conta') ?></div>
      <div class="pilha-sm">
        <h2 class="t-title-3">Entre na sua conta</h2>
        <p class="t-subheadline texto-secundario">Acompanhe pedidos, salve endereços e favoritos. Para comprar, não precisa de conta.</p>
      </div>
      <a class="botao botao--primary botao--bloco" href="<?= e(url('conta/entrar.php')) ?>">Entrar</a>
      <a class="botao botao--glass botao--bloco" href="<?= e(url('conta/criar.php')) ?>">Criar conta</a>
    </section>
  <?php endif; ?>

  <section class="pilha-sm">
    <h2 class="t-label-caps texto-secundario">Ajuda</h2>
    <ul class="lista" role="list">
      <li><?= linha_lista('Trocas e devoluções', 'trocas', url('trocas.php')) ?></li>
      <li><?= linha_lista('Sobre e contato', 'info', url('sobre.php')) ?></li>
      <li><?= linha_lista('Fale no WhatsApp', 'whatsapp', 'https://wa.me/' . CONTATO_WHATSAPP) ?></li>
    </ul>
  </section>

  <?php if ($cliente): ?>
    <form method="post" action="<?= e(url('conta/sair.php')) ?>">
      <?= csrf_campo() ?>
      <button type="submit" class="botao botao--ghost botao--bloco texto-erro"><?= icone('sair', 'icone--sm') ?> Sair da conta</button>
    </form>
  <?php endif; ?>
</div>
<?php pagina_fim(); ?>
