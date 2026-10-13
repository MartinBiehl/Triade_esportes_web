<?php
/**
 * Tela 30 · Pagamento recusado
 * A sacola continua igual; o cliente pode tentar outro cartão ou pagar com Pix.
 */
require __DIR__ . '/../includes/bootstrap.php';

pagina_inicio([
    'titulo' => 'Pagamento recusado',
    'nav'    => ['titulo' => 'Pagamento', 'voltar' => url('checkout/pagamento.php')],
]);
?>
<div class="conteudo conteudo--leitura">
  <div class="estado-vazio">
    <div class="estado-vazio__icone estado-vazio__icone--erro"><?= icone('cartao') ?></div>
    <h1 class="t-title-2">Pagamento não aprovado</h1>
    <p class="t-subheadline">O banco recusou o cartão. Nada foi cobrado e sua sacola continua salva.</p>
    <p class="t-footnote texto-terciario">Isso costuma acontecer por limite, dados digitados errado ou bloqueio de segurança do banco.</p>
    <div class="pilha-sm" style="width: 100%; max-width: 320px; margin-top: var(--spacing-lg)">
      <a class="botao botao--primary botao--bloco" href="<?= e(url('checkout/pagamento.php')) ?>">Tentar outro cartão</a>
      <a class="botao botao--glass botao--bloco" href="<?= e(url('checkout/pagamento.php')) ?>"><?= icone('pix', 'icone--sm') ?> Pagar com Pix (<?= (int) (PIX_DESCONTO * 100) ?>% off)</a>
    </div>
  </div>
</div>
<?php pagina_fim(); ?>
