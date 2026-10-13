<?php
/**
 * Tela 17 · Pix aguardando pagamento
 *
 * SIMULAÇÃO: o QR code e o código "copia e cola" são de mentira.
 * O botão "Simular pagamento" faz o papel do banco avisando que o Pix caiu.
 */
require __DIR__ . '/../includes/bootstrap.php';

$pedido = pedido_da_sessao((string) ($_GET['pedido'] ?? ''));
if (!$pedido || $pedido['pagamento_tipo'] !== 'pix') {
    redirecionar('index.php');
}
if ($pedido['status'] !== 'aguardando_pagamento') {
    redirecionar('checkout/confirmado.php?pedido=' . urlencode($pedido['numero']));
}

$expira = (new DateTimeImmutable($pedido['criado_em']))->modify('+' . PIX_VALIDADE_MINUTOS . ' minutes');
$expirado = $expira < new DateTimeImmutable();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$expirado) {
    csrf_validar();
    aprovar_pagamento_simulado($pedido, 'Pagamento Pix confirmado');
    redirecionar('checkout/confirmado.php?pedido=' . urlencode($pedido['numero']));
}

$codigo = pix_copia_e_cola($pedido);

pagina_inicio([
    'titulo' => 'Pague com Pix',
    'nav'    => ['titulo' => 'Pedido #' . $pedido['numero'], 'voltar' => null],
]);
?>
<div class="conteudo conteudo--leitura pilha-xl" style="padding-top: var(--spacing-xl)">
  <?php if ($expirado): ?>
    <div class="estado-vazio">
      <div class="estado-vazio__icone estado-vazio__icone--erro"><?= icone('relogio') ?></div>
      <h1 class="t-title-3">O código Pix expirou</h1>
      <p class="t-subheadline">O pedido #<?= e($pedido['numero']) ?> ficou sem pagamento. Fale com a gente no WhatsApp para gerar um novo código.</p>
      <a class="botao botao--accent botao--md" href="https://wa.me/<?= e(CONTATO_WHATSAPP) ?>"><?= icone('whatsapp', 'icone--sm') ?> Falar no WhatsApp</a>
    </div>
  <?php else: ?>
    <section class="pilha-sm" style="text-align: center; align-items: center">
      <p class="t-label-caps texto-secundario">Total a pagar</p>
      <p class="t-price-large"><?= dinheiro((float) $pedido['total']) ?></p>
      <p class="t-subheadline texto-secundario">
        O código vence em <strong class="pix-relogio" data-expira-em="<?= $expira->getTimestamp() ?>"><?= PIX_VALIDADE_MINUTOS ?>:00</strong>
      </p>
    </section>

    <div class="pix-qr caixa">
      <?= qr_simulado_svg($pedido['numero']) ?>
    </div>

    <section class="pilha-sm">
      <label class="t-headline" for="pix-codigo">Pix copia e cola</label>
      <textarea id="pix-codigo" class="campo__entrada pix-codigo" readonly rows="3"><?= e($codigo) ?></textarea>
      <button type="button" class="botao botao--primary botao--bloco" data-copiar="pix-codigo"><?= icone('copiar', 'icone--sm') ?> Copiar código</button>
    </section>

    <ol class="pilha-sm t-subheadline texto-secundario passos-pix">
      <li>Abra o app do seu banco e escolha <strong>Pix</strong>.</li>
      <li>Escaneie o QR code ou cole o código.</li>
      <li>Confirme o pagamento. A gente avisa por e-mail na hora.</li>
    </ol>

    <form method="post" class="caixa caixa--sutil pilha-sm">
      <?= csrf_campo() ?>
      <p class="t-footnote"><strong>Simulação para o trabalho.</strong> Este QR code não é real. Use o botão abaixo para fazer de conta que o banco confirmou o pagamento.</p>
      <button type="submit" class="botao botao--accent botao--md"><?= icone('check-circulo', 'icone--sm') ?> Simular pagamento aprovado</button>
    </form>
  <?php endif; ?>
</div>
<?php pagina_fim(['scripts' => ['checkout.js']]); ?>
