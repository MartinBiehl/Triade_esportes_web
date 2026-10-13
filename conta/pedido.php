<?php
/**
 * Tela 24 · Detalhe do pedido com rastreio
 * Abre para o dono do pedido (logado) ou para quem fez o pedido nesta sessão.
 */
require __DIR__ . '/../includes/bootstrap.php';

$numero = (string) ($_GET['numero'] ?? '');
$pedido = pedido_da_sessao($numero);
if (!$pedido) {
    if (!cliente_logado()) {
        redirecionar('conta/entrar.php?voltar=' . urlencode('conta/pedido.php?numero=' . $numero));
    }
    aviso_definir('Pedido não encontrado', 'erro');
    redirecionar('conta/pedidos.php');
}

$itens = consulta(
    'SELECT i.*, p.midia, (SELECT arquivo FROM produto_imagens WHERE produto_id = p.id ORDER BY ordem LIMIT 1) AS imagem
       FROM pedido_itens i LEFT JOIN produtos p ON p.id = i.produto_id WHERE i.pedido_id = ? ORDER BY i.id',
    [$pedido['id']]
)->fetchAll();

$linha    = linha_do_tempo($pedido);
$retirada = $pedido['entrega_tipo'] === 'retirada';
$aguardando = $pedido['status'] === 'aguardando_pagamento';
$encerrado  = in_array($pedido['status'], ['entregue', 'cancelado', 'pagamento_recusado'], true);
$nomes_entrega = ['pac' => 'PAC', 'sedex' => 'SEDEX', 'transportadora' => 'Transportadora', 'retirada' => 'Retirada em São Leopoldo'];

$texto_pagamento = match ($pedido['pagamento_tipo']) {
    'pix'    => 'Pix · ' . (int) (PIX_DESCONTO * 100) . '% de desconto',
    'cartao' => 'Cartão de crédito · ' . ($pedido['parcelas'] > 1 ? $pedido['parcelas'] . 'x de ' . dinheiro(round($pedido['total'] / $pedido['parcelas'], 2)) : 'à vista'),
    'boleto' => 'Boleto bancário',
};

$mensagem_whatsapp = 'Olá! Tenho uma dúvida sobre o pedido #' . $pedido['numero'];

