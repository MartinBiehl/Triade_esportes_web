<?php
/**
 * Painel da loja · Detalhe do pedido
 * Mostra tudo do pedido e o próximo passo: confirmar pagamento, separar, enviar (com rastreio),
 * avisar retirada, marcar entrega ou cancelar. Cada passo aparece na linha do tempo do cliente.
 */
require __DIR__ . '/../includes/admin.php';

exigir_admin();

$numero = (string) ($_GET['numero'] ?? '');
$pedido = pedido_admin($numero);
if (!$pedido) {
    aviso_definir('Pedido não encontrado', 'erro');
    redirecionar('admin/pedidos.php');
}

$erro = null;
$rastreio_digitado = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $acao = (string) ($_POST['acao'] ?? '');
    $rastreio_digitado = (string) ($_POST['rastreio'] ?? '');

    if ($acao === 'status') {
        $novo = (string) ($_POST['novo'] ?? '');
        $erro = mudar_status_pedido($pedido, $novo, $rastreio_digitado, (string) ($_POST['motivo'] ?? ''));
        if ($erro === null) {
            aviso_definir('Pedido #' . $pedido['numero'] . ': ' . STATUS_PEDIDO[$novo][0]);
        }
    } elseif ($acao === 'rastreio') {
        $erro = corrigir_rastreio($pedido, $rastreio_digitado);
        if ($erro === null) {
            aviso_definir('Código de rastreio corrigido');
        }
    }
    if ($erro === null) {
        redirecionar('admin/pedido.php?numero=' . urlencode($pedido['numero']));
    }
}

$proximos = proximos_status($pedido);
$avancar = array_values(array_filter($proximos, fn ($s) => $s !== 'cancelado'));
$pode_cancelar = in_array('cancelado', $proximos, true);
$retirada = $pedido['entrega_tipo'] === 'retirada';
$quantidade = array_sum(array_column($pedido['itens'], 'quantidade'));

$texto_pagamento = match ($pedido['pagamento_tipo']) {
    'pix'    => 'Pix · ' . (int) (PIX_DESCONTO * 100) . '% de desconto',
    'cartao' => 'Cartão de crédito · ' . ($pedido['parcelas'] > 1 ? $pedido['parcelas'] . 'x de ' . dinheiro(round($pedido['total'] / $pedido['parcelas'], 2)) : 'à vista'),
    'boleto' => 'Boleto bancário',
};

// o que a loja precisa fazer agora, em palavras
$orientacao = match ($pedido['status']) {
    'aguardando_pagamento' => $pedido['pagamento_tipo'] === 'boleto'
        ? 'Confirme quando o boleto for compensado. Até lá, não separe os produtos.'
        : 'O cliente ainda não pagou o Pix. Confirme só se o pagamento aparecer na conta da loja.',
    'pagamento_recusado'   => 'O pagamento foi recusado. Cancele para devolver as peças ao estoque.',
    'pago'                 => 'Pagamento aprovado. Separe os produtos para ' . ($retirada ? 'retirada na loja.' : 'envio.'),
    'em_separacao'         => $retirada ? 'Quando estiver tudo separado, avise o cliente que pode retirar.' : 'Quando postar, digite o código de rastreio que os Correios ou a transportadora deram.',
    'enviado'              => 'Pedido a caminho. Marque como entregue quando chegar ao cliente.',
    'pronto_retirada'      => 'Esperando o cliente vir buscar em São Leopoldo. Confira o CPF na retirada.',
    'entregue'             => 'Pedido concluído. Trocas valem até ' . TROCA_PRAZO_DIAS . ' dias depois da entrega.',
    'cancelado'            => 'Pedido cancelado. As peças já voltaram para o estoque.',
    default                => '',
};

