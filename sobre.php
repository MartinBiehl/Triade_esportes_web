<?php
/**
 * Tela 28 · Sobre e contato
 */
require __DIR__ . '/includes/bootstrap.php';

$telefone = CONTATO_WHATSAPP;
$telefone_texto = '(' . substr($telefone, 2, 2) . ') ' . substr($telefone, 4, 5) . '-' . substr($telefone, 9);

pagina_inicio([
    'titulo'    => 'Sobre e contato',
    'descricao' => 'A Triade Esporte é uma loja de materiais esportivos de São Leopoldo/RS. Fale com a gente pelo WhatsApp, Instagram ou e-mail.',
    'aba'       => 'conta',
    'nav'       => ['titulo' => 'Sobre e contato', 'voltar' => url('conta/')],
]);
?>
<div class="conteudo conteudo--leitura pilha-xl" style="padding-top: var(--spacing-lg)">

  <section class="hero hero--escuro" style="min-height: 0">
    <span class="hero__sobretitulo">Desde São Leopoldo</span>
    <h1 class="hero__titulo">Triade Esporte</h1>
    <p class="hero__subtitulo">Camisas de time, chuteiras, bolas e acessórios de futebol e basquete, com entrega para todo o Rio Grande do Sul.</p>
  </section>

  <section class="pilha-sm">
    <h2 class="t-headline">Quem somos</h2>
    <p class="t-body texto-secundario">
      Começamos vendendo pelo Instagram para a Grande Porto Alegre. Agora a loja chega a todo o estado,
      com produtos oficiais, Pix com desconto e troca grátis. Novos esportes entram no catálogo aos poucos.
    </p>
  </section>

  <ul class="vantagens vantagens--grade" role="list">
    <li class="vantagem"><?= icone('escudo') ?><span>Produtos oficiais licenciados</span></li>
    <li class="vantagem"><?= icone('pix') ?><span><?= (int) (PIX_DESCONTO * 100) ?>% de desconto no Pix</span></li>
    <li class="vantagem"><?= icone('caminhao') ?><span>Frete grátis acima de <?= dinheiro(FRETE_GRATIS_ACIMA) ?></span></li>
    <li class="vantagem"><?= icone('trocas') ?><span>Troca grátis em <?= TROCA_PRAZO_DIAS ?> dias</span></li>
  </ul>

  <section class="pilha-sm">
    <h2 class="t-headline">Fale com a gente</h2>
    <ul class="lista" role="list">
      <li><?= linha_lista('WhatsApp', 'whatsapp', 'https://wa.me/' . CONTATO_WHATSAPP, $telefone_texto) ?></li>
      <li><?= linha_lista('Instagram', 'instagram', 'https://instagram.com/' . CONTATO_INSTAGRAM, '@' . CONTATO_INSTAGRAM) ?></li>
      <li><?= linha_lista(CONTATO_EMAIL, 'email', 'mailto:' . CONTATO_EMAIL) ?></li>
    </ul>
  </section>

  <section class="pilha-sm">
    <h2 class="t-headline">Retirada no depósito</h2>
    <ul class="lista" role="list">
      <li>
        <a class="linha-lista" href="<?= e('https://www.google.com/maps/search/?api=1&query=' . rawurlencode(DEPOSITO_ENDERECO)) ?>">
          <?= icone('local', 'linha-lista__icone') ?>
          <span class="linha-lista__rotulo"><?= e(DEPOSITO_ENDERECO) ?><br><span class="t-footnote texto-secundario">Abrir no mapa</span></span>
          <?= icone('seta-direita', 'icone--sm linha-lista__seta') ?>
        </a>
      </li>
      <li><?= linha_lista(CONTATO_HORARIO, 'relogio') ?></li>
    </ul>
    <p class="t-footnote texto-secundario">Escolha "Retirada" na compra. O pedido fica pronto em 1 dia útil e a retirada é grátis.</p>
  </section>

  <section class="pilha-sm">
    <h2 class="t-headline">Políticas</h2>
    <ul class="lista" role="list">
      <li><?= linha_lista('Trocas e devoluções', 'trocas', url('trocas.php')) ?></li>
    </ul>
  </section>

  <p class="t-footnote texto-terciario">
    Trabalho acadêmico. Contatos, endereço e regras da loja são exemplos até a empresa confirmar.
  </p>
</div>
<?php pagina_fim(); ?>
