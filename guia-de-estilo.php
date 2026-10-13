<?php
/**
 * Guia de estilo: mostra todos os componentes da etapa 1 numa página só.
 * Serve para conferir o visual com o Figma e para apresentar o design system.
 */
require __DIR__ . '/includes/bootstrap.php';

// Alguns produtos reais do banco para os cards
$produtos = consulta(
    'SELECT p.*, (SELECT SUM(t.estoque) FROM produto_tamanhos t WHERE t.produto_id = p.id) AS estoque_total
       FROM produtos p
      WHERE p.ativo = 1
      ORDER BY p.destaque DESC, p.id
      LIMIT 6'
)->fetchAll();

$camisa = consulta('SELECT * FROM produtos WHERE id = 1')->fetch();
$tamanhos = consulta('SELECT tamanho, estoque FROM produto_tamanhos WHERE produto_id = 1 ORDER BY ordem')->fetchAll();

$cores = [
    'Marca' => ['--color-brand-primary', '--color-brand-pressed', '--color-brand-subtle', '--color-accent-primary', '--color-accent-pressed'],
    'Texto' => ['--color-text-primary', '--color-text-secondary', '--color-text-tertiary', '--color-text-link'],
    'Fundo' => ['--color-bg-primary', '--color-bg-secondary', '--color-bg-tertiary', '--color-bg-inverse'],
    'Status' => ['--color-status-success', '--color-status-warning', '--color-status-error'],
];

$textos = [
    't-display' => 'Display', 't-title-1' => 'Title 1', 't-title-2' => 'Title 2', 't-title-3' => 'Title 3',
    't-headline' => 'Headline', 't-body' => 'Body', 't-body-strong' => 'Body Strong', 't-callout' => 'Callout',
    't-subheadline' => 'Subheadline', 't-footnote' => 'Footnote', 't-caption' => 'Caption',
    't-label-caps' => 'Label Caps', 't-price' => 'R$ 299,90', 't-price-large' => 'R$ 634,43',
];

pagina_inicio([
    'titulo' => 'Guia de estilo',
    'aba'    => 'inicio',
    'nav'    => [
        'titulo' => 'Guia de estilo',
        'voltar' => url('index.php'),
        'acoes'  => '<button type="button" class="botao-icone botao-icone--plain" aria-label="Compartilhar">' . icone('compartilhar') . '</button>',
    ],
]);
?>
<style>
  .gs-secao { padding-block: var(--spacing-2xl); border-bottom: 1px solid var(--color-border-default); }
  .gs-secao > h2 { margin-bottom: var(--spacing-lg); }
  .gs-cores { display: grid; grid-template-columns: repeat(auto-fill, minmax(96px, 1fr)); gap: var(--spacing-md); }
  .gs-cor { display: flex; flex-direction: column; gap: 6px; font-size: 11px; color: var(--color-text-secondary); word-break: break-all; }
  .gs-cor span:first-child { height: 56px; border-radius: var(--radius-md); border: 1px solid var(--color-border-default); }
  .gs-icones { display: grid; grid-template-columns: repeat(auto-fill, minmax(64px, 1fr)); gap: var(--spacing-md); }
  .gs-icones div { display: flex; flex-direction: column; align-items: center; gap: 6px; font-size: 10px; color: var(--color-text-secondary); }
  .gs-fundo-foto { padding: var(--spacing-lg); border-radius: var(--radius-xl);
    background: linear-gradient(135deg, var(--blue-400), var(--lime-500)); }
  .gs-silhuetas { display: grid; grid-template-columns: repeat(auto-fill, minmax(96px, 1fr)); gap: var(--spacing-md); }
</style>

