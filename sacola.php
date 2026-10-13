<?php
/**
 * Telas 10 · Sacola e 11 · Sacola vazia
 */
require __DIR__ . '/includes/bootstrap.php';

$itens  = sacola_itens($avisos);
$resumo = sacola_resumo($itens);

pagina_inicio(['titulo' => 'Sacola', 'aba' => 'sacola']);
?>
<div class="conteudo conteudo--leitura" id="sacola-conteudo">
  <?= nav_large('Sacola', $itens
      ? '<span class="t-subheadline texto-secundario">' . $resumo['quantidade'] . ($resumo['quantidade'] == 1 ? ' item' : ' itens') . '</span>'
      : '') ?>

  <?php foreach ($avisos as $aviso): ?>
    <p class="caixa caixa--sutil t-subheadline" role="status" style="margin-bottom: var(--spacing-md)"><?= icone('info', 'icone--sm') ?> <?= e($aviso) ?></p>
  <?php endforeach; ?>

  <?php if (!$itens): ?>
    <!-- Tela 11 · Sacola vazia -->
    <div class="estado-vazio">
      <div class="estado-vazio__icone"><?= icone('sacola') ?></div>
      <h2 class="t-title-3">Sua sacola está vazia</h2>
      <p class="t-subheadline">Que tal começar pelas camisas da temporada?</p>
      <a class="botao botao--primary botao--md" href="<?= e(url_resultados([], ['esporte' => 'futebol', 'categoria' => 'camisas'])) ?>">Ver camisas</a>
      <a class="botao botao--ghost botao--md" href="<?= e(url('esportes.php')) ?>">Ver todos os esportes</a>
    </div>

  <?php else: ?>
    <div class="pilha">

      <!-- Quanto falta para o frete grátis -->
      <div class="caixa frete-progresso">
        <?php if ($resumo['frete_gratis']): ?>
          <p class="t-callout texto-sucesso"><?= icone('caminhao', 'icone--sm') ?> Você ganhou frete grátis no PAC</p>
        <?php else: ?>
          <p class="t-callout"><?= icone('caminhao', 'icone--sm') ?> Faltam <strong><?= dinheiro($resumo['falta_frete']) ?></strong> para o frete grátis</p>
        <?php endif; ?>
        <div class="frete-progresso__barra" role="progressbar" aria-label="Progresso até o frete grátis"
             aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= (int) min(100, $resumo['subtotal'] / FRETE_GRATIS_ACIMA * 100) ?>">
          <span style="width: <?= min(100, $resumo['subtotal'] / FRETE_GRATIS_ACIMA * 100) ?>%"></span>
        </div>
      </div>

      <!-- Itens -->
      <ul class="pilha-sm itens-sacola" role="list" style="margin: 0; padding: 0">
        <?php foreach ($itens as $item): ?>
          <?php $p = $item['produto']; $link = url('produto.php?id=' . (int) $p['id']); ?>
          <li class="item-sacola-trilho" data-chave="<?= e($item['chave']) ?>">
            <!-- aparece por trás ao deslizar para a esquerda -->
            <div class="item-sacola__fundo" aria-hidden="true"><?= icone('lixeira') ?> Remover</div>

            <article class="item-sacola">
              <a href="<?= e($link) ?>" tabindex="-1" aria-hidden="true"><?= midia_produto($p) ?></a>
              <div class="item-sacola__info">
                <h2 class="item-sacola__nome"><a href="<?= e($link) ?>" style="color: inherit"><?= e($p['nome']) ?></a></h2>
                <span class="item-sacola__detalhe">Tamanho <?= e($item['tamanho']) ?> · <?= dinheiro($item['preco']) ?> cada</span>
                <?php if ($item['estoque'] <= 3): ?>
                  <span class="item-sacola__detalhe texto-erro">
                    <?= $item['estoque'] == 1 ? 'Última unidade' : 'Últimas ' . $item['estoque'] . ' unidades' ?>
                  </span>
                <?php endif; ?>
                <div class="item-sacola__rodape">
                  <span class="t-headline"><?= dinheiro($item['subtotal']) ?></span>

                  <form class="seletor-qtd" method="post" action="<?= e(url('api/sacola.php')) ?>" data-form-sacola>
                    <?= csrf_campo() ?>
                    <input type="hidden" name="acao" value="atualizar">
                    <input type="hidden" name="chave" value="<?= e($item['chave']) ?>">
                    <button type="submit" name="quantidade" value="<?= $item['quantidade'] - 1 ?>"
                            aria-label="<?= $item['quantidade'] == 1 ? 'Remover ' . e($p['nome']) : 'Diminuir quantidade' ?>">
                      <?= icone($item['quantidade'] == 1 ? 'lixeira' : 'menos', 'icone--sm') ?>
                    </button>
                    <output aria-live="polite" aria-label="Quantidade"><?= (int) $item['quantidade'] ?></output>
                    <button type="submit" name="quantidade" value="<?= $item['quantidade'] + 1 ?>" aria-label="Aumentar quantidade"
                            <?= $item['quantidade'] >= $item['estoque'] ? 'disabled' : '' ?>>
                      <?= icone('mais', 'icone--sm') ?>
                    </button>
                  </form>
                </div>
              </div>
            </article>
          </li>
        <?php endforeach; ?>
      </ul>
      <p class="t-footnote texto-terciario dica-deslizar">Deslize um item para a esquerda para remover.</p>

      <!-- Resumo -->
      <section class="caixa pilha" aria-label="Resumo">
        <dl class="resumo-valores" style="margin: 0">
          <div><dt>Subtotal</dt><dd><?= dinheiro($resumo['subtotal']) ?></dd></div>
          <div>
            <dt>Frete</dt>
            <dd class="<?= $resumo['frete_gratis'] ? 'texto-sucesso' : 'texto-secundario' ?>">
              <?= $resumo['frete_gratis'] ? 'Grátis no PAC' : 'Escolha na entrega' ?>
            </dd>
          </div>
          <div><dt>Desconto no Pix (<?= (int) (PIX_DESCONTO * 100) ?>%)</dt><dd class="texto-sucesso">− <?= dinheiro($resumo['desconto_pix']) ?></dd></div>
          <div class="resumo-valores__total"><dt>Total no Pix</dt><dd><?= dinheiro($resumo['total_pix']) ?></dd></div>
        </dl>
        <p class="t-footnote texto-secundario" style="margin-top: calc(var(--spacing-sm) * -1)">
          ou <?= dinheiro($resumo['subtotal']) ?> em <?= e(texto_parcelas($resumo['subtotal'])) ?>
        </p>
        <a class="botao botao--primary botao--bloco" href="<?= e(url('checkout/')) ?>">
          Continuar <?= icone('seta-avancar', 'icone--sm') ?>
        </a>
        <p class="t-footnote texto-terciario" style="text-align: center">
          <?= icone('cadeado', 'icone--xs') ?> Não precisa criar conta para comprar
        </p>
      </section>
    </div>
  <?php endif; ?>
</div>
<?php pagina_fim(['scripts' => ['sacola.js']]); ?>
