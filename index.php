<?php
/**
 * Tela 01 · Início
 */
require __DIR__ . '/includes/bootstrap.php';

$esportes    = esportes_com_categorias();
$destaques   = produtos_destaque();
$lancamentos = produtos_lancamentos(4);
$promocoes   = produtos_promocao();

// Desenho que aparece no bloco de cada esporte (esportes novos usam a bola)
$arte_esporte = ['futebol' => 'bola', 'basquete' => 'tenis'];

pagina_inicio(['aba' => 'inicio']);
?>
<div class="conteudo pilha-xl">

  <header class="inicio-topo">
    <h1 class="visualmente-oculto"><?= e(LOJA_NOME) ?>: loja de materiais esportivos</h1>
    <a class="marca" href="<?= e(url('index.php')) ?>" aria-label="<?= e(LOJA_NOME) ?>, início">
      TRIADE<span>ESPORTE</span>
    </a>
    <a class="botao-icone botao-icone--glass" href="https://wa.me/<?= e(CONTATO_WHATSAPP) ?>" target="_blank" rel="noopener" aria-label="Falar no WhatsApp">
      <?= icone('whatsapp') ?>
    </a>
  </header>

  <a class="campo-busca" href="<?= e(url('buscar.php')) ?>">
    <?= icone('buscar', 'icone--sm') ?>
    <span>Times, produtos, esportes</span>
  </a>

  <!-- Banner -->
  <section class="carrossel" aria-label="Destaques da loja">
    <div class="rolagem-horizontal">
      <article class="hero">
        <?= silhueta('camisa', 'hero__arte') ?>
        <span class="hero__sobretitulo">Temporada 26/27</span>
        <h2 class="hero__titulo">Os novos mantos chegaram</h2>
        <p class="hero__subtitulo">Grêmio, Inter e Juventude com selo oficial.</p>
        <a class="botao botao--accent botao--md" href="<?= e(url_resultados([], ['esporte' => 'futebol', 'categoria' => 'camisas'])) ?>">Ver camisas</a>
      </article>
      <article class="hero hero--escuro">
        <?= silhueta('tenis', 'hero__arte') ?>
        <span class="hero__sobretitulo">Basquete</span>
        <h2 class="hero__titulo">Quadra ou rua, tem tênis</h2>
        <p class="hero__subtitulo">Amortecimento para jogar o jogo todo.</p>
        <a class="botao botao--glass botao--md" href="<?= e(url_resultados([], ['esporte' => 'basquete', 'categoria' => 'tenis'])) ?>">Ver tênis</a>
      </article>
      <article class="hero hero--lime">
        <?= silhueta('bola', 'hero__arte') ?>
        <span class="hero__sobretitulo">Para todo o RS</span>
        <h2 class="hero__titulo">Frete grátis acima de <?= dinheiro(FRETE_GRATIS_ACIMA) ?></h2>
        <p class="hero__subtitulo">Ou retire de graça no nosso depósito em São Leopoldo.</p>
        <a class="botao botao--primary botao--md" href="<?= e(url('trocas.php')) ?>">Como funciona</a>
      </article>
    </div>
    <div class="pontos-carrossel" aria-hidden="true">
      <span aria-current="true"></span><span></span><span></span>
    </div>
  </section>

  <!-- Esportes -->
  <section>
    <?= cabecalho_secao('Esportes', url('esportes.php')) ?>
    <div class="grade-tiles">
      <?php foreach ($esportes as $esporte): ?>
        <a class="tile-categoria tile-categoria--<?= e($esporte['cor']) ?>" href="<?= e(url_resultados([], ['esporte' => $esporte['slug']])) ?>">
          <span class="tile-categoria__titulo"><?= e($esporte['nome']) ?></span>
          <span class="tile-categoria__detalhe"><?= (int) $esporte['total_produtos'] ?> produtos</span>
          <?= silhueta($arte_esporte[$esporte['slug']] ?? 'bola', 'tile-categoria__arte') ?>
        </a>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- Destaques -->
  <?php if ($destaques): ?>
  <section>
    <?= cabecalho_secao('Mais procurados', url_resultados([])) ?>
    <div class="rolagem-horizontal">
      <?php foreach ($destaques as $produto): ?>
        <?= card_produto($produto) ?>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- Vantagens -->
  <section class="rolagem-horizontal vantagens" aria-label="Vantagens" tabindex="0">
    <div class="vantagem">
      <?= icone('caminhao') ?>
      <div><strong>Frete grátis</strong><span>PAC acima de <?= dinheiro(FRETE_GRATIS_ACIMA) ?></span></div>
    </div>
    <div class="vantagem">
      <?= icone('pix') ?>
      <div><strong><?= (int) (PIX_DESCONTO * 100) ?>% off no Pix</strong><span>Aprovação na hora</span></div>
    </div>
    <div class="vantagem">
      <?= icone('cartao') ?>
      <div><strong>Até <?= CARTAO_MAX_PARCELAS ?>x sem juros</strong><span>No cartão de crédito</span></div>
    </div>
    <div class="vantagem">
      <?= icone('trocas') ?>
      <div><strong>Troca em <?= TROCA_PRAZO_DIAS ?> dias</strong><span>Primeira troca grátis</span></div>
    </div>
  </section>

  <!-- Lançamentos -->
  <?php if ($lancamentos): ?>
  <section>
    <?= cabecalho_secao('Lançamentos', url_resultados([], ['ordem' => 'novidades'])) ?>
    <div class="grade-produtos">
      <?php foreach ($lancamentos as $produto): ?>
        <?= card_produto($produto) ?>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- Promoções -->
  <?php if ($promocoes): ?>
  <section>
    <?= cabecalho_secao('Promoções', url_resultados([], ['promo' => true])) ?>
    <div class="rolagem-horizontal">
      <?php foreach ($promocoes as $produto): ?>
        <?= card_produto($produto) ?>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- Ajuda -->
  <section class="caixa ajuda-whatsapp">
    <div class="pilha-sm">
      <h2 class="t-headline">Ficou com dúvida no tamanho?</h2>
      <p class="t-subheadline texto-secundario">Fale com a gente no WhatsApp antes de comprar.</p>
    </div>
    <a class="botao botao--accent botao--md" href="https://wa.me/<?= e(CONTATO_WHATSAPP) ?>" target="_blank" rel="noopener">
      <?= icone('whatsapp', 'icone--sm') ?> Conversar
    </a>
  </section>

</div>
<?php pagina_fim(); ?>