pagina_inicio([
    'titulo' => 'Pedido #' . $pedido['numero'],
    'aba'    => 'conta',
    'nav'    => ['titulo' => 'Pedido #' . $pedido['numero'], 'voltar' => url(cliente_logado() ? 'conta/pedidos.php' : 'index.php')],
]);
?>
<div class="conteudo conteudo--leitura pilha-xl" style="padding-top: var(--spacing-lg)">

  <section class="pilha-sm">
    <?= selo_status($pedido['status']) ?>
    <h1 class="t-title-2">
      <?php if ($aguardando): ?>
        Esperando o pagamento
      <?php elseif ($pedido['status'] === 'entregue'): ?>
        <?= $retirada ? 'Pedido retirado' : 'Pedido entregue' ?>
      <?php elseif ($pedido['status'] === 'enviado'): ?>
        Chega <?= e(mb_strtolower(mb_substr($pedido['entrega_prazo'], 0, 1)) . mb_substr($pedido['entrega_prazo'], 1)) ?>
      <?php elseif ($pedido['status'] === 'pronto_retirada'): ?>
        Pronto para retirar
      <?php elseif ($encerrado): ?>
        Pedido encerrado
      <?php else: ?>
        Previsão: <?= e(mb_strtolower(mb_substr($pedido['entrega_prazo'], 0, 1)) . mb_substr($pedido['entrega_prazo'], 1)) ?>
      <?php endif; ?>
    </h1>
    <p class="t-footnote texto-secundario">Feito em <?= e(data_longa($pedido['criado_em'])) ?></p>
  </section>

  <?php if ($aguardando && $pedido['pagamento_tipo'] === 'pix'): ?>
    <a class="botao botao--primary botao--bloco" href="<?= e(url('checkout/pix.php?pedido=' . urlencode($pedido['numero']))) ?>"><?= icone('pix', 'icone--sm') ?> Pagar com Pix</a>
  <?php endif; ?>

  <!-- Rastreio -->
  <section class="pilha-sm">
    <h2 class="t-headline">Acompanhamento</h2>
    <?php if ($pedido['codigo_rastreio']): ?>
      <div class="caixa linha linha--entre">
        <div>
          <p class="t-footnote texto-secundario">Código de rastreio · <?= e($nomes_entrega[$pedido['entrega_tipo']]) ?></p>
          <p class="t-callout pix-codigo" id="codigo-rastreio"><?= e($pedido['codigo_rastreio']) ?></p>
        </div>
        <button type="button" class="botao-icone botao-icone--tint botao-icone--sm" data-copiar="codigo-rastreio" data-copiado="Código copiado" aria-label="Copiar código de rastreio"><?= icone('copiar', 'icone--sm') ?></button>
      </div>
    <?php endif; ?>
    <ol class="rastreio caixa" aria-label="Etapas do pedido">
      <?php foreach ($linha as $i => $passo): ?>
        <?php $atual = $passo['feito'] && empty($linha[$i + 1]['feito']); ?>
        <li class="rastreio__passo<?= $passo['feito'] ? ' rastreio__passo--feito' : '' ?><?= $atual ? ' rastreio__passo--atual' : '' ?>"<?= $atual ? ' aria-current="step"' : '' ?>>
          <span class="rastreio__ponto" aria-hidden="true"><?= $passo['feito'] ? icone('check', 'icone--xs') : '' ?></span>
          <span class="rastreio__textos">
            <span class="t-callout"><?= e($passo['rotulo']) ?></span>
            <?php if ($passo['quando']): ?>
              <span class="t-footnote texto-secundario"><?= e($passo['descricao']) ?> · <?= e(data_hora_curta($passo['quando'])) ?></span>
            <?php endif; ?>
          </span>
        </li>
      <?php endforeach; ?>
    </ol>
    <?php if (in_array($pedido['status'], ['pagamento_recusado', 'cancelado'], true)): ?>
      <p class="t-footnote texto-erro"><?= $pedido['status'] === 'cancelado' ? 'Este pedido foi cancelado.' : 'O pagamento não foi aprovado.' ?> Fale com a gente se precisar de ajuda.</p>
    <?php endif; ?>
  </section>

  <!-- Itens -->
  <section class="pilha-sm">
    <h2 class="t-headline"><?= count($itens) ?> <?= count($itens) === 1 ? 'produto' : 'produtos' ?></h2>
    <ul class="lista revisao-itens" role="list">
      <?php foreach ($itens as $item): ?>
        <li>
          <?php $conteudo = midia_produto(['midia' => $item['midia'] ?? 'camisa', 'imagem' => $item['imagem']], 'revisao-itens__foto')
            . '<span class="linha-lista__rotulo"><span class="t-callout">' . e($item['nome_produto']) . '</span><br>'
            . '<span class="t-footnote texto-secundario">Tamanho ' . e($item['tamanho']) . ' · ' . (int) $item['quantidade'] . ' un.</span></span>'
            . '<span class="linha-lista__valor">' . dinheiro($item['quantidade'] * (float) $item['preco_unitario']) . '</span>'; ?>
          <?php if ($item['produto_id']): ?>
            <a class="linha-lista" href="<?= e(url('produto.php?id=' . (int) $item['produto_id'])) ?>"><?= $conteudo ?></a>
          <?php else: ?>
            <div class="linha-lista"><?= $conteudo ?></div>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
  </section>

  <!-- Entrega e pagamento -->
  <section class="pilha-sm">
    <h2 class="t-headline">Entrega e pagamento</h2>
    <ul class="lista" role="list">
      <li>
        <div class="linha-lista">
          <?= icone($retirada ? 'loja' : 'local', 'linha-lista__icone') ?>
          <span class="linha-lista__rotulo">
            <?= e($nomes_entrega[$pedido['entrega_tipo']]) ?><br>
            <span class="t-footnote texto-secundario">
              <?php if ($retirada): ?>
                <?= e(DEPOSITO_ENDERECO) ?>
              <?php else: ?>
                <?php [$rua, $cidade] = endereco_texto($pedido); ?>
                <?= e($pedido['nome']) ?> · <?= e($rua) ?><br><?= e($cidade) ?>
              <?php endif; ?>
            </span>
          </span>
        </div>
      </li>
      <li>
        <div class="linha-lista">
          <?= icone($pedido['pagamento_tipo'] === 'pix' ? 'pix' : ($pedido['pagamento_tipo'] === 'cartao' ? 'cartao' : 'boleto'), 'linha-lista__icone') ?>
          <span class="linha-lista__rotulo"><?= e($texto_pagamento) ?></span>
        </div>
      </li>
    </ul>
  </section>

  <dl class="caixa resumo-valores" style="margin: 0">
    <div><dt>Produtos</dt><dd><?= dinheiro((float) $pedido['subtotal']) ?></dd></div>
    <div><dt>Frete</dt><dd class="<?= $pedido['frete'] == 0 ? 'texto-sucesso' : '' ?>"><?= $pedido['frete'] == 0 ? 'Grátis' : dinheiro((float) $pedido['frete']) ?></dd></div>
    <?php if ($pedido['desconto'] > 0): ?>
      <div><dt>Desconto Pix</dt><dd class="texto-sucesso">− <?= dinheiro((float) $pedido['desconto']) ?></dd></div>
    <?php endif; ?>
    <div class="resumo-valores__total"><dt>Total</dt><dd><?= dinheiro((float) $pedido['total']) ?></dd></div>
  </dl>

  <!-- Ajuda -->
  <section class="pilha-sm">
    <h2 class="t-headline">Precisa de ajuda?</h2>
    <ul class="lista" role="list">
      <?php if ($pedido['status'] === 'entregue'): ?>
        <li><?= linha_lista('Trocar ou devolver', 'trocas', url('trocas.php'), 'até ' . TROCA_PRAZO_DIAS . ' dias') ?></li>
      <?php endif; ?>
      <li><?= linha_lista('Falar sobre este pedido', 'whatsapp', 'https://wa.me/' . CONTATO_WHATSAPP . '?text=' . rawurlencode($mensagem_whatsapp)) ?></li>
    </ul>
  </section>
</div>
<?php pagina_fim(['scripts' => ['checkout.js']]); ?>
