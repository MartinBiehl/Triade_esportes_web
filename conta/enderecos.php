<?php
/**
 * Tela 25 · Endereços
 * Ações por POST: principal (marcar como principal) e excluir.
 */
require __DIR__ . '/../includes/bootstrap.php';

$cliente = exigir_login();
$cid = (int) $cliente['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $id = (int) ($_POST['id'] ?? 0);
    if (endereco_do_cliente($cid, $id)) {
        if (($_POST['acao'] ?? '') === 'principal') {
            tornar_principal($cid, $id);
            aviso_definir('Endereço principal alterado');
        } elseif (($_POST['acao'] ?? '') === 'excluir') {
            excluir_endereco($cid, $id);
            aviso_definir('Endereço excluído');
        }
    }
    redirecionar('conta/enderecos.php');
}

$enderecos = enderecos_do_cliente($cid);

pagina_inicio([
    'titulo' => 'Endereços',
    'aba'    => 'conta',
    'nav'    => [
        'titulo' => 'Endereços',
        'voltar' => url('conta/'),
        'acoes'  => '<a class="botao-icone botao-icone--plain" href="' . e(url('conta/endereco.php')) . '" aria-label="Adicionar endereço">' . icone('mais') . '</a>',
    ],
]);
?>
<div class="conteudo conteudo--leitura pilha" style="padding-top: var(--spacing-lg)">
  <?php if (!$enderecos): ?>
    <section class="estado-vazio">
      <div class="estado-vazio__icone"><?= icone('local') ?></div>
      <h1 class="t-title-3">Nenhum endereço salvo</h1>
      <p class="t-subheadline">Salve onde você recebe as compras e o checkout fica mais rápido.</p>
      <a class="botao botao--primary botao--md" href="<?= e(url('conta/endereco.php')) ?>"><?= icone('mais', 'icone--sm') ?> Adicionar endereço</a>
    </section>
  <?php else: ?>
    <ul class="pilha" role="list" style="padding: 0; margin: 0; list-style: none">
      <?php foreach ($enderecos as $end): ?>
        <?php [$rua, $cidade] = endereco_texto($end); ?>
        <li class="cartao-endereco<?= $end['principal'] ? ' cartao-endereco--principal' : '' ?>">
          <div class="linha linha--entre">
            <span class="linha">
              <?= icone('local', 'icone--sm') ?>
              <span class="t-headline"><?= e($end['apelido'] ?: 'Endereço') ?></span>
              <?php if ($end['principal']): ?><span class="selo selo--principal">Principal</span><?php endif; ?>
            </span>
            <a class="botao-icone botao-icone--plain botao-icone--sm" href="<?= e(url('conta/endereco.php?id=' . (int) $end['id'])) ?>" aria-label="Editar <?= e($end['apelido'] ?: 'endereço') ?>"><?= icone('editar', 'icone--sm') ?></a>
          </div>
          <p class="t-subheadline texto-secundario">
            <?= e($end['destinatario']) ?><br><?= e($rua) ?><br><?= e($cidade) ?>
          </p>
          <div class="cartao-endereco__acoes">
            <?php if (!$end['principal']): ?>
              <form method="post">
                <?= csrf_campo() ?>
                <input type="hidden" name="id" value="<?= (int) $end['id'] ?>">
                <button type="submit" name="acao" value="principal" class="botao botao--ghost botao--md">Usar como principal</button>
              </form>
            <?php endif; ?>
            <form method="post" data-confirmar="Excluir o endereço <?= e($end['apelido'] ?: $rua) ?>?">
              <?= csrf_campo() ?>
              <input type="hidden" name="id" value="<?= (int) $end['id'] ?>">
              <button type="submit" name="acao" value="excluir" class="botao botao--ghost botao--md texto-erro"><?= icone('lixeira', 'icone--sm') ?> Excluir</button>
            </form>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
    <a class="botao botao--glass botao--bloco" href="<?= e(url('conta/endereco.php')) ?>"><?= icone('mais', 'icone--sm') ?> Adicionar endereço</a>
    <p class="t-footnote texto-terciario" style="text-align: center">Por enquanto entregamos só no Rio Grande do Sul.</p>
  <?php endif; ?>
</div>
<?php pagina_fim(['scripts' => ['conta.js']]); ?>