admin_pagina_inicio([
    'titulo' => 'Pedido #' . $pedido['numero'],
    'aba'    => 'pedidos',
    'nav'    => ['titulo' => 'Pedido #' . $pedido['numero'], 'voltar' => url('admin/pedidos.php')],
]);
?>
<div class="conteudo conteudo--leitura pilha-xl" style="padding-top: var(--spacing-lg)">

  <section class="pilha-sm">
    <?= selo_status($pedido['status']) ?>
    <h1 class="t-title-2"><?= e($pedido['nome']) ?></h1>
    <p class="t-footnote texto-secundario">
      Feito em <?= e(data_longa($pedido['criado_em'])) ?>, às <?= e((new DateTimeImmutable($pedido['criado_em']))->format('H:i')) ?>
      · <?= $quantidade ?> <?= $quantidade === 1 ? 'peça' : 'peças' ?> · <?= dinheiro((float) $pedido['total']) ?>
    </p>
  </section>

  <!-- Próximo passo -->
  <section class="caixa pilha admin-passo" aria-labelledby="titulo-passo">
    <div class="pilha-xs">
      <h2 class="t-headline" id="titulo-passo"><?= $proximos ? 'Próximo passo' : 'Pedido encerrado' ?></h2>
      <p class="t-subheadline texto-secundario"><?= e($orientacao) ?></p>
    </div>

    <?php if ($erro): ?>
      <p class="caixa-erro t-subheadline" role="alert"><?= icone('alerta', 'icone--sm') ?> <?= e($erro) ?></p>
    <?php endif; ?>

    <?php foreach ($avancar as $status): ?>
      <form method="post" class="pilha-sm" <?= $status === 'enviado' ? 'data-validar novalidate' : '' ?>>
        <?= csrf_campo() ?>
        <input type="hidden" name="acao" value="status">
        <input type="hidden" name="novo" value="<?= e($status) ?>">
        <?php if ($status === 'enviado'): ?>
          <?= campo_texto('rastreio', 'Código de rastreio · ' . NOMES_ENTREGA[$pedido['entrega_tipo']], $rastreio_digitado, null,
              ['required' => true, 'autocapitalize' => 'characters', 'autocomplete' => 'off', 'spellcheck' => 'false', 'maxlength' => 30,
               'placeholder' => 'Ex.: QB123456789BR', 'data-obrigatorio' => 'Digite o código de rastreio.'],
              'O cliente vê este código na tela do pedido.') ?>
        <?php endif; ?>
        <button type="submit" class="botao botao--primary botao--bloco"><?= e(rotulo_acao_status($status, $pedido)) ?></button>
      </form>
    <?php endforeach; ?>

    <?php if ($pedido['status'] === 'enviado'): ?>
      <div class="caixa caixa--sutil linha linha--entre">
        <div>
          <p class="t-footnote texto-secundario">Código de rastreio · <?= e(NOMES_ENTREGA[$pedido['entrega_tipo']]) ?></p>
          <p class="t-callout pix-codigo"><?= e($pedido['codigo_rastreio']) ?></p>
        </div>
      </div>
      <details class="admin-detalhes">
        <summary class="t-subheadline">Corrigir o código de rastreio</summary>
        <form method="post" class="pilha-sm" data-validar novalidate>
          <?= csrf_campo() ?>
          <input type="hidden" name="acao" value="rastreio">
          <?= campo_texto('rastreio', 'Código certo', $pedido['codigo_rastreio'], null,
              ['required' => true, 'autocapitalize' => 'characters', 'autocomplete' => 'off', 'maxlength' => 30]) ?>
          <button type="submit" class="botao botao--glass botao--bloco">Salvar código</button>
        </form>
      </details>
    <?php elseif ($pedido['codigo_rastreio']): ?>
      <p class="t-footnote texto-secundario">Rastreio: <span class="pix-codigo"><?= e($pedido['codigo_rastreio']) ?></span></p>
    <?php endif; ?>

    <?php if ($pode_cancelar): ?>
      <details class="admin-detalhes">
        <summary class="t-subheadline texto-erro">Cancelar este pedido</summary>
        <form method="post" class="pilha-sm" data-confirmar="Cancelar o pedido #<?= e($pedido['numero']) ?>? As peças voltam para o estoque e isso não pode ser desfeito.">
          <?= csrf_campo() ?>
          <input type="hidden" name="acao" value="status">
          <input type="hidden" name="novo" value="cancelado">
          <?= campo_texto('motivo', 'Motivo (o cliente vê)', '', null, ['maxlength' => 120, 'placeholder' => 'Ex.: pagamento não identificado'], 'Opcional') ?>
          <?php if (in_array($pedido['status'], ['pago', 'em_separacao', 'pronto_retirada'], true)): ?>
            <p class="t-footnote texto-secundario"><?= icone('info', 'icone--xs') ?> O pedido já foi pago: devolva o dinheiro ao cliente por fora (estorno do cartão ou Pix).</p>
          <?php endif; ?>
          <button type="submit" class="botao botao--ghost botao--bloco texto-erro">Cancelar pedido</button>
        </form>
      </details>
    <?php endif; ?>
  </section>

  <!-- Cliente -->
  <section class="pilha-sm">
    <h2 class="t-headline">Cliente</h2>
    <ul class="lista" role="list">
      <li>
        <div class="linha-lista">
          <?= icone('conta', 'linha-lista__icone') ?>
          <span class="linha-lista__rotulo">
            <?= e($pedido['nome']) ?><br>
            <span class="t-footnote texto-secundario">CPF <?= e(formatar_cpf($pedido['cpf'])) ?> · <?= $pedido['cliente_id'] ? 'tem conta na loja' : 'comprou sem conta' ?></span>
          </span>
        </div>
      </li>
      <li><?= linha_lista($pedido['email'], 'email', 'mailto:' . $pedido['email'] . '?subject=' . rawurlencode('Pedido #' . $pedido['numero'])) ?></li>
      <li><?= linha_lista($pedido['telefone'], 'whatsapp', 'https://wa.me/55' . so_numeros($pedido['telefone']) . '?text=' . rawurlencode('Olá, ' . explode(' ', $pedido['nome'])[0] . '! Aqui é da ' . LOJA_NOME . ', sobre o pedido #' . $pedido['numero'] . '.')) ?></li>
    </ul>
  </section>

  <!-- Itens -->
  <section class="pilha-sm">
    <h2 class="t-headline">Separar estes produtos</h2>
    <ul class="lista revisao-itens" role="list">
      <?php foreach ($pedido['itens'] as $item): ?>
        <li>
          <div class="linha-lista">
            <?= midia_produto(['midia' => $item['midia'] ?? 'camisa', 'imagem' => $item['imagem']], 'revisao-itens__foto') ?>
            <span class="linha-lista__rotulo">
              <span class="t-callout"><?= e($item['nome_produto']) ?></span><br>
              <span class="t-footnote texto-secundario">Tamanho <strong><?= e($item['tamanho']) ?></strong> · <?= (int) $item['quantidade'] ?> un. · <?= dinheiro((float) $item['preco_unitario']) ?> cada</span>
            </span>
            <span class="linha-lista__valor"><?= dinheiro($item['quantidade'] * (float) $item['preco_unitario']) ?></span>
          </div>
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
          <?= icone($retirada ? 'loja' : 'caminhao', 'linha-lista__icone') ?>
          <span class="linha-lista__rotulo">
            <?= e(NOMES_ENTREGA[$pedido['entrega_tipo']]) ?> · <?= e($pedido['entrega_prazo']) ?><br>
            <span class="t-footnote texto-secundario">
              <?php if ($retirada): ?>
                <?= e(DEPOSITO_ENDERECO) ?>
              <?php else: ?>
                <?php [$rua, $cidade] = endereco_texto($pedido); ?>
                <?= e($rua) ?><br><?= e($cidade) ?>
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

  <!-- Histórico -->
  <section class="pilha-sm">
    <h2 class="t-headline">Histórico</h2>
    <ol class="lista admin-historico" role="list">
      <?php foreach ($pedido['historico'] as $h): ?>
        <li class="linha-lista">
          <span class="linha-lista__rotulo">
            <span class="t-callout"><?= e($h['status'] === 'aguardando_pagamento' ? 'Pedido recebido' : (STATUS_PEDIDO[$h['status']][0] ?? $h['status'])) ?></span><br>
            <span class="t-footnote texto-secundario"><?= e($h['descricao']) ?></span>
          </span>
          <span class="linha-lista__valor t-footnote"><?= e(data_hora_curta($h['criado_em'])) ?></span>
        </li>
      <?php endforeach; ?>
    </ol>
  </section>
</div>
<?php pagina_fim(['scripts' => ['validacao.js', 'admin.js']]); ?>