<div class="conteudo">
  <p class="t-subheadline texto-secundario" style="padding-top: var(--spacing-lg)">
    Componentes da página "01 · Fundações &amp; Componentes" do Figma, feitos em HTML e CSS.
    Use esta página para comparar com o design.
  </p>

  <!-- ========== Cores ========== -->
  <section class="gs-secao">
    <h2 class="t-title-2">Cores</h2>
    <div class="pilha">
      <?php foreach ($cores as $grupo => $variaveis): ?>
        <div class="pilha-sm">
          <h3 class="t-label-caps texto-secundario"><?= e($grupo) ?></h3>
          <div class="gs-cores">
            <?php foreach ($variaveis as $var): ?>
              <div class="gs-cor"><span style="background: var(<?= e($var) ?>)"></span><span><?= e(str_replace('--color-', '', $var)) ?></span></div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- ========== Tipografia ========== -->
  <section class="gs-secao">
    <h2 class="t-title-2">Tipografia · Inter</h2>
    <div class="pilha-sm">
      <?php foreach ($textos as $classe => $exemplo): ?>
        <div class="linha linha--entre">
          <span class="<?= e($classe) ?>"><?= e($exemplo) ?></span>
          <code class="t-caption texto-terciario">.<?= e($classe) ?></code>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- ========== Botões ========== -->
  <section class="gs-secao">
    <h2 class="t-title-2">Botões</h2>
    <div class="pilha">
      <p class="t-footnote texto-secundario">Encostar o dedo reduz o botão para 97%.</p>
      <button class="botao botao--primary botao--bloco"><?= icone('sacola') ?> Adicionar à sacola</button>
      <div class="gs-fundo-foto pilha">
        <button class="botao botao--glass botao--bloco"><?= icone('regua') ?> Guia de tamanhos</button>
        <button class="botao botao--primary">Vidro azul sobre cor</button>
      </div>
      <button class="botao botao--accent botao--bloco"><?= icone('pix') ?> Pagar com Pix</button>
      <div class="linha" style="flex-wrap: wrap">
        <button class="botao botao--primary botao--md">Medium</button>
        <button class="botao botao--accent botao--md">Accent</button>
        <button class="botao botao--glass botao--md">Glass</button>
        <button class="botao botao--ghost botao--md">Ghost</button>
      </div>
      <div class="linha" style="flex-wrap: wrap">
        <button class="botao botao--primary botao--md" disabled>Desativado</button>
        <button class="botao botao--accent botao--md" disabled>Desativado</button>
      </div>
      <div class="linha">
        <button class="botao-icone botao-icone--glass" aria-label="Favoritar"><?= icone('coracao') ?></button>
        <button class="botao-icone botao-icone--glass" aria-label="Favoritado" aria-pressed="true"><?= icone('coracao') ?></button>
        <button class="botao-icone botao-icone--tint" aria-label="Compartilhar"><?= icone('compartilhar') ?></button>
        <button class="botao-icone botao-icone--plain" aria-label="Filtros"><?= icone('filtros') ?></button>
        <button class="botao-icone botao-icone--glass botao-icone--sm" aria-label="Fechar"><?= icone('fechar') ?></button>
        <button class="botao-icone botao-icone--tint botao-icone--sm" aria-label="Adicionar"><?= icone('mais') ?></button>
      </div>
    </div>
  </section>

  <!-- ========== Selos e chips ========== -->
  <section class="gs-secao">
    <h2 class="t-title-2">Selos e chips</h2>
    <div class="pilha">
      <div class="linha" style="flex-wrap: wrap">
        <?= selo('oficial', 'Oficial') ?><?= selo('promo', 'Promo') ?><?= selo('novo', 'Novo') ?>
        <?= selo('frete', 'Frete grátis') ?><?= selo('esgotado', 'Esgotado') ?>
      </div>
      <div class="rolagem-horizontal">
        <button class="chip" aria-pressed="true">Todos</button>
        <button class="chip" aria-pressed="false">Camisas</button>
        <button class="chip" aria-pressed="false">Chuteiras</button>
        <button class="chip" aria-pressed="false">Bolas</button>
        <button class="chip" aria-pressed="false">Acessórios</button>
      </div>
    </div>
  </section>

  <!-- ========== Campos ========== -->
  <section class="gs-secao">
    <h2 class="t-title-2">Campos</h2>
    <div class="pilha">
      <label class="campo-busca">
        <?= icone('buscar', 'icone--sm') ?>
        <span class="visualmente-oculto">Buscar</span>
        <input type="search" placeholder="Times, produtos, esportes">
      </label>
      <div class="campo">
        <label class="campo__rotulo" for="gs-nome">Nome completo</label>
        <input class="campo__entrada" id="gs-nome" placeholder="Como está no documento">
        <span class="campo__ajuda">Usado na nota fiscal.</span>
      </div>
      <div class="campos-lado-a-lado">
        <div class="campo">
          <label class="campo__rotulo" for="gs-cep">CEP</label>
          <input class="campo__entrada" id="gs-cep" value="93010-000" inputmode="numeric">
        </div>
        <div class="campo campo--erro">
          <label class="campo__rotulo" for="gs-cpf">CPF</label>
          <input class="campo__entrada" id="gs-cpf" value="111.111.111-11" inputmode="numeric" aria-describedby="gs-cpf-erro" aria-invalid="true">
          <span class="campo__ajuda" id="gs-cpf-erro">CPF inválido</span>
        </div>
      </div>
    </div>
  </section>

  <!-- ========== Tamanho e quantidade ========== -->
  <section class="gs-secao">
    <h2 class="t-title-2">Tamanho e quantidade</h2>
    <div class="pilha">
      <fieldset class="tamanhos" style="border: 0; padding: 0; margin: 0">
        <legend class="visualmente-oculto">Tamanho</legend>
        <?php foreach ($tamanhos as $i => $t): ?>
          <input type="radio" class="visualmente-oculto" name="gs-tamanho" id="gs-t<?= $i ?>"
                 <?= $t['estoque'] == 0 ? 'disabled' : '' ?> <?= $t['tamanho'] === 'M' ? 'checked' : '' ?>>
          <label class="opcao-tamanho" for="gs-t<?= $i ?>"><?= e($t['tamanho']) ?></label>
        <?php endforeach; ?>
      </fieldset>
      <div class="seletor-qtd" role="group" aria-label="Quantidade">
        <button type="button" aria-label="Diminuir" disabled><?= icone('menos', 'icone--sm') ?></button>
        <output>1</output>
        <button type="button" aria-label="Aumentar"><?= icone('mais', 'icone--sm') ?></button>
      </div>
    </div>
  </section>

  <!-- ========== Cards de produto ========== -->
  <section class="gs-secao">
    <?= cabecalho_secao('Cards de produto', '#', 'Ver tudo') ?>
    <div class="grade-produtos">
      <?php foreach ($produtos as $p): ?>
        <?= card_produto($p) ?>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- ========== Banner ========== -->
  <section class="gs-secao">
    <h2 class="t-title-2">Banner (carrossel)</h2>
    <div class="carrossel">
      <div class="rolagem-horizontal">
        <article class="hero">
          <?= silhueta('camisa', 'hero__arte') ?>
          <span class="hero__sobretitulo">Temporada 26/27</span>
          <h3 class="hero__titulo">Os novos mantos chegaram</h3>
          <p class="hero__subtitulo">Grêmio, Inter e Juventude com selo oficial.</p>
          <a class="botao botao--accent botao--md" href="#">Ver camisas</a>
        </article>
        <article class="hero hero--escuro">
          <?= silhueta('tenis', 'hero__arte') ?>
          <span class="hero__sobretitulo">Basquete</span>
          <h3 class="hero__titulo">Quadra ou rua, tem tênis</h3>
          <p class="hero__subtitulo">Amortecimento para jogar o jogo todo.</p>
          <a class="botao botao--glass botao--md" href="#">Ver tênis</a>
        </article>
        <article class="hero hero--lime">
          <?= silhueta('bola', 'hero__arte') ?>
          <span class="hero__sobretitulo">Todo o RS</span>
          <h3 class="hero__titulo">Frete grátis acima de R$ 399</h3>
          <p class="hero__subtitulo">Ou retire de graça em São Leopoldo.</p>
          <a class="botao botao--primary botao--md" href="#">Saiba mais</a>
        </article>
      </div>
      <div class="pontos-carrossel" aria-hidden="true">
        <span aria-current="true"></span><span></span><span></span>
      </div>
    </div>
  </section>

  <!-- ========== Categorias ========== -->
  <section class="gs-secao">
    <h2 class="t-title-2">Blocos de categoria</h2>
    <div class="grade-tiles">
      <a class="tile-categoria tile-categoria--blue" href="#"><span class="tile-categoria__titulo">Futebol</span><span class="tile-categoria__detalhe">14 produtos</span><?= silhueta('bola', 'tile-categoria__arte') ?></a>
      <a class="tile-categoria tile-categoria--lime" href="#"><span class="tile-categoria__titulo">Basquete</span><span class="tile-categoria__detalhe">8 produtos</span><?= silhueta('tenis', 'tile-categoria__arte') ?></a>
      <a class="tile-categoria tile-categoria--dark" href="#"><span class="tile-categoria__titulo">Chuteiras</span><span class="tile-categoria__detalhe">3 produtos</span><?= silhueta('chuteira', 'tile-categoria__arte') ?></a>
      <a class="tile-categoria tile-categoria--light" href="#"><span class="tile-categoria__titulo">Meiões</span><span class="tile-categoria__detalhe">1 produto</span><?= silhueta('meiao', 'tile-categoria__arte') ?></a>
    </div>
  </section>

  <!-- ========== Silhuetas ========== -->
  <section class="gs-secao">
    <h2 class="t-title-2">Mídia do produto (silhuetas provisórias)</h2>
    <div class="gs-silhuetas">
      <?php foreach (['camisa', 'bola', 'chuteira', 'tenis', 'calcao', 'meiao', 'caneleira'] as $m): ?>
        <?= midia_produto(['midia' => $m]) ?>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- ========== Opções ========== -->
  <section class="gs-secao">
    <h2 class="t-title-2">Cartões de opção</h2>
    <div class="pilha-sm">
      <input type="radio" class="visualmente-oculto" name="gs-entrega" id="gs-pac" checked>
      <label class="opcao-card" for="gs-pac">
        <span class="opcao-card__radio"></span>
        <span class="opcao-card__textos"><span class="opcao-card__titulo">PAC</span><span class="opcao-card__descricao">Entre 20 e 23 de out.</span></span>
        <span class="opcao-card__valor texto-sucesso">Grátis</span>
      </label>
      <input type="radio" class="visualmente-oculto" name="gs-entrega" id="gs-sedex">
      <label class="opcao-card" for="gs-sedex">
        <span class="opcao-card__radio"></span>
        <span class="opcao-card__textos"><span class="opcao-card__titulo">SEDEX</span><span class="opcao-card__descricao">Entre 16 e 17 de out.</span></span>
        <span class="opcao-card__valor">R$ 32,90</span>
      </label>
      <input type="radio" class="visualmente-oculto" name="gs-entrega" id="gs-retirada">
      <label class="opcao-card" for="gs-retirada">
        <span class="opcao-card__radio"></span>
        <span class="opcao-card__textos"><span class="opcao-card__titulo">Retirar em São Leopoldo</span><span class="opcao-card__descricao">Pronto em 1 dia útil, no depósito</span></span>
        <span class="opcao-card__valor texto-sucesso">Grátis</span>
      </label>
    </div>
  </section>

  <!-- ========== Listas ========== -->
  <section class="gs-secao">
    <h2 class="t-title-2">Lista</h2>
    <ul class="lista" role="list">
      <li><?= linha_lista('Meus pedidos', 'pacote', '#') ?></li>
      <li><?= linha_lista('Endereços', 'local', '#', '1 salvo') ?></li>
      <li><?= linha_lista('Favoritos', 'coracao', '#', '3') ?></li>
      <li><?= linha_lista('Trocas e devoluções', 'trocas', '#') ?></li>
    </ul>
  </section>

  <!-- ========== Sacola ========== -->
  <section class="gs-secao">
    <h2 class="t-title-2">Item da sacola e resumo</h2>
    <div class="pilha">
      <article class="item-sacola">
        <?= midia_produto($camisa) ?>
        <div class="item-sacola__info">
          <h3 class="item-sacola__nome"><?= e($camisa['nome']) ?></h3>
          <span class="item-sacola__detalhe">Tamanho M</span>
          <div class="item-sacola__rodape">
            <span class="t-headline"><?= dinheiro((float) $camisa['preco']) ?></span>
            <div class="seletor-qtd" role="group" aria-label="Quantidade">
              <button type="button" aria-label="Remover"><?= icone('lixeira', 'icone--sm') ?></button>
              <output>1</output>
              <button type="button" aria-label="Aumentar"><?= icone('mais', 'icone--sm') ?></button>
            </div>
          </div>
        </div>
      </article>
      <dl class="caixa resumo-valores" style="margin: 0">
        <div><dt>Subtotal</dt><dd>R$ 667,82</dd></div>
        <div><dt>Frete PAC</dt><dd class="texto-sucesso">Grátis</dd></div>
        <div><dt>Desconto Pix (5%)</dt><dd class="texto-sucesso">− R$ 33,39</dd></div>
        <div class="resumo-valores__total"><dt>Total no Pix</dt><dd>R$ 634,43</dd></div>
      </dl>
    </div>
  </section>

  <!-- ========== Etapas ========== -->
  <section class="gs-secao">
    <h2 class="t-title-2">Etapas do checkout</h2>
    <ol class="etapas">
      <li data-estado="feito">Endereço</li>
      <li data-estado="feito">Entrega</li>
      <li aria-current="step">Pagamento</li>
      <li>Revisão</li>
    </ol>
  </section>

  <!-- ========== Painel e aviso ========== -->
  <section class="gs-secao">
    <h2 class="t-title-2">Painel e aviso</h2>
    <div class="pilha">
      <p class="t-footnote texto-secundario">O painel abre com mola. Arraste para baixo para fechar: se soltar rápido, ele fecha mesmo sem ter descido até a metade.</p>
      <button class="botao botao--glass botao--bloco" data-abrir-painel="gs-painel"><?= icone('regua') ?> Abrir painel</button>
      <button class="botao botao--glass botao--bloco" id="gs-aviso"><?= icone('check-circulo') ?> Mostrar aviso</button>
      <button class="botao botao--glass botao--bloco" id="gs-aviso-erro"><?= icone('alerta') ?> Mostrar aviso de erro</button>
    </div>
  </section>

  <!-- ========== Estados ========== -->
  <section class="gs-secao">
    <h2 class="t-title-2">Carregando</h2>
    <div class="grade-produtos">
      <?php for ($i = 0; $i < 2; $i++): ?>
        <div class="pilha-sm">
          <div class="esqueleto esqueleto--midia"></div>
          <div class="esqueleto esqueleto--texto"></div>
          <div class="esqueleto esqueleto--texto" style="width: 50%"></div>
        </div>
      <?php endfor; ?>
    </div>
  </section>

  <section class="gs-secao" style="border-bottom: 0">
    <div class="estado-vazio">
      <div class="estado-vazio__icone"><?= icone('sacola') ?></div>
      <h2 class="t-title-3">Sua sacola está vazia</h2>
      <p class="t-subheadline">Explore as camisas da temporada e os lançamentos.</p>
      <a class="botao botao--primary botao--md" href="#">Ver produtos</a>
    </div>
  </section>

  <section class="gs-secao" style="border-bottom: 0">
    <h2 class="t-title-2" style="margin-bottom: var(--spacing-lg)">Ícones</h2>
    <div class="gs-icones">
      <?php foreach (['inicio','buscar','esportes','grade','sacola','conta','coracao','caminhao','pix','cartao','boleto','loja','regua','voltar','seta-direita','seta-baixo','fechar','mais','menos','lixeira','filtros','check','check-circulo','alerta','info','ajuda','compartilhar','whatsapp','instagram','email','local','pacote','trocas','cadeado','olho','olho-fechado','sem-internet','relogio','copiar','sair','editar','escudo','calendario','qrcode','seta-avancar'] as $ic): ?>
        <div><?= icone($ic) ?><span><?= e($ic) ?></span></div>
      <?php endforeach; ?>
    </div>
  </section>
</div>

<?= painel_inicio('gs-painel', 'Guia de tamanhos') ?>
  <div class="pilha">
    <p class="t-subheadline texto-secundario">Meça uma camisa que você já usa, deitada, de axila a axila.</p>
    <ul class="lista" role="list">
      <li><?= linha_lista('P', null, null, '50 cm · 70 cm') ?></li>
      <li><?= linha_lista('M', null, null, '53 cm · 72 cm') ?></li>
      <li><?= linha_lista('G', null, null, '56 cm · 74 cm') ?></li>
      <li><?= linha_lista('GG', null, null, '59 cm · 76 cm') ?></li>
    </ul>
    <p class="t-footnote texto-terciario">Largura · comprimento. Medidas aproximadas, variação de até 2 cm.</p>
  </div>
<?= painel_fim('<button class="botao botao--primary botao--bloco" data-fechar-painel>Entendi</button>') ?>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('gs-aviso').addEventListener('click', function () {
      Triade.aviso('Adicionado à sacola', { acao: { rotulo: 'Ver sacola', href: '#' } });
    });
    document.getElementById('gs-aviso-erro').addEventListener('click', function () {
      Triade.aviso('Não foi possível conectar', { tipo: 'erro' });
    });
  });
</script>
<?php pagina_fim(); ?>
